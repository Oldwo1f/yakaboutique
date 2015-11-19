{*
* 2014-2015 (c) Axalone France - Express-Mailing
*
* This file is a commercial module for Prestashop
* Do not edit or add to this file if you wish to upgrade PrestaShop or
* customize PrestaShop for your needs please refer to
* http://www.express-mailing.com for more information.
*
* @author    Axalone France <info@express-mailing.com>
* @copyright 2014-2015 (c) Axalone France
* @license   http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
*}

<script type="text/javascript">
	$(function () {
		var slider{$field_name|escape:'javascript':'UTF-8'}Config = {
			range: false,
			min: {$min_value|escape:'javascript':'UTF-8'},
			max: {$max_value|escape:'javascript':'UTF-8'},
			step: {$step|escape:'javascript':'UTF-8'},
			{if $preset_value}value: {$preset_value|escape:'javascript':'UTF-8'},{/if}
			slide: function (event, ui) {
				$("#{$field_name|escape:'javascript':'UTF-8'}").val(ui.value);
			}
		};
		$("#slider-{$field_name|escape:'javascript':'UTF-8'}").slider(slider{$field_name|escape:'javascript':'UTF-8'}Config);
		var change_{$field_name|escape:'javascript':'UTF-8'} = function (evt) {
			var {$field_name|escape:'javascript':'UTF-8'} = $("#{$field_name|escape:'html':'UTF-8'}");
			$("#slider-{$field_name|escape:'javascript':'UTF-8'}").slider("value", {$field_name|escape:'javascript':'UTF-8'}.val());
		};
		$("#{$field_name|escape:'javascript':'UTF-8'}").change(change_{$field_name|escape:'javascript':'UTF-8'});
	});
</script>

<input class="col-lg-1" type="text" id="{$field_name|escape:'html':'UTF-8'}" name="{$field_name|escape:'html':'UTF-8'}" style="width: 4em" value="{$preset_value|intval}"/>
<div class="slider-container col-lg-4" style="margin-top: 9px; margin-left: 10px; margin-right: 10px">
	<div id="slider-{$field_name|escape:'html':'UTF-8'}"></div>
</div>