<?php
/**
 * 2014-2015 (c) Axalone France - Express-Mailing
 *
 * This file is a commercial module for Prestashop
 * Do not edit or add to this file if you wish to upgrade PrestaShop or
 * customize PrestaShop for your needs please refer to
 * http://www.express-mailing.com for more information.
 *
 * @author    Axalone France <info@express-mailing.com>
 * @copyright 2014-2015 (c) Axalone France
 * @license   http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
 */

class SessionApi
{
	private $express_api = null;
	public $session_id = null;
	public $account_id = null;
	public $account_login = null;
	public $error = null;
	public $credentials = 0;
	public $last_session_buy = null;
	public $application_id = 0;

	private function l($string)
	{
		return Translate::getModuleTranslation('expressmailing', $string, 'session_api');
	}

	public function __construct()
	{
		include 'express_api_php5.php';
		$this->session_id = Configuration::get('adminmarketing_session_api');
		$this->application_id = $this->l('3320');
		$this->express_api = new ExpressApi();
	}

	public function openSession($debug_post = null, $debug_response = null)
	{
		$this->session_id = Configuration::get('adminmarketing_session_api');

		$this->error = null;
		$parameters = array();
		$response_array = array();

		// S'il existe déjà une session, on vérifie qu'elle est toujours active
		// --------------------------------------------------------------------
		if (!empty($this->session_id))
			if ($this->call('server', 'session', 'get_current', $parameters, $response_array, $debug_post, $debug_response))
				if ($this->session_id == $response_array['session_id'])
					return true;

		// Sinon on ouvre une nouvelle session (application_id dépend de la langue FR/PL)
		// ------------------------------------------------------------------------------
		$this->error = null;
		$parameters = array('application_id' => $this->application_id);
		$response_array = array();

		if ($this->call('server', 'session', 'open_session', $parameters, $response_array, $debug_post, $debug_response))
		{
			$this->session_id = $response_array['session_id'];
			Configuration::updateValue('adminmarketing_session_api', $this->session_id);
			return true;
		}

		// S'il est impossible d'ouvrir une session on affichera une erreur dans le code appelant
		// --------------------------------------------------------------------------------------
		return false;
	}

	public function connectUser($parameters, &$response_array)
	{
		if (empty($this->session_id))
			return false;

		if (!is_array($parameters))
			$parameters = array();

		$this->error = null;
		if ($this->call('server', 'session', 'connect_user', $parameters, $response_array))
		{
			$this->account_id = $response_array['account_id'];
			$this->account_login = $parameters['login'];

			return true;
		}

		return false;
	}

	public function createAccount($parameters, &$response_array)
	{
		if (!is_array($parameters))
			$parameters = array();
		if (!empty($this->account_id) && !isset($parameters['account_id']))
			$parameters['account_id'] = $this->account_id;

		$this->error = null;
		if ($this->call('infrastructure', 'account', 'create', $parameters, $response_array))
		{
			if (isset($response_array['account']))
			{
				// Le compte à bien été créé, on mémorise ses infos dans la base locale
				// + On crée une liste de diffusion pour le mailing en cours
				// + On update le message HTML
				// + On passe à l'étape 6 si tout est bon
				// --------------------------------------------------------------------
				$this->account_id = $response_array['account_id'];
				$this->account_login = $response_array['account']['login'];

				$account_login = $response_array['account']['login'];
				$account_password = $response_array['password'];

				Db::getInstance()->insert('expressmailing', array(
					'api_media' => 'all',
					'api_login' => pSQL($account_login),
					'api_password' => pSQL($account_password)
					)
				);

				return true;
			}
		}

		return false;
	}

	public function resendPassword($parameters, &$response_array)
	{
		$this->error = null;
		return $this->call('infrastructure', 'account', 'resend_password', $parameters, $response_array);
	}

	public function call($module, $section, $method, $parameters, &$response_array, $debug_post = null, $debug_response = null)
	{
		$this->error = null;
		return $this->express_api->call($module, $section, $method, $parameters, $response_array, 'xml',
										$this->session_id, $this->error, $debug_post, $debug_response);
	}

	public function callExternal($tmp_base_url, $module, $section, $method, $parameters, &$response_array, $debug_post = null, $debug_response = null)
	{
		$this->error = null;
		$init_base_url = $this->express_api->base_url;
		$this->express_api->base_url = $tmp_base_url;

		$return = $this->express_api->call($module, $section, $method, $parameters, $response_array, 'post',
										$this->session_id, $this->error, $debug_post, $debug_response);

		$this->express_api->base_url = $init_base_url;
		return $return;
	}

	public function connectFromCredentials($media = 'all')
	{
		$req = new DbQuery();
		$req->select('*');
		$req->from('expressmailing');
		$req->orderby('api_media DESC');

		switch ($media)
		{
			case 'email': $req->where('api_media IN (\'all\', \'email\')');
				break;
			case 'fax': $req->where('api_media IN (\'all\', \'fax\')');
				break;
			case 'sms': $req->where('api_media IN (\'all\', \'sms\')');
				break;
			default: $req->where('api_media = \'all\'');
				break;
		}

		$api_credentials = Db::getInstance()->executeS($req, true, false);

		if (count($api_credentials) > 0)
		{
			// On indique que la base locale contient des credentials
			// ------------------------------------------------------
			$this->credentials = count($api_credentials);

			// Si on trouve un couple login/password en local, on initie la connexion à l'api
			// ------------------------------------------------------------------------------
			if ($this->openSession())
			{
				// Puis on regarde si ce compte est toujours actif
				// -----------------------------------------------
				foreach ($api_credentials as $account)
				{
					$this->error = null;
					$response_array = array();
					$parameters = array(
						'login' => $account['api_login'],
						'password' => $account['api_password']
					);

					if ($this->connectUser($parameters, $response_array))
					{
						if (isset($response_array['account_id']) && ((int)$response_array['account_id'] > 0))
						{
							// Le compte est toujours actif !
							// ------------------------------
							Configuration::updateValue('adminmarketing_session_api', $this->session_id);
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	public function getError($error = null)
	{
		if ($error)
			return $this->express_api->getError((int)$error);
		else
			return $this->express_api->getError($this->error);
	}

}