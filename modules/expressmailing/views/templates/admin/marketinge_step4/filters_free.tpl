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

<div class="panel" style="height: 430px; margin-bottom: 0px; padding-bottom: 0px">
	<div class="panel-heading">{l s='Customer filters' mod='expressmailing'}</div>
	<div class="form-wrapper" style="overflow-x: hidden; overflow-y: auto; padding-left: 5px; margin: -12px -15px 3px -10px; height: 390px;">

		{if count($shops_by_sharing_shop_group) > 1}
			<div class="form-group">
				<div class="title text-left">{l s='Multi-shop :' mod='expressmailing'}</div>
				<div class="col-lg-12" style="padding-left: 20px">
					{foreach $shops_by_sharing_shop_group as $sharing_shop_group}
						{if count($sharing_shop_group['shop_ids']) > 1}
							<div class="checkbox gamification_notif no-float">
								<label for="group_id[{$sharing_shop_group.id_shop_group|escape:'html':'UTF-8'}]">
									<input type="checkbox" value="1" class="checkbox-inline" id="group_id[{$sharing_shop_group.id_shop_group|escape:'html':'UTF-8'}]" 
										   name="group_id[{$sharing_shop_group.id_shop_group|escape:'html':'UTF-8'}]" {if in_array($sharing_shop_group.id_shop_group, $checked_groups_shops['shop_groups'])}checked="checked"{/if} onchange="checkChilds(this)"/>
									<div style="display:inline-block;">
										{$sharing_shop_group['shop_group_name']} :
										{foreach from=$sharing_shop_group.shop_names key=shop_key item=shop_name}
											<label for="shop_ids[{$sharing_shop_group.shop_ids.$shop_key|escape:'html':'UTF-8'}]">
												<input type='hidden' name="groups_shops_ids[{$sharing_shop_group.id_shop_group|escape:'html':'UTF-8'}][{$sharing_shop_group.shop_ids.$shop_key|escape:'html':'UTF-8'}]" 
													    value="{if in_array($sharing_shop_group.shop_ids.$shop_key, $checked_groups_shops['shops'])}1{else}0{/if}"/>
												<input type="checkbox" value="1" disabled="true" class="checkbox-inline" name="shop_ids[{$sharing_shop_group.shop_ids.$shop_key|escape:'html':'UTF-8'}]"
													   id="shop_ids[{$sharing_shop_group.shop_ids.$shop_key|escape:'html':'UTF-8'}]" {if in_array($sharing_shop_group.shop_ids.$shop_key, $checked_groups_shops['shops'])}checked="checked"{/if}/>
												{$shop_name|escape:'html':'UTF-8'}
											</label>
										{/foreach}
									</div>
									&nbsp; <span class="badge" style="font-size: smaller; vertical-align: top">{$sharing_shop_group.shop_count|intval}</span>
								</label>
							</div>
						{else}
							<div class="checkbox gamification_notif no-float">
								<label for="groups_shops_ids[{$sharing_shop_group.id_shop_group|escape:'html':'UTF-8'}][{$sharing_shop_group.shop_ids.0|escape:'html':'UTF-8'}]">
									<input type="checkbox" value="1" class="checkbox-inline" 
										   id="groups_shops_ids[{$sharing_shop_group.id_shop_group|escape:'html':'UTF-8'}][{$sharing_shop_group.shop_ids.0|escape:'html':'UTF-8'}]" 
										   name="groups_shops_ids[{$sharing_shop_group.id_shop_group|escape:'html':'UTF-8'}][{$sharing_shop_group.shop_ids.0|escape:'html':'UTF-8'}]"
										   {if in_array($sharing_shop_group.shop_ids.0, $checked_groups_shops['shops'])}checked="checked"{/if}/>
									{$sharing_shop_group.shop_names.0|escape:'html':'UTF-8'}
									&nbsp; <span class="badge" style="font-size: smaller; vertical-align: top">{$sharing_shop_group.shop_count|intval}</span>
								</label>
							</div>
						{/if}
					{/foreach}
				</div>
			</div>
		{/if}
		<div class="form-group">
			<div class="title text-left">{l s='Groups :' mod='expressmailing'}</div>
			<div class="col-lg-12" style="padding-left: 20px">
				{foreach $customers_groups as $group}
				<div class="checkbox gamification_notif no-float">
					<label for="groups[{$group.id_group|intval}]"><input type="checkbox" value="1" class="checkbox-inline" id="groups[{$group.id_group|intval}]" name="groups[{$group.id_group|intval}]" {if $group.checked}checked="checked"{/if}>{$group.name|escape:'html':'UTF-8'} &nbsp; <span class="badge" style="font-size: smaller">{$group.total|intval}</span></label>
				</div>
				{/foreach}
			</div>
		</div>

		<div class="form-group">
			<div class="title text-left">{l s='Purchase language :' mod='expressmailing'}</div>
			<div class="col-lg-12" style="padding-left: 20px">
				{foreach $customers_langs as $lang}
				<div class="checkbox no-float">
					<label for="langs[{$lang.id_lang|intval}]"><input type="checkbox" value="1" class="checkbox-inline" id="langs[{$lang.id_lang|intval}]" name="langs[{$lang.id_lang|intval}]" {if $lang.checked}checked="checked"{/if}>{$lang.name|escape:'html':'UTF-8'} &nbsp; <span class="badge" style="font-size: smaller">{$lang.total|intval}</span></label>
				</div>
				{/foreach}
			</div>
		</div>

		<div class="form-group">
			<div class="title text-left">{l s='Customers status :' mod='expressmailing'}</div>
			<div class="col-lg-12" style="padding-left: 20px">
				<div class="checkbox no-float">
					<label for="subscriptions_campaign_optin"><input type="checkbox" {if $subscriptions_campaign_optin}checked="checked"{/if} value="1" class="checkbox-inline" id="subscriptions_campaign_optin" name="subscriptions_campaign_optin">{l s='Customers Opt-in' mod='expressmailing'} &nbsp; <span class="badge" style="font-size: smaller">{$customers_suscriptions.total_optin|intval}</span></label>
				</div>
				<div class="checkbox no-float">
					<label for="subscriptions_campaign_newsletter"><input type="checkbox" {if $subscriptions_campaign_newsletter}checked="checked"{/if} value="1" class="checkbox-inline" id="subscriptions_campaign_newsletter" name="subscriptions_campaign_newsletter">{l s='Customers Newsletter' mod='expressmailing'} &nbsp; <span class="badge" style="font-size: smaller">{$customers_suscriptions.total_newsletter|intval}</span></label>
				</div>
				<div class="checkbox no-float">
					<label for="subscriptions_campaign_active"><input type="checkbox" {if $subscriptions_campaign_active}checked="checked"{/if} value="1" class="checkbox-inline" id="subscriptions_campaign_active" name="subscriptions_campaign_active">{l s='Customers Enabled' mod='expressmailing'} &nbsp; <span class="badge" style="font-size: smaller">{$customers_suscriptions.total_active|intval}</span></label>
				</div>
			</div>
		</div>
		
		<div class="form-group">
			<div class="title text-left">{l s='My shop :' mod='expressmailing'}</div>
			<div class="col-lg-12" style="padding-left: 20px">
				<div class="checkbox no-float">
					<label for="subscriptions_campaign_guest"><input type="checkbox" {if $subscriptions_campaign_guest}checked="checked"{/if} value="1" class="checkbox-inline" id="subscriptions_campaign_guest" name="subscriptions_campaign_guest">{l s='Include newsletter subscribers' mod='expressmailing'} &nbsp; <span class="badge" style="font-size: smaller">{$customers_suscriptions.total_guest|intval}</span></label>
				</div>
			</div>
		</div>

	</div>
</div>

<script type="text/javascript">
	function checkChilds($obj){
		if($($obj).prop('checked')){
			$($obj).parent().find('input[type=checkbox]').each(function($id, $checkbox){
				$($checkbox).prop('checked', true)
			});
			$($obj).parent().find('input[type=hidden]').each(function($id, $hidden){
				$($hidden).val(1);
			});
		} else {
			$($obj).parent().find('input[type=checkbox]').each(function($id, $checkbox){
				$($checkbox).prop('checked', false)
			});
			$($obj).parent().find('input[type=hidden]').each(function($id, $hidden){
				$($hidden).val(0);
			});
		}
	}
</script>