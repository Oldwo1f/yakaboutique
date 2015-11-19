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

jQuery(document).ready(function()
{
	$('a[href*="&detailsexpressmailing_email"]').each(function()
	{
		$(this).click(function(event)
		{
			// On extrait l'id du bouton 'details' cliqué
			var id = $(this).attr("id").match(/[0-9]+$/);
			
			if ($('#stats_details_' + id).length !== 0)
			{
				// On le retire
				$('#stats_details_' + id).closest('tr').remove();
				// $(this).on('mouseenter mouseleave', handlerInOut );
				$('form').focus();
			}
			else
			{
				// On retrouve la ligne contenant le bouton 'details' cliqué
				var row = $(this).closest('tr');
				var color = row.attr("class");
				
				row.after('<tr class="' + color + '"><td colspan="4" id="stats_details_' + id + '" align="center" style="width: 60%; padding: 20px 20px 20px 50px; background-color: #A0D0EB"><img src="../modules/expressmailing/views/img/progress-bar.gif" border="0"></td></tr>');

				$('#stats_details_' + id).load($(this).attr("href"), function(response, status, xhr)
				{
					if (status === "error")
					{
						var msg = "Sorry but there was an error : ";
						alert(msg + xhr.status + " " + xhr.statusText);
					}
				});
			}

			// On annule le submit du formulaire
			event.preventDefault();
		});
	});
});