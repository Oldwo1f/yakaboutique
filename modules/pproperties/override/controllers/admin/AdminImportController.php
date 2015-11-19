<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminImportController extends AdminImportControllerCore
{

	public function __construct()
	{
		parent::__construct();
		switch ((int)Tools::getValue('entity'))
		{
			case $this->entities[$this->l('Products')]:
				$this->available_fields = array_merge($this->available_fields, array(
					'id_pp_template' => array('label' => $this->l('Properties template ID'))
				));
			break;
		}
	}

	public static function ignoreRow($row)
	{
		// skip empty lines
		if (count($row) == 1 && empty($row[0])) return true;
		return ((isset($row['id']) && is_string($row['id']) && Tools::strtoupper($row['id']) == 'ID')
				|| (isset($row['id_product']) && is_string($row['id_product']) && Tools::strtoupper($row['id_product']) == 'PRODUCT ID*'));
	}

	public static function getMaskedRow($row)
	{
		return array_map('trim', parent::getMaskedRow($row));
	}

	public function categoryImport()
	{
		$cat_moved = array();

		$this->receiveTab();
		$handle = $this->openCsvFile();
		$default_language_id = (int)Configuration::get('PS_LANG_DEFAULT');
		$id_lang = Language::getIdByIso(Tools::getValue('iso_lang'));
		if (!Validate::isUnsignedId($id_lang))
			$id_lang = $default_language_id;
		AdminImportController::setLocale();
		for ($current_line = 0; $line = fgetcsv($handle, MAX_LINE_SIZE, $this->separator); $current_line++)
		{
			if (Tools::getValue('convert'))
				$line = $this->utf8EncodeArray($line);
			$info = AdminImportController::getMaskedRow($line);
			if (self::ignoreRow($info))
				continue;

			$tab_categ = array(Configuration::get('PS_HOME_CATEGORY'), Configuration::get('PS_ROOT_CATEGORY'));
			if (isset($info['id']) && in_array((int)$info['id'], $tab_categ))
			{
				$this->errors[] = Tools::displayError('The category ID cannot be the same as the Root category ID or the Home category ID.');
				continue;
			}
			AdminImportController::setDefaultValues($info);

			if (Tools::getValue('forceIDs') && isset($info['id']) && (int)$info['id'])
				$category = new Category((int)$info['id']);
			else
			{
				if (isset($info['id']) && (int)$info['id'] && Category::existsInDatabase((int)$info['id'], 'category'))
					$category = new Category((int)$info['id']);
				else
					if (isset($info['id']) && is_string($info['id']))
					{
						$cat = Category::searchByName($default_language_id, $info['id'], true);
						if ($cat['id_category'])
							$category = $cat;
						else
							$category = new Category();
					}
					else
						$category = new Category();
			}

			AdminImportController::arrayWalk($info, array('AdminImportController', 'fillInfo'), $category);

			if (isset($category->parent) && is_numeric($category->parent))
			{
				if (isset($cat_moved[$category->parent]))
					$category->parent = $cat_moved[$category->parent];
				$category->id_parent = $category->parent;
			}
			else if (isset($category->parent) && is_string($category->parent))
			{
				$category_parent = Category::searchByName($id_lang, $category->parent, true);
				if ($category_parent['id_category'])
				{
					$category->id_parent = (int)$category_parent['id_category'];
					$category->level_depth = (int)$category_parent['level_depth'] + 1;
				}
				else
				{
					$category_to_create = new Category();
					$category_to_create->name = AdminImportController::createMultiLangField($category->parent);
					$category_to_create->active = 1;
					$category_link_rewrite = Tools::link_rewrite($category_to_create->name[$id_lang]);
					$category_to_create->link_rewrite = AdminImportController::createMultiLangField($category_link_rewrite);
					$category_to_create->id_parent = Configuration::get('PS_HOME_CATEGORY'); // Default parent is home for unknown category to create
					if (($field_error = $category_to_create->validateFields(UNFRIENDLY_ERROR, true)) === true &&
						($lang_field_error = $category_to_create->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true && $category_to_create->add())
						$category->id_parent = $category_to_create->id;
					else
					{
						$this->errors[] = sprintf(
							Tools::displayError('%1$s (ID: %2$s) cannot be saved'),
							$category_to_create->name[$id_lang],
							(isset($category_to_create->id) && !empty($category_to_create->id))? $category_to_create->id : 'null'
						);
						$this->errors[] = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '').
							Db::getInstance()->getMsgError();
					}
				}
			}
			if (isset($category->link_rewrite) && !empty($category->link_rewrite[$default_language_id]))
				$valid_link = Validate::isLinkRewrite($category->link_rewrite[$default_language_id]);
			else
				$valid_link = false;

			if (!Shop::isFeatureActive())
				$category->id_shop_default = 1;
			else
				$category->id_shop_default = (int)Context::getContext()->shop->id;

			$bak = $category->link_rewrite[$default_language_id];
			if ((isset($category->link_rewrite) && empty($category->link_rewrite[$default_language_id])) || !$valid_link)
			{
				$category->link_rewrite = Tools::link_rewrite($category->name[$default_language_id]);
				if ($category->link_rewrite == '')
				{
					$category->link_rewrite = 'friendly-url-autogeneration-failed';
					$this->warnings[] = sprintf(Tools::displayError('URL rewriting failed to auto-generate a friendly URL for: %s'), $category->name[$default_language_id]);
				}
				$category->link_rewrite = AdminImportController::createMultiLangField($category->link_rewrite);
			}

			if (!$valid_link)
				$this->warnings[] = sprintf(
					Tools::displayError('Rewrite link for %1$s (ID: %2$s) was re-written as %3$s.'),
					$bak,
					(isset($info['id']) && !empty($info['id']))? $info['id'] : 'null',
					$category->link_rewrite[$default_language_id]
				);
			$res = false;
			if (($field_error = $category->validateFields(UNFRIENDLY_ERROR, true)) === true &&
				($lang_field_error = $category->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true && empty($this->errors))
			{
				$category_already_created = Category::searchByNameAndParentCategoryId(
					$id_lang,
					$category->name[$id_lang],
					$category->id_parent
				);

				// If category already in base, get id category back
				if ($category_already_created['id_category'])
				{
					$cat_moved[$category->id] = (int)$category_already_created['id_category'];
					$category->id = (int)$category_already_created['id_category'];
					if (Validate::isDate($category_already_created['date_add']))
						$category->date_add = $category_already_created['date_add'];
				}

				if ($category->id && $category->id == $category->id_parent)
				{
					$this->errors[] = Tools::displayError('A category cannot be its own parent');
					continue;
				}

				/* No automatic nTree regeneration for import */
				$category->doNotRegenerateNTree = true;

				// If id category AND id category already in base, trying to update
				$categories_home_root = array(Configuration::get('PS_ROOT_CATEGORY'), Configuration::get('PS_HOME_CATEGORY'));
				if ($category->id && $category->categoryExists($category->id) && !in_array($category->id, $categories_home_root))
					$res = $category->update();
				if ($category->id == Configuration::get('PS_ROOT_CATEGORY'))
					$this->errors[] = Tools::displayError('The root category cannot be modified.');
				// If no id_category or update failed
				$category->force_id = (bool)Tools::getValue('forceIDs');
				if (!$res)
					$res = $category->add();
			}
			//copying images of categories
			if (isset($category->image) && !empty($category->image))
				if (!(AdminImportController::copyImg($category->id, null, $category->image, 'categories', !Tools::getValue('regenerate'))))
					$this->warnings[] = $category->image.' '.Tools::displayError('cannot be copied.');
			// If both failed, mysql error
			if (!$res)
			{
				$this->errors[] = sprintf(
					Tools::displayError('%1$s (ID: %2$s) cannot be saved'),
					(isset($info['name']) && !empty($info['name']))? Tools::safeOutput($info['name']) : 'No Name',
					(isset($info['id']) && !empty($info['id']))? Tools::safeOutput($info['id']) : 'No ID'
				);
				$error_tmp = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '').Db::getInstance()->getMsgError();
				if ($error_tmp != '')
					$this->errors[] = $error_tmp;
			}
			else
			{
				// Associate category to shop
				if (Shop::isFeatureActive())
				{
					Db::getInstance()->execute('
						DELETE FROM '._DB_PREFIX_.'category_shop
						WHERE id_category = '.(int)$category->id
					);

					if (!Shop::isFeatureActive())
						$info['shop'] = 1;
					elseif (!isset($info['shop']) || empty($info['shop']))
						$info['shop'] = implode($this->multiple_value_separator, Shop::getContextListShopID());

					// Get shops for each attributes
					$info['shop'] = explode($this->multiple_value_separator, $info['shop']);

					foreach ($info['shop'] as $shop)
						if (!empty($shop) && !is_numeric($shop))
							$category->addShop(Shop::getIdByName($shop));
						elseif (!empty($shop))
							$category->addShop($shop);
				}
			}
		}

		/* Import has finished, we can regenerate the categories nested tree */
		Category::regenerateEntireNtree();

		$this->closeCsvFile($handle);
	}

	public function productImport()
	{
		$this->receiveTab();
		$handle = $this->openCsvFile();
		$default_language_id = (int)Configuration::get('PS_LANG_DEFAULT');
		$id_lang = Language::getIdByIso(Tools::getValue('iso_lang'));
		if (!Validate::isUnsignedId($id_lang))
			$id_lang = $default_language_id;
		AdminImportController::setLocale();
		$shop_ids = Shop::getCompleteListOfShopsID();
		for ($current_line = 0; $line = fgetcsv($handle, MAX_LINE_SIZE, $this->separator); $current_line++)
		{
			if (Tools::getValue('convert'))
				$line = $this->utf8EncodeArray($line);
			$info = AdminImportController::getMaskedRow($line);
			if (self::ignoreRow($info))
				continue;

			if (Tools::getValue('forceIDs') && isset($info['id']) && (int)$info['id'])
				$product = new Product((int)$info['id']);
			elseif (Tools::getValue('match_ref') && array_key_exists('reference', $info))
			{
					$datas = Db::getInstance()->getRow('
						SELECT p.`id_product`
						FROM `'._DB_PREFIX_.'product` p
						'.Shop::addSqlAssociation('product', 'p').'
						WHERE p.`reference` = "'.pSQL($info['reference']).'"
					');
					if (isset($datas['id_product']) && $datas['id_product'])
						$product = new Product((int)$datas['id_product']);
					else
						$product = new Product();
			}
			else
			{
				if (array_key_exists('id', $info) && is_string($info['id']))
				{
					$prod = self::findProductByName($default_language_id, $info['id'], $info['name']);
					if ($prod['id_product'])
						$info['id'] = (int)$prod['id_product'];
				}
				if (array_key_exists('id', $info) && (int)$info['id'] && Product::existsInDatabase((int)$info['id'], 'product'))
				{
					$product = new Product((int)$info['id']);
					$product->loadStockData();
					$category_data = Product::getProductCategories((int)$product->id);

					if (is_array($category_data))
						foreach ($category_data as $tmp)
							if (!isset($product->category) || !$product->category || is_array($product->category))
								$product->category[] = $tmp;
				}
				else
					$product = new Product();
			}

			AdminImportController::setEntityDefaultValues($product);
			AdminImportController::arrayWalk($info, array('AdminImportController', 'fillInfo'), $product);

			if (!Shop::isFeatureActive())
				$product->shop = 1;
			elseif (!isset($product->shop) || empty($product->shop))
				$product->shop = implode($this->multiple_value_separator, Shop::getContextListShopID());

			if (!Shop::isFeatureActive())
				$product->id_shop_default = 1;
			else
				$product->id_shop_default = (int)Context::getContext()->shop->id;

			// link product to shops
			$product->id_shop_list = array();
			foreach (explode($this->multiple_value_separator, $product->shop) as $shop)
				if (!empty($shop) && !is_numeric($shop))
					$product->id_shop_list[] = Shop::getIdByName($shop);
				elseif (!empty($shop))
					$product->id_shop_list[] = $shop;

			if ((int)$product->id_tax_rules_group != 0)
			{
				if (Validate::isLoadedObject(new TaxRulesGroup($product->id_tax_rules_group)))
				{
					$address = $this->context->shop->getAddress();
					$tax_manager = TaxManagerFactory::getManager($address, $product->id_tax_rules_group);
					$product_tax_calculator = $tax_manager->getTaxCalculator();
					$product->tax_rate = $product_tax_calculator->getTotalRate();
				}
				else
					$this->addProductWarning(
						'id_tax_rules_group',
						$product->id_tax_rules_group,
						Tools::displayError('Invalid tax rule group ID. You first need to create a group with this ID.')
					);
			}
			if (isset($product->manufacturer) && is_numeric($product->manufacturer) && Manufacturer::manufacturerExists((int)$product->manufacturer))
				$product->id_manufacturer = (int)$product->manufacturer;
			elseif (isset($product->manufacturer) && is_string($product->manufacturer) && !empty($product->manufacturer))
			{
				if ($manufacturer = Manufacturer::getIdByName($product->manufacturer))
					$product->id_manufacturer = (int)$manufacturer;
				else
				{
					$manufacturer = new Manufacturer();
					$manufacturer->name = $product->manufacturer;
					if (($field_error = $manufacturer->validateFields(UNFRIENDLY_ERROR, true)) === true &&
						($lang_field_error = $manufacturer->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true && $manufacturer->add())
						$product->id_manufacturer = (int)$manufacturer->id;
					else
					{
						$this->errors[] = sprintf(
							Tools::displayError('%1$s (ID: %2$s) cannot be saved'),
							$manufacturer->name,
							(isset($manufacturer->id) && !empty($manufacturer->id))? $manufacturer->id : 'null'
						);
						$this->errors[] = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '').
							Db::getInstance()->getMsgError();
					}
				}
			}

			if (isset($product->supplier) && is_numeric($product->supplier) && Supplier::supplierExists((int)$product->supplier))
				$product->id_supplier = (int)$product->supplier;
			elseif (isset($product->supplier) && is_string($product->supplier) && !empty($product->supplier))
			{
				if ($supplier = Supplier::getIdByName($product->supplier))
					$product->id_supplier = (int)$supplier;
				else
				{
					$supplier = new Supplier();
					$supplier->name = $product->supplier;
					$supplier->active = true;

					if (($field_error = $supplier->validateFields(UNFRIENDLY_ERROR, true)) === true &&
						($lang_field_error = $supplier->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true && $supplier->add())
					{
						$product->id_supplier = (int)$supplier->id;
						$supplier->associateTo($product->id_shop_list);
					}
					else
					{
						$this->errors[] = sprintf(
							Tools::displayError('%1$s (ID: %2$s) cannot be saved'),
							$supplier->name,
							(isset($supplier->id) && !empty($supplier->id))? $supplier->id : 'null'
						);
						$this->errors[] = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '').
							Db::getInstance()->getMsgError();
					}
				}
			}

			if (isset($product->price_tex) && !isset($product->price_tin))
				$product->price = $product->price_tex;
			elseif (isset($product->price_tin) && !isset($product->price_tex))
			{
				$product->price = $product->price_tin;
				// If a tax is already included in price, withdraw it from price
				if ($product->tax_rate)
					$product->price = (float)number_format($product->price / (1 + $product->tax_rate / 100), 6, '.', '');
			}
			elseif (isset($product->price_tin) && isset($product->price_tex))
				$product->price = $product->price_tex;

			if (!Configuration::get('PS_USE_ECOTAX'))
				$product->ecotax = 0;
			$properties = $product->productProperties();
			if ((float)$properties['pp_unit_price_ratio'] > 0)
				$product->unit_price = (float)$product->price / (float)$properties['pp_unit_price_ratio'];
			if (isset($product->category) && is_array($product->category) && count($product->category))
			{
				$product->id_category = array(); // Reset default values array
				foreach ($product->category as $value)
				{
					if (is_numeric($value))
					{
						if (Category::categoryExists((int)$value))
							$product->id_category[] = (int)$value;
						else
						{
							$category_to_create = new Category();
							$category_to_create->id = (int)$value;
							$category_to_create->name = AdminImportController::createMultiLangField($value);
							$category_to_create->active = 1;
							$category_to_create->id_parent = Configuration::get('PS_HOME_CATEGORY'); // Default parent is home for unknown category to create
							$category_link_rewrite = Tools::link_rewrite($category_to_create->name[$default_language_id]);
							$category_to_create->link_rewrite = AdminImportController::createMultiLangField($category_link_rewrite);
							if (($field_error = $category_to_create->validateFields(UNFRIENDLY_ERROR, true)) === true &&
								($lang_field_error = $category_to_create->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true && $category_to_create->add())
								$product->id_category[] = (int)$category_to_create->id;
							else
							{
								$this->errors[] = sprintf(
									Tools::displayError('%1$s (ID: %2$s) cannot be saved'),
									$category_to_create->name[$default_language_id],
									(isset($category_to_create->id) && !empty($category_to_create->id))? $category_to_create->id : 'null'
								);
								$this->errors[] = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '').
									Db::getInstance()->getMsgError();
							}
						}
					}
					elseif (is_string($value) && !empty($value))
					{
						$category = Category::searchByPath($default_language_id, trim($value), $this, 'productImportCreateCat');
						if ($category['id_category'])
							$product->id_category[] = (int)$category['id_category'];
						else
							$this->errors[] = sprintf(Tools::displayError('%1$s cannot be saved'), trim($value));
					}
				}
				$product->id_category = array_values(array_unique($product->id_category));
			}

			if (!isset($product->id_category_default) || !$product->id_category_default)
				$product->id_category_default = isset($product->id_category[0]) ? (int)$product->id_category[0] : (int)Configuration::get('PS_HOME_CATEGORY');

			$link_rewrite = (is_array($product->link_rewrite) && isset($product->link_rewrite[$id_lang])) ? trim($product->link_rewrite[$id_lang]) : '';
			$valid_link = Validate::isLinkRewrite($link_rewrite);

			if ((isset($product->link_rewrite[$id_lang]) && empty($product->link_rewrite[$id_lang])) || !$valid_link)
			{
				$link_rewrite = Tools::link_rewrite($product->name[$id_lang]);
				if ($link_rewrite == '')
					$link_rewrite = 'friendly-url-autogeneration-failed';
			}

			if (!$valid_link)
				$this->warnings[] = sprintf(
					Tools::displayError('Rewrite link for %1$s (ID: %2$s) was re-written as %3$s.'),
					$product->name[$id_lang],
					(isset($info['id']) && !empty($info['id']))? $info['id'] : 'null',
					$link_rewrite
				);

			if (!(Tools::getValue('match_ref') || Tools::getValue('forceIDs')) || !(is_array($product->link_rewrite) && count($product->link_rewrite) && !empty($product->link_rewrite[$id_lang])))
				$product->link_rewrite = AdminImportController::createMultiLangField($link_rewrite);

			// replace the value of separator by coma
			if ($this->multiple_value_separator != ',')
				if (is_array($product->meta_keywords))
					foreach ($product->meta_keywords as &$meta_keyword)
						if (!empty($meta_keyword))
							$meta_keyword = str_replace($this->multiple_value_separator, ',', $meta_keyword);

			// Convert comma into dot for all floating values
			foreach (Product::$definition['fields'] as $key => $array)
				if ($array['type'] == Product::TYPE_FLOAT)
					$product->{$key} = str_replace(',', '.', $product->{$key});

			// Indexation is already 0 if it's a new product, but not if it's an update
			$product->indexed = 0;

			$res = false;
			$field_error = $product->validateFields(UNFRIENDLY_ERROR, true);
			$lang_field_error = $product->validateFieldsLang(UNFRIENDLY_ERROR, true);
			if ($field_error === true && $lang_field_error === true)
			{
				// check quantity
				if ($product->quantity == null)
					$product->quantity = 0;

				// If match ref is specified && ref product && ref product already in base, trying to update
				if (Tools::getValue('match_ref') && $product->reference && $product->existsRefInDatabase($product->reference))
				{
					$datas = Db::getInstance()->getRow('
						SELECT product_shop.`date_add`, p.`id_product`
						FROM `'._DB_PREFIX_.'product` p
						'.Shop::addSqlAssociation('product', 'p').'
						WHERE p.`reference` = "'.pSQL($product->reference).'"
					');
					$product->id = (int)$datas['id_product'];
					$product->date_add = pSQL($datas['date_add']);
					$res = $product->update();
				} // Else If id product && id product already in base, trying to update
				elseif ($product->id && Product::existsInDatabase((int)$product->id, 'product'))
				{
					$datas = Db::getInstance()->getRow('
						SELECT product_shop.`date_add`
						FROM `'._DB_PREFIX_.'product` p
						'.Shop::addSqlAssociation('product', 'p').'
						WHERE p.`id_product` = '.(int)$product->id);
					$product->date_add = pSQL($datas['date_add']);
					$res = $product->update();
				}
				// If no id_product or update failed
				$product->force_id = (bool)Tools::getValue('forceIDs');

				if (!$res)
				{
					if (isset($product->date_add) && $product->date_add != '')
						$res = $product->add(false);
					else
						$res = $product->add();
				}

				if ($product->getType() == Product::PTYPE_VIRTUAL)
					StockAvailable::setProductOutOfStock((int)$product->id, 1);
				else
					StockAvailable::setProductOutOfStock((int)$product->id, (int)$product->out_of_stock);

			}

			$shops = array();
			$product_shop = explode($this->multiple_value_separator, $product->shop);
			foreach ($product_shop as $shop)
			{
				if (empty($shop))
					continue;
				$shop = trim($shop);
				if (!empty($shop) && !is_numeric($shop))
					$shop = Shop::getIdByName($shop);

				if (in_array($shop, $shop_ids))
					$shops[] = $shop;
				else
					$this->addProductWarning(Tools::safeOutput($info['name']), $product->id, $this->l('Shop is not valid'));
			}
			if (empty($shops))
				$shops = Shop::getContextListShopID();
			// If both failed, mysql error
			if (!$res)
			{
				$this->errors[] = sprintf(
					Tools::displayError('%1$s (ID: %2$s) cannot be saved'),
					(isset($info['name']) && !empty($info['name']))? Tools::safeOutput($info['name']) : 'No Name',
					(isset($info['id']) && !empty($info['id']))? Tools::safeOutput($info['id']) : 'No ID'
				);
				$this->errors[] = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '').
					Db::getInstance()->getMsgError();

			}
			else
			{
				// Product supplier
				if (isset($product->id) && $product->id && isset($product->id_supplier) && property_exists($product, 'supplier_reference'))
				{
					$id_product_supplier = (int)ProductSupplier::getIdByProductAndSupplier((int)$product->id, 0, (int)$product->id_supplier);
					if ($id_product_supplier)
						$product_supplier = new ProductSupplier($id_product_supplier);
					else
						$product_supplier = new ProductSupplier();

					$product_supplier->id_product = (int)$product->id;
					$product_supplier->id_product_attribute = 0;
					$product_supplier->id_supplier = (int)$product->id_supplier;
					$product_supplier->product_supplier_price_te = $product->wholesale_price;
					$product_supplier->product_supplier_reference = $product->supplier_reference;
					$product_supplier->save();
				}

				// SpecificPrice (only the basic reduction feature is supported by the import)
				if (!Shop::isFeatureActive())
					$info['shop'] = 1;
				elseif (!isset($info['shop']) || empty($info['shop']))
					$info['shop'] = implode($this->multiple_value_separator, Shop::getContextListShopID());

				// Get shops for each attributes
				$info['shop'] = explode($this->multiple_value_separator, $info['shop']);

				$id_shop_list = array();
				foreach ($info['shop'] as $shop)
					if (!empty($shop) && !is_numeric($shop))
						$id_shop_list[] = (int)Shop::getIdByName($shop);
					elseif (!empty($shop))
						$id_shop_list[] = $shop;

					if ((isset($info['reduction_price']) && $info['reduction_price'] > 0) || (isset($info['reduction_percent']) && $info['reduction_percent'] > 0))
						foreach ($id_shop_list as $id_shop)
						{
							$specific_price = SpecificPrice::getSpecificPrice($product->id, $id_shop, 0, 0, 0, 1, 0, 0, 0, 0);

						if (is_array($specific_price) && isset($specific_price['id_specific_price']))
								$specific_price = new SpecificPrice((int)$specific_price['id_specific_price']);
							else
								$specific_price = new SpecificPrice();
							$specific_price->id_product = (int)$product->id;
							$specific_price->id_specific_price_rule = 0;
							$specific_price->id_shop = $id_shop;
							$specific_price->id_currency = 0;
							$specific_price->id_country = 0;
							$specific_price->id_group = 0;
							$specific_price->price = -1;
							$specific_price->id_customer = 0;
							$specific_price->from_quantity = 1;
							$specific_price->reduction = (isset($info['reduction_price']) && $info['reduction_price']) ? $info['reduction_price'] : $info['reduction_percent'] / 100;
							$specific_price->reduction_type = (isset($info['reduction_price']) && $info['reduction_price']) ? 'amount' : 'percentage';
							$specific_price->from = (isset($info['reduction_from']) && Validate::isDate($info['reduction_from'])) ? $info['reduction_from'] : '0000-00-00 00:00:00';
							$specific_price->to = (isset($info['reduction_to']) && Validate::isDate($info['reduction_to']))  ? $info['reduction_to'] : '0000-00-00 00:00:00';
							if (!$specific_price->save())
								$this->addProductWarning(Tools::safeOutput($info['name']), $product->id, $this->l('Discount is invalid'));
						}

				if (isset($product->tags) && !empty($product->tags))
				{
					if (isset($product->id) && $product->id)
					{
						$tags = Tag::getProductTags($product->id);
						if (is_array($tags) && count($tags))
						{
							if (!empty($product->tags))
								$product->tags = explode($this->multiple_value_separator, $product->tags);
							if (is_array($product->tags) && count($product->tags))
							{
								foreach ($product->tags as $key => $tag)
									if (!empty($tag))
										$product->tags[$key] = trim($tag);
								$tags[$id_lang] = $product->tags;
								$product->tags = $tags;
							}
						}
					}
					// Delete tags for this id product, for no duplicating error
					Tag::deleteTagsForProduct($product->id);
					if (!is_array($product->tags) && !empty($product->tags))
					{
						$product->tags = AdminImportController::createMultiLangField($product->tags);
						foreach ($product->tags as $key => $tags)
						{
							$is_tag_added = Tag::addTags($key, $product->id, $tags, $this->multiple_value_separator);
							if (!$is_tag_added)
							{
								$this->addProductWarning(Tools::safeOutput($info['name']), $product->id, $this->l('Tags list is invalid'));
								break;
							}
						}
					}
					else
					{
						foreach ($product->tags as $key => $tags)
						{
							$str = '';
							foreach ($tags as $one_tag)
								$str .= $one_tag.$this->multiple_value_separator;
							$str = rtrim($str, $this->multiple_value_separator);

							$is_tag_added = Tag::addTags($key, $product->id, $str, $this->multiple_value_separator);
							if (!$is_tag_added)
							{
								$this->addProductWarning(Tools::safeOutput($info['name']), (int)$product->id, 'Invalid tag(s) ('.$str.')');
								break;
							}
						}
					}
				}

				//delete existing images if "delete_existing_images" is set to 1
				if (isset($product->delete_existing_images))
					if ((bool)$product->delete_existing_images)
						$product->deleteImages();

				if (isset($product->image) && is_array($product->image) && count($product->image))
				{
					$product_has_images = (bool)Image::getImages($this->context->language->id, (int)$product->id);
					foreach ($product->image as $key => $url)
					{
						$url = trim($url);
						$error = false;
						if (!empty($url))
						{
							$url = str_replace(' ', '%20', $url);

							$image = new Image();
							$image->id_product = (int)$product->id;
							$image->position = Image::getHighestPosition($product->id) + 1;
							$image->cover = (!$key && !$product_has_images) ? true : false;
							// file_exists doesn't work with HTTP protocol
							if (($field_error = $image->validateFields(UNFRIENDLY_ERROR, true)) === true &&
								($lang_field_error = $image->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true && $image->add())
							{
								// associate image to selected shops
								$image->associateTo($shops);
								if (!AdminImportController::copyImg($product->id, $image->id, $url, 'products', !Tools::getValue('regenerate')))
								{
									$image->delete();
									$this->warnings[] = sprintf(Tools::displayError('Error copying image: %s'), $url);
								}
							}
							else
								$error = true;
						}
						else
							$error = true;

						if ($error)
							$this->warnings[] = sprintf(Tools::displayError('Product #%1$d: the picture (%2$s) cannot be saved.'), $image->id_product, $url);
					}
				}

				if (isset($product->id_category) && is_array($product->id_category))
					$product->updateCategories(array_map('intval', $product->id_category));

				$product->checkDefaultAttributes();
				if (!$product->cache_default_attribute)
					Product::updateDefaultAttribute($product->id);

				// Features import
				$features = get_object_vars($product);

				if (isset($features['features']) && !empty($features['features']))
					foreach (explode($this->multiple_value_separator, $features['features']) as $single_feature)
					{
						if (empty($single_feature))
							continue;
						$tab_feature = explode(':', $single_feature);
						$feature_name = isset($tab_feature[0]) ? trim($tab_feature[0]) : '';
						$feature_value = isset($tab_feature[1]) ? trim($tab_feature[1]) : '';
						$position = isset($tab_feature[2]) ? (int)$tab_feature[2] - 1 : false;
						$custom = isset($tab_feature[3]) ? (int)$tab_feature[3] : false;
						if (!empty($feature_name) && !empty($feature_value))
						{
							$id_feature = (int)Feature::addFeatureImport($feature_name, $position);
							$id_product = null;
							if (Tools::getValue('forceIDs') || Tools::getValue('match_ref'))
								$id_product = (int)$product->id;
							$id_feature_value = (int)FeatureValue::addFeatureValueImport($id_feature, $feature_value, $id_product, $id_lang, $custom);
							Product::addFeatureProductImport($product->id, $id_feature, $id_feature_value);
						}
					}
				// clean feature positions to avoid conflict
				Feature::cleanPositions();

				// set advanced stock managment
				if (isset($product->advanced_stock_management))
				{
					if ($product->advanced_stock_management != 1 && $product->advanced_stock_management != 0)
						$this->warnings[] = sprintf(Tools::displayError('Advanced stock management has incorrect value. Not set for product %1$s '), $product->name[$default_language_id]);
					elseif (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $product->advanced_stock_management == 1)
						$this->warnings[] = sprintf(Tools::displayError('Advanced stock management is not enabled, cannot enable on product %1$s '), $product->name[$default_language_id]);
					else
						$product->setAdvancedStockManagement($product->advanced_stock_management);
					// automaticly disable depends on stock, if a_s_m set to disabled
					if (StockAvailable::dependsOnStock($product->id) == 1 && $product->advanced_stock_management == 0)
						StockAvailable::setProductDependsOnStock($product->id, 0);
				}

				// Check if warehouse exists
				if (isset($product->warehouse) && $product->warehouse)
				{
					if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'))
						$this->warnings[] = sprintf(Tools::displayError('Advanced stock management is not enabled, warehouse not set on product %1$s '), $product->name[$default_language_id]);
					else
					{
						if (Warehouse::exists($product->warehouse))
						{
							// Get already associated warehouses
							$associated_warehouses_collection = WarehouseProductLocation::getCollection($product->id);
							// Delete any entry in warehouse for this product
							foreach ($associated_warehouses_collection as $awc)
								$awc->delete();
							$warehouse_location_entity = new WarehouseProductLocation();
							$warehouse_location_entity->id_product = $product->id;
							$warehouse_location_entity->id_product_attribute = 0;
							$warehouse_location_entity->id_warehouse = $product->warehouse;
								if (WarehouseProductLocation::getProductLocation($product->id, 0, $product->warehouse) !== false)
									$warehouse_location_entity->update();
								else
									$warehouse_location_entity->save();
							StockAvailable::synchronize($product->id);
						}
						else
							$this->warnings[] = sprintf(Tools::displayError('Warehouse did not exist, cannot set on product %1$s.'), $product->name[$default_language_id]);
					}
				}

				// stock available
				if (isset($product->depends_on_stock))
				{
					if ($product->depends_on_stock != 0 && $product->depends_on_stock != 1)
						$this->warnings[] = sprintf(Tools::displayError('Incorrect value for "depends on stock" for product %1$s '), $product->name[$default_language_id]);
					elseif ((!$product->advanced_stock_management || $product->advanced_stock_management == 0) && $product->depends_on_stock == 1)
						$this->warnings[] = sprintf(Tools::displayError('Advanced stock management not enabled, cannot set "depends on stock" for product %1$s '), $product->name[$default_language_id]);
					else
						StockAvailable::setProductDependsOnStock($product->id, $product->depends_on_stock);

					// This code allows us to set qty and disable depends on stock
					if (isset($product->quantity) && $product->quantity)
					{
						// if depends on stock and quantity, add quantity to stock
						if ($product->depends_on_stock == 1)
						{
							$stock_manager = StockManagerFactory::getManager();
							$price = str_replace(',', '.', $product->wholesale_price);
							if ($price == 0)
								$price = 0.000001;
							$price = round((float)$price, 6);
							$warehouse = new Warehouse($product->warehouse);
							if ($stock_manager->addProduct((int)$product->id, 0, $warehouse, $product->quantity, 1, $price, true))
								StockAvailable::synchronize((int)$product->id);
						}
						else
						{
							if (Shop::isFeatureActive())
								foreach ($shops as $shop)
									StockAvailable::setQuantity((int)$product->id, 0, $product->quantity, (int)$shop);
							else
								StockAvailable::setQuantity((int)$product->id, 0, $product->quantity, (int)$this->context->shop->id);
						}
					}
				}
				else // if not depends_on_stock set, use normal qty
				{
					if (Shop::isFeatureActive())
						foreach ($shops as $shop)
							StockAvailable::setQuantity((int)$product->id, 0, $product->quantity, (int)$shop);
					else
						StockAvailable::setQuantity((int)$product->id, 0, $product->quantity, (int)$this->context->shop->id);
				}
			}
		}
		$this->closeCsvFile($handle);
	}

	public function attributeImport()
	{
		$default_language = Configuration::get('PS_LANG_DEFAULT');

		$groups = array();
		foreach (AttributeGroup::getAttributesGroups($default_language) as $group)
			$groups[$group['name']] = (int)$group['id_attribute_group'];

		$attributes = array();
		foreach (Attribute::getAttributes($default_language) as $attribute)
			$attributes[$attribute['attribute_group'].'_'.$attribute['name']] = (int)$attribute['id_attribute'];

		$this->receiveTab();
		$handle = $this->openCsvFile();
		AdminImportController::setLocale();
		for ($current_line = 0; $line = fgetcsv($handle, MAX_LINE_SIZE, $this->separator); $current_line++)
		{
			if (count($line) == 1 && empty($line[0]))
				continue;

			if (Tools::getValue('convert'))
				$line = $this->utf8EncodeArray($line);
			$info = AdminImportController::getMaskedRow($line);
			$info = array_map('trim', $info);
			if (self::ignoreRow($info))
				continue;

			AdminImportController::setDefaultValues($info);

			if (!Shop::isFeatureActive())
				$info['shop'] = 1;
			elseif (!isset($info['shop']) || empty($info['shop']))
				$info['shop'] = implode($this->multiple_value_separator, Shop::getContextListShopID());

			// Get shops for each attributes
			$info['shop'] = explode($this->multiple_value_separator, $info['shop']);

			$id_shop_list = array();
			if (is_array($info['shop']) && count($info['shop']))
				foreach ($info['shop'] as $shop)
					if (!empty($shop) && !is_numeric($shop))
						$id_shop_list[] = Shop::getIdByName($shop);
					elseif (!empty($shop))
						$id_shop_list[] = $shop;

			if (isset($info['id_product']) && is_string($info['id_product']))
			{
				$prod = self::findProductByName($default_language, $info['id_product']);
				if ($prod['id_product'])
					$info['id_product'] = $prod['id_product'];
				else
					unset($info['id_product']);
			}
			if (!isset($info['id_product']) && Tools::getValue('match_ref') && isset($info['product_reference']) && $info['product_reference'])
			{
				$datas = Db::getInstance()->getRow('
					SELECT p.`id_product`
					FROM `'._DB_PREFIX_.'product` p
					'.Shop::addSqlAssociation('product', 'p').'
					WHERE p.`reference` = "'.pSQL($info['product_reference']).'"
				');
				if (isset($datas['id_product']) && $datas['id_product'])
					$info['id_product'] = $datas['id_product'];
			}
			if (isset($info['id_product']))
				$product = new Product((int)$info['id_product'], false, $default_language);
			else
				continue;
			$id_image = array();

			//delete existing images if "delete_existing_images" is set to 1
			if (array_key_exists('delete_existing_images', $info) && $info['delete_existing_images'] && !isset($this->cache_image_deleted[(int)$product->id]))
			{
				$product->deleteImages();
				$this->cache_image_deleted[(int)$product->id] = true;
			}

			if (isset($info['image_url']) && $info['image_url'])
			{
				$info['image_url'] = explode(',', $info['image_url']);

				if (is_array($info['image_url'] ) && count($info['image_url'] ))
					foreach ($info['image_url'] as $url)
					{
						$url = trim($url);
						$product_has_images = (bool)Image::getImages($this->context->language->id, $product->id);

						$image = new Image();
						$image->id_product = (int)$product->id;
						$image->position = Image::getHighestPosition($product->id) + 1;
						$image->cover = (!$product_has_images) ? true : false;

						$field_error = $image->validateFields(UNFRIENDLY_ERROR, true);
						$lang_field_error = $image->validateFieldsLang(UNFRIENDLY_ERROR, true);

						if ($field_error === true && $lang_field_error === true && $image->add())
						{
							$image->associateTo($id_shop_list);
							if (!AdminImportController::copyImg($product->id, $image->id, $url, 'products', !Tools::getValue('regenerate')))
							{
								$this->warnings[] = sprintf(Tools::displayError('Error copying image: %s'), $url);
								$image->delete();
							}
							else
								$id_image[] = (int)$image->id;
						}
						else
						{
							$this->warnings[] = sprintf(
								Tools::displayError('%s cannot be saved'),
								(isset($image->id_product) ? ' ('.$image->id_product.')' : '')
							);
							$this->errors[] = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '').mysql_error();
						}
					}
			}
			elseif (isset($info['image_position']) && $info['image_position'])
			{
				$info['image_position'] = explode(',', $info['image_position']);

				if (is_array($info['image_position'] ) && count($info['image_position'] ))
					foreach ($info['image_position'] as $position)
					{
						// choose images from product by position
						$images = $product->getImages($default_language);

						if ($images)
							foreach ($images as $row)
								if ($row['position'] == (int)$position)
								{
									$id_image[] = (int)$row['id_image'];
									break;
								}
						if (empty($id_image))
							$this->warnings[] = sprintf(
								Tools::displayError('No image was found for combination with id_product = %s and image position = %s.'),
								$product->id,
								(int)$position
							);
					}
			}

			$id_attribute_group = 0;
			// groups
			$groups_attributes = array();
			if (isset($info['group']))
				foreach (explode($this->multiple_value_separator, $info['group']) as $key => $group)
				{
					if (empty($group))
						continue;
					$tab_group = explode(':', $group);
					$group = trim($tab_group[0]);
					if (!isset($tab_group[1]))
						$type = 'select';
					else
						$type = trim($tab_group[1]);

					// sets group
					$groups_attributes[$key]['group'] = $group;

					// if position is filled
					if (isset($tab_group[2]))
						$position = trim($tab_group[2]);
					else
						$position = false;

					if (!isset($groups[$group]))
					{
						$obj = new AttributeGroup();
						$obj->is_color_group = false;
						$obj->group_type = pSQL($type);
						$obj->name[$default_language] = $group;
						$obj->public_name[$default_language] = $group;
						$obj->position = (!$position) ? AttributeGroup::getHigherPosition() + 1 : $position;

						if (($field_error = $obj->validateFields(UNFRIENDLY_ERROR, true)) === true &&
							($lang_field_error = $obj->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true)
						{
							$obj->add();
							$obj->associateTo($id_shop_list);
							$groups[$group] = $obj->id;
						}
						else
							$this->errors[] = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '');

						// fills groups attributes
						$id_attribute_group = $obj->id;
						$groups_attributes[$key]['id'] = $id_attribute_group;
					}
					else // already exists
					{
						$id_attribute_group = $groups[$group];
						$groups_attributes[$key]['id'] = $id_attribute_group;
					}
				}

			// inits attribute
			$id_product_attribute = 0;
			$id_product_attribute_update = false;
			$attributes_to_add = array();

			// for each attribute
			if (isset($info['attribute']))
				foreach (explode($this->multiple_value_separator, $info['attribute']) as $key => $attribute)
				{
					if (empty($attribute))
						continue;
					$tab_attribute = explode(':', $attribute);
					$attribute = trim($tab_attribute[0]);
					// if position is filled
					if (isset($tab_attribute[1]))
						$position = trim($tab_attribute[1]);
					else
						$position = false;

					if (isset($groups_attributes[$key]))
					{
						$group = $groups_attributes[$key]['group'];
						if (!isset($attributes[$group.'_'.$attribute]) && count($groups_attributes[$key]) == 2)
						{
							$id_attribute_group = $groups_attributes[$key]['id'];
							$obj = new Attribute();
							// sets the proper id (corresponding to the right key)
							$obj->id_attribute_group = $groups_attributes[$key]['id'];
							$obj->name[$default_language] = str_replace('\n', '', str_replace('\r', '', $attribute));
							$obj->position = (!$position && isset($groups[$group])) ? Attribute::getHigherPosition($groups[$group]) + 1 : $position;

							if (($field_error = $obj->validateFields(UNFRIENDLY_ERROR, true)) === true &&
								($lang_field_error = $obj->validateFieldsLang(UNFRIENDLY_ERROR, true)) === true)
							{
								$obj->add();
								$obj->associateTo($id_shop_list);
								$attributes[$group.'_'.$attribute] = $obj->id;
							}
							else
								$this->errors[] = ($field_error !== true ? $field_error : '').(isset($lang_field_error) && $lang_field_error !== true ? $lang_field_error : '');
						}

						$info['minimal_quantity'] = isset($info['minimal_quantity']) && $info['minimal_quantity'] ? (int)$info['minimal_quantity'] : 1;

						$info['wholesale_price'] = str_replace(',', '.', $info['wholesale_price']);
						$info['price'] = str_replace(',', '.', $info['price']);
						$info['ecotax'] = str_replace(',', '.', $info['ecotax']);
						$info['weight'] = str_replace(',', '.', $info['weight']);
						$info['available_date'] = Validate::isDate($info['available_date']) ? $info['available_date'] : null;

						if (!Validate::isEan13($info['ean13']))
						{
							$this->warnings[] = sprintf(Tools::displayError('EAN13 "%1s" has incorrect value for product with id %2d.'), $info['ean13'], $product->id);
							$info['ean13'] = '';
						}

						if ($info['default_on'])
							$product->deleteDefaultAttributes();

						// if a reference is specified for this product, get the associate id_product_attribute to UPDATE
						if (isset($info['reference']) && !empty($info['reference']))
						{
							$id_product_attribute = Combination::getIdByReference($product->id, (string)$info['reference']);

							// updates the attribute
							if ($id_product_attribute)
							{
								// gets all the combinations of this product
								$attribute_combinations = $product->getAttributeCombinations($default_language);
								foreach ($attribute_combinations as $attribute_combination)
								{
									if ($id_product_attribute && in_array($id_product_attribute, $attribute_combination))
									{
										$product->updateAttribute(
											$id_product_attribute,
											(float)$info['wholesale_price'],
											(float)$info['price'],
											(float)$info['weight'],
											0,
											(Configuration::get('PS_USE_ECOTAX') ? (float)$info['ecotax'] : 0),
											$id_image,
											(string)$info['reference'],
											(string)$info['ean13'],
											(int)$info['default_on'],
											0,
											(string)$info['upc'],
											(int)$info['minimal_quantity'],
											$info['available_date'],
											null,
											$id_shop_list
										);
										$id_product_attribute_update = true;
										if (isset($info['supplier_reference']) && !empty($info['supplier_reference']))
											$product->addSupplierReference($product->id_supplier, $id_product_attribute, $info['supplier_reference']);
									}
								}
							}
						}

						// if no attribute reference is specified, creates a new one
						if (!$id_product_attribute)
						{
							$id_product_attribute = $product->addCombinationEntity(
								(float)$info['wholesale_price'],
								(float)$info['price'],
								(float)$info['weight'],
								0,
								(Configuration::get('PS_USE_ECOTAX') ? (float)$info['ecotax'] : 0),
								(int)$info['quantity'],
								$id_image,
								(string)$info['reference'],
								0,
								(string)$info['ean13'],
								(int)$info['default_on'],
								0,
								(string)$info['upc'],
								(int)$info['minimal_quantity'],
								$id_shop_list,
								$info['available_date']
							);

							if (isset($info['supplier_reference']) && !empty($info['supplier_reference']))
								$product->addSupplierReference($product->id_supplier, $id_product_attribute, $info['supplier_reference']);
						}

						// fills our attributes array, in order to add the attributes to the product_attribute afterwards
						if (isset($attributes[$group.'_'.$attribute]))
							$attributes_to_add[] = (int)$attributes[$group.'_'.$attribute];

						// after insertion, we clean attribute position and group attribute position
						$obj = new Attribute();
						$obj->cleanPositions((int)$id_attribute_group, false);
						AttributeGroup::cleanPositions();
					}
				}

			$product->checkDefaultAttributes();
			if (!$product->cache_default_attribute)
				Product::updateDefaultAttribute($product->id);
			if ($id_product_attribute)
			{
				// now adds the attributes in the attribute_combination table
				if ($id_product_attribute_update)
				{
					Db::getInstance()->execute('
						DELETE FROM '._DB_PREFIX_.'product_attribute_combination
						WHERE id_product_attribute = '.(int)$id_product_attribute);
				}

				foreach ($attributes_to_add as $attribute_to_add)
				{
					Db::getInstance()->execute('
						INSERT IGNORE INTO '._DB_PREFIX_.'product_attribute_combination (id_attribute, id_product_attribute)
						VALUES ('.(int)$attribute_to_add.','.(int)$id_product_attribute.')');
				}

				// set advanced stock managment
				if (isset($info['advanced_stock_management']))
				{
					if ($info['advanced_stock_management'] != 1 && $info['advanced_stock_management'] != 0)
						$this->warnings[] = sprintf(Tools::displayError('Advanced stock management has incorrect value. Not set for product with id %d.'), $product->id);
					elseif (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $info['advanced_stock_management'] == 1)
						$this->warnings[] = sprintf(Tools::displayError('Advanced stock management is not enabled, cannot enable on product with id %d.'), $product->id);
					else
						$product->setAdvancedStockManagement($info['advanced_stock_management']);
					// automaticly disable depends on stock, if a_s_m set to disabled
					if (StockAvailable::dependsOnStock($product->id) == 1 && $info['advanced_stock_management'] == 0)
						StockAvailable::setProductDependsOnStock($product->id, 0, null, $id_product_attribute);
				}

				// Check if warehouse exists
				if (isset($info['warehouse']) && $info['warehouse'])
				{
					if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'))
						$this->warnings[] = sprintf(Tools::displayError('Advanced stock management is not enabled, warehouse is not set on product with id %d.'), $product->id);
					else
					{
						if (Warehouse::exists($info['warehouse']))
						{
							$warehouse_location_entity = new WarehouseProductLocation();
							$warehouse_location_entity->id_product = $product->id;
							$warehouse_location_entity->id_product_attribute = $id_product_attribute;
							$warehouse_location_entity->id_warehouse = $info['warehouse'];
							if (WarehouseProductLocation::getProductLocation($product->id, $id_product_attribute, $info['warehouse']) !== false)
								$warehouse_location_entity->update();
							else
								$warehouse_location_entity->save();
							StockAvailable::synchronize($product->id);
						}
						else
							$this->warnings[] = sprintf(Tools::displayError('Warehouse did not exist, cannot set on product %1$s.'), $product->name[$default_language]);
					}
				}

				// stock available
				if (isset($info['depends_on_stock']))
				{
					if ($info['depends_on_stock'] != 0 && $info['depends_on_stock'] != 1)
						$this->warnings[] = sprintf(Tools::displayError('Incorrect value for depends on stock for product %1$s '), $product->name[$default_language]);
					elseif ((!$info['advanced_stock_management'] || $info['advanced_stock_management'] == 0) && $info['depends_on_stock'] == 1)
						$this->warnings[] = sprintf(Tools::displayError('Advanced stock management is not enabled, cannot set depends on stock %1$s '), $product->name[$default_language]);
					else
						StockAvailable::setProductDependsOnStock($product->id, $info['depends_on_stock'], null, $id_product_attribute);

					// This code allows us to set qty and disable depends on stock
					if (isset($info['quantity']) && $info['quantity'])
					{
						// if depends on stock and quantity, add quantity to stock
						if ($info['depends_on_stock'] == 1)
						{
							$stock_manager = StockManagerFactory::getManager();
							$price = str_replace(',', '.', $info['wholesale_price']);
							if ($price == 0)
								$price = 0.000001;
							$price = round((float)$price, 6);
							$warehouse = new Warehouse($info['warehouse']);
							if ($stock_manager->addProduct((int)$product->id, $id_product_attribute, $warehouse, $info['quantity'], 1, $price, true))
								StockAvailable::synchronize((int)$product->id);
						}
						else
						{
							if (Shop::isFeatureActive())
								foreach ($id_shop_list as $shop)
									StockAvailable::setQuantity((int)$product->id, $id_product_attribute, $info['quantity'], (int)$shop);
							else
								StockAvailable::setQuantity((int)$product->id, $id_product_attribute, $info['quantity'], $this->context->shop->id);
						}

					}
				}
				// if not depends_on_stock set, use normal qty
				else
				{
					if (Shop::isFeatureActive())
						foreach ($id_shop_list as $shop)
							StockAvailable::setQuantity((int)$product->id, $id_product_attribute, (int)$info['quantity'], (int)$shop);
					else
						StockAvailable::setQuantity((int)$product->id, $id_product_attribute, (int)$info['quantity'], $this->context->shop->id);
				}

			}
		}

		$this->closeCsvFile($handle);
	}

	private static function findProductByName($id_lang, $name, $second_name = false)
	{
		$product = false;
		if (is_string($name))
		{
			$product = self::searchProductByName($id_lang, $name);
			if ($product === false)
				$product = self::searchProductByName($id_lang, Tools::iconv('UTF-8', 'ISO-8859-1', $name));
		}
		if ($product === false && is_string($second_name))
			$product = self::findProductByName($id_lang, $second_name, false);
		return $product;
	}

	private static function searchProductByName($id_lang, $name)
	{
		return Db::getInstance()->getRow('
		SELECT p.*, pl.*
		FROM `'._DB_PREFIX_.'product` p
		'.Shop::addSqlAssociation('product', 'p').'
		LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` AND `id_lang` = '.(int)($id_lang).')
		WHERE `name`  LIKE \''.pSQL($name).'\'');
	}
}
