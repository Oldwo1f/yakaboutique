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

include_once 'em_tools.php';

/**
 * Step 7 : Receive a test
 */
class AdminMarketingFStep7Controller extends ModuleAdminController
{
	private $campaign_id = null;
	private $session_api = null;
	private $campaign_api_message_id = null;

	public function __construct()
	{
		$this->name = 'adminmarketingfstep7';
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
		if (!$this->session_api->connectFromCredentials('fax'))
		{
			Tools::redirectAdmin('index.php?controller=AdminMarketingFStep6&token='.Tools::getAdminTokenLite('AdminMarketingFStep6'));
			exit;
		}

		$this->getFieldsValues();
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send a fax-mailing', 'adminmarketingfstep1');
	}

	public function renderList()
	{
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Test your fax-mailing before his validation (step 7)', 'adminmarketingfstep7'),
				'icon' => 'icon-print'
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
					'label' => $this->module->l('Your fax number or an email address :', 'adminmarketingfstep7'),
					'name' => 'campaign_last_tester',
					'prefix' => '<i class="icon-phone"></i>',
					'col' => 3,
					'required' => true
				)
			),
			'buttons' => array(
				array (
					'href' => 'index.php?controller=AdminMarketingFStep8&campaign_id='.$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingFStep8'),
					'title' => $this->module->l('Next', 'adminmarketingfstep7'),
					'icon' => 'process-icon-next',
					'class' => 'pull-right'
				),
				array (
					'href' => 'index.php?controller=AdminMarketingFStep6&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingFStep6'),
					'title' => $this->module->l('Back', 'adminmarketingfstep7'),
					'icon' => 'process-icon-back'
				),
				array (
					'type' => 'submit',
					'title' => $this->module->l('Send a test', 'adminmarketingsstep6'),
					'name' => 'submitFaxTest',
					'icon' => 'process-icon-duplicate',
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
		$sql->from('expressmailing_fax');
		$sql->where('campaign_id = '.$this->campaign_id);

		$result = Db::getInstance()->getRow($sql);
		$this->campaign_api_message_id = $result['campaign_api_message_id'];

		return true;
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitFaxTest'))
		{
			$number_or_email = (string)Tools::getValue('campaign_last_tester');

			if (empty($number_or_email))
			{
				$this->errors[] = $this->module->l('Invalid fax number !', 'adminmarketingfstep7');
				return false;
			}

			if (Tools::strpos($number_or_email, '@'))
			{
				if (!Validate::isEmail($number_or_email))
				{
					$this->errors[] = $this->module->l('Invalid email address !', 'adminmarketingfstep7');
					return false;
				}
			}
			else
			{
				$prefixe = EMTools::getShopPrefixeCountry();
				$number_or_email = EMTools::cleanNumber($number_or_email, $prefixe);

				if (!Validate::isPhoneNumber($number_or_email))
				{
					$this->errors[] = $this->module->l('Invalid fax number !', 'adminmarketingfstep7');
					return false;
				}

				if ($number_or_email[0] != '0' && $number_or_email[0] != '+')
				{
					$this->errors[] = $this->module->l('Invalid fax number !', 'adminmarketingfstep7');
					return false;
				}
			}

			$response_array = array();
			$parameters = array(
				'campaign_id' => $this->campaign_api_message_id,
				'recipient' => $number_or_email
			);

			if ($this->session_api->call('fax', 'campaign', 'send_test', $parameters, $response_array))
			{
				// We store the last fax number
				// ----------------------------
				Db::getInstance()->update('expressmailing_fax', array(
					'campaign_last_tester' => pSQL($number_or_email)
					), 'campaign_id = '.$this->campaign_id
				);

				$this->confirmations[] = sprintf($this->module->l('Please wait, your fax is processing to %s ...', 'adminmarketingfstep7'),
											$number_or_email);
				return true;
			}

			$this->errors[] = sprintf($this->module->l('Error while sending fax to the API : %s', 'adminmarketingfstep7'),
								$this->session_api->getError());
			return false;
		}
	}

}
