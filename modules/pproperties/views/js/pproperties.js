/**
* Product Properties Extension
*
* @author    PS&More www.psandmore.com <support@psandmore.com>
* @copyright 2011-2015 PS&More
* @license   psandmore.com/licenses/sla
*
* Do not modify this file. If you need to make changes create file
* themes/your_theme/js/modules/pproperties/views/js/custom.js
* and add your changes there.
*/

var pp = {
	decimalSign: '',
	delay: function(func, wait) {
		var args = Array.prototype.slice.call(arguments, 2);
		return setTimeout(function() {return func.apply(null, args);}, wait);
	},
	formatQty: function(qty, decimalSign, decimals) {
		var q = pp.parseFloat(String(qty).replace(/,/g, '.'));
		qty = String(q.toFixed(8));  // toFixed is requred because of javascript floating operation bugs (try: 3 * 1.2 = 3.5999999999999996)
		var dot = qty.indexOf('.');
		if (dot >= 0) {
			var i = qty.length - 1;
			for (; i > dot; i--) {
				if ('0' != qty.charAt(i))
					break;
			}
			qty = qty.substring(0, i+1);
		}
		if (qty.indexOf('.', qty.length - 1) != -1) {
			qty = qty.substring(0, qty.length - 1);
		}
		if (decimals != undefined && decimals >= 0) {
			qty = q.toFixed(decimals);
		}
		if (decimalSign != undefined && decimalSign !== '' ? decimalSign == ',' : this.decimalSign == ',') {
			qty = qty.replace(/\./g, ',');
		}
		return qty;
	},
	normalizeFloatAsString: function(input) {
		string = String(input).replace(/,/g, '.').replace(/[^0-9\.-]/g, '');
		if (string.indexOf('.') == 0) string = '0' + string;
		return string;
	},
	parseFloat: function(input) {
		var f = parseFloat(pp.normalizeFloatAsString(input));
		return (isNaN(f) ? 0 : f);
	},
	decimals: function(number) {
		var s = String(number).replace(/,/g, '.');
		var dot = s.indexOf('.');
		return (dot >= 0 ? s.length - dot - 1 : -1);
	},
	safeoutput: function(input) {
		if (typeof(input) == 'string') {
			return input.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/&lt;sup&gt;/g, '<sup>').replace(new RegExp('&lt;/sup&gt;','g'), '</sup>');
		}
		return input;
	},
	spanText: function(cl, text, prefix) {
		return (prefix == undefined ? ' ' : prefix) + '<span class="'+cl+'">'+pp.safeoutput(text)+'</span>';
	},
	priceText: function(product) {
		return '<span class="pp_price_text">'+pp.safeoutput(product.pp_price_text)+'</span>';
	},
	qtyText: function(product, decimalSign, wrapper_tag, no_x) {
		if (product.cart_quantity_fractional > 0)
			return '<'+(wrapper_tag?wrapper_tag:'div')+' class="pp_qty_wrapper'+(no_x?'_no_x':'')+'">'+(no_x?'':'<span class="pp_x">x </span>')+'<span class="pp_qty">'+pp.formatQty(product.cart_quantity_fractional, decimalSign)+'</span>&nbsp;<span class="pp_qty_text">'+pp.safeoutput(product.pp_qty_text)+'</span></'+(wrapper_tag?'/'+wrapper_tag:'/div')+'>';
		return '';
	},
	totalText: function(product, qty) {
		return '<span class="pp_qty_wrapper"><span class="pp_qty">'+qty+'</span>&nbsp;<span class="pp_qty_text">'+pp.safeoutput(product.pp_qty_text)+'</span></span>';
	},
	getCartTotalQty: function(product) {
		return (product.cart_quantity_fractional > 0 ? product.cart_quantity * product.cart_quantity_fractional : product.cart_quantity);
	},
	processQtyStep: function(qty, qtyStep) {
		qty = qty.toFixed(8);
		if (qtyStep > 0) {
			var q = qty/qtyStep;
			q = Math.floor(q.toFixed(8));
			var s = q * qtyStep;
			s = s.toFixed(8);  // toFixed is requred because of javascript floating operation bugs (try: 3 * 1.2 = 3.5999999999999996)
			if (s < qty) {
				qty = (q+1)*qtyStep;
				qty = qty.toFixed(8);
			}
		}
		return parseFloat(qty.toString()); // remove trailing zeros
	},
	isPackCalculator: function(product) {
		return ((product['pp_ext'] == 1) && (product['pp_ext_policy'] == 1));
	},
	packCalculator: function(qty) {
		if (!isNaN(qty) && qty > 0 && productUnitPriceRatio > 0) {
			var q = qty / productUnitPriceRatio;
			q = q.toFixed(8);
			$('#quantity_wanted').val(pp.formatQty(Math.ceil(q)));
		}
	},
	startsWith: function(string, search) {
		return (string != undefined && search != undefined && search.length > 0 && string.lastIndexOf(search, search.length - 1) == 0);
	},
	endsWith: function(string, search) {
		return (string != undefined && search != undefined && search.length > 0 && string.indexOf(search, string.length - search.length) != -1);
	},
	contains: function(string, search) {
		return (string != undefined && search != undefined && search.length > 0 && string.indexOf(search) != -1);
	},
	getClasses: function(o) {
		var ca = o.attr('class');
		var rval = [];
		if (ca && ca.length && ca.split) {
			ca = jQuery.trim(ca); /* strip leading and trailing spaces */
			ca = ca.replace(/\s+/g,' '); /* remove doube spaces */
			rval = ca.split(' ');
		}
		return rval;
	},
	swapHtml: function(selector1, selector2) {
		var o1 = $(selector1);
		var o2 = $(selector2);
		if (o1.length && o2.length) {
			var o1_html = o1.html();
			var o2_html = o2.html();
			o1.empty().html(o2_html);
			o2.empty().html(o1_html);
		}
	},
	findUrlParam: function(url, param) {
		if (typeof url == 'string') {
			var i, val, params = url.split("?")[1].split("&");       
			for (i = 0 ; i < params.length ; i++) {
				val = params[i].split("=");
				if (val[0] == param) {
					return val[1];
				}
			}
		}
		return undefined;
	},
	observer: function(func, condition, interval) {
		var o = {
			timer: 0,
			timeout: false,
			func: func,
			condition: condition,
			args: null,
			clear: function() {
				clearTimeout(this.timer);
				this.timer = 0;
				this.timeout = false;
			},
			observe: function() {
				this.args = arguments;
				if (!this.timeout) {
					if (condition(o)) {
						this.clear();
						this.timer = setTimeout(function() {
							o.timer = 0;
							o.timeout = true;
							o.func(o);
							o.timeout = false;
						}, interval);
						return true;
					}
				}
				return false;
			},
			observing: function() {
				return (this.timer != 0);
			}
		};
		return o;
	},
	ready: {
		deferreds:[],
		deferred: function() {this.deferreds.push($.Deferred()); return this.deferreds[this.deferreds.length-1]},
		when: function(deferred) {deferred.resolve(); return $.when.apply(this, this.deferreds);}
	},
	hooks: {
		// to add definition of new onReady hook function add the following code:
		// pp.hooks.onReadyFuncName = function(id, params) {};
		// to register new onReady hook function to be run on the document ready event add the following code:
		// pp.hooks.onReady(id, params);
		onReadyHooks: new Object(),
		onReady: function(id, hook) {
			var params = null;
			if (arguments.length > 2) {
				params = Array.prototype.slice.call(arguments, 2);
			}
			this.onReadyHooks[id] = [hook, params];
		},
		// to register new hook function to be called by pp.hooks.call() add the following code:
		// pp.hooks.hook(id, func);
		hooks: new Object(),
		hook: function(id, func) {
			if ($.isFunction(func))
				this.hooks[id] = func;
		},
		call: function(id) {
			if ($.isFunction(this.hooks[id])) {
				if (arguments.length > 1)
					this.hooks[id].apply(this, Array.prototype.slice.call(arguments, 1));
				else
					this.hooks[id].call();
			}
		}
	}
};

var ppProduct = {
	prop: false,
	fallback_ext_quantity: '',
	original_url: window.location + '',
	a_url: [],
	getProp: function(id_product_attribute, position) {
		if ($.isArray(ppProduct.prop) && ppProduct.prop.length > 0) {
			for (var i = ppProduct.prop.length - 1; i >= 0; i--) {
				var p = ppProduct.prop[i];
				if (p.id_product_attribute == id_product_attribute && p.position == position) {
					return p;
				}
			};
		}
		return undefined;
	},
	getExtQty: function(id_product_attribute, position, default_value) {
		var prop = ppProduct.getProp(id_product_attribute, position);
		var qty = (prop != undefined ? prop.quantity : default_value);
		if (qty == undefined || qty <= 0) {
			qty = ppProduct.fallback_ext_quantity;
		}
		return (qty == '' ? '' : pp.formatQty(qty));
	},
	amendUrlRegister: function(element, key) {
		if (element.length) {
			element.data('amendUrl', key);
			ppProduct.a_url.push(element);
		}
	},
	amendUrl: function() {
		var str = '';
		for (var i = 0; i < ppProduct.a_url.length; i++) {
			if (ppProduct.a_url[i].pp_is(':pp_val')) {
				var o = ppProduct.a_url[i];
				if (pp.parseFloat(o.val()) > 0) {
					str = '#'; // at least one field modified
					break;
				}
			}
		};
		if (str.length) {
			for (var i = 0; i < ppProduct.a_url.length; i++) {
				var o = ppProduct.a_url[i];
				if (pp.parseFloat(o.val()) > 0)
					str += '_' + o.data('amendUrl') + '-' + pp.formatQty(o.val());
			};

			var url = window.location + '';
			if (url.indexOf('#_') != -1)
				url = url.substring(0, url.indexOf('#_'));
			if (url.indexOf('#/') != -1 && url.indexOf('/', url.length - 1) == -1)
				url = url + '/';
			url += str;

			original_url = url;
			window.location.replace(original_url);
		}
	}
};

jQuery.fn.extend({
	pp_val: function (value, force) {
		if (arguments.length && (force === true || this.data('pp_val') !== true))
			this.val(value);
		else
			this.val();
		if (force !== false) this.data('pp_val', true);
		return this;
	},
	pp_is: function (selector) {
		return !!selector && (
			typeof selector === "string" ?
				(selector == ":pp_val" ? this.data('pp_val') === true : false)
				: false);
	}
});

(function(deferred) {
	$(document).ready(function() {
		pp.ready.when(deferred).done(function() {
			$.each(pp.hooks.hooks, function(key, value) {
				if (typeof pp.hooks[value[0]] == 'function')
					pp.hooks[value[0]](key, value[1]);
			});
		});
	});
})(pp.ready.deferred());
