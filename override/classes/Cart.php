<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class Cart extends CartCore
{
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getLastProduct()
	{
		$sql = '
			SELECT `id_product`, `id_product_attribute`, id_shop, `id_cart_product`
			FROM `'._DB_PREFIX_.'cart_product`
			WHERE `id_cart` = '.(int)$this->id.'
			ORDER BY `date_add` DESC';
		$result = Db::getInstance()->getRow($sql);
		if ($result && isset($result['id_product']) && $result['id_product'] && isset($result['id_cart_product']))
			foreach ($this->getProducts() as $product)
			if ($result['id_product'] == $product['id_product']
				&& (
					!$result['id_product_attribute']
					|| $result['id_product_attribute'] == $product['id_product_attribute']
				))
				return $product;

		return false;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getProducts($refresh = false, $id_product = false, $id_country = null)
	{
		if (!$this->id)
			return array();
				if ($this->_products !== null && !$refresh)
		{
						if (is_int($id_product))
			{
				foreach ($this->_products as $product)
					if ($product['id_product'] == $id_product)
						return array($product);
				return array();
			}
			return $this->_products;
		}

				$sql = new DbQuery();

				$sql->select('cp.`id_product_attribute`, cp.`id_product`, cp.`quantity` AS cart_quantity, cp.id_shop, pl.`name`, p.`is_virtual`,
						cp.`id_cart_product`, cp.`quantity_fractional` AS cart_quantity_fractional, p.id_pp_template,
						pl.`description_short`, pl.`available_now`, pl.`available_later`, product_shop.`id_category_default`, p.`id_supplier`,
						p.`id_manufacturer`, product_shop.`on_sale`, product_shop.`ecotax`, product_shop.`additional_shipping_cost`,
						product_shop.`available_for_order`, product_shop.`price`, product_shop.`active`, product_shop.`unity`, product_shop.`unit_price_ratio`,
						stock.`quantity` + stock.`quantity_remainder` AS quantity_available, p.`unit_price_ratio`, p.`width`, p.`height`, p.`depth`, stock.`out_of_stock`, p.`weight`,
						p.`date_add`, p.`date_upd`, IFNULL(stock.quantity, 0) + IFNULL(stock.quantity_remainder, 0) as quantity, pl.`link_rewrite`, cl.`link_rewrite` AS category,
						cp.`id_cart_product` AS unique_id, cp.id_address_delivery,
						product_shop.advanced_stock_management, ps.product_supplier_reference supplier_reference, IFNULL(sp.`reduction_type`, 0) AS reduction_type');

				$sql->from('cart_product', 'cp');

				$sql->leftJoin('product', 'p', 'p.`id_product` = cp.`id_product`');
		$sql->innerJoin('product_shop', 'product_shop', '(product_shop.`id_shop` = cp.`id_shop` AND product_shop.`id_product` = p.`id_product`)');
		$sql->leftJoin('product_lang', 'pl', '
			p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = '.(int)$this->id_lang.Shop::addSqlRestrictionOnLang('pl', 'cp.id_shop')
		);

		$sql->leftJoin('category_lang', 'cl', '
			product_shop.`id_category_default` = cl.`id_category`
			AND cl.`id_lang` = '.(int)$this->id_lang.Shop::addSqlRestrictionOnLang('cl', 'cp.id_shop')
		);

		$sql->leftJoin('product_supplier', 'ps', 'ps.`id_product` = cp.`id_product` AND ps.`id_product_attribute` = cp.`id_product_attribute` AND ps.`id_supplier` = p.`id_supplier`');

		$sql->leftJoin('specific_price', 'sp', 'sp.`id_product` = cp.`id_product`'); 
				$sql->join(Product::sqlStock('cp', 'cp'));

				$sql->where('cp.`id_cart` = '.(int)$this->id);
		if ($id_product)
			$sql->where('cp.`id_product` = '.(int)$id_product);
		$sql->where('p.`id_product` IS NOT NULL');

				$sql->groupBy('unique_id');

				$sql->orderBy('cp.`date_add`, p.`id_product`, cp.`id_product_attribute` ASC');

		if (Customization::isFeatureActive())
		{
			$sql->select('cu.`id_customization`, cu.`quantity` AS customization_quantity, cu.`quantity_fractional` AS customization_quantity_fractional');
			$sql->leftJoin('customization', 'cu',
								'cp.`id_cart_product` = cu.`id_cart_product`');
		}
		else
			$sql->select('NULL AS customization_quantity, NULL AS customization_quantity_fractional, NULL AS id_customization');

		if (Combination::isFeatureActive())
		{
			$sql->select('
				product_attribute_shop.`price` AS price_attribute, product_attribute_shop.`ecotax` AS ecotax_attr,
				IF (IFNULL(pa.`reference`, \'\') = \'\', p.`reference`, pa.`reference`) AS reference,
				(p.`weight`+ pa.`weight`) weight_attribute,
				IF (IFNULL(pa.`ean13`, \'\') = \'\', p.`ean13`, pa.`ean13`) AS ean13,
				IF (IFNULL(pa.`upc`, \'\') = \'\', p.`upc`, pa.`upc`) AS upc,
				pai.`id_image` as pai_id_image, il.`legend` as pai_legend,
				IFNULL(product_attribute_shop.`minimal_quantity`, product_shop.`minimal_quantity`) as minimal_quantity,
				IFNULL(product_attribute_shop.`minimal_quantity_fractional`, product_shop.`minimal_quantity_fractional`) as minimal_quantity_fractional,
				IF(product_attribute_shop.wholesale_price > 0,  product_attribute_shop.wholesale_price, product_shop.`wholesale_price`) wholesale_price
			');

			$sql->leftJoin('product_attribute', 'pa', 'pa.`id_product_attribute` = cp.`id_product_attribute`');
			$sql->leftJoin('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.`id_shop` = cp.`id_shop` AND product_attribute_shop.`id_product_attribute` = pa.`id_product_attribute`)');
			$sql->leftJoin('product_attribute_image', 'pai', 'pai.`id_product_attribute` = pa.`id_product_attribute`');
			$sql->leftJoin('image_lang', 'il', 'il.`id_image` = pai.`id_image` AND il.`id_lang` = '.(int)$this->id_lang);
		}
		else
			$sql->select(
				'p.`reference` AS reference, p.`ean13`,
				p.`upc` AS upc, product_shop.`minimal_quantity` AS minimal_quantity, product_shop.`wholesale_price` wholesale_price'
			);
		$result = Db::getInstance()->executeS($sql);

				$products_ids = array();
		$pa_ids = array();
		if ($result)
			foreach ($result as $row)
			{
				$products_ids[] = $row['id_product'];
				$pa_ids[] = $row['id_product_attribute'];
			}
				Product::cacheProductsFeatures($products_ids);
		Cart::cacheSomeAttributesLists($pa_ids, $this->id_lang);

		$this->_products = array();
		if (empty($result))
			return array();

		$cart_shop_context = Context::getContext()->cloneContext();
		foreach ($result as &$row)
		{
			if (isset($row['ecotax_attr']) && $row['ecotax_attr'] > 0)
				$row['ecotax'] = (float)$row['ecotax_attr'];

			$row['stock_quantity'] = (float)$row['quantity'];
						$row['quantity'] = (int)$row['cart_quantity'];

			if (isset($row['id_product_attribute']) && (int)$row['id_product_attribute'] && isset($row['weight_attribute']))
				$row['weight'] = (float)$row['weight_attribute'];

			if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice')
				$address_id = (int)$this->id_address_invoice;
			else
				$address_id = (int)$row['id_address_delivery'];
			if (!Address::addressExists($address_id))
				$address_id = null;

			if ($cart_shop_context->shop->id != $row['id_shop'])
				$cart_shop_context->shop = new Shop((int)$row['id_shop']);

			$address = Address::initialize($address_id, true);
			$id_tax_rules_group = Product::getIdTaxRulesGroupByIdProduct((int)$row['id_product'], $cart_shop_context);
			$tax_calculator = TaxManagerFactory::getManager($address, $id_tax_rules_group)->getTaxCalculator();

			$specific_price_output = null;
			$row['price'] = Product::getPriceStatic(
				(int)$row['id_product'],
				false,
				isset($row['id_product_attribute']) ? (int)$row['id_product_attribute'] : null,
				6,
				null,
				false,
				true,
				array($row['cart_quantity'], $row['cart_quantity_fractional']),
				false,
				(int)$this->id_customer ? (int)$this->id_customer : null,
				(int)$this->id,
				$address_id,
				$specific_price_output,
				false,
				true,
				$cart_shop_context
			);

			switch (Configuration::get('PS_ROUND_TYPE'))
			{
				case Order::ROUND_TOTAL:
					$row['total'] = PP::calcPrice($row['price'], $row['cart_quantity'], $row['cart_quantity_fractional'], (int)$row['id_product'], false);
					$row['total_wt'] = PP::calcPrice($tax_calculator->addTaxes($row['price']), $row['cart_quantity'], $row['cart_quantity_fractional'], (int)$row['id_product'], false);
					$ppropertiessmartprice_hook1 = null;
					break;
				case Order::ROUND_LINE:
					$row['total'] = PP::calcPrice($row['price'], $row['cart_quantity'], $row['cart_quantity_fractional'], (int)$row['id_product'], false);
					$row['total_wt'] = PP::calcPrice($tax_calculator->addTaxes($row['price']), $row['cart_quantity'], $row['cart_quantity_fractional'], (int)$row['id_product'], false);
					$ppropertiessmartprice_hook1 = null;
					$row['total'] = Tools::ps_round($row['total'], _PS_PRICE_COMPUTE_PRECISION_);
					$row['total_wt'] = Tools::ps_round($row['total_wt'], _PS_PRICE_COMPUTE_PRECISION_);
					break;

				case Order::ROUND_ITEM:
				default:
					$row['total'] = PP::calcPrice($row['price'], $row['cart_quantity'], $row['cart_quantity_fractional'], (int)$row['id_product'], Order::ROUND_ITEM);
					$row['total_wt'] = PP::calcPrice($tax_calculator->addTaxes($row['price']), $row['cart_quantity'], $row['cart_quantity_fractional'], (int)$row['id_product'], Order::ROUND_ITEM);
					$ppropertiessmartprice_hook2 = null;
					break;
			}

			$row['price_wt'] = $tax_calculator->addTaxes($row['price']);
			$row['description_short'] = Tools::nl2br($row['description_short']);

			if (!isset($row['pai_id_image']) || $row['pai_id_image'] == 0)
			{
				$cache_id = 'Cart::getProducts_'.'-pai_id_image-'.(int)$row['id_product'].'-'.(int)$this->id_lang.'-'.(int)$row['id_shop'];
				if (!Cache::isStored($cache_id))
				{
					$row2 = Db::getInstance()->getRow('
						SELECT image_shop.`id_image` id_image, il.`legend`
						FROM `'._DB_PREFIX_.'image` i
						JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (i.id_image = image_shop.id_image AND image_shop.cover=1 AND image_shop.id_shop='.(int)$row['id_shop'].')
						LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)$this->id_lang.')
						WHERE i.`id_product` = '.(int)$row['id_product'].' AND image_shop.`cover` = 1'
					);
					Cache::store($cache_id, $row2);
				}
				$row2 = Cache::retrieve($cache_id);
				if (!$row2)
					$row2 = array('id_image' => false, 'legend' => false);
				else
					$row = array_merge($row, $row2);
			}
			else
			{
				$row['id_image'] = $row['pai_id_image'];
				$row['legend'] = $row['pai_legend'];
			}

			$row['reduction_applies'] = ($specific_price_output && (float)$specific_price_output['reduction']);
			$row['quantity_discount_applies'] = ($specific_price_output && PP::resolveQty($row['cart_quantity'], $row['cart_quantity_fractional']) >= (float)$specific_price_output['from_quantity']);
			$row['id_image'] = Product::defineProductImage($row, $this->id_lang);
			$row['allow_oosp'] = Product::isAvailableWhenOutOfStock($row['out_of_stock']);
			$row['features'] = Product::getFeaturesStatic((int)$row['id_product']);

			if (array_key_exists($row['id_product_attribute'].'-'.$this->id_lang, self::$_attributesLists))
				$row = array_merge($row, self::$_attributesLists[$row['id_product_attribute'].'-'.$this->id_lang]);

			$row = Product::getTaxesInformations($row, $cart_shop_context);

			Product::amendProduct($row);

			$this->_products[] = $row;
		}

		return $this->_products;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function containsProduct($id_product, $id_product_attribute = 0, $id_customization = 0, $id_address_delivery = 0, $quantity = 0, $id_cart_product = 0)
	{
		$sql = 'SELECT cp.`quantity`, cp.`quantity_fractional`, cp.`id_cart_product` FROM `'._DB_PREFIX_.'cart_product` cp';

		if ((int)$id_cart_product > 0)
		{
			if ($id_customization)
				$sql .= '
				LEFT JOIN `'._DB_PREFIX_.'customization` c ON (
						c.`id_cart_product` = cp.`id_cart_product`
					)';
			$sql .= ' WHERE cp.`id_cart_product` = '.(int)$id_cart_product;
			if ($id_customization)
				$sql .= ' AND c.`id_customization` = '.(int)$id_customization;
		}
		else
		{
			if ($id_customization)
				$sql .= '
					LEFT JOIN `'._DB_PREFIX_.'customization` c ON (
					c.`id_product` = cp.`id_product`
					AND c.`id_product_attribute` = cp.`id_product_attribute`
				)';
			else
				$sql .= '
					LEFT JOIN `'._DB_PREFIX_.'customization` c ON (
						c.`id_cart_product` = cp.`id_cart_product`
					)';

			$sql .= '
				WHERE cp.`id_product` = '.(int)$id_product.'
				AND cp.`id_product_attribute` = '.(int)$id_product_attribute.'
				AND cp.`id_cart` = '.(int)$this->id;
			if (Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->isMultiAddressDelivery())
				$sql .= ' AND cp.`id_address_delivery` = '.(int)$id_address_delivery;

			if ($id_customization)
				$sql .= ' AND c.`id_customization` = '.(int)$id_customization;
			else
				$sql .= '
					AND (cp.`id_cart_product` <> c.`id_cart_product` OR c.`id_cart_product` is null)
					AND ((SELECT count(cu.`id_customization`) FROM `'._DB_PREFIX_.'customization` cu
					WHERE cu.`id_cart` = '.(int)$this->id.'
					AND cu.`id_product` = '.(int)$id_product.'
					AND cu.`in_cart` = 0 AND cu.`id_cart_product` = 0) = 0)';

			if ($quantity > 0)
				$sql .= ' AND cp.`quantity_fractional` = '.number_format((float)$quantity, 6, '.', '');
		}

		return Db::getInstance()->getRow($sql);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function updateQty($quantity, $id_product, $id_product_attribute = null, $id_customization = false,
		$operator = 'up', $id_address_delivery = 0, Shop $shop = null, $auto_add_cart_rule = true, $id_cart_product = 0, $ext_prop_quantities = null, $ext_qty = 0)
	{
		if (!$shop)
			$shop = Context::getContext()->shop;

		if (Context::getContext()->customer->id)
		{
			if ($id_address_delivery == 0 && (int)$this->id_address_delivery) 				$id_address_delivery = $this->id_address_delivery;
			elseif ($id_address_delivery == 0) 				$id_address_delivery = (int)Address::getFirstCustomerAddressId((int)Context::getContext()->customer->id);
			elseif (!Customer::customerHasAddress(Context::getContext()->customer->id, $id_address_delivery)) 				$id_address_delivery = 0;
		}

				$id_product = (int)$id_product;
		$id_product_attribute = (int)$id_product_attribute;
		$product = new Product($id_product, false, Configuration::get('PS_LANG_DEFAULT'), $shop->id);

		if ($id_product_attribute)
		{
			$combination = new Combination((int)$id_product_attribute);
			if ($combination->id_product != $id_product)
				return false;
		}

		$properties = $product->productProperties();
		$quantity = $product->normalizeQty($quantity);

		
		if (!empty($id_product_attribute))
			$minimal_quantity = $product->attributeMinQty($id_product_attribute);
		else
			$minimal_quantity = $product->minQty();

		if (!Validate::isLoadedObject($product))
			die(Tools::displayError());

		if (isset(self::$_nbProducts[$this->id]))
			unset(self::$_nbProducts[$this->id]);

		if (isset(self::$_totalWeight[$this->id]))
			unset(self::$_totalWeight[$this->id]);

		Hook::exec('actionBeforeCartUpdateQty', array(
			'cart' => $this,
			'product' => $product,
			'id_product_attribute' => $id_product_attribute,
			'id_customization' => $id_customization,
			'quantity' => $quantity,
			'operator' => $operator,
			'id_address_delivery' => $id_address_delivery,
			'shop' => $shop,
			'auto_add_cart_rule' => $auto_add_cart_rule,
		));

		if ($quantity <= 0)
			return $this->deleteProduct($id_product, $id_product_attribute, (int)$id_customization, 0, $id_cart_product);
		elseif (!$product->available_for_order || (Configuration::get('PS_CATALOG_MODE') && !defined('_PS_ADMIN_DIR_')))
			return false;
		else
		{
			if ($id_cart_product == 0 && !PP::qtyPolicyLegacy($properties['pp_qty_policy']))
				$result = false; 			else
				
				$result = $this->containsProduct($id_product, $id_product_attribute, (int)$id_customization, (int)$id_address_delivery, (PP::productQtyPolicyLegacy($product) ? 0 : $quantity), $id_cart_product);

			
			if ($result)
			{
				if ($operator == 'up' || $operator == 'update')
				{
					$sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, IFNULL(stock.quantity_remainder, 0) as quantity_remainder
							FROM '._DB_PREFIX_.'product p
							'.Product::sqlStock('p', $id_product_attribute, true, $shop).'
							WHERE p.id_product = '.$id_product;

					$result2 = Db::getInstance()->getRow($sql);
					$product_qty = (int)$result2['quantity'] + (float)$result2['quantity_remainder'];
										if (Pack::isPack($id_product))
						$product_qty = Pack::getQuantity($id_product, $id_product_attribute);
					if ($operator == 'up')
					{
						$q = ($id_cart_product > 0 || PP::qtyPolicyLegacy($properties['pp_qty_policy']) ? (int)$quantity : 1);
						$new_qty = PP::resolveQty((int)$result['quantity'] + $q, $result['quantity_fractional']);
						$new_min_qty = ($properties['pp_ext'] == 1 ? (int)$result['quantity'] + $q : $new_qty);
						$qty = '+ '.(int)$q;
					}
					else
						$new_qty = $new_min_qty = $qty = $quantity;

					if (!Product::isAvailableWhenOutOfStock((int)$result2['out_of_stock']))
						if ($new_qty > $product_qty)
							return false;
				}
				else if ($operator == 'down')
				{
					$q = ($id_cart_product > 0 ? (int)$quantity : 1);
					$new_qty = PP::resolveQty((int)$result['quantity'] - $q, $result['quantity_fractional']);
					$new_min_qty = ($properties['pp_ext'] == 1 ? (int)$result['quantity'] - $q : $new_qty);
					$qty = '- '.(int)$q;
					if ($new_min_qty < $minimal_quantity && (PP::qtyPolicyLegacy($properties['pp_qty_policy'] || $properties['pp_ext'] == 1) ? $minimal_quantity > 1 : $new_qty > 0))
						return -1;
				}
				else
					return false;

				
				if (($properties['pp_ext'] == 1 ? $new_min_qty : $new_qty) <= 0)
					return $this->deleteProduct((int)$id_product, (int)$id_product_attribute, (int)$id_customization, $id_address_delivery, $id_cart_product);
				else if ($new_min_qty < $minimal_quantity)
					return -1;
				else
					if ($operator == 'up' || $operator == 'down')
						Db::getInstance()->execute('
								UPDATE `'._DB_PREFIX_.'cart_product`
								SET `quantity` = `quantity` '.$qty.', `date_add` = NOW()
								WHERE `id_cart_product` = '.(int)$result['id_cart_product'].'
								LIMIT 1'
							);
					else
						Db::getInstance()->execute('
							UPDATE `'._DB_PREFIX_.'cart_product`
							SET `quantity_fractional` = '.$qty.', `date_add` = NOW()
							WHERE `id_cart_product` = '.(int)$result['id_cart_product'].'
							LIMIT 1'
						);
				$id_cart_product = (int)$result['id_cart_product'];
			}
			
			elseif ($operator == 'up')
			{
				$sql = 'SELECT stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, IFNULL(stock.quantity_remainder, 0) as quantity_remainder
						FROM '._DB_PREFIX_.'product p
						'.Product::sqlStock('p', $id_product_attribute, true, $shop).'
						WHERE p.id_product = '.$id_product;

				$result2 = Db::getInstance()->getRow($sql);

								if (Pack::isPack($id_product))
					$result2['quantity'] = Pack::getQuantity($id_product, $id_product_attribute);

				$total_quantity = (PP::qtyPolicyLegacy($properties['pp_qty_policy']) ? $quantity : ($ext_prop_quantities !== null && $ext_qty > 0 ? $quantity * $ext_qty : $quantity));

				if (!Product::isAvailableWhenOutOfStock((int)$result2['out_of_stock']))
					if ($total_quantity > $result2['quantity'] + (PP::qtyPolicyFractional($properties['pp_qty_policy']) ? (float)$result2['quantity_remainder'] : 0))
					return false;

				if (PP::qtyPolicyFractional($properties['pp_qty_policy']) && $ext_prop_quantities !== null && $ext_qty > 0)
					if ($ext_qty < $minimal_quantity)
						return -1;
				else
					if ($total_quantity < $minimal_quantity)
						return -1;

				$result_add = Db::getInstance()->insert('cart_product', array(
												'id_product' => 			(int)$id_product,
												'id_product_attribute' => 	(int)$id_product_attribute,
												'id_cart' => 				(int)$this->id,
												'id_address_delivery' => 	(int)$id_address_delivery,
												'id_shop' => 				$shop->id,
												'quantity' => 				(PP::qtyPolicyLegacy($properties['pp_qty_policy']) ? $quantity : ($ext_prop_quantities !== null && $ext_qty > 0 ? $ext_qty : 1)),
												'quantity_fractional' =>	(PP::qtyPolicyLegacy($properties['pp_qty_policy']) ? 0 : $quantity),
												'date_add' => 				date('Y-m-d H:i:s')
				));

				if (!$result_add)
					return false;
				$id_cart_product = Db::getInstance()->Insert_ID();

				if (count($ext_prop_quantities))
				{
					$db = Db::getInstance();
					foreach ($ext_prop_quantities as $index => $value)
					{
						$db->insert('pp_product_ext', array(
							'id_cart_product' => (int)$id_cart_product,
							'position'		  => (int)$index,
							'quantity'		  => (float)$value
						));
					}
				}
			}
			$this->last_icp = $id_cart_product;
		}
				$this->_products = $this->getProducts(true);
		$this->update();
		$context = Context::getContext()->cloneContext();
		$context->cart = $this;
		Cache::clean('getContextualValue_*');
		if ($auto_add_cart_rule)
			CartRule::autoAddToCart($context);

		if ($product->customizable)
			return $this->_updateCustomizationQuantity($quantity, (int)$id_customization, (int)$id_product, (int)$id_product_attribute, (int)$id_address_delivery, $operator, $id_cart_product);
		else
			return true;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	protected function _updateCustomizationQuantity($quantity, $id_customization, $id_product, $id_product_attribute, $id_address_delivery, $operator = 'up', $id_cart_product = 0)
	{
				if (empty($id_customization))
		{
			$customization = $this->getProductCustomization($id_product, null, true);
			foreach ($customization as $field)
			{
				if ($field['quantity'] == 0)
				{
					$qty_policy = PP::productQtyPolicy($id_product);
					$q = PP::normalizeQty($quantity, $qty_policy);
					Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'customization`
					SET `quantity` = '.(PP::qtyPolicyLegacy($qty_policy) ? $q : 1).',
						`quantity_fractional` = '.(PP::qtyPolicyLegacy($qty_policy) ? 0 : $q).',
						`id_cart_product` = '.(int)$id_cart_product.',
						`id_product_attribute` = '.(int)$id_product_attribute.',
						`id_address_delivery` = '.(int)$id_address_delivery.',
						`in_cart` = 1
					WHERE `id_customization` = '.(int)$field['id_customization']);
				}
			}
		}

		
		if (!empty($id_customization) && (int)$quantity < 1)
			return $this->_deleteCustomization((int)$id_customization, (int)$id_product, (int)$id_product_attribute);

		
		if (!empty($id_customization))
		{
			$result = Db::getInstance()->getRow('SELECT `quantity` FROM `'._DB_PREFIX_.'customization` WHERE `id_customization` = '.(int)$id_customization);
			if ($result && Db::getInstance()->NumRows())
			{
				if ($operator == 'down' && (int)$result['quantity'] - (int)$quantity < 1)
					return Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'customization` WHERE `id_customization` = '.(int)$id_customization);

				return Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'customization`
					SET
						`quantity` = `quantity` '.($operator == 'up' ? '+ ' : '- ').(int)$quantity.',
						`id_address_delivery` = '.(int)$id_address_delivery.'
					WHERE `id_customization` = '.(int)$id_customization);
			}
			else
				Db::getInstance()->execute('
					UPDATE `'._DB_PREFIX_.'customization`
					SET `id_address_delivery` = '.(int)$id_address_delivery.'
					WHERE `id_customization` = '.(int)$id_customization);
		}
				$this->_products = $this->getProducts(true);
		$this->update();
		return true;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function deleteProduct($id_product, $id_product_attribute = null, $id_customization = null, $id_address_delivery = 0, $id_cart_product = 0)
	{
		if (isset(self::$_nbProducts[$this->id]))
			unset(self::$_nbProducts[$this->id]);

		if (isset(self::$_totalWeight[$this->id]))
			unset(self::$_totalWeight[$this->id]);

		/*
		if ((int)$id_customization)
		{
			$product_total_quantity = (int)Db::getInstance()->getValue(
				'SELECT `quantity`
				FROM `'._DB_PREFIX_.'cart_product`
				WHERE `id_product` = '.(int)$id_product.'
				AND `id_cart` = '.(int)$this->id.'
				AND `id_product_attribute` = '.(int)$id_product_attribute);

			$customization_quantity = (int)Db::getInstance()->getValue('
			SELECT `quantity`
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int)$this->id.'
			AND `id_product` = '.(int)$id_product.'
			AND `id_product_attribute` = '.(int)$id_product_attribute.'
			'.((int)$id_address_delivery ? 'AND `id_address_delivery` = '.(int)$id_address_delivery : ''));

			if (!$this->_deleteCustomization((int)$id_customization, (int)$id_product, (int)$id_product_attribute, (int)$id_address_delivery))
				return false;

						$this->_products = $this->getProducts(true);
			return ($customization_quantity == $product_total_quantity && $this->deleteProduct((int)$id_product, (int)$id_product_attribute, null, (int)$id_address_delivery));
		}

				$result = Db::getInstance()->getRow('
			SELECT SUM(`quantity`) AS \'quantity\'
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int)$this->id.'
			AND `id_product` = '.(int)$id_product.'
			AND `id_product_attribute` = '.(int)$id_product_attribute);

		if ($result === false)
			return false;

				if (Db::getInstance()->NumRows() && (int)$result['quantity'])
			return Db::getInstance()->execute('
				UPDATE `'._DB_PREFIX_.'cart_product`
				SET `quantity` = '.(int)$result['quantity'].'
				WHERE `id_cart` = '.(int)$this->id.'
				AND `id_product` = '.(int)$id_product.
				($id_product_attribute != null ? ' AND `id_product_attribute` = '.(int)$id_product_attribute : '')
			);
		*/

		if (($id_cart_product = PP::resolveIcp($id_cart_product)) <= 0)
			return false;
		$sql_icp = PP::sqlIcp($id_cart_product);

		$id_customization = (int)Db::getInstance()->getValue('
			SELECT `id_customization`
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart_product` = '.(int)$id_cart_product);

		if ((int)$id_customization && !$this->_deleteCustomization((int)$id_customization, (int)$id_product, (int)$id_product_attribute, (int)$id_address_delivery))
			return false;

		
		$result = Db::getInstance()->execute('
		DELETE FROM `'._DB_PREFIX_.'cart_product`
		WHERE `id_product` = '.(int)$id_product.'
		'.(!is_null($id_product_attribute) ? ' AND `id_product_attribute` = '.(int)$id_product_attribute : '').'
		AND `id_cart` = '.(int)$this->id.'
		'.((int)$id_address_delivery ? 'AND `id_address_delivery` = '.(int)$id_address_delivery : '').$sql_icp);

		if (PP::multidimensionalEnabled())
			Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'pp_product_ext`
			WHERE `id_cart_product` = '.(int)$id_cart_product);

		if ($result)
		{
			$return = $this->update();
						$this->_products = $this->getProducts(true);
			CartRule::autoRemoveFromCart();
			CartRule::autoAddToCart();

			return $return;
		}

		return false;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	protected function _deleteCustomization($id_customization, $id_product, $id_product_attribute, $id_address_delivery = 0)
	{
		$result = true;
		$customization = Db::getInstance()->getRow('SELECT *
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_customization` = '.(int)$id_customization);

		if ($customization)
		{
			$cust_data = Db::getInstance()->getRow('SELECT *
				FROM `'._DB_PREFIX_.'customized_data`
				WHERE `id_customization` = '.(int)$id_customization);

						if (isset($cust_data['type']) && $cust_data['type'] == 0)
				$result &= (@unlink(_PS_UPLOAD_DIR_.$cust_data['value']) && @unlink(_PS_UPLOAD_DIR_.$cust_data['value'].'_small'));

			$result &= Db::getInstance()->execute(
				'DELETE FROM `'._DB_PREFIX_.'customized_data`
				WHERE `id_customization` = '.(int)$id_customization
			);

			/*
			if ($result)
				$result &= Db::getInstance()->execute(
					'UPDATE `'._DB_PREFIX_.'cart_product`
					SET `quantity` = `quantity` - '.(int)$customization['quantity'].'
					WHERE `id_cart` = '.(int)$this->id.'
					AND `id_product` = '.(int)$id_product.
					((int)$id_product_attribute ? ' AND `id_product_attribute` = '.(int)$id_product_attribute : '').'
					AND `id_address_delivery` = '.(int)$id_address_delivery
				);
			*/

			if (!$result)
				return false;

			return Db::getInstance()->execute(
				'DELETE FROM `'._DB_PREFIX_.'customization`
				WHERE `id_customization` = '.(int)$id_customization
			);
		}

		return true;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getOrderTotal($with_taxes = true, $type = Cart::BOTH, $products = null, $id_carrier = null, $use_cache = true)
	{
		static $address = null;

		if (!$this->id)
			return 0;

		$type = (int)$type;
		$array_type = array(
			Cart::ONLY_PRODUCTS,
			Cart::ONLY_DISCOUNTS,
			Cart::BOTH,
			Cart::BOTH_WITHOUT_SHIPPING,
			Cart::ONLY_SHIPPING,
			Cart::ONLY_WRAPPING,
			Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING,
			Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING,
		);

				$virtual_context = Context::getContext()->cloneContext();
		$virtual_context->cart = $this;

		if (!in_array($type, $array_type))
			die(Tools::displayError());

		$with_shipping = in_array($type, array(Cart::BOTH, Cart::ONLY_SHIPPING));

				if ($type == Cart::ONLY_DISCOUNTS && !CartRule::isFeatureActive())
			return 0;

				$virtual = $this->isVirtualCart();
		if ($virtual && $type == Cart::ONLY_SHIPPING)
			return 0;

		if ($virtual && $type == Cart::BOTH)
			$type = Cart::BOTH_WITHOUT_SHIPPING;

		if ($with_shipping || $type == Cart::ONLY_DISCOUNTS)
		{
			if (is_null($products) && is_null($id_carrier))
				$shipping_fees = $this->getTotalShippingCost(null, (boolean)$with_taxes);
			else
				$shipping_fees = $this->getPackageShippingCost($id_carrier, (bool)$with_taxes, null, $products);
		}
		else
			$shipping_fees = 0;

		if ($type == Cart::ONLY_SHIPPING)
			return $shipping_fees;

		if ($type == Cart::ONLY_PRODUCTS_WITHOUT_SHIPPING)
			$type = Cart::ONLY_PRODUCTS;

		$param_product = true;
		if (is_null($products))
		{
			$param_product = false;
			$products = $this->getProducts();
		}

		if ($type == Cart::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING)
		{
			foreach ($products as $key => $product)
				if ($product['is_virtual'])
					unset($products[$key]);
			$type = Cart::ONLY_PRODUCTS;
		}

		$order_total = 0;
		if (Tax::excludeTaxeOption())
			$with_taxes = false;

		$products_total = array();
		$ecotax_total = 0;

		foreach ($products as $product) 		{
			if ($virtual_context->shop->id != $product['id_shop'])
				$virtual_context->shop = new Shop((int)$product['id_shop']);

			if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice')
				$id_address = (int)$this->id_address_invoice;
			else
				$id_address = (int)$product['id_address_delivery']; 			if (!Address::addressExists($id_address))
				$id_address = null;

			$null = null;
			$price = Product::getPriceStatic(
				(int)$product['id_product'],
				false,
				(int)$product['id_product_attribute'],
				6,
				null,
				false,
				true,
				array($product['cart_quantity'], $product['cart_quantity_fractional']),
				false,
				(int)$this->id_customer ? (int)$this->id_customer : null,
				(int)$this->id,
				$id_address,
				$null,
				false,
				true,
				$virtual_context
			);

			if (Configuration::get('PS_USE_ECOTAX'))
			{
				$ecotax = $product['ecotax'];
				if (isset($product['attribute_ecotax']) && $product['attribute_ecotax'] > 0)
					$ecotax = $product['attribute_ecotax'];
			}
			else
				$ecotax = 0;

			$address = Address::initialize($id_address, true);

			if ($with_taxes)
			{
				$id_tax_rules_group = Product::getIdTaxRulesGroupByIdProduct((int)$product['id_product'], $virtual_context);
				$tax_calculator = TaxManagerFactory::getManager($address, $id_tax_rules_group)->getTaxCalculator();

				if ($ecotax)
					$ecotax_tax_calculator = TaxManagerFactory::getManager($address, (int)Configuration::get('PS_ECOTAX_TAX_RULES_GROUP_ID'))->getTaxCalculator();
			}
			else
				$id_tax_rules_group = 0;

			if (in_array(Configuration::get('PS_ROUND_TYPE'), array(Order::ROUND_ITEM, Order::ROUND_LINE)))
			{
				if (!isset($products_total[$id_tax_rules_group]))
					$products_total[$id_tax_rules_group] = 0;
			}
			else
				if (!isset($products_total[$id_tax_rules_group.'_'.$id_address]))
					$products_total[$id_tax_rules_group.'_'.$id_address] = 0;

			switch (Configuration::get('PS_ROUND_TYPE'))
			{
				case Order::ROUND_TOTAL:
					$products_total[$id_tax_rules_group.'_'.$id_address] += PP::calcPrice($price, $product['cart_quantity'], $product['cart_quantity_fractional'], (int)$product['id_product'], false);
					$ppropertiessmartprice_hook3 = null;
					if ($ecotax)
						$ecotax_total += PP::calcPrice($ecotax, $product['cart_quantity'], $product['cart_quantity_fractional'], null, false);
					break;
				case Order::ROUND_LINE:
					$product_price = PP::calcPrice($price, $product['cart_quantity'], $product['cart_quantity_fractional'], (int)$product['id_product'], false);
					$ppropertiessmartprice_hook4 = null;

					if ($with_taxes)
						$products_total[$id_tax_rules_group] += Tools::ps_round($product_price + $tax_calculator->getTaxesTotalAmount($product_price), _PS_PRICE_COMPUTE_PRECISION_);
					else
						$products_total[$id_tax_rules_group] += Tools::ps_round($product_price, _PS_PRICE_COMPUTE_PRECISION_);

					if ($ecotax)
					{
						$ecotax_price = PP::calcPrice($ecotax, $product['cart_quantity'], $product['cart_quantity_fractional'], null, false);

						if ($with_taxes)
							$ecotax_total += Tools::ps_round($ecotax_price + $ecotax_tax_calculator->getTaxesTotalAmount($ecotax_price), _PS_PRICE_COMPUTE_PRECISION_);
						else
							$ecotax_total += Tools::ps_round($ecotax_price, _PS_PRICE_COMPUTE_PRECISION_);
					}
					break;
				case Order::ROUND_ITEM:
				default:
					$product_price = $with_taxes ? $tax_calculator->addTaxes($price) : $price;
					$products_total[$id_tax_rules_group] += PP::calcPrice($product_price, $product['cart_quantity'], $product['cart_quantity_fractional'], (int)$product['id_product'], Order::ROUND_ITEM);
					$ppropertiessmartprice_hook5 = null;
					if ($ecotax)
					{
						$ecotax_price = $with_taxes ? $ecotax_tax_calculator->addTaxes($ecotax) : $ecotax;
						$ecotax_total += PP::calcPrice($ecotax_price, $product['cart_quantity'], $product['cart_quantity_fractional'], null, Order::ROUND_ITEM);
					}
					break;
			}
		}

		foreach ($products_total as $key => $price)
		{
			if ($with_taxes && Configuration::get('PS_ROUND_TYPE') == Order::ROUND_TOTAL)
			{
				$tmp = explode('_', $key);
				$address = Address::initialize((int)$tmp[1], true);
				$tax_calculator = TaxManagerFactory::getManager($address, $tmp[0])->getTaxCalculator();
				$order_total += Tools::ps_round($price + $tax_calculator->getTaxesTotalAmount($price), _PS_PRICE_COMPUTE_PRECISION_);
			}
			else
				$order_total += $price;
		}

		if ($ecotax_total && $with_taxes && Configuration::get('PS_ROUND_TYPE') == Order::ROUND_TOTAL)
			$ecotax_total = Tools::ps_round($ecotax_total, _PS_PRICE_COMPUTE_PRECISION_) + Tools::ps_round($ecotax_tax_calculator->getTaxesTotalAmount($ecotax_total), _PS_PRICE_COMPUTE_PRECISION_);

		$order_total += $ecotax_total;
		$order_total_products = $order_total;

		if ($type == Cart::ONLY_DISCOUNTS)
			$order_total = 0;

				$wrapping_fees = 0;
		if ($this->gift)
			$wrapping_fees = Tools::convertPrice(Tools::ps_round($this->getGiftWrappingPrice($with_taxes), _PS_PRICE_COMPUTE_PRECISION_), Currency::getCurrencyInstance((int)$this->id_currency));
		if ($type == Cart::ONLY_WRAPPING)
			return $wrapping_fees;

		$order_total_discount = 0;
		$order_shipping_discount = 0;
		if (!in_array($type, array(Cart::ONLY_SHIPPING, Cart::ONLY_PRODUCTS)) && CartRule::isFeatureActive())
		{
						if ($with_shipping || $type == Cart::ONLY_DISCOUNTS)
				$cart_rules = $this->getCartRules(CartRule::FILTER_ACTION_ALL);
			else
			{
				$cart_rules = $this->getCartRules(CartRule::FILTER_ACTION_REDUCTION);
								foreach ($this->getCartRules(CartRule::FILTER_ACTION_GIFT) as $tmp_cart_rule)
				{
					$flag = false;
					foreach ($cart_rules as $cart_rule)
						if ($tmp_cart_rule['id_cart_rule'] == $cart_rule['id_cart_rule'])
							$flag = true;
					if (!$flag)
						$cart_rules[] = $tmp_cart_rule;
				}
			}

			$id_address_delivery = 0;
			if (isset($products[0]))
				$id_address_delivery = (is_null($products) ? $this->id_address_delivery : $products[0]['id_address_delivery']);
			$package = array('id_carrier' => $id_carrier, 'id_address' => $id_address_delivery, 'products' => $products);

						$flag = false;
			foreach ($cart_rules as $cart_rule)
			{
								if (($with_shipping || $type == Cart::ONLY_DISCOUNTS) && $cart_rule['obj']->free_shipping && !$flag)
				{
					$order_shipping_discount = (float)Tools::ps_round($cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_SHIPPING, ($param_product ? $package : null), $use_cache), _PS_PRICE_COMPUTE_PRECISION_);
					$flag = true;
				}

								if ((int)$cart_rule['obj']->gift_product)
				{
					$in_order = false;
					if (is_null($products))
						$in_order = true;
					else
						foreach ($products as $product)
							if ($cart_rule['obj']->gift_product == $product['id_product'] && $cart_rule['obj']->gift_product_attribute == $product['id_product_attribute'])
								$in_order = true;

					if ($in_order)
						$order_total_discount += $cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_GIFT, $package, $use_cache);
				}

								if ($cart_rule['obj']->reduction_percent > 0 || $cart_rule['obj']->reduction_amount > 0)
					$order_total_discount += Tools::ps_round($cart_rule['obj']->getContextualValue($with_taxes, $virtual_context, CartRule::FILTER_ACTION_REDUCTION, $package, $use_cache), _PS_PRICE_COMPUTE_PRECISION_);
			}
			$order_total_discount = min(Tools::ps_round($order_total_discount, 2), (float)$order_total_products) + (float)$order_shipping_discount;
			$order_total -= $order_total_discount;
		}

		if ($type == Cart::BOTH)
			$order_total += $shipping_fees + $wrapping_fees;

		if ($order_total < 0 && $type != Cart::ONLY_DISCOUNTS)
			return 0;

		if ($type == Cart::ONLY_DISCOUNTS)
			return $order_total_discount;

		return Tools::ps_round((float)$order_total, _PS_PRICE_COMPUTE_PRECISION_);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getTotalWeight($products = null)
	{
		if (!is_null($products))
		{
			$total_weight = 0;
			foreach ($products as $product)
			{
				if (!isset($product['weight_attribute']) || is_null($product['weight_attribute']))
					$total_weight += $product['weight'] * PP::resolveQty($product['cart_quantity'], $product['cart_quantity_fractional']);
				else
					$total_weight += $product['weight_attribute'] * PP::resolveQty($product['cart_quantity'], $product['cart_quantity_fractional']);
			}
			return $total_weight;
		}

		if (!isset(self::$_totalWeight[$this->id]))
		{
			if (Combination::isFeatureActive())
				$weight_product_with_attribute = Db::getInstance()->getValue('
				SELECT SUM((p.`weight` + pa.`weight`) * '.PP::sqlQty('quantity', 'cp').') as nb
				FROM `'._DB_PREFIX_.'cart_product` cp
				LEFT JOIN `'._DB_PREFIX_.'product` p ON (cp.`id_product` = p.`id_product`)
				LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa ON (cp.`id_product_attribute` = pa.`id_product_attribute`)
				WHERE (cp.`id_product_attribute` IS NOT NULL AND cp.`id_product_attribute` != 0)
				AND cp.`id_cart` = '.(int)$this->id);
			else
				$weight_product_with_attribute = 0;

			$weight_product_without_attribute = Db::getInstance()->getValue('
			SELECT SUM(p.`weight` * '.PP::sqlQty('quantity', 'cp').') as nb
			FROM `'._DB_PREFIX_.'cart_product` cp
			LEFT JOIN `'._DB_PREFIX_.'product` p ON (cp.`id_product` = p.`id_product`)
			WHERE (cp.`id_product_attribute` IS NULL OR cp.`id_product_attribute` = 0)
			AND cp.`id_cart` = '.(int)$this->id);

			self::$_totalWeight[$this->id] = round((float)$weight_product_with_attribute + (float)$weight_product_without_attribute, 3);
		}

		return self::$_totalWeight[$this->id];
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getProductCustomization($id_product, $type = null, $not_in_cart = false)
	{
		if (!Customization::isFeatureActive())
			return array();

		$result = Db::getInstance()->executeS('
			SELECT cu.id_customization, cd.index, cd.value, cd.type, cu.in_cart, cu.quantity, cu.quantity_fractional, cu.id_cart_product
			FROM `'._DB_PREFIX_.'customization` cu
			LEFT JOIN `'._DB_PREFIX_.'customized_data` cd ON (cu.`id_customization` = cd.`id_customization`)
			WHERE cu.id_cart = '.(int)$this->id.'
			AND cu.id_product = '.(int)$id_product.
			($type === Product::CUSTOMIZE_FILE ? ' AND type = '.(int)Product::CUSTOMIZE_FILE : '').
			($type === Product::CUSTOMIZE_TEXTFIELD ? ' AND type = '.(int)Product::CUSTOMIZE_TEXTFIELD : '').
			($not_in_cart ? ' AND in_cart = 0' : '')
		);
		return $result;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function duplicate()
	{
		if (!Validate::isLoadedObject($this))
			return false;

		$cart = new Cart($this->id);
		$cart->id = null;
		$cart->id_shop = $this->id_shop;
		$cart->id_shop_group = $this->id_shop_group;

		if (!Customer::customerHasAddress((int)$cart->id_customer, (int)$cart->id_address_delivery))
			$cart->id_address_delivery = (int)Address::getFirstCustomerAddressId((int)$cart->id_customer);

		if (!Customer::customerHasAddress((int)$cart->id_customer, (int)$cart->id_address_invoice))
			$cart->id_address_invoice = (int)Address::getFirstCustomerAddressId((int)$cart->id_customer);

		if ($cart->id_customer)
			$cart->secure_key = Cart::$_customer->secure_key;

		$cart->add();

		if (!Validate::isLoadedObject($cart))
			return false;

		$success = true;
		$products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT * FROM `'._DB_PREFIX_.'cart_product` WHERE `id_cart` = '.(int)$this->id);

		$product_gift = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT cr.`gift_product`, cr.`gift_product_attribute` FROM `'._DB_PREFIX_.'cart_rule` cr LEFT JOIN `'._DB_PREFIX_.'order_cart_rule` ocr ON (ocr.`id_order` = '.(int)$this->id.') WHERE ocr.`id_cart_rule` = cr.`id_cart_rule`');

		$id_address_delivery = Configuration::get('PS_ALLOW_MULTISHIPPING') ? $cart->id_address_delivery : 0;

		foreach ($products as $product)
		{
			if ($id_address_delivery)
				if (Customer::customerHasAddress((int)$cart->id_customer, $product['id_address_delivery']))
					$id_address_delivery = $product['id_address_delivery'];

			foreach ($product_gift as $gift)
				if (isset($gift['gift_product']) && isset($gift['gift_product_attribute']) && (int)$gift['gift_product'] == (int)$product['id_product'] && (int)$gift['gift_product_attribute'] == (int)$product['id_product_attribute'])
					$product['quantity'] = (int)$product['quantity'] - 1;

			$success &= $cart->updateQty(
				PP::resolveQty($product['quantity'], $product['quantity_fractional']),
				(int)$product['id_product'],
				(int)$product['id_product_attribute'],
				null,
				'up',
				(int)$id_address_delivery,
				new Shop((int)$cart->id_shop),
				false
			);
		}

				$customs = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT *
			FROM '._DB_PREFIX_.'customization c
			LEFT JOIN '._DB_PREFIX_.'customized_data cd ON cd.id_customization = c.id_customization
			WHERE c.id_cart = '.(int)$this->id
		);

				$customs_by_id = array();
		foreach ($customs as $custom)
		{
			if (!isset($customs_by_id[$custom['id_customization']]))
				$customs_by_id[$custom['id_customization']] = array(
					'id_product_attribute' => $custom['id_product_attribute'],
					'id_product' => $custom['id_product'],
					'quantity' => $custom['quantity'],
					'quantity_fractional' => $custom['quantity_fractional']
				);
		}

				$custom_ids = array();
		foreach ($customs_by_id as $customization_id => $val)
		{
						Db::getInstance()->execute('
				INSERT INTO `'._DB_PREFIX_.'customization` (id_cart, id_product_attribute, id_product, `id_address_delivery`, quantity, quantity_fractional, `quantity_refunded`, `quantity_returned`, `in_cart`)
				VALUES('.(int)$cart->id.', '.(int)$val['id_product_attribute'].', '.(int)$val['id_product'].', '.(int)$id_address_delivery.', '.(int)$val['quantity'].', '.(float)$val['quantity_fractional'].', 0, 0, 1)'
			);
			$custom_ids[$customization_id] = Db::getInstance(_PS_USE_SQL_SLAVE_)->Insert_ID();
		}

				if (count($customs))
		{
			$first = true;
			$sql_custom_data = 'INSERT INTO '._DB_PREFIX_.'customized_data (`id_customization`, `type`, `index`, `value`) VALUES ';
			foreach ($customs as $custom)
			{
				if (!$first)
					$sql_custom_data .= ',';
				else
					$first = false;

				$sql_custom_data .= '('.(int)$custom_ids[$custom['id_customization']].', '.(int)$custom['type'].', '.
					(int)$custom['index'].', \''.pSQL($custom['value']).'\')';
			}
			Db::getInstance()->execute($sql_custom_data);
		}

		return array('cart' => $cart, 'success' => $success);
	}
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function setNoMultishipping()
	{
		$emptyCache = false;
		if (Configuration::get('PS_ALLOW_MULTISHIPPING'))
		{
						$sql = 'SELECT sum(`quantity`) as quantity, id_product, id_product_attribute, count(*) as count
					FROM `'._DB_PREFIX_.'cart_product`
					WHERE `id_cart` = '.(int)$this->id.'
						AND `id_shop` = '.(int)$this->id_shop.'
					GROUP BY id_cart_product
					HAVING count > 1';

			foreach (Db::getInstance()->executeS($sql) as $product)
			{
				$sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
					SET `quantity` = '.$product['quantity'].'
					WHERE  `id_cart` = '.(int)$this->id.'
						AND `id_shop` = '.(int)$this->id_shop.'
						AND id_product = '.$product['id_product'].'
						AND id_product_attribute = '.$product['id_product_attribute'].'
						AND id_cart_product = '.$product['id_cart_product'];
				if (Db::getInstance()->execute($sql))
					$emptyCache = true;
			}

						$sql = 'DELETE cp1
				FROM `'._DB_PREFIX_.'cart_product` cp1
					INNER JOIN `'._DB_PREFIX_.'cart_product` cp2
					ON (
						(cp1.id_cart = cp2.id_cart)
						AND (cp1.id_product = cp2.id_product)
						AND (cp1.id_product_attribute = cp2.id_product_attribute)
						AND (cp1.id_cart_product = cp2.id_cart_product)
						AND (cp1.id_address_delivery <> cp2.id_address_delivery)
						AND (cp1.date_add > cp2.date_add)
					)';
					Db::getInstance()->execute($sql);
		}

				$sql = 'UPDATE `'._DB_PREFIX_.'cart_product`
		SET `id_address_delivery` = (
			SELECT `id_address_delivery` FROM `'._DB_PREFIX_.'cart`
			WHERE `id_cart` = '.(int)$this->id.' AND `id_shop` = '.(int)$this->id_shop.'
		)
		WHERE `id_cart` = '.(int)$this->id.'
		'.(Configuration::get('PS_ALLOW_MULTISHIPPING') ? ' AND `id_shop` = '.(int)$this->id_shop : '');

		$cache_id = 'Cart::setNoMultishipping'.(int)$this->id.'-'.(int)$this->id_shop.((isset($this->id_address_delivery) && $this->id_address_delivery) ? '-'.(int)$this->id_address_delivery : '');
		if (!Cache::isStored($cache_id))
		{
			if ($result = (bool)Db::getInstance()->execute($sql))
				$emptyCache = true;
			Cache::store($cache_id, $result);
		}

		if (Customization::isFeatureActive())
			Db::getInstance()->execute('
			UPDATE `'._DB_PREFIX_.'customization`
			SET `id_address_delivery` = (
				SELECT `id_address_delivery` FROM `'._DB_PREFIX_.'cart`
				WHERE `id_cart` = '.(int)$this->id.'
			)
			WHERE `id_cart` = '.(int)$this->id);

		if ($emptyCache)	
			$this->_products = null;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function checkQuantities($return_product = false)
	{
		if (Configuration::get('PS_CATALOG_MODE') && !defined('_PS_ADMIN_DIR_'))
			return false;

		$products_quantities = array();
		$products = $this->getProducts();
		foreach ($products as $product)
		{
			if (!$this->allow_seperated_package && !$product['allow_oosp'] && StockAvailable::dependsOnStock($product['id_product']) &&
				$product['advanced_stock_management'] && (bool)Context::getContext()->customer->isLogged() && ($delivery = $this->getDeliveryOption()) && !empty($delivery))
				$product['stock_quantity'] = StockManager::getStockByCarrier((int)$product['id_product'], (int)$product['id_product_attribute'], $delivery);
			if (!$product['active'] || !$product['available_for_order'])
				return $return_product ? $product : false;
			if (!$product['allow_oosp'])
			{
				$qty = (float)PP::resolveQty($product['cart_quantity'], $product['cart_quantity_fractional']);
				if ((float)$product['stock_quantity'] < $qty)
					return $return_product ? $product : false;
				if (!isset($products_quantities[$product['id_product']][$product['id_product_attribute']]))
					$products_quantities[$product['id_product']][$product['id_product_attribute']] = 0;
				$products_quantities[$product['id_product']][$product['id_product_attribute']] += $qty;
			}

		}

		foreach ($products as $product)
		{
			if (!$product['allow_oosp'])
			{
				if ((float)$product['stock_quantity'] < $products_quantities[$product['id_product']][$product['id_product_attribute']])
					return $return_product ? $product : false;
			}
		}

		return true;
	}
}
