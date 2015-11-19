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

<div class="row">
	<div class="col-lg-12">
		<form id="configuration_form" class="defaultForm form-horizontal" action="index.php?controller=AdminMarketingFStep2&token={Tools::getAdminTokenLite('AdminMarketingFStep2')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate="">
			<div class="panel">
				<div class="panel-heading">
                    <i class="icon-file-text"></i> {l s='Document preview' mod='expressmailing'}
                </div>
				<div class="form-wrapper">
					<div class="form-group hide">
                        <label class="control-label col-lg-3">
                            Ref :
                        </label>
                        <div class="col-lg-1">
                            <input type="{if $mod_dev}text{else}hiden{/if}" name="campaign_id" id="campaign_id" value="{$campaign_id|intval}" class="" readonly="readonly">
                        </div>
                    </div>
					<div class="form-group">
						<div class="col-lg-12" style="text-align: center">
							{foreach $settled_pages as $page}
								<div class="fax_preview" style="background-image: url({$page['page_url']|escape:'html':'UTF-8'}); background-size: 210px 297px; display: inline-block; width: 210px; height: 297px; border: 1px solid black; margin-left: .5em; margin-right: .5em">
									<a class="fax_delete" onclick="return false" href="index.php?controller=AdminMarketingFStep2&campaign_id={$campaign_id|intval}&token={Tools::getAdminTokenLite('AdminMarketingFStep2')|escape:'html':'UTF-8'}&delete_page={$page['id']|intval}" style="float: right; margin: .5em" title="{l s='Delete this page' mod='expressmailing'}"><img src="../img/admin/cross.png" border="0" /></a>
								</div>
							{/foreach}
						</div>
					</div>
				</div>
				<div class="panel-footer">
					<button type="submit" value="1" id="configuration_form_submit_btn" name="submitFaxStep2" class="btn btn-default pull-right">
						<i class="process-icon-next"></i> {l s='Next' mod='expressmailing'}
					</button>
					<a href="index.php?controller=AdminMarketingFStep1&campaign_id={$campaign_id|intval}&token={Tools::getAdminTokenLite('AdminMarketingFStep1')|escape:'html':'UTF-8'}" class="btn btn-default"><i class="process-icon-back"></i> {l s='Back' mod='expressmailing'}</a>
				</div>
			</div>
		</form>
	</div>
</div>

<div id="dialog-message" title="Page preview" style="display: none">
	<img id="dialog-preview" src="../modules/expressmailing/views/img/progress-bar.gif" alt="" />
</div>

<script>
	$(document).ready(function ()
	{
		var dTop = $("#fieldset_0").offset().top;
		var dLeft = $("#fieldset_0").offset().left;
		var dWidth = $("#fieldset_0").width();
		var dHeight = parseInt($(window).height() - 1.5 * dTop);

		$("#dialog-message").dialog({
			autoOpen: false,
			modal: true,
			draggable: true,
			width: dWidth,
			height: dHeight,
			position: [dLeft, dTop],
			buttons:
			{
				Ok: function()
				{
					$(this).dialog("close");
				}
			}
		});

		$(".fax_delete").click(function(event)
		{
			event.stopPropagation();
			window.location = $(this).attr("href");
		});

		$(".fax_preview").click(function()
		{
			$("#dialog-message").dialog("open");
			$("#dialog-preview").attr("src", $(this).css('background-image').replace(/^url\(['"]?/,'').replace(/['"]?\)$/,''));
		});
	});
</script>