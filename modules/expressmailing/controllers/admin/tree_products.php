<?php
/**
 * 2014-2015 (c) Axalone France - Express-Mailing
 *
 * This file is a commercial module for Prestashop
 * Do not edit or add to this file if you wish to upgrade PrestaShop or
 * customize PrestaShop for your needs please refer to
 * http://www.express-mailing.com for more information.
 *
 * @author    Axalone France <info@express-mailing.com>
 * @copyright 2014-2015 (c) Axalone France
 * @license   http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
 */

class HelperTreeCategoriesProducts extends Tree
{
	const DEFAULT_TEMPLATE = 'tree_categories.tpl';
	const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder_radio.tpl';
	const DEFAULT_NODE_ITEM_TEMPLATE = 'tree_node_item_radio.tpl';

	private $my_disabled_categories;
	private $my_input_name;
	private $my_lang;
	private $my_root_category;
	private $my_selected_categories;
	private $my_selected_products;
	private $my_shop;
	private $my_use_checkbox;
	private $my_use_checkbox_toolbar_link;
	private $my_use_search;
	private $my_use_shop_restriction;

	public function __construct($id, $title = null, $root_category = null, $lang = null, $use_shop_restriction = true)
	{
		if (isset($title))
			$this->setTitle($title);

		if (isset($root_category))
			$this->setRootCategory($root_category);

		parent::__construct($id);

		$this->setLang($lang);
		$this->setUseShopRestriction($use_shop_restriction);
	}

	public function getData()
	{
		if (!isset($this->_data))
		{
			$categories = Category::getNestedCategories();
			$products = $this->getProductsDB();
			$categories = $this->populateCategoriesWithProducts($categories, $products);
			$this->setData($categories);
		}
		return $this->_data;
	}

	public function setDisabledCategories($value)
	{
		$this->my_disabled_categories = $value;
		return $this;
	}

	public function getDisabledCategories()
	{
		return $this->my_disabled_categories;
	}

	public function setInputName($value)
	{
		$this->my_input_name = $value;
		return $this;
	}

	public function getInputName()
	{
		if (!isset($this->my_input_name))
			$this->setInputName('tree');

		return $this->my_input_name;
	}

	public function setLang($value)
	{
		$this->my_lang = $value;
		return $this;
	}

	public function getLang()
	{
		if (!isset($this->my_lang))
			$this->setLang($this->getContext()->employee->id_lang);

		return $this->my_lang;
	}

	public function getNodeFolderTemplate()
	{
		if (!isset($this->_node_folder_template))
			$this->setNodeFolderTemplate(self::DEFAULT_NODE_FOLDER_TEMPLATE);

		return $this->_node_folder_template;
	}

	public function getNodeItemTemplate()
	{
		if (!isset($this->_node_item_template))
			$this->setNodeItemTemplate(self::DEFAULT_NODE_ITEM_TEMPLATE);

		return $this->_node_item_template;
	}

	public function setRootCategory($value)
	{
		if (!Validate::isInt($value))
			throw new PrestaShopException('Root category must be an integer value');

		$this->my_root_category = $value;
		return $this;
	}

	public function getRootCategory()
	{
		return $this->my_root_category;
	}

	public function setSelectedCategories($value)
	{
		if (!is_array($value))
			throw new PrestaShopException('Selected categories value must be an array');

		$this->my_selected_categories = $value;
		return $this;
	}

	public function getSelectedCategories()
	{
		if (!isset($this->my_selected_categories))
			$this->my_selected_categories = array();

		return $this->my_selected_categories;
	}

	public function setSelectedProducts($value)
	{
		if (!is_array($value))
			throw new PrestaShopException('Selected products value must be an array');

		$this->my_selected_products = $value;
		return $this;
	}

	public function getSelectedProducts()
	{
		if (!isset($this->my_selected_products))
			$this->my_selected_products = array();

		return $this->my_selected_products;
	}

	public function setShop($value)
	{
		$this->my_shop = $value;
		return $this;
	}

	public function getShop()
	{
		if (!isset($this->my_shop))
		{
			if (Tools::isSubmit('id_shop'))
				$this->setShop(new Shop(Tools::getValue('id_shop')));
			else if ($this->getContext()->shop->id)
				$this->setShop(new Shop($this->getContext()->shop->id));
			else if (!Shop::isFeatureActive())
				$this->setShop(new Shop(Configuration::get('PS_SHOP_DEFAULT')));
			else
				$this->setShop(new Shop(0));
		}

		return $this->my_shop;
	}

	public function getTemplate()
	{
		if (!isset($this->_template))
			$this->setTemplate(self::DEFAULT_TEMPLATE);

		return $this->_template;
	}

	public function setUseCheckBox($value)
	{
		$this->my_use_checkbox = (bool)$value;
		return $this;
	}

	public function setUseCheckBoxToolbarLink($value)
	{
		$this->my_use_checkbox_toolbar_link = (bool)$value;
		return $this;
	}

	public function setUseSearch($value)
	{
		$this->my_use_search = (bool)$value;
		return $this;
	}

	public function setUseShopRestriction($value)
	{
		$this->my_use_shop_restriction = (bool)$value;
		return $this;
	}

	public function useCheckBox()
	{
		return (isset($this->my_use_checkbox) && $this->my_use_checkbox);
	}

	public function useCheckBoxToolbarLink()
	{
		return (isset($this->my_use_checkbox_toolbar_link) && $this->my_use_checkbox_toolbar_link);
	}

	public function useSearch()
	{
		return (isset($this->my_use_search) && $this->my_use_search);
	}

	public function useShopRestriction()
	{
		return (isset($this->my_use_shop_restriction) && $this->my_use_shop_restriction);
	}

	public function render($data = null)
	{
		if (!isset($data))
			$data = $this->getData();

		if (isset($this->my_disabled_categories) && !empty($this->my_disabled_categories))
			$this->disableCategories($data, $this->getDisabledCategories());

		// Default bootstrap style of search is push-right, so we add this button first
		// ----------------------------------------------------------------------------
		if ($this->useSearch())
		{
			$this->addAction(new TreeToolbarSearchCategories(
				'Find a category:', $this->getId().'-categories-search')
			);
			$this->setAttribute('use_search', $this->useSearch());
		}

		$collapse_all = new TreeToolbarLink(
			'Collapse All', '#', '$(\'#'.$this->getId().'\').tree(\'collapseAll\');$(\'#collapse-all-'.$this->getId().
			'\').hide();$(\'#expand-all-'.$this->getId().'\').show(); return false;', 'icon-collapse-alt'
		);
		$collapse_all->setAttribute('id', 'collapse-all-'.$this->getId());
		$expand_all = new TreeToolbarLink(
			'Expand All', '#', '$(\'#'.$this->getId().'\').tree(\'expandAll\');$(\'#collapse-all-'.$this->getId().
			'\').show();$(\'#expand-all-'.$this->getId().'\').hide(); return false;', 'icon-expand-alt'
		);
		$expand_all->setAttribute('id', 'expand-all-'.$this->getId());
		$this->addAction($collapse_all);
		$this->addAction($expand_all);

		if ($this->useCheckBox())
		{
			if ($this->useCheckBoxToolbarLink())
			{
				$check_all = new TreeToolbarLink(
					'Check All', '#', 'checkAllAssociatedCategories($(\'#'.$this->getId().'\')); return false;', 'icon-check-sign');
				$check_all->setAttribute('id', 'check-all-'.$this->getId());
				$uncheck_all = new TreeToolbarLink(
					'Uncheck All', '#', 'uncheckAllAssociatedCategories($(\'#'.$this->getId().'\')); return false;', 'icon-check-empty');
				$uncheck_all->setAttribute('id', 'uncheck-all-'.$this->getId());
				$this->addAction($check_all);
				$this->addAction($uncheck_all);
			}
			$this->setNodeFolderTemplate('tree_node_folder_cat_prod_checkbox.tpl');
			$this->setNodeItemTemplate('tree_node_item_cat_prod_checkbox.tpl');
			$this->setAttribute('use_checkbox', $this->useCheckBox());
		}

		$this->getContext()->smarty->assign('root_category', Configuration::get('PS_ROOT_CATEGORY'));

		return parent::render($data);
	}

	public function renderNodes($data = null)
	{
		if (!isset($data))
			$data = $this->getData();

		if (!is_array($data) && !$data instanceof Traversable)
			throw new PrestaShopException('Data value must be a traversable array');

		$html = '';

		foreach ($data as $item)
		{
			if (array_key_exists('children', $item) && !empty($item['children']))
				$html .= $this->getContext()->smarty->createTemplate(
						$this->getTemplateFile($this->getNodeFolderTemplate()), $this->getContext()->smarty
					)->assign(array (
						'input_name' => $this->getInputName(),
						'children' => $this->renderNodes($item['children']),
						'node' => $item
					))->fetch();
			else
				$html .= $this->getContext()->smarty->createTemplate(
						$this->getTemplateFile($this->getNodeItemTemplate()), $this->getContext()->smarty
					)->assign(array (
						'input_name' => $this->getInputName(),
						'node' => $item
					))->fetch();
		}

		return $html;
	}

	private function getProductsDB()
	{
		$result = array();
		$products_db = Product::getProducts($this->getLang(), 0, 0, 'id_product', 'ASC');

		foreach ($products_db as $value)
			$result[$value['id_category_default']][] = $value;

		return $result;
	}

	private function populateCategoriesWithProducts($item, $products)
	{
		$resulted_item = array();
		$id_category = null;

		foreach ($item as $key => $value)
		{
			if (!is_array($value))
			{
				if ($key == 'id_category')
					$id_category = $value;

				$resulted_item[$key] = $value;
			}
			else
				$resulted_item[$key] = $this->populateCategoriesWithProducts($value, $products);
		}

		if (isset($products[$id_category]))
		{
			foreach ($products[$id_category] as $value)
				$resulted_item['children'][] = $value;
		}

		return $resulted_item;
	}

	private function disableCategories(&$categories, $disabled_categories = null)
	{
		foreach ($categories as &$category)
		{
			if (!isset($disabled_categories) || in_array($category['id_category'], $disabled_categories))
			{
				$category['disabled'] = true;
				if (array_key_exists('children', $category) && is_array($category['children']))
					$this->disableCategories($category['children']);
			}
			else if (array_key_exists('children', $category) && is_array($category['children']))
				$this->disableCategories($category['children'], $disabled_categories);
		}
	}

	private function getSelectedChildNumbers(&$categories, $selected, &$parent = null)
	{
		$selected_childs = 0;

		foreach ($categories as &$category)
		{
			if (isset($parent) && in_array($category['id_category'], $selected))
				$selected_childs++;

			if (isset($category['children']) && !empty($category['children']))
				$selected_childs += $this->getSelectedChildNumbers($category['children'], $selected, $category);
		}

		if (!isset($parent['selected_childs']))
			$parent['selected_childs'] = 0;

		$parent['selected_childs'] = $selected_childs;

		return $selected_childs;
	}

}