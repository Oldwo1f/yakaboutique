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
 * Step 6 : Receive a test
 */
include_once 'em_tools.php';

class AdminMarketingSStep6Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $session_api = null;
	private $campaign_api_message_id = null;
	private $campaign_sms_text = null;

	public function __construct()
	{
		$this->name = 'adminmarketingsstep6';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;
		parent::__construct();

		$this->campaign_id = (int)Tools::getValue('campaign_id');

		if (empty($this->campaign_id))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
			exit;
		}

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

		$this->getFieldsValues();
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a sms-mailing', 'adminmarketingsstep1');
	}

	public function renderList()
	{
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Test your sms-mailing before his validation (step 6)', 'adminmarketingsstep6'),
				'icon' => 'icon-phone'
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
					'label' => $this->module->l('Your mobile phone number :', 'adminmarketingsstep6'),
					'name' => 'campaign_last_tester',
					'prefix' => '<i class="icon-phone"></i>',
					'col' => 3,
					'required' => true
				)
			),
			'buttons' => array(
				array (
					'href' => 'index.php?controller=AdminMarketingSStep7&campaign_id='.$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingSStep7'),
					'title' => $this->module->l('Next', 'adminmarketingsstep6'),
					'icon' => 'process-icon-next',
					'class' => 'pull-right'
				),
				array (
					'href' => 'index.php?controller=AdminMarketingSStep5&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingSStep5'),
					'title' => $this->module->l('Back', 'adminmarketingsstep6'),
					'icon' => 'process-icon-back'
				),
				array (
					'type' => 'submit',
					'title' => $this->module->l('Send a test', 'adminmarketingsstep6'),
					'name' => 'submitSmsTest',
					'icon' => 'process-icon-import',
					'class' => 'pull-right'
				)
			)
		);

		$this->getFieldsValues();

		$output = parent::renderForm();

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

		$this->campaign_api_message_id = $result['campaign_api_message_id'];
		$this->campaign_sms_text = $result['campaign_sms_text'];
		$this->fields_value['campaign_last_tester'] = $result['campaign_last_tester'];

		return true;
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitSmsTest'))
		{
			$number = (string)Tools::getValue('campaign_last_tester');

			if (empty($number) || !Validate::isPhoneNumber($number))
			{
				$this->errors[] = $this->module->l('Invalid gsm number !', 'adminmarketingsstep6');
				return false;
			}

			$prefixe = EMTools::getShopPrefixeCountry();
			$number = EMTools::cleanNumber($number, $prefixe);

			if ($number[0] != '0' && $number[0] != '+')
			{
				$this->errors[] = $this->module->l('Invalid gsm number !', 'adminmarketingsstep6');
				return false;
			}

			$response_array = array();
			$parameters = array(
				'campaign_id' => $this->campaign_api_message_id,
				'recipient' => $number,
				'text' => $this->module->l('[TEST]', 'adminmarketingsstep6').' '.$this->campaign_sms_text
			);

			if ($this->session_api->call('sms', 'campaign', 'send_test', $parameters, $response_array))
			{
				// We store the last fax number
				// ----------------------------
				Db::getInstance()->update('expressmailing_sms', array(
					'campaign_last_tester' => pSQL($number)
					), 'campaign_id = '.$this->campaign_id
				);

				$this->confirmations[] = sprintf($this->module->l('Please wait, your sms is processing to %s ...', 'adminmarketingsstep6'), $number);
				return true;
			}

			$this->errors[] = sprintf($this->module->l('Error while sending sms to the API : %s', 'adminmarketingsstep6'),
								$this->session_api->getError());
			return false;
		}
	}

}
