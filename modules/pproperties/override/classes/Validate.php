<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

class Validate extends ValidateCore
{
	public static function validateProductQuantity($value)
	{
		if (PP::productQtyPolicyLegacy((int)Tools::getValue('id_product')))
			return self::isUnsignedInt($value);
		else
		{
			if (is_string($value))
				$value = str_replace(',', '.', $value);
			return self::isUnsignedFloat($value);
		}
	}

	public static function validateSpecificPriceProductQuantity($value)
	{
		if (PP::productQtyPolicyLegacy((int)Tools::getValue('id_product')))
			return self::isUnsignedInt($value);
		else
		{
			if (is_string($value))
				$value = str_replace(',', '.', $value);
			return self::isUnsignedFloat($value);
		}
	}
}
