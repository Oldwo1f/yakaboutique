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

	public function __construct()
	{
		parent::__construct();
		$this->fields_list['physical_quantity']['callback'] = 'callbackPhysicalQuantity';
	}

	public static function callbackPhysicalQuantity($echo, $tr)
	{
		return PP::adminControllerDisplayListContentQuantity($echo, $tr, 'physical_quantity', 'stock-mvt physical');
	}
}
