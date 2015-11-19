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

include_once 'db_marketing.php';

/**
 * Step 7 : Receive a test
 */
class AdminMarketingEStep7Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $session_api = null;
	private $campaign_infos = array();
	private $checked_groups = array();
	private $checked_langs = array();
	private $checked_campaign_optin = null;
	private $checked_campaign_newsletter = null;
	private $checked_campaign_active = null;

	public function __construct()
	{
		$this->name = 'adminmarketingestep7';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;

		$this->campaign_id = (int)Tools::getValue('campaign_id');

		if (empty($this->campaign_id))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
			exit;
		}

		parent::__construct();

		// API initialization
		// ------------------
		include _PS_MODULE_DIR_.$this->module->name.'/controllers/admin/session_api.php';
		$this->session_api = new SessionApi();

		// Checking the session
		// --------------------
		if (!$this->session_api->connectFromCredentials('email'))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingEStep5&token='.Tools::getAdminTokenLite('AdminMarketingEStep5'));
			exit;
		}

		// Puis on retrouve les informations de la campagne
		// ------------------------------------------------
		$this->campaign_infos = $this->getCampaignInfos();
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send an e-mailing', 'adminmarketingestep1');
	}

	public function renderList()
	{
		// 1er bloc : Tests avant envoi
		// ----------------------------
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Test your e-mailing before his validation (step 7)', 'adminmarketingestep7'),
				'icon' => 'icon-envelope-alt'
			),
			'input' => array(
				array(
					'type' => _PS_MODE_DEV_ ? 'text' : 'hidden',
					'lang' => false,
					'label' => 'Ref :',
					'name' => 'campaign_id',
					'col' => 1,
					'readonly' => 'readonly'
				),
				array(
					'type' => 'text',
					'lang' => false,
					'label' => $this->module->l('Your email address', 'adminmarketingestep7'),
					'name' => 'campaign_last_tester',
					'prefix' => '<i class="icon-envelope-o"></i>',
					'required' => true
				)
			),
			'submit' => array(
				'title' => $this->module->l('Send a test', 'adminmarketingestep7'),
				'name' => 'submitEmailingTest',
				'icon' => 'process-icon-envelope'
			)
		);

		$this->getFieldsValues();

		$output = parent::renderForm();

		// 2eme bloc : Apercu du HTML
		// --------------------------
		$this->context->smarty->assign('campaign_id', $this->campaign_id);
		$email_preview = $this->getTemplatePath().'marketinge_step7/email_preview.tpl';
		$output .= $this->context->smarty->fetch($email_preview);

		// Puis on renvoi la fusion des 2 blocs + le footer
		// ------------------------------------------------
		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function postProcess()
	{
		// ---------------------------------------------------
		// Nota : La session est vérifiée dans le constructeur
		// donc ici la session est déjà établie !
		// ---------------------------------------------------

		if (Tools::isSubmit('submitEmailingTest'))
		{
			// On vérifie l'adresse email saisie
			// ---------------------------------
			$campaign_last_tester = (string)Tools::getValue('campaign_last_tester', Configuration::get('PS_SHOP_EMAIL'));

			if (!Validate::isEmail($campaign_last_tester))
			{
				$this->errors[] = $this->module->l('Please verify your email address', 'adminmarketingestep7');
				return false;
			}

			// On mémorise le dernier email de test dans la bdd locale
			// -------------------------------------------------------
			Db::getInstance()->update('expressmailing_email', array(
				'campaign_last_tester' => pSQL($campaign_last_tester)
				), 'campaign_id = '.$this->campaign_id
			);

			// Puis on appele la fonction d'envoi de test
			// ------------------------------------------
			return $this->sendTest($campaign_last_tester);
		}
		elseif (Tools::isSubmit('submitEmailingValidate'))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingEStep8&campaign_id='.
				$this->campaign_id.
				'&token='.Tools::getAdminTokenLite('AdminMarketingEStep8'));
			exit;
		}
	}

	public function displayAjax()
	{
		// Fetch preview from API
		// ----------------------
		$preview = '';
		$parameters = array(
			'account_id' => $this->session_api->account_id,
			'campaign_id' => $this->campaign_infos['campaign_api_message_id']
		);

		if ($this->session_api->call('email', 'campaign', 'get_preview', $parameters, $preview))
			die($preview);
		else
			die(sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingestep8'),
							$this->session_api->getError()));

	}

	private function getFieldsValues()
	{
		// Campaign_id
		// -----------
		$this->fields_value['campaign_id'] = $this->campaign_id;

		// On retrouve l'aperçu html du mailing
		// ------------------------------------
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_email');
		$sql->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($sql);

		$this->fields_value['preview_html'] = '<div class="panel" style="height: 300px; overflow: scroll; border: 1px solid grey">'.
												$result['campaign_html'].
												'</div>';

		if (!empty($result['campaign_last_tester']))
			$this->fields_value['campaign_last_tester'] = $result['campaign_last_tester'];
		else
			$this->fields_value['campaign_last_tester'] = Configuration::get('PS_SHOP_EMAIL');

		return true;
	}

	private function sendTest($recipient)
	{
		if (!empty($this->session_api->account_id) && ($this->session_api->account_id > 0))
		{
			$last_tester = new Customer();

			// 1 - On ajoute le destinataire du test dans la liste du mailing en cours
			// -----------------------------------------------------------------------
			if ($last_tester->getByEmail((string)$recipient))
			{
				$response_array = array();
				$parameters = array(
					'account_id' => $this->session_api->account_id,
					'list_id' => $this->campaign_infos['campaign_api_list_id'],
					'recipients' => array(
						array(
							'target' => $last_tester->email,
							'lastname' => $last_tester->lastname,
							'firstname' => $last_tester->firstname
						)
					)
				);

				$this->session_api->call('email', 'recipients', 'add', $parameters, $response_array);
			}

			// 2 - On envoi un test au destinataire
			/// -----------------------------------
			$response_array = array();
			$parameters = array(
				'account_id' => $this->session_api->account_id,
				'campaign_id' => $this->campaign_infos['campaign_api_message_id'],
				'list_id' => $this->campaign_infos['campaign_api_list_id'],
				'recipient' => $recipient
			);

			if ($this->session_api->call('email', 'campaign', 'send_test', $parameters, $response_array))
			{
				$this->confirmations[] = sprintf($this->module->l('An email as been sent to : %s', 'adminmarketingestep7'), $recipient);
				return true;
			}
		}

		$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingestep7'),
			$this->session_api->getError());

		return false;
	}

	private function getCampaignInfos()
	{
		$req = new DbQuery();
		$req->select('*');
		$req->from('expressmailing_email');
		$req->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($req->build());

		return $result;
	}
}
