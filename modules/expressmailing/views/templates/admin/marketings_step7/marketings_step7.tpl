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
	var chartData = [
		{
			"label": String.fromCharCode(160) + "{$count_planned|intval} {l s='planned' mod='expressmailing'}",
			"value": {$count_planned|intval}
		},
		{
			"label": String.fromCharCode(160) + "{$count_cancelled|intval} {l s='cancelled' mod='expressmailing'}",
			"value": {$count_cancelled|intval}
		}
	];
</script>

<div class="row">
    <div class="col-lg-12">

		{if !$campaign_sended}

			<form id="configuration_form" class="defaultForm form-horizontal" action="index.php?controller=AdminMarketingSStep7&token={Tools::getAdminTokenLite('AdminMarketingSStep7')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate="">
				<div class="panel" id="fieldset_0">
					<div class="panel-heading">
						<i class="icon-envelope-alt"></i> {l s='Final validation before sending (step 7)' mod='expressmailing'}
					</div>
					<div class="form-wrapper">
						<div class="form-group hidden">
							<label class="control-label col-lg-3">
								Ref :
							</label>
							<div class="col-lg-1">
								<input type="text" name="campaign_id" id="campaign_id" value="{$campaign_id|intval}" class="" readonly="readonly">
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-7">
								<div class="form-wrapper">
									<div class="form-group">
										<label class="control-label col-lg-4"> {l s='Campaign name :' mod='expressmailing'} </label>
										<div class="col-lg-8">
											<input id="campaign_name" type="text" readonly="readonly" value="{$campaign_name|escape:'html':'UTF-8'}" name="campaign_name">
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-lg-4"> {l s='Sms content :' mod='expressmailing'} </label>
										<div class="col-lg-8" style="height:10em;">
											<!-- Attention pas de retour a la ligne dans le textarea -->
											<textarea name="campaign_text" readonly="readonly" style="display: block; height: 100%;">{$campaign_text|escape:'html':'UTF-8'}</textarea>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-lg-4"> {l s='Cost detail :' mod='expressmailing'} </label>
										<div class="col-lg-8">
											{$cost_sms_detail|unescape}
										</div>
									</div>
								</div>
							</div>
							<div class="col-lg-4">
								<div class="form-wrapper">
									<div class="form-group">
										<div class="col-lg-5">
											<svg id="chart" style="height:260px; width: 350px"></svg>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					{$footer_validation|unescape}
				</div>
			</form>

		{else}

			<!-- Stats -->
			<div class="panel" id="fieldset_0">
				<a class="btn btn-default" href="index.php?controller=AdminMarketingSList&token={Tools::getAdminTokenLite('AdminMarketingSList')|escape:'html':'UTF-8'}">
					<i class="process-icon-stats"></i> {l s='Display statistics' mod='expressmailing'}
				</a>
			</div>

		{/if}
    </div>
</div>