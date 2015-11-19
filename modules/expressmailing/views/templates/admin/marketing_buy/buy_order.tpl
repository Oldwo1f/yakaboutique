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

<div class="row" id="start_products">
	<div class="col-lg-12">

		<form class="container-command-top-spacing" action="index.php?controller=AdminMarketingBuy&token={$smarty.get.token|escape:'html':'UTF-8'}" method="post">

			<div class="panel">

				<div class="panel-heading">
					<i class="icon-shopping-cart"></i>
					{if $order_btn_proceed == 'pay'}
						{l s='Purchase order' mod='expressmailing'}
					{else}
						{l s='Cart' mod='expressmailing'}
					{/if}
					 <span class="badge">{$cart.products|@count|intval}</span>
				</div>

				<div class="table-responsive">

					<input type="text" name="order_session" id="campaign_id" value="{$order_session|escape:'html':'UTF-8'}" class="hidden" readonly="readonly">

					<table class="table" id="orderProducts">
					<thead>
						<tr>
							<th><span class="title_box ">{l s='Ref' mod='expressmailing'}</span></th>
							<th><span class="title_box ">{l s='Product' mod='expressmailing'}</span></th>
							<th class="text-center"><span class="title_box ">{l s='Unit Price' mod='expressmailing'}</span></th>
							<th class="text-center"><span class="title_box ">{l s='Qty' mod='expressmailing'}</span></th>
							<th class="text-center"><span class="title_box ">{l s='Total' mod='expressmailing'}</span></th>
						</tr>
					</thead>
					<tbody>
					{foreach $cart.products as $product}
						<tr>
							<td>{$product.product_ref|escape:'html':'UTF-8'}</td>
							<td>{$product.product_desc|escape:'html':'UTF-8'}</td>
							<td class="text-center">
								{if isset($product.promo_price) and ($product.promo_price > 0)}
									{displayPrice price=$product.promo_price currency=$currency->id}
								{else}
									{displayPrice price=$product.normal_price currency=$currency->id}
								{/if}
							</td>
							<td class="text-center" nowrap style="vertical-align: middle">
								{if $order_btn_proceed == 'checkout'}
									<input type="text" style="width: 3em; display: inline" name="qty_{$product.product_ref|escape:'html':'UTF-8'}" value="{$product.product_quantity|intval}">
									<button class="btn btn-default" style="vertical-align: baseline;" name="submitQty[{$product.product_ref|escape:'html':'UTF-8'}]" type="submit"><i class="icon-refresh"></i></button>
								{else}
									{$product.product_quantity|intval}
								{/if}
							</td>
							<td class="text-center">{displayPrice price=$product.total_price currency=$currency->id}</td>
						</tr>
					{/foreach}
					</tbody>
					</table>

				</div>

				&nbsp;

				<div class="row">

					<div class="col-xs-4">
						{if $order_btn_proceed == 'pay'}
							<div class="panel">
								<div><b>{l s='Billing address :' mod='expressmailing'}</b></div>
								<div class="table-responsive">
									<div>{$inscription.company_name|escape:'html':'UTF-8'}</div>
									<div>{$inscription.company_address1|escape:'html':'UTF-8'}</div>
									{if $inscription.company_address2}<div>{$inscription.company_address2|escape:'html':'UTF-8'}</div>{/if}
									<div>{$inscription.company_zipcode|escape:'html':'UTF-8'} {$inscription.company_city|escape:'html':'UTF-8'}</div>
									<div>{$inscription.company_country|escape:'html':'UTF-8'}</div>
								</div>
							</div>
						{/if}
					</div>

					<div class="col-xs-4"></div>

					<div class="col-xs-4">
						<div class="panel panel-total">
							<div class="table-responsive">
								<table class="table">
								<tr id="total_products">
									<td class="text-right">{l s='Products :' mod='expressmailing'}</td>
									<td class="amount text-right">
										{displayPrice price=$cart.cart_total_before_tax currency=$currency->id}
									</td>
								</tr>
								<tr id="total_taxes">
									<td class="text-right">{l s='Taxes (%s) :' mod='expressmailing' sprintf=$cart.cart_tax_country}</td>
									<td class="amount text-right" >{displayPrice price=$cart.cart_total_tax currency=$currency->id}</td>
								</tr>
								<tr id="total_order">
									<td class="text-right"><strong>{l s='Total :' mod='expressmailing'}</strong></td>
									<td class="amount text-right">
										<strong>{displayPrice price=$cart.cart_total_with_tax currency=$currency->id}</strong>
									</td>
								</tr>
								</table>
							</div>
						</div>
					</div>

				</div>

				<div class="panel-footer" style="height: auto" align="center">
				{if $order_btn_proceed == 'checkout'}
					<button type="submit" name="submitCheckout" class="btn btn-default">
						<i class="process-icon-cart"></i> {l s='Proceed to Checkout ...' mod='expressmailing'}
					</button>
				{elseif $order_btn_proceed == 'pay'}
					{foreach $payments as $payment}
						<div class="btn-group">
							<a class="btn btn-default" href="{$payment.payment_url|escape:'html':'UTF-8'}" style="margin: 0px 25px">
								<img src="{$payment.payment_image|escape:'html':'UTF-8'}" style="margin: 7px"><br>
								<b>{l s='Pay with %s' mod='expressmailing' sprintf=$payment.payment_name}</b>
							</a>
						</div>
					{/foreach}
				{/if}
				</div>

			</div>

		</form>

	</div>
</div>