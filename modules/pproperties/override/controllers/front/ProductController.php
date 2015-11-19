<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class ProductController extends ProductControllerCore
{
	protected function assignAttributesGroups()
	{
		$colors = array();
		$groups = array();
		$combinations = array();

		// @todo (RM) should only get groups and not all declination ?
		$attributes_groups = $this->product->getAttributesGroups($this->context->language->id);
		if (is_array($attributes_groups) && $attributes_groups)
		{
			$combination_images = $this->product->getCombinationImages($this->context->language->id);
			$combination_prices_set = array();
			foreach ($attributes_groups as $k => $row)
			{
				// Color management
				if (isset($row['is_color_group']) && $row['is_color_group'] && (isset($row['attribute_color']) && $row['attribute_color']) || (file_exists(_PS_COL_IMG_DIR_.$row['id_attribute'].'.jpg')))
				{
					$colors[$row['id_attribute']]['value'] = $row['attribute_color'];
					$colors[$row['id_attribute']]['name'] = $row['attribute_name'];
					if (!isset($colors[$row['id_attribute']]['attributes_quantity']))
						$colors[$row['id_attribute']]['attributes_quantity'] = 0;
					$colors[$row['id_attribute']]['attributes_quantity'] += $row['quantity'];
				}
				if (!isset($groups[$row['id_attribute_group']]))
					$groups[$row['id_attribute_group']] = array(
						'group_name' => $row['group_name'],
						'name' => $row['public_group_name'],
						'group_type' => $row['group_type'],
						'default' => -1,
					);

				$groups[$row['id_attribute_group']]['attributes'][$row['id_attribute']] = $row['attribute_name'];
				if ($row['default_on'] && $groups[$row['id_attribute_group']]['default'] == -1)
					$groups[$row['id_attribute_group']]['default'] = (int)$row['id_attribute'];
				if (!isset($groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']]))
					$groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] = 0;
				$groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] += $row['quantity'];

				$combinations[$row['id_product_attribute']]['attributes_values'][$row['id_attribute_group']] = $row['attribute_name'];
				$combinations[$row['id_product_attribute']]['attributes'][] = (int)$row['id_attribute'];
				$combinations[$row['id_product_attribute']]['price'] = (float)$row['price'];

				// Call getPriceStatic in order to set $combination_specific_price
				if (!isset($combination_prices_set[(int)$row['id_product_attribute']]))
				{
					$combination_specific_price = null;
					Product::getPriceStatic((int)$this->product->id, false, $row['id_product_attribute'], 6, null, false, true, 1, false, null, null, null, $combination_specific_price);
					$combination_prices_set[(int)$row['id_product_attribute']] = true;
					$combinations[$row['id_product_attribute']]['specific_price'] = $combination_specific_price;
				}
				$combinations[$row['id_product_attribute']]['ecotax'] = (float)$row['ecotax'];
				$combinations[$row['id_product_attribute']]['weight'] = (float)$row['weight'];
				$combinations[$row['id_product_attribute']]['quantity'] = (float)$row['quantity'];
				$combinations[$row['id_product_attribute']]['reference'] = $row['reference'];
				$combinations[$row['id_product_attribute']]['unit_impact'] = $row['unit_price_impact'];
				$combinations[$row['id_product_attribute']]['minimal_quantity'] = $this->product->resolveMinQty($row['minimal_quantity'], $row['minimal_quantity_fractional']);
				if ($row['available_date'] != '0000-00-00')
				{
					$combinations[$row['id_product_attribute']]['available_date'] = $row['available_date'];
					$combinations[$row['id_product_attribute']]['date_formatted'] = Tools::displayDate($row['available_date']);
				}
				else
					$combinations[$row['id_product_attribute']]['available_date'] = '';

				if (!isset($combination_images[$row['id_product_attribute']][0]['id_image']))
					$combinations[$row['id_product_attribute']]['id_image'] = -1;
				else
				{
					$combinations[$row['id_product_attribute']]['id_image'] = $id_image = (int)$combination_images[$row['id_product_attribute']][0]['id_image'];
					if ($row['default_on'])
					{
						if (isset($this->context->smarty->tpl_vars['cover']->value))
							$current_cover = $this->context->smarty->tpl_vars['cover']->value;

						if (is_array($combination_images[$row['id_product_attribute']]))
						{
							foreach ($combination_images[$row['id_product_attribute']] as $tmp)
								if ($tmp['id_image'] == $current_cover['id_image'])
								{
									$combinations[$row['id_product_attribute']]['id_image'] = $id_image = (int)$tmp['id_image'];
									break;
								}
						}

						if ($id_image > 0)
						{
							if (isset($this->context->smarty->tpl_vars['images']->value))
								$product_images = $this->context->smarty->tpl_vars['images']->value;
							if (isset($product_images) && is_array($product_images) && isset($product_images[$id_image]))
							{
								$product_images[$id_image]['cover'] = 1;
								$this->context->smarty->assign('mainImage', $product_images[$id_image]);
								if (count($product_images))
									$this->context->smarty->assign('images', $product_images);
							}
							if (isset($this->context->smarty->tpl_vars['cover']->value))
								$cover = $this->context->smarty->tpl_vars['cover']->value;
							if (isset($cover) && is_array($cover) && isset($product_images) && is_array($product_images))
							{
								$product_images[$cover['id_image']]['cover'] = 0;
								if (isset($product_images[$id_image]))
									$cover = $product_images[$id_image];
								$cover['id_image'] = (Configuration::get('PS_LEGACY_IMAGES') ? ($this->product->id.'-'.$id_image) : (int)$id_image);
								$cover['id_image_only'] = (int)$id_image;
								$this->context->smarty->assign('cover', $cover);
							}
						}
					}
				}
			}

			// wash attributes list (if some attributes are unavailables and if allowed to wash it)
			if (!Product::isAvailableWhenOutOfStock($this->product->out_of_stock) && Configuration::get('PS_DISP_UNAVAILABLE_ATTR') == 0)
			{
				foreach ($groups as &$group)
					foreach ($group['attributes_quantity'] as $key => &$quantity)
						if ($quantity <= 0)
							unset($group['attributes'][$key]);

				foreach ($colors as $key => $color)
					if ($color['attributes_quantity'] <= 0)
						unset($colors[$key]);
			}
			foreach ($combinations as $id_product_attribute => $comb)
			{
				$attribute_list = '';
				foreach ($comb['attributes'] as $id_attribute)
					$attribute_list .= '\''.(int)$id_attribute.'\',';
				$attribute_list = rtrim($attribute_list, ',');
				$combinations[$id_product_attribute]['list'] = $attribute_list;
			}

			$this->context->smarty->assign(array(
				'groups' => $groups,
				'colors' => (count($colors)) ? $colors : false,
				'combinations' => $combinations,
				'combinationImages' => $combination_images
			));
		}
	}
}
