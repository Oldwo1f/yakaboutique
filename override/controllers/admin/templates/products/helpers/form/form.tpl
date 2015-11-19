{*
* Extends product properties and add support for products with fractional
* units of measurements (for example: weight, length, volume).
*
* NOTICE OF LICENSE
*
* This source file is subject to the commercial software
* license agreement available through the world-wide-web at this URL:
* http://psandmore.com/licenses/sla
* If you are unable to obtain the license through the
* world-wide-web, please send an email to
* support@psandmore.com so we can send you a copy immediately.
*
* --- DO NOT REMOVE OR MODIFY THIS LINE PP_VERSION[1.6.0.14] PP_VERSION_REQUIRED[1.6] ---
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*}

{extends file="controllers/products/helpers/form/form.tpl"}

{block name="script" append}
	ppAdminTemplates.minQtyExpl = {
		"-1" : "{$s_ppMinQtyExpl_disable}",
		 "0" : "{$s_ppMinQtyExpl_0}",
		 "1" : "{$s_ppMinQtyExpl_1}",
		 "2" : "{$s_ppMinQtyExpl_2}"
	};
	ppAdminTemplates.qtyPolicyExpl = {
		 "0" : "{$s_pp_qty_policy_0}",
		 "1" : "{$s_pp_qty_policy_1}",
		 "2" : "{$s_pp_qty_policy_2}",
		 "ext" : "{$s_pp_qty_policy_ext}"
	};
	ppAdminTemplates.qtyModeExpl = {
		 "0" : "{$s_pp_qty_mode_0}",
		 "1" : "{$s_pp_qty_mode_1}"
	};
	ppAdminTemplates.displayModeExpl = {
		 "0" : "{$s_pp_display_mode_0}",
		 "1" : "{$s_pp_display_mode_1}",
		 "2" : "{$s_pp_display_mode_2}",
		 "4" : "{$s_pp_display_mode_4}"
	};
	ppAdminTemplates.priceDisplayModeExpl = {
		 "0" : "{$s_pp_price_display_mode_0}",
		 "1" : "{$s_pp_price_display_mode_1}"
	};
	{assign var=hasAttributes value=$product->hasAttributes()}
	ppProduct.hasAttributes = {$hasAttributes};
	ppProduct.fallback_ext_quantity = 1;
	{assign var=boTemplates value=PP::getAdminProductsTemplates((int)$product->id_pp_template)}
	ppAdminTemplates.templates = {Tools::jsonEncode($boTemplates)};
	{foreach from=$boTemplates item=template name=bo}
		{if $product->id_pp_template == $template['id_pp_template']}
			ppAdminTemplates.currentTemplate = {$smarty.foreach.bo.index};
			{if $boTemplates[{$smarty.foreach.bo.index}].ext == 1}
			ppProduct.prop = {Tools::jsonEncode($product->productProp())};
			{/if}
		{/if}
	{/foreach}

	$(document).ready(function() {
		function add_bo_ext_prop_block(selector, separation_on_top) {
			$(selector).append('<div class="bo_ext_prop_block" style="display:none;"></div>');
			var pp_ext = $(selector + ' .bo_ext_prop_block');
			if (separation_on_top) {
				pp_ext.append('<div class="separation"></div>');
			}
			pp_ext.append('<h4>{$s_ppe_title}</h4><div class="pp_ext_title"></div>');
			for (i = 1; i <= 3; i++) {
				var q = 'pp_ext_prop_quantity_' + i;
				var s = '<div class="pp_ext_prop_wrapper ' + 'pp_ext_prop_wrapper_' + i + '">';
				s += '<span class="pp_ext_prop_property"></span> ';
				s += '<input type="text" class="pp_ext_prop_quantity" name="' + q + '" value=""></input>';
				s += ' <span class="pp_ext_prop_text"></span>';
				s += '</div>';
				pp_ext.append(s);
			}
			if (!separation_on_top) {
				pp_ext.append('<div class="separation"></div>');
			}
		}

		tabs_manager.onLoad('Informations', function() {
			{if (int)$product->id > 0}
				$('#product-tab-content-Informations h3.tab').append('<span style="float:right; padding-right: 5px;">{$s_ID} <span style="font-weight: normal;">{$product->id}</span>');
			{/if}
		});
		tabs_manager.onLoad('Prices', function() {
			$('#priceTE').after('<span class="input-group-addon pp_retail_price_text"></span>');
			$('#sp_price').after(' <span class="input-group-addon pp_price_text"></span>');
			$('#unit_price + span.input-group-addon').addClass('ps_unity_display');
			$('#unit_price').after('<span class="input-group-addon pp_unit_price" id="pp_unit_price" style="dysplay:none;"></span>');
			$('#unity').after('<span class="input-group-addon pp_unity_display pp_unity_text"></span><span class="input-group-addon pp_unity_display" id="pp_unity_text_expl" style="dysplay:none;">({$s_pp_unity_text_expl})</span>');
			if ($('#unit_price_with_tax').length) {
				$($('#unit_price_with_tax')[0].nextSibling).wrap('<span class="ps_unity_display"></span>');
				$('#unity_second').before(' ');
			}

			var f_calcPriceTI = window.calcPriceTI;
			window.calcPriceTI = function() {
				ppAdminTemplates.calcUnitPrice();
				f_calcPriceTI();
				unitPriceWithTax('unit');
				ppAdminTemplates.amendPrices();
			};
			var f_calcPriceTE = window.calcPriceTE;
			window.calcPriceTE = function() {
				ppAdminTemplates.calcUnitPrice();
				f_calcPriceTE();
				unitPriceWithTax('unit');
				ppAdminTemplates.amendPrices();
			};
			ppAdminTemplates.showTemplateInfo();
			calcPriceTI();
		});
		tabs_manager.onLoad('Quantities', function() {
			var span = $('.available_quantity').closest('table').find("th:first > span");
			span.html(span.html() + ' <i class="pp_bo_qty_text"></i>');
			{if !$hasAttributes}
				var minimal_quantity = $('#minimal_quantity');
				minimal_quantity.val({$product->resolveBoMinQty($product->minimal_quantity, $product->minimal_quantity_fractional)});
				minimal_quantity.parent().find("p.help-block").addClass('minimal_quantity_expl');
				minimal_quantity.wrap('<div class="input-group fixed-width-sm"></div>');
				minimal_quantity.after('<span class="input-group-addon pp_bo_qty_text"></span><div class="input-group-addon ppMinQtyExplTemplate" style="visibility:hidden;">'+"{$s_minimal_quantity}"+' <span class="pp_minimal_quantity"></span> <span class="pp_bo_qty_text"></span></div>');
				add_bo_ext_prop_block('#product-tab-content-Quantities', true);
			{/if}
			ppAdminTemplates.showTemplateInfo();
		});
		tabs_manager.onLoad('Pack', function() {
			var info = $('#product-pack div.alert-info');
			info.html(info.html() + '<br/>'+"{$s_pack_hint}");
		});
		tabs_manager.onLoad('Combinations', function() {
			$('#attribute_wholesale_price').after('<span class="input-group-addon pp_price_text"></span>');
			$('#attribute_wholesale_price').
			parent().removeClass('col-lg-2').addClass('col-lg-3');
			{if $hasAttributes}
				var attribute_minimal_quantity = $('#attribute_minimal_quantity');
				attribute_minimal_quantity.val({$product->resolveBoMinQty($product->minimal_quantity, $product->minimal_quantity_fractional)});
				attribute_minimal_quantity.addClass('fixed-width-sm');
				attribute_minimal_quantity.parent().after('<p class="help-block minimal_quantity_expl"></p>');
				var label = attribute_minimal_quantity.closest('.form-group').find('.label-tooltip');
				label.parent().html(label.html());
				attribute_minimal_quantity.after('<span class="input-group-addon pp_bo_qty_text"></span><div class="input-group-addon ppMinQtyExplTemplate" style="visibility:hidden;">'+"{$s_minimal_quantity}"+' <span class="pp_minimal_quantity"></span> <span class="pp_bo_qty_text"></span></div>');
				add_bo_ext_prop_block('#add_new_combination', false);
			{/if}
			$('#link-Combinations,#desc-product-newCombination,#product-tab-content-Combinations a.edit').on('click', function() {
				ppAdminTemplates.showTemplateInfo();
			});
			var f_calcImpactPriceTI = window.calcImpactPriceTI;
			window.calcImpactPriceTI = function() {
				f_calcImpactPriceTI();
				ppAdminTemplates.showTemplateInfo(true);
			};

			ppAdminTemplates.showTemplateInfo();
		});
		tabs_manager.onLoad('ModulePproperties', function() {
			$('#id_pp_template').on('change keyup', function () {
				ppAdminTemplates.changeTemplateRelatedFields($(this).get(0).selectedIndex, 'all');
				$('#product-tab-content-ModulePproperties .hint:hidden').fadeIn('slow');
				if ($('#product-modulepproperties div.panel-footer').length == 0) {
					$('#product-tab-content-Informations div.panel-footer').clone().appendTo('#product-modulepproperties');
				}
			});
			if ($('#product-modulepproperties div.panel-footer').length == 0 && $('#product-tab-content-Informations div.panel-footer').length) {
				$('#product-tab-content-Informations div.panel-footer').clone().appendTo('#product-modulepproperties');
			}
			ppAdminTemplates.showTemplateInfo();
		});
	});
{/block}

