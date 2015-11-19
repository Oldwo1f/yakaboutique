<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class OrderController extends OrderControllerCore
{
    public function init()
	{
        parent::init();
        
        if(Tools::isSubmit('storedelivery') && (int)Tools::getValue('storedelivery') != 0) {
     
            //Save cookie only if previous id_adress wasn't a store
            $cookie = new Cookie('storedelivery');
            $cookie->__set('id_address_delivery', $this->context->cart->id_address_delivery);
        
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
                $address->firstname = " ";
                $address->address1 = $store->address1;
                $address->address2 = $store->address2;
                $address->postcode = $store->postcode;
                $address->city = $store->city;
                $address->phone = $store->phone;
                $address->deleted = 0;              //create an address non deleted to register them in order
                $address->add();
                
                $id_address = $address->id;
            }
            
            //Update cart info
            $cart = $this->context->cart;
            $cart->id_address_delivery = $id_address;
			$cart->update();
            
            //Change address of all product in cart else we are redirect on step Carrier because of function autostep or OrderController
            Db::getInstance()->update('cart_product', 
                    array('id_address_delivery' => 	(int)$id_address), 
                    $where = 'id_cart = '.$this->context->cart->id);
            
            //Change post carrier option else bad default carrier is saved by fonction processCarrier of ParentOrderController
            $array = array_values(Tools::getValue('delivery_option'));
            $_POST['delivery_option'] = array($id_address => $array[0]);
        }
        else {
            $cookie = new Cookie('storedelivery');
            $id_address_delivery = $cookie->__get('id_address_delivery');
            if($id_address_delivery != false && $this->context->cart->id_address_delivery != $id_address_delivery && Tools::isSubmit('storedelivery')) {
                $this->context->cart->id_address_delivery = $cookie->__get('id_address_delivery');
                $this->context->cart->update();
                
                //Change address of all product in cart else we are redirect on step Carrier because of function autostep or OrderController
                Db::getInstance()->update('cart_product', 
                        array('id_address_delivery' => 	(int)$cookie->__get('id_address_delivery')), 
                        $where = 'id_cart = '.$this->context->cart->id);

                //Change post carrier option else bad default carrier is saved by fonction processCarrier of ParentOrderController
                $array = array_values(Tools::getValue('delivery_option'));
                $_POST['delivery_option'] = array($cookie->__get('id_address_delivery') => $array[0]);
                
                $cookie->__unset('id_address_delivery');
            }
        }
    }
}
