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

class AdminMarketingSStatsController extends ModuleAdminController
{
	private $campaign_id = null;
	private $session_api = null;
	private $campaign_name = null;
	private $campaign_text = null;
	private $campaign_state = null;
	private $start_date = null;
	private $finished_date = null;
	private $planning_start_hour = null;
	private $planning_stop_hour = null;
	private $max_delivrered_allowed = null;
	private $max_delivrered_allowed_per_day = null;
	private $planning_allow_monday = null;
	private $planning_allow_tuesday = null;
	private $planning_allow_wednesday = null;
	private $planning_allow_thursday = null;
	private $planning_allow_friday = null;
	private $planning_allow_saturday = null;
	private $planning_allow_sunday = null;
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
	private $campaign_graph = null;
	private $cost_sms_detail = null;

	public function __construct()
	{
		$this->name = 'adminmarketingsstats';
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
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'My sms statistics', 'marketing_step0');
	}

	public function renderList()
	{
		$this->getFieldsValues();
		$this->setSmartyVars();

		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Statistics for campaign', 'adminmarketingsstats').
							' &laquo;&nbsp;'.
							$this->campaign_name.
							'&nbsp;&raquo;',
				'icon' => 'icon-bar-chart'
			),
			'input' => array(
				array (
					'type' => _PS_MODE_DEV_ ? 'text' : 'hidden',
					'lang' => false,
					'label' => 'Ref :',
					'name' => 'campaign_id',
					'col' => 1,
					'readonly' => 'readonly'
				),
				array (
					'type' => 'text',
					'lang' => false,
					'label' => $this->module->l('Campaign name :', 'adminmarketingsstats'),
					'name' => 'campaign_name',
					'col' => 7,
					'readonly' => 'readonly'
				),
				array (
					'type' => 'text',
					'lang' => false,
					'label' => $this->module->l('Campaign state :', 'adminmarketingsstats'),
					'name' => 'campaign_state',
					'col' => 7,
					'readonly' => 'readonly'
				),
				array (
					'type' => 'free',
					'lang' => false,
					'label' => $this->module->l('Sms content :', 'adminmarketingsstats'),
					'name' => 'campaign_text',
					'col' => 7,
					'readonly' => 'readonly'
				),
				array (
					'type' => 'free',
					'lang' => false,
					'label' => $this->module->l('Cost detail :', 'adminmarketingsstats'),
					'col' => 7,
					'name' => 'sms_detail_count'
				),
				array (
					'type' => 'free',
					'lang' => false,
					'label' => $this->module->l('Chart :', 'adminmarketingsstats'),
					'col' => 7,
					'name' => 'campaign_graph'
				)
			),
			'buttons' => array(
				array (
					'href' => 'index.php?controller=AdminMarketingSList&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingSList'),
					'title' => $this->module->l('Back', 'adminmarketingsstats'),
					'icon' => 'process-icon-back'
				)
			)
		);

		$display = $this->getTemplatePath().'marketings_stats/text_s_stats.tpl';
		$this->campaign_text = $this->context->smarty->fetch($display);
		$this->fields_value['campaign_text'] = $this->campaign_text;

		$display = $this->getTemplatePath().'marketings_stats/graphic_s_stats.tpl';
		$this->campaign_graph = $this->context->smarty->fetch($display);
		$this->fields_value['campaign_graph'] = $this->campaign_graph;

		$display = $this->getTemplatePath().'marketings_stats/cost_s_stats.tpl';
		$this->context->smarty->assign('count_detail_array', $this->cost_sms_detail);
		$this->cost_sms_detail = $this->context->smarty->fetch($display);
		$this->fields_value['sms_detail_count'] = $this->cost_sms_detail;

		$output = parent::renderForm();
		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addJS(_PS_MODULE_DIR_.'expressmailing/views/js/emcharts_s_stats.js', 'all');
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css', 'all');
	}

	private function setSmartyVars()
	{
		$this->context->smarty->assign(array (
			'count_delivered' => $this->count_delivered_recipients,
			'count_sending' => $this->count_transmiting_recipients + $this->count_delivering_recipients,
			'count_not_delivered' => $this->count_not_delivered_recipients,
			'count_planned' => $this->count_planned_recipients,
			'count_cancelled' => $this->count_cancelled_recipients,
			'campaign_text' => $this->campaign_text
		));
	}

	private function getFieldsValues()
	{
		if ($this->session_api->connectFromCredentials('sms'))
		{
			$response_array = array();
			$parameters = array(
				'campaign_id' => $this->campaign_id
			);

			if ($this->session_api->call('sms', 'campaign', 'get_infos', $parameters, $response_array))
			{
				$this->campaign_name = $response_array['name'];
				$this->campaign_text = $response_array['text'];
				$this->campaign_state = $response_array['state'];
				$this->start_date = $response_array['start_date'];
				$this->finished_date = $response_array['finished_date'];
				$this->planning_start_hour = $response_array['planning_start_hour'];
				$this->planning_stop_hour = $response_array['planning_stop_hour'];
				$this->max_delivrered_allowed = $response_array['max_delivrered_allowed'];
				$this->max_delivrered_allowed_per_day = $response_array['max_delivrered_allowed_per_day'];
				$this->planning_allow_monday = $response_array['planning_allow_monday'];
				$this->planning_allow_tuesday = $response_array['planning_allow_tuesday'];
				$this->planning_allow_wednesday = $response_array['planning_allow_wednesday'];
				$this->planning_allow_thursday = $response_array['planning_allow_thursday'];
				$this->planning_allow_friday = $response_array['planning_allow_friday'];
				$this->planning_allow_saturday = $response_array['planning_allow_saturday'];
				$this->planning_allow_sunday = $response_array['planning_allow_sunday'];
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
				$this->fields_value['campaign_name'] = Tools::htmlentitiesDecodeUTF8($this->campaign_name);
				$this->fields_value['campaign_text'] = $this->campaign_text;
				$this->fields_value['campaign_state'] = $this->campaign_state;
			}

			if ($this->session_api->call('sms', 'campaign', 'enum_count_sms_detail', $parameters, $response_array))
				$this->cost_sms_detail = $response_array;
		}

		return true;
	}

}
