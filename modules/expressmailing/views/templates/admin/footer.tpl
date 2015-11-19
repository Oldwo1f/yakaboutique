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

{capture name=price assign=price}{l s='International call price' mod='expressmailing'}{/capture}

<div id="footer" class="bootstrap hide" style="background-color: white; padding: 8px 0 0 0px; margin: 0px; line-height: 15px">

	<table border="0" cellspacing="0" cellpadding="0" >
	<tr>
		<td valign="top"><img border="0" hspace="10" alt="" align="left" src="../modules/expressmailing/views/img/help.gif" width="53" height="32"></td>
		<td valign="top"><b>{l s='Don\'t get stuck' mod='expressmailing'}</b>{l s=', contact our helpdesk' mod='expressmailing'}<br><i><b><font color="#1A3E7A">Express-</font><font color="#FEB202">Mailing</font></b></i> &nbsp;{l s='Monday to Friday from <b>9:30 to 5:00 p.m.</b>' mod='expressmailing' js=true}</td>
		<td align="center"><img border="0" hspace="10" title="{l s='+33 169.313.961' mod='expressmailing'} : {l s='International call price' mod='expressmailing'}" src="../modules/expressmailing/views/img/{l s='tel_en.gif' mod='expressmailing'}">{if $price != 'International call price'}<br><font style="font-size: .75em; color: #7E7E7E">{$price|escape:'html':'UTF-8'}</font>{/if}</td>
	</tr>
	</table>

</div>