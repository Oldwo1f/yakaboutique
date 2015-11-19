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
 * Step 1 : Campaign name, date and broadcast limitations
 */
class AdminMarketingFStep1Controller extends ModuleAdminController
{
	private $campaign_id = null;

	public function __construct()
	{
		$this->name = 'adminmarketingfstep1';
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
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = $this->module->l('Send a fax-mailing', 'adminmarketingfstep1');
	}

	public function setMedia()
	{
		parent::setMedia();

		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
		$this->addJS(_PS_MODULE_DIR_.'expressmailing/views/js/marketingf_step1.js');
		$this->addJqueryUI('ui.slider');
		$this->addJqueryUI('ui.spinner');
	}

	public function renderList()
	{
		$this->getFieldsValues();

		$field_days = array(
			array (
				'short_day' => 'L',
				'name' => Translate::getAdminTranslation('Monday')
			),
			array (
				'short_day' => 'M',
				'name' => Translate::getAdminTranslation('Tuesday')
			),
			array (
				'short_day' => 'C',
				'name' => Translate::getAdminTranslation('Wednesday')
			),
			array (
				'short_day' => 'J',
				'name' => Translate::getAdminTranslation('Thursday')
			),
			array (
				'short_day' => 'V',
				'name' => Translate::getAdminTranslation('Friday')
			),
			array (
				'short_day' => 'S',
				'name' => Translate::getAdminTranslation('Saturday')
			),
			array (
				'short_day' => 'D',
				'name' => Translate::getAdminTranslation('Sunday')
			)
		);

		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Campaign configuration (step 1)', 'adminmarketingfstep1'),
				'icon' => 'icon-cogs'
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
					'label' => $this->module->l('Campaign name :', 'adminmarketingfstep1'),
					'name' => 'campaign_name',
					'col' => 5,
					'required' => true
				),
				array (
					'type' => 'datetime',
					'lang' => false,
					'name' => 'campaign_date_send',
					'label' => $this->module->l('Sending date :', 'adminmarketingfstep1'),
					'col' => 5,
					'required' => true
				),
				array (
					'type' => 'checkbox',
					'label' => $this->module->l('Limit by day of the week :', 'adminmarketingfstep1'),
					'class' => 'checkbox-inline',
					'name' => 'week_day_limit',
					'values' => array(
						'query' => $field_days,
						'id' => 'short_day',
						'name' => 'name',
						'class' => 'checkbox-inline'
					)
				),
				array (
					'type' => 'free',
					'name' => 'start_end_hours',
					'label' => $this->module->l('Schedule sending :', 'adminmarketingfstep1'),
					'required' => true
				),
				array (
					'type' => 'free',
					'name' => 'campaign_day_limit',
					'label' => $this->module->l('Daily sending limit :', 'adminmarketingfstep1')
				)
			),
			'submit' => array(
				'title' => $this->module->l('Next', 'adminmarketingfstep1'),
				'name' => 'submitFaxStep1',
				'icon' => 'process-icon-next'
			)
		);

		$output = parent::renderForm();

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	private function getFieldsValues()
	{
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_fax');
		$sql->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($sql);

		$this->fields_value['campaign_id'] = $this->campaign_id;
		$this->fields_value['campaign_name'] = $result['campaign_name'];
		$this->fields_value['campaign_date_send'] = $result['campaign_date_send'];
		$this->fields_value['week_day_limit_L'] = strpos($result['campaign_week_limit'], 'L') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_M'] = strpos($result['campaign_week_limit'], 'M') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_C'] = strpos($result['campaign_week_limit'], 'C') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_J'] = strpos($result['campaign_week_limit'], 'J') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_V'] = strpos($result['campaign_week_limit'], 'V') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_S'] = strpos($result['campaign_week_limit'], 'S') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_D'] = strpos($result['campaign_week_limit'], 'D') !== false ? 'on' : null;
		$this->fields_value['campaign_day_limit'] = $this->generateSpinner($result['campaign_day_limit']);
		$this->fields_value['start_end_hours'] = $this->generateSlider('start_end_hours', 0, 1440, 10,
													$result['campaign_start_hour'], $result['campaign_end_hour']);
		return true;
	}

	private function generateSlider($field_name, $min_value, $max_value, $step, $preset_value_start, $preset_value_end)
	{
		$this->context->smarty->assign('field_name', $field_name);
		$this->context->smarty->assign('min_value', $min_value);
		$this->context->smarty->assign('max_value', $max_value);
		$this->context->smarty->assign('start_value', $preset_value_start);
		$this->context->smarty->assign('end_value', $preset_value_end);
		$this->context->smarty->assign('step', $step);

		$template_path = $this->getTemplatePath().'marketingf_step1/marketingf_slider.tpl';
		return $this->context->smarty->fetch($template_path);
	}

	private function generateSpinner($preset_value)
	{
		$this->context->smarty->assign('preset_value', $preset_value);
		$template_path = $this->getTemplatePath().'marketingf_step1/marketingf_spinner.tpl';
		return $this->context->smarty->fetch($template_path);
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitFaxStep1'))
		{
			$date_send = DateTime::createFromFormat('Y-m-d H:i:s', (string)Tools::getValue('campaign_date_send'));
			$campaign_date_send = $date_send->getTimestamp();
			$campaign_date_send = date('Y-m-d H:i:s', $campaign_date_send);

			$campaign_name = Tools::ucfirst((string)Tools::getValue('campaign_name'));
			$campaign_day_limit = (int)Tools::getValue('campaign_day_limit');

			$campaign_week_limit = '';
			if (Tools::getValue('week_day_limit_L'))
				$campaign_week_limit .= 'L';
			if (Tools::getValue('week_day_limit_M'))
				$campaign_week_limit .= 'M';
			if (Tools::getValue('week_day_limit_C'))
				$campaign_week_limit .= 'C';
			if (Tools::getValue('week_day_limit_J'))
				$campaign_week_limit .= 'J';
			if (Tools::getValue('week_day_limit_V'))
				$campaign_week_limit .= 'V';
			if (Tools::getValue('week_day_limit_S'))
				$campaign_week_limit .= 'S';
			if (Tools::getValue('week_day_limit_D'))
				$campaign_week_limit .= 'D';

			if (empty($this->campaign_id) || empty($campaign_name) || empty($campaign_date_send))
			{
				$this->errors[] = $this->module->l('Please verify the required fields', 'adminmarketingfstep1');
				return false;
			}
			else
			{
				// On mémorise els info, même si la date n'est pas bonne
				// -----------------------------------------------------
				Db::getInstance()->update('expressmailing_fax', array(
					'campaign_state' => 1,
					'campaign_date_update' => date('Y-m-d H:i:s'),
					'campaign_date_send' => $campaign_date_send,
					'campaign_name' => pSQL($campaign_name),
					'campaign_week_limit' => $campaign_week_limit,
					'campaign_day_limit' => $campaign_day_limit,
					'campaign_start_hour' => (int)Tools::getValue('start_hour_hidden'),
					'campaign_end_hour' => (int)Tools::getValue('end_hour_hidden')
					), 'campaign_id = '.$this->campaign_id
				);

				if ($campaign_date_send > mktime(0, 0, 0, date('m') + 3, date('d'), date('Y')))
				{
					$this->errors[] = $this->module->l('Invalid date (max 3 months)', 'adminmarketingfstep1');
					return false;
				}

				Tools::redirectAdmin('index.php?controller=AdminMarketingFStep2&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingFStep2'));
			}
		}
	}

}
