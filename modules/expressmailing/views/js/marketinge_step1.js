/**
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
*/

$(function ()
{
	var onChange_Checkbox_Day = function (evt)
	{
		if (evt.target.checked)
		{
			$(evt.target).parent().removeClass('red');
			$(evt.target).parent().addClass('green');
		}
		else
		{
			$(evt.target).parent().removeClass('green')
			$(evt.target).parent().addClass('red');
		}
	};

	$('#week_day_limit_L').change(onChange_Checkbox_Day);
	$('#week_day_limit_M').change(onChange_Checkbox_Day);
	$('#week_day_limit_C').change(onChange_Checkbox_Day);
	$('#week_day_limit_J').change(onChange_Checkbox_Day);
	$('#week_day_limit_V').change(onChange_Checkbox_Day);
	$('#week_day_limit_S').change(onChange_Checkbox_Day);
	$('#week_day_limit_D').change(onChange_Checkbox_Day);

	if ($('#week_day_limit_L').is(':checked'))
		$('#week_day_limit_L').parent().addClass('green');
	else
		$('#week_day_limit_L').parent().addClass('red');
	if ($('#week_day_limit_M').is(':checked'))
		$('#week_day_limit_M').parent().addClass('green');
	else
		$('#week_day_limit_M').parent().addClass('red');
	if ($('#week_day_limit_C').is(':checked'))
		$('#week_day_limit_C').parent().addClass('green');
	else
		$('#week_day_limit_C').parent().addClass('red');
	if ($('#week_day_limit_J').is(':checked'))
		$('#week_day_limit_J').parent().addClass('green');
	else
		$('#week_day_limit_J').parent().addClass('red');
	if ($('#week_day_limit_V').is(':checked'))
		$('#week_day_limit_V').parent().addClass('green');
	else
		$('#week_day_limit_V').parent().addClass('red');
	if ($('#week_day_limit_S').is(':checked'))
		$('#week_day_limit_S').parent().addClass('green');
	else
		$('#week_day_limit_S').parent().addClass('red');
	if ($('#week_day_limit_D').is(':checked'))
		$('#week_day_limit_D').parent().addClass('green');
	else
		$('#week_day_limit_D').parent().addClass('red');

});
