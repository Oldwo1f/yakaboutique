/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*
* Do not modify this file. If you need to make changes create file
* themes/your_theme/js/modules/pproperties/custom_product.js
* and add your changes there.
*/

// search the combinations' case of attributes and update displaying of availability, prices, ecotax, and image
function findCombination()
{
	$('#minimal_quantity_wanted_p').fadeOut();
	$('#minimal_quantity_label').html('');
	$('#quantity_wanted').pp_val(pp.formatQty(ppProductProperties['defaultQty']), false);

	//create a temporary 'choice' array containing the choices of the customer
	var choice = [];
	var radio_inputs = parseInt($('#attributes .checked > input[type=radio]').length);
	if (radio_inputs)
		radio_inputs = '#attributes .checked > input[type=radio]';
	else
		radio_inputs = '#attributes input[type=radio]:checked';

	$('#attributes select, #attributes input[type=hidden], ' + radio_inputs).each(function(){
		choice.push(parseInt($(this).val()));
	});

	if (typeof combinations == 'undefined' || !combinations)
		combinations = [];
	//testing every combination to find the conbination's attributes' case of the user
	for (var combination = 0; combination < combinations.length; ++combination)
	{
		//verify if this combinaison is the same that the user's choice
		var combinationMatchForm = true;
		$.each(combinations[combination]['idsAttributes'], function(key, value)
		{
			if (!in_array(parseInt(value), choice))
				combinationMatchForm = false;
		});

		if (combinationMatchForm)
		{
			if (ppProductProperties['pp_qty_policy'] == 2 || combinations[combination]['minimal_quantity'] > 1)
			{
				var minQty = combinations[combination]['minimal_quantity'];
				$('#minimal_quantity_label').html(pp.formatQty(minQty));
				//$('#minimal_quantity_wanted_p').fadeIn();
				if (pp.parseFloat($('#quantity_wanted').val()) < minQty) {
					$('#quantity_wanted').pp_val(pp.formatQty(minQty), false);
				}
				//$('#quantity_wanted').bind('keyup', function() {checkMinimalQuantity(combinations[combination]['minimal_quantity']);});
			}
			//combination of the user has been found in our specifications of combinations (created in back office)
			selectedCombination['unavailable'] = false;
			selectedCombination['reference'] = combinations[combination]['reference'];
			$('#idCombination').val(combinations[combination]['idCombination']);

			//get the data of product with these attributes
			quantityAvailable = combinations[combination]['quantity'];
			selectedCombination['price'] = combinations[combination]['price'];
			selectedCombination['unit_price'] = combinations[combination]['unit_price'];
			selectedCombination['specific_price'] = combinations[combination]['specific_price'];
			if (combinations[combination]['ecotax'])
				selectedCombination['ecotax'] = combinations[combination]['ecotax'];
			else
				selectedCombination['ecotax'] = default_eco_tax;

			//show the large image in relation to the selected combination
			if (combinations[combination]['image'] && combinations[combination]['image'] != -1)
				displayImage( $('#thumb_'+combinations[combination]['image']).parent() );

			//show discounts values according to the selected combination
			if (combinations[combination]['idCombination'] && combinations[combination]['idCombination'] > 0)
				displayDiscounts(combinations[combination]['idCombination']);

			//get available_date for combination product
			selectedCombination['available_date'] = combinations[combination]['available_date'];

			//update the display
			updateDisplay();

			if (firstTime)
			{
				refreshProductImages(0);
				firstTime = false;
			}
			else
				refreshProductImages(combinations[combination]['idCombination']);
			//leave the function because combination has been found
			return;
		}
	}
	//this combination doesn't exist (not created in back office)
	selectedCombination['unavailable'] = true;
	if (typeof(selectedCombination['available_date']) != 'undefined')
		delete selectedCombination['available_date'];
	updateDisplay();
}

var f_updateDisplay = window.updateDisplay;
window.updateDisplay = function() {
	f_updateDisplay();
	$('#quantityAvailable').html(pp.formatQty(quantityAvailable));
	ppAmendExtProp();
	ppQtyUpdated();
};

var f_getProductAttribute = window.getProductAttribute;
window.getProductAttribute = function() {
	f_getProductAttribute();
	ppProduct.amendUrl();
};

function checkMinimalQuantity(minimal_quantity) {}

var ppQuantityObserver = pp.observer(
	ppCalcPrice,
	function (observer) {
		return pp.normalizeFloatAsString(observer.args[0]) != pp.normalizeFloatAsString(observer.args[1]);
	},
	1200
);

var ppPriceObserver = pp.observer(
	function (observer) {
		var clean = function() {
			$('#pp_price, #pp_price_smartprice_info').removeClass('calculating');
			$('.pp_price .ajax-processing').removeClass('visible');
		};
		if (observer.ajaxData != observer.data) {
			observer.ajaxData = observer.data;
			var ajaxData = observer.ajaxData;
			var price = observer.price;
			$('.pp_price .ajax-processing').addClass('visible');
			$.ajax({
				type: 'POST',
				headers: { "cache-control": "no-cache" },
				url: ppProduct.actions.price + '&ajax=1&rand=' + new Date().getTime(),
				cache: false,
				dataType : "json",
				data: observer.ajaxData
			})
			.done(function(jsonData) {
				if (jsonData.status == 'success') {
					if (!observer.observing() && ajaxData == observer.ajaxData) {
						observer.data = '';
						if (typeof jsonData.total != "undefined") {
							$('.pp_price').css('visibility', 'visible');
							$('#pp_price').html(formatCurrency(jsonData.total, currencyFormat, currencySign, currencyBlank));
						};
						pp.hooks.call('smartprice', 'priceObserver', jsonData);
					}
				};
			})
			.always(function() {
				clean();
			});
		}
		else {
			clean();
		}
	},
	function (observer) {
		var qty  = pp.parseFloat(observer.args[0]);
		var pp_ext = '';
		$('input.pp_ext_prop_quantity').each(function() {
			pp_ext += '&' + $(this).attr('name') + '=' + $(this).val();
		});
		var data = '&id_product=' + $('#product_page_product_id').val()
				 + '&id_product_attribute=' + $('#idCombination').val()
				 + '&qty=' + qty
				 + pp_ext;

		observer.price = observer.args[1];
		var observe = (qty > 0 && observer.data != data);
		observer.data = data;
		if (observer.ajaxData != observer.data)
			$('#pp_price, #pp_price_smartprice_info').addClass('calculating');
		return observe;
	},
	800
);

function ppCalcPrice() {
	if (!ppPriceObserver.initialized)
		return;

	var ext_qty;
	if (ppProductProperties['pp_ext'] == 1) {
		var pp_ext_prop_quantity = $('input.pp_ext_prop_quantity');
		if (pp_ext_prop_quantity.length) {
			pp_ext_prop_quantity.each(function() {
				var q = pp.parseFloat($(this).val());
				if (q > 0) {
					if (ppQuantityObserver.observe($(this).val(), q)) {
						return false;
					}

					var position = $(this).data('pp_ext_position');
					var o = ppProductProperties['pp_ext_prop'][position];
					var minQty = o.minimum_quantity;
					var maxQty = o.maximum_quantity;
					var qtyRatio = o.qty_ratio;
					var qtyStep = o.qty_step;
					var decimals = pp.decimals(qtyStep);
					if (qtyStep > 0) {
						var qq = pp.processQtyStep(q, qtyStep);
						if (ppQuantityObserver.observe(pp.formatQty(qq, '.', decimals), q)) {
							return false;
						}
						q = qq;
					}
					if (minQty > 0 && q < minQty) {
						q = minQty;
					}
					if (maxQty > 0 && q > maxQty) {
						q = maxQty;
					}
					if (ppQuantityObserver.observe($(this).val(), q)) {
						return false;
					}
					$(this).val(pp.formatQty(q, '', decimals));
				}
				if (ppProductProperties["pp_ext_method"] == 1) {
					if (ext_qty == undefined) ext_qty = 1;
					ext_qty *= (qtyRatio > 0 ? q / qtyRatio : q);
				}
				else {
					if (ext_qty == undefined) ext_qty = 0;
					ext_qty += (qtyRatio > 0 ? q / qtyRatio : q);
				}
			});
			if (ppQuantityObserver.observing()) {
				return;
			}

			if ((ppProductProperties['pp_ext_policy'] == 1) && (isNaN(ext_qty) || ext_qty <= 0)) {
				ext_qty = pp.parseFloat($('input.pp_ext_result').val());
			}
			if (!isNaN(ext_qty) && ext_qty > 0) {
				var formatted_ext_qty = pp.formatQty(ext_qty);
				$('span.pp_ext_result').html(formatted_ext_qty);
				$('input.pp_ext_result').val(formatted_ext_qty);
				if (ppProductProperties['pp_ext_policy'] == 1) {
					ppPackCalculator(ext_qty);
				}
			}
			else {
				if (ppProductProperties['pp_ext_policy'] == 1) {
					ppPackCalculator(-1); // notify custom calculator
				}
				$('span.pp_ext_result').html(0); // input.pp_ext_result is not modified
			}
		}
	}

	var price = 0;
	var qty = pp.parseFloat($('#quantity_wanted').val());
	if (qty > 0) {
		if (ppQuantityObserver.observe($('#quantity_wanted').val(), qty)) {
			return;
		}

		var currentPrice;
		var rate;
		if (typeof priceWithDiscountsDisplay == 'undefined') {
			currentPrice = productPrice;
			rate = 1;
		}
		else {
			currentPrice = priceWithDiscountsDisplay;
			rate = currencyRate;
		}

		if (!isNaN(currentPrice) && currentPrice > 0) {
			currentPrice = ps_round(currentPrice * rate, priceDisplayPrecision);
			if (ppProductProperties['pp_qty_policy'] != 2) {
				qty = Math.floor(qty);
			}
			var qtyStep = ppProductProperties['pp_qty_step'];
			var decimals = pp.decimals(qtyStep);
			if (qtyStep > 0) {
				var q = pp.processQtyStep(qty, qtyStep);
				if (ppQuantityObserver.observe(pp.formatQty(q, '.', decimals), qty)) {
					return;
				}
				qty = q;
			}
			if (ppProductProperties['pp_ext'] == 1) {
				if (ppProductProperties['pp_ext_policy'] == 1) {
					ext_qty = 1;
					if (productUnitPriceRatio > 0) {
						var q = qty * productUnitPriceRatio;
						$('#pp_ext_result_quantity').html(pp.formatQty(q));
					}
				}
				else {
					qty = Math.floor(qty);
					if (isNaN(ext_qty)) {
						ext_qty = 0;
					}
				}
			}
			else {
				ext_qty = 1;
			}
			var q = (ext_qty * qty).toFixed(8);
			price = ps_round(currentPrice * q, priceDisplayPrecision);
			var minPriceRatio = ppProductProperties['pp_minimal_price_ratio'];
			if (!isNaN(minPriceRatio) && minPriceRatio > 0) {
				var min_price = ps_round(currentPrice * minPriceRatio, priceDisplayPrecision);
				if (price < min_price) {
					price = min_price;
				}
			}
			$('#quantity_wanted').val(pp.formatQty(qty, '', decimals));
		}
	}

	ppQuantityObserver.clear();
	
	ppProduct.amendUrl();

	if ($('#quantity_wanted').val() !== '') {
		minimal_quantity = pp.parseFloat($('#minimal_quantity_label').html());
		if (pp.parseFloat($('#quantity_wanted').val()) < minimal_quantity) {
			$('#minimal_quantity_wanted_p:hidden').fadeIn('slow');
			$('#quantity_wanted').css('border-color', 'red');
		}
		else {
			$('#quantity_wanted').css('border-color', $('#quantity_wanted').data('border-color'));
			$('#minimal_quantity_wanted_p').fadeOut();
		}
	}

	if (productShowPrice != 1)
		return;

	if (!ppProduct.priceObserver)
		$('#pp_price').html(formatCurrency(price, currencyFormat, currencySign, currencyBlank));
	ppAmendPriceDisplay(qty, price);

	if (ppProduct.priceObserver)
		ppPriceObserver.observe($('#quantity_wanted').val(), price);
}

function ppQtyUpdated(event) {
	if (event instanceof Object && event.target != undefined && event.which != undefined) {
		if ((event.which >= 33 && event.which <= 40) || (event.which >= 16 && event.which <= 20) || $.inArray(event.which, [9,27,45,46,144,145]) != -1) {
			return;
		}
		$(event.target).pp_val();
	}
	ppCalcPrice();
}

function ppAmendPriceDisplay(qty, price) {
	if (productShowPrice == 1) {
		if (typeof(qty) == 'object') {
			qty = String($(this).val()).replace(/,/g, '.');
			price = (qty == '' || isNaN(qty) ? 0 : 1);
		}
		if (price <= 0 || (ppProductProperties['pp_qty_policy'] != 2 && !isNaN(qty) && qty == 1)) {
			if (ppProductProperties['pp_price_display_mode'] == 0) {
				if (!ppProduct.priceObserver && !pp.isPackCalculator(ppProductProperties)) {
					$('.pp_price').css('visibility', 'hidden');
				}
			}
			else if (ppProductProperties['pp_price_display_mode'] == 1) {
				$('.pp_price').hide();
				$('#our_price_display').show();
				$('.our_price_display .pp_price_text').show();
			}
		}
		else {
			if (ppProductProperties['pp_price_display_mode'] == 0) {
				if (ppProduct.priceObserver || !pp.isPackCalculator(ppProductProperties)) {
					$('.pp_price').css('visibility', 'visible');
				}
			}
			else if (ppProductProperties['pp_price_display_mode'] == 1) {
				$('.pp_price').show();
				$('#our_price_display').hide();
				$('.our_price_display .pp_price_text').hide();
			}
		}
		if (productUnitPriceRatio > 0 && ((ppProductProperties['pp_display_mode'] & 4) == 4)) {
			unit_price = productBasePriceTaxExcl / productUnitPriceRatio;
			if (!noTaxForThisProduct && !customerGroupWithoutTax)
				unit_price = unit_price * (taxRate/100 + 1);
			$('#unit_price_display').text(formatCurrency(unit_price * currencyRate, currencyFormat, currencySign, currencyBlank));
		}
	}
}

function ppAmendExtProp(id_product_attribute) {
	if (!selectedCombination['unavailable'] && ppProductProperties['pp_ext'] == 1) {
		var id_product_attribute = $('#idCombination').val();
		for (i = 1; i < ppProductProperties['pp_ext_prop'].length; i++) {
			var o = ppProductProperties['pp_ext_prop'][i];
			var name = 'pp_ext_prop_quantity_' + i;
			var q = ppProduct.getExtQty(id_product_attribute, i, o.default_quantity);
			$('input.' + name).pp_val(q, false);
			$('span.' + name).html(q);
		}
	}
}

function ppPackCalculator(qty) {
	pp.packCalculator(qty);
}

(function(deferred) {
	$(document).ready(function() {
		$('#quantity_wanted').data('border-color', $('#quantity_wanted').css('border-color'));
		if ($('#quantity_wanted_p').is(':visible')) {
			ppProduct.amendUrlRegister($('#quantity_wanted'), 'qty');
			var minimal_quantity_label = $('#minimal_quantity_label');
			if (minimal_quantity_label.length === 0)
				// in case theme does not contain minimal_quantity_label we add it as a hidden field
				// minimal_quantity_label is used in checkMinimalQuantity()
				$('#quantity_wanted').after('<span id="minimal_quantity_label" style="display:none"></span>');
			else
				minimal_quantity_label.after('<span class="pp_qty_text"> '+ppProductProperties['pp_qty_text']);
			minimal_quantity_label.html(pp.formatQty(ppProductProperties['minQty']));
			$('#quantity_wanted').val(pp.formatQty(Math.max(ppProductProperties['defaultQty'], ppProductProperties['minQty'])));
			var qw = $('#quantity_wanted_p a.product_quantity_up');
			(qw.length > 0 ? qw : $('#quantity_wanted')).before('<span class="pp_qty_text"> '+ppProductProperties['pp_qty_text']+'</span>'); 
			if (ppProductProperties['pp_qty_policy'] == 2)
				$('#quantity_wanted').attr('maxlength', 8);
			$('#quantity_wanted').off('keyup').on('keyup', function(event) {ppQtyUpdated(event)});

			if (ppProductProperties['pp_ext'] == 1) {
				$('#quantity_wanted_p').before('<div class="pp_ext"></div>');
				var pp_ext = $('.pp_ext');
				if (ppProductProperties['pp_ext_title'].length > 0) {
					pp_ext.append('<div class="pp_ext_title">' + ppProductProperties['pp_ext_title'] + '</div>');
				}
				for (i = 1; i < ppProductProperties['pp_ext_prop'].length; i++) {
					var o = ppProductProperties['pp_ext_prop'][i];
					var name = 'pp_ext_prop_quantity_' + i;
					var s = '<div class="pp_ext_prop_wrapper ' + 'pp_ext_prop_wrapper_' + i + '">';
					s += '<span class="pp_ext_prop_property">' + o.property + '</span>';
					s += '<input type="text" class="pp_ext_prop_quantity ' + name + '" name="' + name + '" value="' + ppProduct.getExtQty(0, i, o.default_quantity) + '"></input>';
					if (ppProductProperties['pp_ext_policy'] == 2) {
						s += '<span class="pp_ext_prop_quantity ' + name + '">' + ppProduct.getExtQty(0, i, o.default_quantity) + '</span>';
					}
					s += '<span class="pp_ext_prop_text">' + o.text + '</span>';
					s += '</div>';
					pp_ext.append(s);
					$('input.' + name).data('pp_ext_position', i);
					ppProduct.amendUrlRegister($('input.' + name), 'qty' + i);
				}
				if (ppProductProperties['pp_ext_property'].length > 0) {
					var s_prefix = '<span class="pp_ext_property">' + ppProductProperties['pp_ext_property'] + '</span>';
					var s_suffix = '<span class="pp_ext_text">' + ppProductProperties['pp_ext_text'] + '</span></div>';
					if (ppProductProperties['pp_ext_policy'] == 1) {
						var s = '<input type="text" class="pp_ext_result" />';
						$('.pp_price').after('<div class="pp_ext_result_quantity">' + s_prefix +
											 '<span id="pp_ext_result_quantity"></span>' + s_suffix);
					}
					else {
						var s = '<span class="pp_ext_result"></span>';
					}
					pp_ext.append('<div class="pp_ext_method_wrapper">' + s_prefix + s + s_suffix);
				}
				if (ppProductProperties['pp_ext_policy'] == 2) {
					$('input.pp_ext_prop_quantity').hide();
				}
				else {
					if (ppProductProperties['pp_ext_policy'] == 1) {
						$('input.pp_ext_result').on('keyup', function() {
							$('input.pp_ext_prop_quantity').val('');
						});
						$('#quantity_wanted').on('keyup', function() {
							$('input.pp_ext_prop_quantity, input.pp_ext_result').val('');
						});
						ppPackCalculator(-1); // initialize custom calculator
					}
					$('input.pp_ext_prop_quantity, input.pp_ext_result').on('keyup', function(event) {ppQtyUpdated(event)});
				}
			}

			if (productShowPrice == 1) {
				if (ppProductProperties['explanation']) {
					var expl = '<div id="pp_explanation">'+ppProductProperties['explanation']+'</div>';
					if ($('#buy_block .pp_ext').length > 0)
						$('#buy_block .pp_ext').append(expl);
					else
						$('#quantity_wanted_p').after(expl);
				}
				if (ppProductProperties['pp_price_display_mode'] == 0) {
					var style = (ppProduct.priceObserver || pp.isPackCalculator(ppProductProperties) ? '' : ' style="visibility: hidden;"');
					$('#quantity_wanted_p').after('<div class="pp_price pp_price_display pp_price_display_mode_0"'+style+'>'+ppProductProperties['priceTxt']+' <span id="pp_price"></span></div>');
				}
				else if (ppProductProperties['pp_price_display_mode'] == 1) {
					$('#our_price_display').after('<span class="pp_price pp_price_display pp_price_display_mode_1" style="display: none;"><span id="pp_price"></span></span>');
				}
				pp.hooks.call('smartprice', 'inject', $('.pp_price_display'));
				if (ppProduct.priceObserver)
					$('#pp_price').after('<span class="ajax-processing" style="visibility: hidden;"></span>');
			}

			// The button to increment the product value
			$(document).off('click', '.product_quantity_up').on('click', '.product_quantity_up', function(e) {
				e.preventDefault();
				fieldName = $(this).data('field-qty');
				var currentVal = pp.parseFloat($('input[name='+fieldName+']').val());
				if (!isNaN(currentVal)) {
					var newVal = (ppProductProperties['pp_qty_step'] > 0 ?
									pp.processQtyStep(currentVal + ppProductProperties['pp_qty_step'], ppProductProperties['pp_qty_step']) :
									currentVal + ppProductProperties['defaultQty']);
					var quantityAvailableT = (quantityAvailable > 0 ? quantityAvailable : 100000000);
					if (newVal <= quantityAvailableT || ppProductProperties['pp_ext'] == 1) {
						$('input[name='+fieldName+']').pp_val(pp.formatQty(newVal), true).trigger('keyup');    
					}
				}
			});
			// The button to decrement the product value
			$(document).off('click', '.product_quantity_down').on('click', '.product_quantity_down', function(e) {
				e.preventDefault();
				fieldName = $(this).data('field-qty');
				var currentVal = pp.parseFloat($('input[name='+fieldName+']').val());
				if (!isNaN(currentVal)) {
					var newVal = (ppProductProperties['pp_qty_step'] > 0 ?
									pp.processQtyStep(currentVal - ppProductProperties['pp_qty_step'], ppProductProperties['pp_qty_step']) :
									currentVal - ppProductProperties['defaultQty']);
					if (newVal > 0 && newVal >= ppProductProperties['pp_qty_step']) {
						$('input[name='+fieldName+']').pp_val(pp.formatQty(newVal), true).trigger('keyup');    
					}
				}
			});
			$(document).on('keyup', '.attribute_select', function(e) {
				e.preventDefault();
				if (e.which == undefined || (e.which >= 33 && e.which <= 40))
					$(this).trigger('change');
			});
		}
		$('#quantityAvailable').html(pp.formatQty(quantityAvailable));
		if (ppProductProperties['pp_qty_text']) {
			$('#quantityAvailable').after('<span class="pp_qty_text"> '+ppProductProperties['pp_qty_text']+'</span>');
			$('#quantityAvailableTxt').html(ppProductProperties['qtyAvailableTxt']);
			$('#quantityAvailableTxtMultiple').html(ppProductProperties['qtyAvailableTxt']);
			$('#quantityDiscount td:nth-child(1)').append('<span class="pp_qty_text"> '+ppProductProperties['pp_qty_text']+'</span>');
		}
		if (ppProductProperties['pp_price_text']) {
			$('#quantityDiscount td:nth-child(2)').append('<span class="pp_price_text"> '+ppProductProperties['pp_price_text']+'</span>');
			$('#our_price_display').after('<span class="pp_price_text"> '+ppProductProperties['pp_price_text']+'</span>');
		}
		if (ppProductProperties['pp_unity_text']) {
			var unit_price_display = $('#unit_price_display').detach();
			$('p.unit-price').empty().append(unit_price_display).append('<span class="pp_unity_text"> '+ppProductProperties['pp_unity_text']+'</span>');
		}
		if (ppProductProperties['display_mode_retail_price'] != undefined) {
			$('#unit_price_display').html(formatCurrency(ppProductProperties['display_mode_retail_price'], currencyFormat, currencySign, currencyBlank));
			$('#unit_price_display').attr('id', 'retail_price_display')
		}
		if ((ppProductProperties['pp_display_mode'] & 1) == 1) {
			pp.swapHtml('.our_price_display', '.unit-price');
		}
		if (typeof productPrice == 'string') {
			productPrice = pp.parseFloat(productPrice);
		}

		if (typeof productHasAttributes != 'undefined' && productHasAttributes)
			findCombination();

		if (ppProduct.original_url.indexOf('#_') != -1) {
			var params = ppProduct.original_url.substring(ppProduct.original_url.indexOf('#_') + 1, ppProduct.original_url.length).split('_');
			if (params[0] == '')
				params.shift();
			for (var i in params) {
				var p = params[i].split('-');
				if (p[0].substr(0, 3) == 'qty') {
					if (p[0].substr(3) == '')
						$('#quantity_wanted').pp_val(pp.formatQty(p[1]), true);
					else if (ppProductProperties['pp_ext'] == 1)
						$('input.pp_ext_prop_quantity_'+p[0].substr(3)).pp_val(pp.formatQty(p[1]), true);
				}
			}
			ppProduct.qty_updated_target = 'pp_ext_prop_quantity';
		}

		pp.ready.when(deferred).done(function() {
			ppPriceObserver.initialized = true;
			ppCalcPrice();
		});
	});
})(pp.ready.deferred());
