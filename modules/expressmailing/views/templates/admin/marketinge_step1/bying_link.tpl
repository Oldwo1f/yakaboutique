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

<a class="btn btn-default" id="em_bying_link"><i class="icon-shopping-cart"></i> &nbsp;{l s='Increase capacity ?' mod='expressmailing'}</a>

<div id="bying_dialog" title="{l s='Increase capacity ?' mod='expressmailing'}">

	<div style="width: 100%; margin-right: auto; margin-left: auto; text-align: center">
		<br><img src="../modules/expressmailing/views/img/progress-bar.gif" alt="" />
	</div>

</div>

<script type="text/javascript">

	$(function ()
	{
		var url_base = "index.php?controller=AdminMarketingEStep1";
		var url_ajax = "&ajax=true";
		var url_cpid = "&campaign_id={$campaign_id|intval}";
		var url_token = "&token={Tools::getAdminTokenLite('AdminMarketingEStep1')|escape:'html':'UTF-8'}";

		var dialogByingConfig =
		{
			autoOpen: false,
			resizable: true,
			position: 'center',
			modal: true,
			width: 820,
			height: 450,
			buttons: {
				"{l s='Close' mod='expressmailing'}": function () {
					$(this).dialog("close");
				}
			}
		};

		$('#bying_dialog').dialog(dialogByingConfig);

		$('#em_bying_link').click(function ()
		{
			$('#bying_dialog').load(url_base + url_ajax + url_cpid + url_token).dialog('open');
		});

	});

</script>
