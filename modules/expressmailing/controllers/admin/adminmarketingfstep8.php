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
 * Step 8 : Final validation
 */
class AdminMarketingFStep8Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $session_api = null;
	private $name = null;
	private $state = null;
	private $campaign_sended = false;
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
	private $count_planned_recipients = null;
	private $count_recipients_to_send = null;
	private $count_invalid_recipients = null;
	private $count_duplicate_recipients = null;
	private $count_recipients_in_not_allowed_country = null;
	private $count_recipients_on_system_redlist = null;
	private $count_recipients_on_noads_redlist = null;
	private $count_recipients_on_personnal_redlist = null;

	public function __construct()
	{
		$this->name = 'adminmarketingfstep8';
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

		// Initialisation de l'API
		// -----------------------
		include _PS_MODULE_DIR_.$this->module->name.'/controllers/admin/session_api.php';
		$this->session_api = new SessionApi();
		$this->getFieldsValues();
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a fax-mailing', 'adminmarketingfstep1');
	}

	public function renderList()
	{
		$this->context->smarty->assign(array (
			'campaign_id' => $this->campaign_id,
			'campaign_name' => Tools::htmlentitiesDecodeUTF8($this->name),
			'campaign_sended' => $this->campaign_sended,
			'count_planned' => $this->count_planned_recipients,
			'count_cancelled' => $this->count_cancelled_recipients
		));

		$display = $this->getTemplatePath().'footer_validation.tpl';
		$this->context->smarty->assign('footer_validation', $this->context->smarty->fetch($display));

		$display = $this->getTemplatePath().'marketingf_step8/marketingf_step8.tpl';
		$output = $this->context->smarty->fetch($display);

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addJS(_PS_MODULE_DIR_.'expressmailing/views/js/emcharts_f_step8.js');
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
	}

	private function getFieldsValues()
	{
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_fax');
		$sql->where('campaign_id = '.$this->campaign_id);

		$result = Db::getInstance()->getRow($sql);
		$this->campaign_api_message_id = $result['campaign_api_message_id'];

		if ($this->session_api->connectFromCredentials('fax'))
		{
			$response_array = array ();
			$parameters = array (
				'campaign_id' => $this->campaign_api_message_id
			);

			if ($this->session_api->call('fax', 'campaign', 'get_infos', $parameters, $response_array))
			{
				$this->name = $response_array['name'];
				$this->state = $response_array['state'];
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
				$this->count_planned_recipients = $response_array['count_planned_recipients'];
				$this->count_recipients_to_send = $response_array['count_recipients_to_send'];
				$this->count_invalid_recipients = $response_array['count_invalid_recipients'];
				$this->count_duplicate_recipients = $response_array['count_duplicate_recipients'];
				$this->count_recipients_in_not_allowed_country = $response_array['count_recipients_in_not_allowed_country'];
				$this->count_recipients_on_system_redlist = $response_array['count_recipients_on_system_redlist'];
				$this->count_recipients_on_noads_redlist = $response_array['count_recipients_on_noads_redlist'];
				$this->count_recipients_on_personnal_redlist = $response_array['count_recipients_on_personnal_redlist'];
				$this->fields_value['campaign_name'] = $this->name;
				$this->fields_value['campaign_state'] = $this->state;
			}
		}
		return true;
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
					$this->confirmations[] = $this->module->l('Your campaign is now sending ...', 'adminmarketingfstep8');

					// Tracking Prestashop
					// -------------------
					Db::getInstance()->update('expressmailing_fax', array (
						'campaign_state' => 2,
						'campaign_api_validation' => '1'
						), 'campaign_id = '.$this->campaign_id
					);

					// On vide la table temporaire des contacts
					// ----------------------------------------

					Db::getInstance()->delete('expressmailing_fax_recipients', 'campaign_id = '.$this->campaign_id);

					$req = new DbQuery();
					$req->select('*');
					$req->from('expressmailing_fax_pages');
					$req->where('campaign_id = '.$this->campaign_id);
					$req->orderBy('id');

					$pages_db = Db::getInstance()->executeS($req, true, false);
					foreach ($pages_db as $page)
					{
						unlink($page['page_path']);
						unlink($page['page_path_original']);
					}

					Db::getInstance()->delete('expressmailing_fax_pages', 'campaign_id = '.$this->campaign_id);

					return true;
				}
			}
			else
			{
				$this->errors[] = sprintf($this->module->l('Please fill the %s field', 'adminmarketingfstep8'), '&laquo;&nbsp;'.
									Translate::getModuleTranslation('expressmailing', 'YES', 'footer_validation').
									'&nbsp;&raquo;');
			}

			return false;
		}
	}

	private function sendCampaignAPI()
	{
		$response_array = null;
		$parameters = array (
			'campaign_id' => $this->campaign_api_message_id
		);

		if ($this->session_api->call('fax', 'campaign', 'send', $parameters, $response_array))
		{
			$this->campaign_sended = true;
			return true;
		}
		else
		{
			$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketingfstep8'),
								$this->session_api->getError());
			return false;
		}
	}

}
