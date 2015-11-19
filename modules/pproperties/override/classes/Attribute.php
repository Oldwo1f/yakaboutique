<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class Attribute extends AttributeCore
{
	public static function getAttributeMinimalQty($id_product_attribute)
	{
		throw new PrestaShopException('Use product->attributeMinQty');
	}
}

