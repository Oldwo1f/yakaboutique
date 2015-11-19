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

<html>
	<body class="margin: 0; padding: 0;">
		<table style="background-color: #eeeeee; width: 100%;">
			<tr>
				<td align="center" style="padding: 10px 50px">
					<table style="padding: 10px; background-color: #ffffff; width: 100%; min-width: 600px; max-width: 800px; font-family: Verdana, Helvetica, sans-serif; font-size: 11px;">
						<tr>
							<td>
								<table cellspacing="0" cellpadding="0" width="100%">
									<tr>
										<td align="left" width="50%">
											<div id="header_logo">
												<a href="{$base_url|escape:'html':'UTF-8'}" title="{$shop_name|escape:'htmlall':'UTF-8'}"><img src="{$base_url|escape:'html':'UTF-8'}{$img_dir|escape:'html':'UTF-8'}{$logo_name|escape:'html':'UTF-8'}" style="max-width: 100%" border="0" alt="{$shop_name|escape:'htmlall':'UTF-8'}" {if $logo_width}width="{$logo_width|intval}"{/if} {if $logo_height}height="{$logo_height|intval}"{/if} /></a>
											</div>
										</td>
										<td align="center" width="50%">&nbsp;</td>
									</tr>
									<tr>
										<td align="left" colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td align="left" colspan="2"><span style="font-family: Verdana, Helvetica, sans-serif; font-size: 14pt;">{l s='%s Newsletter' mod='expressmailing' sprintf=$shop_name}</span></td>
									</tr>
									<tr>
										<td align="left" colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td align="left" colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td align="left" colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td align="left" colspan="2">&nbsp;</td>
									</tr>
									<tr>
										<td align="left" colspan="2">&nbsp;</td>
									</tr>
									<tr>
										{capture assign="site"}<a href="{$base_url|escape:'html':'UTF-8'}">{$domain_name|escape:'htmlall':'UTF-8'}</a>{/capture}
										{capture assign="footer"}{l s='You are receiving this email because you have visited or ordered[br]on the website [www]' mod='expressmailing'}{/capture}
										<td align="center" colspan="2">
											<font size="1">
											{$footer|escape:'htmlall':'UTF-8'|replace:['[br]','[www]']:['<br/>',$site]}&nbsp;&nbsp;|&nbsp;&nbsp;<a href="##DESABONNEMENT##">{l s='Unsubscribe' mod='expressmailing'}</a>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="##AMI##">{l s='Forward to a friend' mod='expressmailing'}</a>
											</font>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>