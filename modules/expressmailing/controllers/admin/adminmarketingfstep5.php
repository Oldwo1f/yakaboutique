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
 * Step 5 : Subscription (no connected) and send campaign settings to the API (if connected)
 */
class AdminMarketingFStep5Controller extends ModuleAdminController
{
	private $campaign_id = null;

	public function __construct()
	{
		$this->name = 'adminmarketingfstep5';
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
		if ($this->session_api->connectFromCredentials('fax'))
		{
			if ($this->updateApiListMessage())
			{
				Tools::redirectAdmin('index.php?controller=AdminMarketingFStep6&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingFStep6'));
				exit;
			}
		}
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a fax-mailing', 'adminmarketingfstep1');
	}

	private function updateApiListMessage()
	{
		// Get campaign informations
		// -------------------------
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_fax');
		$sql->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($sql);

		if (empty($result['campaign_api_message_id']))
		{
			// Create the campaign on the API
			// ------------------------------
			$response_array = array ();
			$parameters = array (
				'account_id' => $this->session_api->account_id,
				'campaign_name' => $result['campaign_name']
			);

			if ($this->session_api->call('fax', 'campaign', 'new', $parameters, $response_array))
			{
				// We store the api_message_id into the local database
				// ---------------------------------------------------
				$result['campaign_api_message_id'] = $response_array['campaign_id'];

				Db::getInstance()->update('expressmailing_fax', array (
					'campaign_api_message_id' => $result['campaign_api_message_id']
					), 'campaign_id = '.$this->campaign_id
				);
			}
			else
			{
				$this->errors[] = $this->module->l('Fail to create campaign', 'adminmarketingfstep5');
				return false;
			}
		}

		// We update the campaign informations
		// -----------------------------------
		$response_array = array ();
		$parameters = array (
			'account_id' => $this->session_api->account_id,
			'campaign_id' => $result['campaign_api_message_id'],
			'campaign_name' => $result['campaign_name'],
			'campaign_max_end_date' => null,
			'campaign_max_delivrered' => null,
			'campaign_max_delivrered_per_day' => $result['campaign_day_limit'],
			'campaign_max_delivrered_per_hour' => null,
			'campaign_planning_start_hour' => $result['campaign_start_hour'],
			'campaign_planning_stop_hour' => $result['campaign_end_hour'],
			'campaign_planning_allow_monday' => (strpos($result['campaign_week_limit'], 'L') !== false),
			'campaign_planning_allow_tuesday' => (strpos($result['campaign_week_limit'], 'M') !== false),
			'campaign_planning_allow_wednesday' => (strpos($result['campaign_week_limit'], 'C') !== false),
			'campaign_planning_allow_thursday' => (strpos($result['campaign_week_limit'], 'J') !== false),
			'campaign_planning_allow_friday' => (strpos($result['campaign_week_limit'], 'V') !== false),
			'campaign_planning_allow_saturday' => (strpos($result['campaign_week_limit'], 'S') !== false),
			'campaign_planning_allow_sunday' => (strpos($result['campaign_week_limit'], 'D') !== false),
			'id_expeditor' => null
		);

		if ($this->session_api->call('fax', 'campaign', 'set_infos', $parameters, $response_array))
		{
			// We update the broadcasting plan
			// -------------------------------
			$response_array = array ();
			$date = new DateTime($result['campaign_date_send']);
			$gmt_date_send = gmdate('Y/m/j H:i:s', $date->getTimestamp());

			$tz_london = new DateTimeZone('Europe/London');
			$date_london = new DateTime($gmt_date_send, $tz_london);

			$tz_paris = new DateTimeZone('Europe/Paris');
			$date_paris = $date_london->setTimezone($tz_paris);

			$plannings = array (
				'campaign_id' => $result['campaign_api_message_id'],
				'plannings' => array (
					array (
						'date' => $date_paris->getTimestamp() + $date_paris->getOffset(),
						'tries' => 1
					)
				)
			);

			if ($this->session_api->call('fax', 'campaign', 'set_planning', $plannings, $response_array))
			{
				// Adding the document
				// -------------------
				$response_array = null;
				$parameters = array (
					'campaign_id' => $result['campaign_api_message_id'],
					'document' => $this->getDocument()
				);

				if ($this->session_api->call('fax', 'campaign', 'set_document', $parameters, $response_array))
					return true;
			}
		}

		$this->errors[] = $this->module->l('Fail to modify campaign', 'adminmarketingfstep5');
		return false;
	}

	private function getDocument()
	{
		$req = new DbQuery();
		$req->select('page_path_original');
		$req->from('expressmailing_fax_pages');
		$req->where('campaign_id = '.$this->campaign_id);
		$req->orderBy('id');

		$pages_db = Db::getInstance()->executeS($req, true, false);

		$document = array ();
		$document['default_customization_data'] = array ();

		foreach ($pages_db as $page)
		{
			$document['pages'][] = array (
				'content' => mb_convert_encoding(Tools::file_get_contents($page['page_path_original']), 'BASE64', 'UTF-8'),
				'format' => 'Png',
				'customizations' => array ()
			);
		}

		return $document;
	}
}