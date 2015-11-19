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
 * Step 7 : Final validation
 */
class AdminMarketingSStep7Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $session_api = null;

	private $campaign_api_message_id = null;
	private $campaign_sended = false;

	private $campaign_name = null;
	private $campaign_text = null;
	private $start_date = null;
	private $count_total_recipients = null;
	private $count_delivered_recipients = null;
	private $count_not_delivered_recipients = null;
	private $count_cancelled_recipients = null;
	private $count_delivering_recipients = null;
	private $count_transmiting_recipients = null;
	private $count_planned_recipients = null;
	private $count_recipients_to_send = null;
	private $count_invalid_recipients = null;
	private $count_absent_recipients = null;
	private $count_duplicate_recipients = null;
	private $count_recipients_in_not_allowed_country = null;
	private $count_recipients_on_system_redlist = null;
	private $count_recipients_on_noads_redlist = null;
	private $count_recipients_on_personnal_redlist = null;

	public function __construct()
	{
		$this->name = 'adminmarketingsstep7';
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
		if (!$this->session_api->connectFromCredentials('sms'))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingSStep4&token='.Tools::getAdminTokenLite('AdminMarketingSStep4'));
			exit;
		}

		// Enfin, on retrouve les éléments depuis la base de données + de l'API
		// --------------------------------------------------------------------
		$this->getFieldsValues();
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a sms-mailing', 'adminmarketingsstep1');
	}

	public function renderList()
	{
		$this->context->smarty->assign(array (
			'campaign_id' => $this->campaign_id,
			'count_delivered' => $this->count_delivered_recipients,
			'count_sending' => $this->count_transmiting_recipients + $this->count_delivering_recipients,
			'count_not_delivered' => $this->count_not_delivered_recipients,
			'count_planned' => $this->count_planned_recipients,
			'count_cancelled' => $this->count_cancelled_recipients,
			'count_detail_array' => $this->cost_sms_detail,
			'campaign_name' => Tools::htmlentitiesDecodeUTF8($this->campaign_name),
			'campaign_text' => $this->campaign_text,
			'campaign_sended' => $this->campaign_sended
		));

		$display = $this->getTemplatePath().'marketings_stats/cost_s_stats.tpl';
		$this->context->smarty->assign('cost_sms_detail', $this->context->smarty->fetch($display));

		$display = $this->getTemplatePath().'footer_validation.tpl';
		$this->context->smarty->assign('footer_validation', $this->context->smarty->fetch($display));

		$display = $this->getTemplatePath().'marketings_step7/marketings_step7.tpl';
		$output = $this->context->smarty->fetch($display);

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addJS(_PS_MODULE_DIR_.'expressmailing/views/js/emcharts_s_step7.js');
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
	}

	private function getFieldsValues()
	{
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_sms');
		$sql->where('campaign_id = '.$this->campaign_id);

		$result = Db::getInstance()->getRow($sql);

		$this->campaign_api_message_id = $result['campaign_api_message_id'];

		if ($this->session_api->connectFromCredentials('sms'))
		{
			$response_array = array();
			$parameters = array(
				'campaign_id' => $this->campaign_api_message_id
			);

			if ($this->session_api->call('sms', 'campaign', 'get_infos', $parameters, $response_array))
			{
				$this->campaign_name = $response_array['name'];
				$this->campaign_text = $response_array['text'];
				$this->campaign_state = $response_array['state'];
				$this->start_date = $response_array['start_date'];
				$this->count_total_recipients = $response_array['count_total_recipients'];
				$this->count_delivered_recipients = $response_array['count_delivered_recipients'];
				$this->count_not_delivered_recipients = $response_array['count_not_delivered_recipients'];
				$this->count_cancelled_recipients = $response_array['count_cancelled_recipients'];
				$this->count_delivering_recipients = $response_array['count_delivering_recipients'];
				$this->count_transmiting_recipients = $response_array['count_transmiting_recipients'];
				$this->count_planned_recipients = $response_array['count_planned_recipients'];
				$this->count_recipients_to_send = $response_array['count_recipients_to_send'];
				$this->count_invalid_recipients = $response_array['count_invalid_recipients'];
				$this->count_absent_recipients = $response_array['count_absent_recipients'];
				$this->count_duplicate_recipients = $response_array['count_duplicate_recipients'];
				$this->count_recipients_in_not_allowed_country = $response_array['count_recipients_in_not_allowed_country'];
				$this->count_recipients_on_system_redlist = $response_array['count_recipients_on_system_redlist'];
				$this->count_recipients_on_noads_redlist = $response_array['count_recipients_on_noads_redlist'];
				$this->count_recipients_on_personnal_redlist = $response_array['count_recipients_on_personnal_redlist'];
				$this->fields_value['campaign_name'] = $this->campaign_name;
				$this->fields_value['campaign_text'] = $this->campaign_text;
			}

			if ($this->session_api->call('sms', 'campaign', 'enum_count_sms_detail', $parameters, $response_array))
				$this->cost_sms_detail = $response_array;
		}
	}

	public function postProcess()
	{
		if (Tools::isSubmit('sendCampaign'))
		{
			$yes = (string)Tools::getValue('YES', '');
			$yes = Tools::strtoupper($yes);

			if ($yes == Tools::strtoupper(Translate::getModuleTranslation('expressmailing', 'YES', 'footer_validation')))
			{
				if ($this->sendCampaignAPI())
				{
					$this->confirmations[] = $this->module->l('Your campaign is now sending ...', 'adminmarketingsstep7');

					// Tracking Prestashop
					// -------------------
					Db::getInstance()->update('expressmailing_sms', array(
						'campaign_state' => 2,
						'campaign_api_validation' => '1'
						), 'campaign_id = '.$this->campaign_id
					);

					// On vide la table temporaire des contacts
					// ----------------------------------------
					Db::getInstance()->delete('expressmailing_sms_recipients', 'campaign_id = '.$this->campaign_id);

					return true;
				}
			}
			else
			{
				$this->errors[] = sprintf($this->module->l('Please fill the %s field', 'adminmarketingsstep7'),
					'&laquo;&nbsp;'.
					Translate::getModuleTranslation('expressmailing', 'YES', 'footer_validation').
					'&nbsp;&raquo;');
			}

			return false;
		}
	}

	private function sendCampaignAPI()
	{
		$response_array = null;
		$parameters = array(
			'account_id' => $this->session_api->account_id,
			'campaign_id' => $this->campaign_api_message_id
		);

		if ($this->session_api->call('sms', 'campaign', 'send', $parameters, $response_array))
		{
			$this->campaign_sended = true;
			return true;
		}
		else
		{
			$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingsstep7'),
								$this->session_api->getError());
			return false;
		}
	}

}