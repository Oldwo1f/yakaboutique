<?php
/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

smartyRegisterFunction($smarty, 'modifier', 'icp', 'smartyModifierIcp');
smartyRegisterFunction($smarty, 'modifier', 'pp_safeoutput', 'smartyModifierPPSafeoutput');
smartyRegisterFunction($smarty, 'modifier', 'pp_safeoutput_lenient', 'smartyModifierPPSafeoutputLenient');
smartyRegisterFunction($smarty, 'modifier', 'pp', 'smartyModifierPP');
smartyRegisterFunction($smarty, 'modifier', 'formatQty', 'smartyModifierFormatQty');

$smarty->unregisterPlugin('function', 'convertPrice');
smartyRegisterFunction($smarty, 'function', 'convertPrice', array('PP', 'smartyConvertPrice'));
$smarty->unregisterPlugin('function', 'convertPriceWithCurrency');
smartyRegisterFunction($smarty, 'function', 'convertPriceWithCurrency', array('PP', 'smartyConvertPrice'));

smartyRegisterFunction($smarty, 'function', 'ppAssign', array('PP', 'smartyPPAssign'));
smartyRegisterFunction($smarty, 'function', 'formatQty', array('PP', 'smartyFormatQty'));
smartyRegisterFunction($smarty, 'function', 'convertQty', array('PP', 'smartyConvertQty'));
smartyRegisterFunction($smarty, 'function', 'displayQty', array('PP', 'smartyDisplayQty'));
$smarty->unregisterPlugin('function', 'displayPrice');
smartyRegisterFunction($smarty, 'function', 'displayPrice', array('PP', 'smartyDisplayPrice'));
/* $smarty->unregisterPlugin('function', 'displayWtPrice'); */
/* smartyRegisterFunction($smarty, 'function', 'displayWtPrice', array('PP', 'smartyDisplayPrice')); */
$smarty->unregisterPlugin('function', 'displayWtPriceWithCurrency');
smartyRegisterFunction($smarty, 'function', 'displayWtPriceWithCurrency', array('PP', 'smartyDisplayPrice'));
smartyRegisterFunction($smarty, 'function', 'displayProductName', array('PP', 'smartyDisplayProductName'));
smartyRegisterFunction($smarty, 'function', 'smartpriceText', 'smartyFunctionSmartpriceText');

function smartyModifierIcp($product, $id = false, $mode = 'css')
{
	$icp = 'icp-'.($id === false ? (int)$product : (int)$product[$id]);
	if ($mode == 'css' && ($css = smartyModifierPP($product, 'css', 'left')))
		$icp .= $css;
	return $icp;
}

function smartyModifierPPSafeoutput($string, $type = null)
{
	if ($type === null) $type = 'html';
	switch ($type)
	{
		case 'html':
			return PP::safeOutput($string);
		case 'js':
		case 'javascript':
			return PP::safeOutputJS($string);
		case 'value':
			return PP::safeOutputValue($string);
		default:
			return $string;
	}
}

function smartyModifierPPSafeoutputLenient($string, $type = null)
{
	if ($type === null) $type = 'html';
	switch ($type)
	{
		case 'html':
			return PP::safeOutputLenient($string);
		case 'js':
		case 'javascript':
			return PP::safeOutputLenientJS($string);
		case 'value':
			return PP::safeOutputLenientValue($string);
		default:
			return $string;
	}
}

function smartyModifierPP($product, $mode, $wrap = true)
{
	$key = 'pp_'.$mode;
	if (is_array($product))
	{
		if (isset($product[$key]))
			$text = $product[$key];
	}
	if (!isset($text))
	{
		$properties = PP::getProductProperties($product);
		if (isset($properties[$key]))
			$text = $properties[$key];
	}
	return (isset($text) && $text != '' ? ($wrap === true ? PP::wrap($text, $key) : ($wrap == 'left' ? ' '.$text : ($wrap == 'right' ? $text.' ' : $text))) : '');
}

function smartyModifierFormatQty($qty, $currency = null)
{
	if ($currency)
		return PP::smartyFormatQty(array('qty' => $qty, 'currency' => $currency));
	else
		return PP::smartyFormatQty(array('qty' => $qty));
}

function smartyFunctionSmartpriceText($params, &$smarty)
{
	/*[HOOK ppropertiessmartprice]*/
	return '';
}
