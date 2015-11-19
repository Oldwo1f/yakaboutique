{*
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

<div class="panel integration">
	<div class="panel-heading"><i class="icon-AdminParentPreferences"></i> {l s='Integration' mod='pproperties'}</div>
	{if isset($integration.confirmation)}{$integration.confirmation}{/if}
	{if isset($integration.display)}
		{foreach from=$integration.display key=title item=value}
			{if $value|is_array}
				<br/><div><b>{$title}</b></div>
				{foreach from=$value item=val}
					<div>{$val}</div>
				{/foreach}
			{else}
				<div>{$value}</div>
			{/if}
		{/foreach}
		{if isset($integration.hasDesc)}
			<hr/>
			<div class="alert alert-warning">
				{$integration_instructions_link="<a href='{$integration._path}integration_instructions.pdf' target='_blank'>"}
				<div><strong>{l s='If you see "Integration test failed" message, please, run setup by clicking on the "Run Setup" button.' mod='pproperties'}</strong></div><br/>
				<div>{l s='Read the [1]"%s"[/1] document for more information' sprintf=$integration_instructions mod='pproperties' tags=[{$integration_instructions_link}]}.</div>
			</div>
		{/if}
		<div class="tab-hr">&nbsp</div>
	{/if}
	<div class="tab-buttons">
		<a class="btn btn-default pp-action-btn runSetup" href="{$currenturl}{$integration.btn_action}">{$integration.btn_title}</a>
	</div>
</div>
