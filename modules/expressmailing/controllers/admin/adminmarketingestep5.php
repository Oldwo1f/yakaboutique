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

include 'session_api.php';

/**
 * Step 5 : Inscription (no connected) and send campaign settings to the API (if connected)
 */
class AdminMarketingEStep5Controller extends ModuleAdminController
{
	private $campaign_id = null;

	public function __construct()
	{
		$this->name = 'adminmarketingestep5';
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

		$this->session_api = new SessionApi();

		// On regarde si le compte est toujours en activité
		// --------------------------------------------------------------------------------
		if ($this->session_api->connectFromCredentials('email'))
		{
			// Si le compte est toujours en activité :
			// 1/ on crée une liste de diffusion pour le mailing actuel
			// 2/ on update le message HTML
			// 3/ puis on passe à l'étape 6
			// -------------------------------------------
			if ($this->updateApiListMessage())
			{
				Tools::redirectAdmin('index.php?controller=AdminMarketingEStep6&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingEStep6'));
				exit;
			}
		}
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send an e-mailing', 'adminmarketingestep1');
	}

	private function updateApiListMessage()
	{
		// Retrieve sender email & name defines at step 2
		// ----------------------------------------------
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_email');
		$sql->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($sql, false);

		// Create or Update the mailing-list parameters (on-line) associate with the current mailing (off-line)
		// ----------------------------------------------------------------------------------------------------
		$response_array = array();
		$parameters = array(
			'account_id' => $this->session_api->account_id,
			'list_name' => 'ps_mailing_'.$this->campaign_id,
			'list_language' => $result['campaign_lang'],
			'list_sender_email' => $result['campaign_sender_email'],
			'list_sender_name' => $result['campaign_sender_name'],
			'list_text_after_subscription' => $this->module->l('Your subscription to our newsletter has been saved.
We will keep you informed of all the now news or improvements made to our site and our products.

The editorial board', 'adminmarketingestep5'),
			'list_text_after_unsubscription' => $this->module->l('Your unsubscription to our newsletter has been saved.
We regret your departure, but hope to see you at one of events we regularly participate.

The editorial board', 'adminmarketingestep5')
		);

		if (empty($result['campaign_api_list_id']))
		{
			if ($this->session_api->call('email', 'list', 'new', $parameters, $response_array))
			{
				// Store the on-line mailing-list id into local database
				// -----------------------------------------------------
				$result['campaign_api_list_id'] = $response_array['list_id'];

				Db::getInstance()->update('expressmailing_email', array(
					'campaign_api_list_id' => $result['campaign_api_list_id']
					), 'campaign_id = '.$this->campaign_id
				);
			}
		}
		else
		{
			$parameters['list_id'] = $result['campaign_api_list_id'];
			if (!$this->session_api->call('email', 'list', 'set_infos', $parameters, $response_array))
			{
				$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingestep5'),
									$this->session_api->getError());
				return false;
			}
		}

		// Create or Update the campaign parameters (on-line) associate with the current mailing (off-line)
		// ------------------------------------------------------------------------------------------------
		$date = new DateTime($result['campaign_date_send']);
		$gmt_date_send = gmdate('Y/m/j H:i:s', $date->getTimestamp());

		$tz_london = new DateTimeZone('Europe/London');
		$date_london = new DateTime($gmt_date_send, $tz_london);

		$tz_paris = new DateTimeZone('Europe/Paris');
		$date_paris = $date_london->setTimezone($tz_paris);

		$planning_allow_monday = Tools::strpos($result['campaign_week_limit'], 'L') !== false ? 'True' : 'False';
		$planning_allow_tuesday = Tools::strpos($result['campaign_week_limit'], 'M') !== false ? 'True' : 'False';
		$planning_allow_wednesday = Tools::strpos($result['campaign_week_limit'], 'C') !== false ? 'True' : 'False';
		$planning_allow_thursday = Tools::strpos($result['campaign_week_limit'], 'J') !== false ? 'True' : 'False';
		$planning_allow_friday = Tools::strpos($result['campaign_week_limit'], 'V') !== false ? 'True' : 'False';
		$planning_allow_saturday = Tools::strpos($result['campaign_week_limit'], 'S') !== false ? 'True' : 'False';
		$planning_allow_sunday = Tools::strpos($result['campaign_week_limit'], 'D') !== false ? 'True' : 'False';

		$response_array = array();
		$parameters = array(
			'account_id' => $this->session_api->account_id,
			'campaign_name' => $result['campaign_name'],
			'campaign_send_date' => $date_paris->getTimestamp(),
			'campaign_sender' => $result['campaign_api_list_id'],
			'campaign_language' => $result['campaign_lang'],
			'campaign_html' => $result['campaign_html'],
			'campaign_tracking' => $result['campaign_tracking'] ? 'True' : 'False',
			'campaign_linking' => $result['campaign_linking'] ? 'True' : 'False',
			'campaign_redlist' => $result['campaign_redlist'] ? 'True' : 'False',
			'campaign_limit_daily' => $result['campaign_day_limit'],
			'planning_allow_monday' => $planning_allow_monday,
			'planning_allow_tuesday' => $planning_allow_tuesday,
			'planning_allow_wednesday' => $planning_allow_wednesday,
			'planning_allow_thursday' => $planning_allow_thursday,
			'planning_allow_friday' => $planning_allow_friday,
			'planning_allow_saturday' => $planning_allow_saturday,
			'planning_allow_sunday' => $planning_allow_sunday
		);

		if (empty($result['campaign_api_message_id']))
		{
			// Create the campaign
			// -------------------
			if ($this->session_api->call('email', 'campaign', 'new', $parameters, $response_array))
			{
				// On mémorise l'id message dans la base locale
				// --------------------------------------------
				if (Db::getInstance()->update('expressmailing_email', array(
					'campaign_api_message_id' => (int)$response_array['campaign_id']
					), 'campaign_id = '.$this->campaign_id
				))
					return true;
			}
		}
		else
		{
			// Or Update it
			// ------------
			$parameters['campaign_id'] = $result['campaign_api_message_id'];
			if ($this->session_api->call('email', 'campaign', 'set_infos', $parameters, $response_array))
				return true;
		}

		$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingestep5'),
								$this->session_api->getError());
		return false;
	}

}