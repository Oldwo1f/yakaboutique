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
class AdminMarketingSStep1Controller extends ModuleAdminController
{
	private $campaign_id = null;

	public function __construct()
	{
		$this->name = 'adminmarketingsstep1';
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
		$this->toolbar_title = $this->module->l('Send a sms-mailing', 'adminmarketingsstep1');
	}

	public function renderList()
	{
		$this->getFieldsValues();

		$display = $this->getTemplatePath().'marketings_step1/marketings_mobile.tpl';
		$sms_content = $this->context->smarty->fetch($display);

		$this->context->smarty->assign(array (
			'mod_dev' => _PS_MODE_DEV_,
			'campaign_id' => $this->campaign_id,
			'input_parameters' => $this->renderParameter(),
			'sms_content' => $sms_content
		));
		$display = $this->getTemplatePath().'marketings_step1/marketings_step1.tpl';
		$output = $this->context->smarty->fetch($display);

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	private function getFieldsValues()
	{
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_sms');
		$sql->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($sql);

		$this->fields_value['campaign_id'] = $this->campaign_id;
		$this->fields_value['campaign_name'] = $result['campaign_name'];
		$this->fields_value['campaign_date_send'] = $result['campaign_date_send'];
		$this->context->smarty->assign('campaign_text', $result['campaign_sms_text']);
		$this->fields_value['week_day_limit_L'] = strpos($result['campaign_week_limit'], 'L') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_M'] = strpos($result['campaign_week_limit'], 'M') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_C'] = strpos($result['campaign_week_limit'], 'C') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_J'] = strpos($result['campaign_week_limit'], 'J') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_V'] = strpos($result['campaign_week_limit'], 'V') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_S'] = strpos($result['campaign_week_limit'], 'S') !== false ? 'on' : null;
		$this->fields_value['week_day_limit_D'] = strpos($result['campaign_week_limit'], 'D') !== false ? 'on' : null;
		$this->fields_value['schedule_sending'] = $this->generateSlider('schedule_sending', 0, 1440, 10,
																		$result['campaign_start_hour'], $result['campaign_end_hour']);
		return true;
	}

	private function renderParameter()
	{
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
				'title' => $this->module->l('Campaign configuration (step 1)', 'adminmarketingsstep1'),
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
					'label' => $this->module->l('Give a name to this campaign :', 'adminmarketingsstep1'),
					'name' => 'campaign_name',
					'col' => 7,
					'required' => true
				),
				array (
					'type' => 'datetime',
					'lang' => false,
					'name' => 'campaign_date_send',
					'label' => $this->module->l('Sending date :', 'adminmarketingsstep1'),
					'col' => 5,
					'required' => true
				),
				array (
					'type' => 'checkbox',
					'label' => $this->module->l('Limit by day of the week :', 'adminmarketingsstep1'),
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
					'name' => 'schedule_sending',
					'label' => $this->module->l('Schedule sending :', 'adminmarketingsstep1'),
				)
			),
			'submit' => array(
				'title' => $this->module->l('Next', 'adminmarketingsstep1'),
				'name' => 'submitSmsStep1',
				'icon' => 'process-icon-next'
			)
		);

		$output = parent::renderForm();
		return $this->getFormWrapperElement($output);
	}

	public function setMedia()
	{
		parent::setMedia();

		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/expressmailing.css');
		$this->addJS(_PS_MODULE_DIR_.'expressmailing/views/js/marketings_step1.js');
		$this->addJqueryUI('ui.slider');
		$this->addJqueryUI('ui.spinner');
	}

	private function generateSlider($field_name, $min_value, $max_value, $step, $preset_value_start, $preset_value_end)
	{
		$this->context->smarty->assign('field_name', $field_name);
		$this->context->smarty->assign('min_value', $min_value);
		$this->context->smarty->assign('max_value', $max_value);
		$this->context->smarty->assign('start_value', $preset_value_start);
		$this->context->smarty->assign('end_value', $preset_value_end);
		$this->context->smarty->assign('step', $step);
		$template_path = $this->getTemplatePath().'marketings_step1/marketings_slider.tpl';

		return $this->context->smarty->fetch($template_path);
	}

	private function getFormWrapperElement($html)
	{
		$dom_original = new DOMDocument();
		$dom_output = new DOMDocument();

		$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
		$dom_original->loadHTML($html);
		$xpath = new DOMXpath($dom_original);

		// Keep every javascript tags
		// --------------------------
		$result = $xpath->query('//script[@type="text/javascript"]');
		for ($i = 0; $i < $result->length; $i++)
		{
			$output_div = $result->item($i);
			$dom_output->appendChild($dom_output->importNode($output_div, true));
		}

		// Keep every alert tags
		// ---------------------
		$result = $xpath->query('//div[@class="alert alert-info"]');
		for ($i = 0; $i < $result->length; $i++)
		{
			$output_div = $result->item(0);
			$dom_output->appendChild($dom_output->importNode($output_div, true));
		}

		// Keep the form wrapper tag
		// -------------------------
		$result = $xpath->query('//div[@class="form-wrapper"]');
		$output_div = $result->item(0);
		$dom_output->appendChild($dom_output->importNode($output_div, true));

		return $dom_output->saveHTML();
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitSmsStep1'))
		{
			// Date verification
			// -----------------
			$date_send = DateTime::createFromFormat('Y-m-d H:i:s', (string)Tools::getValue('campaign_date_send'));
			$campaign_date_send = $date_send->getTimestamp();
			$campaign_date_send = date('Y-m-d H:i:s', $campaign_date_send);

			$campaign_name = Tools::ucfirst((string)Tools::getValue('campaign_name'));
			$campaign_text = Tools::ucfirst((string)Tools::getValue('campaign_text'));

			$limit_dayoftheweek = '';
			if (Tools::getValue('week_day_limit_L'))
				$limit_dayoftheweek .= 'L';
			if (Tools::getValue('week_day_limit_M'))
				$limit_dayoftheweek .= 'M';
			if (Tools::getValue('week_day_limit_C'))
				$limit_dayoftheweek .= 'C';
			if (Tools::getValue('week_day_limit_J'))
				$limit_dayoftheweek .= 'J';
			if (Tools::getValue('week_day_limit_V'))
				$limit_dayoftheweek .= 'V';
			if (Tools::getValue('week_day_limit_S'))
				$limit_dayoftheweek .= 'S';
			if (Tools::getValue('week_day_limit_D'))
				$limit_dayoftheweek .= 'D';

			if (empty($this->campaign_id)
				|| empty($campaign_name)
				|| empty($campaign_date_send)
				|| empty($campaign_text)
				|| (Tools::strlen($campaign_text) < 5))
			{
				$this->errors[] = $this->module->l('Please verify the required fields', 'adminmarketingsstep1');
				return false;
			}
			else
			{
				// On mémorise els info, même si la date n'est pas bonne
				// -----------------------------------------------------
				Db::getInstance()->update('expressmailing_sms', array(
					'campaign_state' => 1,
					'campaign_date_update' => date('Y-m-d H:i:s'),
					'campaign_date_send' => $campaign_date_send,
					'campaign_sms_text' => pSQL($campaign_text),
					'campaign_name' => pSQL($campaign_name),
					'campaign_week_limit' => $limit_dayoftheweek,
					'campaign_start_hour' => (int)Tools::getValue('start_hour_hidden'),
					'campaign_end_hour' => (int)Tools::getValue('end_hour_hidden'),
					), 'campaign_id = '.$this->campaign_id
				);

				if ($campaign_date_send > mktime(0, 0, 0, date('m') + 3, date('d'), date('Y')))
				{
					$this->errors[] = $this->module->l('Invalid date (max 3 months)', 'adminmarketingsstep1');
					return false;
				}

				Tools::redirectAdmin('index.php?controller=AdminMarketingSStep2&campaign_id='.$this->campaign_id.
									'&token='.Tools::getAdminTokenLite('AdminMarketingSStep2'));
				exit;
			}
		}
	}

}
