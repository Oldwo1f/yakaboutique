<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminProductsController extends AdminProductsControllerCore
{
	public function __construct()
	{
		parent::__construct();
		if (Configuration::get('PS_STOCK_MANAGEMENT'))
		{
			$this->fields_list['sav_quantity']['callback'] = 'callbackSavQuantity';
			$this->fields_list['sav_quantity']['filter_key'] = 'sav!quantity';
			$this->_select = str_replace('sav.`quantity` as', 'sav.`quantity_remainder` as sav_quantity_remainder, sav.`quantity` as', $this->_select);
		}
		$this->fields_list['name']['callback'] = 'callbackName';
	}

	public static function callbackSavQuantity($echo, $tr)
	{
		return PP::adminControllerDisplayListContentQuantity($echo, $tr, 'sav_quantity', 'products sav');
	}

	public static function callbackName($echo, $tr)
	{
		static $show = null;
		if ($show === null)
			$show = (bool)Configuration::get('PP_TEMPLATE_NAME_IN_CATALOG');
		if ($show)
		{
			$id_pp_template = PP::getProductTemplateId($tr);
			if ($id_pp_template > 0)
			{
				PP::getAdminProductsTemplates(PP::getProductTemplateId($tr));
				$name = PP::getTemplateName($id_pp_template, true);
				if ($name != '')
				{
					static $template_title = null;
					if ($template_title === null)
					{
						$module = Module::getInstanceByName('pproperties');
						$translations = $module->getTranslations('AdminProductsController');
						$template_title = $translations['template_title'];
					}
					return $echo.'<br><span class="pp_list_template" title="'.$template_title.'"><i class="icon-template"></i>'
							.PP::safeOutputLenient($name).'</span>';
				}
			}
		}
		return $echo;
	}

	protected function copyFromPost(&$object, $table)
	{
		parent::copyFromPost($object, $table);
		if (get_class($object) != 'Product')
			return;

		if (Tools::getIsset('minimal_quantity'))
		{
			$minimal_quantity = Tools::getValue('minimal_quantity');
			$minimal_quantity = (empty($minimal_quantity) ? '0' : str_replace(',', '.', $minimal_quantity));
			$_POST['minimal_quantity'] = $minimal_quantity;
			$object->setMinQty($minimal_quantity);
		}
		if (Tools::getIsset('sp_from_quantity'))
			$_POST['sp_from_quantity'] = str_replace(',', '.', Tools::getValue('sp_from_quantity'));
	}

	public function ajaxProcessEditProductAttribute()
	{
		if ($this->tabAccess['edit'] === '1')
		{
			$id_product = (int)Tools::getValue('id_product');
			$id_product_attribute = (int)Tools::getValue('id_product_attribute');
			if ($id_product && Validate::isUnsignedId($id_product) && Validate::isLoadedObject($product = new Product((int)$id_product)))
			{
				$combinations = $product->getAttributeCombinationsById($id_product_attribute, $this->context->language->id);
				foreach ($combinations as $key => $combination)
				{
					$combinations[$key]['minimal_quantity'] = $product->resolveBoMinQty($combination['minimal_quantity'], $combination['minimal_quantity_fractional']);
					$combinations[$key]['attributes'][] = array($combination['group_name'], $combination['attribute_name'], $combination['id_attribute']);
				}

				die(Tools::jsonEncode($combinations));
			}
		}
	}

	public function ajaxProcessProductQuantity()
	{
		if (!Tools::getValue('actionQty'))
			return Tools::jsonEncode(array('error' => $this->l('Undefined action')));

		$product = new Product((int)Tools::getValue('id_product'), true);
		switch (Tools::getValue('actionQty'))
		{
			case 'depends_on_stock':
				if (Tools::getValue('value') === false)
					die (Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
				if ((int)Tools::getValue('value') != 0 && (int)Tools::getValue('value') != 1)
					die (Tools::jsonEncode(array('error' =>  $this->l('Incorrect value'))));
				if (!$product->advanced_stock_management && (int)Tools::getValue('value') == 1)
					die (Tools::jsonEncode(array('error' =>  $this->l('Not possible if advanced stock management is disabled. '))));
				if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int)Tools::getValue('value') == 1
					&& (Pack::isPack($product->id) && !Pack::allUsesAdvancedStockManagement($product->id)
					&& ($product->pack_stock_type == 2 || $product->pack_stock_type == 1 ||
						($product->pack_stock_type == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 1 || Configuration::get('PS_PACK_STOCK_TYPE') == 2)))))
					die (Tools::jsonEncode(array('error' => $this->l('You cannot use advanced stock management for this pack because').'</br>'.
						$this->l('- advanced stock management is not enabled for these products').'</br>'.
						$this->l('- you have chosen to decrement products quantities.'))));

				StockAvailable::setProductDependsOnStock($product->id, (int)Tools::getValue('value'));
				break;

			case 'pack_stock_type':
				$value = Tools::getValue('value');
				if ($value === false)
					die (Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
				if ((int)$value != 0 && (int)$value != 1
					&& (int)$value != 2 && (int)$value != 3)
					die (Tools::jsonEncode(array('error' =>  $this->l('Incorrect value'))));
				if ($product->depends_on_stock && !Pack::allUsesAdvancedStockManagement($product->id) && ((int)$value == 1
					|| (int)$value == 2 || ((int)$value == 3 && (Configuration::get('PS_PACK_STOCK_TYPE') == 1 || Configuration::get('PS_PACK_STOCK_TYPE') == 2))))
					die (Tools::jsonEncode(array('error' => $this->l('You cannot use this stock management option because:').'</br>'.
						$this->l('- advanced stock management is not enabled for these products').'</br>'.
						$this->l('- advanced stock management is enabled for the pack'))));

				Product::setPackStockType($product->id, $value);
				break;

			case 'out_of_stock':
				if (Tools::getValue('value') === false)
					die (Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
				if (!in_array((int)Tools::getValue('value'), array(0, 1, 2)))
					die (Tools::jsonEncode(array('error' =>  $this->l('Incorrect value'))));

				StockAvailable::setProductOutOfStock($product->id, (int)Tools::getValue('value'));
				break;

			case 'set_qty':
				$value = Tools::getValue('value');
				if ($value !== false)
					$value = $product->normalizeQty(trim($value));
				if ($value === false || !is_numeric($value))
					die (Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
				if (Tools::getValue('id_product_attribute') === false)
					die (Tools::jsonEncode(array('error' =>  $this->l('Undefined id product attribute'))));

				StockAvailable::setQuantity($product->id, (int)Tools::getValue('id_product_attribute'), $value);
				Hook::exec('actionProductUpdate', array('id_product' => (int)$product->id, 'product' => $product));

				// Catch potential echo from modules
				$error = ob_get_contents();
				if (!empty($error))
				{
					ob_end_clean();
					die (Tools::jsonEncode(array('error' => $error)));
				}
				break;
			case 'advanced_stock_management' :
				if (Tools::getValue('value') === false)
					die (Tools::jsonEncode(array('error' =>  $this->l('Undefined value'))));
				if ((int)Tools::getValue('value') != 1 && (int)Tools::getValue('value') != 0)
					die (Tools::jsonEncode(array('error' =>  $this->l('Incorrect value'))));
				if (!Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int)Tools::getValue('value') == 1)
					die (Tools::jsonEncode(array('error' =>  $this->l('Not possible if advanced stock management is disabled. '))));

				$product->setAdvancedStockManagement((int)Tools::getValue('value'));
				if (StockAvailable::dependsOnStock($product->id) == 1 && (int)Tools::getValue('value') == 0)
					StockAvailable::setProductDependsOnStock($product->id, 0);
				break;

		}
		die(Tools::jsonEncode(array('error' => false)));
	}

	public function initContent($token = null)
	{
		$pproperties = new PProperties();
		foreach ($pproperties->getTranslations('AdminProducts') as $key => $value)
			$this->tpl_form_vars[$key] = $value;
		if ($this->display == 'edit' || $this->display == 'add')
			Product::$amend = false;
		elseif ($this->tabAccess['edit'] === '1')
		{
			if (!is_array($this->bulk_actions))
				$this->bulk_actions = array();
			$this->bulk_actions['manageTemplatesDivider'] = array(
				'text' => 'divider'
			);
			$this->bulk_actions['manageTemplates'] = array(
				'text' => $this->l('Manage templates'),
				'icon' => 'icon-template'
			);
		}
		parent::initContent($token);
	}
}
