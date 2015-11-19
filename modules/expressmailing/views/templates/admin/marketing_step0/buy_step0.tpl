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

<form id="buy_form" class="defaultForm form-horizontal adminmarketingx" action="index.php?controller={if (!empty($credential_fax) || !empty($credential_sms))}AdminMarketingBuy&token={Tools::getAdminTokenLite('AdminMarketingBuy')|escape:'html':'UTF-8'}{else}AdminMarketingInscription&token={Tools::getAdminTokenLite('AdminMarketingInscription')|escape:'html':'UTF-8'}{/if}" method="post" enctype="multipart/form-data" novalidate>
	<div class="panel pricingblock" id="fieldset_0">
		<div class="panel-heading">
            <i class="icon-cogs"></i>&nbsp;{l s='Loss leader pricing' mod='expressmailing'}
            {if !empty($tool_tip)}
                <span class="panel-heading-action">
                    <a class="list-toolbar-btn" href="#">
                        <span class="label-tooltip" data-placement="left" data-html="true" data-original-title="{$tool_tip|escape:'html':'UTF-8'}" data-toggle="tooltip" title="">
                            <i class="process-icon-help"></i>
                        </span>
                    </a>
                </span>
            {/if}
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <div>
                    <table class="media_choice">
						<tr>
							<td class="colorcell"><div class="greencell"></div></td>
							<td class="linkcell">{l s='Emailing' mod='expressmailing'}</td>
							<td class="commentcell">
								{l s='Up to %d free email per day' mod='expressmailing' sprintf=$broadcast_max_daily}
								<b>&nbsp;-&nbsp;{l s='ou' mod='expressmailing'}&nbsp;-&nbsp;</b>
								<a id="em_bying_link_email2"{if $smarty_email_disabled} disabled="disabled"{/if}>{l s='Sign Up for a Premium Plan' mod='expressmailing' sprintf=$smarty_email_lowest_price}</a>
								{if !empty($smarty_email_promotion)}
									<span class="badge"><i class="icon-star"></i> <small>{l s='Discount' mod='expressmailing'}</small></span>
								{/if}
							</td>
						</tr>
						<tr>
							<td class="colorcell"><div class="redcell"></div></td>
							<td class="linkcell">{l s='Fax' mod='expressmailing'}</td>
							<td class="commentcell">
								{l s='Lowest price to Metropolitan France' mod='expressmailing'}
								<b>&nbsp;-&nbsp;</b>
								{capture assign="smarty_fax_lowest_price"}{$smarty_fax_lowest_price|string_format:"%.3f"|replace:'0.':'0,'|rtrim:'0'|escape:'htmlall':'UTF-8'}{/capture}
								{capture assign="fprice"}{l s='From %s € per page' mod='expressmailing' sprintf=$smarty_fax_lowest_price}{/capture}
								<a id="em_bying_link_fax2"{if $smarty_fax_disabled} disabled="disabled"{/if}>{$fprice|escape:'html':'UTF-8'}</a>
								{if !empty($smarty_fax_promotion)}
									<b>&nbsp;-&nbsp;</b>
									<span class="badge"><i class="icon-star"></i> <small>{l s='Discount' mod='expressmailing'}</small></span>
								{/if}
							</td>
						</tr>
						<tr>
							<td class="colorcell"><div class="bluecell"></div></td>
							<td class="linkcell">{l s='Sms' mod='expressmailing'}</td>
							<td class="commentcell">
								{l s='Lowest price to Metropolitan France' mod='expressmailing'}
								<b>&nbsp;-&nbsp;</b>
								{capture assign="smarty_sms_lowest_price"}{$smarty_sms_lowest_price|string_format:"%.3f"|replace:'0.':'0,'|rtrim:'0'|escape:'htmlall':'UTF-8'}{/capture}
								{capture assign="fprice"}{l s='From %s € per sms' mod='expressmailing' sprintf=$smarty_sms_lowest_price}{/capture}
								<a id="em_bying_link_sms2"{if $smarty_sms_disabled} disabled="disabled"{/if}>{$fprice|escape:'html':'UTF-8'}</a>
								{if !empty($smarty_sms_promotion)}
									<b>&nbsp;-&nbsp;</b>
									<span class="badge"><i class="icon-star"></i> <small>{l s='Discount' mod='expressmailing'}</small></span>
								{/if}
							</td>
						</tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</form>