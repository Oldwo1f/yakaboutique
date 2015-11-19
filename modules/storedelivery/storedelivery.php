<?php

/**
 * Module Store Delivery
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class StoreDelivery extends Module {

    public function __construct() {
        $this->name = 'storedelivery';
        $this->tab = 'others';
		$this->version = '2.0.4';
		$this->author = 'Olivier Michaud';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7'); 
        $this->module_key = "6e94bd28ed779b2bac4d9f694fd5f133";

        parent::__construct();

        $this->displayName = $this->l('Store Delivery');
        $this->description = $this->l("Permit to choose delivery in store and send an email to store to inform them");
        
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Store Delivery Module?');
    }

    
    public function install() {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);
                                
        if (!parent::install() OR
            !$this->registerHook('displayHeader') OR
            !$this->registerHook('displayCarrierList') OR
            !$this->registerHook('actionValidateOrder') OR
            !$this->registerHook('actionObjectCarrierUpdateAfter') OR
            !Configuration::updateValue('STORE_DELIVERY_SEND_MAIL', '0') OR
            !Configuration::updateValue('STORE_DELIVERY_CARRIER', '') OR
            !Configuration::updateValue('STORE_DELIVERY_CARRIER_REFERENCE', '') OR
            !Configuration::updateValue('STORE_DELIVERY_DISPLAY_MAP', '1') OR
            !Configuration::updateValue('STORE_DELIVERY_HEIGHT_MAP', '300px') OR
            !Configuration::updateValue('STORE_DELIVERY_WIDTH_MAP', '725px')) {
                return false;
        }
        
        return true;
    }
    
    
    public function uninstall() {
        if (!parent::uninstall() OR
            !Configuration::deleteByName('STORE_DELIVERY_SEND_MAIL') OR
            !Configuration::deleteByName('STORE_DELIVERY_CARRIER') OR
            !Configuration::deleteByName('STORE_DELIVERY_CARRIER_REFERENCE') OR
            !Configuration::deleteByName('STORE_DELIVERY_DISPLAY_MAP') OR
            !Configuration::deleteByName('STORE_DELIVERY_HEIGHT_MAP') OR
            !Configuration::deleteByName('STORE_DELIVERY_WIDTH_MAP')) {
                return false;
        }

        return true;
    }
    
    
    public function getContent() {
		$this->_html = '<h2>'.$this->displayName.'</h2>';

		if (Tools::isSubmit('submitUpdate'))
		{
			if (Tools::getValue('STORE_DELIVERY_SEND_MAIL') !== false && Validate::isBool(Tools::getValue('STORE_DELIVERY_SEND_MAIL')))
				Configuration::updateValue('STORE_DELIVERY_SEND_MAIL', Tools::getValue('STORE_DELIVERY_SEND_MAIL'));

			if (Tools::getValue('STORE_DELIVERY_CARRIER') != false && Validate::isInt(Tools::getValue('STORE_DELIVERY_CARRIER'))) {
				Configuration::updateValue('STORE_DELIVERY_CARRIER', Tools::getValue('STORE_DELIVERY_CARRIER'));
            
                $carrierObj = new Carrier((int)Tools::getValue('STORE_DELIVERY_CARRIER'));
                Configuration::updateValue('STORE_DELIVERY_CARRIER_REFERENCE', $carrierObj->id_reference);
            }
            
            if (Tools::getValue('STORE_DELIVERY_DISPLAY_MAP') !== false && Validate::isBool(Tools::getValue('STORE_DELIVERY_DISPLAY_MAP')))
				Configuration::updateValue('STORE_DELIVERY_DISPLAY_MAP', Tools::getValue('STORE_DELIVERY_DISPLAY_MAP'));
            
            if (Tools::getValue('STORE_DELIVERY_HEIGHT_MAP') !== false && Validate::isString(Tools::getValue('STORE_DELIVERY_HEIGHT_MAP')))
				Configuration::updateValue('STORE_DELIVERY_HEIGHT_MAP', Tools::getValue('STORE_DELIVERY_HEIGHT_MAP'));
            
            if (Tools::getValue('STORE_DELIVERY_WIDTH_MAP') !== false && Validate::isString(Tools::getValue('STORE_DELIVERY_WIDTH_MAP')))
				Configuration::updateValue('STORE_DELIVERY_WIDTH_MAP', Tools::getValue('STORE_DELIVERY_WIDTH_MAP'));
            
			if (!Validate::isBool(Tools::getValue('STORE_DELIVERY_SEND_MAIL')) || !Validate::isInt(Tools::getValue('STORE_DELIVERY_CARRIER')) ||
                !Validate::isBool(Tools::getValue('STORE_DELIVERY_DISPLAY_MAP')) || !Validate::isString(Tools::getValue('STORE_DELIVERY_HEIGHT_MAP')) ||
                !Validate::isString(Tools::getValue('STORE_DELIVERY_WIDTH_MAP')))
                    $this->_html .= '<div class="alert">'.$this->l('Error! An information is invalid').'</div>';
		}
        
		return $this->_displayForm();
	}

    
	private function _displayForm() {
		$this->_html .= '
        <form method="post" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'">
			<fieldset>
				<legend><img src="'.$this->_path.'logo.png" width="16" height="16"/>'.$this->l('Settings').'</legend> 
				
                <label>'.$this->l('Carrier').'</label>
				<div class="margin-form">
                    <select name="STORE_DELIVERY_CARRIER">';
                        foreach(Carrier::getCarriers($this->context->language->id) as $carrier) {
                            $this->_html .= '<option value="'.$carrier['id_carrier'].'"'.(($carrier['id_carrier'] == Configuration::get('STORE_DELIVERY_CARRIER')) ? ' selected="selected"' : '').'>'.$carrier['name'].'</option>';
                        }
                    $this->_html .= '</select>
                    <p class="clear">'.$this->l('Choose here a carrier used for the delivery in store').'</p>
                </div>
				<div class="clear"></div>
                
                <label>'.$this->l('Send email to store to prevent new order').'</label>
				<div class="margin-form">
                    <input type="radio" name="STORE_DELIVERY_SEND_MAIL" id="sendmail_on" value="1" '.(Configuration::get('STORE_DELIVERY_SEND_MAIL') == '1' ? 'checked="checked" ' : '').'/>
					<label class="t" for="sendmail_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" /></label>
					<input type="radio" name="STORE_DELIVERY_SEND_MAIL" id="sendmail_off" value="0" '.(Configuration::get('STORE_DELIVERY_SEND_MAIL') == '0' ? 'checked="checked" ' : '').'/>
					<label class="t" for="sendmail_off"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" /></label>
					<p class="clear">'.$this->l('You must enter email address in each store admin').'</p>
				</div>
				<div class="clear"></div>
                
                <label>'.$this->l('Display Google Map').'</label>
				<div class="margin-form">
                    <input type="radio" name="STORE_DELIVERY_DISPLAY_MAP" id="map_on" value="1" '.(Configuration::get('STORE_DELIVERY_DISPLAY_MAP') == '1' ? 'checked="checked" ' : '').'/>
					<label class="t" for="map_on"><img src="../img/admin/enabled.gif" alt="'.$this->l('Enabled').'" title="'.$this->l('Enabled').'" /></label>
					<input type="radio" name="STORE_DELIVERY_DISPLAY_MAP" id="map_off" value="0" '.(Configuration::get('STORE_DELIVERY_DISPLAY_MAP') == '0' ? 'checked="checked" ' : '').'/>
					<label class="t" for="map_off"><img src="../img/admin/disabled.gif" alt="'.$this->l('Disabled').'" title="'.$this->l('Disabled').'" /></label>
					<p class="clear">'.$this->l("You can display a map of stores. Don't forgot to change default latitude/longitude in Store Contact page (under Preference/Store Contact admin page)").'</p>
				</div>
				<div class="clear"></div>
                
                <label>'.$this->l('Height of Google map').'</label>
				<div class="margin-form">
					<input type="text" name="STORE_DELIVERY_HEIGHT_MAP" value="'.Configuration::get('STORE_DELIVERY_HEIGHT_MAP').'"/>
                        <p class="clear">'.$this->l('Height of map in px (for exemple enter "300px")').'</p>
                </div>
				<div class="clear"></div>
                
                <label>'.$this->l('Width of Google map').'</label>
				<div class="margin-form">
					<input type="text" name="STORE_DELIVERY_WIDTH_MAP" value="'.Configuration::get('STORE_DELIVERY_WIDTH_MAP').'"/>
                    <p class="clear">'.$this->l('Width of map in px or % (for exemple enter "725px" or "100%"...)').'</p>
                </div>
				<div class="clear"></div>
                
				<div class="margin-form clear pspace">
                    <input type="submit" name="submitUpdate" value="'.$this->l('Update').'" class="button" />
                </div>
			</fieldset>
		</form>';

		return $this->_html;
	}
    
    
    public function hookDisplayHeader($params) {
        $this->context->controller->addCSS($this->_path.'views/css/storedelivery.css');
        $default_country = new Country((int)Configuration::get('PS_COUNTRY_DEFAULT'));
		$this->context->controller->addJS('http'.((Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE')) ? 's' : '').'://maps.google.com/maps/api/js?sensor=true&amp;region='.Tools::substr($default_country->iso_code, 0, 2));
    }
    
    
    public function hookDisplayCarrierList($params){
        $stores = Db::getInstance()->executeS('
			SELECT s.*, cl.name country, st.iso_code state
			FROM '._DB_PREFIX_.'store s
			'.Shop::addSqlAssociation('store', 's').'
			LEFT JOIN '._DB_PREFIX_.'country_lang cl ON (cl.id_country = s.id_country)
			LEFT JOIN '._DB_PREFIX_.'state st ON (st.id_state = s.id_state)
			WHERE s.active = 1 AND id_shop='.$this->context->shop->id.' AND cl.id_lang = '.(int)$this->context->language->id);
        for($i = 0; $i<count($stores); $i++) {
            $stores[$i]['has_store_picture'] = file_exists(_PS_STORE_IMG_DIR_.(int)($stores[$i]['id_store']).'.jpg');
            $stores[$i]['hours'] = str_replace("\n", "", $this->_renderStoreWorkingHours($stores[$i]));
        }

        $this->context->smarty->assign(array(
            'stores' => $stores,
			'carrier' => (int)Configuration::get('STORE_DELIVERY_CARRIER'),
            'map' => Configuration::get('STORE_DELIVERY_DISPLAY_MAP')
        ));
        
        /*Google maps var*/ 
        if(Configuration::get('STORE_DELIVERY_DISPLAY_MAP') == true) {
            $this->context->smarty->assign('heightMap',Configuration::get('STORE_DELIVERY_HEIGHT_MAP'));
            $this->context->smarty->assign('widthMap',Configuration::get('STORE_DELIVERY_WIDTH_MAP'));
            $this->context->smarty->assign('defaultLat',(float)Configuration::get('PS_STORES_CENTER_LAT'));
            $this->context->smarty->assign('defaultLong', (float)Configuration::get('PS_STORES_CENTER_LONG'));
            $this->context->smarty->assign('logo_store', Configuration::get('PS_STORES_ICON'));
            $this->context->smarty->assign('hasStoreIcon', file_exists(_PS_IMG_DIR_.Configuration::get('PS_STORES_ICON')));
        }

		return $this->display(__FILE__,'storedelivery.tpl');
	}

    
    public function hookActionObjectCarrierUpdateAfter($params){
		$carrierObj = new Carrier($params['object']->id);
		if($carrierObj->id_reference == Configuration::get('STORE_DELIVERY_CARRIER_REFERENCE')){
			Configuration::updateValue('STORE_DELIVERY_CARRIER',$params['object']->id);
		}
	}
    
    
    public function hookAjaxOpc() {
        $cookie = new Cookie('storedelivery');
        $cookie->__set('id_address_delivery', $this->context->cart->id_address_delivery);
        $cookie->__set('opc', true);
            
        if(Tools::isSubmit('storedelivery') && (int)Tools::getValue('storedelivery') != 0) {
            $store = new Store(Tools::getValue('storedelivery'));
            
            //Test if store address exist in address table
            (Tools::strlen($store->name) > 32) ? $storeName = Tools::substr(preg_replace("/[^a-zA-Zěščřžýáíéèêàô ]+/",'',$store->name), 0, 29).'...' : $storeName = preg_replace("/[^a-zA-Zěščřžýáíéèêàô ]+/",'',$store->name);
            $sql = 'SELECT id_address FROM '._DB_PREFIX_.'address WHERE alias=\''.addslashes($storeName).'\' AND address1=\''.addslashes($store->address1).'\' AND address2=\''.addslashes($store->address2).'\' AND postcode=\''.$store->postcode.'\' AND city=\''.addslashes($store->city).'\' AND id_country=\''.addslashes($store->id_country).'\' AND active=1 AND deleted=0';
            $id_address = Db::getInstance()->getValue($sql);
            
            //Create store adress if not exist for this user
            if(empty($id_address)){
                $country = new Country($store->id_country, $this->context->language->id);
                $address = new Address();
                $address->id_country = $store->id_country;
                $address->id_state = $store->id_state;
                $address->country = $country->name;
                (Tools::strlen($store->name) > 32) ? $address->alias = Tools::substr(preg_replace("/[^a-zA-Zěščřžýáíéèêàô ]+/",'',$store->name), 0, 29).'...' : $address->alias = preg_replace("/[^a-zA-Zěščřžýáíéèêàô ]+/",'',$store->name);
                (Tools::strlen($store->name) > 32) ? $address->lastname = Tools::substr(preg_replace("/[^a-zA-Zěščřžýáíéèêàô ]+/",'',$store->name), 0, 29).'...' : $address->lastname = preg_replace("/[^a-zA-Zěščřžýáíéèêàô ]+/",'',$store->name);
                $address->firstname = $this->l('Store of');
                $address->address1 = $store->address1;
                $address->address2 = $store->address2;
                $address->postcode = $store->postcode;
                $address->city = $store->city;
                $address->phone = $store->phone;
                $address->deleted = 0;              //create an address non deleted to register them in order
                $address->add();
                
                $id_address = $address->id;
            }
            
            $cookie->__set('id_address_delivery', $id_address);
        }
        
        die(Tools::jsonEncode(array('result' => "ok")));
    }
    
    
    public function hookActionValidateOrder($params){
        //Update Order info
        $order = $params['order'];
        $cart = $params['cart'];

        $cookie = new Cookie('storedelivery');
        if($cookie->__get('opc') != false) {
            $order->id_address_delivery = $cookie->__get('id_address_delivery');
        }
        else {
            $order->id_address_delivery = $cart->id_address_delivery;
        }
        $cookie->__unset('id_address_delivery');
        $cookie->__unset('opc');
        $order->update();

        if(Configuration::get('STORE_DELIVERY_SEND_MAIL') == 1 && (int)Configuration::get('STORE_DELIVERY_CARRIER') == (int)$params['cart']->id_carrier) {
            $this->_sendEmailToStore($params);
        }
	}
    
    
    private function _sendEmailToStore($params) {

		//Getting differents vars
		$id_lang = $this->context->language->id;
		$id_shop = $this->context->shop->id;
		$currency = $params['currency'];
		$order = $params['order'];
		$customer = $params['customer'];
		$configuration = Configuration::getMultiple(array('PS_MAIL_METHOD', 'PS_MAIL_SERVER', 'PS_MAIL_USER', 'PS_MAIL_PASSWD', 'PS_SHOP_NAME', 'PS_MAIL_COLOR','PS_SHOP_EMAIL'), $id_lang, null, $id_shop);
		$delivery = new Address((int)$order->id_address_delivery);
		$invoice = new Address((int)$order->id_address_invoice);
		$order_date_text = Tools::displayDate($order->date_add, (int)$id_lang);
		$carrier = new Carrier((int)$order->id_carrier);
		$message = $order->getFirstMessage();
        
		if (!$message || empty($message))
			$message = $this->l('No message');

		$items_table = '';

		$products = $params['order']->getProducts();
		$customized_datas = Product::getAllCustomizedDatas((int)$params['cart']->id);
		Product::addCustomizationPrice($products, $customized_datas);
		foreach ($products as $key => $product)
		{
			$unit_price = $product['product_price_wt'];

			$customization_text = '';
			if (isset($customized_datas[$product['product_id']][$product['product_attribute_id']]))
			{
				foreach ($customized_datas[$product['product_id']][$product['product_attribute_id']][$order->id_address_delivery] as $customization)
				{
					if (isset($customization['datas'][_CUSTOMIZE_TEXTFIELD_]))
						foreach ($customization['datas'][_CUSTOMIZE_TEXTFIELD_] as $text)
							$customization_text .= $text['name'].': '.$text['value'].'<br />';

					if (isset($customization['datas'][_CUSTOMIZE_FILE_]))
						$customization_text .= count($customization['datas'][_CUSTOMIZE_FILE_]).' image(s)<br />';

					$customization_text .= '---<br />';
				}

				$customization_text = rtrim($customization_text, '---<br />');
			}

			$items_table .=
				'<tr style="background-color:'.($key % 2 ? '#DDE2E6' : '#EBECEE').';">
					<td style="padding:0.6em 0.4em;">'.$product['product_reference'].'</td>
					<td style="padding:0.6em 0.4em;">
						<strong>'
							.$product['product_name'].(isset($product['attributes_small']) ? ' '.$product['attributes_small'] : '').(!empty($customization_text) ? '<br />'.$customization_text : '').
						'</strong>
					</td>
					<td style="padding:0.6em 0.4em; text-align:right;">';
                    $items_table .= Tools::displayPrice($unit_price, $currency, false);
                    $items_table .= '</td>
					<td style="padding:0.6em 0.4em; text-align:center;">'.(int)$product['product_quantity'].'</td>
					<td style="padding:0.6em 0.4em; text-align:right;">';
                    $items_table .= Tools::displayPrice(($unit_price * $product['product_quantity']), $currency, false);
				$items_table .= '</td></tr>';
		}
		foreach ($params['order']->getCartRules() as $discount)
		{
			$items_table .=
			'<tr style="background-color:#EBECEE;">
					<td colspan="4" style="padding:0.6em 0.4em; text-align:right;">'.$this->l('Voucher code:').' '.$discount['name'].'</td>
					<td style="padding:0.6em 0.4em; text-align:right;">-'.Tools::displayPrice($discount['value'], $currency, false).'</td>
			</tr>';
		}
		if ($delivery->id_state)
			$delivery_state = new State((int)$delivery->id_state);
		if ($invoice->id_state)
			$invoice_state = new State((int)$invoice->id_state);
        
        // Get Store info
        $id_store = DB::getInstance()->getValue("SELECT id_store FROM "._DB_PREFIX_."store WHERE address1='".$delivery->address1."' AND city='".$delivery->city."' AND postcode='".$delivery->postcode."'");
		$store = new Store($id_store);      //used for store email

		// Filling-in vars for email
		$template = 'new_order';
		$template_vars = array(
			'{firstname}' => $customer->firstname,
			'{lastname}' => $customer->lastname,
			'{email}' => $customer->email,
			'{delivery_block_txt}' => AddressFormat::generateAddress($delivery, array('avoid' => array()), "\n", ' ', array()),
			'{invoice_block_txt}' => AddressFormat::generateAddress($invoice, array('avoid' => array()), "\n", ' ', array()),
			'{delivery_block_html}' => AddressFormat::generateAddress($delivery, array('avoid' => array()), '<br />', ' ', array(
			'firstname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>',
			'lastname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>')),
			'{invoice_block_html}' => AddressFormat::generateAddress($invoice, array('avoid' => array()), '<br />', ' ', array(
			'firstname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].' font-weight:bold;">%s</span>',
			'lastname' => '<span style="color:'.$configuration['PS_MAIL_COLOR'].'; font-weight:bold;">%s</span>')),
			'{delivery_company}' => $delivery->company,
			'{delivery_firstname}' => $delivery->firstname,
			'{delivery_lastname}' => $delivery->lastname,
			'{delivery_address1}' => $delivery->address1,
			'{delivery_address2}' => $delivery->address2,
			'{delivery_city}' => $delivery->city,
			'{delivery_postal_code}' => $delivery->postcode,
			'{delivery_country}' => $delivery->country,
			'{delivery_state}' => $delivery->id_state ? $delivery_state->name : '',
			'{delivery_phone}' => $delivery->phone ? $delivery->phone : $delivery->phone_mobile,
			'{delivery_other}' => $delivery->other,
			'{invoice_company}' => $invoice->company,
			'{invoice_firstname}' => $invoice->firstname,
			'{invoice_lastname}' => $invoice->lastname,
			'{invoice_address2}' => $invoice->address2,
			'{invoice_address1}' => $invoice->address1,
			'{invoice_city}' => $invoice->city,
			'{invoice_postal_code}' => $invoice->postcode,
			'{invoice_country}' => $invoice->country,
			'{invoice_state}' => $invoice->id_state ? $invoice_state->name : '',
			'{invoice_phone}' => $invoice->phone ? $invoice->phone : $invoice->phone_mobile,
			'{invoice_other}' => $invoice->other,
			'{order_name}' => sprintf('%06d', $order->id),
			'{shop_name}' => $configuration['PS_SHOP_NAME'],
			'{date}' => $order_date_text,
			'{carrier}' => (($carrier->name == '0') ? $configuration['PS_SHOP_NAME'] : $carrier->name),
			'{payment}' => Tools::substr($order->payment, 0, 32),
			'{items}' => $items_table,
            '{total_tax_paid}' => Tools::displayPrice(($order->total_products_wt - $order->total_products) + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl), $currency, false),
			'{total_paid}' => Tools::displayPrice($order->total_paid, $currency),
			'{total_products}' => Tools::displayPrice($order->getTotalProductsWithTaxes(), $currency),
			'{total_discounts}' => Tools::displayPrice($order->total_discounts, $currency),
			'{total_shipping}' => Tools::displayPrice($order->total_shipping, $currency),
			'{total_wrapping}' => Tools::displayPrice($order->total_wrapping, $currency),
			'{currency}' => $currency->sign,
			'{message}' => $message
		);

		$iso = Language::getIsoById($id_lang);

		if (file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.txt') &&
			file_exists(dirname(__FILE__).'/mails/'.$iso.'/'.$template.'.html')) {

                Mail::Send(
                    $id_lang,
                    $template,
                    sprintf(Mail::l('New order - #%06d', $id_lang), $order->id),
                    $template_vars,
                    $store->email,
                    null,
                    Configuration::get('PS_SHOP_EMAIL'),
                    $configuration['PS_SHOP_NAME'],
                    null,
                    null,
                    dirname(__FILE__).'/mails/',
                    null,
                    $id_shop
                );
		}
    }
    
    private function _renderStoreWorkingHours($store) {
		
        $days = array();
		$days[1] = 'Monday';
		$days[2] = 'Tuesday';
		$days[3] = 'Wednesday';
		$days[4] = 'Thursday';
		$days[5] = 'Friday';
		$days[6] = 'Saturday';
		$days[7] = 'Sunday';
		
		$days_datas = array();
		$hours = array_filter(unserialize($store['hours']));
		if (!empty($hours))
		{
			for ($i = 1; $i < 8; $i++)
			{
				if (isset($hours[(int)($i) - 1]))
				{
					$hours_datas = array();
					$hours_datas['hours'] = $hours[(int)($i) - 1];
					$hours_datas['day'] = $days[$i];
					$days_datas[] = $hours_datas;
				}
			}
			$this->context->smarty->assign('days_datas', $days_datas);
			$this->context->smarty->assign('id_country', $store['id_country']);
			return $this->context->smarty->fetch(_PS_THEME_DIR_.'store_infos.tpl');
		}
		return false;
	}
}