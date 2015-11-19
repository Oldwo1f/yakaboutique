{*
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

<div class="panel templates">
	{if $integrated}
		<div class="panel-heading"><i class="icon-template"></i> {l s='Templates' mod='pproperties'}</div>
		<a class="btn btn-link add-new" href="{$currenturl}clickEditTemplate&amp;mode=add"><i class="icon-plus-sign"></i>{l s='Add new' mod='pproperties'}</a>
		<div style="height: 10px;">&nbsp;</div>
		<table class="table templates">
			<thead>
				<th class="center">{l s='ID' mod='pproperties'}</th>
				<th>{l s='name' mod='pproperties'}</th>
				<th>{l s='quantity policy' mod='pproperties'}</th>
				<th>{l s='approx quantity' mod='pproperties'}</th>
				<th>{l s='display mode' mod='pproperties'} *</th>
				<th>{l s='explanation' mod='pproperties'} **</th>
				<th>{l s='quantity text' mod='pproperties'}</th>
				<th>{l s='price text' mod='pproperties'}</th>
				<th>{l s='unit price text' mod='pproperties'}</th>
				<th>{l s='unit price ratio' mod='pproperties'}</th>
				<th>{l s='minimum quantity' mod='pproperties'}</th>
				<th>{l s='default quantity' mod='pproperties'}</th>
				<th class="center" style="width:80px;" colspan="2">{l s='Actions' mod='pproperties'}</th>
			</thead>
			{foreach from=$templates item=template}
				{assign var=action value="{$currenturl}id={$template.id_pp_template}&amp;"}
				{assign var=editTemplate value="{$action}clickEditTemplate&amp;mode="}
				{assign var=onclick value="onclick=\"document.location='{$editTemplate}edit'\""}
				<tbody>
					<tr>
						<td class="pointer center" rowspan="2" {$onclick}>{$template.id_pp_template}</td>
						<td class="p nowrap" {$onclick}>{$template.name}</td>
						<td class="p nowrap center" {$onclick}>{if $template.pp_qty_policy == 1}{l s='whole' mod='pproperties'}{elseif $template.pp_qty_policy == 2}{l s='fract' mod='pproperties'}{else}{l s='items' mod='pproperties'}{/if}</td>
						<td class="p nowrap center" {$onclick}>{if $template.pp_qty_mode}{l s='yes' mod='pproperties'}{else}&nbsp;{/if}</td>
						<td class="p nowrap center" {$onclick}>{if $template.display_mode}{$template.display_mode}{else}&nbsp;{/if}</td>
						<td class="p nowrap" {$onclick}>{if $template.pp_explanation}{l s='yes' mod='pproperties'} ({$template.pp_bo_buy_block_index}){else}&nbsp;{/if}</td>
						<td class="p nowrap" {$onclick}>{$template.pp_qty_text}</td>
						<td class="p nowrap" {$onclick}>{$template.pp_price_text}</td>
						<td class="p nowrap" {$onclick}>{$template.pp_unity_text}</td>
						<td class="p nowrap" {$onclick}>{if (float)$template.pp_unit_price_ratio == 0}&nbsp;{else}{(float)$template.pp_unit_price_ratio}{/if}</td>
						{if $template.pp_qty_policy == 2 && $template.pp_ext != 1}{$db_minimal_quantity = (float)$template.db_minimal_quantity}{else}{$db_minimal_quantity = (int)$template.db_minimal_quantity}{/if}
						<td class="p nowrap" {$onclick}>{if $db_minimal_quantity > 0}{$db_minimal_quantity|formatQty}{if $template.pp_ext != 1} {$template.pp_bo_qty_text}{/if}{else}&nbsp;{/if}</td>
						{if $template.pp_qty_policy == 2 && $template.pp_ext != 1}{$db_default_quantity = (float)$template.db_default_quantity}{else}{$db_default_quantity = (int)$template.db_default_quantity}{/if}
						<td class="p nowrap" {$onclick}>{if $db_default_quantity > 0}{$db_default_quantity|formatQty}{if $template.pp_ext != 1} {$template.pp_bo_qty_text}{/if}{else}&nbsp;{/if}</td>
						<td class="text-right" rowspan="2">
							<a href="{$currenturl}clickHiddenStatusTemplate&amp;show={(int)$template.pp_bo_hidden}&amp;id={$template.id_pp_template}" title="{if (int)$template.pp_bo_hidden}{l s='hidden' mod='pproperties'}{else}{l s='visible' mod='pproperties'}{/if}" class="list-action-enable action-{if (int)$template.pp_bo_hidden}disabled{else}enabled{/if}"><i class="icon-check{if (int)$template.pp_bo_hidden} hidden{/if}"></i><i class="icon-remove{if !(int)$template.pp_bo_hidden} hidden{/if}"></i></a>
						</td>
						<td class="text-right" rowspan="2">
							<div class="btn-group-action">
								<div class="btn-group pull-right">
									<a href="{$editTemplate}edit" class="btn btn-default"><i class="icon-pencil"></i> {l s='Edit' mod='pproperties'}</a>
									<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
										<span class="caret"></span>&nbsp;
									</button>
									<ul class="dropdown-menu">
										<li>
											<a href="{$editTemplate}copy">
												<i class="icon-copy"></i> {l s='Copy' mod='pproperties'}
											</a>
										</li>
										<li>
											<a href="{$action}clickDeleteTemplate"
												onclick="return confirm('{l s='Do you really want to delete template #%s: %s?' sprintf=[$template.id_pp_template, $template.name] mod='pproperties'}');">
												<i class="icon-trash"></i> {l s='Delete' mod='pproperties'}
											</a>
										</li>
									</ul>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td class="pointer nowrap description" colspan="11" {$onclick}>{$template.description}</td>
					</tr>
				</tbody>
			{/foreach}
		</table>
		<table class="table table-striped pptable display_mode_table">
			<caption>* {l s='display mode' mod='pproperties'}</caption>
			<thead><th>#</th><th>{l s='Description' mod='pproperties'}</th></thead>
			{foreach from=$display_mode_text key=key item=text}
				<tr><td>{$key+1}</td><td>{$text}</td></tr>
			{/foreach}
		</table>
		<table class="table table-striped pptable buy_block_table">
			<caption>** {l s='explanation as appears in shop (if set to yes)' mod='pproperties'}</caption>
			<thead><th>{l s='ID' mod='pproperties'}</th><th>{l s='Text' mod='pproperties'}</th></thead>
			<tbody>
				{foreach from=$buy_block_text key=key item=text}
					<tr><td>{$key}</td><td>{$text}</td></tr>
				{/foreach}
			</tbody>
		</table>
		<div class="tab-hr">&nbsp</div>
		<div class="tab-buttons">
			<a class="btn btn-default" href="{$currenturl}submitRestoreDefaults" onclick="return confirm('{l s='Restore Defaults?' mod='pproperties'}');"><i class="icon-star"></i> {l s='Restore Defaults' mod='pproperties'}</a>
		</div>
		<p style="margin-top:10px;">{l s='Restore Defaults button restores templates to the factory settings known at the installation time. User created templates are not affected.' mod='pproperties'}</p>
	{else}
		<div class="alert alert-warning">{$integration_message}</div>
	{/if}
</div>
