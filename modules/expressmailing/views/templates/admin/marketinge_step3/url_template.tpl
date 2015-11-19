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

{if !function_exists('curl_version')}
	<div class="alert alert-info">Pour pouvoir utiliser ces fonctions, veuillez activer cURL (extension PHP)</div>
{/if}

<div class="form-wrapper" style="margin-left: -20px; margin-right: -20px;">
	<div class="form-group">
		<label class="control-label col-lg-3"> Ref : </label>
		<div class="col-lg-1 ">
			<input type="text" name="campaign_id" id="campaign_id" value="{$campaign_id|intval}" class="" readonly="readonly" />
		</div>
	</div>
	<div class="form-group">
		<label class="control-label col-lg-3"> Importer depuis une page web : </label>
		<div class="input-group col-lg-4">
			<input type="text" name="html_url" id="html_url" value="" class="hide" />
			<div class="dummyfile input-group" style="padding-top: 0px; padding-left: 5px;">
				<span class="input-group-addon">http://</span>
				<input id="html_url-name" type="text" name="filename" />
				<span class="input-group-btn">
					<button id="html_url-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default"> <i class="icon-cloud-upload"></i> {l s='Import' mod='expressmailing'} </button>
				</span>
			</div>
		</div>
		<script type="text/javascript">
			$(document).ready(function () {
				$('#html_url-selectbutton').click(function (e) {
					$('#ps_expressmailing_email_html_form').submit();
				});
				$('#html_url-name').click(function (e) {
					$('#html_url').trigger('click');
				});
				$('#html_url-name').change(function (e) {
					$('#html_url').val($('#html_url-name').val());
				});
			});
		</script>
	</div>
</div>