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
		$("#tabs").tabs();
		$("#prestashop-customers-tab").click(function () {
			$('#ps_expressmailing_sms_recipients_form_submit_btn').attr('name', 'import-prestashop-customers');
		});
		$("#xls-file-tab").click(function () {
			$('#ps_expressmailing_sms_recipients_form_submit_btn').attr('name', 'import-xls');
		});
		$("#csv-file-tab").click(function () {
			$('#ps_expressmailing_sms_recipients_form_submit_btn').attr('name', 'import-csv');
		});
		$("#quick-import-tab").click(function () {
			$('#ps_expressmailing_sms_recipients_form_submit_btn').attr('name', 'quick-import');
			});
		});
</script>
<form id="ps_expressmailing_sms_recipients_form" class="defaultForm form-horizontal AdminMarketingSStep2" action="index.php?controller=AdminMarketingSStep2&token={Tools::getAdminTokenLite('AdminMarketingSStep2')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate>
	<input type="hidden" name="submitAddps_expressmailing_sms_recipients" value="1" />
	<input type="hidden" name="campaign_id" value="{$campaign_id|intval}" />

	<div class="panel" id="fieldset_0">
		<div class="panel-heading" style="margin-bottom: 0px; border-bottom: none">
			<i class="icon-beaker"></i>	{$title|escape:'html':'UTF-8'}
		</div>
		<div class="form-wrapper" style="margin-left: -20px; margin-right: -20px;">
			<div class="form-group" style="margin-bottom: 0px;">
				<div class="col-lg-12">
					<div id="tabs" style="border: 0px; padding: 0px">
						<ul style="border-radius: 0px; background: white; border: 0px; border-bottom: 1px solid #e6e6e6">
							<li style="border-color: #e6e6e6"><a id="csv-file-tab" href="#csv-file">{l s='Import a CSV file' mod='expressmailing'}</a></li>
							<li style="border-color: #e6e6e6"><a id="prestashop-customers-tab" href="#prestashop-customers">{l s='Import from your Prestashop\'s customers' mod='expressmailing'}</a></li>
							<!--<li style="border-color: #e6e6e6"><a id="xls-file-tab" href="#xls-file">{l s='Import an XLS file' mod='expressmailing'}</a></li>
							<li style="border-color: #e6e6e6"><a id="quick-import-tab" href="#quick-import">{l s='Quick import' mod='expressmailing'}</a></li>-->
						</ul>
						<div id="csv-file">
							<div class="form-group">
								<label class="control-label col-lg-3">
									{l s='Import a csv file :' mod='expressmailing'}
								</label>
								<div class="col-lg-9 ">
									<div class="form-group">
										<div class="col-sm-6">
											<input id="csv_file" type="file" name="csv_file" class="hide">
											<div class="dummyfile input-group">
												<span class="input-group-addon"><i class="icon-file"></i></span>
												<input id="csv_file-name" type="text" name="filename" readonly="">
												<span class="input-group-btn">
													<button id="csv_file-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
														<i class="icon-folder-open"></i> {l s='Insert a file :' mod='expressmailing'}</button>
												</span>
											</div>
										</div>
									</div>
									<script type="text/javascript">
										$(document).ready(function () {
											$('#csv_file-selectbutton').click(function (e) {
												$('#csv_file').trigger('click');
											});

											$('#csv_file-name').click(function (e) {
												$('#csv_file').trigger('click');
											});

											$('#csv_file-name').on('dragenter', function (e) {
												e.stopPropagation();
												e.preventDefault();
											});

											$('#csv_file-name').on('dragover', function (e) {
												e.stopPropagation();
												e.preventDefault();
											});

											$('#csv_file-name').on('drop', function (e) {
												e.preventDefault();
												var files = e.originalEvent.dataTransfer.files;
												$('#csv_file')[0].files = files;
												$(this).val(files[0].name);
											});

											$('#csv_file').change(function (e) {
												if ($(this)[0].files !== undefined)
												{
													var files = $(this)[0].files;
													var name = '';

													$.each(files, function (index, value) {
														name += value.name + ', ';
													});

													$('#csv_file-name').val(name.slice(0, -2));
												}
												else // Internet Explorer 9 Compatibility
												{
													var name = $(this).val().split(/[\\/]/);
													$('#csv_file-name').val(name[name.length - 1]);
												}
											});

											if (typeof csv_file_max_files !== 'undefined')
											{
												$('#csv_file').closest('form').on('submit', function (e) {
													if ($('#csv_file')[0].files.length > csv_file_max_files) {
														e.preventDefault();
														alert('You can upload a maximum of files');
													}
												});
											}
										});
									</script>
								</div>
							</div>
						</div>

						<div id="prestashop-customers">
							{$customers_filters|unescape}
						</div>
						<!--
						<div id="xls-file">
							<div class="form-group">
								<label class="control-label col-lg-3">
						{l s='Import an xls file :' mod='expressmailing'}
					</label>
					<div class="col-lg-9 ">
						<div class="form-group">
							<div class="col-sm-6">
								<input id="xls_file" type="file" name="xls_file" class="hide">
								<div class="dummyfile input-group">
									<span class="input-group-addon"><i class="icon-file"></i></span>
									<input id="xls_file-name" type="text" name="filename" readonly="">
									<span class="input-group-btn">
										<button id="xls_file-selectbutton" type="button" name="submitAddAttachments" class="btn btn-default">
											<i class="icon-folder-open"></i> {l s='Insert a file :' mod='expressmailing'}</button>
									</span>
								</div>
							</div>
						</div>
						<script type="text/javascript">
							$(document).ready(function () {
								$('#xls_file-selectbutton').click(function (e) {
									$('#xls_file').trigger('click');
								});

								$('#xls_file-name').click(function (e) {
									$('#xls_file').trigger('click');
								});

								$('#xls_file-name').on('dragenter', function (e) {
									e.stopPropagation();
									e.preventDefault();
								});

								$('#xls_file-name').on('dragover', function (e) {
									e.stopPropagation();
									e.preventDefault();
								});

								$('#xls_file-name').on('drop', function (e) {
									e.preventDefault();
									var files = e.originalEvent.dataTransfer.files;
									$('#xls_file')[0].files = files;
									$(this).val(files[0].name);
								});

								$('#xls_file').change(function (e) {
									if ($(this)[0].files !== undefined)
									{
										var files = $(this)[0].files;
										var name = '';

										$.each(files, function (index, value) {
											name += value.name + ', ';
										});

										$('#xls_file-name').val(name.slice(0, -2));
									}
									else // Internet Explorer 9 Compatibility
									{
										var name = $(this).val().split(/[\\/]/);
										$('#xls_file-name').val(name[name.length - 1]);
									}
								});

								if (typeof xls_file_max_files !== 'undefined')
								{
									$('#xls_file').closest('form').on('submit', function (e) {
										if ($('#xls_file')[0].files.length > xls_file_max_files) {
											e.preventDefault();
											alert('You can upload a maximum of files');
										}
									});
								}
							});
						</script>
					</div>
				</div>
			</div>

			<div id="quick-import">
				<div class="form-group">
					<label class="control-label col-lg-3">
									{l s='Paste a csv content :' mod='expressmailing'}
								</label>
								<div class="col-lg-9 ">
									<textarea name="paste-csv" ></textarea>
								</div>
							</div>
						</div>
									-->
					</div>
				</div>
			</div>
		</div>
		<div class="panel-footer" style="margin-top:0px">
			<button type="submit" value="1"	id="ps_expressmailing_sms_recipients_form_submit_btn" name="import-csv" class="btn btn-default pull-right"><i class="process-icon-cogs"></i> {l s='Start analysis ...' mod='expressmailing'}</button>
			<button type="submit" class="btn btn-default" name="clearRecipients"><i class="process-icon-delete" ></i> {l s='Clear selection' mod='expressmailing'}</button>
			<button type="submit" class="btn btn-default" name="clearDuplicate"><i class="process-icon-eraser" ></i> {l s='Clear duplicates (%d)' mod='expressmailing' sprintf=$duplicate_count}</button>
		</div>
	</div>
</form>
