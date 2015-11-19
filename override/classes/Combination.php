<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class Combination extends CombinationCore
{
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public $minimal_quantity_fractional = 0;

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function ppInit()
	{
		self::$definition['fields'] = array_merge(self::$definition['fields'], array(
			'minimal_quantity' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'validateProductQuantity'), 			'minimal_quantity_fractional' => array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isUnsignedFloat')
		));
	}
}


Combination::ppInit();
