<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class StockAvailable extends StockAvailableCore
{
	public $quantity_remainder = 0;

	public static function ppInit()
	{
		self::$definition['fields'] = array_merge(self::$definition['fields'], array(
			'quantity_remainder' => array('type' => self::TYPE_FLOAT, 'validate'=> 'isUnsignedFloat')
		));
	}

	public static function synchronize($id_product, $order_id_shop = null)
	{
		if (!Validate::isUnsignedId($id_product))
			return false;

		//if product is pack sync recursivly product in pack
		if (Pack::isPack($id_product))
		{
			if (Validate::isLoadedObject($product = new Product((int)$id_product)))
			{
				if ($product->pack_stock_type == 1
					|| $product->pack_stock_type == 2
					|| ($product->pack_stock_type == 3 && Configuration::get('PS_PACK_STOCK_TYPE') > 0))
				{
					$products_pack = Pack::getItems($id_product, (int)Configuration::get('PS_LANG_DEFAULT'));
					foreach ($products_pack as $product_pack)
						StockAvailable::synchronize($product_pack->id, $order_id_shop);
				}
			}
			else
				return false;
		}

		// gets warehouse ids grouped by shops
		$ids_warehouse = Warehouse::getWarehousesGroupedByShops();
		if ($order_id_shop !== null)
		{
			$order_warehouses = array();
			$wh = Warehouse::getWarehouses(false, (int)$order_id_shop);
			foreach ($wh as $warehouse)
				$order_warehouses[] = $warehouse['id_warehouse'];
		}

		// gets all product attributes ids
		$ids_product_attribute = array();
		foreach (Product::getProductAttributesIds($id_product) as $id_product_attribute)
			$ids_product_attribute[] = $id_product_attribute['id_product_attribute'];

		// Allow to order the product when out of stock?
		$out_of_stock = StockAvailable::outOfStock($id_product);

		$manager = StockManagerFactory::getManager();
		// loops on $ids_warehouse to synchronize quantities
		foreach ($ids_warehouse as $id_shop => $warehouses)
		{
			// first, checks if the product depends on stock for the given shop $id_shop
			if (StockAvailable::dependsOnStock($id_product, $id_shop))
			{
				// init quantity
				$product_quantity = 0;

				// if it's a simple product
				if (empty($ids_product_attribute))
				{
					$allowed_warehouse_for_product = WareHouse::getProductWarehouseList((int)$id_product, 0, (int)$id_shop);
					$allowed_warehouse_for_product_clean = array();
					foreach ($allowed_warehouse_for_product as $warehouse)
						$allowed_warehouse_for_product_clean[] = (int)$warehouse['id_warehouse'];
					$allowed_warehouse_for_product_clean = array_intersect($allowed_warehouse_for_product_clean, $warehouses);
					if ($order_id_shop != null && !count(array_intersect($allowed_warehouse_for_product_clean, $order_warehouses)))
						continue;

					$product_quantity = $manager->getProductRealQuantities($id_product, null, $allowed_warehouse_for_product_clean, true);

					Hook::exec('actionUpdateQuantity',
									array(
										'id_product' => $id_product,
										'id_product_attribute' => 0,
										'quantity' => $product_quantity
										)
					);
				}
				// else this product has attributes, hence loops on $ids_product_attribute
				else
				{
					foreach ($ids_product_attribute as $id_product_attribute)
					{

						$allowed_warehouse_for_combination = WareHouse::getProductWarehouseList((int)$id_product, (int)$id_product_attribute, (int)$id_shop);
						$allowed_warehouse_for_combination_clean = array();
						foreach ($allowed_warehouse_for_combination as $warehouse)
							$allowed_warehouse_for_combination_clean[] = (int)$warehouse['id_warehouse'];
						$allowed_warehouse_for_combination_clean = array_intersect($allowed_warehouse_for_combination_clean, $warehouses);
						if ($order_id_shop != null && !count(array_intersect($allowed_warehouse_for_combination_clean, $order_warehouses)))
							continue;

						$quantity = $manager->getProductRealQuantities($id_product, $id_product_attribute, $allowed_warehouse_for_combination_clean, true);

						$query = new DbQuery();
						$query->select('COUNT(*)');
						$query->from('stock_available');
						$query->where('id_product = '.(int)$id_product.' AND id_product_attribute = '.(int)$id_product_attribute.
							StockAvailable::addSqlShopRestriction(null, $id_shop));

						if ((int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query))
						{
							$query = array(
								'table' => 'stock_available',
								'data' => PP::hydrateQty(array(), 'quantity', $quantity),
								'where' => 'id_product = '.(int)$id_product.' AND id_product_attribute = '.(int)$id_product_attribute.
								StockAvailable::addSqlShopRestriction(null, $id_shop)
							);
							Db::getInstance()->update($query['table'], $query['data'], $query['where']);
						}
						else
						{
							$query = array(
								'table' => 'stock_available',
								'data' => PP::hydrateQty(array(
									'depends_on_stock' => 1,
									'out_of_stock' => $out_of_stock,
									'id_product' => (int)$id_product,
									'id_product_attribute' => (int)$id_product_attribute,
									), 'quantity', $quantity
								)
							);
							StockAvailable::addSqlShopParams($query['data']);
							Db::getInstance()->insert($query['table'], $query['data']);
						}

						$product_quantity += $quantity;

						Hook::exec('actionUpdateQuantity',
									array(
										'id_product' => $id_product,
										'id_product_attribute' => $id_product_attribute,
										'quantity' => $quantity
									)
						);
					}
				}
				// updates
				// if $id_product has attributes, it also updates the sum for all attributes
				$query = array(
					'table' => 'stock_available',
					'data' =>  PP::hydrateQty(array(), 'quantity', $product_quantity),
					'where' => 'id_product = '.(int)$id_product.' AND id_product_attribute = 0'.
					StockAvailable::addSqlShopRestriction(null, $id_shop)
				);
				Db::getInstance()->update($query['table'], $query['data'], $query['where']);
			}
		}

		// In case there are no warehouses, removes product from StockAvailable
		if (count($ids_warehouse) == 0 && StockAvailable::dependsOnStock((int)$id_product))
			Db::getInstance()->update('stock_available', array('quantity' => 0, 'quantity_remainder' => 0), 'id_product = '.(int)$id_product);

		Cache::clean('StockAvailable::getQuantityAvailableByProduct_'.(int)$id_product.'*');
	}

	public static function getQuantityAvailableByProduct($id_product = null, $id_product_attribute = null, $id_shop = null)
	{
		// if null, it's a product without attributes
		if ($id_product_attribute === null)
			$id_product_attribute = 0;

		$key = 'StockAvailable::getQuantityAvailableByProduct_'.(int)$id_product.'-'.(int)$id_product_attribute.'-'.(int)$id_shop;
		if (!Cache::isStored($key))
		{
			$query = new DbQuery();
			$query->select('SUM(quantity + quantity_remainder)');
			$query->from('stock_available');

			// if null, it's a product without attributes
			if ($id_product !== null)
				$query->where('id_product = '.(int)$id_product);

			$query->where('id_product_attribute = '.(int)$id_product_attribute);
			$query = StockAvailable::addSqlShopRestriction($query, $id_shop);

			Cache::store($key, (float)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($query));
		}

		return Cache::retrieve($key);
	}

	public function postSave()
	{
		if ($this->id_product_attribute == 0)
			return true;

		$id_shop = (Shop::getContext() != Shop::CONTEXT_GROUP && $this->id_shop ? $this->id_shop : null);

		if (!Configuration::get('PS_DISP_UNAVAILABLE_ATTR'))
		{
			$combination = new Combination((int)$this->id_product_attribute);
			if ($colors = $combination->getColorsAttributes())
			{
				$product = new Product((int)$this->id_product);
				foreach ($colors as $color)
				{
					if ($product->isColorUnavailable((int)$color['id_attribute'], (int)$this->id_shop))
					{
						Tools::clearColorListCache($product->id);
						break;
					}
				}
			}
		}

		$total_quantity = (float)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
			SELECT SUM(quantity) + SUM(quantity_remainder) as quantity
			FROM '._DB_PREFIX_.'stock_available
			WHERE id_product = '.(int)$this->id_product.'
			AND id_product_attribute <> 0 '.
			StockAvailable::addSqlShopRestriction(null, $id_shop)
		);

		$this->setQuantity($this->id_product, 0, $total_quantity, $id_shop);

		return true;
	}

	public static function updateQuantity($id_product, $id_product_attribute, $delta_quantity, $id_shop = null)
	{
		if (!Validate::isUnsignedId($id_product))
			return false;

		$id_stock_available = StockAvailable::getStockAvailableIdByProductId($id_product, $id_product_attribute, $id_shop);

		if (!$id_stock_available)
			return false;

		// Update quantity of the pack products
		if (Pack::isPack($id_product))
		{
			if (Validate::isLoadedObject($product = new Product((int)$id_product)))
			{
				if ($product->pack_stock_type == 1
					|| $product->pack_stock_type == 2
					|| ($product->pack_stock_type == 3 && Configuration::get('PS_PACK_STOCK_TYPE') > 0))
				{
					$products_pack = Pack::getItems($id_product, (int)Configuration::get('PS_LANG_DEFAULT'));
					foreach ($products_pack as $product_pack)
						StockAvailable::updateQuantity($product_pack->id, $product_pack->id_pack_product_attribute, $product_pack->pack_quantity * $delta_quantity, $id_shop);
				}

				$stock_available = new StockAvailable($id_stock_available);
				PP::setQty($stock_available, $stock_available->quantity + $stock_available->quantity_remainder + $delta_quantity);

				if ($product->pack_stock_type == 0 || $product->pack_stock_type == 2 ||
					($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 0 || Configuration::get('PS_PACK_STOCK_TYPE') == 2)))
					$stock_available->update();
			}
			else
				return false;
		}
		else
		{
			$stock_available = new StockAvailable($id_stock_available);
			PP::setQty($stock_available, $stock_available->quantity + $stock_available->quantity_remainder + $delta_quantity);
			$stock_available->update();
		}

		Cache::clean('StockAvailable::getQuantityAvailableByProduct_'.(int)$id_product.'*');

		Hook::exec('actionUpdateQuantity',
					array(
						'id_product' => $id_product,
						'id_product_attribute' => $id_product_attribute,
						'quantity' => $stock_available->quantity + $stock_available->quantity_remainder
					)
				);

		return true;
	}

	public static function setQuantity($id_product, $id_product_attribute, $quantity, $id_shop = null)
	{
		if (!Validate::isUnsignedId($id_product))
			return false;

		$context = Context::getContext();

		// if there is no $id_shop, gets the context one
		if ($id_shop === null && Shop::getContext() != Shop::CONTEXT_GROUP)
			$id_shop = (int)$context->shop->id;

		$depends_on_stock = StockAvailable::dependsOnStock($id_product);

		//Try to set available quantity if product does not depend on physical stock
		if (!$depends_on_stock)
		{
			$id_stock_available = (int)StockAvailable::getStockAvailableIdByProductId($id_product, $id_product_attribute, $id_shop);
			if ($id_stock_available)
			{
				$stock_available = new StockAvailable($id_stock_available);
				PP::setQty($stock_available, $quantity);
				$stock_available->update();
			}
			else
			{
				$out_of_stock = StockAvailable::outOfStock($id_product, $id_shop);
				$stock_available = new StockAvailable();
				$stock_available->out_of_stock = (int)$out_of_stock;
				$stock_available->id_product = (int)$id_product;
				$stock_available->id_product_attribute = (int)$id_product_attribute;
				PP::setQty($stock_available, $quantity);

				if ($id_shop === null)
					$shop_group = Shop::getContextShopGroup();
				else
					$shop_group = new ShopGroup((int)Shop::getGroupFromShop((int)$id_shop));

				// if quantities are shared between shops of the group
				if ($shop_group->share_stock)
				{
					$stock_available->id_shop = 0;
					$stock_available->id_shop_group = (int)$shop_group->id;
				}
				else
				{
					$stock_available->id_shop = (int)$id_shop;
					$stock_available->id_shop_group = 0;
				}
				$stock_available->add();
			}

			Hook::exec('actionUpdateQuantity',
						array(
						'id_product' => $id_product,
						'id_product_attribute' => $id_product_attribute,
						'quantity' => $stock_available->quantity + $stock_available->quantity_remainder
						)
					);
		}

		Cache::clean('StockAvailable::getQuantityAvailableByProduct_'.(int)$id_product.'*');
	}
}
