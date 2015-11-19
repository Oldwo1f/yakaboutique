<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminStockMvtController extends AdminStockMvtControllerCore
{

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function __construct()
	{
		parent::__construct();
		$this->fields_list['physical_quantity']['callback'] = 'callbackPhysicalQuantity';
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function callbackPhysicalQuantity($echo, $tr)
	{
		return PP::adminControllerDisplayListContentQuantity($echo, $tr, 'physical_quantity', 'stock-mvt physical');
	}
}
