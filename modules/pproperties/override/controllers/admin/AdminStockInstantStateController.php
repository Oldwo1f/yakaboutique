<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminStockInstantStateController extends AdminStockInstantStateControllerCore
{

	public function __construct()
	{
		parent::__construct();
		$this->fields_list['physical_quantity']['callback'] = 'callbackPhysicalQuantity';
		$this->fields_list['usable_quantity']['callback'] = 'callbackUsableQuantity';
		$this->fields_list['real_quantity']['callback'] = 'callbackRealQuantity';
	}

	public static function callbackPhysicalQuantity($echo, $tr)
	{
		return PP::adminControllerDisplayListContentQuantity($echo, $tr, 'physical_quantity', 'stock-instant-state physical');
	}

	public static function callbackUsableQuantity($echo, $tr)
	{
		return PP::adminControllerDisplayListContentQuantity($echo, $tr, 'usable_quantity', 'stock-instant-state usable');
	}

	public static function callbackRealQuantity($echo, $tr)
	{
		return PP::adminControllerDisplayListContentQuantity($echo, $tr, 'real_quantity', 'stock-instant-state real');
	}
}
