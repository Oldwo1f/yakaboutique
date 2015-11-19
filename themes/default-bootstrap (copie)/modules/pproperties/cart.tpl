{*
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
* --- DO NOT REMOVE OR MODIFY THIS LINE PP_VERSION[1.6.0.14] PP_VERSION_REQUIRED[1.6.0.14] ---
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

{if $products}
<script type="text/javascript">
	ppCart.removeProducts();
	{if isset($currency)}
		{assign var="_currency" value={PP::resolveCurrency($currency)->id}}
	{else}
		{assign var="_currency" value={PP::resolveCurrency()->id}}
	{/if}
	ppCart.decimalSign = '{PP::getDecimalSign($_currency)}';
	ppCart.currencyFormat = '{PP::resolveCurrency($_currency)->format}';
	ppCart.currencySign = '{PP::resolveCurrency($_currency)->sign}';
	ppCart.currencyBlank = '{PP::resolveCurrency($_currency)->blank}';
{foreach $products as $product}
	{append var="p" index="icp" value=$product.id_cart_product nocache}
	{append var="p" index="cart_quantity" value=PP::obtainQty($product) nocache}
	{append var="p" index="cart_quantity_fractional" value=PP::obtainQtyFractional($product) nocache}
	{append var="p" index="pp_qty_text" value=$product.pp_product_qty_text|pp_safeoutput nocache}
	{append var="p" index="pp_price_text" value=$product.pp_price_text|pp_safeoutput nocache}
	{append var="p" index="pp_qty_policy" value=$product.pp_qty_policy nocache}
	{append var="p" index="pp_qty_mode" value=$product.pp_qty_mode nocache}
	{append var="p" index="pp_qty_step" value=$product.pp_qty_step nocache}
	{append var="p" index="pp_ext" value=$product['pp_ext'] nocache}
	{append var="p" index="pp_ext_prop_data" value=PP::productExtProperties($product) nocache}
	{append var="p" index="unit_price_ratio" value=$product['unit_price_ratio'] nocache}
	{if $product['pp_ext'] == 1}
		{append var="p" index="pp_ext_policy" value=$product['pp_ext_policy'] nocache}
	{/if}
	{*[HOOK ppropertiessmartprice]*}
	ppCart.addProduct({Tools::jsonEncode($p)});
{/foreach}
{if isset($ppEval)}
	{$ppEval}
{/if}
</script>
{/if}
