/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*/

var ppAdminTemplates = {
	templates: new Object(),
	currentTemplate: 0,
	minQtyExpl: new Object(),
	qtyPolicyExpl: new Object(),
	qtyModeExpl: new Object(),
	displayModeExpl: new Object(),
	priceDisplayModeExpl: new Object(),
	setCurrentTemplate: function(index) {
		if (isNaN(index)) {
			var select = $('#id_pp_template');
			index = (select.length ? select.get(0).selectedIndex : 0);
		}
		ppAdminTemplates.currentTemplate = (index >= 0 && index < ppAdminTemplates.templates.length ? index : 0);
	},
	getCurrentTemplate: function() {
		return ppAdminTemplates.get(ppAdminTemplates.currentTemplate);
	},
	get: function(index) {
		return ppAdminTemplates.templates[(index >= 0 && index < ppAdminTemplates.templates.length ? index : 0)];
	},
	showTemplateInfo: function(force_update) {
		force_update = (force_update == undefined ? false : force_update);
		var template = ppAdminTemplates.getCurrentTemplate();
		var unity_text = template.unity_text;
		$('.pp_template_desc').html(template.description);
		$('.pp_price_text').html(template.price_text);
		$('.pp_unity_text').html(unity_text);
		$('.pp_qty_text').html(template.qty_text);
		$('.pp_bo_qty_text').html(template.bo_qty_text);
		$('.pp_unit_price_ratio').html(template.ratio > 0 ? pp.formatQty(template.ratio) : '');
		$('.pp_minimal_price_ratio').html(template.minimal_price_ratio > 0 ? pp.formatQty(template.minimal_price_ratio) : '');
		$('.pp_minimal_quantity').html(pp.formatQty(template.min_qty));
		$('.pp_bo_minimal_quantity').html(pp.formatQty(template.min_bo_qty));
		$('.pp_default_quantity').html(pp.formatQty(template.default_quantity));
		$('.pp_qty_step').html(template.qty_step > 0 ? pp.formatQty(template.qty_step) : '');
		$('.pp_explanation').html(template.explanation);

		$('.minimal_quantity_expl').html(ppAdminTemplates.minQtyExpl[template.qty_policy]);
		$('.pp_qty_policy_expl').html(ppAdminTemplates.qtyPolicyExpl[template.ext == 1 ? 'ext' : template.qty_policy]);
		$('.pp_qty_mode_expl').html(ppAdminTemplates.qtyModeExpl[template.qty_mode]);
		var display_mode_expl = '';
		if (template.display_mode == 0)
			display_mode_expl = ppAdminTemplates.displayModeExpl['0'];
		else {
			if ((template.display_mode & 1) == 1)
				display_mode_expl = ppAdminTemplates.displayModeExpl['1'];
			if ((template.display_mode & 2) == 2)
				display_mode_expl = (display_mode_expl == '' ? '' : display_mode_expl + ', ') + ppAdminTemplates.displayModeExpl['2'];
			if ((template.display_mode & 4) == 4)
				display_mode_expl = (display_mode_expl == '' ? '' : display_mode_expl + ', ') + ppAdminTemplates.displayModeExpl['4'];
		}
		$('.pp_display_mode_expl').html(display_mode_expl);
		$('.pp_price_display_mode_expl').html(ppAdminTemplates.priceDisplayModeExpl[template.price_display_mode]);
		
		var displayMode = template.display_mode;
		if ((displayMode == 2 || displayMode == 3) && unity_text.length > 0) {
			$('.pp_retail_price_text').html(unity_text);
		}
		else {
			$('.pp_retail_price_text').html(template.price_text);
		}

		if (template.ratio > 0) {
			$('#unit_price').hide();
			$('#pp_unit_price').show();
		}
		else {
			$('#unit_price').show();
			$('#pp_unit_price').hide();
		}

		if (unity_text.length > 0) {
			$('#unity').hide();
			$('#unity_second').html(unity_text);
			$('#unity_third').html(unity_text);
			$('.ps_unity_display').hide();
			$('.pp_unity_display').show();
		}
		else {
			var _unity = $('#unity');
			if (_unity.length) { // need during tab loading
				_unity.show();
				$('#unity_second').html(_unity.val());
				$('#unity_third').html(_unity.val());
			}
			$('.ps_unity_display').show();
			$('.pp_unity_display').hide();
		}

		$('.pp_multidimensional')[template.ext == 1 ? 'show' : 'hide']();
		if (template.ext == 1 && template.ext_policy == 2) {
			$('.bo_ext_prop_block').show();
			if (template.ext_title.length > 0) {
				$('.pp_ext_title').html(template.ext_title).show();
			}
			else {
				$('.pp_ext_title').hide();
			}
			$('.pp_ext_prop_wrapper').hide();
			for (var i = 1; i <= Object.keys(template.ext_prop).length; i++) {
				var prop = template.ext_prop[i];
				var id_product_attribute = 0;
				if (ppProduct.hasAttributes) {
					id_product_attribute = $('input#id_product_attribute').val();
				}
				var o = $('.pp_ext_prop_wrapper.pp_ext_prop_wrapper_' + i).show();
				o.find('.pp_ext_prop_property').html(prop.property);
				o.find('.pp_ext_prop_text').html(prop.text);
				var field = o.find('.pp_ext_prop_quantity');
				if (force_update || !field.data('has-value')) {
					field.data('has-value', true).val(ppProduct.getExtQty(id_product_attribute, i, prop.quantity));
				}
			}
		}
		else {
			$('.bo_ext_prop_block').hide();
		}

		if (ppAdminTemplates.currentTemplate > 0) {
			var a = $('a.edit-pp-template-link');
			if (a.length) {
				a.attr('href', a.attr('href').replace(/&id=.*/, '&id=') + template.id_pp_template);
			}
			$('.pp_template_toggle').css('visibility', 'visible');
			$('.ppMinQtyExplTemplate').css('visibility', 'visible');
		}
		else {
			$('.pp_template_toggle').css('visibility', 'hidden');
			$('.ppMinQtyExplTemplate').css('visibility', 'hidden');
			$('.pp_template_value').html('');
		}
		$('.input-group-addon.pp_unity_display').each(function() {
			var self = $(this);
			if ($(this).html().length == 0) {
				self.addClass('hide-when-empty');
			}
			else {
				self.removeClass('hide-when-empty');
			}
		});
	},
	changeTemplateRelatedFields: function(index, section) {
		ppAdminTemplates.setCurrentTemplate(index);
		var template = ppAdminTemplates.getCurrentTemplate();
		var minimal_quantity = $('#minimal_quantity');
		if (minimal_quantity.length) minimal_quantity.val(template.bo_min_qty);
		var attribute_minimal_quantity = $('#attribute_minimal_quantity');
		if (attribute_minimal_quantity.length) attribute_minimal_quantity.val(template.bo_min_qty);
		var unit_price = $('#unit_price');
		if (unit_price.length) {
			unit_price.val('');
			$('#pp_unit_price').html('');
			$('#unit_price_with_tax').html('');
		}

		ppAdminTemplates.showTemplateInfo(section == 'all');
		ppAdminTemplates.calcUnitPrice();
	},
	calcUnitPrice: function() {
		var template = ppAdminTemplates.getCurrentTemplate();
		if (template.ratio > 0) {
			var priceTE = pp.parseFloat($('#priceTEReal').val());
			if (isNaN(priceTE) || priceTE == 0) {
				unit_price = '';
			}
			else {
				var newPrice = priceTE / template.ratio;
				var unit_price = ps_round(newPrice, 2).toFixed(2);
			}
			$('#unit_price').val(unit_price);
			$('#pp_unit_price').html(unit_price);
			unitPriceWithTax('unit');
		}
	},
	amendPrices: function() {
		if (isNaN(pp.parseFloat($('#priceTE').val()))) $('#priceTE').val('');
		if (isNaN(pp.parseFloat($('#priceTI').val()))) $('#priceTI').val('');
		if (isNaN($('#finalPrice').html())) $('#finalPrice').html('');
	}
};

function ppMakeTotalProductCaculation(element, quantity, price) {
	if (element) {
		quantity = pp.parseFloat(element.find('td .edit_product_quantity').val());
		if (quantity <= 0 || isNaN(quantity))
			quantity = 1;
		var qf = element.find('.product_quantity_edit .pp_quantity_fractional');
		if (qf.length) {
			var qty = pp.parseFloat(qf.text());
			price = ps_round(price * qty, priceDisplayPrecision);
		}
	}
	return makeTotalProductCaculation(quantity, price);
}
