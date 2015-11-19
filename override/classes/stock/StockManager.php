<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class StockManager extends StockManagerCore
{
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getProductPhysicalQuantities($id_product, $id_product_attribute, $ids_warehouse = null, $usable = false)
	{
		if (!is_null($ids_warehouse))
		{
						if (!is_array($ids_warehouse))
				$ids_warehouse = array($ids_warehouse);

						$ids_warehouse = array_map('intval', $ids_warehouse);
			if (!count($ids_warehouse))
				return 0;
		}
		else
			$ids_warehouse = array();

		$query = new DbQuery();
		$query->select('SUM('.($usable ? 's.usable_quantity+s.usable_quantity_remainder' : 's.physical_quantity+s.physical_quantity_remainder').')');
		$query->from('stock', 's');
		$query->where('s.id_product = '.(int)$id_product);
		if (0 != $id_product_attribute)
			$query->where('s.id_product_attribute = '.(int)$id_product_attribute);

		if (count($ids_warehouse))
			$query->where('s.id_warehouse IN('.implode(', ', $ids_warehouse).')');

		return (float)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getProductRealQuantities($id_product, $id_product_attribute, $ids_warehouse = null, $usable = false)
	{
		if (!is_null($ids_warehouse))
		{
						if (!is_array($ids_warehouse))
				$ids_warehouse = array($ids_warehouse);

						$ids_warehouse = array_map('intval', $ids_warehouse);
		}

		$client_orders_qty = 0;

				if (!Pack::isPack($id_product) && $in_pack = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
			'SELECT id_product_pack, quantity FROM '._DB_PREFIX_.'pack
			WHERE id_product_item = '.(int)$id_product.'
			AND id_product_attribute_item = '.($id_product_attribute ? (int)$id_product_attribute : '0')))
		{
			foreach ($in_pack as $value)
			{
				if (Validate::isLoadedObject($product = new Product((int)$value['id_product_pack'])) &&
					($product->pack_stock_type == 1 || $product->pack_stock_type == 2 || ($product->pack_stock_type == 3 && Configuration::get('PS_PACK_STOCK_TYPE') > 0)))
				{
					$query = new DbQuery();
					$query->select('od.product_quantity, od.product_quantity_fractional, od.product_quantity_refunded');
					$query->from('order_detail', 'od');
					$query->leftjoin('orders', 'o', 'o.id_order = od.id_order');
					$query->where('od.product_id = '.(int)$value['id_product_pack']);
					$query->leftJoin('order_history', 'oh', 'oh.id_order = o.id_order AND oh.id_order_state = o.current_state');
					$query->leftJoin('order_state', 'os', 'os.id_order_state = oh.id_order_state');
					$query->where('os.shipped != 1');
					$query->where('o.valid = 1 OR (os.id_order_state != '.(int)Configuration::get('PS_OS_ERROR').'
								   AND os.id_order_state != '.(int)Configuration::get('PS_OS_CANCELED').')');
					$query->groupBy('od.id_order_detail');
					if (count($ids_warehouse))
						$query->where('od.id_warehouse IN('.implode(', ', $ids_warehouse).')');
					$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
					if (count($res))
						foreach ($res as $row)
							$client_orders_qty += PP::resolveQty($row['product_quantity'] - $row['product_quantity_refunded'], $row['product_quantity_fractional']);
				}
			}
		}


				if (!Pack::isPack($id_product) || (Pack::isPack($id_product) && Validate::isLoadedObject($product = new Product((int)$id_product))
			&& $product->pack_stock_type == 0 || $product->pack_stock_type == 2 ||
					($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 0 || Configuration::get('PS_PACK_STOCK_TYPE') == 2))))
		{
						$query = new DbQuery();
			$query->select('od.product_quantity, od.product_quantity_fractional, od.product_quantity_refunded');
			$query->from('order_detail', 'od');
			$query->leftjoin('orders', 'o', 'o.id_order = od.id_order');
			$query->where('od.product_id = '.(int)$id_product);
			if (0 != $id_product_attribute)
				$query->where('od.product_attribute_id = '.(int)$id_product_attribute);
			$query->leftJoin('order_history', 'oh', 'oh.id_order = o.id_order AND oh.id_order_state = o.current_state');
			$query->leftJoin('order_state', 'os', 'os.id_order_state = oh.id_order_state');
			$query->where('os.shipped != 1');
			$query->where('o.valid = 1 OR (os.id_order_state != '.(int)Configuration::get('PS_OS_ERROR').'
						   AND os.id_order_state != '.(int)Configuration::get('PS_OS_CANCELED').')');
			$query->groupBy('od.id_order_detail');
			if (count($ids_warehouse))
				$query->where('od.id_warehouse IN('.implode(', ', $ids_warehouse).')');
			$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
			if (count($res))
				foreach ($res as $row)
					$client_orders_qty += PP::resolveQty($row['product_quantity'] - $row['product_quantity_refunded'], $row['product_quantity_fractional']);
		}
				$query = new DbQuery();

		$query->select('sod.quantity_expected, sod.quantity_received');
		$query->from('supply_order', 'so');
		$query->leftjoin('supply_order_detail', 'sod', 'sod.id_supply_order = so.id_supply_order');
		$query->leftjoin('supply_order_state', 'sos', 'sos.id_supply_order_state = so.id_supply_order_state');
		$query->where('sos.pending_receipt = 1');
		$query->where('sod.id_product = '.(int)$id_product.' AND sod.id_product_attribute = '.(int)$id_product_attribute);
		if (!is_null($ids_warehouse) && count($ids_warehouse))
			$query->where('so.id_warehouse IN('.implode(', ', $ids_warehouse).')');

		$supply_orders_qties = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

		$supply_orders_qty = 0;
		foreach ($supply_orders_qties as $qty)
			if ($qty['quantity_expected'] > $qty['quantity_received'])
				$supply_orders_qty += ($qty['quantity_expected'] - $qty['quantity_received']);

				$qty = $this->getProductPhysicalQuantities($id_product, $id_product_attribute, $ids_warehouse, $usable);

				return ($qty - $client_orders_qty + $supply_orders_qty);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function getStockByCarrier($id_product = 0, $id_product_attribute = 0, $delivery_option = null)
	{
		if (!(int)$id_product || !is_array($delivery_option) || !is_int($id_product_attribute))
			return false;

		$results = Warehouse::getWarehousesByProductId($id_product, $id_product_attribute);
		$stock_quantity = 0;

		foreach ($results as $result)
			if (isset($result['id_warehouse']) && (int)$result['id_warehouse'])
			{
				$ws = new Warehouse((int)$result['id_warehouse']);
				$carriers = $ws->getWsCarriers();

				if (is_array($carriers) && !empty($carriers))
					$stock_quantity += Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT SUM(s.`usable_quantity` + s.`usable_quantity_remainder`) as quantity
						FROM '._DB_PREFIX_.'stock s
						LEFT JOIN '._DB_PREFIX_.'warehouse_carrier wc ON wc.`id_warehouse` = s.`id_warehouse`
						LEFT JOIN '._DB_PREFIX_.'carrier c ON wc.`id_carrier` = c.`id_reference`
						WHERE s.`id_product` = '.(int)$id_product.' AND s.`id_product_attribute` = '.(int)$id_product_attribute.' AND s.`id_warehouse` = '.$result['id_warehouse'].' AND c.`id_carrier` IN ('.rtrim($delivery_option[(int)Context::getContext()->cart->id_address_delivery], ',').') GROUP BY s.`id_product`');
				else
					$stock_quantity += Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('SELECT SUM(s.`usable_quantity` + s.`usable_quantity_remainder`) as quantity
						FROM '._DB_PREFIX_.'stock s
						WHERE s.`id_product` = '.(int)$id_product.' AND s.`id_product_attribute` = '.(int)$id_product_attribute.' AND s.`id_warehouse` = '.$result['id_warehouse'].' GROUP BY s.`id_product`');
			}

		return $stock_quantity;
	}
}

