<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminStockManagementController extends AdminStockManagementControllerCore
{

	public function __construct()
	{
		parent::__construct();
		$this->fields_list['stock']['callback'] = 'callbackStockQuantity';
	}

	public static function callbackStockQuantity($echo, $tr)
	{
		return PP::adminControllerDisplayListContentQuantity($echo, $tr, 'stock', 'stock-management stock');
	}

	public function postProcess()
	{
		$this->adminControllerPostProcess();

		// Checks access
		if (Tools::isSubmit('addStock') && !($this->tabAccess['add'] === '1'))
			$this->errors[] = Tools::displayError('You do not have the required permission to add stock.');
		if (Tools::isSubmit('removeStock') && !($this->tabAccess['delete'] === '1'))
			$this->errors[] = Tools::displayError('You do not have the required permission to delete stock');
		if (Tools::isSubmit('transferStock') && !($this->tabAccess['edit'] === '1'))
			$this->errors[] = Tools::displayError('You do not have the required permission to transfer stock.');

		if (count($this->errors))
			return;

		// Global checks when add / remove / transfer product
		if ((Tools::isSubmit('addstock') || Tools::isSubmit('removestock') || Tools::isSubmit('transferstock') ) && Tools::isSubmit('is_post'))
		{
			// get product ID
			$id_product = (int)Tools::getValue('id_product', 0);
			if ($id_product <= 0)
				$this->errors[] = Tools::displayError('The selected product is not valid.');

			// get product_attribute ID
			$id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);

			// check the product hash
			$check = Tools::getValue('check', '');
			$check_valid = md5(_COOKIE_KEY_.$id_product.$id_product_attribute);
			if ($check != $check_valid)
				$this->errors[] = Tools::displayError('The selected product is not valid.');

			// get quantity and check that the post value is really an integer
			// If it's not, we have nothing to do
			$quantity = Tools::getValue('quantity', 0);
			$quantity = PP::normalizeProductQty($quantity, $id_product);
			if (!is_numeric($quantity) || $quantity <= 0)
				$this->errors[] = Tools::displayError('The quantity value is not valid.');
			//$quantity = (int)$quantity;

			$token = Tools::getValue('token') ? Tools::getValue('token') : $this->token;
			$redirect = self::$currentIndex.'&token='.$token;
		}

		// Global checks when add / remove product
		if ((Tools::isSubmit('addstock') || Tools::isSubmit('removestock') ) && Tools::isSubmit('is_post'))
		{
			// get warehouse id
			$id_warehouse = (int)Tools::getValue('id_warehouse', 0);
			if ($id_warehouse <= 0 || !Warehouse::exists($id_warehouse))
				$this->errors[] = Tools::displayError('The selected warehouse is not valid.');

			// get stock movement reason id
			$id_stock_mvt_reason = (int)Tools::getValue('id_stock_mvt_reason', 0);
			if ($id_stock_mvt_reason <= 0 || !StockMvtReason::exists($id_stock_mvt_reason))
				$this->errors[] = Tools::displayError('The reason is not valid.');

			// get usable flag
			$usable = Tools::getValue('usable', null);
			if (is_null($usable))
				$this->errors[] = Tools::displayError('You have to specify whether the product quantity is usable for sale on shops or not.');
			$usable = (bool)$usable;
		}

		if (Tools::isSubmit('addstock') && Tools::isSubmit('is_post'))
		{
			// get product unit price
			$price = str_replace(',', '.', Tools::getValue('price', 0));
			if (!is_numeric($price))
				$this->errors[] = Tools::displayError('The product price is not valid.');
			$price = round((float)$price, 6);

			// get product unit price currency id
			$id_currency = (int)Tools::getValue('id_currency', 0);
			if ($id_currency <= 0 || ( !($result = Currency::getCurrency($id_currency)) || empty($result) ))
				$this->errors[] = Tools::displayError('The selected currency is not valid.');

			// if all is ok, add stock
			if (count($this->errors) == 0)
			{
				$warehouse = new Warehouse($id_warehouse);

				// convert price to warehouse currency if needed
				if ($id_currency != $warehouse->id_currency)
				{
					// First convert price to the default currency
					$price_converted_to_default_currency = Tools::convertPrice($price, $id_currency, false);

					// Convert the new price from default currency to needed currency
					$price = Tools::convertPrice($price_converted_to_default_currency, $warehouse->id_currency, true);
				}

				// add stock
				$stock_manager = StockManagerFactory::getManager();

				if ($stock_manager->addProduct($id_product, $id_product_attribute, $warehouse, $quantity, $id_stock_mvt_reason, $price, $usable))
				{
					// Create warehouse_product_location entry if we add stock to a new warehouse
					$id_wpl = (int)WarehouseProductLocation::getIdByProductAndWarehouse($id_product, $id_product_attribute, $id_warehouse);
					if (!$id_wpl)
					{
						$wpl = new WarehouseProductLocation();
						$wpl->id_product = (int)$id_product;
						$wpl->id_product_attribute = (int)$id_product_attribute;
						$wpl->id_warehouse = (int)$id_warehouse;
						$wpl->save();
					}

					StockAvailable::synchronize($id_product);

					if (Tools::isSubmit('addstockAndStay'))
					{
						$redirect = self::$currentIndex.'&id_product='.(int)$id_product;
						if ($id_product_attribute)
							$redirect .= '&id_product_attribute='.(int)$id_product_attribute;
						$redirect .= '&addstock&token='.$token;
					}
					Tools::redirectAdmin($redirect.'&conf=1');
				}
				else
					$this->errors[] = Tools::displayError('An error occurred. No stock was added.');
			}
		}

		if (Tools::isSubmit('removestock') && Tools::isSubmit('is_post'))
		{
			// if all is ok, remove stock
			if (count($this->errors) == 0)
			{
				$warehouse = new Warehouse($id_warehouse);

				// remove stock
				$stock_manager = StockManagerFactory::getManager();
				$removed_products = $stock_manager->removeProduct($id_product, $id_product_attribute, $warehouse, $quantity, $id_stock_mvt_reason, $usable);

				if (count($removed_products) > 0)
				{
					StockAvailable::synchronize($id_product);
					Tools::redirectAdmin($redirect.'&conf=2');
				}
				else
				{
					$physical_quantity_in_stock = (int)$stock_manager->getProductPhysicalQuantities($id_product, $id_product_attribute, array($warehouse->id), false);
					$usable_quantity_in_stock = (int)$stock_manager->getProductPhysicalQuantities($id_product, $id_product_attribute, array($warehouse->id), true);
					$not_usable_quantity = ($physical_quantity_in_stock - $usable_quantity_in_stock);
					if ($usable_quantity_in_stock < $quantity)
						$this->errors[] = sprintf(Tools::displayError('You don\'t have enough usable quantity. Cannot remove %d items out of %d.'), (int)$quantity, (int)$usable_quantity_in_stock);
					elseif ($not_usable_quantity < $quantity)
						$this->errors[] = sprintf(Tools::displayError('You don\'t have enough usable quantity. Cannot remove %d items out of %d.'), (int)$quantity, (int)$not_usable_quantity);
					else
						$this->errors[] = Tools::displayError('It is not possible to remove the specified quantity. Therefore no stock was removed.');
				}
			}
		}

		if (Tools::isSubmit('transferstock') && Tools::isSubmit('is_post'))
		{
			// get source warehouse id
			$id_warehouse_from = (int)Tools::getValue('id_warehouse_from', 0);
			if ($id_warehouse_from <= 0 || !Warehouse::exists($id_warehouse_from))
				$this->errors[] = Tools::displayError('The source warehouse is not valid.');

			// get destination warehouse id
			$id_warehouse_to = (int)Tools::getValue('id_warehouse_to', 0);
			if ($id_warehouse_to <= 0 || !Warehouse::exists($id_warehouse_to))
				$this->errors[] = Tools::displayError('The destination warehouse is not valid.');

			// get usable flag for source warehouse
			$usable_from = Tools::getValue('usable_from', null);
			if (is_null($usable_from))
				$this->errors[] = Tools::displayError('You have to specify whether the product quantity in your source warehouse(s) is ready for sale or not.');
			$usable_from = (bool)$usable_from;

			// get usable flag for destination warehouse
			$usable_to = Tools::getValue('usable_to', null);
			if (is_null($usable_to))
				$this->errors[] = Tools::displayError('You have to specify whether the product quantity in your destination warehouse(s) is ready for sale or not.');
			$usable_to = (bool)$usable_to;

			// if we can process stock transfers
			if (count($this->errors) == 0)
			{
				// transfer stock
				$stock_manager = StockManagerFactory::getManager();

				$is_transfer = $stock_manager->transferBetweenWarehouses(
					$id_product,
					$id_product_attribute,
					$quantity,
					$id_warehouse_from,
					$id_warehouse_to,
					$usable_from,
					$usable_to
				);
				StockAvailable::synchronize($id_product);
				if ($is_transfer)
					Tools::redirectAdmin($redirect.'&conf=3');
				else
					$this->errors[] = Tools::displayError('It is not possible to transfer the specified quantity. No stock was transferred.');
			}
		}
	}
}
