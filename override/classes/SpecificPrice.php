<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class SpecificPrice extends SpecificPriceCore
{
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function ppInit()
	{
		self::$definition['fields'] = array_merge(self::$definition['fields'], array(
			'from_quantity' => array('type' => self::TYPE_FLOAT, 'validate' => 'validateSpecificPriceProductQuantity'), 		));
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function getSpecificPrice($id_product, $id_shop, $id_currency, $id_country, $id_group, $quantity, $id_product_attribute = null, $id_customer = 0, $id_cart = 0, $real_quantity = 0)
	{
		if (!SpecificPrice::isFeatureActive())
			return array();
		/*
		** The date is not taken into account for the cache, but this is for the better because it keeps the consistency for the whole script.
		** The price must not change between the top and the bottom of the page
		*/

		$key = ((int)$id_product.'-'.(int)$id_shop.'-'.(int)$id_currency.'-'.(int)$id_country.'-'.(int)$id_group.'-'.(float)$quantity.'-'.(int)$id_product_attribute.'-'.(int)$id_cart.'-'.(int)$id_customer.'-'.(float)$real_quantity);
		if (!array_key_exists($key, SpecificPrice::$_specificPriceCache))
		{
			$now = date('Y-m-d H:i:s');
			$query = '
				SELECT *, '.SpecificPrice::_getScoreQuery($id_product, $id_shop, $id_currency, $id_country, $id_group, $id_customer).'
				FROM `'._DB_PREFIX_.'specific_price`
				WHERE `id_product` IN (0, '.(int)$id_product.')
				AND `id_product_attribute` IN (0, '.(int)$id_product_attribute.')
				AND `id_shop` IN (0, '.(int)$id_shop.')
				AND `id_currency` IN (0, '.(int)$id_currency.')
				AND `id_country` IN (0, '.(int)$id_country.')
				AND `id_group` IN (0, '.(int)$id_group.')
				AND `id_customer` IN (0, '.(int)$id_customer.')
				AND
				(
					(`from` = \'0000-00-00 00:00:00\' OR \''.$now.'\' >= `from`)
					AND
					(`to` = \'0000-00-00 00:00:00\' OR \''.$now.'\' <= `to`)
				)
				AND id_cart IN (0, '.(int)$id_cart.') 
				AND IF(`from_quantity` > '.PP::getSpecificPriceFromQty((int)$id_product).', `from_quantity`, 0) <= ';

			$query .= (Configuration::get('PS_QTY_DISCOUNT_ON_COMBINATION') || !$id_cart || !$real_quantity) ? (float)$quantity : max(PP::getSpecificPriceFromQty((int)$id_product), (float)$real_quantity);
			$query .= ' ORDER BY `id_product_attribute` DESC, `from_quantity` DESC, `id_specific_price_rule` ASC, `score` DESC';

			SpecificPrice::$_specificPriceCache[$key] = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
		}
		return SpecificPrice::$_specificPriceCache[$key];
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function getQuantityDiscounts($id_product, $id_shop, $id_currency, $id_country, $id_group, $id_product_attribute = null, $all_combinations = false, $id_customer = 0)
	{
		if (!SpecificPrice::isFeatureActive())
			return array();

		$now = date('Y-m-d H:i:s');
		$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT *,
					'.SpecificPrice::_getScoreQuery($id_product, $id_shop, $id_currency, $id_country, $id_group, $id_customer).'
			FROM `'._DB_PREFIX_.'specific_price`
			WHERE
					`id_product` IN(0, '.(int)$id_product.') AND
					'.(!$all_combinations ? '`id_product_attribute` IN(0, '.(int)$id_product_attribute.') AND ' : '').'
					`id_shop` IN(0, '.(int)$id_shop.') AND
					`id_currency` IN(0, '.(int)$id_currency.') AND
					`id_country` IN(0, '.(int)$id_country.') AND
					`id_group` IN(0, '.(int)$id_group.') AND
					`id_customer` IN(0, '.(int)$id_customer.')
					AND
					(
						(`from` = \'0000-00-00 00:00:00\' OR \''.$now.'\' >= `from`)
						AND
						(`to` = \'0000-00-00 00:00:00\' OR \''.$now.'\' <= `to`)
					)
					ORDER BY `from_quantity` ASC, `id_specific_price_rule` ASC, `score` DESC
		');

		$targeted_prices = array();
		$last_quantity = array();

		foreach ($res as $specific_price)
		{
			if (!isset($last_quantity[(int)$specific_price['id_product_attribute']]))
				$last_quantity[(int)$specific_price['id_product_attribute']] = $specific_price['from_quantity'];
			elseif ($last_quantity[(int)$specific_price['id_product_attribute']] == $specific_price['from_quantity'])
				continue;

			$last_quantity[(int)$specific_price['id_product_attribute']] = $specific_price['from_quantity'];
			if ($specific_price['from_quantity'] > PP::getSpecificPriceFromQty((int)$id_product))
				$targeted_prices[] = $specific_price;
		}

		return $targeted_prices;
	}
}

SpecificPrice::ppInit();
