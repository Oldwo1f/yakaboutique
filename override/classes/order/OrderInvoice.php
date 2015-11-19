<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class OrderInvoice extends OrderInvoiceCore
{
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getProducts($products = false, $selected_products = false, $selected_qty = false)
	{
		$products = parent::getProducts($products, $selected_products, $selected_qty);
		foreach ($products as &$product)
			Product::amendProduct($product);
		return $products;
	}
}
