{*
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

<div class="panel properties">
	{if $integrated}
		{foreach from=$types key=key item=type name=properties}
			{if $type|is_array}
				{if $key == 'attributes'}
					{assign var=title value={l s='Attributes' mod='pproperties'}}
					{assign var=name value={l s='Attribute' mod='pproperties'}}
				{elseif $key == 'texts'}
					{assign var=title value={l s='Texts' mod='pproperties'}}
					{assign var=name value={l s='Text' mod='pproperties'}}
				{elseif $key == 'dimensions'}
					{assign var=title value={l s='Dimensions' mod='pproperties'}}
					{assign var=name value={l s='Dimension' mod='pproperties'}}
				{/if}
				{if !$smarty.foreach.properties.first}
					<div style="margin-top: 15px;">&nbsp;</div>
				{/if}
				<div class="panel-heading">{$title}</div>
				<a class="btn btn-link add-new" href="{$currenturl}clickEditProperty&amp;mode=add&amp;type={$type.id}"><i class="icon-plus-sign"></i>{l s='Add new' mod='pproperties'}</a>
				<div style="height: 10px;">&nbsp;</div>
				<table class="table table-striped">
					<thead>
						<th class="center" style="width:50px;">{l s='ID' mod='pproperties'}</th>
						{if $type.metric}
						<th><b>{$name}</b> <span style="font-weight:normal;font-style:italic">{l s='metric' mod='pproperties'}</span></th>
						{/if}
						{if $type.nonmetric}
						<th><b>{$name}</b> <span style="font-weight:normal;font-style:italic">{l s='non metric (imperial/US)' mod='pproperties'}</span></th>
						{/if}
						<th class="center" style="width:80px;">{l s='Actions' mod='pproperties'}</th>
					</thead>
					<tbody>
						{foreach from=$properties key=id item=prop}
							{if $property_types.$id == $type.id}
								<tr>
									<td class="center">{$id}</td>
									{if $type.metric}
									<td>{$prop.text_1|pp_safeoutput_lenient}</td>
									{/if}
									{if $type.nonmetric}
									<td>{$prop.text_2|pp_safeoutput_lenient}</td>
									{/if}
									<td>
										<div class="btn-group-action">
											<div class="btn-group pull-right">
												<a href="{$currenturl}clickEditProperty&amp;mode=edit&amp;type={$type.id}&amp;id={$id}" class="btn btn-default"><i class="icon-pencil"></i> {l s='Edit' mod='pproperties'}</a>
												<button class="btn btn-default dropdown-toggle" data-toggle="dropdown">
													<span class="caret"></span>&nbsp;
												</button>
												<ul class="dropdown-menu">
													<li>
														<a href="{$currenturl}clickDeleteProperty&amp;mode=edit&amp;type={$type.id}&amp;id={$id}" onclick="return confirm('{l s='Do you really want to delete property #%s?' sprintf=$id mod='pproperties'}');">
															<i class="icon-trash"></i> {l s='Delete' mod='pproperties'}</a>
													</li>
												</ul>
											</div>
										</div>
									</td>
								</tr>
							{/if}
						{/foreach}
					</tbody>
				</table>
			{/if}
		{/foreach}
	{else}
		<div class="alert alert-warning">{$integration_message}</div>
	{/if}
</div>
