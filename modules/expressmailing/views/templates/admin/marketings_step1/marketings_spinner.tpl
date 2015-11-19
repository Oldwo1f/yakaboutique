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
		var spinner = $("#spinner").spinner({
			step: 1
		});
		if ({$preset_value|intval} == 0) {
			spinner.spinner("disable");
			spinner.spinner("value", 0);
		}
		else {
			spinner.spinner("enable");
			spinner.spinner("value", {$preset_value|intval});
			$("#enable_limit")[0].checked = true;
		}

		$("#enable_limit").click(function () {
			if (spinner.spinner("option", "disabled")) {
				spinner.spinner("enable");
			} else {
				spinner.spinner("disable");
				spinner.spinner("value", 0);
			}
		});

	});
</script>


<div class="slider-container col-lg-4" style="margin: 9px 10px 0px 10px">
	<input type="checkbox" name="enable_limit" id="enable_limit" >
	<input id="spinner" name="spinner" >
</div>


