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
	$(function () {
		$("#em-tabs").tabs();
		$("#em-editor-tab").click(function () {
			$('#tab_id').val('editor');
			$('#configuration_form_submit_btn_1').removeClass('hidden');
		});
		$("#em-file-tab").click(function () {
			$('#tab_id').val('file');
			$('#configuration_form_submit_btn_1').addClass('hidden');
		});
		$("#em-url-tab").click(function () {
			$('#tab_id').val('url');
			$('#configuration_form_submit_btn_1').addClass('hidden');
		});
	});
</script>
<form id="ps_expressmailing_email_html_form" class="defaultForm form-horizontal AdminMarketingEStep2" action="index.php?controller=AdminMarketingEStep3&token={Tools::getAdminTokenLite('AdminMarketingEStep3')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate>
	<input type="hidden" name="campaign_id" value="{$campaign_id|intval}" />
	<input type="hidden" id="tab_id" name="tab_id" value="editor" />
	<div class="panel" id="fieldset_0">
		<div class="panel-heading" style="margin-bottom: 0px; border-bottom: none">
			<i class="icon-beaker"></i>	{$title|escape:'html':'UTF-8'}
		</div>
		<div class="form-wrapper" style="margin-left: -20px; margin-right: -20px;">
			<div class="form-group" style="margin-bottom: 0px;">
				<div class="col-lg-12">
					<div id="em-tabs" style="border: 0px; padding: 0px">
						<ul style="border-radius: 0px; background: white; border: 0px; border-bottom: 1px solid #e6e6e6">
							<li style="border-color: #e6e6e6"><a id="em-editor-tab" href="#editor-tab">{l s='Edit your HTML' mod='expressmailing'}</a></li>
							<li style="border-color: #e6e6e6"><a id="em-file-tab" href="#file-tab">{l s='Import from an HTML file' mod='expressmailing'}</a></li>
							<li style="border-color: #e6e6e6"><a id="em-url-tab" href="#url-tab">{l s='Import from a web page' mod='expressmailing'}</a></li>
						</ul>
						<div id="editor-tab" style="padding-bottom: 0px">
							{$editor_form|unescape}
						</div>
						<div id="file-tab" style="padding-bottom: 0px">
							{$file_form|unescape}
						</div>
						<div id="url-tab" style="padding-bottom: 0px">
							{$url_form|unescape}
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="panel-footer" style="margin-top: 0px">
			<button type="submit" value="1"	id="configuration_form_submit_btn_1" name="nextEmailingStep3" class="btn btn-default pull-right">
				<i class="process-icon-next"></i> {l s='Next' mod='expressmailing'}
			</button>
			<a href="index.php?controller=AdminMarketingEStep2&campaign_id={$campaign_id|intval}&token={Tools::getAdminTokenLite('AdminMarketingEStep2')}"  class="btn btn-default" ><i class="process-icon-back" ></i> {l s='Back' mod='expressmailing'}</a>
		</div>
	</div>
</form>