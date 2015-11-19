{*
*  @author Marcin Kubiak <support@add-ons.eu>
*  @copyright  Smart Soft
*  @license    Commercial license
*  International Registered Trademark & Property of Smart Soft
*}

<li id="menu-item-{$node.id}" class="{if $MEGAMENU_DISPLAYIMAGES == 1} hasimage {/if}" style="{if $MEGAMENU_DISPLAYIMAGES == 1 && $node.currentDepth == 2}background: url('{$node.image}') no-repeat scroll center 0 transparent;padding: {$node.imageheight}px 0 0;{/if}">
	<a href="{$node.link}" title="{$node.desc|strip_tags|escape:htmlall:'UTF-8'}" class="{if $node.desc == 'display more'} more {else} {/if}" >
	  {$node.name|escape:html:'UTF-8'}
	</a>
	{if $node.children|@count > 0}
		<ul>
		{foreach from=$node.children item=child name=categoryTreeBranch}
			{if isset($smarty.foreach.categoryTreeBranch) && $smarty.foreach.categoryTreeBranch.last}
				{include file="$branche_tpl_path" node=$child last='true'}
			{else}
				{include file="$branche_tpl_path" node=$child last='false'}
			{/if}
		{/foreach}
		</ul>
	{/if}
</li>
