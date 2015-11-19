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

/**
 * Step 6 : Send recipients to the API & clean invalid numbers
 */
class AdminMarketingFStep6Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $session_api = null;
	private $campaign_api_message_id = null;
	private $use_noads_redlist = null;
	private $use_personnal_redlist = null;

	public function __construct()
	{
		$this->name = 'adminmarketingfstep6';
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
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a fax-mailing', 'adminmarketingfstep1');
	}

	private function getFieldsValues()
	{
		// Get campaign informations
		// -------------------------
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_fax');
		$sql->where('campaign_id = '.$this->campaign_id);

		return Db::getInstance()->getRow($sql, false);
	}

	public function renderList()
	{
		// Checking the session
		// --------------------
		if (!$this->session_api->connectFromCredentials('fax'))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingFStep2&token='.Tools::getAdminTokenLite('AdminMarketingFStep2'));
			exit;
		}

		// Get campaign informations
		// -------------------------
		$result = $this->getFieldsValues();

		// Upload local customers to the API or not ?
		// ------------------------------------------
		$this->campaign_api_message_id = $result['campaign_api_message_id'];

		if ($result['recipients_modified'] == '1')
		{
			// Reset the uploaded flag on all recipients
			// -----------------------------------------
			Db::getInstance()->execute('UPDATE '._DB_PREFIX_.'expressmailing_fax_recipients SET uploaded = 0 WHERE campaign_id = '.$this->campaign_id, false);

			// Obtain a token for importing per blocs
			// --------------------------------------
			$guid_import = array ();
			$parameters = array ();
			$this->session_api->call('fax', 'campaign', 'init_recipients_from_file', $parameters, $guid_import);

			$this->context->smarty->assign('campaign_id', $this->campaign_id);
			$this->context->smarty->assign('guid_import', $guid_import);

			// Then display the upload ajax page
			// ---------------------------------
			$ajax_upload = $this->getTemplatePath().'marketingf_step6/ajax_upload.tpl';
			$output = $this->context->smarty->fetch($ajax_upload);
		}
		else
		{
			$response_array = array ();
			$parameters = array (
				'campaign_id' => $this->campaign_api_message_id
			);

			if ($this->session_api->call('fax', 'campaign', 'get_infos', $parameters, $response_array))
			{
				$this->use_noads_redlist = $response_array['use_noads_redlist'];
				$this->use_personnal_redlist = $response_array['use_personnal_redlist'];
			}
			$this->context->smarty->assign('campaign_id', $this->campaign_id);
			$this->context->smarty->assign('countries_list', $this->getCountriesAPI());
			$this->context->smarty->assign('grouped_target_list', $this->getTargetsGroupedByCountriesAPI());
			$this->context->smarty->assign('campaign_infos', $this->getCampaignInfosAPI());
			$this->context->smarty->assign('use_personnal_redlist', $this->use_personnal_redlist);
			$this->context->smarty->assign('use_noads_redlist', $this->use_noads_redlist);
			$display = $this->getTemplatePath().'marketingf_step6/display_cleaner.tpl';
			$output = $this->context->smarty->fetch($display);
		}

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function displayAjax()
	{
		// Retrieve the import token
		// -------------------------
		$guid_import = (string)Tools::getValue('guid_import');
		if (empty($guid_import))
			die('ended');

		// Get a bloc of 500 customers (formated)
		// --------------------------------------
		$req = new DbQuery();
		$req->select('*');
		$req->from('expressmailing_fax_recipients');
		$req->where('campaign_id = '.$this->campaign_id);
		$req->where('uploaded = \'0\'');
		$req->limit(1500);

		$recipients_list = Db::getInstance()->executeS($req, true, false);

		if (count($recipients_list) == 0)
			die($this->finalizeImport($guid_import));

		$uploaded_id = array ();
		$formated_recipients = array ();

		foreach ($recipients_list as $recipient)
		{
			$uploaded_id[] = (int)$recipient['id'];
			$data = array ('Target' => $recipient['target']);

			if (!empty($recipient['col_0']))
				$data['Col_0'] = $recipient['col_0'];
			if (!empty($recipient['col_1']))
				$data['Col_1'] = $recipient['col_1'];
			if (!empty($recipient['col_2']))
				$data['Col_2'] = $recipient['col_2'];
			if (!empty($recipient['col_3']))
				$data['Col_3'] = $recipient['col_3'];
			if (!empty($recipient['col_4']))
				$data['Col_4'] = $recipient['col_4'];
			if (!empty($recipient['col_5']))
				$data['Col_5'] = $recipient['col_5'];
			if (!empty($recipient['col_6']))
				$data['Col_6'] = $recipient['col_6'];
			if (!empty($recipient['col_7']))
				$data['Col_7'] = $recipient['col_7'];
			if (!empty($recipient['col_8']))
				$data['Col_8'] = $recipient['col_8'];
			if (!empty($recipient['col_9']))
				$data['Col_9'] = $recipient['col_9'];
			if (!empty($recipient['col_10']))
				$data['Col_10'] = $recipient['col_10'];
			if (!empty($recipient['col_11']))
				$data['Col_11'] = $recipient['col_11'];
			if (!empty($recipient['col_12']))
				$data['Col_12'] = $recipient['col_12'];
			if (!empty($recipient['col_13']))
				$data['Col_13'] = $recipient['col_13'];
			if (!empty($recipient['col_14']))
				$data['Col_14'] = $recipient['col_14'];
			if (!empty($recipient['col_15']))
				$data['Col_15'] = $recipient['col_15'];
			if (!empty($recipient['col_16']))
				$data['Col_16'] = $recipient['col_16'];
			if (!empty($recipient['col_17']))
				$data['Col_17'] = $recipient['col_17'];
			if (!empty($recipient['col_18']))
				$data['Col_18'] = $recipient['col_18'];
			if (!empty($recipient['col_19']))
				$data['Col_19'] = $recipient['col_19'];

			$formated_recipients[] = $data;
		}

		// Upload the bloc
		// ---------------
		$response_array = array ();
		$parameters = array (
			'operation' => $guid_import,
			'recipients' => $formated_recipients
		);

		if ($this->session_api->call('fax', 'campaign', 'sendpart_recipients_from_file', $parameters, $response_array))
		{
			// Mark as uploaded the recipients treated
			// ---------------------------------------
			if (Db::getInstance()->update('expressmailing_fax_recipients', array (
					'uploaded' => '1'
					), 'campaign_id = '.$this->campaign_id.' AND id IN ('.join(',', $uploaded_id).')', 0, false, false
				))
				die('continue');

			echo Db::getInstance()->getMsgError();
		}

		// Return the error to the AJAX process
		// ------------------------------------
		die(sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingfstep6'), $this->session_api->getError()));
	}

	private function finalizeImport($guid_import)
	{
		// Get campaign informations
		// -------------------------
		$result = $this->getFieldsValues();

		// Finalize the import
		// -------------------
		$response_array = array ();
		$parameters = array (
			'Operation' => (string)$guid_import,
			'Campaign_Id' => $result['campaign_api_message_id'],
			'Name_Column_0' => '',
			'Name_Column_1' => '',
			'Name_Column_2' => '',
			'Name_Column_3' => '',
			'Name_Column_4' => '',
			'Name_Column_5' => '',
			'Name_Column_6' => '',
			'Name_Column_7' => '',
			'Name_Column_8' => '',
			'Name_Column_9' => '',
			'Name_Column_10' => '',
			'Name_Column_11' => '',
			'Name_Column_12' => '',
			'Name_Column_13' => '',
			'Name_Column_14' => '',
			'Name_Column_15' => '',
			'Name_Column_16' => '',
			'Name_Column_17' => '',
			'Name_Column_18' => '',
			'Name_Column_19' => '',
			'Name_Column_20' => '',
			'Name_Column_21' => '',
			'Name_Column_22' => '',
			'Name_Column_23' => '',
			'Name_Column_24' => '',
			'Name_Column_25' => '',
			'Name_Column_26' => '',
			'Name_Column_27' => '',
			'Name_Column_28' => '',
			'Name_Column_29' => '',
			'Name_Column_30' => '',
			'Name_Column_31' => '',
			'Name_Column_32' => '',
			'Name_Column_33' => '',
			'Name_Column_34' => '',
			'Name_Column_35' => '',
			'Name_Column_36' => '',
			'Name_Column_37' => '',
			'Name_Column_38' => '',
			'Name_Column_39' => '',
			'Show_Column_0' => true,
			'Show_Column_1' => true,
			'Show_Column_2' => true,
			'Show_Column_3' => true,
			'Show_Column_4' => true,
			'Show_Column_5' => true,
			'Show_Column_6' => true,
			'Show_Column_7' => true,
			'Show_Column_8' => true,
			'Show_Column_9' => true,
			'Show_Column_10' => true,
			'Show_Column_11' => true,
			'Show_Column_12' => true,
			'Show_Column_13' => true,
			'Show_Column_14' => true,
			'Show_Column_15' => true,
			'Show_Column_16' => true,
			'Show_Column_17' => true,
			'Show_Column_18' => true,
			'Show_Column_19' => true,
			'Show_Column_20' => false,
			'Show_Column_21' => false,
			'Show_Column_22' => false,
			'Show_Column_23' => false,
			'Show_Column_24' => false,
			'Show_Column_25' => false,
			'Show_Column_26' => false,
			'Show_Column_27' => false,
			'Show_Column_28' => false,
			'Show_Column_29' => false,
			'Show_Column_30' => false,
			'Show_Column_31' => false,
			'Show_Column_32' => false,
			'Show_Column_33' => false,
			'Show_Column_34' => false,
			'Show_Column_35' => false,
			'Show_Column_36' => false,
			'Show_Column_37' => false,
			'Show_Column_38' => false,
			'Show_Column_39' => false,
			'In_Unique_Filter_0' => false,
			'In_Unique_Filter_1' => false,
			'In_Unique_Filter_2' => false,
			'In_Unique_Filter_3' => false,
			'In_Unique_Filter_4' => false,
			'In_Unique_Filter_5' => false,
			'In_Unique_Filter_6' => false,
			'In_Unique_Filter_7' => false,
			'In_Unique_Filter_8' => false,
			'In_Unique_Filter_9' => false,
			'In_Unique_Filter_10' => false,
			'In_Unique_Filter_11' => false,
			'In_Unique_Filter_12' => false,
			'In_Unique_Filter_13' => false,
			'In_Unique_Filter_14' => false,
			'In_Unique_Filter_15' => false,
			'In_Unique_Filter_16' => false,
			'In_Unique_Filter_17' => false,
			'In_Unique_Filter_18' => false,
			'In_Unique_Filter_19' => false,
			'In_Unique_Filter_20' => false,
			'In_Unique_Filter_21' => false,
			'In_Unique_Filter_22' => false,
			'In_Unique_Filter_23' => false,
			'In_Unique_Filter_24' => false,
			'In_Unique_Filter_25' => false,
			'In_Unique_Filter_26' => false,
			'In_Unique_Filter_27' => false,
			'In_Unique_Filter_28' => false,
			'In_Unique_Filter_29' => false,
			'In_Unique_Filter_30' => false,
			'In_Unique_Filter_31' => false,
			'In_Unique_Filter_32' => false,
			'In_Unique_Filter_33' => false,
			'In_Unique_Filter_34' => false,
			'In_Unique_Filter_35' => false,
			'In_Unique_Filter_36' => false,
			'In_Unique_Filter_37' => false,
			'In_Unique_Filter_38' => false,
			'In_Unique_Filter_39' => false,
			'In_Unique_Filter_Recipient_Number' => false
		);

		if ($this->session_api->call('fax', 'campaign', 'finalize_recipients_from_file', $parameters, $response_array))
		{
			// Stop the AJAX process
			// ---------------------
			if (Db::getInstance()->update('expressmailing_fax', array (
					'campaign_date_update' => date('Y-m-d H:i:s'),
					'recipients_modified' => 0
					), 'campaign_id = '.$this->campaign_id
				))
				die('ended');

			echo Db::getInstance()->getMsgError();
		}

		// Return the error to the AJAX process
		// ------------------------------------
		die(sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingfstep6'), $this->session_api->getError()));
	}

	private function getCountriesAPI()
	{
		$response_array = array ();
		$parameters = array ();

		if ($this->session_api->call('infrastructure', 'countries', 'get_all', $parameters, $response_array))
		{
			$formated_array = array ();
			foreach ($response_array as $item)
				$formated_array[$item['country_id']] = $item;

			return $formated_array;
		}
		else
		{
			$this->errors[] = $this->module->l('Failed to get countries list from API', 'adminmarketingfstep6');
			return false;
		}
	}

	private function setAllowedCountries($country_ids)
	{
		$response_array = array ();
		$parameters = array (
			'campaign_id' => $this->campaign_api_message_id,
			'countries_ids' => $country_ids
		);

		if ($this->session_api->call('fax', 'campaign', 'set_allowedcountries', $parameters, $response_array))
			return true;

		$this->errors[] = $this->module->l('Failed to get data from API');
		return false;
	}

	private function getCampaignInfosAPI()
	{
		$response_array = array ();
		$parameters = array ('campaign_id' => $this->campaign_api_message_id);

		if ($this->session_api->call('fax', 'campaign', 'get_infos', $parameters, $response_array))
			return $response_array;

		$this->errors[] = $this->module->l('Failed to get data from API', 'adminmarketingfstep6');
		return false;
	}

	private function getTargetsGroupedByCountriesAPI()
	{
		$response_array = array ();
		$parameters = array ('campaign_id' => $this->campaign_api_message_id);

		if ($this->session_api->call('fax', 'campaign', 'enum_recipients_by_countries_statistics', $parameters, $response_array))
			return $response_array;

		$this->errors[] = $this->module->l('Failed to get data from API', 'adminmarketingfstep6');
		return false;
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitFaxStep6'))
		{
			$sql = new DbQuery();
			$sql->select('*');
			$sql->from('expressmailing_fax');
			$sql->where('campaign_id = '.$this->campaign_id);

			$result = Db::getInstance()->getRow($sql, false);

			$response_array = array ();
			$parameters = array (
				'campaign_id' => $result['campaign_api_message_id'],
				'personnal_redlist' => Tools::getValue('personnal_redlist'),
				'noad_redlist' => Tools::getValue('noads_redlist')
			);

			$this->session_api->call('fax', 'campaign', 'set_redlist_filters', $parameters, $response_array);

			$selected_countries = Tools::getValue('selected_countries');
			$allowed_countries = array ();

			foreach ($selected_countries as $country_id)
			{
				if ((int)$country_id > 0)
					$allowed_countries[] = $country_id;
			}

			$this->setAllowedCountries($allowed_countries);

			Tools::redirectAdmin('index.php?controller=AdminMarketingFStep7&campaign_id='.
				$this->campaign_id.
				'&token='.Tools::getAdminTokenLite('AdminMarketingFStep7'));
		}
	}

}
