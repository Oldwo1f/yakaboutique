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

{if isset($smarty_fax_tickets) && is_array($smarty_fax_tickets)}

	<div class="alert alert-danger">
		{l s='Your mailing contains %d contacts, but you have only %d credits on your account.' mod='expressmailing' sprintf=[$count_fax,$remaining_fax]}<br>
		{l s='You must buy additional credits (number of tickets can be changed at the next step).' mod='expressmailing'}
	</div>

    <table>
        {foreach $smarty_fax_tickets as $key => $ticket}
            {if isset($ticket.promo_ending) && $ticket.promo_ending > time()}
                {assign var=promotion value=true}
            {else}
                {assign var=promotion value=false}
            {/if}
            <tr>
                <td class="radio" style="padding-bottom: 2px; border-bottom: medium dotted #C5E9F3{if $key == 0}; border-top: medium dotted #C5E9F3{/if}">
                    {if $promotion}
                        {if isset($ticket.promo_ending)}
                            <span style="display: block; color: red">{l s='Promotion until' mod='expressmailing'} {$tool_date->getLocalizableDate($ticket.promo_ending)|escape:'html':'UTF-8'}</span>
                        {/if}
                        {if isset($ticket.promo_desc) and !empty($ticket.promo_desc)}
                            <span style="display: block; color: red; font-weight: bold">{$ticket.promo_desc|escape:'html':'UTF-8'}</span>
                        {/if}
                    {/if}
                    <label>
                        <input type="radio" name="product" value="{$ticket.product_ref|escape:'htmlall':'UTF-8'}" {if ($cart_product == '' and $key == 0) or ($cart_product == $ticket.product_ref) }checked="checked" {/if}/>
                        <span style="width:170px; display:inline-block">
                            {$ticket.product_units|number_format:0:",":"."} {$fax_credits|escape:'htmlall':'UTF-8'}<br>{$ticket.product_desc|escape:'html':'UTF-8'}
                        </span>
                        <span style="width:170px; display:inline-block">
                            <span style="{if $promotion}text-decoration: line-through{else}font-weight: bold{/if}">
                                {$ticket.normal_price|number_format:2:",":"."} {$euro_symbol|escape:'htmlall':'UTF-8'}
                            </span>
                            {if $promotion}
                                &nbsp;&nbsp;<span style="color: red; font-weight: bold">{$ticket.promo_price|number_format:2:",":"."} {$euro_symbol|escape:'htmlall':'UTF-8'}</span>
                            {/if}
                            <br>
                            {if $promotion}
                                {$fax_per_unit|sprintf:($ticket.promo_price / $ticket.product_units)|escape:'htmlall':'UTF-8'}
                            {else}
                                {$fax_per_unit|sprintf:($ticket.normal_price / $ticket.product_units)|escape:'htmlall':'UTF-8'}
                            {/if}
                        </span>
                    </label>
                </td>
            </tr>
        {/foreach}
    </table>
{/if}

{if isset($smarty_sms_tickets) && is_array($smarty_sms_tickets)}

	<div class="alert alert-danger">
		{l s='Your mailing contains %d contacts, but you have only %d credits on your account.' mod='expressmailing' sprintf=[$count_sms,$remaining_sms]}<br>
		{l s='You must buy additional credits (number of tickets can be changed at the next step).' mod='expressmailing'}
	</div>

    <table>
        {foreach $smarty_sms_tickets as $key => $ticket}
            {if isset($ticket.promo_ending) && $ticket.promo_ending > time()}
                {assign var=promotion value=true}
            {else}
                {assign var=promotion value=false}
            {/if}
            <tr>
                <td class="radio" style="padding-bottom: 2px; border-bottom: medium dotted #C5E9F3{if $key == 0}; border-top: medium dotted #C5E9F3{/if}">
                    {if $promotion}
                        {if isset($ticket.promo_ending)}
                            <span style="display: block; color: red">{l s='Promotion until' mod='expressmailing'} {$tool_date->getLocalizableDate($ticket.promo_ending)|escape:'html':'UTF-8'}</span>
                        {/if}
                        {if isset($ticket.promo_desc) and !empty($ticket.promo_desc)}
                            <span style="display: block; color: red; font-weight: bold">{$ticket.promo_desc|escape:'html':'UTF-8'}</span>
                        {/if}
                    {/if}
                    <label>
                        <input type="radio" name="product" value="{$ticket.product_ref|escape:'html':'UTF-8'}" {if ($cart_product == '' and $key == 0) or ($cart_product == $ticket.product_ref) }checked="checked" {/if}/>
                        <span style="width:170px; display:inline-block">
                            {$ticket.product_units|number_format:0:",":"."} {$sms_credits|escape:'htmlall':'UTF-8'}<br>{$ticket.product_desc|escape:'html':'UTF-8'}
                        </span>
                        <span style="width:170px; display:inline-block">
                            <span style="{if $promotion}text-decoration: line-through{else}font-weight: bold{/if}">
                                {$ticket.normal_price|number_format:2:",":"."|escape:'htmlall':'UTF-8'} {$euro_symbol|escape:'htmlall':'UTF-8'}
                            </span>
                            {if $promotion}
                                &nbsp;&nbsp;<span style="color: red; font-weight: bold">{$ticket.promo_price|number_format:2:",":"."|escape:'htmlall':'UTF-8'} {$euro_symbol|escape:'htmlall':'UTF-8'}</span>
                            {/if}
                            <br>
                            {if $promotion}
                                {$sms_per_unit|sprintf:($ticket.promo_price / $ticket.product_units)|escape:'htmlall':'UTF-8'}
                            {else}
                                {$sms_per_unit|sprintf:($ticket.normal_price / $ticket.product_units)|escape:'htmlall':'UTF-8'}
                            {/if}
                        </span>
                    </label>
                </td>
            </tr>
        {/foreach}
    </table>
{/if}