<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class Stock extends StockCore
{
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public $physical_quantity_remainder = 0;
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public $usable_quantity_remainder = 0;

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function ppInit()
	{
		self::$definition['fields'] = array_merge(self::$definition['fields'], array(
			'physical_quantity_remainder' => array('type' => self::TYPE_FLOAT, 'validate'=> 'isUnsignedFloat'),
			'usable_quantity_remainder' => array('type' => self::TYPE_FLOAT, 'validate'=> 'isUnsignedFloat')
		));
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function hydrate(array $data, $id_lang = null)
	{
		parent::hydrate($data, $id_lang);
		if (!isset($data['physical_quantity_remainder']))
			PP::hydrateQty($this, 'physical_quantity', $data['physical_quantity'] + $this->physical_quantity_remainder);
		if (!isset($data['usable_quantity_remainder']))
			PP::hydrateQty($this, 'usable_quantity', $data['usable_quantity'] + $this->usable_quantity_remainder);
	}
}


Stock::ppInit();
