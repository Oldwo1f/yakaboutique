<?php
/**
* Product Properties Extension
*
* Extends product properties and add support for products with fractional
* units of measurements (for example: weight, length, volume).
*
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
*
* --- DO NOT REMOVE OR MODIFY THIS LINE PP_VERSION[1.6.0.14] ---
*/

class PPCore
{
	const PP_TEMPLATE_VERSION = 1;
	const PP_MS_DEFAULT       = 0;
	const PP_MS_METRIC        = 1;
	const PP_MS_NON_METRIC    = 2; /* imperial/US */

	private static $cache_templates = array();
	private static $cache_product_properties = array();

	public static function obtainQty($product)
	{
		return (int)(isset($product['cart_quantity']) ? $product['cart_quantity'] : $product['product_quantity']);
	}

	public static function obtainQtyFractional($product)
	{
		return (float)(isset($product['cart_quantity_fractional']) ? $product['cart_quantity_fractional'] : $product['product_quantity_fractional']);
	}

	public static function resolveQty($quantity, $quantity_fractional)
	{
		return ((float)$quantity_fractional > 0 ? $quantity * (float)$quantity_fractional : (int)$quantity);
	}

	public static function getProductProperties($product)
	{
		if ($product instanceof Product)
		{
			$key = $product->id.':'.(int)$product->id_pp_template;
			if (!isset(self::$cache_product_properties[$key]))
			{
				$properties = array();
				$properties['id_product'] = $product->id;
				PP::calcProductProperties($properties, null, (int)$product->id_pp_template);
				self::$cache_product_properties[$key] = $properties;
			}
			return self::$cache_product_properties[$key];
		}
		return self::getProductPropertiesByTemplateId(self::getProductTemplateId($product));
	}

	public static function getProductPropertiesByTemplateId($template_id)
	{
		$properties = array();
		self::calcProductProperties($properties, null, $template_id);
		return $properties;
	}

	public static function calcProductProperties(&$product, $data = null, $template_id = false, $extra = false)
	{
		if (is_array($product))
		{
			if ($data === null)
			{
				if ($template_id === false)
					$template_id = self::getProductTemplateId($product);
				$data = self::getTemplateById($template_id);
			}
			if (!is_array($data))
			{
				$data = array();
				$data['id_pp_template'] = 0;
				$data['qty_policy'] = 0;
				$data['qty_mode'] = 0;
				$data['display_mode'] = 0;
				$data['price_display_mode'] = 0;
				$data['measurement_system'] = self::PP_MS_METRIC;
				$data['unit_price_ratio'] = 0;
				$data['minimal_price_ratio'] = 0;
				$data['minimal_quantity'] = 0;
				$data['default_quantity'] = 0;
				$data['qty_step'] = 0;
				$data['ext'] = 0;
				$data['qty_available_display'] = 0;
				$data['hidden'] = 0;
				$data['css'] = '';
				$data['template_properties'] = array();
			}
			self::resolveTemplate($product, $data, false, $extra);
		}
	}

	public static function calcProductDisplayPrice($product, $product_properties = null, $price = null, $mode = null)
	{
		$key = null;
		$product_object = self::productAsObject($product);
		if ($product_object != null)
		{
			if ($product_properties === null)
				$product_properties = PP::getProductProperties($product_object);
			$display_retail_price = ((($product_properties['pp_display_mode'] & 2) == 2) &&
									(((($product_properties['pp_display_mode'] & 1) == 1) && ($mode != 'unit_price')) ||
									(!(($product_properties['pp_display_mode'] & 1) == 1) && ($mode == 'unit_price'))));
			if ($display_retail_price)
				$price = Product::getRetailPrice($product_object);
			elseif (($product_properties['pp_display_mode'] & 1) == 1)
			{
				if ($product_object->unit_price_ratio > 0)
				{
					if ($price === null)
						$price = self::calcProductPrice($product_object);
					if ($mode == 'unit_price')
						$price = Tools::ps_round((float)$price * $product_object->unit_price_ratio, _PS_PRICE_COMPUTE_PRECISION_);
					else
					{
						if (is_array($product) && isset($product['price_tax_exc']) && $product['price_tax_exc'] > 0 && (round($price, 8) > round($product['price_tax_exc'], 8)))
							$tax = $price / $product['price_tax_exc'];
						// we use $product_object->price because unit price is calculated based on the base price, without attribute impact on the price
						$price = Tools::ps_round($product_object->price / $product_object->unit_price_ratio, _PS_PRICE_COMPUTE_PRECISION_);
						if (($attribute = Product::getDefaultAttribute($product_object->id)) > 0)
						{
							$combination = $product_object->getAttributeCombinationsById($attribute, Context::getContext()->language->id);
							$price += Tools::ps_round($combination[0]['unit_price_impact'], _PS_PRICE_COMPUTE_PRECISION_);
						}
						if (isset($tax))
							$price = Tools::ps_round($price * $tax, _PS_PRICE_COMPUTE_PRECISION_);
					}
				}
			}
			if (($product_properties['pp_display_mode'] & 1) == 1)
			{
				if ($mode == 'unit_price')
				{
					if (!empty($product_properties['pp_price_text']))
						$key = 'pp_price_text';
				}
				else
				{
					if (!empty($product_properties['pp_unity_text']))
						$key = 'pp_unity_text';
				}
			}
			else
			{
				if ($mode == 'unit_price')
				{
					if (!empty($product_properties['pp_unity_text']))
						$key = 'pp_unity_text';
				}
				else
				{
					if (!empty($product_properties['pp_price_text']))
						$key = 'pp_price_text';
				}
			}
			if ($price === null)
				$price = self::calcProductPrice($product_object);
		}
		return array($key, $price);
	}

	private static function calcProductPrice($product)
	{
		$price_display = Product::getTaxCalculationMethod();
		return $product->getPrice(!$price_display || $price_display == 2, null, _PS_PRICE_DISPLAY_PRECISION_);
	}

	public static function getAllTemplates()
	{
		foreach (Language::getLanguages() as $language)
			self::getTemplates($language['id_lang'], $language['id_lang']);
		return self::$cache_templates;
	}

	public static function resetTemplates()
	{
		self::$cache_templates = array();
		self::$cache_product_properties = array();
	}

	public static function getTemplates($id_lang = false, $extra_id_lang = false)
	{
		if ($id_lang === false)
			$id_lang = (int)Context::getContext()->cookie->id_lang;
		if ($id_lang <= 0)
			$id_lang = Configuration::get('PS_LANG_DEFAULT');
		if (!isset(self::$cache_templates[$id_lang]))
		{
			$templates = array();
			$rows = Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.'pp_template`');
			foreach ($rows as $row)
			{
				$template = array();
				self::resolveTemplate($template, $row, $id_lang, true, $extra_id_lang);
				$templates[$template['id_pp_template']] = $template;
			}
			self::$cache_templates[$id_lang] = $templates;
		}
		return self::$cache_templates[$id_lang];
	}

	public static function getAdminProductsTemplates($current_id = 0, $id_lang = false, $extra_id_lang = false)
	{
		static $cache_bo_templates = array();
		if (!isset($cache_bo_templates[$current_id]))
		{
			$bo_templates = array();
			$templates = self::getTemplates($id_lang, $extra_id_lang);
			$bo = array();
			self::calcProductProperties($bo, false, 0, true);
			array_unshift($templates, $bo);
			foreach ($templates as $template)
			{
				if (($template['pp_bo_hidden'] == 0) || ($current_id == $template['id_pp_template']))
				{
					$bo = array();
					$bo['id_pp_template']      = $template['id_pp_template'];
					$bo['name'] = sprintf('#%s %s', $template['id_pp_template'], $template['name']);
					$bo['description']         = $template['description'];
					$bo['qty_policy']          = $template['pp_qty_policy'];
					$bo['qty_mode']            = $template['pp_qty_mode'];
					$bo['display_mode']        = $template['pp_display_mode'];
					$bo['price_display_mode']  = $template['pp_price_display_mode'];
					$bo['price_text']          = $template['pp_price_text'];
					$bo['unity_text']          = $template['pp_unity_text'];
					$bo['qty_text']            = $template['pp_qty_text'];
					$bo['bo_qty_text']         = $template['pp_bo_qty_text'];
					$bo['ratio']               = $template['pp_unit_price_ratio'];
					$bo['minimal_price_ratio'] = $template['pp_minimal_price_ratio'];
					$bo['min_qty']             = $template['pp_minimal_quantity'];
					$bo['bo_min_qty']          = $template['pp_bo_minimal_quantity'];
					$bo['default_quantity']    = $template['pp_default_quantity'];
					$bo['qty_step']            = $template['pp_qty_step'];
					$bo['explanation']         = $template['pp_explanation'];
					$bo['ext']                 = $template['pp_ext'];

					if (self::multidimensionalEnabled() && (int)$template['pp_ext'] == 1)
					{
						$bo['ext_policy'] = $template['pp_ext_policy'];
						$bo['ext_method'] = $template['pp_ext_method'];
						$bo['ext_title'] = $template['pp_ext_title'];
						$bo['ext_property'] = $template['pp_ext_property'];
						$bo['ext_text'] = $template['pp_ext_text'];
						$bo['qty_ext_text'] = $template['pp_qty_ext_text'];
						$bo['ext_prop'] = $template['pp_ext_prop'];
						foreach ($bo['ext_prop'] as $position => $ext_prop)
							$bo['ext_prop'][$position]['quantity'] = ($bo['ext_prop'][$position]['default_quantity'] > 0 ? $bo['ext_prop'][$position]['default_quantity'] : 1);
					}
					$bo_templates[] = $bo;
				}
			}

			$bo_templates[0]['name'] = ' ';
			$bo_templates[0]['qty_policy'] = -1;

			$cache_bo_templates[$current_id] = $bo_templates;
		}
		return $cache_bo_templates[$current_id];
	}

	public static function productExtProperties(&$product)
	{
		if (!array_key_exists('pp_ext_prop_data', $product))
		{
			$product['pp_ext_prop_data'] = null;
			if (self::multidimensionalEnabled())
				PSM::getPlugin('ppropertiesmultidimensional')->productExtProperties($product);
		}
		return $product['pp_ext_prop_data'];
	}

	public static function isPackCalculator($data)
	{
		return (is_array($data) && $data['pp_ext'] == 1 && $data['pp_ext_policy'] == 1);
	}

	public static function getTemplateExtProperty($template, $position, $property_name)
	{
		if (isset($template['pp_ext_prop']) && isset($template['pp_ext_prop'][$position]))
			return $template['pp_ext_prop'][$position][$property_name];
		return false;
	}

	public static function productProp($prop, $id_product_attribute, $position, $property_name)
	{
		if (is_array($prop))
		{
			foreach ($prop as $p)
				if ($p['id_product_attribute'] == (int)$id_product_attribute && $p['position'] == (int)$position)
					return $p[$property_name];
		}
		return false;
	}

	public static function qtyBehavior($product, $quantity)
	{
		$properties = PP::getProductProperties($product);
		return ($properties['pp_qty_policy'] != 0 && $properties['pp_ext'] == 0 && (int)$quantity == 1);
	}

	public static function productQtyPolicy($product)
	{
		if ($product instanceof Product)
			return $product->qtyPolicy();

		$qty_policy = 0;
		$id_pp_template = self::getProductTemplateId($product);
		if ($id_pp_template > 0)
		{
			$template = self::getTemplateById($id_pp_template);
			if (is_array($template) && isset($template['qty_policy']))
			{
				$q = (int)$template['qty_policy'];
				$qty_policy = ($q == 0 || $q == 1 || $q == 2 ? $q : 0);
			}
		}
		return $qty_policy;
	}

	/* Legacy use of quantity (items). NOT opposite productQtyPolicyFractional */
	public static function productQtyPolicyLegacy($product)
	{
		return self::qtyPolicyLegacy(self::productQtyPolicy($product));
	}

	/* Quantity in fractional unit. NOT opposite productQtyPolicyLegacy */
	public static function productQtyPolicyFractional($product)
	{
		return self::qtyPolicyFractional(self::productQtyPolicy($product));
	}

	/* Legacy use of quantity (items). NOT opposite qtyPolicyFractional */
	public static function qtyPolicyLegacy($qty_policy)
	{
		return ((int)$qty_policy == 0);
	}

	/* Quantity in fractional unit. NOT opposite qtyPolicyLegacy */
	public static function qtyPolicyFractional($qty_policy)
	{
		return ((int)$qty_policy == 2);
	}

	public static function normalizeProductQty($qty, $product)
	{
		return self::normalizeQty($qty, self::productQtyPolicy($product));
	}

	public static function normalizeQty($qty, $qty_policy)
	{
		return (self::qtyPolicyFractional($qty_policy) ? (float)str_replace(',', '.', $qty) : (int)$qty);
	}

	public static function resolveInputQty($qty, $qty_policy, $qty_step = 0, $default_qty = false)
	{
		if (($qty === false || $qty == 'default') && $default_qty !== false)
			$qty = $default_qty;
		$qty = abs(self::normalizeQty($qty, $qty_policy));
		if ($qty_step > 0)
		{
			$q = floor($qty / $qty_step);
			if (round($q * $qty_step, 8) < round($qty, 8))
				$qty = ($q + 1) * $qty_step;
		}
		return $qty;
	}

	public static function isMeasurementSystemFOActivated()
	{
		return ((int)Configuration::get('PP_MEASUREMENT_SYSTEM_FO') == 1);
	}

	public static function resolveMS($ms = false)
	{
		if ($ms !== false)
		{
			if ((int)$ms == self::PP_MS_METRIC)
				return (int)$ms;
			elseif ((int)$ms == self::PP_MS_NON_METRIC)
				return (int)$ms;
		}
		$ms = self::measurementSystemFO();
		return (($ms != self::PP_MS_NON_METRIC) ? self::PP_MS_METRIC : self::PP_MS_NON_METRIC);
	}

	private static function measurementSystemFO()
	{
		if (self::isMeasurementSystemFOActivated())
		{
			$context = Context::getContext();
			if (isset($context->controller) && $context->controller->controller_type == 'front')
				if (isset($context->cookie->pp_measurement_system_fo))
					return (int)$context->cookie->pp_measurement_system_fo;
		}
		return (int)Configuration::get('PP_MEASUREMENT_SYSTEM');
	}

	public static function formatQty($qty, $currency = null)
	{
		$qty = (float)$qty;
		return str_replace('.', self::getDecimalSign($currency), (string)$qty);
	}

	public static function calcPrice($base_price, $quantity, $quantity_fractional, $product = null, $round_type = -1)
	{
		if ($round_type !== false)
		{
			if ($round_type == -1)
				$round_type = Configuration::get('PS_ROUND_TYPE');
			switch ($round_type)
			{
				case Order::ROUND_TOTAL:
				case Order::ROUND_LINE:
					break;
				case Order::ROUND_ITEM:
					break;
				default:
					$round_type = Order::ROUND_ITEM;
					break;
			}
		}

		if ($round_type == Order::ROUND_ITEM)
			$base_price = Tools::ps_round($base_price, _PS_PRICE_COMPUTE_PRECISION_);
		if ((float)$quantity_fractional > 0)
		{
			$price = ($base_price * (float)$quantity_fractional);
			if ($round_type == Order::ROUND_ITEM)
				$price = Tools::ps_round($price, _PS_PRICE_COMPUTE_PRECISION_);
		}
		else
			$price = $base_price;
		$price = $price * (int)$quantity;

		if ($product !== null && ($id_pp_template = self::getProductTemplateId($product)) > 0)
		{
			$template = self::getTemplateById($id_pp_template);
			if (is_array($template) && isset($template['minimal_price_ratio']))
			{
				$ratio = (float)$template['minimal_price_ratio'];
				if ($ratio > 0)
				{
					$min_price = ($base_price * (float)$ratio);
					if ($round_type == Order::ROUND_ITEM)
						$min_price = Tools::ps_round($min_price, _PS_PRICE_COMPUTE_PRECISION_);
					if (round($price, 8) < round($min_price, 8))
						$price = $min_price;
				}
			}
		}
		return ($round_type === false || $round_type == Order::ROUND_ITEM ? $price : Tools::ps_round($price, _PS_PRICE_COMPUTE_PRECISION_));
	}

	public static function getDecimalSign($currency = null)
	{
		$currency = self::resolveCurrency($currency);
		$c_format = $currency->format;
		switch ($c_format)
		{
			/* X 0,000.00 */
			case 1:
				$ret = '.';
				break;
			/* 0 000,00 X*/
			case 2:
				$ret = ',';
				break;
			/* X 0.000,00 */
			case 3:
				$ret = ',';
				break;
			/* 0,000.00 X */
			case 4:
				$ret = '.';
				break;
			/* 0 000.00 X */
			case 5:
				$ret = '.';
				break;
		}
		return $ret;
	}

	public static function resolveCurrency($currency = null)
	{
		if ($currency === null || $currency === false)
			return Context::getContext()->currency;
		if (is_numeric($currency))
			return Currency::getCurrencyInstance((int)$currency);
		if ($currency instanceof Currency)
			return $currency;
		return Context::getContext()->currency;
	}

	public static function getSpecificPriceFromQty($product)
	{
		return (self::productQtyPolicyLegacy($product) ? 1 : 0);
	}

	public static function resolveProductId($obj)
	{
		if ($obj instanceof Product)
			$id = $obj->id;
		elseif ($obj instanceof OrderDetail)
			$id = $obj->product_id;
		elseif ($obj instanceof ObjectModel)
			$id = (isset($obj->id_product) ? $obj->id_product : 0);
		elseif (is_array($obj))
			$id = (isset($obj['id_product']) ? $obj['id_product'] : (isset($obj['product_id']) ? $obj['product_id'] : 0));
		elseif (is_numeric($obj))
			$id = (int)$obj;
		else
			$id = 0;
		return (int)$id;
	}

	public static function productAsObject($obj)
	{
		if ($obj instanceof Product && $obj->id)
			return $obj;
		$id = self::resolveProductId($obj);
		if ($id > 0)
			return new Product($id);
		return null;
	}

	public static function getProductTemplateId($obj)
	{
		if ($obj instanceof Product)
			return (int)$obj->id_pp_template;
		if (is_array($obj) && isset($obj['id_pp_template']))
			return $obj['id_pp_template'];

		$id = self::resolveProductId($obj);
		if ($id > 0)
			$id = (int)Db::getInstance()->getValue('SELECT `id_pp_template` FROM `'._DB_PREFIX_.'product` WHERE `id_product` = '.$id);
		return $id;
	}

	private static function resolveTemplate(&$template, $data = false, $id_lang = false, $extra = false, $extra_id_lang = false)
	{
		if (is_array($data))
		{
			$id_pp_template = $data['id_pp_template'];
			$template['id_pp_template'] = $id_pp_template;
			$template['pp_qty_policy'] = $data['qty_policy'];
			$template['pp_qty_mode'] = $data['qty_mode'];
			$template['pp_display_mode'] = $data['display_mode'];
			$template['pp_price_display_mode'] = $data['price_display_mode'];
			$template['pp_bo_measurement_system'] = $data['measurement_system'];
			$template['pp_unit_price_ratio'] = $data['unit_price_ratio'];
			$template['pp_minimal_price_ratio'] = $data['minimal_price_ratio'];
			$template['db_minimal_quantity'] = $data['minimal_quantity'];
			$template['db_default_quantity'] = $data['default_quantity'];
			$template['db_qty_step']         = $data['qty_step'];
			$template['pp_ext'] = $data['ext'];
			$template['pp_bo_qty_available_display'] = $data['qty_available_display'];
			$template['pp_bo_hidden'] = $data['hidden'];
			$template['pp_css'] = $data['css'];
			if (isset($data['template_properties'])) $template_properties = $data['template_properties'];
		}
		if (!isset($template_properties) || !is_array($template_properties))
		{
			$rows = Db::getInstance()->executeS('SELECT `pp_name`, `id_pp_property` FROM `'._DB_PREFIX_.'pp_template_property` WHERE `id_pp_template` = '.$template['id_pp_template']);
			$template_properties = array();
			if ($rows !== false)
				foreach ($rows as $row)
					$template_properties[$row['pp_name']] = $row['id_pp_property'];
		}

		$ms = self::resolveMS($template['pp_bo_measurement_system']);
		$template['pp_qty_available_display'] = $template['pp_bo_qty_available_display'];

		if ((int)$template['pp_ext'] == 1)
		{
			if ($template['pp_qty_available_display'] == 0)
				$template['pp_qty_available_display'] = 2;
			$db = Db::getInstance();
			$template_ext = $db->getRow('SELECT * FROM `'._DB_PREFIX_.'pp_template_ext` WHERE `id_pp_template` = '.$template['id_pp_template']);
			if ($template_ext !== false)
			{
				$template['pp_ext_policy'] = $template_ext['policy'];
				$template['pp_ext_method'] = $template_ext['method'];
				$template['pp_ext_title'] = self::resolveProperty($template_ext['title'], $id_lang, $ms);
				$template['pp_ext_property'] = self::resolveProperty($template_ext['property'], $id_lang, $ms);
				$template['pp_ext_text'] = self::resolveProperty($template_ext['text'], $id_lang, $ms);
				$template['pp_qty_ext_text'] = $template['pp_ext_text'];
			}
			$rows = $db->executeS('SELECT * FROM `'._DB_PREFIX_.'pp_template_ext_prop` WHERE `id_pp_template` = '.$template['id_pp_template'].' ORDER BY `position`');
			$pp_ext_prop = array();
			if ($rows !== false)
			{
				foreach ($rows as $row)
				{
					$ext = array();
					$ext['property'] = self::resolveProperty($row['property'], $id_lang, $ms);
					$ext['text'] = self::resolveProperty($row['text'], $id_lang, $ms);
					$ext['order_text'] = self::resolveProperty($row['order_text'], $id_lang, $ms);
					$ext['minimum_quantity'] = $row['minimum_quantity'];
					$ext['maximum_quantity'] = $row['maximum_quantity'];
					$ext['default_quantity'] = $row['default_quantity'];
					$ext['qty_step']         = $row['qty_step'];
					$ext['qty_ratio']        = $row['qty_ratio'];
					$pp_ext_prop[$row['position']] = $ext;
				}
			}
			$template['pp_ext_prop'] = $pp_ext_prop;

			if (! self::multidimensionalEnabled())
				$template['pp_bo_hidden'] = 1;
		}

		if (isset($template_properties['pp_explanation']))
		{
			$template['pp_bo_buy_block_index'] = (int)$template_properties['pp_explanation'];
			$template['pp_explanation'] = self::resolveProperty($template_properties['pp_explanation'], $id_lang, $ms);
		}
		else
		{
			$template['pp_bo_buy_block_index'] = 0;
			$template['pp_explanation'] = '';
		}
		$template['pp_unity_text'] = (isset($template_properties['pp_unity_text']) ? self::resolveProperty($template_properties['pp_unity_text'], $id_lang, $ms) : '');
		$template['pp_qty_text'] = (isset($template_properties['pp_qty_text']) ? self::resolveProperty($template_properties['pp_qty_text'], $id_lang, $ms) : '');
		$template['pp_price_text'] = (isset($template_properties['pp_price_text']) ? self::resolveProperty($template_properties['pp_price_text'], $id_lang, $ms) : '');
		$template['pp_unit_price_ratio'] = (float)(isset($template['pp_unit_price_ratio']) ? $template['pp_unit_price_ratio'] : 0);
		$template['pp_minimal_price_ratio'] = (float)(isset($template['pp_minimal_price_ratio']) ? $template['pp_minimal_price_ratio'] : 0);
		$template['pp_product_qty_text'] = (isset($template['pp_qty_ext_text']) ? $template['pp_qty_ext_text'] : $template['pp_qty_text']);

		if (isset($template['pp_qty_policy']))
		{
			$q = (int)$template['pp_qty_policy'];
			$template['pp_qty_policy'] = ($q == 0 || $q == 1 || $q == 2 ? $q : 0);
		}
		else
			$template['pp_qty_policy'] = 0;
		if ($template['pp_qty_policy'] == 0 || isset($template['pp_ext_method']))
		{
			if ((int)$template['db_minimal_quantity'] > 1)
				$template['pp_minimal_quantity'] = (int)$template['db_minimal_quantity'];
			else
				$template['pp_minimal_quantity'] = 1;
			$template['pp_default_quantity'] = ((int)$template['db_default_quantity'] > 1 ? (int)$template['db_default_quantity'] : 1);
			$template['pp_qty_step'] = ((int)$template['db_qty_step'] > 0 ? (int)$template['db_qty_step'] : 0);
			$template['pp_bo_minimal_quantity'] = 1; // used in back office as template's default value
			$template['pp_bo_qty_text'] = ($template['pp_qty_policy'] == 0 ? '' : $template['pp_product_qty_text']); // used in back office
		}
		else
		{
			if ((float)$template['db_minimal_quantity'] > 0)
				$template['pp_minimal_quantity'] = (float)$template['db_minimal_quantity'];
			else
				$template['pp_minimal_quantity'] = 0.1;
			$template['pp_default_quantity'] = ((float)$template['db_default_quantity'] > 0 ? (float)$template['db_default_quantity'] : 0.5);
			$template['pp_qty_step'] = ((float)$template['db_qty_step'] > 0 ? (float)$template['db_qty_step'] : 0);
			$template['pp_bo_minimal_quantity'] = 0; // used in back office as template's default value
			$template['pp_bo_qty_text'] = $template['pp_qty_text']; // used in back office
		}

		if ($extra)
		{
			$result = self::resolveTemplateAttributes(
								(int)$id_pp_template > 0 ? (int)$id_pp_template : 0,
								$extra_id_lang === false ? $id_lang : $extra_id_lang
							);

			$template['name'] = $result['name'];
			$template['auto_desc'] = ($ms != 2 ? $result['auto_desc_1'] : $result['auto_desc_2']);
			$template['description'] = ($ms != 2 ? $result['description_1'] : $result['description_2']);
		}
	}

	public static function getTemplateName($id_pp_template, $with_id = false, $id_lang = 0)
	{
		$cache_id = 'PP::templateNames-'.(int)$id_lang.'-'.($with_id ? 1 : 0);
		if (!Cache::isStored($cache_id))
		{
			if ((int)$id_lang == 0)
				$id_lang = Context::getContext()->language->id;
			$rows = Db::getInstance()->executeS('
				SELECT DISTINCT '.($with_id ? 'CONCAT("#", t.id_pp_template, " ", name)' : 'name').' as name, t.id_pp_template as id
				FROM '._DB_PREFIX_.'pp_template t
				LEFT JOIN `'._DB_PREFIX_.'pp_template_lang` tl
					ON (t.`id_pp_template` = tl.`id_pp_template`
					AND tl.`id_lang` = '.(int)$id_lang.')
				WHERE tl.`id_lang` = '.(int)$id_lang.'
				ORDER BY id');
			Cache::store($cache_id, PSM::arrayColumn($rows, 'name', 'id'));
		}
		$names = Cache::retrieve($cache_id);
		return (array_key_exists($id_pp_template, $names) ? $names[$id_pp_template] : '');
	}

	private static function resolveTemplateAttributes($id_pp_template, $id_lang)
	{
		if ($id_pp_template > 0)
		{
			$result = self::getTemplateAttributes($id_pp_template, $id_lang);
			if ($result === false)
			{
				$result = self::getTemplateAttributes($id_pp_template, Configuration::get('PS_LANG_DEFAULT'));
				if ($result === false)
					$result = self::getTemplateAttributes($id_pp_template, 1);
			}
		}
		else
			$result = false;

		if ($result === false)
		{
			$result = array();
			$result['name'] = '';
			$result['auto_desc_1'] = 1;
			$result['auto_desc_2'] = 1;
			$result['description_1'] = '';
			$result['description_2'] = '';
		}
		return $result;
	}

	private static function getTemplateById($id_pp_template)
	{
		return ($id_pp_template > 0 ? Db::getInstance()->getRow('SELECT * FROM `'._DB_PREFIX_.'pp_template` WHERE `id_pp_template` = '.(int)$id_pp_template) : false);
	}

	private static function getTemplateAttributes($id_pp_template, $id_lang)
	{
		if ($id_lang === false)
			$id_lang = (int)Context::getContext()->language->id;
		if ($id_lang <= 0)
			$id_lang = Configuration::get('PS_LANG_DEFAULT');
		if ((int)$id_pp_template > 0)
			return Db::getInstance()->getRow('
				SELECT * FROM `'._DB_PREFIX_.'pp_template_lang` WHERE `id_pp_template` = '
				.(int)$id_pp_template.' AND `id_lang` = '.(int)$id_lang);
		return false;
	}

	private static function resolveProperty($id_pp_property, $id_lang, $ms)
	{
		static $cache_properties = array();
		if ($id_lang === false)
			$id_lang = (int)Context::getContext()->cookie->id_lang;
		if ($id_lang <= 0)
			$id_lang = Configuration::get('PS_LANG_DEFAULT');
		$key = $id_pp_property.'_'.$id_lang.'_'.$ms;
		if (!isset($cache_properties[$key]))
		{
			$result = self::getProperty($id_pp_property, $id_lang, $ms);
			if ($result === false)
			{
				$result = self::getProperty($id_pp_property, Configuration::get('PS_LANG_DEFAULT'), $ms);
				if ($result === false)
				{
					$result = self::getProperty($id_pp_property, 1, $ms);
					if ($result === false)
						$result = '';
				}
			}
			$cache_properties[$key] = $result;
		}
		return $cache_properties[$key];
	}

	private static function getProperty($id_pp_property, $id_lang, $ms)
	{
		return Db::getInstance()->getValue('
			SELECT `text_'.((int)$ms != 2 ? '1' : '2').'` FROM `'._DB_PREFIX_.'pp_property_lang` WHERE `id_pp_property` = '
			.(int)$id_pp_property.' AND `id_lang` = '.(int)$id_lang);
	}

	public static function setQty($obj, $quantity)
	{
		if (self::qtyPolicyFractional(self::productQtyPolicy($obj)))
			self::hydrateQty($obj, 'quantity', $quantity);
		else
		{
			$obj->quantity = (int)$quantity;
			$obj->quantity_remainder = 0;
		}
	}

	public static function resolveIcp($icp)
	{
		return ((int)$icp == 0 ? (int)Tools::getValue('icp', 0) : (int)$icp);
	}

	public static function sqlIcp($icp)
	{
		return ((int)$icp > 0 ? ' AND id_cart_product='.(int)$icp : '');
	}

	public static function sqlQty($column, $prefix = false, $column_fractional = false)
	{
		$col = ($prefix === false ? '`'.$column : $prefix.'.`'.$column);
		if ($column_fractional === false)
			$col_frac = $col.'_fractional';
		else
			$col_frac = ($prefix === false ? '`'.$column_fractional : $prefix.'.`'.$column_fractional);
		return 'IF('.$col_frac.'`>0,'.$col.'`*'.$col_frac.'`,'.$col.'`)';
	}

	public static function safeOutput($string, $flags = false, $mode = 0, $lenient = false)
	{
		if (is_array($string))
		{
			array_walk($string, array('PP', 'safeOutputWalk'), array($flags, $mode, $lenient));
			return $string;
		}
		if ($mode == 0)
			$string = htmlentities($string, ($flags === false ? ENT_NOQUOTES : $flags), 'utf-8');
		elseif ($mode == 1)
			$string = htmlspecialchars($string, ($flags === false ? ENT_NOQUOTES : $flags), 'utf-8');
		// elseif ($mode == 2)
		// 	$string = htmlspecialchars($string, ($flags === false ? ENT_NOQUOTES : $flags), 'utf-8');
		$string = self::safeOutputReplace($string);
		return $string;
	}

	public static function safeOutputLenient($string, $flags = false, $mode = 0)
	{
		static $lenient = array(
			array(
				'search' => array('&lt;br&gt;', '&lt;br/&gt;', '&lt;br /&gt;'),
				'replace' => '<br>'
			),
			array(
				'search' => array('&lt;div&gt;', '&lt;/div&gt;', '&lt;span&gt;', '&lt;/span&gt;', '&lt;pre&gt;', '&lt;/pre&gt;'),
				'replace' => array('<div>', '</div>', '<span>', '</span>', '<pre>', '</pre>')
			),
		);
		$s = self::safeOutput($string, $flags, $mode, true);
		foreach ($lenient as $array)
			$s = str_replace($array['search'], $array['replace'], $s);
		return $s;
	}

	/* for javascript (string in javascript should be quoted using double quotes) */
	public static function safeOutputJS($string)
	{
		return self::safeOutput($string, ENT_COMPAT, 1);
	}

	/* for javascript (string in javascript should be quoted using double quotes) */
	public static function safeOutputLenientJS($string)
	{
		return self::safeOutputLenient($string, ENT_COMPAT, 1);
	}

	/* for HTML value field (input, textarea, option select, etc.) should be quoted using double quotes */
	public static function safeOutputValue($string)
	{
		return self::safeOutput($string, ENT_COMPAT, 2);
	}

	/* for HTML value field (input, textarea, option select, etc.) should be quoted using double quotes */
	public static function safeOutputLenientValue($string)
	{
		return self::safeOutput($string, ENT_COMPAT, 2, true);
	}

	private static function safeOutputWalk(&$value, $key, $data)
	{
		list($flags, $mode, $lenient) = $data;
		$value = self::safeOutput($value, $flags, $mode, $lenient);
	}

	private static function safeOutputReplace($s)
	{
		$s = str_replace('&lt;sup&gt;', '<sup>', $s);
		$s = str_replace('&lt;/sup&gt;', '</sup>', $s);
		return $s;
	}

	public static function wrap($text, $class = false, $wrap = '', $safeotput = true)
	{
		if (Tools::strlen($text) > 0)
		{
			$string = $wrap;
			if (is_string($class))
				$string .= '<span class="'.$class.'">';
			$string .= ($safeotput ? self::safeOutput($text) : $text);
			if (is_string($class))
				$string .= '</span>';
			if (strpos($wrap, '<div') === 0)
				$string .= '</div>';
			return $string;
		}
		return '';
	}

	public static function wrapProperty($properties, $property, $wrap = '')
	{
		return self::wrap($properties[$property], $property, $wrap);
	}

	public static function hydrateQty($obj, $key, $quantity)
	{
		$key_remainder = $key.'_remainder';
		if (is_array($obj))
		{
			$obj[$key] = (int)floor((float)$quantity);
			$obj[$key_remainder] = Tools::ps_round((float)$quantity - $obj[$key], 6);
		}
		else
		{
			$obj->{$key} = (int)floor((float)$quantity);
			$obj->{$key_remainder} = Tools::ps_round((float)$quantity - $obj->{$key}, 6);
		}
		return $obj;
	}

	public static function adminControllerDisplayListContentQuantity($echo, $tr, $key, $css = '')
	{
		if (isset($tr[$key]))
		{
			if ($key == 'real_quantity' || $key == 'qty_sold')
			{
				$value = $tr[$key];
				$id_product = $tr['id_product'];
			}
			else if ($key == 'stock')
			{
				$value = $tr[$key];
				$id_product = $tr['id'];
			}
			else
			{
				$key_remainder = $key.'_remainder';
				if (($key == 'sav_quantity' || $key == 'physical_quantity' || $key == 'usable_quantity') && isset($tr[$key_remainder]))
				{
					$value = $tr[$key] + $tr[$key_remainder];
					if (isset($tr['id_product']))
						$id_product = $tr['id_product'];
					else
					{
						if (isset($tr['id_stock']))
							$id_product = (int)Db::getInstance()->getValue(
								'SELECT `id_product` FROM `'._DB_PREFIX_.'stock` WHERE `id_stock` = '.(int)$tr['id_stock']);
					}
				}
			}
			if (is_numeric($value))
			{
				$properties = self::getProductProperties($id_product);
				return '<span class="pp_list_qty_wrapper '.$css.'">'.self::formatQty($value, Context::getContext()->currency).'<span class="pp_bo_qty_text_wrapper">'.self::wrapProperty($properties, 'pp_bo_qty_text', ' ').'</span></span>';
			}
		}
		return $echo;
	}

	public static function multidimensionalEnabled()
	{
		static $multidimensional_enabled = null;
		if ($multidimensional_enabled === null)
			$multidimensional_enabled = Module::isEnabled('ppropertiesmultidimensional');
		return $multidimensional_enabled;
	}

	public static function convertPriceFull($amount, Currency $currency_from = null, Currency $currency_to = null, $round = true)
	{
		if ($round)
			return Tools::convertPriceFull($amount, $currency_from, $currency_to);

		if ($currency_from === $currency_to)
			return $amount;

		if ($currency_from === null)
			$currency_from = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

		if ($currency_to === null)
			$currency_to = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));

		if ($currency_from->id == Configuration::get('PS_CURRENCY_DEFAULT'))
			$amount *= $currency_to->conversion_rate;
		else
		{
			$conversion_rate = ($currency_from->conversion_rate == 0 ? 1 : $currency_from->conversion_rate);
			// Convert amount to default currency (using the old currency rate)
			$amount = $amount / $conversion_rate;
			// Convert to new currency
			$amount *= $currency_to->conversion_rate;
		}
		return $amount;
	}

	/* smarty plugin */
	private static $smarty_generated = false;
	private static $smarty_cart = null;
	private static $smarty_order = null;
	private static $smarty_product = null;
	private static $smarty_currency = null;
	private static $smarty_bo = null;
	private static $smarty_pdf = false;
	private static $smarty_multiline_prefix = '';
	private static $smarty_multiline_suffix = '';

	public static function smartyCompile($tag, $mode, $item, &$smarty)
	{
		if ($tag == 'foreach')
		{
			$ret = '';
			$item = (string)$item;
			$key = trim((string)$item, '\'"');
			if ($key == 'product' || $key == 'newproduct' || $key == 'orderProduct')
			{
				if ($key == 'orderProduct')
				{
					if (strpos($smarty->_current_file, 'crossselling.tpl') !== false)
					{
						$generated = 'crossselling';
						$ignore = false;
					}
					else
						$ignore = true;
				}
				else
				{
					$generated = 'product';
					$ignore = stripos($smarty->_current_file, 'cart') || stripos($smarty->_current_file, 'order');
				}
				if (!$ignore)
				{
					switch ($mode)
					{
						case 'open':
							$ret = "<?php PP::smartyPPAssign(array('product'=>\$_smarty_tpl->tpl_vars[$item]->value, 'generated'=>'$generated'));?>";
							break;
						case 'close':
							$ret = '<?php PP::smartyPPAssign();?>';
							break;
						default:
							break;
					}
				}
			}
			return $ret;
		}
		return '';
	}

	public static function smartyPPAssign($params = null, &$smarty = null)
	{
		if ($params === null) $params = array();
		self::$smarty_generated = (array_key_exists('generated', $params) ? $params['generated'] : false);
		self::$smarty_cart = (array_key_exists('cart', $params) ? $params['cart'] : null);
		self::$smarty_order = (array_key_exists('order', $params) ? $params['order'] : null);
		self::$smarty_product = (array_key_exists('product', $params) ? $params['product'] : null);
		self::$smarty_currency = (array_key_exists('currency', $params) ? self::smartyGetCurrency($params) : null);
		self::$smarty_bo = (array_key_exists('bo', $params) ? $params['bo'] : null);
		self::$smarty_pdf = array_key_exists('pdf', $params);
		self::$smarty_multiline_prefix = (array_key_exists('multiline_prefix', $params) ? $params['multiline_prefix'] : self::$smarty_pdf ? '<div style="line-height:4px;">' : '');
		self::$smarty_multiline_suffix = (array_key_exists('multiline_suffix', $params) ? $params['multiline_suffix'] : self::$smarty_pdf ? '</div>' : '');
		self::resolveSmartyAssign();
	}

	public static function smartyFormatQty($params, &$smarty = null)
	{
		$qty = (array_key_exists('qty', $params) ? (float)$params['qty'] : 0);
		return self::formatQty($qty, self::smartyGetCurrency($params));
	}

	public static function smartyConvertQty($params, &$smarty = null)
	{
		list($data, $quantity, $quantity_fractional,) = self::resolveSmartyParams($params, $smarty);
		return ($quantity_fractional > 0 && self::qtyBehavior($data, $quantity)
				? self::formatQty((float)$quantity_fractional, self::smartyGetCurrency($params))
				: (int)$quantity);
	}

	public static function smartyDisplayQty($params, &$smarty = null)
	{
		list($data, $quantity, $quantity_fractional, $bo) = self::resolveSmartyParams($params, $smarty);
		if ($data != null)
		{
			$inline = (array_key_exists('m', $params) && $params['m'] == 'inline');
			if ($inline)
			{
				$params['qty'] = $quantity;
				$display = self::smartyFormatQty($params, $smarty);

				if ($bo)
				{
					$text_class = 'pp_bo_qty_text';
					$text = $data['pp_bo_qty_text'];
				}
				else
				{
					$text_class = 'pp_qty_text';
					$text = $data['pp_product_qty_text'];
				}
				if (Tools::strlen($text) > 0)
					$display .= ' <span class="'.$text_class.'">'.self::safeOutput($text).'</span>';
				return $display;
			}
			else
			{
				$unit = (array_key_exists('m', $params) && $params['m'] == 'unit');
				$fractional = (array_key_exists('m', $params) && $params['m'] == 'fractional');
				if ($quantity_fractional > 0)
				{
					$close = true;
					$qty_behavior = self::qtyBehavior($data, $quantity);
					$pp_ext_policy2 = ($data['pp_ext'] == 1 && $data['pp_ext_policy'] == 2);
					if ($bo)
					{
						$text_class = 'pp_bo_qty_text';
						$text = $data['pp_bo_qty_text'];
					}
					else
					{
						$text_class = 'pp_qty_text';
						$text = $data['pp_product_qty_text'];
					}
					if ($unit)
					{
						if ($qty_behavior)
						{
							$display = (Tools::strlen($text) > 0 ? '<span class="'.$text_class.'">'.self::safeOutput($text).'</span>' : '');
							$close = false;
						}
						else
							$display = '<br><span class="'.$text_class.' pp_with_br"><span class="pp_x">x </span>';
					}
					elseif ($fractional)
						$display = '<span class="pp_qty_wrapper"><span class="pp_x">x </span><span class="pp_qty">';
					else
					{
						if ($pp_ext_policy2)
						{
							$display = $quantity;
							$close = false;
						}
						else
						{
							if ($quantity == 1 || $qty_behavior)
								$display = '<span class="'.$text_class.'">';
							else
								$display = $quantity.'<br><span class="'.$text_class.' pp_with_br"><span class="pp_x">x </span>';
						}
					}
					if ($close)
					{
						$params['qty'] = $quantity_fractional;
						$display .= '<span class="pp_quantity_fractional">'.self::smartyFormatQty($params, $smarty).'</span>';

						if (Tools::strlen($text) > 0)
						{
							if ($fractional)
								$display .= ' <span class="'.$text_class.'">'.self::safeOutput($text).'</span>';
							else
								$display .= ' '.self::safeOutput($text);
						}
						$display .= '</span>';
					}
				}
				else
					$display = ($unit || $fractional ? '' : $quantity);
				return self::smartyAmendDisplay($params, $display);
			}
		}
		return ($quantity ? $quantity : '');
	}

	public static function smartyConvertPrice($params, &$smarty = null)
	{
		$currency = self::smartyGetCurrency($params);
		$price = (array_key_exists('price', $params) ? $params['price'] : null);
		$product = (array_key_exists('product', $params) ? $params['product'] : self::$smarty_product);
		if ($product != null)
		{
			$product_properties = PP::getProductProperties($product);
			$mode = (array_key_exists('m', $params) ? $params['m'] : null);
			list($key, $price) = PP::calcProductDisplayPrice($product, $product_properties, $price, $mode);
			$display = (is_numeric($price) ? Tools::displayPrice($price, $currency) : ($price === null ? 0 : $price));
			if ($key)
				$display .= ' <span class="'.$key.'">'.PP::safeOutput($product_properties[$key]).'</span>';
		}
		else
			$display = Tools::displayPrice(($price === null ? 0 : $price), $currency);
		return $display;
	}

	public static function smartyDisplayPrice($params, &$smarty = null)
	{
		$currency = self::smartyGetCurrency($params);
		$display = Tools::displayPrice(array_key_exists('price', $params) ? $params['price'] : $params['p'], $currency);

		list($data, $quantity, $quantity_fractional, $bo) = self::resolveSmartyParams($params, $smarty);
		if ($data != null)
		{
			$pack_calculator = (self::isPackCalculator($data) && $data['unit_price_ratio'] > 0);
			if ($quantity_fractional > 0 || $pack_calculator)
			{
				$total = (array_key_exists('m', $params) && $params['m'] == 'total');
				if ($total)
				{
					$text_key = ($bo && !$pack_calculator ? 'pp_bo_qty_text' : 'pp_product_qty_text');
					$text = $data[$text_key];
					if (Tools::strlen($text) > 0)
					{
						if ($quantity > 1 || $pack_calculator)
						{
							if ($pack_calculator)
								$qty = (int)$quantity * $data['unit_price_ratio'];
							else
								$qty = self::resolveQty($quantity, $quantity_fractional);
							$display .= '<br><span class="pp_price_text pp_with_br">';
							$display .= self::formatQty($qty, $currency);
							$display .= ' '.self::safeOutput($text);
							$display .= '</span>';
						}
					}
				}
				else
				{
					$text = $data['pp_price_text'];
					if (Tools::strlen($text) > 0)
						$display .= '<br><span class="pp_price_text pp_with_br">'.self::safeOutput($text).'</span>';
				}
				$display = self::smartyAmendDisplay($params, $display);
			}
		}
		return $display;
	}

	public static function smartyDisplayProductName($params, &$smarty = null)
	{
		$display = $params['name'];
		if (self::multidimensionalEnabled())
		{
			list($data, , ,) = self::resolveSmartyParams($params, $smarty);
			self::productExtProperties($data);
			$pp_ext_prop_data = $data['pp_ext_prop_data'];
			if (is_array($pp_ext_prop_data))
			{
				$s = '';
				$count = count($pp_ext_prop_data);
				for ($i = 0; $i < $count; $i++)
				{
					$s .= '<br><span'.(self::$smarty_pdf ? ' style="font-size:80%;"' : '').'>'.self::safeOutput($pp_ext_prop_data[$i]['property']);
					$s .= ' '.self::formatQty($pp_ext_prop_data[$i]['quantity']);
					$s .= ' '.self::safeOutput($pp_ext_prop_data[$i]['order_text']).'</span>';
				}
				$display .= $s;
			}
		}
		return self::smartyAmendDisplay($params, $display);
	}

	private static function resolveSmartyParams($params, &$smarty)
	{
		$bo = $data = $quantity = $quantity_fractional = null;
		$bo = (array_key_exists('bo', $params) ? $params['bo'] : self::$smarty_bo);
		$order = (array_key_exists('order', $params) ? $params['order'] : null);
		if ($order == null)
		{
			$cart = (array_key_exists('cart', $params) ? $params['cart'] : null);
			if ($cart == null)
			{
				$product = (array_key_exists('product', $params) ? $params['product'] : null);
				if ($product == null)
				{
					$order = self::$smarty_order;
					$cart = self::$smarty_cart;
					$product = self::$smarty_product;
				}
			}
		}
		if ($order != null)
		{
			$data = $order;
			$quantity = (int)$data['product_quantity'];
			$quantity_fractional = (float)$data['product_quantity_fractional'];
		}
		else
		{
			if ($cart != null)
			{
				$data = $cart;
				$quantity = (int)$data['cart_quantity'];
				$quantity_fractional = (float)$data['cart_quantity_fractional'];
			}
			else
			{
				if ($product != null)
				{
					$data = $product;
					$quantity = (int)$data['quantity'];
					$quantity_fractional = (float)$data['quantity_fractional'];
				}
			}
		}
		if (array_key_exists('quantity', $params))
			$quantity = $params['quantity'];
		if (array_key_exists('quantity_fractional', $params))
			$quantity_fractional = $params['quantity_fractional'];
		return array($data, $quantity, $quantity_fractional, $bo);
	}

	private static function resolveSmartyAssign()
	{
		switch (self::$smarty_generated)
		{
			case 'product':
				if (!is_array(self::$smarty_product)
					|| !array_key_exists('id_pp_template', self::$smarty_product)
					|| array_key_exists('cart_quantity_fractional', self::$smarty_product)
					|| array_key_exists('product_quantity_fractional', self::$smarty_product))
				{
					if (self::$smarty_product instanceof Product && self::$smarty_product->id)
						self::$smarty_product = array(
							'id_product' => self::$smarty_product->id,
							'id_pp_template' => self::$smarty_product->id_pp_template
						);
					else
						self::$smarty_product = null;
				}
				break;
			case 'crossselling':
				if (!is_array(self::$smarty_product))
					self::$smarty_product = null;
				break;
			default:
				break;
		}
	}

	public static function smartyGetCurrency($params)
	{
		if (array_key_exists('currency', $params))
		{
			$currency = $params['currency'];
			if (Validate::isLoadedObject($currency))
				self::$smarty_currency = $currency;
			elseif (is_numeric($params['currency']))
			{
				$currency = Currency::getCurrencyInstance((int)$params['currency']);
				if (Validate::isLoadedObject($currency))
					self::$smarty_currency = $currency;
			}
		}
		return self::$smarty_currency;
	}

	private static function smartyAmendDisplay($params, $display)
	{
		$prefix  = (array_key_exists('prefix', $params) ? $params['prefix'] : null);
		$suffix  = (array_key_exists('suffix', $params) ? $params['suffix'] : null);
		if (is_string($display) && strpos($display, '<br') !== false)
		{
			if ($prefix !== null || $suffix !== null || $display !== null)
			{
				$s = self::$smarty_multiline_prefix;
				if ($prefix !== null)
					$s .= $prefix;
				if ($display !== null)
					$s .= $display;
				if ($suffix !== null)
					$s .= $suffix;
				$display = $s.self::$smarty_multiline_suffix;
			}
		}
		return $display;
	}
}
