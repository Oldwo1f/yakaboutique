<?php
/**
* Product Properties Extension
*
* Extends product properties and add support for products with fractional
* units of measurements (for example: weight, length, volume).
*
* NOTICE OF LICENSE
*
* This source file is subject to the commercial software
* license agreement available through the world-wide-web at this URL:
* http://psandmore.com/licenses/sla
* If you are unable to obtain the license through the
* world-wide-web, please send an email to
* support@psandmore.com so we can send you a copy immediately.
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
* [PSM_OBFUSCATED]
*/

if (!defined('_PS_VERSION_'))
	exit;

class PProperties extends Module
{
	const USER_START_ID         = 100;
	const PROPERTY_TYPE_GENERAL = 1;
	const PROPERTY_TYPE_BUY_BLOCK_TEXT = 2;
	const PROPERTY_TYPE_EXT     = 3;
	const DIMENSIONS            = 3;

	public $integrated = false;
	public $integration_test_result = null;

	private $default_language_id;
	private $multidimensional_plugin = false;
	private $active_languages;
	private static $hook_id = 0;

	public function __construct($name = null, Context $context = null)
	{
		$this->name = 'pproperties';
		$this->tab = 'administration';
		$this->version = '2.14';
		$this->author = 'psandmore';
		$this->module_key = 'a78315086f12ede793183c113b223617';
		$this->need_instance = 1;
		$this->ps_versions_compliancy = array('min' => '1.6.0.14', 'max' => '1.6.0.14');
		$this->bootstrap = true;

		parent::__construct($name, $context);

		$this->displayName = $this->l('Product Properties Extension');
		$this->description = $this->l('Extends product properties and add support for products with fractional units of measurements (for example: weight, length, volume)');
		$this->confirmUninstall = $this->l('When you uninstall this module the user data is not lost and remains in the database. It will be available next time you install the module. Are you sure you want to remove the Product Properties Extension module?');
		$this->secure_key = Tools::encrypt($this->name);

		if (Module::isInstalled($this->name))
		{
			require_once(dirname(__FILE__).'/psm_helper.php');
			$this->integrated = (Configuration::get('PP_INTEGRATION') == $this->integrationKey());
			if (PP::multidimensionalEnabled())
				$this->multidimensional_plugin = PSM::getPlugin('ppropertiesmultidimensional');
		}
		else
		{
			static $done;
			if (!$done)
			{
				$done = true;
				require_once(dirname(__FILE__).'/psm_helper_integrate.php');
				psmHelperIntegrate(array($this->name));
				$filename = _PS_ROOT_DIR_.'/classes/module/Module.php';
				$content = Tools::file_get_contents($filename);
				$content = str_replace(array('?(?:php)?\s#', 'array(\' \','), array('?php#', 'array(\'\','), $content, $count);
				if ($count > 0)
					@file_put_contents($filename, $content);
				require_once(dirname(__FILE__).'/psm_helper.php');
				psmIntegrateCore($this, dirname(__FILE__).'/psm.php', $this->_errors);
			}
		}
	}

	public function install()
	{
		set_time_limit(0);

		if (version_compare(phpversion(), '5.3', '<'))
		{
			$this->_errors[] = sprintf($this->l('Requres PHP version %s or above. Currently running PHP %s version.'), '5.3', phpversion());
			return false;
		}

		psmHelperIntegrate(array($this->name));

		Tools::deleteFile(_PS_ROOT_DIR_.'/classes/PP.php');
		if (!psmIntegrateCore($this, dirname(__FILE__).'/psm.php', $this->_errors) ||
			!psmIntegrateCore($this, dirname(__FILE__).'/PP.php', $this->_errors, 'PP_VERSION['))
			return false;

		if (Shop::isFeatureActive())
			Shop::setContext(Shop::CONTEXT_ALL);

		$setup = $this->setupInstance();
		$setup->cleanupOverriddenFiles();

		if (!parent::install() || !$setup->installAdminTab('AdminPproperties'))
			return false;

		if (!$this->registerHook('displayNav') ||
			!$this->registerHook('displayHeader') ||
			!$this->registerHook('displayFooter') ||
			!$this->registerHook('displayProductPriceBlock') ||
			!$this->registerHook('displayPpropertiesProduct') ||
			!$this->registerHook('displayBackOfficeHeader') ||
			!$this->registerHook('displayAdminProductsExtra') ||
			!$this->registerHook('displayProductsListLeadin') ||
			!$this->registerHook('actionModuleInstallAfter') ||
			!$this->registerHook('actionModuleUpgradeAfter') ||
			!$this->registerHook('actionProductAdd') ||
			!$this->registerHook('actionProductUpdate') ||
			!$this->registerHook('actionProductDelete') ||
			!$this->registerHook('actionProductAttributeDelete') ||
			!$this->registerHook('actionObjectCombinationDeleteAfter'))
			return false;

		if ((int)Configuration::get('PP_MEASUREMENT_SYSTEM') == 0)
		{
			$w = Configuration::get('PS_WEIGHT_UNIT');
			Configuration::updateValue('PP_MEASUREMENT_SYSTEM', (Tools::strtolower($w) == 'lb') ? 2 : 1);
		}
		if (!Configuration::hasKey('PP_POWEREDBY'))
			Configuration::updateValue('PP_POWEREDBY', 1);
		if (!Configuration::hasKey('PP_TEMPLATE_NAME_IN_CATALOG'))
			Configuration::updateValue('PP_TEMPLATE_NAME_IN_CATALOG', 1);
		Configuration::updateValue('PP_INSTALL_TIME', time());

		$setup->install();
		Configuration::deleteByName('PP_LAST_VERSION');
		Configuration::updateValue('PP_VERSION', $this->version);
		psmClearCache();
		return true;
	}

	public function uninstall()
	{
		set_time_limit(0);

		$plugins = $this->plugins();
		foreach ($plugins as $name => $api_version)
			if (Module::isInstalled($name))
				$this->_errors[] = sprintf($this->l('Please uninstall the "%s" module.'), Module::getModuleName($name));
		if ($this->_errors)
			return false;

		$setup = $this->setupInstance();
		$setup->cleanupOverriddenFiles();

		if (!parent::uninstall() || !$setup->uninstallAdminTab('AdminPproperties'))
			return false;

		$setup->uninstall();
		Configuration::deleteByName('PP_INTEGRATION');
		Configuration::deleteByName('PP_INTEGRATION_CHECK');
		Configuration::deleteByName('PP_INTEGRATION_EXTRA_MODULES');
		Configuration::deleteByName('PP_INFO_CONTENT');
		Configuration::deleteByName('PP_INFO_CHECK_TIME');
		Configuration::deleteByName('PP_VERSION');
		Configuration::updateValue('PP_LAST_VERSION', $this->version);

		PrestaShopAutoload::getInstance()->generateIndex();
		return true;
	}

	public function hookDisplayNav($params)
	{
		if (Tools::getValue('ajax') || Configuration::get('PS_CATALOG_MODE'))
			return;
		if (PP::isMeasurementSystemFOActivated())
		{
			$measurement_systems = array(
				PP::PP_MS_METRIC     => array('name' => $this->l('Metric'),      'title' => $this->l('Metric measurement system')),
				PP::PP_MS_NON_METRIC => array('name' => $this->l('Imperial/US'), 'title' => $this->l('Imperial/US measurement system'))
			);
			$this->smarty->assign('measurement_systems', $measurement_systems);
			return $this->display(__FILE__, 'front/measurement_system.tpl');
		}
	}

	public function hookDisplayHeader($params)
	{
		if (Tools::getValue('ajax') || Configuration::get('PS_CATALOG_MODE'))
			return;
		$this->context->controller->addCSS($this->getPathUri().'views/css/pp_theme_pproperties.css');
		$this->context->controller->addCSS($this->getPathUri().'custom.css');
		$this->context->controller->addJquery();
		$this->context->controller->addJS($this->getPathUri().'views/js/pproperties.js');
		$this->context->controller->addJS($this->getPathUri().'views/js/pp_theme_pproperties.js');
		if ($this->context->controller instanceof ProductController)
		{
			$this->context->controller->addJqueryPlugin('typewatch');
			$this->context->controller->addJS($this->getPathUri().'views/js/pp_theme_product.js');
			$this->context->controller->addJS($this->getPathUri().'custom_product.js');
		}
		$this->context->controller->addJS($this->getPathUri().'custom.js');
		$pp_version = '"pp-'.str_replace(array('.', '-'), '', $this->version).'"';
		return '
			<script type="text/javascript">
				var pp_version = '.$pp_version.';
				pp.decimalSign="'.PP::getDecimalSign().'";
				$(document).ready(function() {
					$("body").addClass('.$pp_version.');
				});
			</script>';
	}

	public function hookDisplayFooter($params)
	{
		if (Tools::getValue('ajax') || Configuration::get('PS_CATALOG_MODE'))
			return;
		$str = '<noscript>Please enable javascript in order to use Product Properties Extension <a href="http://psandmore.com" title="Product Properties Extension powered by PS&amp;More&trade;">Powered by PS&amp;More&trade;</a></noscript>';
		if ((int)Configuration::get('PP_POWEREDBY'))
		{
			$str .= '<span id="powered_by_psandmore" class="solo"><a href="http://psandmore.com" target="_blank" title="'.
					sprintf($this->l('This site is using Product Properties Extension powered by %s'), 'PS&amp;More&trade;').'">'.
					sprintf($this->l('Powered by %s'), 'PS&amp;More&trade;').'</a></span>';
			$str .= '
			<script type="text/javascript">
				$(document).ready(function() {
					var bottom_footer = $("section.bottom-footer > div");
					if (bottom_footer.length) {
						var powered_by_psandmore = $("#powered_by_psandmore");
						powered_by_psandmore.removeClass("solo");
						bottom_footer.append(powered_by_psandmore.detach());
					}
				});
			</script>';
		}
		return $str;
	}

	public function hookDisplayProductPriceBlock($params)
	{
		if (!$this->integrated)
			return;

		static $s_product = null;
		if (isset($params['product']))
			$product = $params['product'];
		elseif (isset($params['id_product']))
		{
			if ($s_product !== null && $s_product->id == $params['id_product'])
				$product = $s_product;
			else
				$product = new Product($params['id_product'], true, $this->context->language->id);
		}
		else
			$product = null;

		if (!Validate::isLoadedObject($product))
			return;

		$s_product = $product;
		//$properties = $product->productProperties();

		$ret = '';
		$type = $params['type'];
		switch ($type)
		{
			case 'price':
				break;
			case 'unit_price':
				//$id = 'ppHook'.++self::$hook_id;
				//$ret = '<span id="'.$id.'"></span><script type="text/javascript">pp.hooks.onReady("'.$id.'",
						// "hookDisplayProductPriceBlock_'.$type.'", "'.PP::safeOutputJS($properties['pp_unity_text']).'")</script>';
				break;
			case 'old_price':
				break;
			case 'weight':
				break;
			default:
				break;
		}
		return $ret;
	}

	public function hookDisplayPpropertiesProduct($params)
	{
		if (!$this->integrated)
			return;
		$product = $params['product'];
		if (!Validate::isLoadedObject($product))
			return;
		$has_discounts = (isset($params['ppDiscounts']) && (bool)$params['ppDiscounts']);
		$product_properties = $product->productProperties();
		$pp_product_properties = array();
		$quotes = array();
		foreach ($product_properties as $key => $value)
		{
			if (strpos($key, 'pp_') === 0 && strpos($key, 'pp_bo_') !== 0 && strpos($key, '_text') > 0)
			{
				$pp_product_properties[$key] = $value;
				$quotes[$key] = true;
			}
		}
		foreach ($this->getTranslations('ProductController') as $key => $value)
		{
			$pp_product_properties[$key] = $value;
			$quotes[$key] = true;
		}
		$pp_product_properties = PP::safeOutputJS($pp_product_properties);
		$pp_product_properties['id_pp_template']         = $product_properties['id_pp_template'];
		$pp_product_properties['pp_qty_policy']          = $product_properties['pp_qty_policy'];
		$pp_product_properties['pp_display_mode']        = $product_properties['pp_display_mode'];
		$pp_product_properties['pp_price_display_mode']  = $product_properties['pp_price_display_mode'];
		$pp_product_properties['pp_minimal_price_ratio'] = $product_properties['pp_minimal_price_ratio'];
		$pp_product_properties['pp_qty_step']            = $product_properties['pp_qty_step'];
		$pp_product_properties['minQty']                 = $product->minQty();
		$pp_product_properties['defaultQty'] = (isset($this->context->smarty->tpl_vars['quantityBackup'])
												? $this->context->smarty->tpl_vars['quantityBackup'] : $product->defaultQty());
		$pp_product_properties['explanation'] = PP::safeOutputLenientJS($product_properties['pp_explanation']);
		$quotes['explanation'] = true;
		$pp_product_properties['pp_css'] = PP::safeOutputJS($product_properties['pp_css']);
		$quotes['pp_css'] = true;

		if ((int)(($product_properties['pp_display_mode'] & 2) == 2))
			$pp_product_properties['display_mode_retail_price'] = Product::getRetailPrice($product);
		if (! $this->multidimensional_plugin)
			$product_properties['pp_ext'] = 0;
		$pp_product_properties['pp_ext'] = $product_properties['pp_ext'];

		$script = '
		<script type="text/javascript">
			$("body").addClass("'.$product_properties['pp_css'].' pp_template_'.$product_properties['id_pp_template'].
								(Configuration::get('PP_SHOW_POSITIONS') ? ' pp-positions-visible' : '').'");
			var ppProductProperties = [];';
		foreach ($pp_product_properties as $key => $value)
		{
			if (isset($quotes[$key]))
				$value = '"'.$value.'"';
			$script .= '
			ppProductProperties["'.$key.'"] = '.$value.';';
		}

		if ($product_properties['pp_ext'] == 1)
		{
			$script .= '
			ppProductProperties["pp_ext_policy"] = '.$product_properties['pp_ext_policy'].';';
			$script .= '
			ppProductProperties["pp_ext_method"] = '.$product_properties['pp_ext_method'].';';
			$script .= '
			ppProductProperties["pp_ext_title"] = "'.PP::safeOutputLenientJS($product_properties['pp_ext_title']).'";';
			$script .= '
			ppProductProperties["pp_ext_property"] = "'.PP::safeOutputLenientJS($product_properties['pp_ext_property']).'";';
			$script .= '
			ppProductProperties["pp_ext_text"] = "'.PP::safeOutputLenientJS($product_properties['pp_ext_text']).'";';
			$script .= '
			ppProductProperties["pp_ext_prop"] = [];';
			foreach ($product_properties['pp_ext_prop'] as $position => $arr)
			{
				$s = '{';
				$s .= 'property:"'.PP::safeOutputLenientJS($arr['property']).'"';
				$s .= ',text:"'.PP::safeOutputLenientJS($arr['text']).'"';
				$s .= ',minimum_quantity:'.(float)$arr['minimum_quantity'];
				$s .= ',maximum_quantity:'.(float)$arr['maximum_quantity'];
				$s .= ',default_quantity:'.(float)$arr['default_quantity'];
				$s .= ',qty_step:'.(float)$arr['qty_step'];
				$s .= ',qty_ratio:'.(float)$arr['qty_ratio'];
				$s .= '}';
				$script .= '
				ppProductProperties["pp_ext_prop"]['.$position.'] = '.$s.';';
			}

			if ($product_properties['pp_ext_policy'] == 2)
			{
				$script .= '
				ppProduct.fallback_ext_quantity = 1;
				ppProduct.prop = '.Tools::jsonEncode($product->productProp()).';';
			}
		}

		$actions = array('price' => $this->context->link->getModuleLink($this->name, 'product', array('process' => 'price')));
		$script .= '
			ppProduct.actions = '.Tools::jsonEncode($actions).';
			ppProduct.hasAttributes = '.($product->hasAttributes() ? 'true' : 'false').';
			ppProduct.priceObserver = '.($has_discounts || PSM::getPlugin('ppropertiessmartprice') ? 'true' : 'false').';';

		$script .= '
		</script>';
		return $script;
	}

	public function hookDisplayBackOfficeHeader($params)
	{
		$tab = Tools::getValue('tab');
		$controller = Tools::getValue('controller');

		if (Tools::strtolower($tab) == 'adminselfupgrade' || Tools::strtolower($controller) == 'adminselfupgrade')
		{
			$warn = '<div class="alert alert-danger"><button data-dismiss="alert" class="close" type="button">×</button>'.
						sprintf($this->l('%s: Please uninstall this module before upgrading and obtain, if needed, version compatible with your new PrestaShop version.'), $this->displayName).
						'<br>'.$this->compatibilityText()
					.'</div>';
			return '
			<script type="text/javascript">
				$(document).ready(function() {
					$("#content .bootstrap").prepend(\''.$warn.'\');
					$("#upgradeNow").remove();
					$("#currentConfiguration table tbody").append(\'<tr><td>'.
						sprintf($this->l('%s module uninstalled'), $this->displayName).
						'<br>'.$this->compatibilityText().
						'</td><td><img alt="ok" src="../img/admin/disabled.gif"></td></tr>\');
				});
			</script>
			';
		}
		else
		{
			if ($this->context->controller instanceof Controller)
			{
				if (Tools::getValue('configure') != 'pproperties')
				{
					if (!(int)Tools::getValue('ajax'))
					{
						$last_integration_check = Configuration::get('PP_INTEGRATION_CHECK');
						if (time() > ($last_integration_check + ($this->integrated ? 3600 : 3)))
						{
							$setup = $this->setupInstance();
							$setup->checkIntegration();
							$this->integrated = (count($this->integration_test_result) == 0);
						}
					}
					if (!$this->integrated)
					{
						$warn = '<div class="alert alert-danger" style="clear:both;">'.
									'<button data-dismiss="alert" class="close" type="button">×</button>'.
									sprintf($this->l('%s: Integration warning. Your site will not work properly until you %s.'), $this->displayName, '<a style="text-decoration:underline;color:inherit;" href="index.php?controller=adminmodules&configure=pproperties&token='.Tools::getAdminTokenLite('AdminModules').'&tab_module=administration&module_name=pproperties">'.$this->l('resolve the integration problems').'</a>')
								.'</div>';
						return '
						<script type="text/javascript">
							$(document).ready(function() {
								$("#content.bootstrap").prepend(\''.$warn.'\');
							});
						</script>
						';
					}
				}

				$html = '';
				$add_extra = false;
				$this->context->controller->addJquery();
				$css_files = array();
				$js_files = array();
				if (in_array(Tools::strtolower($controller), array('adminproducts', 'adminorders', 'admincarts', 'adminstockmanagement', 'adminstockmvt', 'adminstockinstantstate', 'adminstockcover')))
					$add_extra = true;
				elseif (strcasecmp($controller, 'AdminModules') == 0)
				{
					if (Tools::getValue('configure') == 'pproperties')
					{
						$this->context->controller->addJqueryUI('ui.tabs', 'base');
						$add_extra = true;
					}
				}
				if ($add_extra)
				{
					$css_files[] = 'views/css/pproperties_admin.css';
					$js_files[] = 'views/js/pproperties.js';
					$js_files[] = 'views/js/pproperties_admin.js';
					if ($this->integrated)
						$html .= '<script type="text/javascript">pp.decimalSign=\''.PP::getDecimalSign().'\';</script>';
				}
				if ($this->integrated && strcasecmp($controller, 'AdminAttributeGenerator') == 0)
				{
					$template_id = PP::getProductTemplateId(Tools::getValue('id_product'));
					if ($template_id > 0)
					{
						$properties = PP::getProductPropertiesByTemplateId($template_id);
						if (!empty($properties['pp_bo_qty_text']))
						{
							$css_files[] = 'css/pproperties_admin.css';
							$html .= '<script type="text/javascript">$(function() {$(\'#generator input[name="quantity"]\').after(\' '.$properties['pp_bo_qty_text'].'\');});</script>';
						}
					}
				}
				if ($css_files)
				{
					foreach ($css_files as $file)
						$this->context->controller->addCSS($this->getPathUri().$file);
					PSM::amendCSS($this->context->controller->css_files, $css_files);
				}
				if ($js_files)
				{
					foreach ($js_files as $file)
						$this->context->controller->addJS($this->getPathUri().$file);
					PSM::amendJS($this->context->controller->js_files, $js_files);
				}
				return $html;
			}
		}
	}

	public function hookDisplayAdminProductsExtra($params)
	{
		$id_product = Tools::getValue('id_product');
		$this->context->smarty->assign(
			array(
				'integrated' => $this->integrated,
				'multidimensional' => (bool)$this->multidimensional_plugin,
				'id_product' => $id_product,
				'_path' => $this->getPathUri(),
				'_PS_ADMIN_IMG_' => _PS_ADMIN_IMG_,
				's_header' => $this->l('Assign or change product template'),
				's_product_template' => $this->l('Product template'),
				's_hint' => $this->l('Please save this product before making any other changes.'),
				's_advice' => $this->l('You can assign or remove template for several products in one operation using bulk actions in product\'s catalog.'),
				's_configure_templates' => $this->l('Configure templates'),
				's_edit_template' => $this->l('Edit this template'),
				's_user_guide' => $this->l('Read user guide'),
			)
		);
		if (!$this->integrated)
			$this->context->smarty->assign('integration_warning', $this->l('Please resolve integration problems.'));
		if (!(bool)$this->multidimensional_plugin)
			$this->context->smarty->assign('multidimensional_warning', $this->l('Multidimensional plugin not installed.'));
		$translations = $this->getTranslations('AdminProductsExtra');
		foreach ($translations as $key => $value)
			$this->context->smarty->assign($key, $value);
		$this->context->smarty->assign('hook_html', Hook::exec('ppropertiesAdmin', array('mode' => 'displayAdminProductsExtra', 'id_product' => $id_product), null, true));
		return $this->display(__FILE__, 'admin/product.tpl');
	}

	public function hookActionModuleInstallAfter($params)
	{
		$this->setupInstance()->moduleInstalled($params['object']);
	}

	public function hookActionModuleUpgradeAfter($params)
	{
		$this->setupInstance()->moduleUpgraded($params['object']);
	}

	public function hookActionProductAdd($params)
	{
		$this->updateProductProp($params);
	}

	public function hookActionProductUpdate($params)
	{
		$this->updateProductProp($params);
	}

	public function hookActionProductDelete($params)
	{
		if ($this->multidimensional_plugin)
		{
			$product = $params['product'];
			if (Validate::isLoadedObject($product))
				Db::getInstance()->delete(_DB_PREFIX_.'pp_product_prop', 'id_product = '.$product->id);
		}
	}

	public function hookActionProductAttributeDelete($params)
	{
		if ($this->multidimensional_plugin)
		{
			$id_product = $params['id_product'];
			$id_product_attribute = $params['id_product_attribute'];
			$delete_all_attributes = $params['deleteAllAttributes'];
			Db::getInstance()->delete(_DB_PREFIX_.'pp_product_prop', 'id_product = '.$id_product.($delete_all_attributes ? '' : ' AND id_product_attribute='.$id_product_attribute));
		}
	}

	public function hookActionObjectCombinationDeleteAfter($params)
	{
		if ($this->multidimensional_plugin)
		{
			$object = $params['object'];
			if (Validate::isLoadedObject($object))
				Db::getInstance()->delete(_DB_PREFIX_.'pp_product_prop', 'id_product = '.$object->id_product.' AND id_product_attribute='.$object->id);
		}
	}

	public function hookDisplayProductsListLeadin($params)
	{
		if (!$this->integrated) return;
		if (Tools::isSubmit('submitBulkmanageTemplatesproduct'))
		{
			if (Tools::getIsset('cancel'))
				Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts'));
			if (($assign = Tools::getIsset('submitAssignTemplate')) || Tools::getIsset('submitRemoveTemplate'))
			{
				$id_pp_template = (int)Tools::getValue('id_pp_template');
				if ($id_pp_template > 0)
				{
					if (($manageTemplates = Tools::getValue('manageTemplates')) && is_array($manageTemplates))
					{
						$sql = 'UPDATE `'._DB_PREFIX_.'product` SET `id_pp_template` = '.($assign ? $id_pp_template : 0).
								' WHERE `id_product` in ('.implode(',', $manageTemplates).') and '.
								($assign ? '(`id_pp_template` = 0 or `id_pp_template` is NULL)' : '`id_pp_template` = '.$id_pp_template);
						DB::getInstance()->execute($sql);
					}
					return;
				}
				else
					$this->context->smarty->assign('error_no_template', true);
			}
			$this->context->smarty->assign(array(
				'REQUEST_URI' => $_SERVER['REQUEST_URI'],
			));
			return $this->display(__FILE__, 'admin/products_list_header.tpl');
		}
	}

	private function updateProductProp($params)
	{
		if ($this->multidimensional_plugin)
		{
			$product = (isset($params['product']) ? $params['product'] : PP::productAsObject($params));
			if (Validate::isLoadedObject($product))
			{
				$properties = $product->productProperties();
				if ($properties['pp_ext'] == 1 && $properties['pp_ext_policy'] == 2)
				{
					$id_product_attribute = 0;
					$has_attributes = $product->hasAttributes();
					if ($has_attributes)
					{
						$id_product_attribute = (int)Tools::getValue('id_product_attribute');
						if ($id_product_attribute <= 0)
							$id_product_attribute = false;
					}
					if ($id_product_attribute !== false)
					{
						$db = Db::getInstance();
						$db->delete(_DB_PREFIX_.'pp_product_prop', 'id_product = '.$product->id.($has_attributes ? ' AND (id_product_attribute=0 OR id_product_attribute='.$id_product_attribute.')' : ''));

						$r = array('id_product' => $product->id, 'id_product_attribute' => $id_product_attribute);
						foreach ($properties['pp_ext_prop'] as $position => $ext_prop)
						{
							$ext_prop_quantity = (float)str_replace(',', '.', Tools::getValue('pp_ext_prop_quantity_'.$position));
							$r['position'] = $position;
							$r['quantity'] = ($ext_prop_quantity > 0 ? $ext_prop_quantity : ((float)$ext_prop['default_quantity'] > 0 ? (float)$ext_prop['default_quantity'] : 1));
							$db->autoExecute(_DB_PREFIX_.'pp_product_prop', $r, 'INSERT');
						}
					}
				}
			}
		}
	}

	public function getTranslations($key, &$translations = null)
	{
		if ($translations === null) $translations = array();
		if ($key == 'AdminProducts')
		{
			$translations['s_ID'] = $this->l('ID:');
			$translations['s_ppMinQtyExpl_disable'] = $this->l('The minimum quantity to buy this product (set to 1 to disable this feature)');
			$s = $this->l('The minimum quantity to buy this product (set to %d to use the template default)');
			$translations['s_ppMinQtyExpl_0'] = sprintf($s, 1);
			$translations['s_ppMinQtyExpl_1'] = sprintf($s, 0);
			$translations['s_ppMinQtyExpl_2'] = sprintf($s, 0);
			$translations['s_minimal_quantity'] = $this->l('the minimum quantity defined in template is');
			$translations['s_pp_unity_text_expl'] = $this->l('specified by template');
			$translations['s_pack_hint'] = $this->l('You can only add to a pack products sold in items (cannot add products sold by weight, length, etc.).');
			$translations['s_ppe_title'] = sprintf($this->l('%s properties'), $this->displayName);
			return $this->getTranslations('AdminProductsExtra', $translations);
		}
		elseif ($key == 'AdminProductsController')
			$translations['template_title'] = $this->l('This product uses Product Properties Extension template');
		elseif ($key == 'ProductController')
		{
			$translations['priceTxt'] = $this->l('price');
			$translations['qtyAvailableTxt'] = $this->l('in stock');
		}
		elseif ($key == 'EditTemplate' || $key == 'AdminProductsExtra')
		{
			$translations['s_pp_qty_policy'] = $this->l('quantity policy');
			$translations['s_pp_qty_mode'] = $this->l('quantity mode');
			$translations['s_pp_display_mode'] = $this->l('display mode');
			$translations['s_pp_price_display_mode'] = $this->l('price display mode');
			$translations['s_pp_price_text'] = $this->l('price text');
			$translations['s_pp_qty_text'] = $this->l('quantity text');
			$translations['s_pp_unity_text'] = $this->l('unit price text');
			$translations['s_pp_unit_price_ratio'] = $this->l('unit price ratio');
			$translations['s_pp_minimal_price_ratio'] = $this->l('quantity threshold for minimum price');
			$translations['s_pp_minimal_quantity'] = $this->l('minimum quantity');
			$translations['s_pp_default_quantity'] = $this->l('default quantity');
			$translations['s_pp_qty_step'] = $this->l('quantity step');
			$translations['s_pp_explanation'] = $this->l('inline explanation');
			$translations['s_pp_qty_policy_0'] = $this->l('items');
			$translations['s_pp_qty_policy_1'] = $this->l('whole units');
			$translations['s_pp_qty_policy_2'] = $this->l('fractional units');
			$translations['s_pp_qty_policy_ext'] = $this->l('multidimensional');
			$translations['s_pp_qty_mode_0'] = $this->l('exact quantity');
			$translations['s_pp_qty_mode_1'] = $this->l('approximate quantity');
			$translations['s_pp_display_mode_0'] = $this->l('normal');
			$translations['s_pp_display_mode_1'] = $this->l('reversed price display');
			$translations['s_pp_display_mode_1_long'] = $this->l('display unit price as price (reversed price display)');
			$translations['s_pp_display_mode_2'] = $this->l('display retail price as unit price');
			$translations['s_pp_display_mode_4'] = $this->l('display base unit price for all combinations');
			$translations['s_pp_price_display_mode_0']  = $this->l('normal');
			$translations['s_pp_price_display_mode_1']  = $this->l('as product price');
			$translations['s_pp_price_display_mode_16'] = $this->l('hide price display');
		}
		elseif ($key == 'ppExt')
		{
			$translations['s_single_dimension'] = $this->l('single dimension');
			$translations['s_multiplication'] = $this->l('multiplication: dimensions in all directions are multiplied (giving area or volume)');
			$translations['s_summation'] = $this->l('summation: dimensions in all directions are added (giving perimeter)');
		}
		return $translations;
	}

	/** Called in Back Office when user clicks "Configure" */
	public function getContent()
	{
		if (Configuration::get('PS_DISABLE_NON_NATIVE_MODULE'))
			return '<div class="module_error alert alert-danger">'.$this->l('Non PrestaShop modules disabled.').'</div>';

		$this->active_languages = $this->context->controller->getLanguages();
		$this->default_language_id = $this->context->controller->default_form_language;

		$setup = $this->setupInstance();
		if (!(int)Tools::getValue('pp'))
			$setup->checkIntegration();

		$tab = '0';
		$output0 = $output1 = $output2 = $output3 = $output4 = '';
		$templates = null;
		$properties = null;
		if (Tools::isSubmit('submitRestoreDefaults'))
		{
			$tab = '0';
			$setup->insertData(true);
		}
		elseif (Tools::isSubmit('cancelSaveTemplate'))
			$tab = '0';
		elseif (Tools::isSubmit('cancelSaveProperty'))
			$tab = '1';
		elseif (Tools::isSubmit('submitSaveTemplate'))
		{
			$result = $this->saveTemplate();
			if ($result['error'] == '')
				$tab = '0';
			else
			{
				$templates = $result['templates'];
				$output4 = $this->displayError($result['error']);
				$tab = '4';
			}
		}
		elseif (Tools::isSubmit('submitSaveProperty'))
		{
			$result = $this->saveProperty();
			if ($result['error'] == '')
				$tab = '1';
			else
			{
				$properties = $result['properties'];
				$output4 = $this->displayError($result['error']);
				$tab = '4';
			}
		}
		elseif (Tools::isSubmit('submitConfigSettings'))
		{
			$tab = '2';
			Configuration::updateValue('PP_MEASUREMENT_SYSTEM', (int)Tools::getValue('measurement_system', 1));
			Configuration::updateValue('PP_MEASUREMENT_SYSTEM_FO', (int)Tools::getValue('measurement_system_fo', 0));
			Configuration::updateValue('PP_POWEREDBY', (int)Tools::getValue('poweredby', 0));
			Configuration::updateValue('PP_TEMPLATE_NAME_IN_CATALOG', (int)Tools::getValue('template_name_in_catalog', 1));
			Configuration::updateValue('PP_SHOW_POSITIONS', (int)Tools::getValue('show_positions', 0));
			$output2 = $this->displayConfirmation($this->l('Settings updated'));
		}
		elseif (Tools::isSubmit('submitSetup'))
		{
			$tab = '2';
			$setup->runSetup();
		}
		elseif (Tools::isSubmit('submitIntegration'))
		{
			$tab = '2';
			$setup->runIntegrationTest();
		}
		elseif (Tools::isSubmit('clickClearCache'))
		{
			$tab = '2';
			PSM::clearCache();
		}
		elseif (Tools::isSubmit('submitStatistics'))
			$tab = '3';
		// NOTE: all 'click' test must be at the end.
		// They are performed as GET and can interfier with 'submit' buttons.
		elseif (Tools::isSubmit('clickEditTemplate'))
			$tab = '4';
		elseif (Tools::isSubmit('clickDeleteTemplate'))
		{
			$tab = '0';
			$this->deleteTemplate();
		}
		elseif (Tools::isSubmit('clickHiddenStatusTemplate'))
		{
			$tab = '0';
			$this->changeHiddenStatus();
		}
		elseif (Tools::isSubmit('clickEditProperty'))
			$tab = '4';
		elseif (Tools::isSubmit('clickDeleteProperty'))
		{
			$tab = '1';
			$this->deleteProperty();
		}

		$html = '';
		if (version_compare(_PS_VERSION_, $this->ps_versions_compliancy['min']) < 0
			|| version_compare(_PS_VERSION_, $this->ps_versions_compliancy['max']) > 0)
		{
			$html .= $this->displayError($this->l('This module is not fully compatible with the installed PrestaShop version.').
													' '.$this->compatibilityText().
													'<br>'.$this->l('Please upgrade to the newer version.').'<br>');
		}
		if (count($this->integration_test_result) != 0)
		{
			$html .= $this->displayError($this->l('Integration test failed.'));
			$tab = '2';
		}

		$tabs = array();
		$tabs[0] = array(
			'type' => 'templates',
			'name' => $this->l('Templates'),
			'html' => $output0.$this->getTemplatesTabHtml(),
		);
		$tabs[1] = array(
			'type' => 'properties',
			'name' => $this->l('Properties'),
			'html' => $output1.$this->getPropertiesTabHtml(),
		);
		$tabs[2] = array(
			'type' => 'settings',
			'name' => $this->l('Settings'),
			'html' => $output2.$this->getSettingsTabHtml(Tools::isSubmit('submitIntegration') || Tools::isSubmit('submitSetup')),
		);
		$tabs[3] = array(
			'type' => 'statistics',
			'name' => $this->l('Statistics'),
			'html' => $output3.$this->getStatisticsTabHtml(Tools::isSubmit('submitStatistics')),
		);
		if (($tab == 4) && (Tools::isSubmit('clickEditTemplate') || Tools::isSubmit('submitSaveTemplate')))
		{
			$mode = Tools::getValue('mode');
			if ($mode == 'add')
				$title = $this->l('Add template');
			elseif ($mode == 'copy')
				$title = $this->l('Add template');
			else
			{
				$mode = 'edit';
				$title = $this->l('Edit template');
			}
			$tabs[4] = array(
				'type' => 'modifyTemplate',
				'name' => $title,
				'html' => $output4.$this->getEditTemplateTabHtml($templates, $mode, $title),
			);
		}
		elseif (($tab == 4) && (Tools::isSubmit('clickEditProperty') || Tools::isSubmit('submitSaveProperty')))
		{
			$mode = Tools::getValue('mode');
			if ($mode == 'add')
			{
				$type = (int)Tools::getValue('type');
				if ($type == self::PROPERTY_TYPE_GENERAL)
					$title = $this->l('Add property attribute');
				elseif ($type == self::PROPERTY_TYPE_BUY_BLOCK_TEXT)
					$title = $this->l('Add property text');
				else
					$title = $this->l('Add property dimension');
			}
			else
			{
				$mode = 'edit';
				$title = $this->l('Edit property');
			}
			$tabs[4] = array(
				'type' => 'modifyProperty',
				'name' => $title,
				'html' => $output4.$this->getEditPropertyTabHtml($properties, $mode, $title),
			);
		}

		//$helper->fields_value['tab'] = $tab;
		$helper = $this->createTemplate('pproperties');
		$helper->tpl_vars['html'] = $html;
		$helper->tpl_vars['tabs'] = $tabs;
		$helper->tpl_vars['active'] = $tab;
		$helper->tpl_vars['version'] = $this->version;
		$helper->tpl_vars['ppe_id'] = PSM::getPSMId($this);
		$helper->tpl_vars['_path'] = $this->getPathUri();
		$helper->tpl_vars['s_user_guide'] = $this->l('user guide');
		$helper->tpl_vars['s_version'] = $this->l('Version');
		$helper->tpl_vars['s_pp_info_ignore'] = $this->l("don't show this message again");
		$helper->tpl_vars['token_adminpproperties'] = Tools::getAdminTokenLite('AdminPproperties');
		$helper->tpl_vars['jstranslations'] = PP::safeOutputJS(
			array(
				'rerun' => $this->l('Re-run Setup'),
				'integration_module_success_IntegrationModuleIgnore' => $this->l('ignored - please re-run setup'),
				'integration_module_success_IntegrationModuleIntegrate' => $this->l('integation activated - please re-run setup'),
				'integration_module_rerun_IntegrationModuleCheckForUpdates' => $this->l('please re-run setup'),
				'integration_module_downloaded_IntegrationModuleCheckForUpdates' => $this->l('update downloaded - please re-run setup'),
				'integration_module_no_updates_IntegrationModuleCheckForUpdates' => $this->l('no updates available - please contact customer support'),
				'integration_module_error' => $this->l('error occurred')
			)
		);

		if (!Module::isInstalled('psmextmanager') && Module::getInstanceByName('psmextmanager'))
			$helper->tpl_vars['psmextmanager_install'] = $this->context->link->getAdminLink('AdminModules').'&install=psmextmanager&tab_module=administration&module_name=psmextmanager&anchor=Psmextmanager';

		return $helper->generate();
	}

	private function getTemplatesTabHtml()
	{
		$helper = $this->createTemplate('templates');
		$helper->tpl_vars['integrated'] = $this->integrated;
		if ($this->integrated)
		{
			$templates = PP::getTemplates();
			$buy_block_text = array();
			foreach ($templates as &$template)
			{
				$display_mode = array();
				if (($template['pp_display_mode'] & 1) == 1)
					$display_mode[] = 1;
				if (($template['pp_display_mode'] & 2) == 2)
					$display_mode[] = 2;
				if (($template['pp_display_mode'] & 4) == 4)
					$display_mode[] = 3;
				$template['display_mode'] = implode(',', $display_mode);
				if ($template['pp_explanation'])
					$buy_block_text[$template['pp_bo_buy_block_index']] = PP::safeOutputLenient($template['pp_explanation']);
			}
			ksort($buy_block_text, SORT_NUMERIC);

			$helper->tpl_vars['templates'] = PP::safeOutput($templates);
			$helper->tpl_vars['buy_block_text'] = $buy_block_text;

			$translations = $this->getTranslations('EditTemplate');
			$helper->tpl_vars['display_mode_text'] = array(
				$translations['s_pp_display_mode_1_long'],
				$translations['s_pp_display_mode_2'],
				$translations['s_pp_display_mode_4']
			);
		}
		else
			$helper->tpl_vars['integration_message'] = $this->getTabIntegrationWarning();

		return $helper->generate();
	}

	private function getPropertiesTabHtml()
	{
		$helper = $this->createTemplate('properties');
		$helper->tpl_vars['integrated'] = $this->integrated;
		if ($this->integrated)
		{
			$all_properties = $this->getAllProperties();
			$metric = (PP::resolveMS() != 2);

			$helper->tpl_vars['properties'] = $all_properties[$this->default_language_id];
			$helper->tpl_vars['property_types'] = $this->getPropertyTypes();
			$helper->tpl_vars['types'] = array(
				'attributes' => array('id' => self::PROPERTY_TYPE_GENERAL, 'metric' => true, 'nonmetric' => true),
				'texts' => array('id' => self::PROPERTY_TYPE_BUY_BLOCK_TEXT, 'metric' => $metric, 'nonmetric' => !$metric),
				'dimensions' => ($this->multidimensional_plugin ? array('id' => self::PROPERTY_TYPE_EXT, 'metric' => true, 'nonmetric' => true) : false),
			);
		}
		else
			$helper->tpl_vars['integration_message'] = $this->getTabIntegrationWarning();

		return $helper->generate();
	}

	private function getSettingsTabHtml($display)
	{
		$html = '';
		if ($this->integrated)
		{
			$helper = $this->createHelperForm('pp_settings_form', $this->l('Settings'), 'submitConfigSettings', 'icon-AdminAdmin');
			$form = array(
				'input' => array(
					array(
						'label'	=> $this->l('Measurement system'),
						'type'	=> 'radio',
						'name'	=> 'measurement_system',
						'desc'	=> $this->l('unit measurement system used by default (can be overridden in template)'),
						'values'=> array(
							array(
								'id'    => 'measurement_system_1',
								'value' => (int)PP::PP_MS_METRIC,
								'label' => $this->l('metric')
							),
							array(
								'id'    => 'measurement_system_2',
								'value' => (int)PP::PP_MS_NON_METRIC,
								'label' => $this->l('non metric (imperial/US)')
							)
						),
					),
					/*
					array(
						'label'	=> $this->l('Display measurement system'),
						'type'	=> 'switch',
						'name'	=> 'measurement_system_fo',
						'desc'	=> $this->l('customer selected measurement system works only for templates that use the default unit measurement system'),
						'hint'	=> $this->l('Add a block allowing customers to choose their preferred measurement system.'),
						'values'=> array(
							array(
								'id'    => 'measurement_system_fo_on',
								'value' => 1,
							),
							array(
								'id'    => 'measurement_system_fo_off',
								'value' => 0,
							)
						),
					),
					*/
					array(
						'label'	=> $this->l('Display "Powered by PS&More"'),
						'type'	=> 'switch',
						'name'	=> 'poweredby',
						'values'=> array(
							array(
								'id'    => 'psandmore_on',
								'value' => 1,
							),
							array(
								'id'    => 'psandmore_off',
								'value' => 0,
							)
						),
					),
					array(
						'label'	=> $this->l('Show templates in the catalog'),
						'type'	=> 'switch',
						'name'	=> 'template_name_in_catalog',
						'desc'	=> $this->l('show or hide template names in the products catalog'),
						'values'=> array(
							array(
								'id'    => 'on',
								'value' => 1,
							),
							array(
								'id'    => 'off',
								'value' => 0,
							)
						),
					),
					/*
					array(
						'label'	=> $this->l('Display positions'),
						'type'	=> 'switch',
						'name'	=> 'show_positions',
						'desc'	=> $this->l('show or hide position names on the product\'s page (use it only for testing)'),
						'values'=> array(
							array(
								'id'    => 'show_positions_on',
								'value' => 1,
							),
							array(
								'id'    => 'show_positions_off',
								'value' => 0,
							)
						),
					),
					*/
					array(
						'type' => 'clearcache',
						'name' => $this->l('Clear cache'),
					),
				),
			);
			$helper->fields_value['measurement_system'] = (int)Tools::getValue('measurement_system', Configuration::get('PP_MEASUREMENT_SYSTEM'));
			$helper->fields_value['measurement_system_fo'] = (int)Configuration::get('PP_MEASUREMENT_SYSTEM_FO');
			$helper->fields_value['poweredby'] = (int)Configuration::get('PP_POWEREDBY');
			$helper->fields_value['template_name_in_catalog'] = (int)Configuration::get('PP_TEMPLATE_NAME_IN_CATALOG');
			$helper->fields_value['show_positions'] = (int)Configuration::get('PP_SHOW_POSITIONS');
			$html .= $this->generateForm($helper, $form);
		}

		$integration = array();
		$modified_files = $this->setupInstance()->checkModifiedFiles();
		$extra_modules = $this->setupInstance()->checkExtraModulesIntegrity(true);
		if (count($this->integration_test_result) == 0)
		{
			$integration['btn_action'] = 'submitIntegration';
			$integration['btn_title'] = $this->l('Perform integration test');
			if ($display)
			{
				$integration['confirmation'] = $this->displayConfirmation($this->l('Integration test completed successfully.'));
				$res = $modified_files;
				$res = array_replace_recursive($res, $extra_modules);
				if (isset($this->integration_test_result_notes))
					$res = array_merge_recursive($res, $this->integration_test_result_notes);
				$integration['display'] = $this->showIntegrationTestResults($res);
			}
		}
		else
		{
			$this->integration_test_result = array_replace_recursive($this->integration_test_result, $modified_files);
			$this->integration_test_result = array_replace_recursive($this->integration_test_result, $extra_modules);
			if (isset($this->integration_test_result_notes))
				$this->integration_test_result = array_merge_recursive($this->integration_test_result, $this->integration_test_result_notes);
			$integration['btn_action'] = 'submitSetup';
			$integration['btn_title'] = $this->l('Run Setup');
			$integration['display'] = $this->showIntegrationTestResults($this->integration_test_result);
			$integration['hasDesc'] = true;
			$integration['_path'] = $this->getPathUri();
		}
		$helper = $this->createTemplate('integration');
		$helper->tpl_vars['integration'] = $integration;
		$helper->tpl_vars['integration_instructions'] = $this->l('Integration Instructions');
		$html .= $helper->generate();
		return $html;
	}

	private function getStatisticsTabHtml($display)
	{
		$helper = $this->createTemplate('statistics');
		$helper->tpl_vars['integrated'] = $this->integrated;
		if ($this->integrated)
		{
			set_time_limit(0);
			if ($display)
			{
				$db = Db::getInstance();
				$templates = PP::getTemplates();

				$statistics = array();
				$used_templates = array();
				$rows = $db->executeS('SELECT count(`id_pp_template`) as count, `id_pp_template` FROM `'._DB_PREFIX_.'product` WHERE `id_pp_template` > 0 group by `id_pp_template`');
				foreach ($rows as $row)
				{
					$statistics[$row['id_pp_template']] = $row['count'];
					$used_templates[$row['id_pp_template']] = $row['id_pp_template'];
				}
				$rows = array();
				foreach ($templates as $template)
				{
					$id_pp_template = $template['id_pp_template'];
					unset($used_templates[$id_pp_template]);
					$row = array();
					$row['id'] = $id_pp_template;
					$row['name'] = $template['name'];
					$row['count'] = (isset($statistics[$id_pp_template]) ? $statistics[$id_pp_template] : 0);
					if ($row['count'] > 0)
					{
						$products = $db->executeS('SELECT p.`id_product`, pl.`name` FROM `'._DB_PREFIX_.'product` p LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.$this->default_language_id.') WHERE p.`id_pp_template` = '.$id_pp_template);
						$row['products'] = $products;
					}
					$rows[] = $row;
				}
				$helper->tpl_vars['existing'] = $rows;

				if (!empty($used_templates))
				{
					$products = $db->executeS('SELECT p.`id_product`, p.`id_pp_template`, pl.`name` FROM `'._DB_PREFIX_.'product` p LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` AND pl.`id_lang` = '.$this->default_language_id.') WHERE p.`id_pp_template` in ('.implode(',', $used_templates).')');
					if (is_array($products) && count($products) > 0)
						$helper->tpl_vars['missing'] = $products;
				}

				$helper->tpl_vars['linkAdminProducts'] = $this->context->link->getAdminLink('AdminProducts');
			}
		}
		else
			$helper->tpl_vars['integration_message'] = $this->getTabIntegrationWarning();

		return $helper->generate();
	}

	private function getEditTemplateTabHtml($templates, $mode, $title)
	{
		if (!$this->integrated) return '';

		if ($mode == 'add')
		{
			$id = 0;
			if ($templates == null)
			{
				foreach ($this->active_languages as $language)
				{
					$template = PP::getProductPropertiesByTemplateId($id);
					$template['name'] = '';
					$template['auto_desc'] = 1;
					$template['description'] = '';
					$templates[$language['id_lang']][$id] = $template;
				}
			}
		}
		else
			$id = (int)Tools::getValue('id');

		if ($templates == null)
			$templates = PP::getAllTemplates();

		$template = $templates[$this->default_language_id][$id];
		$ms = PP::resolveMS($template['pp_bo_measurement_system']);
		$all_properties = $this->getAllProperties($ms);
		$property_types = $this->getPropertyTypes();
		$translations = $this->getTranslations('EditTemplate');

		$buttons = array(array(
							'title' => $this->l('Cancel'),
							'type'  => 'submit',
							'name'  => 'cancelSaveTemplate',
							'icon'  => 'process-icon-cancel',
					));
		$helper = $this->createHelperForm('pp_template_form', $title, 'submitSaveTemplate', 'icon-edit');
		$form = array(
			'input' => array(
				array(
					'type'  => 'div',
					'label' => $this->l('ID'),
					'name'  => $id,
					'class' => 'control-text',
					'condition' => ($mode == 'edit'),
				),
				array(
					'type'  => 'text',
					'label' => $this->l('name'),
					'name'  => 'name_input',
					'lang'  => true,
				),
				array(
					'type'  => 'text',
					'label' => $this->l('description'),
					'name'  => 'description_input',
					'lang'  => true,
					'desc'  => $this->l('leave blank to use auto generated description'),
				),
				array(
					'type'   => 'radio',
					'label'  => $translations['s_pp_qty_policy'],
					'name'   => 'pp_qty_policy',
					'desc'   => $this->l('ordered quantity specifies number of items (pieces, packs, etc.) or one item of the specified number of whole or fractional units (kg, m, ft, etc.)'),
					'values' => array(
						array('id' => 'pp_qty_policy_0', 'value' => 0, 'label' => $translations['s_pp_qty_policy_0']),
						array('id' => 'pp_qty_policy_1', 'value' => 1, 'label' => $translations['s_pp_qty_policy_1']),
						array('id' => 'pp_qty_policy_2', 'value' => 2, 'label' => $translations['s_pp_qty_policy_2']),
						array('id' => 'pp_qty_policy_3', 'value' => 3, 'label' => $translations['s_pp_qty_policy_ext']),
					),
				),
				array(
					'type'   => 'radio',
					'label'  => $translations['s_pp_qty_mode'],
					'name'   => 'pp_qty_mode',
					'desc'   => $this->l('product quantity can be exactly measured or only approximately (the exact amount cannot be ordered) - only if quantity policy is set to units'),
					'values' => array(
						array('id' => 'pp_qty_mode_0', 'value' => 0, 'label' => $translations['s_pp_qty_mode_0']),
						array('id' => 'pp_qty_mode_1', 'value' => 1, 'label' => $translations['s_pp_qty_mode_1']),
					),
				),
				array(
					'type'   => 'radio',
					'label'  => $translations['s_pp_display_mode'],
					'name'   => 'pp_display_mode',
					'desc'   => $translations['s_pp_display_mode_1_long'],
					'values' => array(
						array('id' => 'pp_display_mode_0', 'value' => 0, 'label' => $translations['s_pp_display_mode_0']),
						array('id' => 'pp_display_mode_1', 'value' => 1, 'label' => $translations['s_pp_display_mode_1']),
					),
					'checkboxes' => array(
						array(
							'values' => array(
								'query' => array(
									array(
										'id'   => 'retail_price',
										'name' => $translations['s_pp_display_mode_2'],
										'val'  => '1'
									),
								),
								'id'   => 'id',
								'name' => 'name'
							),
						),
						array(
							'values' => array(
								'query' => array(
									array(
										'id'   => 'base_unit_price',
										'name' => $translations['s_pp_display_mode_4'],
										'val'  => '1'
									),
								),
								'id'   => 'id',
								'name' => 'name'
							),
						),
					),
				),
				array(
					'type'   => 'radio',
					'label'  => $translations['s_pp_price_display_mode'],
					'name'   => 'pp_price_display_mode',
					'desc'   => $this->l('show calculated price separately, display it the position of the product price or hide the calculated price'),
					'values' => array(
						array('id' => 'pp_price_display_mode_0',  'value' => 0,  'label' => $translations['s_pp_price_display_mode_0']),
						array('id' => 'pp_price_display_mode_1',  'value' => 1,  'label' => $translations['s_pp_price_display_mode_1']),
						array('id' => 'pp_price_display_mode_16', 'value' => 16, 'label' => $translations['s_pp_price_display_mode_16']),
					),
				),
				$this->createHelperFormSelect('pp_price_text',
												array('label' => $translations['s_pp_price_text'],
													'desc'  => $this->l('displayed after the product\'s price')),
												self::PROPERTY_TYPE_GENERAL,
												$helper, $template, $all_properties, $property_types),
				$this->createHelperFormSelect('pp_qty_text',
												array('label' => $translations['s_pp_qty_text'],
													'desc'  => $this->l('displayed after the product\'s quantity')),
												self::PROPERTY_TYPE_GENERAL,
												$helper, $template, $all_properties, $property_types),
				$this->createHelperFormSelect('pp_unity_text',
												array('label' => $translations['s_pp_unity_text'],
													'desc'  => $this->l('displayed for products with unit price greater than zero')),
												self::PROPERTY_TYPE_GENERAL,
											$helper, $template, $all_properties, $property_types),
				array(
					'type'  => 'text',
					'label' => $translations['s_pp_unit_price_ratio'],
					'name'  => 'unit_price_ratio',
					'class' => 'fixed-width-xl',
					'desc'  => $this->l('used to auto calculate unit price in product catalog'),
				),
				array(
					'type'  => 'text',
					'label' => $translations['s_pp_minimal_price_ratio'],
					'name'  => 'minimal_price_ratio',
					'class' => 'fixed-width-xl',
					'desc'  => $this->l('used to calculate minimum price for quantity less than the specified threshold'),
				),
				array(
					'type'  => 'text',
					'label' => $translations['s_pp_minimal_quantity'],
					'name'  => 'minimal_quantity',
					'class' => 'fixed-width-xl',
					'desc'  => $this->l('the minimum quantity to buy a product (leave blank to use default)'),
				),
				array(
					'type'  => 'text',
					'label' => $translations['s_pp_default_quantity'],
					'name'  => 'default_quantity',
					'class' => 'fixed-width-xl',
					'desc'  => $this->l('the initial quantity to buy a product (leave blank to use default)'),
				),
				array(
					'type'  => 'text',
					'label' => $translations['s_pp_qty_step'],
					'name'  => 'qty_step',
					'class' => 'fixed-width-xl',
					'desc'  => $this->l('quantity step (leave blank to use default)'),
				),
				$this->createHelperFormSelect('pp_explanation',
												array('label' => $translations['s_pp_explanation']),
												self::PROPERTY_TYPE_BUY_BLOCK_TEXT,
												$helper, $template, $all_properties, $property_types),
				array(
					'type'  => 'text',
					'label' => $this->l('CSS classes'),
					'name'  => 'pp_css',
					'desc'  => $this->l('specify valid CSS classes separated by space (these classes will be added to HTML for products using this template)').'
							   <br/>'.sprintf($this->l('add your classes definitions in the "%s" file'), PSM::normalizePath('themes/'._THEME_NAME_.'/css/modules/pproperties/custom.css')),
				),
				array(
					'type'   => 'radio',
					'label'  => $this->l('display available quantities mode'),
					'name'   => 'pp_bo_qty_available_display',
					'desc'   => $this->l('display available quantities on the product page based on the template configuration (only if enabled in preferences)').
								($template['pp_bo_qty_available_display'] == 0 ? '<br>'.($template['pp_qty_available_display'] == 2 ? $this->l('-- available quantities will be hidden on the product page for current template --') : $this->l('-- available quantities will be displayed on the product page for current template --')) : ''),
					'values' => array(
						array('id' => 'pp_bo_qty_available_display_0', 'value' => 0, 'label' => $this->l('auto')),
						array('id' => 'pp_bo_qty_available_display_1', 'value' => 1, 'label' => $this->l('visible')),
						array('id' => 'pp_bo_qty_available_display_2', 'value' => 2, 'label' => $this->l('hidden')),
					),
				),
				array(
					'type'   => 'radio',
					'label'  => $this->l('measurement system'),
					'name'   => 'pp_bo_measurement_system',
					'desc'   => $this->l('unit measurement system used by this template (default - use measurement system defined in Settings)'),
					'values' => array(
						array('id' => 'pp_bo_measurement_system_0', 'value' => (int)PP::PP_MS_DEFAULT, 'label' => $this->l('default')),
						array('id' => 'pp_bo_measurement_system_1', 'value' => (int)PP::PP_MS_METRIC, 'label' => $this->l('metric')),
						array('id' => 'pp_bo_measurement_system_2', 'value' => (int)PP::PP_MS_NON_METRIC, 'label' => $this->l('non metric')),
					),
				),
				array(
					'type'   => 'radio',
					'label'  => $this->l('visible in catalog'),
					'name'   => 'pp_bo_hidden',
					'desc'   => $this->l('hidden template is not visible in the product catalog, but still used in the shop'),
					'values' => array(
						array('id' => 'pp_bo_hidden_0', 'value' => 0, 'label' => $this->l('visible')),
						array('id' => 'pp_bo_hidden_1', 'value' => 1, 'label' => $this->l('hidden')),
					),
				),
				array(
					'type'  => 'hidden',
					'name'  => 'id',
				),
				array(
					'type'  => 'hidden',
					'name'  => 'mode',
				),
			),
			'buttons' => $buttons,
		);

		$helper->fields_value['id'] = $id;
		$helper->fields_value['mode'] = $mode;
		$helper->fields_value['name_input'] = array();
		$helper->fields_value['description_input'] = array();
		foreach ($this->active_languages as $language)
		{
			$id_lang = $language['id_lang'];
			$helper->fields_value['name_input'][$id_lang] = (isset($templates[$id_lang]) ? PP::safeOutputValue($templates[$id_lang][$id]['name']) : '');
			$helper->fields_value['description_input'][$id_lang] = (isset($templates[$id_lang]) ? PP::safeOutputValue($templates[$id_lang][$id]['auto_desc'] || ($mode == 'copy') ? '' : $templates[$id_lang][$id]['description']) : '');
		}
		$helper->fields_value['pp_qty_mode'] = $template['pp_qty_mode'];
		$helper->fields_value['pp_display_mode'] = (int)(($template['pp_display_mode'] & 1) == 1);
		$helper->fields_value['pp_display_mode_retail_price'] = (int)(($template['pp_display_mode'] & 2) == 2);
		$helper->fields_value['pp_display_mode_base_unit_price'] = (int)(($template['pp_display_mode'] & 4) == 4);
		$helper->fields_value['pp_price_display_mode'] = $template['pp_price_display_mode'];
		$helper->fields_value['unit_price_ratio'] = ((float)$template['pp_unit_price_ratio'] > 0 ? PP::formatQty($template['pp_unit_price_ratio']) : '');
		$helper->fields_value['minimal_price_ratio'] = ((float)$template['pp_minimal_price_ratio'] > 0 ? PP::formatQty($template['pp_minimal_price_ratio']) : '');
		$helper->fields_value['minimal_quantity'] = ((float)$template['pp_minimal_quantity'] > 0 ? PP::formatQty($template['pp_minimal_quantity']) : '');
		$helper->fields_value['default_quantity'] = ((float)$template['pp_default_quantity'] > 0 ? PP::formatQty($template['pp_default_quantity']) : '');
		$helper->fields_value['qty_step'] = ((float)$template['pp_qty_step'] > 0 ? PP::formatQty($template['pp_qty_step']) : '');
		$helper->fields_value['pp_css'] = $template['pp_css'];
		$helper->fields_value['pp_bo_qty_available_display'] = $template['pp_bo_qty_available_display'];
		$helper->fields_value['pp_bo_measurement_system'] = $template['pp_bo_measurement_system'];
		$helper->fields_value['pp_bo_hidden'] = $template['pp_bo_hidden'];

		$dimensions = (isset($template['pp_ext_method']) && isset($template['pp_ext_prop']) ? count($template['pp_ext_prop']) : 0);
		if ($dimensions == 0)
			$value = 0;
		elseif ($dimensions == 1)
			$value = 3;
		else
			$value = $template['pp_ext_method'];

		$helper->fields_value['pp_ext_method'] = $value;
		$helper->fields_value['pp_ext_method_fallback'] = $value;
		$helper->fields_value['pp_ext_policy'] = (isset($template['pp_ext_policy']) ? $template['pp_ext_policy'] : 0);
		if ($template['pp_qty_policy'] == 2 && $helper->fields_value['pp_ext_method'] > 0)
			$helper->fields_value['pp_qty_policy'] = 3;
		else
			$helper->fields_value['pp_qty_policy'] = $template['pp_qty_policy'];

		$translations = $this->getTranslations('ppExt');
		$dimensions_form = array(
			'legend'  => array(
				'title' => $this->l('Dimensions'),
			),
			'multidimensional-feature' => array(
				'text' => $this->l('this feature is disabled if calculation method is not specified'),
				'disabled' => $this->l('This feature is disabled. To enable this feature please install the multidimensional plugin from'),
				'readme_url' => ($this->multidimensional_plugin ? $this->multidimensional_plugin->readme_url() : ''),
				'readme_pdf' => $this->l('Multidimensional plugin user guide'),
			),
			'input' => array(
				array(
					'type'  => 'select',
					'label' => $this->l('calculation method'),
					'name'  => 'pp_ext_method',
					'options' => array(
						'query' => array(
							array('id' => 0, 'name' => '&nbsp;'),
							array('id' => 1, 'name' => $translations['s_multiplication']),
							array('id' => 2, 'name' => $translations['s_summation']),
							array('id' => 3, 'name' => $translations['s_single_dimension']),
						),
						'id' => 'id', 'name' => 'name'
					),
				),
				array(
					'type'  => 'hidden',
					'name'  => 'pp_ext_method_fallback',
				),
				$this->createHelperFormSelect('pp_ext_title',
												array('label' => $this->l('dimensions block title'),
													'form_group_class' => 'dimensions-toggle'),
												self::DIMENSIONS,
												$helper, $template, $all_properties, $property_types),
				$this->createHelperFormSelect('pp_ext_property',
												array('label' => $this->l('calculation result label'),
													'desc'  => $this->l('leave blank to hide calculation result'),
													'form_group_class' => 'dimensions-toggle'),
												self::DIMENSIONS,
												$helper, $template, $all_properties, $property_types),
				$this->createHelperFormSelect('pp_ext_text',
												array('label' => $this->l('calculation result text'),
													'form_group_class' => 'dimensions-toggle'),
												array(self::PROPERTY_TYPE_GENERAL, self::DIMENSIONS),
												$helper, $template, $all_properties, $property_types),
				array(
					'type'	=> 'radio',
					'label'	=> $this->l('dimensions policy'),
					'name'	=> 'pp_ext_policy',
					'desc'	=> $this->l('dimensions can be specified by the customer (default) or used by the packs calculator or used as the product properties affecting price, visible in the shop and editable only in the back office'),
					'form_group_class' => 'dimensions-toggle',
					'values' => array(
						array('id' => 'pp_ext_policy_0', 'value' => 0, 'label' => $this->l('default')),
						array('id' => 'pp_ext_policy_1', 'value' => 1, 'label' => $this->l('packs calculator')),
						array('id' => 'pp_ext_policy_2', 'value' => 2, 'label' => $this->l('product properties')),
					),
				),
				/*
				array(
					'type'	=> 'text',
					'label'	=> $this->l('Show in position'),
					'name'	=> 'pp_ext_show_position',
					'class'	=> 'pp_ext_show_position',
					'desc'	=> $this->l('specify display position of the dimensions block on the product\'s page'),
					'hint'	=> $this->l('To show position names enable "Display positions" options in the "Settings" tab.'),
				),
				*/
			),
			'buttons' => $buttons,
		);

		$dimensions_form['dimensions-table'] = array(
			'th'    => array(
							$this->l('dimension'),
							$this->l('quantity text *'),
							$this->l('minimum quantity'),
							$this->l('maximum quantity'),
							$this->l('default quantity'),
							$this->l('quantity step'),
							$this->l('quantity ratio'),
							$this->l('order quantity text **'),
						),
			'tbody' => array(),
		);

		$max_dimensions = (isset($template['pp_ext_prop']) ? count($template['pp_ext_prop']) : 3);
		if ($max_dimensions < 3)
			$max_dimensions = 3;
		for ($dimension_index = 1; $dimension_index <= $max_dimensions; $dimension_index++)
		{
			$td = array();
			$value = PP::getTemplateExtProperty($template, $dimension_index, 'property');
			$td[] = $this->createHelperFormSelect(
				'dimension_'.$dimension_index,
				array('data_type' => 'dimension_', 'data_position' => $dimension_index),
				self::PROPERTY_TYPE_EXT,
				$helper, $value, $all_properties, $property_types
			);

			$value = PP::getTemplateExtProperty($template, $dimension_index, 'text');
			$td[] = $this->createHelperFormSelect(
				'dimension_text_'.$dimension_index,
				array('data_type' => 'dimension_text_', 'data_position' => $dimension_index),
				array(self::PROPERTY_TYPE_GENERAL, self::DIMENSIONS),
				$helper, $value, $all_properties, $property_types
			);

			$td[] = array('type' => 'text', 'name' => 'dimension_minimum_quantity_'.$dimension_index, 'data_type' => 'dimension_minimum_quantity_', 'data_position' => $dimension_index);
			$td[] = array('type' => 'text', 'name' => 'dimension_maximum_quantity_'.$dimension_index, 'data_type' => 'dimension_maximum_quantity_', 'data_position' => $dimension_index);
			$td[] = array('type' => 'text', 'name' => 'dimension_default_quantity_'.$dimension_index, 'data_type' => 'dimension_default_quantity_', 'data_position' => $dimension_index);
			$td[] = array('type' => 'text', 'name' => 'dimension_qty_step_'.$dimension_index, 'data_type' => 'dimension_qty_step_', 'data_position' => $dimension_index);
			$td[] = array('type' => 'text', 'name' => 'dimension_qty_ratio_'.$dimension_index, 'data_type' => 'dimension_qty_ratio_', 'data_position' => $dimension_index);

			$helper->fields_value['dimension_minimum_quantity_'.$dimension_index] = ((float)PP::getTemplateExtProperty($template, $dimension_index, 'minimum_quantity') > 0 ? PP::formatQty(PP::getTemplateExtProperty($template, $dimension_index, 'minimum_quantity')) : '');
			$helper->fields_value['dimension_maximum_quantity_'.$dimension_index] = ((float)PP::getTemplateExtProperty($template, $dimension_index, 'maximum_quantity') > 0 ? PP::formatQty(PP::getTemplateExtProperty($template, $dimension_index, 'maximum_quantity')) : '');
			$helper->fields_value['dimension_default_quantity_'.$dimension_index] = ((float)PP::getTemplateExtProperty($template, $dimension_index, 'default_quantity') > 0 ? PP::formatQty(PP::getTemplateExtProperty($template, $dimension_index, 'default_quantity')) : '');
			$helper->fields_value['dimension_qty_step_'.$dimension_index] = ((float)PP::getTemplateExtProperty($template, $dimension_index, 'qty_step') > 0 ? PP::formatQty(PP::getTemplateExtProperty($template, $dimension_index, 'qty_step')) : '');
			$helper->fields_value['dimension_qty_ratio_'.$dimension_index] = ((float)PP::getTemplateExtProperty($template, $dimension_index, 'qty_ratio') > 0 ? PP::formatQty(PP::getTemplateExtProperty($template, $dimension_index, 'qty_ratio')) : '');

			$value = PP::getTemplateExtProperty($template, $dimension_index, 'order_text');
			$td[] = $this->createHelperFormSelect(
				'dimension_order_text_'.$dimension_index,
				array('data_type' => 'dimension_order_text_', 'data_position' => $dimension_index),
				array(self::PROPERTY_TYPE_GENERAL, self::DIMENSIONS),
				$helper, $value, $all_properties, $property_types
			);

			$dimensions_form['dimensions-table']['tbody'][] = array('tr' => array('td' => $td));
		}

		$dimensions_form['help-block'] = array('class' => 'dimensions-toggle', 'text' => array(
			'*&nbsp;&nbsp;&nbsp;'.$this->l('quantity text is used on the product page in the shop'),
			'**&nbsp;&nbsp;'.$this->l('order quantity text is used in order and invoice')
			)
		);

		if ($this->multidimensional_plugin)
			$dimensions_form['multidimensionalAdmin'] = 'multidimensionalAdmin';

		$forms = array('form' => $form, 'dimensions_form' => $dimensions_form);
		$hook_forms = Hook::exec('ppropertiesAdmin', array('mode' => 'displayEditTemplateForm', 'id_pp_template' => $id), null, true);
		if (is_array($hook_forms))
		{
			foreach ($hook_forms as $hook_module => $hook_form)
				if (isset($hook_form['form']))
				{
					if (!isset($hook_form['form']['buttons']))
						$hook_form['form']['buttons'] = $buttons;
					$forms[$hook_module.'_form'] = $hook_form['form'];
				}
		}
		$html = $this->generateForm($helper, $forms,
									array('id_pp_template' => $id, 'multidimensional' => $this->multidimensional_plugin, 'script' => array('multidimensional')));
		return $html;
	}

	private function getEditPropertyTabHtml($properties, $mode, $title)
	{
		if (!$this->integrated) return '';

		$type = (int)Tools::getValue('type');
		if ($mode == 'add')
		{
			$id = 0;
			if ($properties == null)
			{
				foreach ($this->active_languages as $language)
				{
					$property = array();
					$property['id_pp_property'] = $id;
					$property['type'] = $type;
					$property['text'] = '';
					$properties[$language['id_lang']][$id] = $property;
				}
			}
		}
		else
		{
			$id = (int)Tools::getValue('id');
			if ($properties == null)
				$properties = $this->getAllProperties();
		}

		$helper = $this->createHelperForm('pp_property_form', $title, 'submitSaveProperty', 'icon-edit');
		$form = array(
			'input' => array(
				array(
					'label' => $this->l('ID'),
					'type'  => 'div',
					'name'  => $id,
					'class' => 'control-text',
					'condition' => ($mode == 'edit'),
				),
				array(
					'label' => $this->l('Text'),
					'type'  => 'text',
					'name'  => 'text_input',
					'lang'  => true,
					'desc'  => (PP::resolveMS() != 2 ? $this->l('metric (to edit non metric value change measurement system in Settings)') : $this->l('non metric (to edit metric value change measurement system in Settings)')),
				),
				array(
					'type'  => 'hidden',
					'name'  => 'id',
				),
				array(
					'type'  => 'hidden',
					'name'  => 'mode',
				),
				array(
					'type'  => 'hidden',
					'name'  => 'type',
				),
			),
			'buttons' => array(
				array(
					'title' => $this->l('Cancel'),
					'type'  => 'submit',
					'name'  => 'cancelSaveProperty',
					'icon'  => 'process-icon-cancel',
				),
			),
		);
		$helper->fields_value['id'] = $id;
		$helper->fields_value['mode'] = $mode;
		$helper->fields_value['type'] = $type;
		$helper->fields_value['text_input'] = array();

		foreach ($properties[$this->default_language_id] as $id_pp_property => $property)
		{
			if ($id_pp_property == $id)
			{
				foreach ($this->active_languages as $language)
				{
					$id_lang = $language['id_lang'];
					$helper->fields_value['text_input'][$id_lang] = PP::safeOutputValue($properties[$id_lang][$id]['text']);
				}
				break;
			}
		}

		return $this->generateForm($helper, $form);
	}

	private function getTabIntegrationWarning()
	{
		return $this->l('Please go to the "Settings" tab and resolve the integration problems.');
	}

	private function showIntegrationTestResults($results)
	{
		foreach ($results as &$value)
			if (is_array($value))
				asort($value);
		return $results;
	}

	private function tplVars()
	{
		$token = Tools::getAdminTokenLite('AdminModules');
		$current = AdminController::$currentIndex.'&configure='.$this->name;
		return array(
			'_PS_ADMIN_IMG_' => _PS_ADMIN_IMG_,
			'current' => $current,
			'currenturl' => $current.'&token='.$token.'&pp=1&',
			'token' => $token,
		);
	}

	private function createTemplate($name)
	{
		$helper = new Helper();
		$helper->module = $this;
		$helper->base_folder = 'pproperties/';
		$helper->base_tpl = $name.'.tpl';
		$helper->setTpl($helper->base_tpl);

		$token = Tools::getAdminTokenLite('AdminModules');
		$current = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->tpl_vars['_PS_ADMIN_IMG_'] = _PS_ADMIN_IMG_;
		$helper->tpl_vars['current'] = $current;
		$helper->tpl_vars['currenturl'] = $current.'&token='.$token.'&pp=1&';
		$helper->tpl_vars['token'] = $token;
		return $helper;
		// $tpl = $this->context->smarty->createTemplate(dirname(__FILE__).'/tpl/admin/'.$name.'.tpl');
		// $tpl = $this->context->controller->createTemplate($name.'.tpl');
		// $tpl->assign($this->tplVars());
		// return $tpl;
	}

	private function createHelperForm($id_form, $form_title, $submit_action, $icon = null)
	{
		static $first_call = true;
		$helper = new HelperForm();
		$helper->first_call = $first_call;
		$first_call = false;
		$helper->module = $this;
		$helper->title = $this->displayName;
		$helper->name_controller = $this->name;
		$helper->base_tpl = 'pproperties_form.tpl';
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->languages = $this->active_languages;
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->default_form_language = $this->default_language_id;
		$helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
		$helper->toolbar_scroll = true;
		$helper->submit_action = '';
		$helper->id_form = $id_form;
		$helper->pp_form = array(
			'legend'  => array(
				'title' => $form_title,
			),
			'submit' => array(
				'title' => $this->l('Save'),
				'id'    => $id_form.'_submit_btn',
				'name'  => $submit_action,
				'class' => 'btn btn-default pull-right pp-action-btn'
			),
		);
		if ($icon !== null)
			$helper->pp_form['legend']['icon'] = $icon;

		return $helper;
	}

	private function createHelperFormSelect($name, $data, $type, $helper, $template, $all_properties, $property_types)
	{
		if ($type !== false && !is_array($type))
			$type = array($type);
		$options = array();
		$helper->fields_value[$name] = 0;
		$options[] = array('id' => 0, 'name' => '&nbsp;');
		foreach ($all_properties[$this->default_language_id] as $id => $prop)
		{
			if ($type === false || in_array($property_types[$id], $type))
			{
				$options[] = array(
					'id'   => $id,
					'name' => PP::safeOutputValue($prop['text'])
				);
				if (is_array($template))
				{
					if (isset($template[$name]) && $template[$name] == $prop['text'])
						$helper->fields_value[$name] = $id;
				}
				else
				{
					if ($template == $prop['text'])
						$helper->fields_value[$name] = $id;
				}
			}
		};
		$select = array(
			'type' => 'select',
			'name' => $name,
			'options' => array('query' => $options, 'id' => 'id', 'name' => 'name'),
		);
		if (is_array($data))
			foreach ($data as $key => $value)
				$select[$key] = $value;
		return $select;
	}

	private function generateForm($helper, $form, $tpl_vars = null)
	{
		$vars = array('form' => array());
		if (!isset($form['form']))
			$form = array('form' => $form);
		if (!isset($form['form']['id_form']) && isset($helper->id_form))
			$form['form']['id_form'] = $helper->id_form;
		foreach ($form as $key => $f)
		{
			$vars[$key] = array('form' => array_merge($helper->pp_form, $f));
			if (isset($vars[$key]['form']['buttons']) && $vars[$key]['form']['buttons'] === false)
			{
				unset($vars[$key]['form']['buttons']);
				unset($vars[$key]['form']['submit']);
			}
		}
		if (is_array($tpl_vars))
			foreach ($tpl_vars as $key => $value)
				$vars['form'][$key] = $value;

		$vars['form']['_PS_ADMIN_IMG_'] = _PS_ADMIN_IMG_;
		$vars['form']['form']['input'][] = array('type' => 'hidden', 'name' => 'pp');
		$helper->fields_value['pp'] = 1;
		return $helper->generateForm($vars);
	}

	private function saveTemplate()
	{
		$result = array();
		$result['error'] = '';
		$mode = Tools::getValue('mode');
		if ($mode == 'add')
			$id = 0;
		elseif ($mode == 'copy')
			$id = (int)Tools::getValue('id');
		else
		{
			$mode = 'edit';
			$id = (int)Tools::getValue('id');
		}
		if ($id < 0)
			return $result;

		$errors = array();
		$templates = array();

		$template_properties = array();
		$template_properties['pp_explanation'] = (int)Tools::getValue('pp_explanation');
		$template_properties['pp_price_text'] = (int)Tools::getValue('pp_price_text');
		$template_properties['pp_qty_text'] = (int)Tools::getValue('pp_qty_text');
		$template_properties['pp_unity_text'] = (int)Tools::getValue('pp_unity_text');

		$ext_method = (int)Tools::getValue($this->multidimensional_plugin ? 'pp_ext_method' : 'pp_ext_method_fallback');
		if ($ext_method == 3) $ext_method = 2;
		$display_mode = ((int)Tools::getValue('pp_display_mode') != 0 ? 1 : 0);
		if ((int)Tools::getValue('pp_display_mode_retail_price', 0) > 0)
			$display_mode += 2;
		if ((int)Tools::getValue('pp_display_mode_base_unit_price', 0) > 0)
			$display_mode += 4;
		$price_display_mode = (int)Tools::getValue('pp_price_display_mode');
		if (!in_array($price_display_mode, array(0, 1, 16)))
			$price_display_mode = 0;
		$hidden = ((int)Tools::getValue('pp_bo_hidden', 0) == 1 ? 1 : 0);
		$pp_bo_qty_available_display = (int)Tools::getValue('pp_bo_qty_available_display');
		if (!in_array($pp_bo_qty_available_display, array(0, 1, 2)))
			$pp_bo_qty_available_display = 0;
		$measurement_system = (int)Tools::getValue('pp_bo_measurement_system');
		$unit_price_ratio = $this->getFloatValue('unit_price_ratio');
		$minimal_price_ratio = $this->getFloatValue('minimal_price_ratio');

		$qty_policy = (int)Tools::getValue('pp_qty_policy', 0);
		$qty_policy = ($qty_policy == 3 ? 2 : $qty_policy);
		if ($ext_method > 0)
		{
			$ext_policy = (int)Tools::getValue('pp_ext_policy', 0);
			if (!in_array($ext_policy, array(0, 1, 2)))
				$ext_policy = 0;
			$qty_policy = ($ext_policy == 1 ? 0 : 2);
		}
		$qty_mode = ($qty_policy ? ((int)Tools::getValue('pp_qty_mode') != 0 ? 1 : 0) : 0);
		$minimal_quantity = ($qty_policy == 2 ? $this->getFloatValue('minimal_quantity') : (int)Tools::getValue('minimal_quantity'));
		$default_quantity = ($qty_policy == 2 ? $this->getFloatValue('default_quantity') : (int)Tools::getValue('default_quantity'));
		$qty_step         = ($qty_policy == 2 ? $this->getFloatValue('qty_step')         : (int)Tools::getValue('qty_step'));

		$ms = PP::resolveMS($measurement_system);
		foreach ($this->active_languages as $language)
		{
			$id_lang = $language['id_lang'];

			$template = array();
			$data = array();
			$data['id_pp_template']        = $id;
			$data['qty_policy']            = $qty_policy;
			$data['qty_mode']              = $qty_mode;
			$data['display_mode']          = $display_mode;
			$data['price_display_mode']    = $price_display_mode;
			$data['measurement_system']    = $measurement_system;
			$data['unit_price_ratio']      = $unit_price_ratio;
			$data['minimal_price_ratio']   = $minimal_price_ratio;
			$data['minimal_quantity']      = $minimal_quantity;
			$data['default_quantity']      = $default_quantity;
			$data['qty_step']              = $qty_step;
			$data['ext']                   = ($ext_method > 0 ? 1 : 0);
			$data['qty_available_display'] = $pp_bo_qty_available_display;
			$data['hidden']                = $hidden;
			$data['css']                   = Tools::getValue('pp_css');
			$data['template_properties']   = $template_properties;
			PP::calcProductProperties($template, $data);

			$this->getValue($template, 'name', $this->l('name:'), $errors, $id_lang);
			$template['description'] = Tools::getValue('description_input_'.$id_lang);
			$templates[$id_lang][$id] = $template;
		}

		if (count($errors) == 0)
		{
			$db = Db::getInstance();
			if ($mode == 'edit')
				$id_pp_template = $id;
			else
			{
				$id_pp_template = $this->getNextId($db, 'pp_template', 'id_pp_template');
				$db->execute('INSERT INTO `'._DB_PREFIX_.'pp_template` (id_pp_template, version) VALUE ('.$id_pp_template.', 0)');
				foreach ($this->active_languages as $language)
					$templates[$language['id_lang']][$id]['id_pp_template'] = $id_pp_template;
			}
			$db->autoExecute(_DB_PREFIX_.'pp_template', array (
										'version'               => PP::PP_TEMPLATE_VERSION,
										'qty_policy'            => $template['pp_qty_policy'],
										'qty_mode'              => $template['pp_qty_mode'],
										'display_mode'          => $template['pp_display_mode'],
										'price_display_mode'    => $template['pp_price_display_mode'],
										'measurement_system'    => $template['pp_bo_measurement_system'],
										'unit_price_ratio'      => $template['pp_unit_price_ratio'],
										'minimal_price_ratio'   => $template['pp_minimal_price_ratio'],
										'minimal_quantity'      => $template['db_minimal_quantity'],
										'default_quantity'      => $template['db_default_quantity'],
										'qty_step'              => $template['db_qty_step'],
										'ext'                   => $template['pp_ext'],
										'qty_available_display' => $template['pp_bo_qty_available_display'],
										'hidden'                => $template['pp_bo_hidden'],
										'css'                   => $template['pp_css']),
								'UPDATE', 'id_pp_template = '.$id_pp_template);
			$db->delete(_DB_PREFIX_.'pp_template_property', 'id_pp_template = '.$id_pp_template);
			array_walk($template_properties, create_function('&$value, $key, $id_pp_template', '$value = "(".$id_pp_template.",\'".$key."\',".$value.")";'), $id_pp_template);
			$db->execute('INSERT INTO '._DB_PREFIX_.'pp_template_property (id_pp_template,pp_name,id_pp_property) VALUES '.implode(',', $template_properties));
			foreach ($this->active_languages as $language)
			{
				$id_lang = $language['id_lang'];
				$template = $templates[$id_lang][$id];
				$r = $db->getRow('SELECT * FROM `'._DB_PREFIX_.'pp_template_lang` WHERE id_pp_template = '.$id_pp_template.' AND id_lang='.$id_lang);
				if ($r === false)
					$r = array('description_1' => '', 'description_2' => '', 'id_pp_template' => $id_pp_template, 'id_lang' => $id_lang);
				$auto_desc = 0;
				if ($template['description'] == '')
				{
					$auto_desc = 1;
					$template['description'] = self::generateDescription($template, $id_lang);
				}
				$r[$ms != 2 ? 'description_1' : 'description_2'] = pSQL($template['description'], true);
				$r[$ms != 2 ? 'auto_desc_1' : 'auto_desc_2'] = $auto_desc;
				$r['name'] = pSQL($template['name'], true);
				$db->delete(_DB_PREFIX_.'pp_template_lang', 'id_pp_template = '.$id_pp_template.' AND id_lang='.$id_lang);
				$db->autoExecute(_DB_PREFIX_.'pp_template_lang', $r, 'INSERT');
			}

			$db->delete(_DB_PREFIX_.'pp_template_ext', 'id_pp_template = '.$id_pp_template);
			$db->delete(_DB_PREFIX_.'pp_template_ext_prop', 'id_pp_template = '.$id_pp_template);
			if ($template['pp_ext'] == 1)
			{
				$ext_title = (int)Tools::getValue('pp_ext_title', 0);
				$ext_property = (int)Tools::getValue('pp_ext_property', 0);
				$ext_text = (int)Tools::getValue('pp_ext_text', 0);
				//$ext_show_position = (int)Tools::getValue('pp_ext_show_position', 0);
				$s = (string)$id_pp_template;
				$s .= ',1'; // type 1: quantity calculation based on dimensions
				$s .= ','.$ext_policy;
				$s .= ','.$ext_method;
				$s .= ','.$ext_title;
				$s .= ','.$ext_property;
				$s .= ','.$ext_text;
				//$s .= ','.$ext_show_position;
				$db->execute('INSERT INTO '._DB_PREFIX_.'pp_template_ext (id_pp_template,type,policy,method,title,property,text) VALUES ('.$s.')');

				if ($this->multidimensional_plugin)
					$this->multidimensional_plugin->saveTemplate($id_pp_template, self::DIMENSIONS);
			}
			Hook::exec('ppropertiesAdmin', array('mode' => 'actionTemplateSave', 'id_pp_template' => $id));
			$templates = null;
			PP::resetTemplates();
		}
		else
		{
			$result['error'] .= $this->l('Please fix the following errors:');
			foreach ($errors as $error)
			{
				$result['error'] .= '
				<div>'.$error.'</div>';
			}
		}
		$result['templates'] = $templates;

		return $result;
	}

	private function saveProperty()
	{
		$result = array();
		$result['error'] = '';
		$mode = Tools::getValue('mode');
		$type = Tools::getValue('type');
		if ($mode == 'add')
			$id = 0;
		else
		{
			$mode = 'edit';
			$id = (int)Tools::getValue('id');
		}
		if ($id < 0)
			return $result;

		$type = Tools::getValue('type');
		$errors = array();
		$properties = array();

		foreach ($this->active_languages as $language)
		{
			$id_lang = $language['id_lang'];

			$property = array();
			$this->getValue($property, 'text', $this->l('Text:'), $errors, $id_lang);

			$properties[$id_lang][$id] = $property;
		}

		if (count($errors) == 0)
		{
			$db = Db::getInstance();
			if ($mode == 'edit')
				$id_pp_property = $id;
			else
			{
				$id_pp_property = $this->getNextId($db, 'pp_property', 'id_pp_property');
				$db->execute('INSERT INTO `'._DB_PREFIX_.'pp_property` (id_pp_property, type) VALUE ('.$id_pp_property.', '.$type.')');
				foreach ($this->active_languages as $language)
					$properties[$language['id_lang']][$id]['id_pp_property'] = $id_pp_property;
			}
			foreach ($this->active_languages as $language)
			{
				$id_lang = $language['id_lang'];
				$r = $db->getRow('SELECT * FROM `'._DB_PREFIX_.'pp_property_lang` WHERE id_pp_property = '.$id_pp_property.' AND id_lang='.$id_lang);
				if ($r === false)
					$r = array ('text_1' => '', 'text_2' => '', 'id_pp_property' => $id_pp_property, 'id_lang' => $id_lang);
				$property = $properties[$id_lang][$id];
				$text = pSQL($property['text'], true);
				if (PP::resolveMS() != 2)
				{
					$r['text_1'] = $text;
					if ($r['text_2'] == '')
						$r['text_2'] = $text;
				}
				else
				{
					$r['text_2'] = $text;
					if ($r['text_1'] == '')
						$r['text_1'] = $text;
				}
				$db->delete(_DB_PREFIX_.'pp_property_lang', 'id_pp_property = '.$id_pp_property.' AND id_lang='.$id_lang);
				if ($r['text_1'] != '' || $r['text_2'] != '')
					$db->autoExecute(_DB_PREFIX_.'pp_property_lang', $r, 'INSERT');
			}
		}
		else
		{
			$result['error'] .= $this->l('Please fix the following errors:');
			foreach ($errors as $error)
			{
				$result['error'] .= '
				<div>'.$error.'</div>';
			}
		}
		$result['properties'] = $properties;

		return $result;
	}

	private function deleteTemplate()
	{
		$id = (int)Tools::getValue('id');
		if ($id <= 0)
			return;

		$db = Db::getInstance();
		$db->delete(_DB_PREFIX_.'pp_template', 'id_pp_template = '.$id);
		$db->delete(_DB_PREFIX_.'pp_template_lang', 'id_pp_template = '.$id);
		$db->delete(_DB_PREFIX_.'pp_template_property', 'id_pp_template = '.$id);
	}

	private function deleteProperty()
	{
		$id = (int)Tools::getValue('id');
		if ($id > 0)
		{
			$db = Db::getInstance();
			$db->delete(_DB_PREFIX_.'pp_property', 'id_pp_property = '.$id);
			$db->delete(_DB_PREFIX_.'pp_property_lang', 'id_pp_property = '.$id);
			$db->delete(_DB_PREFIX_.'pp_template_property', 'id_pp_property = '.$id);
		}
	}

	private function changeHiddenStatus()
	{
		$id = (int)Tools::getValue('id');
		if ($id <= 0)
			return;

		Db::getInstance()->AutoExecute(_DB_PREFIX_.'pp_template',
			array('hidden' => ((int)Tools::getValue('show', 1) ? '0' : '1')),
			'UPDATE', '`id_pp_template` = '.$id);
	}

	private function generateDescription($template, $id_lang)
	{
		$desc = '';
		if ($template['pp_qty_policy'] == 1)
			$desc .= $this->l('Product sold in whole units', false, $id_lang);
		elseif ($template['pp_qty_policy'] == 2)
		{
			if ($template['pp_ext'] > 0)
				$desc .= $this->l('Product uses multidimensional feature', false, $id_lang);
			else
				$desc .= $this->l('Product sold in fractional units', false, $id_lang);
		}
		else
			$desc .= $this->l('Product sold in items', false, $id_lang);
		if ($template['pp_qty_mode'] && !PP::qtyPolicyLegacy($template['pp_qty_policy']))
			$desc .= ', '.$this->l('approximate quantity and price (the exact quantity cannot be ordered)', false, $id_lang);
		switch ($template['pp_display_mode'])
		{
			case 1:
				$desc .= ', '.$this->l('reversed price display', false, $id_lang);
				break;
			case 2:
				$desc .= ', '.$this->l('retail price', false, $id_lang);
				break;
			case 3:
				$desc .= ', '.$this->l('retail price', false, $id_lang).', '.$this->l('reversed price display', false, $id_lang);
				break;
			default:
				break;
		}
		return $desc;
	}

	private function getValue(&$template, $key, $name, &$errors, $id_lang)
	{
		$default_value = Tools::getValue($key.'_input_'.$this->default_language_id);
		if (empty($default_value))
		{
			$default_language = Language::getLanguage($this->default_language_id);
			$errors[$key] = $name.' '.$this->l('cannot be empty in').' '.$default_language['name'];
		}
		$template[$key] = Tools::getValue($key.'_input_'.$id_lang);
	}

	private function getFloatValue($key)
	{
		$value = Tools::getValue($key);
		return (float)(empty($value) ? '0' : str_replace(',', '.', Tools::getValue($key)));
	}

	private function getPropertyTypes()
	{
		$result = array();
		$rows = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'pp_property`');
		foreach ($rows as $row)
			$result[$row['id_pp_property']] = $row['type'];
		return $result;
	}

	private function getAllProperties($ms = false)
	{
		$ms = PP::resolveMS($ms);
		$all_properties = array();
		$db = Db::getInstance();
		$rows = $db->executeS('SELECT * FROM `'._DB_PREFIX_.'pp_property_lang`');
		$pp_property = $db->executeS('SELECT * FROM `'._DB_PREFIX_.'pp_property`');
		foreach ($this->active_languages as $language)
		{
			$id_lang = $language['id_lang'];
			$properties = array();
			foreach ($pp_property as $property)
			{
				$id_pp_property = $property['id_pp_property'];
				$property['text'] = '';
				$property['text_1'] = '';
				$property['text_2'] = '';
				$found = $this->getAllPropertiesLang($property, $rows, $id_pp_property, $id_lang, $ms);
				if (!$found)
					$this->getAllPropertiesLang($property, $rows, $id_pp_property, 1, $ms);
				$properties[$id_pp_property] = $property;
			}
			$all_properties[$id_lang] = $properties;
		}
		return $all_properties;
	}

	private function getAllPropertiesLang(&$property, $rows, $id_pp_property, $id_lang, $ms = false)
	{
		foreach ($rows as $row)
		{
			if (($row['id_pp_property'] == $id_pp_property) && ($row['id_lang'] == $id_lang))
			{
				$property['text'] = ($ms != 2 ? $row['text_1'] : $row['text_2']);
				$property['text_1'] = $row['text_1'];
				$property['text_2'] = $row['text_2'];
				return true;
			}
		}
		return false;
	}

	public function integrationKey()
	{
		return _PS_VERSION_.'|'.$this->integrationVersion();
	}

	public function integrationVersion()
	{
		return $this->ps_versions_compliancy['max'];
	}

	public function setupInstance()
	{
		return psmPPsetup($this);
	}

	public function plugins()
	{
		return array('ppropertiesmultidimensional' => 1.6, 'ppropertiessmartprice' => 1.1);
	}

	private function getNextId($db, $table, $column)
	{
		$max_id = (int)$db->getValue('SELECT max(`'.$column.'`) FROM `'._DB_PREFIX_.$table.'`');
		if ($max_id < self::USER_START_ID)
			return self::USER_START_ID;
		return ++$max_id;
	}

	private function compatibilityText()
	{
		if ($this->ps_versions_compliancy['min'] == $this->ps_versions_compliancy['max'])
			return sprintf($this->l('This version of %s module works only with PrestaShop version %s.'), $this->displayName, $this->ps_versions_compliancy['min']);
		else
			return sprintf($this->l('This version of %s module works only with PrestaShop versions %s - %s.'), $this->displayName, $this->ps_versions_compliancy['min'], $this->ps_versions_compliancy['max']);
	}
}
