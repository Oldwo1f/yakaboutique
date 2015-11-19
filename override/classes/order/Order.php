<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class Order extends OrderCore
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

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	protected function setProductCustomizedDatas(&$product, $customized_datas)
	{
		$product['customizedDatas'] = null;
		$product['customizationQuantityTotal'] = 0;
		
		$id_product = (isset($product['id_product']) ? 'id_product' : 'product_id');
		$id_product_attribute = (isset($product['id_product_attribute']) ? 'id_product_attribute' : 'product_attribute_id');
		if (isset($customized_datas[(int)$product[$id_product]][(int)$product[$id_product_attribute]]))
			foreach ($customized_datas[(int)$product[$id_product]][(int)$product[$id_product_attribute]] as $id_address_delivery => $customization_per_address)
				foreach ($customization_per_address as $id_customization => $customization)
					if ($customization['id_cart_product'] == $product['id_cart_product'])
					{
						$product['customizedDatas'][$id_address_delivery][$id_customization] = $customized_datas[(int)$product[$id_product]][(int)$product[$id_product_attribute]][$id_address_delivery][$id_customization];
						break;
					}
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getTotalWeight()
	{
		$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
		SELECT SUM(product_weight * '.PP::sqlQty('product_quantity').')
		FROM '._DB_PREFIX_.'order_detail
		WHERE id_order = '.(int)$this->id);

		return (float)$result;
	}
}
