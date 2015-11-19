<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class Product extends ProductCore
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
	public $id_pp_template = 0;
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	private $p_properties;
	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static $amend = true;

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function ppInit()
	{
		self::$definition['fields'] = array_merge(self::$definition['fields'], array(
			'minimal_quantity' => array('type' => self::TYPE_INT, 'shop' => true, 'validate' => 'validateProductQuantity'), 			'minimal_quantity_fractional' => array('type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isUnsignedFloat'),
			'id_pp_template' => array('type' => self::TYPE_INT)
		));
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, Context $context = null)
	{
		parent::__construct($id_product, $full, $id_lang, $id_shop, $context);
		if (self::$amend)
			$this->amend($full && $this->id);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function updateAttribute($id_product_attribute, $wholesale_price, $price, $weight, $unit, $ecotax,
		$id_images, $reference, $ean13, $default, $location = null, $upc = null, $minimal_quantity = null, $available_date = null, $update_all_fields = true, array $id_shop_list = array())
	{
		$combination = new Combination($id_product_attribute);

		if (!$update_all_fields)
			$combination->setFieldsToUpdate(array(
				'price' => !is_null($price),
				'wholesale_price' => !is_null($wholesale_price),
				'ecotax' => !is_null($ecotax),
				'weight' => !is_null($weight),
				'unit_price_impact' => !is_null($unit),
				'default_on' => !is_null($default),
				'minimal_quantity' => !is_null($minimal_quantity),
				'minimal_quantity_fractional' => !is_null($minimal_quantity),
				'available_date' => !is_null($available_date),
			));

		$price = str_replace(',', '.', $price);
		$weight = str_replace(',', '.', $weight);

		$combination->price = (float)$price;
		$combination->wholesale_price = (float)$wholesale_price;
		$combination->ecotax = (float)$ecotax;
		$combination->weight = (float)$weight;
		$combination->unit_price_impact = (float)$unit;
		$combination->reference = pSQL($reference);
		$combination->location = pSQL($location);
		$combination->ean13 = pSQL($ean13);
		$combination->upc = pSQL($upc);
		$combination->default_on = (int)$default;
		if ($combination->update_fields['minimal_quantity'])
		{
			$minimal_quantity = str_replace(',', '.', $minimal_quantity);
			$this->setMinQty($minimal_quantity, $combination);
		}
		$combination->available_date = $available_date ? pSQL($available_date) : '0000-00-00';

		if (count($id_shop_list))
			$combination->id_shop_list = $id_shop_list;

		$combination->save();

		if (is_array($id_images) && count($id_images))
			$combination->setImages($id_images);

		$id_default_attribute = (int)Product::updateDefaultAttribute($this->id);
		if ($id_default_attribute)
			$this->cache_default_attribute = $id_default_attribute;

		Hook::exec('actionProductAttributeUpdate', array('id_product_attribute' => (int)$id_product_attribute));
		Tools::clearColorListCache($this->id);
		return true;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function addAttribute($price, $weight, $unit_impact, $ecotax, $id_images, $reference, $ean13,
								$default, $location = null, $upc = null, $minimal_quantity = 1, array $id_shop_list = array(), $available_date = null)
	{
		if (!$this->id)
			return;

		$price = str_replace(',', '.', $price);
		$weight = str_replace(',', '.', $weight);

		$combination = new Combination();
		$combination->id_product = (int)$this->id;
		$combination->price = (float)$price;
		$combination->ecotax = (float)$ecotax;
		$combination->quantity = 0;
		$combination->weight = (float)$weight;
		$combination->unit_price_impact = (float)$unit_impact;
		$combination->reference = pSQL($reference);
		$combination->location = pSQL($location);
		$combination->ean13 = pSQL($ean13);
		$combination->upc = pSQL($upc);
		$combination->default_on = (int)$default;
		$minimal_quantity = str_replace(',', '.', $minimal_quantity);
		$this->setMinQty($minimal_quantity, $combination);
		$combination->available_date = $available_date;

		if (count($id_shop_list))
			$combination->id_shop_list = array_unique($id_shop_list);

		$combination->add();

		if (!$combination->id)
			return false;

		$total_quantity = (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT SUM(quantity + quantity_remainder) as quantity
			FROM '._DB_PREFIX_.'stock_available
			WHERE id_product = '.(int)$this->id.'
			AND id_product_attribute <> 0 '
		);

		if (!$total_quantity)
			Db::getInstance()->update('stock_available', array('quantity' => 0, 'quantity_remainder' => 0), '`id_product` = '.$this->id);

		$id_default_attribute = Product::updateDefaultAttribute($this->id);

		if ($id_default_attribute)
		{
			$this->cache_default_attribute = $id_default_attribute;
			if (!$combination->available_date)
				$this->setAvailableDate();
		}

		if (!empty($id_images))
			$combination->setImages($id_images);

		Tools::clearColorListCache($this->id);

		if (Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT') != 0 && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'))
		{
			$warehouse_location_entity = new WarehouseProductLocation();
			$warehouse_location_entity->id_product = $this->id;
			$warehouse_location_entity->id_product_attribute = (int)$combination->id;
			$warehouse_location_entity->id_warehouse = Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT');
			$warehouse_location_entity->location = pSQL('');
			$warehouse_location_entity->save();
		}

		return (int)$combination->id;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function getPriceStatic($id_product, $usetax = true, $id_product_attribute = null, $decimals = 6, $divisor = null,
		$only_reduc = false, $usereduc = true, $quantity = 1, $force_associated_tax = false, $id_customer = null, $id_cart = null,
		$id_address = null, &$specific_price_output = null, $with_ecotax = true, $use_group_reduction = true, Context $context = null,
		$use_customer_price = true)
	{
		if (!$context)
			$context = Context::getContext();

		$cur_cart = $context->cart;

		if ($divisor !== null)
			Tools::displayParameterAsDeprecated('divisor');

		if (!Validate::isBool($usetax) || !Validate::isUnsignedId($id_product))
			die(Tools::displayError());

				$id_group = null;
		if ($id_customer)
			$id_group = Customer::getDefaultGroupId((int)$id_customer);
		if (!$id_group)
			$id_group = (int)Group::getCurrent()->id;

				if (!is_object($cur_cart) || (Validate::isUnsignedInt($id_cart) && $id_cart && $cur_cart->id != $id_cart))
		{
			/*
			* When a user (e.g., guest, customer, Google...) is on PrestaShop, he has already its cart as the global (see /init.php)
			* When a non-user calls directly this method (e.g., payment module...) is on PrestaShop, he does not have already it BUT knows the cart ID
			* When called from the back office, cart ID can be inexistant
			*/
			if (!$id_cart && !isset($context->employee))
				die(Tools::displayError());
			$cur_cart = new Cart($id_cart);
						if (!Validate::isLoadedObject($context->cart))
				$context->cart = $cur_cart;
		}

		$qty = $quantity;
		if (is_array($quantity))
			$quantity = PP::resolveQty($qty[0], $qty[1]);

		$cart_quantity = 0;
		if ((int)$id_cart)
		{
			$cache_id = 'Product::getPriceStatic_'.(int)$id_product.'-'.(int)$id_cart;
			if (!Cache::isStored($cache_id) || ($cart_quantity = Cache::retrieve($cache_id) != (float)$quantity))
			{
				$sql = 'SELECT SUM('.PP::sqlQty('quantity').')
				FROM `'._DB_PREFIX_.'cart_product`
				WHERE `id_product` = '.(int)$id_product.'
				AND `id_cart` = '.(int)$id_cart;
				$cart_quantity = (float)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
				Cache::store($cache_id, $cart_quantity);
			}
			$cart_quantity = Cache::retrieve($cache_id);
		}

		$id_currency = (int)Validate::isLoadedObject($context->currency) ? $context->currency->id : Configuration::get('PS_CURRENCY_DEFAULT');

				$id_country = (int)$context->country->id;
		$id_state = 0;
		$zipcode = 0;

		if (!$id_address && Validate::isLoadedObject($cur_cart))
			$id_address = $cur_cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};

		if ($id_address)
		{
			$address_infos = Address::getCountryAndState($id_address);
			if ($address_infos['id_country'])
			{
				$id_country = (int)$address_infos['id_country'];
				$id_state = (int)$address_infos['id_state'];
				$zipcode = $address_infos['postcode'];
			}
		}
		elseif (isset($context->customer->geoloc_id_country))
		{
			$id_country = (int)$context->customer->geoloc_id_country;
			$id_state = (int)$context->customer->id_state;
			$zipcode = $context->customer->postcode;
		}

		if (Tax::excludeTaxeOption())
			$usetax = false;

		if ($usetax != false
			&& !empty($address_infos['vat_number'])
			&& $address_infos['id_country'] != Configuration::get('VATNUMBER_COUNTRY')
			&& Configuration::get('VATNUMBER_MANAGEMENT'))
			$usetax = false;

		if (is_null($id_customer) && Validate::isLoadedObject($context->customer))
			$id_customer = $context->customer->id;

		return Product::priceCalculation(
			$context->shop->id,
			$id_product,
			$id_product_attribute,
			$id_country,
			$id_state,
			$zipcode,
			$id_currency,
			$id_group,
			$qty,
			$usetax,
			$decimals,
			$only_reduc,
			$usereduc,
			$with_ecotax,
			$specific_price_output,
			$use_group_reduction,
			$id_customer,
			$use_customer_price,
			$id_cart,
			$cart_quantity
		);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function getAttributesGroups($id_lang)
	{
		if (!Combination::isFeatureActive())
			return array();
		$sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
					a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, product_attribute_shop.`id_product_attribute`,
					IFNULL(stock.quantity, 0) + IFNULL(stock.quantity_remainder, 0) as quantity, product_attribute_shop.`price`, product_attribute_shop.`ecotax`, product_attribute_shop.`weight`,
					product_attribute_shop.`default_on`, pa.`reference`, product_attribute_shop.`unit_price_impact`,
					product_attribute_shop.`minimal_quantity`, product_attribute_shop.`minimal_quantity_fractional`, product_attribute_shop.`available_date`, ag.`group_type`
				FROM `'._DB_PREFIX_.'product_attribute` pa
				'.Shop::addSqlAssociation('product_attribute', 'pa').'
				'.Product::sqlStock('pa', 'pa').'
				LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
				LEFT JOIN `'._DB_PREFIX_.'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
				LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
				'.Shop::addSqlAssociation('attribute', 'a').'
				WHERE pa.`id_product` = '.(int)$this->id.'
					AND al.`id_lang` = '.(int)$id_lang.'
					AND agl.`id_lang` = '.(int)$id_lang.'
				GROUP BY id_attribute_group, id_product_attribute
				ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';
		return Db::getInstance()->executeS($sql);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function getAllCustomizedDatas($id_cart, $id_lang = null, $only_in_cart = true, $id_shop = null)
	{
		if (!Customization::isFeatureActive())
			return false;

				if (!$id_cart)
			return false;
		if (!$id_lang)
			$id_lang = Context::getContext()->language->id;
		if (Shop::isFeatureActive() && !$id_shop)
			$id_shop = (int)Context::getContext()->shop->id;

		if (!$result = Db::getInstance()->executeS('
			SELECT cd.`id_customization`, c.`id_address_delivery`, c.`id_product`, cfl.`id_customization_field`, c.`id_product_attribute`, c.`id_cart_product`,
				cd.`type`, cd.`index`, cd.`value`, cfl.`name`
			FROM `'._DB_PREFIX_.'customized_data` cd
			NATURAL JOIN `'._DB_PREFIX_.'customization` c
			LEFT JOIN `'._DB_PREFIX_.'customization_field_lang` cfl ON (cfl.id_customization_field = cd.`index` AND id_lang = '.(int)$id_lang.
				($id_shop ? ' AND cfl.`id_shop` = '.$id_shop : '').')
			WHERE c.`id_cart` = '.(int)$id_cart.
			($only_in_cart ? ' AND c.`in_cart` = 1' : '').'
			ORDER BY `id_product`, `id_product_attribute`, `type`, `index`'))
			return false;

		$customized_datas = array();

		foreach ($result as $row)
			$customized_datas[(int)$row['id_product']][(int)$row['id_product_attribute']][(int)$row['id_address_delivery']][(int)$row['id_customization']]['datas'][(int)$row['type']][] = $row;

		if (!$result = Db::getInstance()->executeS(
			'SELECT `id_product`, `id_product_attribute`, `id_customization`, `id_address_delivery`, `quantity`, `quantity_fractional`, `quantity_refunded`, `quantity_returned`, `id_cart_product`
			FROM `'._DB_PREFIX_.'customization`
			WHERE `id_cart` = '.(int)$id_cart.($only_in_cart ? '
			AND `in_cart` = 1' : '')))
			return false;

		foreach ($result as $row)
		{
			$customized_datas[(int)$row['id_product']][(int)$row['id_product_attribute']][(int)$row['id_address_delivery']][(int)$row['id_customization']]['id_cart_product'] = (int)$row['id_cart_product'];
			$customized_datas[(int)$row['id_product']][(int)$row['id_product_attribute']][(int)$row['id_address_delivery']][(int)$row['id_customization']]['quantity'] = (int)$row['quantity'];
			$customized_datas[(int)$row['id_product']][(int)$row['id_product_attribute']][(int)$row['id_address_delivery']][(int)$row['id_customization']]['quantity_fractional'] = (float)$row['quantity_fractional'];
			$customized_datas[(int)$row['id_product']][(int)$row['id_product_attribute']][(int)$row['id_address_delivery']][(int)$row['id_customization']]['quantity_refunded'] = (int)$row['quantity_refunded'];
			$customized_datas[(int)$row['id_product']][(int)$row['id_product_attribute']][(int)$row['id_address_delivery']][(int)$row['id_customization']]['quantity_returned'] = (int)$row['quantity_returned'];
		}

		return $customized_datas;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function addCustomizationPrice(&$products, &$customized_datas)
	{
		if (!$customized_datas)
			return;

		foreach ($products as &$product_update)
		{
			$product_update['customizationQuantityTotal'] = 0;
			$product_update['customizationQuantityRefunded'] = 0;
			$product_update['customizationQuantityReturned'] = 0;
		}
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function hasCustomizedDatas($product, $customized_datas)
	{
		if ($customized_datas)
		{
			
			$id_product = (isset($product['id_product']) ? 'id_product' : 'product_id');
			$id_product_attribute = (isset($product['id_product_attribute']) ? 'id_product_attribute' : 'product_attribute_id');
			if (isset($customized_datas[(int)$product[$id_product]][(int)$product[$id_product_attribute]]))
			{
				foreach ($customized_datas[(int)$product[$id_product]][(int)$product[$id_product_attribute]] as $addresses)
					foreach ($addresses as $customization)
						if ($product['id_cart_product'] == $customization['id_cart_product'])
							return true;
			}
		}
		return false;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	private function amend($all)
	{
		$this->productProperties();
		if ($this->p_properties['pp_unit_price_ratio'] > 0)
			$this->unit_price_ratio = $this->p_properties['pp_unit_price_ratio'];
		if (Tools::strlen($this->p_properties['pp_unity_text']) > 0)
			$this->unity = $this->p_properties['pp_unity_text'];
		if ($all)
		{
			if ($this->unit_price_ratio != 0)
				$this->unit_price = ($this->price / $this->unit_price_ratio); 		}
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function amendProduct(&$product)
	{
		if (is_array($product))
		{
			$properties = PP::getProductProperties($product);
			$product = array_merge($product, $properties);
			if ($properties['pp_unit_price_ratio'] > 0 || !isset($product['unit_price_ratio']))
				$product['unit_price_ratio'] = $properties['pp_unit_price_ratio'];
			if (Tools::strlen($properties['pp_unity_text']) > 0 || !isset($product['unity']))
				$product['unity'] = $properties['pp_unity_text'];
			if ($product['unit_price_ratio'] != 0 && array_key_exists('price', $product))
				$product['unit_price'] = ($product['price'] / $product['unit_price_ratio']); 		}
		return $product;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public static function getRetailPrice($product)
	{
		$price_display = Product::getTaxCalculationMethod();
		return $product->getPrice(!$price_display || $price_display == 2, false, _PS_PRICE_DISPLAY_PRECISION_);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function qtyPolicy()
	{
		$this->productProperties();
		return $this->p_properties['pp_qty_policy'];
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function productProperties()
	{
		$this->p_properties = PP::getProductProperties($this);
		return $this->p_properties;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function productProp()
	{
		return Db::getInstance()->executeS('SELECT id_product_attribute, position, quantity FROM `'._DB_PREFIX_.'pp_product_prop` WHERE `id_product` = '.$this->id);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function normalizeQty($qty)
	{
		return PP::normalizeQty($qty, $this->qtyPolicy());
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function setMinQty($min_quantity, $object = false)
	{
		if ($object === false)
			$object = $this;
		if (PP::qtyPolicyLegacy($this->qtyPolicy()))
		{
			$object->minimal_quantity = $this->resolveMinQty($min_quantity, $min_quantity);
			$object->minimal_quantity_fractional = 0;
		}
		else
		{
			$object->minimal_quantity = 1;
			$object->minimal_quantity_fractional = ((float)$min_quantity > 0 ? $this->resolveMinQty($min_quantity, $min_quantity) : 0);
		}
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function minQty($format = false)
	{
		$q = $this->resolveMinQty($this->minimal_quantity, $this->minimal_quantity_fractional);
		if (PP::qtyPolicyLegacy($this->qtyPolicy()))
			$this->minimal_quantity = $q;
		else
			$this->minimal_quantity_fractional = $q;
		return ($format ? PP::formatQty($q) : $q);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function resolveMinQty($min_qty, $min_qty_fractional, $raw_data = false)
	{
		return $this->resolveMinQtyInternal($min_qty, $min_qty_fractional, 'pp_minimal_quantity', $raw_data);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function resolveBoMinQty($min_qty, $min_qty_fractional, $raw_data = false)
	{
		return $this->resolveMinQtyInternal($min_qty, $min_qty_fractional, 'pp_bo_minimal_quantity', $raw_data);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function attributeMinQty($id_product_attribute)
	{
		$q = Db::getInstance()->getRow('
			SELECT `minimal_quantity`, `minimal_quantity_fractional`
			FROM `'._DB_PREFIX_.'product_attribute_shop` pas
			WHERE `id_shop` = '.(int)Context::getContext()->shop->id.'
			AND `id_product_attribute` = '.(int)$id_product_attribute
		);
		if ($q !== false)
			$q = $this->resolveMinQty($q['minimal_quantity'], $q['minimal_quantity_fractional']);
		return $q;
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function defaultQty($format = false)
	{
		return $this->defaultQtyInternal($this->minQty(), $format);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function attributeDefaultQty($id_product_attribute, $format = false)
	{
		return $this->defaultQtyInternal($this->attributeMinQty($id_product_attribute), $format);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	public function qtyStep()
	{
		$this->productProperties();
		return $this->p_properties['pp_qty_step'];
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	private function resolveMinQtyInternal($min_qty, $min_qty_fractional, $key, $raw_data)
	{
		$this->productProperties();
		$q = ($this->p_properties['pp_ext'] == 1 ? 0 : $this->qtyPolicy());
		if ($q == 0)
			return (int)((int)$min_qty > 1 || $raw_data ? $min_qty : $this->p_properties[$key]);
						else
			return (float)((float)$min_qty_fractional > 0 || $raw_data ? $min_qty_fractional : $this->p_properties[$key]);
	}

	/*
	* module: pproperties
	* date: 2015-10-30 20:57:18
	* version: 2.14
	*/
	private function defaultQtyInternal($qty, $format)
	{
		$default_qty = $this->p_properties['pp_default_quantity'];
		if ($qty > $default_qty)
			$default_qty = $qty;
		return ($format ? PP::formatQty($default_qty) : $default_qty);
	}
}


Product::ppInit();
