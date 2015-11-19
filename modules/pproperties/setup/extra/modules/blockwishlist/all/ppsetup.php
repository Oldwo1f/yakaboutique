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

class PPSetupBlockwishlist extends PPSetup
{
	public function setup()
	{
		$result = true;
		if ($this->install_mode)
			$result &= $this->setupDB();
		$result &= $this->processFiles(true);
		return $result;
	}

	public function checkIntegrity()
	{
		$result = true;
		$result &= $this->checkDbIntegrity();
		$result &= $this->processFiles(false);
		return $result;
	}

	private function processFiles($setup)
	{
		$module = 'blockwishlist';
		$params = array(
			array(
				'files'  => array('views/templates/front/managewishlist.tpl'),
				'replace'=> array(
								array(
									'"{$product.quantity|intval}" size="3"/>',
									'"{PP::resolveQty($product.quantity,$product.quantity_fractional)|formatQty}" size="6"/> {$product|pp:qty_text}'),
							)
			),
			array(
				'files'  => array('views/templates/front/view.tpl'),
				'replace'=> array(
								array(
									'"{$product.quantity|intval}" size="3"/>',
									'"{PP::resolveQty($product.quantity,$product.quantity_fractional)|formatQty}" size="6"/> {$product|pp:qty_text}'.
									'<span id="total_quantity_{$product.id_product}_{$product.id_product_attribute}" style="display:none">'.
										'{PP::resolveQty($product.quantity,$product.quantity_fractional)}'.
									'</span>'
								),
								array(
									'\'{$product.id_product}_{$product.id_product_attribute}\'',
									'\'quantity_{$product.id_product}_{$product.id_product_attribute}\''
								),
							)
			),
			array(
				'files'  => array('js/ajax-wishlist.js'),
				'replace'=> array(
								array('button, 1,', 'button, $(\'#\' + id_quantity).val(),'),
								array(
									'\'&id_product=\' + id_product + \'&id_product_attribute=\' + id_product_attribute,',
									'\'&id_product=\' + id_product + \'&id_product_attribute=\' + id_product_attribute + \'&qty=\' + $(\'#\' + id_quantity).val(),'
								),
								array(
									'$(\'#\' + id_quantity).val($(\'#\' + id_quantity).val() - 1);',
									'{'.
										'var q = pp.parseFloat($(\'#\' + id_quantity).val());'.
										'var t = pp.parseFloat($(\'#total_\' + id_quantity).html());'.
										'$(\'#\' + id_quantity).val(pp.formatQty(t-q)); $(\'#total_\' + id_quantity).html(t-q);'.
									'}'
								),
							)
			),
			array(
				'files'  => array('cart.php', 'managewishlist.php'),
				'replace'=> array(
								array(
									'$quantity = (int)Tools::getValue(\'quantity\');',
									'$quantity = PP::normalizeProductQty(Tools::getValue(\'quantity\'), $id_product);'
								),
							)
			),
		);

		return $this->processModuleFiles($module, $params, $setup);
	}

	protected function dbData()
	{
		$data = array();
		$data[] = array(
			'column' => 'quantity_fractional',
			'table'  => 'wishlist_product',
			'sql'    => 'decimal(20,6) NOT NULL DEFAULT 0.000000 AFTER `quantity`'
		);
		$data[] = array(
			'column' => 'quantity_fractional',
			'table'  => 'wishlist_product_cart',
			'sql'    => 'decimal(20,6) NOT NULL DEFAULT 0.000000 AFTER `quantity`'
		);
		return $data;
	}
}
