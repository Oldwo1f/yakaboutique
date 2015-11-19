<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class OrderDetail extends OrderDetailCore
{
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public $id_cart_product;
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public $product_quantity_fractional;
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	private $ppropertiessmartprice_hook1;

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function ppInit()
	{
		self::$definition['fields'] = array_merge(self::$definition['fields'], array(
			'id_cart_product' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
			'product_quantity_fractional' => array('type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat'),
		));
		$ppropertiessmartprice_hook2 = null;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function updateTaxAmount($order, $tax_calculator = false)
	{
		$this->setContext((int)$this->id_shop);
		$address = new Address((int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
		$tax_manager = TaxManagerFactory::getManager($address, (int)Product::getIdTaxRulesGroupByIdProduct((int)$this->product_id, $this->context));
		$this->tax_calculator = $tax_manager->getTaxCalculator();

		return ($tax_calculator ? $this->tax_calculator : $this->saveTaxCalculator($order, true));
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	protected function checkProductStock($product, $id_order_state)
	{
		if ($id_order_state != Configuration::get('PS_OS_CANCELED') && $id_order_state != Configuration::get('PS_OS_ERROR'))
		{
			$update_quantity = true;
			$qty = PP::resolveQty($product['cart_quantity'], $product['cart_quantity_fractional']);
			if (!StockAvailable::dependsOnStock($product['id_product']))
				$update_quantity = StockAvailable::updateQuantity($product['id_product'], $product['id_product_attribute'], -$qty);

			if ($update_quantity)
				$product['stock_quantity'] -= $qty;

			if ($product['stock_quantity'] < 0 && Configuration::get('PS_STOCK_MANAGEMENT'))
				$this->outOfStock = true;
			Product::updateDefaultAttribute($product['id_product']);
		}
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	protected function setDetailProductPrice(Order $order, Cart $cart, $product)
	{
		$this->setContext((int)$product['id_shop']);
		$specific_price = $null = null;
		Product::getPriceStatic((int)$product['id_product'], true, (int)$product['id_product_attribute'], 6, null, false, true, array($product['cart_quantity'], $product['cart_quantity_fractional']), false, (int)$order->id_customer, (int)$order->id_cart, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $specific_price, true, true, $this->context);
		$this->specificPrice = $specific_price;
		$this->original_product_price = Product::getPriceStatic($product['id_product'], false, (int)$product['id_product_attribute'], 6, null, false, false, 1, false, null, null, null, $null, true, true, $this->context);
		$this->product_price = $this->original_product_price;
		$this->unit_price_tax_incl = (float)$product['price_wt'];
		$this->unit_price_tax_excl = (float)$product['price'];
		$this->total_price_tax_incl = (float)$product['total_wt'];
		$this->total_price_tax_excl = (float)$product['total'];

		$this->purchase_supplier_price = (float)$product['wholesale_price'];
		if ($product['id_supplier'] > 0 && ($supplier_price = ProductSupplier::getProductPrice((int)$product['id_supplier'], $product['id_product'], $product['id_product_attribute'], true)) > 0)
			$this->purchase_supplier_price = (float)$supplier_price;

		$this->setSpecificPrice($order, $product);

		$this->group_reduction = (float)Group::getReduction((int)$order->id_customer);

		$shop_id = $this->context->shop->id;

		$quantity_discount = SpecificPrice::getQuantityDiscount((int)$product['id_product'], $shop_id,
		(int)$cart->id_currency, (int)$this->vat_address->id_country,
		(int)$this->customer->id_default_group, (int)PP::resolveQty($product['cart_quantity'], $product['cart_quantity_fractional']), false, null, null, $null, true, true, $this->context);

		$unit_price = Product::getPriceStatic((int)$product['id_product'], true,
			($product['id_product_attribute'] ? (int)$product['id_product_attribute'] : null),
			2, null, false, true, 1, false, (int)$order->id_customer, null, (int)$order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $null, true, true, $this->context);
		$this->product_quantity_discount = 0.00;
		if ($quantity_discount)
		{
			$this->product_quantity_discount = $unit_price;
			if (Product::getTaxCalculationMethod((int)$order->id_customer) == PS_TAX_EXC)
				$this->product_quantity_discount = Tools::ps_round($unit_price, 2);

			if (isset($this->tax_calculator))
				$this->product_quantity_discount -= $this->tax_calculator->addTaxes($quantity_discount['price']);
		}

		$this->discount_quantity_applied = (($this->specificPrice && $this->specificPrice['from_quantity'] > PP::getSpecificPriceFromQty((int)$product['id_product'])) ? 1 : 0);

		$this->id_cart_product = (int)$product['id_cart_product'];
		$this->product_quantity_fractional = (float)$product['cart_quantity_fractional'];
		$ppropertiessmartprice_hook3 = null;
	}
}

OrderDetail::ppInit();
