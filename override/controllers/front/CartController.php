<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class CartController extends CartControllerCore
{
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	protected function processChangeProductInCart()
	{
		$mode = (Tools::getIsset('update') && $this->id_product) ? 'update' : 'add';

		if (!$this->id_product)
			$this->errors[] = Tools::displayError('Product not found', !Tools::getValue('ajax'));

		$product = new Product($this->id_product, true, $this->context->language->id);
		if (!$product->id || !$product->active || !$product->checkAccess($this->context->cart->id_customer))
		{
			$this->errors[] = Tools::displayError('This product is no longer available.', !Tools::getValue('ajax'));
			return;
		}

		$qty_factor = 1;
		$ext_qty_factor = 1;
		$ext_prop_quantities = null;
		$id_cart_product = 0;
		$qty_behavior = 0;
		$icp = (int)Tools::getValue('icp');
		$properties = $product->productProperties();
		if ($icp && $this->context->cart->id)
		{
						$cart_products = $this->context->cart->getProducts();
			if (count($cart_products))
			{
				foreach ($cart_products as $cart_product)
				{
					if ($icp == (int)$cart_product['id_cart_product'])
					{
						$id_cart_product = $icp;
						if ($mode == 'add')
						{
							if (Tools::getValue('qty') != 'default')
								$qty_factor = (int)Tools::getValue('qty');
							$_POST['qty'] = ((float)$cart_product['cart_quantity_fractional'] > 0 ? (float)$cart_product['cart_quantity_fractional'] : ($product->qtyStep() > 0 ? $product->qtyStep() : 1));
						}
						elseif ($mode == 'update')
							$qty_behavior = PP::qtyBehavior($product, $cart_product['cart_quantity']);
						break;
					}
				}
			}
		}
		else
		{
			if ($properties['pp_ext'] == 1 && in_array($properties['pp_ext_policy'], array(0, 2)))
			{
				$ext_prop_quantities = array();
				$ext_prop_qty_ratio = array();
				if ($properties['pp_ext_policy'] == 2)
				{
					$prop = $product->productProp();
					if ($this->id_product_attribute)
						$id_product_attribute = $this->id_product_attribute;
					else if ($product->hasAttributes())
						$id_product_attribute = Product::getDefaultAttribute($product->id);
					else
						$id_product_attribute = 0;
				}

				$positions = count($properties['pp_ext_prop']);
				for ($position = 1; $position <= $positions; $position++)
				{
					$pp_ext_prop = $properties['pp_ext_prop'][$position];
					if ($properties['pp_ext_policy'] == 2)
					{
						$q = PP::productProp($prop, $id_product_attribute, $position, 'quantity');
						if ($q === false)
							$q = (float)$pp_ext_prop['default_quantity'];
						if ($q <= 0)
							$q = 1;
					}
					else
						$q = PP::resolveInputQty(Tools::getValue('pp_ext_prop_quantity_'.$position, 'default'), $properties['pp_qty_policy'], $pp_ext_prop['qty_step'], ($pp_ext_prop['default_quantity'] > 0 ? $pp_ext_prop['default_quantity'] : 1));

					$ext_prop_quantities[$position] = $q;
					$ext_prop_qty_ratio[$position] = $properties['pp_ext_prop'][$position]['qty_ratio'];
					if ($q <= 0)
						$this->errors[] = Tools::displayError('Quantity not specified.', !Tools::getValue('ajax'));
					$min_qty = (float)$pp_ext_prop['minimum_quantity'];
					if ($min_qty > 0 && $q < $min_qty)
						$this->errors[] = Tools::displayError(sprintf('Please specify at least %s for %s', (string)PP::formatQty($min_qty), (string)$pp_ext_prop['property']), !Tools::getValue('ajax'));
					$max_qty = (float)$pp_ext_prop['maximum_quantity'];
					if ($max_qty > 0 && $q > $max_qty)
						$this->errors[] = Tools::displayError(sprintf('Please specify no more than %s for %s', (string)PP::formatQty($max_qty), (string)$pp_ext_prop['property']), !Tools::getValue('ajax'));
				}
				if (!$this->errors)
				{
					$ext_qty_factor = ($properties['pp_ext_method'] == 1 ? 1 : 0);
					$positions = count($ext_prop_quantities);
					for ($position = 1; $position <= $positions; $position++)
					{
						$value = $ext_prop_quantities[$position];
						$qty_ratio = $ext_prop_qty_ratio[$position];
						if ($properties['pp_ext_method'] == 1)
							$ext_qty_factor *= ($qty_ratio > 0 ? $value / $qty_ratio : $value);
						elseif ($properties['pp_ext_method'] == 2)
							$ext_qty_factor += ($qty_ratio > 0 ? $value / $qty_ratio : $value);
					}
				}
			}
		}

		if (!$this->errors)
		{
						if ($this->id_product_attribute)
			{
				$default_quantity = $product->attributeDefaultQty($this->id_product_attribute);
				$this->qty = $qty_factor * $this->resolveInputQty($properties, $default_quantity);
				if ($this->qty == 0)
					$this->errors[] = Tools::displayError('Quantity not specified.', !Tools::getValue('ajax'));
				else if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($this->id_product_attribute, $ext_qty_factor * $this->qty))
					$this->errors[] = Tools::displayError('There isn\'t enough product in stock.', !Tools::getValue('ajax'));
			}
			else if ($product->hasAttributes())
			{
				$min_quantity = ($product->out_of_stock == 2) ? !Configuration::get('PS_ORDER_OUT_OF_STOCK') : !$product->out_of_stock;
				$this->id_product_attribute = Product::getDefaultAttribute($product->id, $min_quantity);
								if (!$this->id_product_attribute)
					Tools::redirectAdmin($this->context->link->getProductLink($product));
				else
				{
					$default_quantity = $product->attributeDefaultQty($this->id_product_attribute);
					$this->qty = $qty_factor * $this->resolveInputQty($properties, $default_quantity);
					if ($this->qty == 0)
						$this->errors[] = Tools::displayError('Quantity not specified.', !Tools::getValue('ajax'));
					else if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && !Attribute::checkAttributeQty($this->id_product_attribute, $ext_qty_factor * $this->qty))
						$this->errors[] = Tools::displayError('There isn\'t enough product in stock.', !Tools::getValue('ajax'));
				}
			}
			else
			{
				$default_quantity = $product->defaultQty();
				$this->qty = $qty_factor * $this->resolveInputQty($properties, $default_quantity);
				if ($this->qty == 0)
					$this->errors[] = Tools::displayError('Quantity not specified.', !Tools::getValue('ajax'));
				else if (!$product->checkQty($ext_qty_factor * $this->qty))
					$this->errors[] = Tools::displayError('There isn\'t enough product in stock.', !Tools::getValue('ajax'));
			}
		}

				if (!$this->errors && ($mode == 'add' || ($mode == 'update' && $qty_behavior)))
		{
						if ($mode == 'add' && !$this->context->cart->id)
			{
				if (Context::getContext()->cookie->id_guest)
				{
					$guest = new Guest(Context::getContext()->cookie->id_guest);
					$this->context->cart->mobile_theme = $guest->mobile_theme;
				}
				$this->context->cart->add();
				if ($this->context->cart->id)
					$this->context->cookie->id_cart = (int)$this->context->cart->id;
			}

						if (!$product->hasAllRequiredCustomizableFields() && !$this->customization_id)
				$this->errors[] = Tools::displayError('Please fill in all of the required fields, and then save your customizations.', !Tools::getValue('ajax'));

			if (!$this->errors)
			{
				$cart_rules = $this->context->cart->getCartRules();
				$update_quantity = $this->context->cart->updateQty(
					$id_cart_product ? ($mode == 'add' ? $qty_factor : $this->qty) : ($ext_prop_quantities !== null ? $ext_qty_factor : $this->qty),
					$this->id_product, $this->id_product_attribute, $this->customization_id,
					($mode == 'update' ? 'update' : Tools::getValue('op', 'up')),
					$this->id_address_delivery, null, true, $id_cart_product, $ext_prop_quantities, $this->qty);
				if ($update_quantity < 0)
				{
										$minimal_quantity = ($this->id_product_attribute) ? $product->attributeMinQty($this->id_product_attribute) : $product->minQty();
					$this->errors[] = Tools::displayError(sprintf('You must add %s minimum quantity', $minimal_quantity), !Tools::getValue('ajax'));
				}
				elseif (!$update_quantity)
					$this->errors[] = Tools::displayError('You already have the maximum quantity available for this product.', !Tools::getValue('ajax'));
				elseif ((int)Tools::getValue('allow_refresh'))
				{
										$cart_rules2 = $this->context->cart->getCartRules();
					if (count($cart_rules2) != count($cart_rules))
						$this->ajax_refresh = true;
					else
					{
						$rule_list = array();
						foreach ($cart_rules2 as $rule)
							$rule_list[] = $rule['id_cart_rule'];
						foreach ($cart_rules as $rule)
							if (!in_array($rule['id_cart_rule'], $rule_list))
							{
								$this->ajax_refresh = true;
								break;
							}
					}
				}
			}
		}

		$removed = CartRule::autoRemoveFromCart();
		CartRule::autoAddToCart();
		if (count($removed) && (int)Tools::getValue('allow_refresh'))
			$this->ajax_refresh = true;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function displayAjax()
	{
		if ($this->errors)
			$this->ajaxDie(Tools::jsonEncode(array('hasError' => true, 'errors' => $this->errors)));
		if ($this->ajax_refresh)
			$this->ajaxDie(Tools::jsonEncode(array('refresh' => true)));

				$this->context->cookie->write();

		if (Tools::getIsset('summary'))
		{
			$result = array();
			if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1)
			{
				$groups = (Validate::isLoadedObject($this->context->customer)) ? $this->context->customer->getGroups() : array(1);
				if ($this->context->cart->id_address_delivery)
					$delivery_address = new Address($this->context->cart->id_address_delivery);
				$id_country = (isset($delivery_address) && $delivery_address->id) ? (int)$delivery_address->id_country : (int)Tools::getCountry();

				Cart::addExtraCarriers($result);
			}
			$result['summary'] = $this->context->cart->getSummaryDetails(null, true);
			$result['customizedDatas'] = Product::getAllCustomizedDatas($this->context->cart->id, null, true);
			$result['HOOK_SHOPPING_CART'] = Hook::exec('displayShoppingCartFooter', $result['summary']);
			$result['HOOK_SHOPPING_CART_EXTRA'] = Hook::exec('displayShoppingCart', $result['summary']);

			foreach ($result['summary']['products'] as $key => &$product)
			{
				$product['quantity_without_customization'] = $product['quantity'];
				if ($result['customizedDatas'] && isset($result['customizedDatas'][(int)$product['id_product']][(int)$product['id_product_attribute']]))
				{
					foreach ($result['customizedDatas'][(int)$product['id_product']][(int)$product['id_product_attribute']] as $addresses)
						foreach ($addresses as $customization)
							if ($product['id_cart_product'] == $customization['id_cart_product'])
								$product['quantity_without_customization'] -= (int)$customization['quantity'];
				}
				$product['price_without_quantity_discount'] = Product::getPriceStatic(
					$product['id_product'],
					!Product::getTaxCalculationMethod(),
					$product['id_product_attribute'],
					6,
					null,
					false,
					false
				);
				$ppropertiessmartprice_hook1 = null;

				if ($product['reduction_type'] == 'amount')
				{
					$reduction = (float)$product['price_wt'] - (float)$product['price_without_quantity_discount'];
					$product['reduction_formatted'] = Tools::displayPrice($reduction);
				}
			}
			if ($result['customizedDatas'])
				Product::addCustomizationPrice($result['summary']['products'], $result['customizedDatas']);

			$json = null;
			Hook::exec('actionCartListOverride', array('summary' => $result, 'json' => &$json));
			$this->ajaxDie(Tools::jsonEncode(array_merge($result, (array)Tools::jsonDecode($json, true))));

		}
				elseif (file_exists(_PS_MODULE_DIR_.'/blockcart/blockcart-ajax.php'))
		{
			$context = Context::getContext();
			$cart = $context->cart;
			if ($cart && isset($cart->last_icp))
				$context->smarty->assign('last_icp', $cart->last_icp);
			require_once(_PS_MODULE_DIR_.'/blockcart/blockcart-ajax.php');
		}
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	private function resolveInputQty($properties, $default_qty)
	{
		return PP::resolveInputQty(Tools::getValue('qty'), $properties['pp_qty_policy'], $properties['pp_qty_step'], $default_qty);
	}
}
