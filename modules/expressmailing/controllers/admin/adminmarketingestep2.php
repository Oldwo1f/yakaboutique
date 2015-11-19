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
 * Step 2 : Broadcast sender name & email
 */
class AdminMarketingEStep2Controller extends ModuleAdminController
{
	private $campaign_id = null;

	public function __construct()
	{
		$this->name = 'adminmarketingestep2';
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
		$this->toolbar_title = Translate::getModuleTranslation('expressmailing', 'Send an e-mailing', 'adminmarketingestep1');
	}

	public function renderList()
	{
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Campaign configuration (step 2)', 'adminmarketingestep2'),
				'icon' => 'icon-cogs'
			),
			'description' => $this->module->l('Avoid sender names too commercial', 'adminmarketingestep2')."<br>\r\n".
			$this->module->l('Use a 2 words name. Like First and Last names', 'adminmarketingestep2'),
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
					'label' => $this->module->l('Campaign sender email :', 'adminmarketingestep2'),
					'name' => 'campaign_sender_email',
					'prefix' => '<i class="icon-envelope-o"></i>',
					'col' => 4,
					'required' => true
				),
				array (
					'type' => 'text',
					'lang' => false,
					'label' => $this->module->l('Campaign sender name :', 'adminmarketingestep2'),
					'name' => 'campaign_sender_name',
					'prefix' => '<i class="icon-user"></i>',
					'col' => 4,
					'required' => true
				)
			),
			'submit' => array(
				'title' => $this->module->l('Next', 'adminmarketingestep2'),
				'name' => 'submitEmailingStep2',
				'icon' => 'process-icon-next'
			),
			'buttons' => array(
				array (
					'href' => 'index.php?controller=AdminMarketingEStep1&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingEStep1'),
					'title' => $this->module->l('Back', 'adminmarketingestep2'),
					'icon' => 'process-icon-back'
				)
			)
		);

		$this->getFieldsValues();
		$output = parent::renderForm();

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function postProcess()
	{
		if (Tools::isSubmit('submitEmailingStep2'))
		{
			$this->campaign_sender_email = (string)Tools::getValue('campaign_sender_email');
			$this->campaign_sender_name = (string)Tools::getValue('campaign_sender_name');

			if (empty($this->campaign_id) || empty($this->campaign_sender_email) || empty($this->campaign_sender_name))
				$this->errors[] = $this->module->l('Please verify the required fields', 'adminmarketingestep2');
			elseif (!Validate::isEmail($this->campaign_sender_email))
				$this->errors[] = $this->module->l('Please verify your email address', 'adminmarketingestep2');
			elseif (!Validate::isMailName($this->campaign_sender_name))
				$this->errors[] = $this->module->l('Please verify your sender name', 'adminmarketingestep2');
			else
			{
				Db::getInstance()->update('expressmailing_email', array(
					'campaign_sender_email' => pSQL($this->campaign_sender_email),
					'campaign_sender_name' => pSQL($this->campaign_sender_name),
					), 'campaign_id = '.pSQL($this->campaign_id)
				);

				Tools::redirectAdmin('index.php?controller=AdminMarketingEStep3&campaign_id='.
					$this->campaign_id.
					'&token='.Tools::getAdminTokenLite('AdminMarketingEStep3'));
				exit;
			}
		}
	}

	private function getFieldsValues()
	{
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_email');
		$sql->where('campaign_id = '.$this->campaign_id);
		$result = Db::getInstance()->getRow($sql);

		$this->fields_value['campaign_id'] = $this->campaign_id;

		if (!empty($result['campaign_sender_email']))
			$this->fields_value['campaign_sender_email'] = $result['campaign_sender_email'];
		else
			$this->fields_value['campaign_sender_email'] = Configuration::get('PS_SHOP_EMAIL');

		if (!empty($result['campaign_sender_name']))
			$this->fields_value['campaign_sender_name'] = $result['campaign_sender_name'];
		else
			$this->fields_value['campaign_sender_name'] = Configuration::get('PS_SHOP_NAME');

		return true;
	}

}
