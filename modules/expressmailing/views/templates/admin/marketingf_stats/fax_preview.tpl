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
                    <i class="icon-print"></i> {l s='Document preview' mod='expressmailing'}
                </div>
				<div class="form-wrapper">
					<div class="form-group">
						<div class="col-lg-12" style="text-align: center">
							{foreach $settled_pages as $page}
								<img class="fax_preview" style="background-size: 210px 297px; display: inline-block; width: 210px; height: 297px; border: 1px solid black; margin-left: .5em; margin-right: .5em" src="data:image/png;base64, {$page['image_base64']|escape:'html':'UTF-8'}" />
							{/foreach}
						</div>
					</div>
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

		$(".fax_preview").click(function()
		{
			$("#dialog-message").dialog("open");
			$("#dialog-preview").attr("src", $(this).attr("src"));
			$("#dialog-preview").height("auto");
			$("#dialog-preview").width("100%");
			$("#dialog-preview").height(2 * $("#dialog-preview").height());
		});
	});
</script>