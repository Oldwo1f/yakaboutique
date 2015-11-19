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
* --- DO NOT REMOVE OR MODIFY THIS LINE PP_VERSION[1.6.0.14] PP_VERSION_REQUIRED[1.6] ---
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

{extends file="helpers/form/form.tpl"}

{block name="script" append}
	{assign var=qty_text value=$fields_value.id_product|pp:'bo_qty_text'}
	{if not empty($qty_text)}
		$(document).ready(function() {
			$('#quantity').after("{$qty_text|replace:'"':'\"'}");
		});
	{/if}
{/block}
