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

<form id="configuration_form" class="defaultForm form-horizontal" action="index.php?controller={$order.campaign_media|escape:'html':'UTF-8'}&campaign_id={$order.campaign_id|intval}&&token={Tools::getAdminTokenLite($order.campaign_media)|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate="">

	<div class="panel">
		<div class="panel-heading">
			<i class="icon process-icon-cart"></i>&nbsp;&nbsp;{l s='Purchase order' mod='expressmailing'}
		</div>
		<div class="form-wrapper">
			<div class="form-group">
				<div class="col-lg-5">

					<span id="waiting">
						{l s='Please wait, your payment is being processed' mod='expressmailing'} <span id="progress"></span>
						<br><img src="../modules/expressmailing/views/img/progress-bar.gif" alt="" />
					</span>

					<div id="success" style="display: none;">

						<div class="alert alert-success clearfix">{l s='Transaction Successful' mod='expressmailing'}</div>

						{l s='Many thanks, your payment has been successfully processed.' mod='expressmailing'}
						<br><br>
						
						<button class="btn btn-default" name="back" value="1" type="submit">
							<i class="process-icon-edit"></i>
							{l s='Back to the mailing process' mod='expressmailing'}
						</button>

						<br><br>
						<i class="icon-envelope"></i>&nbsp;&nbsp;<b>{l s='We will email you, your personal billing within 24/48 hours.' mod='expressmailing'}</b>

					</div>

				</div>
			</div>
		</div>
	</div>
</form>

<script type="text/javascript">

	$(function ()
	{
		$("#progress").append('.');

		var i = 1;
		var url_base = "index.php?controller=AdminMarketingBuy";
		var url_ajax = "&ajax=true";
		var url_guid = "&order_session={$order_session|escape:'html':'UTF-8'}";
		var url_token = "&token={Tools::getAdminTokenLite('AdminMarketingBuy')|escape:'html':'UTF-8'}";

		check();

		function check()
		{
			$.ajax(
			{
				url: url_base + url_ajax + url_guid + url_token,
				type: "POST",
				success: function(output)
				{
					$("#progress").append(".");

					if (output == 'True')
					{
						$("#waiting").hide();
						$("#success").show();
						return;
					}

					if (i++ > 30)
					{
						$("#progress").append("<br>{l s='An error occurred :' mod='expressmailing'}" + output + "<br>" + "{l s='Please contact our support (see footer)' mod='expressmailing'}");
						return;
					}

					check();
				}
			});
		}
	});

</script>