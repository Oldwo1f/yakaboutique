<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class Warehouse extends WarehouseCore
{
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function isEmpty()
	{
		$query = new DbQuery();
		$query->select('SUM(s.physical_quantity+s.physical_quantity_remainder)');
		$query->from('stock', 's');
		$query->where($this->def['primary'].' = '.(int)$this->id);
		return (Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query) == 0);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getQuantitiesOfProducts()
	{
		$query = '
			SELECT SUM(s.physical_quantity+s.physical_quantity_remainder)
			FROM '._DB_PREFIX_.'stock s
			WHERE s.id_warehouse = '.(int)$this->id;

		$res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);

		return ($res ? $res : 0);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getStockValue()
	{
		$query = new DbQuery();
		$query->select('SUM(s.`price_te` * (s.`physical_quantity`+s.`physical_quantity_remainder`)');
		$query->from('stock', 's');
		$query->where('s.`id_warehouse` = '.(int)$this->id);

		return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query);
	}
}

