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

	$(function ()
	{
		$("#progress").append("{l s='Copy recipients in progress ...' mod='expressmailing'}<br>");

		var i = 1;
		var url_base = "index.php?controller=AdminMarketingEStep6";
		var url_ajax = "&ajax=true";
		var url_cpid = "&campaign_id={$campaign_id|escape:'javascript':'UTF-8'}";
		var url_token = "&token={Tools::getAdminTokenLite('AdminMarketingEStep6')|escape:'javascript':'UTF-8'}";

		upload();

		function upload()
		{
			$.ajax(
					{
						url: url_base + url_ajax + url_token,
						type: "POST",
						data: {
							campaign_id: "{$campaign_id|escape:'javascript':'UTF-8'}",
							i: i++
						},
						success: function (output)
						{
							$("#progress").append("<img src='../modules/expressmailing/views/img/progress.gif' border='0' alt=''>");
							if (output == 'continue')
								upload();
							else if (output == 'ended')
								window.location = 'index.php?controller=AdminMarketingEStep7' + url_cpid + "&token={Tools::getAdminTokenLite('AdminMarketingEStep7')|escape:'javascript':'UTF-8'}";
							else
							{
								$("#progress").append("{l s='An error occurred for this block :' mod='expressmailing'}" + output + "<br>" + "{l s='We will try to resend it ...' mod='expressmailing'}" + "<br>");
								if (i > 5)
									return;
								else
									upload();
							}
						}
					});
		}
	});

</script>

<form id="configuration_form" class="defaultForm form-horizontal AdminMarketingEStep6" action="index.php?controller=AdminMarketingEStep6&token={Tools::getAdminTokenLite('AdminMarketingEStep6')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate="">
	<div class="panel">
		<div class="panel-heading">
			<i class="icon process-icon-loading"></i>&nbsp;&nbsp;{l s='Recipients upload (step 6)' mod='expressmailing'}
		</div>
		<div class="form-wrapper">
			<div class="form-group">
				<div class="col-lg-5">
					<span id="progress"></span>
				</div>
			</div>
		</div>
	</div>
</form>