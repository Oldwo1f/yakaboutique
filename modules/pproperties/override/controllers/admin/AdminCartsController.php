<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminCartsController extends AdminCartsControllerCore
{
	public function ajaxProcessUpdateQty()
	{
		if ($this->tabAccess['edit'] === '1')
		{
			$errors = array();
			if (!$this->context->cart->id)
				return;
			if ($this->context->cart->OrderExists())
				$errors[] = Tools::displayError('An order has already been placed with this cart.');
			elseif (!($id_product = (int)Tools::getValue('id_product')) || !($product = new Product((int)$id_product, true, $this->context->language->id)))
				$errors[] = Tools::displayError('Invalid product');
			elseif (!($qty = Tools::getValue('qty')) || $qty == 0)
				$errors[] = Tools::displayError('Invalid quantity');
			else
			{
				$icp = (int)Tools::getValue('icp');
				if ($icp != 'add' && (!($icp = (int)$icp) || $icp == 0))
					$errors[] = Tools::displayError('Invalid cart product reference');
			}

			if (!count($errors))
			{
				// Don't try to use a product if not instanciated before due to errors
				if (isset($product) && $product->id)
				{
					$id_product_attribute = Tools::getValue('id_product_attribute');
					if ($icp == 'add')
					{
						$id_cart_product = 0;
						$properties = $product->productProperties();
						$qty_policy = $properties['pp_qty_policy'];
						$qty = PP::resolveInputQty($qty, $qty_policy, $properties['pp_qty_step']);
						if (PP::qtyPolicyFractional($qty_policy))
						{
							$quantity_fractional = $qty;
							$qty = 1;
							$update_qty = $quantity_fractional;
						}
						else
						{
							$qty = (int)$qty;
							$quantity_fractional = 0;
							$update_qty = $qty;
						}
					}
					else
					{
						$cart_products = $this->context->cart->getProducts();
						if (count($cart_products))
						{
							foreach ($cart_products as $cart_product)
							{
								if ($icp == (int)$cart_product['id_cart_product'])
								{
									$id_cart_product = $icp;
									$qty = (int)$qty;
									$quantity_fractional = $cart_product['cart_quantity_fractional'];
									$update_qty = $qty;
									break;
								}
							}
						}
					}
					if (isset($id_cart_product))
					{
						if ($id_product_attribute != 0)
						{
							if (!Product::isAvailableWhenOutOfStock($product->out_of_stock)
								&& !Attribute::checkAttributeQty((int)$id_product_attribute, PP::resolveQty($qty, $quantity_fractional)))
								$errors[] = Tools::displayError('There is not enough product in stock.');
						}
						else
							if (!$product->checkQty(PP::resolveQty($qty, $quantity_fractional)))
								$errors[] = Tools::displayError('There is not enough product in stock.');
						if (!($id_customization = (int)Tools::getValue('id_customization', 0)) && !$product->hasAllRequiredCustomizableFields())
							$errors[] = Tools::displayError('Please fill in all required fields.');
						$this->context->cart->save();
					}
					else
						$errors[] = Tools::displayError('This product cannot be added to the cart.');
				}
				else
					$errors[] = Tools::displayError('This product cannot be added to the cart.');
			}

			if (!count($errors))
			{
				if ((int)$update_qty < 0)
				{
					$update_qty = str_replace('-', '', $update_qty);
					$operator = 'down';
				}
				else
					$operator = 'up';

				if (!($qty_upd = $this->context->cart->updateQty($update_qty, $id_product, (int)$id_product_attribute, (int)$id_customization, $operator, 0, null, true, $id_cart_product)))
					$errors[] = Tools::displayError('You already have the maximum quantity available for this product.');
				elseif ($qty_upd < 0)
				{
					$minimal_qty = $id_product_attribute ? $product->attributeMinQty((int)$id_product_attribute) : $product->minQty();
					$errors[] = sprintf(Tools::displayError('You must add a minimum quantity of %d', false), $minimal_qty);
				}
			}

			echo Tools::jsonEncode(array_merge($this->ajaxReturnVars(), array('errors' => $errors)));
		}
	}
}
