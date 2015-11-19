<?php
/**
* NOTICE OF LICENSE
*
* This source file is subject to the commercial software
* license agreement available through the world-wide-web at this URL:
* http://psandmore.com/licenses/sla
* If you are unable to obtain the license through the
* world-wide-web, please send an email to
* support@psandmore.com so we can send you a copy immediately.
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class PpropertiesProductModuleFrontController extends ModuleFrontController
{
	public $id_product;
	public $id_product_attribute;

	public function __construct()
	{
		parent::__construct();
		$this->ajax = true;
		$this->content_only = true;
	}

	public function postProcess()
	{
		$this->id_product = (int)Tools::getValue('id_product');
		$this->id_product_attribute = (int)Tools::getValue('id_product_attribute');
		$process = Tools::getValue('process');

		switch ($process)
		{
			case 'price':
				$this->processPrice();
				break;
			default:
				break;
		}

		die(1);
	}

	private function processPrice()
	{
		if ($this->id_product)
		{
			$product = new Product($this->id_product, true, $this->context->language->id);
			if ($product->id && $product->active)
			{
				$error = false;

				$properties = $product->productProperties();
				if ($properties['pp_ext'] == 1 && in_array($properties['pp_ext_policy'], array(0, 2)))
					$qty = PP::resolveInputQty(Tools::getValue('qty'), 0, $properties['pp_qty_step']);
				else
					$qty = PP::resolveInputQty(Tools::getValue('qty'), $properties['pp_qty_policy'], $properties['pp_qty_step']);
				if ($qty <= 0)
					$error = true;

				if (!$error)
				{
					if ($properties['pp_ext'] == 1 && in_array($properties['pp_ext_policy'], array(0, 2)))
					{
						$ext_prop_quantities = array();
						$ext_prop_qty_ratio = array();

						if ($properties['pp_ext_policy'] == 2)
							$prop = $product->productProp();
						$positions = count($properties['pp_ext_prop']);
						for ($position = 1; $position <= $positions; $position++)
						{
							$pp_ext_prop = $properties['pp_ext_prop'][$position];
							if ($properties['pp_ext_policy'] == 2)
							{
								$q = PP::productProp($prop, $this->id_product_attribute, $position, 'quantity');
								if ($q === false)
									$q = (float)$pp_ext_prop['default_quantity'];
								if ($q <= 0)
									$q = 1;
							}
							else
								$q = PP::resolveInputQty(Tools::getValue('pp_ext_prop_quantity_'.$position, 'default'),
														$properties['pp_qty_policy'],
														$pp_ext_prop['qty_step'],
														($pp_ext_prop['default_quantity'] > 0 ? $pp_ext_prop['default_quantity'] : 1));

							$ext_prop_quantities[$position] = $q;
							$ext_prop_qty_ratio[$position] = $properties['pp_ext_prop'][$position]['qty_ratio'];
							if ($q <= 0)
								$error = true;
							$min_qty = (float)$pp_ext_prop['minimum_quantity'];
							if ($min_qty > 0 && $q < $min_qty)
								$error = true;
							$max_qty = (float)$pp_ext_prop['maximum_quantity'];
							if ($max_qty > 0 && $q > $max_qty)
								$error = true;
						}
						if (!$error)
						{
							$ext_prop_qty = array();
							$quantity = $qty;
							$quantity_fractional = ($properties['pp_ext_method'] == 1 ? 1 : 0);
							$count = count($ext_prop_quantities);
							for ($position = 1; $position <= $count; $position++)
							{
								$value = $ext_prop_quantities[$position];
								$qty_ratio = $ext_prop_qty_ratio[$position];
								$ext_prop_qty[$position] = ($qty_ratio > 0 ? $value / $qty_ratio : $value);
								if ($properties['pp_ext_method'] == 1)
									$quantity_fractional *= $ext_prop_qty[$position];
								elseif ($properties['pp_ext_method'] == 2)
									$quantity_fractional += $ext_prop_qty[$position];
							}
						}
					}
					else
					{
						if (PP::qtyPolicyFractional($properties['pp_qty_policy']))
						{
							$quantity = 1;
							$quantity_fractional = $qty;
						}
						else
						{
							$quantity = $qty;
							$quantity_fractional = 0;
						}
					}
				}
				if (!$error)
				{
					$id_customer = (isset($this->context->customer) ? $this->context->customer->id : null);
					$this->id_product_attribute = ($this->id_product_attribute > 0 ? $this->id_product_attribute : null);
					$qty = array($quantity, $quantity_fractional);

					$address = new Address($this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
					$tax = (float)$product->getTaxesRate($address);
					$no_tax = Tax::excludeTaxeOption() || !$tax || Group::getPriceDisplayMethod($this->context->customer->id_default_group);

					$specific_price_output = null;
					$total = Product::getPriceStatic(
						$this->id_product,
						!$no_tax,
						$this->id_product_attribute,
						6,
						null,
						false,
						true,
						$qty,
						false,
						$id_customer,
						null,
						$address->id,
						$specific_price_output,
						true,
						true,
						$this->context
					);

					$total = PP::calcPrice($total, $quantity, $quantity_fractional, $this->id_product, false);
					$result = array('status' => 'success', 'total' => &$total);
					/*[HOOK ppropertiessmartprice]*/

					$result['total'] = Tools::ps_round($total, _PS_PRICE_COMPUTE_PRECISION_);
				}
			}
		}

		if (!isset($result))
			$result = array('status' => 'error');

		die(Tools::jsonEncode($result));
	}
}
