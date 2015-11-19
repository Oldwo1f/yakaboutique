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

class AdminMarketingInscriptionController extends ModuleAdminController
{
	public $session_api = null;
	private $campaign_id = null;

	private $back_action = null;
	private $next_action = null;
	private $step_action = null;
	private $buy_package = false;
	private $count_fax_recipients = 0;
	private $count_sms_recipients = 0;

	public function __construct()
	{
		$this->name = 'adminmarketinginscription';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;
		$this->next_controller = '';

		$this->campaign_id = (int)Tools::getValue('campaign_id');

		parent::__construct();

		switch ($this->controller_name)
		{
			case 'AdminMarketingEStep5': /* ---------------------------------------------------- */

				if (empty($this->campaign_id))
				{
					Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
					exit;
				}
				$this->next_controller = $this->controller_name;
				$this->step_action = '5';
				$this->media = 'email';
				$this->buy_package = false;
				$this->back_action = 'index.php?controller=AdminMarketingEStep4&campaign_id='.
				$this->campaign_id.'&token='.Tools::getAdminTokenLite('AdminMarketingEStep4');

				$this->next_action = 'index.php?controller='.$this->controller_name.'&campaign_id='.
				$this->campaign_id.'&token='.Tools::getAdminTokenLite($this->controller_name);

				break;

			case 'AdminMarketingFStep5': /* ---------------------------------------------------- */

				if (empty($this->campaign_id))
				{
					Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
					exit;
				}
				$this->next_controller = $this->controller_name;
				$this->step_action = '5';
				$this->media = 'fax';
				$this->back_action = 'index.php?controller=AdminMarketingFStep3&campaign_id='.
				$this->campaign_id.'&token='.Tools::getAdminTokenLite('AdminMarketingFStep3');

				$this->next_action = 'index.php?controller='.$this->controller_name.'&campaign_id='.
				$this->campaign_id.'&token='.Tools::getAdminTokenLite($this->controller_name);

				$this->count_fax_recipients = $this->countFaxRecipientsDB();
				if ($this->count_fax_recipients > $this->module->default_remaining_fax)
					$this->buy_package = true;

				break;

			case 'AdminMarketingSStep4': /* ---------------------------------------------------- */

				if (empty($this->campaign_id))
				{
					Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
					exit;
				}
				$this->next_controller = $this->controller_name;
				$this->step_action = '4';
				$this->media = 'sms';
				$this->back_action = 'index.php?controller=AdminMarketingSStep2&campaign_id='.
				$this->campaign_id.'&token='.Tools::getAdminTokenLite('AdminMarketingSStep2');

				$this->next_action = 'index.php?controller='.$this->controller_name.'&campaign_id='.
				$this->campaign_id.'&token='.Tools::getAdminTokenLite($this->controller_name);

				$this->count_sms_recipients = $this->countSmsRecipientsDB();
				if ($this->count_sms_recipients > $this->module->default_remaining_sms)
					$this->buy_package = true;

				break;

			case 'AdminMarketingInscription':

				$this->next_controller = 'AdminMarketingX';
				$this->step_action = '1';
				$product = (string)Tools::getValue('product');

				if (Tools::strpos($product, 'fax-') !== false)
					$this->media = 'fax';
				elseif (Tools::strpos($product, 'sms-') !== false)
					$this->media = 'sms';
				else
					$this->media = 'email';

				$this->back_action = 'index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX');
				$this->next_action = 'index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX');
				break;

			default:

				Tools::redirectAdmin('index.php?controller=AdminMarketingX&token='.Tools::getAdminTokenLite('AdminMarketingX'));
				break;
		}

		// API initialization
		// ------------------
		include _PS_MODULE_DIR_.$this->module->name.'/controllers/admin/session_api.php';
		$this->session_api = new SessionApi();
	}

	public function initToolbarTitle()
	{
		parent::initToolbarTitle();
		$this->toolbar_title = $this->module->l('New subscriber', 'adminmarketinginscription');
	}

	public function renderList()
	{
		$this->getFieldsValues();

		if (!is_array($this->fields_form))
			$this->generateOpenForm();

		$output = parent::renderForm();

		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function generateOpenForm()
	{
		$this->fields_form = array(
			'legend' => array(
				'title' => sprintf($this->module->l('Link your Prestashop to Express-Mailing API (Step %s)', 'adminmarketinginscription'),
									$this->step_action),
				'icon' => 'icon-random'
			),
			'description' =>
$this->module->l('Please fill this form to connect your Prestashop to the Express-Mailing API and send your mailing.', 'adminmarketinginscription'),
			'input' => array(
				array (
					'type' => 'hidden',
					'lang' => false,
					'label' => 'Ref :',
					'name' => 'campaign_id',
					'col' => 1,
					'readonly' => 'readonly'
				),
				array (
					'type' => 'hidden',
					'lang' => false,
					'label' => 'Media :',
					'name' => 'media',
					'col' => 1,
					'readonly' => 'readonly'
				),
				array (
					'type' => 'text',
					'lang' => false,
					'label' => Translate::getAdminTranslation('Shop name', 'AdminStores').' :',
					'prefix' => '<i class="icon-home"></i>',
					'col' => 4,
					'name' => 'company_name',
					'validation' => 'isGenericName',
					'required' => true
				),
				array (
					'type' => 'text',
					'lang' => false,
					'label' => Translate::getAdminTranslation('Shop email', 'AdminStores').' :',
					'prefix' => '<i class="icon-envelope-o"></i>',
					'col' => 4,
					'name' => 'company_email',
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => Translate::getAdminTranslation('Shop address line 1', 'AdminStores').' :',
					'name' => 'company_address1',
					'col' => 4,
					'required' => true,
					'validation' => 'isAddress'
				),
				array(
					'type' => 'text',
					'label' => Translate::getAdminTranslation('Shop address line 2', 'AdminStores').' :',
					'col' => 4,
					'name' => 'company_address2',
					'validation' => 'isAddress'
				),
				array(
					'type' => 'text',
					'label' => Translate::getAdminTranslation('Zip/postal code', 'AdminStores').' :',
					'col' => 1,
					'validation' => 'isGenericName',
					'name' => 'company_zipcode',
					'required' => true
				),
				array(
					'type' => 'text',
					'label' => Translate::getAdminTranslation('City', 'AdminStores').' :',
					'validation' => 'isGenericName',
					'col' => 4,
					'name' => 'company_city',
					'required' => true
				),
				array(
					'type' => 'select',
					'label' => Translate::getAdminTranslation('Country', 'AdminStores').' :',
					'name' => 'country_id',
					'required' => true,
					'default_value' => (int)Configuration::get('PS_COUNTRY_DEFAULT'),
					'options' => array(
						'query' => Country::getCountries($this->context->language->id),
						'id' => 'id_country',
						'name' => 'name',
					)
				),
				array (
					'type' => 'text',
					'lang' => false,
					'label' => Translate::getAdminTranslation('Phone', 'AdminStores').' :',
					'validation' => 'isGenericName',
					'prefix' => '<i class="icon-phone"></i>',
					'col' => 4,
					'name' => 'company_phone',
					'required' => true
				),
				array (
					'type' => 'free',
					'lang' => false,
					'label' => $this->module->l('Choose your package :', 'adminmarketinginscription'),
					'name' => 'buy_package',
					'required' => true,
					'class' => 'alert-info'
				),
				array (
					'type' => 'free',
					'lang' => false,
					'label' => $this->module->l('Terms of Use :', 'adminmarketinginscription'),
					'name' => 'validate_cgu',
					'desc' => $this->module->l('A click on the button', 'adminmarketinginscription').' "'.
					$this->module->l('Link to Express-Mailing', 'adminmarketinginscription').'" '.
					$this->module->l('below, implies acceptance of the present rules.', 'adminmarketinginscription'),
					'required' => true
				)
			),
			'submit' => array(
				'title' => $this->module->l('Link to Express-Mailing', 'adminmarketinginscription'),
				'name' => 'submitInscription',
				'icon' => 'process-icon-next'
			),
			'buttons' => array(
				array (
					'href' => $this->back_action,
					'title' => $this->module->l('Back', 'adminmarketinginscription'),
					'icon' => 'process-icon-back'
				)
			)
		);

		// Remove or keep the block "Choose your package" !
		// ------------------------------------------------
		if (!$this->buy_package)
		{
			foreach ($this->fields_form['input'] as $key => $field)
			{
				if ($field['name'] == 'buy_package')
				{
					unset($this->fields_form['input'][$key]);
					break;
				}
			}
		}

		// Get the Terms of Use (CGU)
		// --------------------------
		switch ($this->media)
		{
			case 'email':
				$cgv_iframe = $this->module->l('https://www.express-mailing.com/actualites/termsofuse-email.php', 'adminmarketinginscription');
				break;

			case 'fax':
				$cgv_iframe = $this->module->l('https://www.express-mailing.com/actualites/termsofuse-fax.php', 'adminmarketinginscription');
				break;

			case 'sms':
				$cgv_iframe = $this->module->l('https://www.express-mailing.com/actualites/termsofuse-sms.php', 'adminmarketinginscription');
				break;
		}

		$this->fields_value['validate_cgu'] = '<iframe border="0" src="'.$cgv_iframe.
			'" width="100%" height="350"><a href="'.$cgv_iframe.'" target="_blank">'.
			$this->module->l('Download Terms of Use', 'adminmarketinginscription').
			'</a></iframe>';

		// Enfin, on récupère tous les tickets disponibles pour Prestashop
		// ---------------------------------------------------------------
		if ($this->buy_package && ($this->media == 'fax' || $this->media == 'sms'))
		{
			$response_array = array();
			$parameters = array('application_id' => $this->session_api->application_id);

			if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php',
												'common', 'account', 'enum_credits', $parameters, $response_array))
			{
				// Reuse translation in step0
				// --------------------------
				$this->context->smarty->assign(
					array(
						'remaining_fax' => $this->module->default_remaining_fax,
						'remaining_sms' => $this->module->default_remaining_sms,
						'count_fax' => $this->count_fax_recipients,
						'count_sms' => $this->count_sms_recipients,
						'fax_credits' => $this->module->l('fax credits'),
						'euro_symbol' => $this->module->l('€'),
						'sms_credits' => $this->module->l('sms credits'),
						'fax_per_unit' => $this->module->l('(Let %.3f € / page)'),
						'sms_per_unit' => $this->module->l('(Let %.3f € / sms)')
					)
				);

				// Puis on affiche les tickets dans smarty
				// ---------------------------------------
				$buy = $this->getTemplatePath().'marketing_inscription/buy_package.tpl';
				$tools = new EMTools;
				$this->context->smarty->assign('tool_date', $tools);

				if (($this->media == 'fax') && isset($response_array['fax']))
				{
					$this->context->smarty->assign('smarty_fax_tickets', $response_array['fax']);
					$this->fields_value['buy_package'] = $this->context->smarty->fetch($buy);
				}
				elseif (($this->media == 'sms') && isset($response_array['sms']))
				{
					$this->context->smarty->assign('smarty_sms_tickets', $response_array['sms']);
					$this->fields_value['buy_package'] = $this->context->smarty->fetch($buy);
				}
			}
		}
	}

	public function generateRescueForm()
	{
		$this->errors[] = sprintf($this->module->l('An account attached to your email address %s has already been registered', 'adminmarketinginscription'),
							Configuration::get('PS_SHOP_EMAIL'));

		$this->fields_form = array(
			'legend' => array(
				'title' => sprintf($this->module->l('Link your Prestashop to Express-Mailing API (Step %s)', 'adminmarketinginscription'),
									$this->step_action),
				'icon' => 'icon-warning-sign'
			),
			'description' => sprintf($this->module->l('We now resend your password to your Prestashop email address %s.', 'adminmarketinginscription'),
										Configuration::get('PS_SHOP_EMAIL')).'<br>'.
			$this->module->l('Simply copy-paste this password into the below text box.', 'adminmarketinginscription').'<br>'.
			$this->module->l('If you do not receive it, please call', 'adminmarketinginscription').
			' <b>'.$this->module->l('+33 169.313.961', 'adminmarketinginscription').'</b> '.
			$this->module->l('(Monday to Friday from 9am to 17pm)', 'adminmarketinginscription').' ...',
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
					'type' => _PS_MODE_DEV_ ? 'text' : 'hidden',
					'lang' => false,
					'label' => 'Media :',
					'name' => 'media',
					'col' => 1,
					'readonly' => 'readonly'
				),
				array (
					'type' => _PS_MODE_DEV_ ? 'text' : 'hidden',
					'lang' => false,
					'label' => 'Package :',
					'name' => 'product',
					'col' => 2,
					'readonly' => 'readonly'
				),
				array (
					'type' => 'text',
					'lang' => false,
					'label' => $this->module->l('Your Express-Mailing password', 'adminmarketinginscription'),
					'prefix' => '<i class="icon-key"></i>',
					'col' => 3,
					'name' => 'api_password',
					'required' => true
				)
			),
			'submit' => array(
				'title' => $this->module->l('Link to Express-Mailing', 'adminmarketinginscription'),
				'name' => 'submitRescue',
				'icon' => 'process-icon-next'
			),
			'buttons' => array(
				array (
					'href' => $this->back_action,
					'title' => $this->module->l('Back', 'adminmarketinginscription'),
					'icon' => 'process-icon-back'
				)
			)
		);
	}

	public function postProcess()
	{
		// On construit un login pour le compte
		// ------------------------------------
		// Si PS_SHOP_EMAIL = info@axalone.com
		// Alors login      = ps-info-axalone
		//   1/ On ajoute 'ps-' devant l'email
		//   2/ On retire l'extention .com à la fin
		//   3/ On remplace toutes les lettres accentuées par leurs équivalents sans accent
		//   4/ On remplace tous les sigles par des tirets
		//   5/ Enfin on remplace les doubles/triples tirets par des simples
		// --------------------------------------------------------------------------------
		$company_login = 'ps-'.Configuration::get('PS_SHOP_EMAIL');
		$company_login = Tools::substr($company_login, 0, strrpos($company_login, '.'));
		$company_login = EMTools::removeAccents($company_login);
		$company_login = Tools::strtolower($company_login);
		$company_login = preg_replace('/[^a-z0-9-]/', '-', $company_login);
		$company_login = preg_replace('/-{2,}/', '-', $company_login);

		$cart_product = (string)Tools::getValue('product', '');

		// Initialisation de l'API
		// -----------------------
		if (Tools::isSubmit('submitInscription'))
		{
			// On prépare l'ouverture du compte
			// --------------------------------
			$company_name = (string)Tools::getValue('company_name');
			$company_email = (string)Tools::getValue('company_email');
			$company_phone = (string)Tools::getValue('company_phone');
			$company_address1 = (string)Tools::getValue('company_address1');
			$company_address2 = (string)Tools::getValue('company_address2');
			$company_zipcode = (string)Tools::getValue('company_zipcode');
			$company_city = (string)Tools::getValue('company_city');
			$country_id = (int)Tools::getValue('country_id');

			$country = new Country($country_id);

			if (!is_object($country) || empty($country->id))
				$this->errors[] = Tools::displayError('Country is invalid');
			else
				$company_country = Country::getNameById($this->context->language->id, $country_id);

			if (!Validate::isGenericName($company_name))
				$this->errors[] = sprintf(Tools::displayError('The %s field is required.'), '« '.
					Translate::getAdminTranslation('Shop name', 'AdminStores').' »');

			if (!Validate::isEmail($company_email))
				$this->errors[] = sprintf(Tools::displayError('The %s field is required.'), '« '.
					Translate::getAdminTranslation('Shop email', 'AdminStores').' »');

			if (!Validate::isPhoneNumber($company_phone))
				$this->errors[] = sprintf(Tools::displayError('The %s field is required.'), '« '.
					Translate::getAdminTranslation('Phone', 'AdminStores').' »');

			if (!Validate::isAddress($company_address1))
				$this->errors[] = sprintf(Tools::displayError('The %s field is required.'), '« '.
					Translate::getAdminTranslation('Shop address line 1', 'AdminStores').' »');

			if ($country->zip_code_format && !$country->checkZipCode($company_zipcode))
				$this->errors[] = Tools::displayError('Your Zip/postal code is incorrect.').'<br />'.
				Tools::displayError('It must be entered as follows:').' '.
				str_replace('C', $country->iso_code, str_replace('N', '0', str_replace('L', 'A', $country->zip_code_format)));
			elseif (empty($company_zipcode) && $country->need_zip_code)
				$this->errors[] = Tools::displayError('A Zip/postal code is required.');
			elseif ($company_zipcode && !Validate::isPostCode($company_zipcode))
				$this->errors[] = Tools::displayError('The Zip/postal code is invalid.');

			if (!Validate::isGenericName($company_city))
				$this->errors[] = sprintf(Tools::displayError('The %s field is required.'), '« '.
					Translate::getAdminTranslation('City', 'AdminStores').' »');

			// We save these informations in the database
			// ------------------------------------------
			Db::getInstance()->insert('expressmailing_order_address', array(
				'id_address' => 1,
				'company_name' => pSQL($company_name),
				'company_email' => pSQL($company_email),
				'company_address1' => pSQL($company_address1),
				'company_address2' => pSQL($company_address2),
				'company_zipcode' => pSQL($company_zipcode),
				'company_city' => pSQL($company_city),
				'country_id' => $country_id,
				'company_country' => pSQL($company_country),
				'company_phone' => pSQL($company_phone),
				'product' => pSQL($cart_product)
				), false, false, Db::REPLACE
			);

			// If form contains 1 or more errors, we stop the process
			// ------------------------------------------------------
			if (is_array($this->errors) && count($this->errors))
				return false;

			// Open a session on Express-Mailing API
			// -------------------------------------
			if ($this->session_api->openSession())
			{
				// We create the account
				// ---------------------
				$response_array = array();

				$base_url = Configuration::get('PS_SSL_ENABLED') == 0 ? Tools::getShopDomain(true, true) : Tools::getShopDomainSsl(true, true);
				$module_dir = Tools::str_replace_once(_PS_ROOT_DIR_, '', _PS_MODULE_DIR_);
				$parameters = array(
					'login' => $company_login,
					'info_company' => $company_name,
					'info_email' => $company_email,
					'info_phone' => $company_phone,
					'info_address' => $company_address1."\r\n".$company_address2,
					'info_country' => $company_country,
					'info_zipcode' => $company_zipcode,
					'info_city' => $company_city,
					'info_phone' => $company_phone,
					'info_contact_firstname' => $this->context->employee->firstname,
					'info_contact_lastname' => $this->context->employee->lastname,
					'email_report' => $this->context->employee->email,
					'gift_code' => 'prestashop_'.Translate::getModuleTranslation('expressmailing', '3320', 'session_api'),
					'INFO_WWW' => $base_url.$module_dir.$this->module->name.'/campaigns/index.php'
				);

				if ($this->session_api->createAccount($parameters, $response_array))
				{
					// If the form include the buying process (field 'product')
					// We initiate a new cart with the product selected
					// --------------------------------------------------------
					if ($cart_product)
					{
						Tools::redirectAdmin('index.php?controller=AdminMarketingBuy&submitCheckout&campaign_id='.
								$this->campaign_id.'&media='.$this->next_controller.'&product='.$cart_product.
								'&token='.Tools::getAdminTokenLite('AdminMarketingBuy'));
						exit;
					}

					// Else we back to the mailing process
					// -----------------------------------
					Tools::redirectAdmin($this->next_action);
					exit;
				}

				if ($this->session_api->error == 11)
				{
					// Account already existe, we print the rescue form (with password input)
					// ----------------------------------------------------------------------
					$response_array = array();
					$parameters = array('login' => $company_login);
					$this->session_api->resendPassword($parameters, $response_array);
					$this->generateRescueForm();
					return;
				}
				else
				{
					// Other error
					// -----------
					$this->errors[] = sprintf($this->module->l('Unable to create an account : %s', 'adminmarketinginscription'),
										$this->session_api->getError());
					return false;
				}
			}
			else
			{
				$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketinginscription'),
									$this->session_api->getError());
				return false;
			}
		}
		elseif (Tools::isSubmit('submitRescue'))
		{
			// Rescue form : ask for existing password
			// ---------------------------------------
			if ($this->session_api->openSession())
			{
				$response_array = array();
				$password = trim((string)Tools::getValue('api_password'));
				$parameters = array('login' => $company_login, 'password' => $password);

				if ($this->session_api->connectUser($parameters, $response_array))
				{
					Db::getInstance()->insert('expressmailing', array(
						'api_login' => pSQL($company_login),
						'api_password' => pSQL($password)
						), false, false, Db::REPLACE
					);

					// If the form include the buying process (field 'product')
					// We initiate a new cart with the product selected
					// --------------------------------------------------------
					if ($cart_product)
					{
						Tools::redirectAdmin('index.php?controller=AdminMarketingBuy&submitCheckout&campaign_id='.
								$this->campaign_id.'&media='.$this->next_controller.'&product='.$cart_product.
								'&token='.Tools::getAdminTokenLite('AdminMarketingBuy'));
						exit;
					}

					// Else we back to the mailing process
					// -----------------------------------
					Tools::redirectAdmin($this->next_action);
					exit;
				}
			}

			$this->errors[] = sprintf($this->module->l('Error during communication with Express-Mailing API : %s', 'adminmarketinginscription'),
								$this->session_api->getError());
			return false;
		}
	}

	private function getFieldsValues()
	{
		$company_name = '';
		$company_email = '';
		$company_phone = '';
		$company_address1 = '';
		$company_address2 = '';
		$company_zipcode = '';
		$company_city = '';
		$company_country = 0;

		$cart_product = (string)Tools::getValue('product', '');

		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_order_address');
		$sql->where('id_address = 1');

		if ($result = Db::getInstance()->getRow($sql))
		{
			$company_name = (string)$result['company_name'];
			$company_email = (string)$result['company_email'];
			$company_phone = (string)$result['company_phone'];
			$company_address1 = (string)$result['company_address1'];
			$company_address2 = (string)$result['company_address2'];
			$company_zipcode = (string)$result['company_zipcode'];
			$company_city = (string)$result['company_city'];
			$company_country = (int)$result['company_country'];
		}

		// Default value for company_name
		// ------------------------------
		if (empty($company_name))
			$company_name = Configuration::get('BLOCKCONTACTINFOS_COMPANY');
		if (empty($company_name))
			$company_name = Configuration::get('CHEQUE_NAME');
		if (empty($company_name))
			$company_name = Configuration::get('BANK_WIRE_OWNER');
		if (empty($company_name))
			$company_name = Configuration::get('PS_SHOP_NAME');

		// Default value for company_email
		// -------------------------------
		if (empty($company_email))
			$company_email = Configuration::get('PS_SHOP_EMAIL');

		// Default value for company_phone
		// -------------------------------
		if (empty($company_phone))
			$company_phone = Configuration::get('PS_SHOP_PHONE');
		if (empty($company_phone))
			$company_phone = Configuration::get('BLOCKCONTACT_TELNUMBER');
		if (empty($company_phone))
			$company_phone = Configuration::get('BLOCKCONTACTINFOS_PHONE');
		if (empty($company_phone))
			$this->module->l('+33 ', 'adminmarketinginscription');

		// Default value for company_country
		// ---------------------------------
		if (empty($company_country))
			$company_country = Configuration::get('PS_SHOP_COUNTRY_ID');
		if (empty($company_country))
			$company_country = (int)$this->context->country->id;

		// Other default values
		// --------------------
		if (empty($company_address1))
			$company_address1 = Configuration::get('PS_SHOP_ADDR1');
		if (empty($company_address2))
			$company_address2 = Configuration::get('PS_SHOP_ADDR2');
		if (empty($company_zipcode))
			$company_zipcode = Configuration::get('PS_SHOP_CODE');
		if (empty($company_city))
			$company_city = Configuration::get('PS_SHOP_CITY');

		// Initialize the smarty variables
		// -------------------------------
		$this->fields_value['media'] = $this->media;
		$this->fields_value['campaign_id'] = $this->campaign_id;
		$this->fields_value['company_name'] = $company_name;
		$this->fields_value['company_email'] = $company_email;
		$this->fields_value['company_phone'] = $company_phone;
		$this->fields_value['company_address1'] = $company_address1;
		$this->fields_value['company_address2'] = $company_address2;
		$this->fields_value['company_zipcode'] = $company_zipcode;
		$this->fields_value['company_city'] = $company_city;
		$this->fields_value['company_country'] = $company_country;

		$this->fields_value['cart_product'] = $cart_product;
		$this->context->smarty->assign('cart_product', $cart_product);

		return true;
	}

	private function countFaxRecipientsDB()
	{
		// Count total recipients
		// ----------------------
		$req = new DbQuery();
		$req->select('SQL_NO_CACHE COUNT(DISTINCT(target))');
		$req->from('expressmailing_fax_recipients');
		$req->where('campaign_id = '.$this->campaign_id);

		return Db::getInstance()->getValue($req, false);
	}

	public function countSmsRecipientsDB()
	{
		// Count total recipients
		// ----------------------
		$req = new DbQuery();
		$req->select('SQL_NO_CACHE COUNT(DISTINCT(target))');
		$req->from('expressmailing_sms_recipients');
		$req->where('campaign_id = '.$this->campaign_id);

		return Db::getInstance()->getValue($req, false);
	}
}