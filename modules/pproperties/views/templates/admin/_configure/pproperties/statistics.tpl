{*
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

<div class="panel statistics">
	{if $integrated}
		<div class="panel-heading"><i class="icon-AdminParentStats"></i> {l s='Statistics' mod='pproperties'}</div>
		{if isset($existing) || isset($missing)}
			{if isset($existing)}
				<table class="table table-striped pptable statistics" style="margin-bottom:15px;" cellspacing="0" cellpadding="0">
					<thead>
						<th class="center" style="width:35px;">{l s='ID' mod='pproperties'}</th>
						<th class="nowrap" style="width:140px;">{l s='template name' mod='pproperties'}</th>
						<th class="nowrap center" style="width:70px;">{l s='# of products' mod='pproperties'}</th>
						<th style="width:60%;">{l s='product IDs' mod='pproperties'}</th>
					</thead>
					<tbody>
						{foreach from=$existing item=row}
							<tr>
								<td class="center"><a href="{$currenturl}&amp;clickEditTemplate&amp;mode=edit&amp;id={$row.id}" title="{l s='Edit template "%s"' sprintf={$row.name} mod='pproperties'}">{$row.id}</a></td>
								<td class="nowrap"><a href="{$currenturl}&amp;clickEditTemplate&amp;mode=edit&amp;id={$row.id}" title="{l s='Edit template "%s"' sprintf={$row.name} mod='pproperties'}">{$row.name}</a></td>
								{if $row.count > 0}
									<td class="center">{$row.count}</td>
									<td>
									{foreach from=$row.products item=product name=products}
									<a href="{$linkAdminProducts}&id_product={$product.id_product}&updateproduct" target="_blank" title="{l s='Edit "%s"' sprintf={$product.name} mod='pproperties'}">{$product.id_product}</a>{if !$smarty.foreach.products.last},{/if}
									{/foreach}
									</td>
								{else}
									<td> </td>
									<td> </td>
								{/if}
							</tr>
						{/foreach}
					</tbody>
				</table>
			{/if}
			{if isset($missing)}
				<table class="table table-striped pptable statistics" style="margin-bottom:15px;" cellspacing="0" cellpadding="0">
					<caption>{l s='Products using non-existing templates' mod='pproperties'}</caption>
						<thead>
							<th>{l s='product ID (template ID)' mod='pproperties'}</th>
						</thead>
						<tbody>
							<tr><td>
							{foreach from=$missing item=product name=products}
								<a href="{$linkAdminProducts}&id_product={$product.id_product}&updateproduct" target="_blank" title="{$product.name}">{$product.id_product}</a>{if !$smarty.foreach.products.last},{/if}
							{/foreach}
							</td></tr>
						</tbody>
				</table>
			{/if}
			<div class="tab-hr">&nbsp</div>
		{/if}
		<div class="tab-buttons">
			<a class="btn btn-default pp-action-btn" href="{$currenturl}submitStatistics">{l s='Run analysis' mod='pproperties'}</a>
		</div>
	{else}
		<div class="alert alert-warning">{$integration_message}</div>
	{/if}
</div>
