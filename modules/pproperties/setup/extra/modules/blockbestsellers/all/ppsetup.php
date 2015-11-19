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

class PPSetupBlockbestsellers extends PPSetup
{
	public function setup()
	{
		return $this->processFiles(true);
	}

	public function checkIntegrity()
	{
		return $this->processFiles(false);
	}

	private function processFiles($setup)
	{
		$module = 'blockbestsellers';
		$params = array(
			array(
				'files'  => array('blockbestsellers.php'),
				'replace'=> array(
								array(
									'$row[\'price\'] = Tools::displayPrice(Product::getPriceStatic((int)$row[\'id_product\'], $usetax), $currency);',
									'$row[\'price\'] = Product::getPriceStatic((int)$row[\'id_product\'], $usetax);'
								),
							)
			),
			array(
				'files'  => array('blockbestsellers.tpl'),
				'replace'=> array(
								array(
									'{$product.price}',
									'{convertPrice price=$product.price product=$product}'
								),
							)
			),
			array(
				'files'  => array('blockbestsellers-home.tpl'),
				'replace'=> array(
								array(
									'{if isset($best_sellers) && $best_sellers}',
									'{if isset($best_sellers) && $best_sellers }'.
										'{capture name="ignore"}'.
										'{foreach from=$best_sellers item=product key=index}{$best_sellers.$index.quantity_remainder = 0}'.
											'{Product::amendProduct($best_sellers.$index)}'.
										'{/foreach}'.
									'{/capture}'
								),
							)
			),
		);
		return $this->processModuleFiles($module, $params, $setup);
	}
}
