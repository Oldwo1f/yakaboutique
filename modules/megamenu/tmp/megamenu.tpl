{*
*  @author Marcin Kubiak <support@add-ons.eu>
*  @copyright  Smart Soft
*  @license    Commercial license
*  International Registered Trademark & Property of Smart Soft
*}

<script type='text/javascript' src='{$this_path|escape:'htmlall':'UTF-8'}js/jquery.hoverIntent.minified.js'></script>
<script type='text/javascript' src='{$this_path|escape:'htmlall':'UTF-8'}js/jquery.dcmegamenu.1.3.3.js'></script>
{if $MEGAMENU_COLOR == 'default'}
  <link href="{$this_path|escape:'htmlall':'UTF-8'}css/megamenu/dcmegamenu.css" rel="stylesheet" type="text/css" />
{else}
  <link href="{$this_path|escape:'htmlall':'UTF-8'}css/megamenu/skins/{$MEGAMENU_COLOR|escape:'htmlall':'UTF-8'}.css" rel="stylesheet" type="text/css" />
{/if}
<div style="clear:both;"></div>
<div id="megamenu_wrapper" class="{$MEGAMENU_COLOR|escape:'htmlall':'UTF-8'}" style="opacity:0;padding:0 15px;z-index: 10001;margin:{$MEGAMENU_MARGINTOP|intval}px {$MEGAMENU_MARGINRIGHT|intval}px {$MEGAMENU_MARGINBOTTOM|intval}px {$MEGAMENU_MARGINLEFT|intval}px;width:100%;clear:both;">
    
	<ul id="mega-menu-1" class="mega-menu" >
        <li class="mobile">
            <a href="" id="home">
                <img src="{$this_path|escape:'htmlall':'UTF-8'}css/megamenu/skins/images/home.png" alt="home"/>
            </a>
            <a  href="#" id="toggleMenu">
                <img src="{$this_path|escape:'htmlall':'UTF-8'}css/megamenu/skins/images/menu.png" alt="home"/>
            </a>
        </li>
		{foreach from=$blockCategTree.children item=child name=blockCategTree}
			{if $smarty.foreach.blockCategTree.last}
				{include file="$branche_tpl_path" node=$child last='true'}
			{else}
				{include file="$branche_tpl_path" node=$child}
			{/if}
		{/foreach}
	</ul>

    <script type="text/javascript">
    
		var ww = document.body.clientWidth;
		
	    {literal}  
        $(document).ready(function() {  
			if (ww < 768) 
            {
				$(".mega-menu").show();
                
				$('#mega-menu-1').dcMegaMenu({  {/literal}
			        rowItems: 1,
			        speed: '{$MEGAMENU_SPEED|escape:'htmlall':'UTF-8'}',
			        effect: '{$MEGAMENU_EFFECT|escape:'htmlall':'UTF-8'}',
			        event: 'click',
			        fullWidth: 1
			    {literal}  });  
				
				$(".mega-menu > li").not(".mobile").hide(); 

		    }
			else if (ww >= 768) 
            {
				 $('#mega-menu-1').dcMegaMenu({  {/literal}
			        rowItems: '{$MEGAMENU_ROWITEMS|escape:'htmlall':'UTF-8'}',
			        speed: '{$MEGAMENU_SPEED|escape:'htmlall':'UTF-8'}',
			        effect: '{$MEGAMENU_EFFECT|escape:'htmlall':'UTF-8'}',
			        event: '{$MEGAMENU_EVENT|escape:'htmlall':'UTF-8'}',
			        fullWidth: '{$MEGAMENU_FULLWIDTH|escape:'htmlall':'UTF-8'}'
			    {literal}  });    
			}      
            $("ul#mega-menu-1 > li").click(function() {
                 $("ul#mega-menu-1 > li").removeClass('mega-active');
                 $(this).addClass('mega-active');
            });

            $('a.mega-hdr-a').css('height', 'auto');
            
			$('#megamenu_wrapper').css('opacity', 1);

            $("#toggleMenu").click(function(e) {
                e.preventDefault();
                $(this).toggleClass("active");
                $("#mega-menu-1 > li:not(li.mobile)").slideToggle("fast");
            }); 
            
            $("#megamenu_wrapper li.mega-unit.mega-hdr").each( function(i, n){
                $(this).bind('click', function(){
                    document.location = $(this).find('a.mega-hdr-a:first').attr('href');
                });
            });  
	    });    
	    {/literal}  
    </script>      
</div>