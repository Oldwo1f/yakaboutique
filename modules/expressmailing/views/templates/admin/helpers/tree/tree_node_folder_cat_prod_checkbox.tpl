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

<li class="tree-folder">
	<span class="tree-folder-name{if isset($node['disabled']) && $node['disabled'] == true} tree-folder-name-disable{/if}
		  {if isset($node['id_category']) && isset($selected_categories_em) && in_array($node['id_category'], $selected_categories_em)}
			  tree-selected
		  {/if}
		  {if isset($node['id_product']) && isset($selected_products_em) && in_array($node['id_product'], $selected_products_em)}
			  tree-selected
		  {/if}
		  ">
		{if $node['id_category'] != $root_category}
			<input type="checkbox"
				   name="{if isset($node['id_category'])}{$input_name|escape:'html':'UTF-8'}-categories{else}{$input_name|escape:'html':'UTF-8'}-products{/if}[]"
				   value="{if isset($node['id_category'])}{$node['id_category']|escape:'html':'UTF-8'}{else}{$node['id_product']|escape:'html':'UTF-8'}{/if}"
				   {if isset($node['disabled']) && $node['disabled'] == true} disabled="disabled"{/if}
				   {if isset($node['id_category']) && isset($selected_categories_em) && in_array($node['id_category'], $selected_categories_em)}
					   checked="true"
				   {/if}
				   {if isset($node['id_product']) && isset($selected_products_em) && in_array($node['id_product'], $selected_products_em)}
					   checked="true"
				   {/if}
				   />
		{/if}
		<i class="icon-folder-close"></i>
		<label class="tree-toggler">{$node['name']|escape:'html':'UTF-8'}{if isset($node['selected_childs']) && (int)$node['selected_childs'] > 0} {l s='(%s selected)' mod='expressmailing' sprintf=$node['selected_childs']}{/if}</label>
	</span>
	<ul class="tree">
		{$children|unescape}
	</ul>
</li>