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

class AdminMarketingBuyController extends ModuleAdminController
{
	private $media = null;
	private $campaign_id = null;

	private $session_api = null;
	private $order_session = null;
	private $order_btn_proceed = 'checkout';

	public function __construct()
	{
		$this->name = 'adminmarketingbuy';
		$this->bootstrap = true;
		$this->module = 'expressmailing';
		$this->context = Context::getContext();
		$this->lang = false;
		$this->default_form_language = $this->context->language->id;

		$this->media = (string)Tools::getValue('media', 'AdminMarketingX');

		$this->campaign_id = (int)Tools::getValue('campaign_id', '0');
		$this->order_session = (string)Tools::getValue('order_session');
		$this->order_product = (string)Tools::getValue('product');

		if (empty($this->order_session) && empty($this->order_product))
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
		$this->toolbar_title = $this->module->l('New order', 'adminmarketingbuy');
	}

	public function postProcess()
	{
		// Update cart product quantity
		// ----------------------------
		$submit_qty = Tools::getValue('submitQty', null);
		if (is_array($submit_qty))
		{
			foreach (array_keys($submit_qty) as $key)
			{
				$response_array = array();
				$parameters = array(
					'order_session' => $this->order_session,
					'product_ref' => $key,
					'product_quantity' => (int)Tools::getValue('qty_'.$key)
				);

				if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php',
													'common', 'order', 'update', $parameters, $response_array))
				{
					$this->context->smarty->assign('order_session', $this->order_session);
					$this->context->smarty->assign('cart', $response_array);
					return true;
				}
			}
		}
	}

	public function renderList()
	{
		// if none, create a new order_session
		// -----------------------------------
		if (empty($this->order_session))
			if (!$this->createNewOrder())
				return;

		// Back success
		// ------------
		if (Tools::isSubmit('success'))
		{
			// Generate the invoice
			// --------------------
			$this->generateInvoice();

			// And display the payment successful transaction
			// ----------------------------------------------
			$order = $this->getOrder();
			if (is_array($order))
			{
				$this->context->smarty->assign('order_session', $this->order_session);
				$this->context->smarty->assign('order', $order);

				$status = $this->getTemplatePath().'marketing_buy/buy_success.tpl';
				$footer = $this->getTemplatePath().'footer.tpl';
				return $this->context->smarty->fetch($status).$this->context->smarty->fetch($footer);
			}
		}

		// Back error
		// ------------
		if (Tools::isSubmit('error'))
		{
			// Retrieve the cart status
			// ------------------------
			$response = array();
			$parameters = array('order_session' => $this->order_session);

			if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php', 'common', 'order', 'enum', $parameters, $response))
				$this->errors[] = $response['payment_last_error'];
		}

		$this->fields_value['order_session'] = $this->order_session;

		// Validate the cart and display billing address
		// ---------------------------------------------
		if (Tools::isSubmit('submitCheckout'))
		{
			$order = $this->getOrder();
			$media = Tools::substr($order['order_product'], 0, Tools::strpos($order['order_product'], '-'));
			if (!$this->session_api->connectFromCredentials($media))
				Tools::redirectAdmin('index.php?controller=AdminMarketingInscription&product='.$order['order_product'].'&token='.
					Tools::getAdminTokenLite('AdminMarketingInscription'));
			$this->order_btn_proceed = 'address';
			$this->getAddress();
			$this->displayAddress();
			return parent::renderForm();
		}

		// Validate the cart and display checkout (with payments buttons)
		// --------------------------------------------------------------
		if (Tools::isSubmit('submitAddress'))
		{
			if (!$this->checkAddress())
			{
				$this->order_btn_proceed = 'address';
				$this->getAddress();
				$this->displayAddress();
				return parent::renderForm();
			}

			$this->order_btn_proceed = 'pay';
			$this->getPayments();
			$this->getAddress();
		}

		// Display the cart
		// ----------------
		$this->getCurrency();
		$this->getCart();
		$buy = $this->getTemplatePath().'marketing_buy/buy_order.tpl';
		$output = $this->context->smarty->fetch($buy);

		// Display the footer
		// ------------------
		$footer = $this->getTemplatePath().'footer.tpl';
		$output .= $this->context->smarty->fetch($footer);

		return $output;
	}

	public function displayAjax()
	{
		// Retrieve the cart status
		// ------------------------
		$response = array();
		$parameters = array('order_session' => $this->order_session);

		if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php', 'common', 'order', 'enum', $parameters, $response))
			die($response['account_credited']);

		// Payment is not yet processed
		// ----------------------------
		die('False');
	}

	private function getCurrency()
	{
		// Prepare cart with currency
		// --------------------------
		$currency = new Currency($this->context->currency->id);
		$this->context->smarty->assign(array (
			'currency' => $currency,
			'order_session' => $this->order_session,
			'order_btn_proceed' => $this->order_btn_proceed
		));
	}

	private function getPayments()
	{
		$response_array = array();
		$parameters = array('order_session' => $this->order_session);

		if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php',
											'common', 'order', 'enum_payments', $parameters, $response_array))
		{
			$this->context->smarty->assign('payments', $response_array);
			return true;
		}

		$this->errors[] = sprintf($this->module->l('Unable to get cart\'s payment methods : %s'), $this->session_api->getError());
		return false;
	}

	private function getCart()
	{
		// Get cart content (or empty)
		// ---------------------------
		$cart = array();
		$parameters = array('order_session' => $this->order_session);
		$this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php', 'common', 'order', 'enum', $parameters, $cart);
		$this->context->smarty->assign('cart', $cart);
		return $cart;
	}

	private function createNewOrder()
	{
		// URL for back payment transaction
		// --------------------------------
		$current_uri = Tools::getShopProtocol().$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
		$current_uri .= '?controller='.$this->context->controller->controller_name;
		$current_uri .= '&order_session=%%session%%';
		$current_token = '&token='.Tools::getAdminTokenLite($this->context->controller->controller_name);

		$order_session = null;
		$parameters = array(
			'application_id' => $this->session_api->application_id,
			'cart_source' => 'Prestashop',
			'account_email' => Configuration::get('PS_SHOP_EMAIL'),
			'country_iso' => $this->context->language->iso_code,
			'back_url_success' => $current_uri.'&success'.$current_token,
			'back_url_error' => $current_uri.'&error'.$current_token
		);

		// account_id can be different for email, sms or fax
		// -------------------------------------------------
		$product = (string)Tools::getValue('product');

		if (Tools::strpos($product, 'fax-') !== false)
		{
			if ($this->session_api->connectFromCredentials('fax'))
				$parameters['account_id'] = $this->session_api->account_id;
		}
		elseif (Tools::strpos($product, 'sms-') !== false)
		{
			if ($this->session_api->connectFromCredentials('sms'))
				$parameters['account_id'] = $this->session_api->account_id;
		}
		else
		{
			if ($this->session_api->connectFromCredentials('email'))
				$parameters['account_id'] = $this->session_api->account_id;
		}

		// Create the order
		// ----------------
		if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php',
											'common', 'order', 'initialize', $parameters, $order_session))
			$this->order_session = $order_session;
		else
		{
			$this->errors[] = sprintf($this->module->l('Unable to create a cart : %s'), $this->session_api->getError());
			return false;
		}

		// Store the order_session into local database
		// -------------------------------------------
		Db::getInstance()->insert('expressmailing_order_cart', array(
			'order_session' => pSQL($this->order_session),
			'order_product' => pSQL($this->order_product),
			'campaign_media' => pSQL($this->media),
			'campaign_id' => (int)$this->campaign_id
		));

		// Add the product into the cart
		// -----------------------------
		$response_array = array();
		$parameters = array(
			'order_session' => $this->order_session,
			'product_ref' => $this->order_product,
			'product_quantity' => 1
		);

		if ($this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php',
											'common', 'order', 'add', $parameters, $response_array))
		{
			$this->context->smarty->assign('cart', $response_array);
			return true;
		}
		else
		{
			$this->errors[] = sprintf($this->module->l('Unable to create a cart : %s'), $this->session_api->getError());
			return false;
		}

		return false;
	}

	private function getOrder()
	{
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_order_cart');
		$sql->where('order_session = \''.pSQL($this->order_session).'\'');

		return Db::getInstance()->getRow($sql);
	}

	private function getAddress()
	{
		// Get last customer informations in database
		// ------------------------------------------
		$sql = new DbQuery();
		$sql->select('*');
		$sql->from('expressmailing_order_address');
		$sql->where('id_address = 1');

		$address = Db::getInstance()->getRow($sql);
		$this->context->smarty->assign('inscription', $address);

		$this->fields_value['company_name'] = $address['company_name'];
		$this->fields_value['company_email'] = $address['company_email'];
		$this->fields_value['company_phone'] = $address['company_phone'];
		$this->fields_value['company_address1'] = $address['company_address1'];
		$this->fields_value['company_address2'] = $address['company_address2'];
		$this->fields_value['company_zipcode'] = $address['company_zipcode'];
		$this->fields_value['company_city'] = $address['company_city'];
		$this->fields_value['country_id'] = $address['country_id'];

		return $address;
	}

	private function checkAddress()
	{
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
			'country_id' => (int)$country_id,
			'company_country' => pSQL($company_country),
			'company_phone' => pSQL($company_phone)
			), false, false, Db::REPLACE
		);

		// If no error we update the cart
		// ------------------------------
		if (!count($this->errors))
		{
			$response = array();
			$parameters = array(
				'order_session' => $this->order_session,
				'account_email' => $company_email
			);

			$this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php', 'common', 'order', 'update', $parameters, $response);
		}

		return !count($this->errors);
	}

	private function displayAddress()
	{
		// Need to center button into footer-panel
		// ---------------------------------------
		$this->addCSS(_PS_MODULE_DIR_.'expressmailing/views/css/footer-center.css');

		// Build the form
		// --------------
		$this->fields_form = array(
			'legend' => array(
				'title' => $this->module->l('Your billing address', 'adminmarketingbuy'),
				'icon' => 'icon-home'
			),
			'input' => array(
				array(
					'type' => _PS_MODE_DEV_ ? 'text' : 'hidden',
					'lang' => false,
					'label' => 'Session :',
					'name' => 'order_session',
					'col' => 3,
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
					'default_value' => (int)$this->context->country->id,
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
				)
			),
			'submit' => array(
				'title' => $this->module->l('Proceed to Payment ...', 'adminmarketingbuy'),
				'name' => 'submitAddress',
				'icon' => 'process-icon-payment',
				'class' => 'btn btn-default center-block'
			)
		);
	}

	private function generateInvoice()
	{
		$response_array = array();
		$parameters = array();

		$order = $this->getOrder();
		foreach ($order as $key => $value)
			if (!empty($value)) $parameters[$key] = $value;

		$address = $this->getAddress();
		foreach ($address as $key => $value)
			if (!empty($value)) $parameters[$key] = $value;

		$this->session_api->callExternal('http://www.express-mailing.com/api/cart/ws.php',
										'common', 'invoice', 'generate', $parameters, $response_array);
	}
}
