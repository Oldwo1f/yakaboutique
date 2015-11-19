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
		<label class="control-label col-lg-3"> Importer depuis une page HTML sur mon disque dur : </label>
		
		<div class="col-lg-4">
			<input id="html_file" type="file" name="html_file" class="hide" />
			<div class="dummyfile input-group" style="padding-top: 0px">
				<span class="input-group-addon"><i class="icon-file"></i></span>
				<input id="html_file-name" type="text" name="filename" readonly />
				<span class="input-group-btn">
					<button id="html_file-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default"> <i class="icon-folder-open"></i> Ajouter un fichier </button>
				</span>
			</div>
		</div>
		<script type="text/javascript">
			$(document).ready(function () {
				$('#html_file-selectbutton').click(function (e) {
					$('#html_file').trigger('click');
				});
				$('#html_file-name').click(function (e) {
					$('#html_file').trigger('click');
				});
				$('#html_file-name').on('dragenter', function (e) {
					e.stopPropagation();
					e.preventDefault();
				});
				$('#html_file-name').on('dragover', function (e) {
					e.stopPropagation();
					e.preventDefault();
				});
				$('#html_file-name').on('drop', function (e) {
					e.preventDefault();
					var files = e.originalEvent.dataTransfer.files;
					$('#html_file')[0].files = files;
					$(this).val(files[0].name);
				});
				$('#html_file').change(function (e) {
					if ($(this)[0].files !== undefined) {
						var files = $(this)[0].files;
						var name = '';
						$.each(files, function (index, value) {
							name += value.name + ', ';
						});
						$('#html_file-name').val(name.slice(0, -2));
					} else {
						var name = $(this).val().split(/[\\/]/);
						$('#html_file-name').val(name[name.length - 1]);
					}
					$('#ps_expressmailing_email_html_form').submit();
				});
				if (typeof html_file_max_files !== 'undefined') {
					$('#html_file').closest('form').on('submit', function (e) {
						if ($('#html_file')[0].files.length > html_file_max_files) {
							e.preventDefault();
							alert('You can upload a maximum of files');
						}
					});
				}
			});
		</script>
	</div>
</div>