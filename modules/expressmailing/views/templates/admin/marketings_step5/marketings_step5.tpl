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
		countries_ids = new Array();
	{foreach $grouped_target_list as $target_group}
		countries_ids.push("{$target_group['country_id']|intval}");
	{/foreach}
		var changeCheckboxAuthorize = function () {
			var countryID = $(this).val();
			var hiddenInput = $("#selected_countries_" + countryID);
			var line = $(this).parents("tr");
			var cells = line.children();
			if ($(this).attr("checked") !== undefined) {
				hiddenInput.val(countryID);
	{*cells.each(function () {
	$(this).css("background-color", "rgb(194,253,194)");
	});*}
			}
		};
		var changeCheckboxRefuse = function () {
			var countryID = $(this).val();
			var hiddenInput = $("#selected_countries_" + countryID);
	{*var line = $(this).parents("tr");*}
	{*var cells = line.children();*}
				if ($(this).attr("checked") !== undefined) {
					hiddenInput.val("-" + countryID);
	{*cells.each(function () {
	$(this).css("background-color", "rgb(253,194,194)");
	});*}
				}
			};
			for (var key in countries_ids) {
				var hiddenInput = $("#selected_countries_" + countries_ids[key]);
				var checkboxAuthorize = $("#country_" + countries_ids[key] + "_authorize");
				checkboxAuthorize.change(changeCheckboxAuthorize);
				var checkboxRefuse = $("#country_" + countries_ids[key] + "_refuse");
				checkboxRefuse.change(changeCheckboxRefuse);
	{*var line = checkboxAuthorize.parents("tr");*}
	{*var cells = line.children();*}
				if (checkboxAuthorize.attr("checked") !== undefined) {
					hiddenInput.val(countries_ids[key]);
	{*cells.each(function () {
	$(this).css("background-color", "rgb(194,253,194)");
	});*}
				} else {
					hiddenInput.val("-" + countries_ids[key]);
	{*cells.each(function () {
	$(this).css("background-color", "rgb(253,194,194)");
	});*}
				}
			}
		});
</script>

<form id="configuration_form" class="defaultForm form-horizontal AdminMarketingSStep5" action="index.php?controller=AdminMarketingSStep5&token={Tools::getAdminTokenLite('AdminMarketingSStep5')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate="">
	<div class="panel">
		<div class="panel-heading">
			<i class="icon-trash"></i> {l s='Recipients cleaner (step 5)' mod='expressmailing'}
		</div>
		<div class="form-wrapper">
			<div class="{if $mod_dev}form-group{else}form-group hide{/if}">
				<label class="control-label col-lg-3">
					Ref :
				</label>
				<div class="col-lg-1">
					<input type="{if $mod_dev}text{else}hiden{/if}" name="campaign_id" id="campaign_id" value="{$campaign_id|intval}" class="" readonly="readonly">
				</div>
			</div>
			<div class="form-group">
				<div class="col-lg-5">
					<div class="table-responsive clearfix">
						<table class="table expressmailing_email">
							<thead>
								<tr class="nodrag nodrop">
									<th class="">
										<span class="title_box">{l s='Broadcasting areas / Filtering tips' mod='expressmailing'}</span>
									</th>
									<th class="">
										<span class="title_box">{l s='Count' mod='expressmailing'}</span>
									</th>
									<th></th>
								</tr>
							</thead>
							<tbody>

								{foreach $grouped_target_list as $target_group}
									{if $target_group['country_id']}
										<tr class="odd">
											<td class="">{$countries_list[$target_group['country_id']]['country_name']|escape:'html':'UTF-8'}</td>
											<td class="">{$target_group['count_total_recipients']|intval}</td>
											<td class="text-right">
												<div class="btn-group-action">
													<div class="btn-group pull-right">
														<span class="switch prestashop-switch fixed-width-lg">
															<input type="hidden" name="selected_countries[]" id="selected_countries_{$target_group['country_id']|intval}" value=""/>
															<input type="radio" name="country_{$target_group['country_id']|intval}" id="country_{$target_group['country_id']|intval}_authorize"
																   value="{$target_group['country_id']|intval}" {if $target_group['country_is_allowed'] == "True"} checked="checked"{/if}/>
															<label  for="country_{$target_group['country_id']|intval}_authorize">{l s='Authorize' mod='expressmailing'}</label>
															<input type="radio" name="country_{$target_group['country_id']|intval}" id="country_{$target_group['country_id']|intval}_refuse"
																   value="{$target_group['country_id']|intval}" {if $target_group['country_is_allowed'] == "False"} checked="checked"{/if}/>
															<label  for="country_{$target_group['country_id']|intval}_refuse">{l s='Refuse' mod='expressmailing'}</label>
															<a class="slide-button btn"></a>
														</span>
													</div>
												</div>
											</td>
										</tr>
									{/if}
								{/foreach}

								{if $campaign_infos['count_recipients_on_system_redlist'] > 0}
									<tr class="odd">
										<td class="">{l s='Recipients on system redlist' mod='expressmailing'}</td>
										<td class="">{$campaign_infos['count_recipients_on_system_redlist']|intval}</td>
										<td class="text-right"></td>
									</tr>
								{/if}
								{if $campaign_infos['count_recipients_on_noads_redlist'] > 0}
									<tr class="odd">
										<td class="">{l s='Recipients on  "No ad" list' mod='expressmailing'}</td>
										<td class="">{$campaign_infos['count_recipients_on_noads_redlist']|intval}</td>
										<td class="text-right"></td>
									</tr>
								{/if}
								{if $campaign_infos['count_recipients_on_personnal_redlist'] > 0}
									<tr class="odd">
										<td class="">{l s='Recipients on personnal redlist' mod='expressmailing'}</td>
										<td class="">{$campaign_infos['count_recipients_on_personnal_redlist']|intval}</td>
										<td class="text-right"></td>
									</tr>
								{/if}
								{if $campaign_infos['count_duplicate_recipients'] > 0}
									<tr class="odd">
										<td class="">{l s='Recipients duplicates' mod='expressmailing'}</td>
										<td class="">{$campaign_infos['count_duplicate_recipients']|intval}</td>
										<td class="text-right"></td>
									</tr>
								{/if}
								{if $campaign_infos['count_invalid_recipients'] > 0}
									<tr class="odd">
										<td class="">{l s='Invalid recipients' mod='expressmailing'}</td>
										<td class="">{$campaign_infos['count_invalid_recipients']|intval}</td>
										<td class="text-right"></td>
									</tr>
								{/if}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="panel-footer">
			<a href="index.php?controller=AdminMarketingSStep2&campaign_id={$campaign_id|intval}&token={Tools::getAdminTokenLite('AdminMarketingSStep2')|escape:'html':'UTF-8'}" class="btn btn-default">
				<i class="process-icon-back"></i> {l s='Back' mod='expressmailing'}
			</a>
			<button type="submit" value="1"	id="configuration_form_submit_btn" name="submitSmsStep5" class="btn btn-default pull-right">
				<i class="process-icon-next"></i> {l s='Next' mod='expressmailing'}
			</button>
		</div>
	</div>
</form>




