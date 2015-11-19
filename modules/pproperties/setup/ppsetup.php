<?php
/**
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
*/

if (!defined('_PS_VERSION_'))
	exit;

class PPSetupEx extends PPSetup
{
	public function setup()
	{
		if (!$this->install_mode)
			$this->processMailFiles(false);
		return $this->processFiles(true);
	}

	public function checkIntegrity()
	{
		$this->processMailFiles(true);
		return $this->processFiles(false);
	}

	private function processFiles($replace)
	{
		$themefiles = array(
			array(
				'files'  => array('product.tpl'),
				'replace'=> array(
								array(
									'{if $errors|@count == 0}',
									'{if $errors|@count == 0 }{$pproperties = $product->productProperties()}{if (isset($quantity_discounts) && count($quantity_discounts) > 0)}{$ppDiscounts=true}{else}{$ppDiscounts=false}{/if}{hook h="displayPpropertiesProduct" product=$product ppDiscounts=$ppDiscounts}'
								),
								array(
									'($display_qties == 1',
									'($display_qties  ==  1 && $pproperties.pp_qty_available_display != 2'
								),
								array(
									'{if $PS_STOCK_MANAGEMENT}',
									'{if $PS_STOCK_MANAGEMENT && $pproperties.pp_qty_available_display != 2}'
								),
								array(
									'data-discount-quantity="{$quantity_discount.quantity|intval}"',
									'data-discount-quantity="{$quantity_discount.quantity|floatval}"',
									'count' => -1
								),
								array(
									'{$quantity_discount.quantity|intval}',
									'{$quantity_discount.quantity|formatQty}'
								),
							)
			),
			array(
				'files'  => array('product-list.tpl'),
				'replace'=> array(
								array('$product.quantity > 0', '($product.quantity + $product.quantity_remainder) > 0'),
								array('<li class="ajax_block_product', '<li class="{$product|pp:css:right}ajax_block_product', 'count' => -1),
							)
			),
			array(
				'files'  => array('products-comparison.tpl'),
				'replace'=> array(
								array('{convertPrice price=$unit_price}', '{convertPrice price=$unit_price m=unit_price}'),
							)
			),
			array(
				'files'  => array('js/product.js'),
				'replace'=> array(
								array('parseInt(combinations[i][\'minimal_quantity\']);', 'parseFloat(combinations[i][\'minimal_quantity\']);'),
								array('if (!noTaxForThisProduct || !customerGroupWithoutTax)', 'if (!noTaxForThisProduct && !customerGroupWithoutTax)', 'count' => -1, 'uninstall' => false),
							)
			),
		);

		$smartysysplugins = array(
			array(
				'files'  => array('smarty_internal_compile_foreach.php'),
				'replace'=> array(
								array('return $output;', 'return $output.PP::smartyCompile(\'foreach\', \'open\', $item, $compiler->smarty);'),
								array('return "<?php } ?>";', 'return "<?php } ?>".PP::smartyCompile(\'foreach\', \'close\', $item, $compiler->smarty);'),
							)
			),
		);

		$root = array(
			array(
				'files'  => array('pdf/delivery-slip.tpl', 'pdf/order-slip.tpl'),
				'replace'=> array(
								array('{foreach $order_details as $order_detail}', '{foreach $order_details as $order_detail }{ppAssign order=$order_detail pdf=true}'),
								array('{$order_detail.product_name}', '{displayProductName name=$order_detail.product_name}'),
								array('{$order_detail.product_quantity}', '{displayQty quantity=$order_detail.product_quantity}{ppAssign}'),
							)
			),
			array(
				'files'  => array('classes/module/Module.php'),
				'replace'=> array(
								array(
									'return implode(\'|\', $cache_array);',
									'if (PP::isMeasurementSystemFOActivated()) $cache_array[] = PP::resolveMS(); return implode(\'|\', $cache_array) ;'
								),
							)
			),
			array(
				'files'  => array('classes/Product.php'),
				'replace'=> array(
								array(
									'static $context = null;',
									'static $context = null ; $qty = $quantity; if (is_array($quantity)) {$quantity = PP::resolveQty($qty[0], $qty[1]);}'
								),
								array('SUM(`quantity`)', 'SUM(\'.PP::sqlQty(\'quantity\').\')'),
								array('$id_cart.\'-\'.(int)$real_quantity', '$id_cart.\'-\'.(float)$real_quantity'),
							)
			),
			array(
				'files'  => array('classes/SpecificPrice.php'),
				'replace'=> array(
								array('`from_quantity`=\'.(int)$from_quantity.\' AND', '`from_quantity`=\'.(float)$from_quantity.\' AND'),
							)
			),
			array(
				'files'  => array('controllers/admin/AdminModulesController.php'),
				'replace'=> array(
								array('if (in_array($module->name, $module_names))', 'if (in_array($module->name, $module_names) ) Hook::exec("actionModuleUpgradeAfter", array("object" => $module)); if (in_array($module->name, $module_names) )'),
							)
			),
			array(
				'files'  => array('controllers/admin/AdminProductsController.php'),
				'replace'=> array(
								array('\'minimal_quantity\' => \'isUnsignedInt\'', '\'minimal_quantity\' => \'validateProductQuantity\''),
								array('elseif (!Validate::isUnsignedInt($from_quantity))', 'elseif (!Validate::validateSpecificPriceProductQuantity($from_quantity))'),
								array('$specificPrice->from_quantity = (int)($from_quantity);', '$specificPrice->from_quantity = (float)$from_quantity;'),
								array('<td>\'.$specific_price[\'from_quantity\'].\'</th>', '<td>\'.PP::formatQty($specific_price["from_quantity"]).\'</th>'),
							)
			),
			array(
				'files'  => array('controllers/admin/AdminOrdersController.php'),
				'replace'=> array(
								array(
									'$combinations = array();',
									'$combinations = array() ; $product["pproperties"] = PP::safeOutput($productObj->productProperties());'
								),
								array(
									'!Validate::isUnsignedInt(Tools::getValue(\'product_quantity\'))',
									'!(PP::qtyBehavior($order_detail, $order_detail->product_quantity) ? Validate::isUnsignedFloat(str_replace(",", ".", Tools::getValue("product_quantity"))) : Validate::isUnsignedInt(Tools::getValue("product_quantity")))'
								),
							)
			),
			array(
				'files'  => array('js/admin/orders.js'),
				'replace'=> array(
								array(
									'= makeTotalProductCaculation(',
									'= ppMakeTotalProductCaculation(typeof element == "undefined" ? null : element, '
								),
								array(
									'var quantity = parseInt($(this).val());',
									'var quantity = pp.parseFloat($(this).val());'
								),
								array(
									'if (quantity < 1 || isNaN(quantity))',
									'if (quantity < 0 || isNaN(quantity))'
								),
								array(
									'var stock_available = parseInt',
									'var stock_available = pp.parseFloat'
								),
								array(
									'element_list.parent().parent().find(\'td .product_quantity_show\').hide();',
									'element_list.find(\'td .product_quantity_show\').hide() ;',
									'count' => -1
								),
								array(
									'element_list.parent().parent().find(\'td .product_quantity_edit\').show();',
									'element_list.find(\'td .product_quantity_edit\').show() ;',
									'count' => -1
								),
								array(
									'$(\'td.product_action\').attr(\'colspan\', 3);',
									'$("td.product_action").attr("colspan", 2);',
									'count' => -1
								),
								array(
									'$(\'th.edit_product_fields\').attr(\'colspan\',  2);',
									'$("th.edit_product_fields").attr("colspan",  1);',
									'count' => -1
								),
							)
			),
		);

		$adminthemefiles = array(
			array(
				'files'  => array('/themes/default/template/controllers/products/helpers/list/list_header.tpl'),
				'replace'=> array(
								array(
									'{block name=leadin}',
									'{block name="leadin"}{hook h="displayProductsListLeadin"}',
									'uninstall' => false
								),
							)
			),
			array(
				'files'  => array('/themes/default/template/controllers/orders/helpers/view/view.tpl'),
				'replace'=> array(
								array(
									'{include file=\'controllers/orders/_product_line.tpl\'}',
									'{include file = \'controllers/orders/_customized_data.tpl\'}'
								),
								array(
									'{include file=\'controllers/orders/_customized_data.tpl\'}',
									'{include file = \'controllers/orders/_product_line.tpl\'}',
								),
							)
			),
		);

		return $this->processReplaceInFiles($themefiles, $replace, _PS_THEME_DIR_)
				&& $this->processReplaceInFiles($smartysysplugins, $replace, SMARTY_SYSPLUGINS_DIR)
				&& $this->processReplaceInFiles($root, $replace, _PS_ROOT_DIR_.'/')
				&& $this->processReplaceInFiles($adminthemefiles, $replace, _PS_ADMIN_DIR_);
	}

	private function processMailFiles($install)
	{
		$params = array(
			array(
				'files' => array(
					'account.html', 'bankwire.html', 'cheque.html', 'download_product.html', 'newsletter.html',
					'order_conf.html', 'order_changed.html', 'order_canceled.html', 'payment.html', 'preparation.html', 'shipped.html'
				),
				'condition' => 'href="http://www.prestashop.com',
				'delimiter' => '</a>',
				'target' => 'psandmore.com',
				'replace'=> ' &amp; <a style="text-decoration: none; color: #337ff1;" href="http://psandmore.com/">PS&amp;More&trade;</a>'
			)
		);
		$this->setupMail($params, $install);
	}
}
