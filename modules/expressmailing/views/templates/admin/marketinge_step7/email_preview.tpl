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

<form id="configuration_form_1" class="defaultForm form-horizontal AdminMarketingEStep7" action="index.php?controller=AdminMarketingEStep7&token={Tools::getAdminTokenLite('AdminMarketingEStep7')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate>
	<div class="panel" id="fieldset_0_1_1">
		<div class="panel-heading">
			<i class="icon-picture"></i> {l s='Preview of your mailing' mod='expressmailing'}
		</div>
		<div class="form-wrapper" style="margin: -15px -20px -20px -20px">
			<div class="form-group hide">
				<input type="hidden" name="campaign_id" id="campaign_id" value="{$campaign_id|intval}" />
			</div>
			<div class="form-group">
				<div class="col-lg-12">
					<div id="waiting" style="width: 100%; height: 60px; text-align: center; margin-left: auto; margin-right: auto">
						<br><img src="../modules/expressmailing/views/img/progress-bar.gif" alt="" />
					</div>
					<iframe id="preview" style="display: none; width: 100%; height: 350px; border: 0px; padding: 0px" src="index.php?controller=AdminMarketingEStep7&campaign_id={$campaign_id|intval}&ajax=true&token={Tools::getAdminTokenLite('AdminMarketingEStep7')|escape:'html':'UTF-8'}" style="border: 0;"></iframe>
				</div>
			</div>
		</div><!-- /.form-wrapper -->
		<div class="panel-footer" style="text-align: center">
			<button type="submit" value="1"	id="configuration_form_submit_btn_1" name="submitEmailingValidate" class="btn btn-default pull-right">
				<i class="process-icon-next"></i> {l s='Next' mod='expressmailing'}
			</button>
			<a href="index.php?controller=AdminMarketingEStep4&campaign_id={$campaign_id|intval}&token={Tools::getAdminTokenLite('AdminMarketingEStep4')|escape:'html':'UTF-8'}"  class="btn btn-default pull-left" ><i class="process-icon-back" ></i> {l s='Back' mod='expressmailing'}</a>
			<a href="index.php?controller=AdminMarketingEStep3&campaign_id={$campaign_id|intval}&token={Tools::getAdminTokenLite('AdminMarketingEStep3')|escape:'html':'UTF-8'}"  class="btn btn-default" ><i class="process-icon-edit" ></i> {l s='Update' mod='expressmailing'}</a>
		</div>
	</div>
</form>

{* Add a blank target to each links begins with 'http' on the preview *}

<script type="text/javascript">
	$("#preview").load(function(){
		$(this).contents().find('a[href^="http"]').each(function(){
			$(this).attr('target', '_blank');
		})
		$("#waiting").hide();
		$("#preview").show();
	});
</script>