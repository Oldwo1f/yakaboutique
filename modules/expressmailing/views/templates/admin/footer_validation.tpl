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

<div style="width: 100%" align="center">
	<table border="0" cellspacing="0" bgcolor="#000000">
	<tbody>
	<tr>
		<td>
			<table border="0" cellspacing="2" cellpadding="2" bgcolor="#FFFFFF">
			<tbody>
			<tr>
				<td colspan="3" align="center">
					<div style="margin-bottom: 0.5em">
						{l s='To send this mailing, please type' mod='expressmailing'}
						<b>&laquo;&nbsp;{l s='YES' mod='expressmailing'}&nbsp;&raquo;</b>
						{l s='and then submit' mod='expressmailing'}
					</div>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td align="center"><input type="text" name="YES" size="20" style="text-align: center; text-transform:uppercase" autocomplete="off" maxlength="{{l s='YES' mod='expressmailing'}|count_characters}"></td>
				<td>&nbsp;</td>
			</tr>
			<tr>
				<td align="right"><img border="0" src="../modules/expressmailing/views/img/left_arrow_history.gif" width="28" height="33"></td>
				<td align="center" style="padding: .5em">
					<button type="submit" value="1" id="configuration_form_submit_btn" name="sendCampaign" class="btn btn-default" style="padding-left: 2em; padding-right: 2em;">
						<i class="process-icon-import"></i> {l s='Send campaign' mod='expressmailing'}
					</button>
				</td>
				<td align="left"><img border="0" src="../modules/expressmailing/views/img/right_arrow_history.gif" width="28" height="33"></td>
			</tr>
			</tbody>
			</table>
		</td>
	</tr>
	</tbody>
	</table>
</div>

<div class="panel-footer" style="height: auto">
	<a href="index.php?controller=AdminMarketingEStep7&campaign_id={$campaign_id|intval}&token={Tools::getAdminTokenLite('AdminMarketingEStep7')|escape:'html':'UTF-8'}"  class="btn btn-default" ><i class="process-icon-back" ></i> {l s='Back' mod='expressmailing'}</a>
</div>
