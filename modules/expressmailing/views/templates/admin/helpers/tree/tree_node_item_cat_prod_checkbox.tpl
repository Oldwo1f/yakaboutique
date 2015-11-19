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

<li class="tree-item{if isset($node['disabled']) && $node['disabled'] == true} tree-item-disable{/if}">
	<span class="tree-item-name{if isset($node['disabled']) && $node['disabled'] == true} tree-item-name-disable{/if}
		  {if isset($node['id_category']) && isset($selected_categories_em) && in_array($node['id_category'], $selected_categories_em)}
			  tree-selected
		  {/if}
		  {if isset($node['id_product']) && isset($selected_products_em) && in_array($node['id_product'], $selected_products_em)}
			  tree-selected
		  {/if}
		  ">
		<input type="checkbox"
			   name="{if isset($node['id_category'])}{$input_name|escape:'htmlall':'UTF-8'}-categories{else}{$input_name|escape:'htmlall':'UTF-8'}-products{/if}[]"
			   value="{if isset($node['id_category'])}{$node['id_category']|intval}{else}{$node['id_product']|intval}{/if}"{if isset($node['disabled']) && $node['disabled'] == true}
			   disabled="disabled"{/if}
			   {if isset($node['id_category']) && isset($selected_categories_em) && in_array($node['id_category'], $selected_categories_em)}
				   checked="true"
			   {/if}
			   {if isset($node['id_product']) && isset($selected_products_em) && in_array($node['id_product'], $selected_products_em)}
				   checked="true"
			   {/if}
			   />
		<i class="tree-dot"></i>
		<label class="tree-toggler">{$node['name']|escape:'htmlall':'UTF-8'}</label>
	</span>
</li>