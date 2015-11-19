<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class AdminAttributeGeneratorController extends AdminAttributeGeneratorControllerCore
{
	protected function addAttribute($attributes, $price = 0, $weight = 0)
	{
		foreach ($attributes as $attribute)
		{
			$price += (float)preg_replace('/[^0-9.-]/', '', str_replace(',', '.', Tools::getValue('price_impact_'.(int)$attribute)));
			$weight += (float)preg_replace('/[^0-9.]/', '', str_replace(',', '.', Tools::getValue('weight_impact_'.(int)$attribute)));
		}
		if ($this->product->id)
		{
			$result = array(
					'id_product' => (int)$this->product->id,
					'price' => (float)$price,
					'weight' => (float)$weight,
					'ecotax' => 0,
					'reference' => pSQL(Tools::getValue('reference')),
					'default_on' => 0,
					'available_date' => '0000-00-00'
			);

			PP::setQty($result, str_replace(',', '.', Tools::getValue('quantity')));
			return $result;
		}
		return array();
	}

	public function processGenerate()
	{
		if (!is_array(Tools::getValue('options')))
			$this->errors[] = Tools::displayError('Please select at least one attribute.');
		else
		{
			$tab = array_values(Tools::getValue('options'));
			if (count($tab) && Validate::isLoadedObject($this->product))
			{
				AdminAttributeGeneratorController::setAttributesImpacts($this->product->id, $tab);
				$this->combinations = array_values(AdminAttributeGeneratorController::createCombinations($tab));
				$values = array_values(array_map(array($this, 'addAttribute'), $this->combinations));

				// @since 1.5.0
				if ($this->product->depends_on_stock == 0)
				{
					$attributes = Product::getProductAttributesIds($this->product->id, true);
					foreach ($attributes as $attribute)
						StockAvailable::removeProductFromStockAvailable($this->product->id, $attribute['id_product_attribute'], Context::getContext()->shop);
				}

				SpecificPriceRule::disableAnyApplication();

				$this->product->deleteProductAttributes();
				$this->product->generateMultipleCombinations($values, $this->combinations);

				// @since 1.5.0
				if ($this->product->depends_on_stock == 0)
				{
					$attributes = Product::getProductAttributesIds($this->product->id, true);
					$quantity = str_replace(',', '.', Tools::getValue('quantity'));
					foreach ($attributes as $attribute)
						StockAvailable::setQuantity($this->product->id, $attribute['id_product_attribute'], $quantity);
				}
				else
					StockAvailable::synchronize($this->product->id);

				SpecificPriceRule::enableAnyApplication();
				SpecificPriceRule::applyAllRules(array((int)$this->product->id));

				Tools::redirectAdmin($this->context->link->getAdminLink('AdminProducts').'&id_product='.(int)Tools::getValue('id_product').'&updateproduct&key_tab=Combinations&conf=4');
			}
			else
				$this->errors[] = Tools::displayError('Unable to initialize these parameters. A combination is missing or an object cannot be loaded.');
		}
	}
}
