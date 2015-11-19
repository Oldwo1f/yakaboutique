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
			range: true,
			min: {$min_value|intval},
			max: {$max_value|intval},
			values: [{$start_value|intval}, {$end_value|intval}],
			step: {$step|intval},
			slide: function (event, ui) {
				updateValue(ui.values[0], ui.values[1]);
			}
		};
		$( "#slider-{$field_name|escape:'javascript':'UTF-8'}" ).css('background', 'rgb(255,0,0)');
		$("#slider-{$field_name|escape:'javascript':'UTF-8'} .ui-slider-range").css('background', 'rgb(0, 128, 0)');
		$("#slider-{$field_name|escape:'javascript':'UTF-8'}").slider(slider{$field_name|escape:'javascript':'UTF-8'}Config);
		var change_{$field_name|escape:'javascript':'UTF-8'} = function (evt) {
			var {$field_name|escape:'javascript':'UTF-8'} = $("#{$field_name|escape:'javascript':'UTF-8'}");
				$("#slider-{$field_name|escape:'javascript':'UTF-8'}").slider("value", {$field_name|escape:'javascript':'UTF-8'}.val());
			};
			$("#{$field_name|escape:'javascript':'UTF-8'}").change(change_{$field_name|escape:'javascript':'UTF-8'});

		updateValue({$start_value|intval},{$end_value|intval});
		function updateValue(val1, val2) {
			var val1_str = (val1 % 60).toString();
			var val2_str = (val2 % 60).toString();
			var val1_str_h = Math.floor(val1 / 60).toString();
			var val2_str_h = Math.floor(val2 / 60).toString();

			if (val1_str.length == 1)
				val1_str = '0' + val1_str;
			if (val2_str.length == 1)
				val2_str = '0' + val2_str;
			if (val1_str_h.length == 1)
				val1_str_h = '0' + val1_str_h;
			if (val2_str_h.length == 1)
				val2_str_h = '0' + val2_str_h;

			$("#start_hour").val(val1_str_h + "h" + val1_str);
			$("#end_hour").val(val2_str_h + "h" + val2_str);
			$("#start_hour_hidden").val(val1);
			$("#end_hour_hidden").val(val2);
		}
	});
</script>
<input class="col-lg-1" type="text" id="start_hour" name="start_hour" style="width: 5em" />
<input class="col-lg-1" type="hidden" id="start_hour_hidden" name="start_hour_hidden" style="width: 5em" />

<div class="slider-container col-lg-4" style="margin: 9px 10px 0px 10px">
	<div id="slider-{$field_name|escape:'html':'UTF-8'}"></div>
</div>

<input class="col-lg-1" type="text" id="end_hour" name="end_hour" style="width: 5em" />
<input class="col-lg-1" type="hidden" id="end_hour_hidden" name="end_hour_hidden" style="width: 5em" />


