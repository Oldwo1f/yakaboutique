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
<form id="configuration_form" class="defaultForm form-horizontal adminmarketingx" action="index.php?controller=AdminMarketingX&token={Tools::getAdminTokenLite('AdminMarketingX')|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" novalidate>
    <div class="small">
        <img src="../modules/expressmailing/views/img/{l s='config_en.png' mod='expressmailing'}" alt="logo" border="0" class="em-space-inline">
		<img src="../modules/expressmailing/views/img/certified_prestashop.png" alt="certified by prestashop" class="em-space-inline"/>
		<br/>&nbsp;
    </div>
    <div class="panel media_list" style="padding-bottom: 0px; text-align: center">
        <div>
            <img src="../modules/expressmailing/views/img/email.png" border="0">
            <div>
                <span class="block bold" style="font-size: 10pt;">{l s='Your email credits' mod='expressmailing'}</span>
				{if $api_connected}
					<span class="block" style="font-size: 9pt">{$smarty_remaining_email_credits|unescape}</span>
				{else}
					<span class="badge" style="font-size: 8pt; background-color: rgb(148, 190, 42);">&nbsp;{$smarty_remaining_email_credits|unescape}&nbsp;</span>
				{/if}
                <div class="block" style="padding-top: 8px">
					<a class="btn btn-default" href="index.php?campaign_type=marketing_e&controller=AdminMarketingX&token={Tools::getAdminTokenLite('AdminMarketingX')|escape:'html':'UTF-8'}"{if $smarty_email_disabled} disabled="disabled"{/if}><i class="icon-envelope-alt"></i> &nbsp;{l s='Send a new e-mailing' mod='expressmailing'}</a><br/>
				    <a class="btn btn-default" href="index.php?controller=AdminMarketingEList&token={Tools::getAdminTokenLite('AdminMarketingEList')|escape:'html':'UTF-8'}"{if $smarty_email_disabled || $smarty_email_stats_disabled} disabled="disabled"{/if}><i class="icon-dashboard"></i> &nbsp;{l s='My email stats' mod='expressmailing'}</a><br/>
                    <a class="btn btn-default" id="em_bying_link_email"{if $smarty_email_disabled} disabled="disabled"{/if}><i class="icon-shopping-cart"></i> &nbsp;{l s='Buy email credits' mod='expressmailing'}</a>
                </div>
            </div>
        </div>
        <div>
            <img src="../modules/expressmailing/views/img/fax.png" border="0">
            <div>
                <span class="block bold" style="font-size: 10pt;">{l s='Your fax credits' mod='expressmailing'}</span>
				{if $api_connected}
					<span class="block" style="font-size: 9pt">{$smarty_remaining_fax_credits|unescape}</span>
				{else}
					<span class="badge" style="font-size: 8pt; background-color: rgb(199, 111, 143);">&nbsp;{$smarty_remaining_fax_credits|unescape}&nbsp;</span>
				{/if}
                <div class="block" style="padding-top: 8px">
					<a class="btn btn-default" href="index.php?campaign_type=marketing_f&controller=AdminMarketingX&token={Tools::getAdminTokenLite('AdminMarketingX')|escape:'html':'UTF-8'}"{if $smarty_fax_disabled} disabled="disabled"{/if}><i class="icon-envelope-alt"></i> &nbsp;{l s='Send a new fax-mailing' mod='expressmailing'}</a><br/>
                    <a class="btn btn-default" href="index.php?controller=AdminMarketingFList&token={Tools::getAdminTokenLite('AdminMarketingFList')|escape:'html':'UTF-8'}"{if $smarty_fax_disabled || $smarty_fax_stats_disabled} disabled="disabled"{/if}><i class="icon-dashboard"></i> &nbsp;{l s='My fax stats' mod='expressmailing'}</a><br/>
                    <a class="btn btn-default" id="em_bying_link_fax"{if $smarty_fax_disabled} disabled="disabled"{/if}><i class="icon-shopping-cart"></i> &nbsp;{l s='Buy fax credits' mod='expressmailing'}</a>
                </div>
            </div>
        </div>
        <div>
            <img src="../modules/expressmailing/views/img/sms.png" border="0">
            <div>
                <span class="block bold" style="font-size: 10pt;">{l s='Your sms credits' mod='expressmailing'}</span>
				{if $api_connected}
					<span class="block" style="font-size: 9pt">{$smarty_remaining_sms_credits|unescape}</span>
				{else}
					<span class="badge" style="font-size: 8pt; background-color: rgb(117, 141, 188);">&nbsp;{$smarty_remaining_sms_credits|unescape}&nbsp;</span>
				{/if}
                <div class="block" style="padding-top: 8px">
					<a class="btn btn-default" href="index.php?campaign_type=marketing_s&controller=AdminMarketingX&token={Tools::getAdminTokenLite('AdminMarketingX')|escape:'html':'UTF-8'}"{if $smarty_sms_disabled} disabled="disabled"{/if}><i class="icon-envelope-alt"></i> &nbsp;{l s='Send a new sms-mailing' mod='expressmailing'}</a><br/>
                    <a class="btn btn-default" href="index.php?controller=AdminMarketingSList&token={Tools::getAdminTokenLite('AdminMarketingSList')|escape:'html':'UTF-8'}"{if $smarty_sms_disabled || $smarty_sms_stats_disabled} disabled="disabled"{/if}><i class="icon-dashboard"></i> &nbsp;{l s='My sms stats' mod='expressmailing'}</a><br/>
                    <a class="btn btn-default" id="em_bying_link_sms"{if $smarty_sms_disabled} disabled="disabled"{/if}><i class="icon-shopping-cart"></i> &nbsp;{l s='Buy sms credits' mod='expressmailing'}</a>
                </div>
            </div>
        </div>
        <div>
            <img src="../modules/expressmailing/views/img/audio.png" border="0">
            <div>
                <span class="block bold" style="font-size: 10pt;">{l s='Coming soon' mod='expressmailing'}</span>
				<br>
                <div class="block" style="padding-top: 8px">
                    <a href="https://www.express-mailing.com/mailing-audio/" target="_blank" class="btn btn-default"><i class="icon-hand-right"></i> &nbsp;{l s='More informations' mod='expressmailing'}</a>
                </div>
            </div>
        </div>
    </div>
</form>

<div id="bying_dialog_email" title="{l s='Increase capacity ?' mod='expressmailing'}">
    <div style="width: 100%; margin-right: auto; margin-left: auto; text-align: center">
        <br><img src="../modules/expressmailing/views/img/progress-bar.gif" alt="" />
    </div>
</div>
<div id="bying_dialog_fax" title="{l s='Buy fax tickets' mod='expressmailing'}">
    <div style="width: 100%; margin-right: auto; margin-left: auto; text-align: center">
        <br><img src="../modules/expressmailing/views/img/progress-bar.gif" alt="" />
    </div>
</div>
<div id="bying_dialog_sms" title="{l s='Buy sms tickets' mod='expressmailing'}">
    <div style="width: 100%; margin-right: auto; margin-left: auto; text-align: center">
        <br><img src="../modules/expressmailing/views/img/progress-bar.gif" alt="" />
    </div>
</div>

<script type="text/javascript">

    $(function ()
    {
        var url_base = "index.php?controller=AdminMarketingX";
        var url_ajax = "&ajax=true";
        var url_token = "&token={Tools::getAdminTokenLite('AdminMarketingX')|escape:'html':'UTF-8'}";

        var dialogByingConfig = {
            autoOpen: false,
            resizable: true,
            position: 'center',
            modal: true,
            width: 820,
            height: 450,
            buttons: {
                "{l s='Close' mod='expressmailing'}": function () {
                    $(this).dialog("close");
                }
            }
        };

        $('#bying_dialog_email').dialog(dialogByingConfig);
        $('#bying_dialog_fax').dialog(dialogByingConfig);
        $('#bying_dialog_sms').dialog(dialogByingConfig);

        $('#em_bying_link_email, #em_bying_link_email2').click(function () {
            $('#bying_dialog_email').load(url_base + url_ajax + url_token + '&media=email').dialog('open');
        });
        $('#em_bying_link_fax, #em_bying_link_fax2').click(function () {
            $('#bying_dialog_fax').load(url_base + url_ajax + url_token + '&media=fax').dialog('open');
        });
        $('#em_bying_link_sms, #em_bying_link_sms2').click(function () {
            $('#bying_dialog_sms').load(url_base + url_ajax + url_token + '&media=sms').dialog('open');
        });

    });

</script>