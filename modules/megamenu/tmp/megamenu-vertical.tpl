{*
*  @author Marcin Kubiak <support@add-ons.eu>
*  @copyright  Smart Soft
*  @license    Commercial license
*  International Registered Trademark & Property of Smart Soft
*}

<script type='text/javascript' src='{$this_path|escape:'html'}js/jquery.hoverIntent.minified.js'></script>
<script type='text/javascript' src='{$this_path|escape:'html'}js/jquery.dcverticalmegamenu.1.3.js'></script>
{if $MEGAMENU_COLOR == 'default'}
  <link href="{$this_path|escape:'html'}css/megamenu-vertical/skins/dcverticalmegamenu.css" rel="stylesheet" type="text/css" />
{else}
  <link href="{$this_path|escape:'html'}css/megamenu-vertical/skins/{$MEGAMENU_COLOR|escape:'html'}.css" rel="stylesheet" type="text/css" />
{/if}

<div id="megamenu-vertical_wrapper" class="{$MEGAMENU_COLOR|escape:'html'}" style="margin:{$MEGAMENU_MARGINTOP|intval}px {$MEGAMENU_MARGINRIGHT|intval}px {$MEGAMENU_MARGINBOTTOM|intval}px {$MEGAMENU_MARGINLEFT|intval}px;">
	<ul id="mega-menu-vertical-1" class="mega-menu-vertical">
		{foreach from=$blockCategTree.children item=child name=blockCategTree}
			{if $smarty.foreach.blockCategTree.last}
				{include file="$branche_tpl_path" node=$child last='true'}
			{else}
				{include file="$branche_tpl_path" node=$child}
			{/if}
		{/foreach}
	</ul>

    <script type="text/javascript">
	    {literal}  $(document).ready(function() {  
		  $('ul.mega-menu-vertical').dcVerticalMegaMenu({  {/literal}
	        rowItems: '{$MEGAMENU_ROWITEMS|escape:"html"}',
	        speed: '{$MEGAMENU_SPEED|escape:"html"}',
	        effect: '{$MEGAMENU_EFFECT|escape:"html"}',
	        event: '{$MEGAMENU_EVENT|escape:"html"}',
	        direction: 'right'
	    {literal}  }); });    
$("ul#mega-menu-1 > li").click(function() {
			            $("ul#mega-menu-1 > li").removeClass('mega-active');
			            $(this).addClass('mega-active');
			      });
	     {/literal}   
    </script>
</div>