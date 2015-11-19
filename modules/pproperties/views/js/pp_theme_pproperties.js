/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*
* Do not modify this file. If you need to make changes create file
* themes/your_theme/js/modules/pproperties/custom.js
* and add your changes there.
*/

var ppCart = {
	products: new Object(),
	removeProducts: function() {
		ppCart.products = new Object();
	},
	addProduct: function(product) {
		ppCart.products[product.icp] = product;
	},
	amendProducts: function() {
		var cart = $('.cart_block,#cart_block');
		var order = $('#order-detail-content');
		var orderingPrcocess = $('body#order').length || $('body#order-opc').length;
		var orderingPayment = $('body#order .paiement_block').length || $('body#order .payment_block').length;
		$.each(ppCart.products, function() {
			ppCart.amendProduct(this, cart, order, orderingPrcocess, orderingPayment);
		});
	},
	amendProduct: function(product, cart, order, orderingPrcocess, orderingPayment) {
		if (product.icp == 0)
			return;
		var icpSelector = ppCart.getIcpSelector(product.icp);
		var pp_ext_prop_data_string = '';
		if ($.isArray(product.pp_ext_prop_data) && product.pp_ext_prop_data.length > 0) {
			pp_ext_prop_data_string = '<div class="pp_ext_prop_data_wrapper">';
			for (i = 0; i < product.pp_ext_prop_data.length; i++) {
				pp_ext_prop_data_string += '<div class="pp_ext_prop_data_' + (i+1) + '"><span class="pp_ext_prop_data_property">' + product.pp_ext_prop_data[i].property + '</span>';
				pp_ext_prop_data_string += ' <span class="pp_ext_prop_data_quantity">' + product.pp_ext_prop_data[i].quantity + '</span>';
				pp_ext_prop_data_string += ' <span class="pp_ext_prop_data_text">' + product.pp_ext_prop_data[i].order_text + '</span>';
				pp_ext_prop_data_string += '</div>';
			}
			pp_ext_prop_data_string += '</div>';
		}

		if (cart != undefined && cart.length) {
			if (product.cart_quantity_fractional > 0) {
				$(icpSelector+' a.cart_block_product_name', cart).after('<span class="pp_qty_wrapper"><span class="pp_x">x </span><span class="pp_qty">'+pp.formatQty(product.cart_quantity_fractional, ppCart.decimalSign)+'</span> <span class="pp_qty_text">'+product.pp_qty_text+'</span></span>');
			}
			$(icpSelector+' .quantity-formated, '+icpSelector+' .pp_qty_wrapper .pp_x', cart)[(product.cart_quantity == undefined ? product.quantity : product.cart_quantity) == 1 ? 'hide' : 'show']();
			if (pp_ext_prop_data_string != '')
				$(icpSelector+' .cart-info .price', cart).before(pp_ext_prop_data_string);
			$(icpSelector+' a.ajax_cart_block_remove_link', cart).each(function() {
				ppCart.addIcpParam($(this), product.icp);
			});
		}

		if (order != undefined && order.length) {
			$('tr'+icpSelector, order).each(function() {
				var self = $(this);
				var qty_behavior = ppCart.qtyBehavior(product);
				if (qty_behavior)
					self.addClass('pp_qty_behavior_1');
				if (orderingPrcocess) {
					self.find('input,a[id],.cart_total span.price').addClass(ppCart.getIcpString(product.icp));
					self.find('a[id]').each(function() {
						ppCart.addIcpParam($(this), product.icp);
					});
				}
				if (! self.hasClass('customization') && !pp.isPackCalculator(product)) {
					if (product.pp_price_text.length) {
						if (orderingPrcocess)
							self.find('.cart_unit > .price').after(pp.priceText(product));
						else
							self.find('.pp_order_unit_price label').append(pp.priceText(product));
					}
					var totalQty = pp.getCartTotalQty(product);
					if (totalQty > 0) {
						if (orderingPrcocess) {
							var el = self.filter('.cart_item').find('.cart_quantity');
							if (orderingPayment) {
								if (product.cart_quantity != 1) {
									el.append(pp.qtyText(product, ppCart.decimalSign));
								}
								else {
									el.find('span').hide();
									el.append(pp.qtyText(product, ppCart.decimalSign, 'span', true));
								}
							}
							else {
								if (qty_behavior)
									el.find('.cart_quantity_input').after('<span class="ajax-processing" style="display: none;"></span>').after(pp.spanText('pp_qty_text', product.pp_qty_text));
								else
									el.append(pp.qtyText(product, ppCart.decimalSign));
							}
						}
						else {
							var el = self.find('.pp_order_qty');
							el.find('label').append(pp.qtyText(product, ppCart.decimalSign, 'span', true));
							el.append(pp.qtyText(product, ppCart.decimalSign));
							ppCart.amendQtyText(el);
							//return slip : enable or disable 'global' quantity editing
							$('td input[type=checkbox]', this).click(function() {
								var el = self.parent().parent().find('.pp_order_qty');
								ppCart.amendQtyText(el, self.is(':checked'));
							});
						}
						var s = self.find(orderingPrcocess ? '.cart_total' : '.pp_order_total_price');
						if (product.cart_quantity_fractional > 0)
							s.append(pp.totalText(product, pp.formatQty(totalQty, ppCart.decimalSign)));
						if (product.cart_quantity <= 1) {
							$('.pp_qty_wrapper', s).hide();
						}
					}
					if (pp_ext_prop_data_string != '') {
						if (orderingPrcocess)
							self.find('.pp_cart_description').append(pp_ext_prop_data_string);
						else
							self.find('.pp_order_product_name').append(pp_ext_prop_data_string);
					}
				}
				if (! self.hasClass('customization')) {
					pp.hooks.call('smartprice', 'ppCart.amendProduct', {'product':product, 'self':self, 'orderingPrcocess':orderingPrcocess});
				}
			});
		}
	},
	amend: function() {
		$('a.ajax_add_to_cart_button').each(function () {
			$(this).attr('href', $(this).attr('href').replace('qty=1&', 'qty=default&'));
		});
		ppCart.amendProducts();
	},
	amendQtyText: function(el, editable) {
		var qty = parseInt(el.find('.order_qte_span').text().trim(),10);
		if (editable) {
			el.find('.pp_qty_wrapper_no_x').hide();
			el.find('.pp_qty_wrapper').show();
		}
		else {
			if (qty != 1) {
				el.find('.pp_qty_wrapper_no_x').hide();
				el.find('.pp_qty_wrapper').show();
			}
			else {
				el.find('.order_qte_span').hide();
				el.find('.pp_qty_wrapper_no_x').show();
				el.find('.pp_qty_wrapper').hide();
			}
		}
	},
	qtyBehavior: function(product) {
		return (product['pp_qty_policy'] != 0 && product['pp_ext'] == 0 && product['cart_quantity'] == 1);
	},
	getIcp: function(o) {
		return ppCart.getIcpClass(o).replace(/icp-/, '');
	},
	getIcpSelector: function(icp) {
		return '.' + ppCart.getIcpString(icp);
	},
	getIcpSuffix: function(icp) {
		return '_' + ppCart.getIcpString(icp);
	},
	getIcpString: function(icp) {
		return 'icp-' + icp;
	},
	getIcpClass: function(o) {
		return o.attr('class').match(/icp-\d+/)[0];
	},
	addIcpParam: function(o, icp) {
		var href = o.attr('href');
		if (href.indexOf('icp=') == -1)
			o.attr('href', href + '&icp=' + icp);
	}
};

$(document).ready(function() {
	ppCart.amend();
	$('.comparison_unit_price').each(function() {
		var found = false;
		$(this).contents().filter(function () {
			if ($(this).hasClass('pp_unity_text') || $(this).hasClass('pp_price_text'))
				found = true;
			return found && this.nodeType === 3; 
		}).remove();
	});
});
