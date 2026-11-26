(function ($) {
	'use strict';

	var TEXT_ADD = (window.necCards && necCards.addToCart) || 'افزودن به سبد خرید';
	var TEXT_VIEW = (window.necCards && necCards.viewCourse) || 'مشاهده دوره';

	function productIdFrom(el) {
		var cls = el.className || '';
		var m = cls.match(/(?:^|\s)post-(\d+)(?:\s|$)/);
		return m ? m[1] : '';
	}

	function isAjaxType(el) {
		var cls = ' ' + (el.className || '') + ' ';
		if (cls.indexOf(' product-type-simple ') === -1) {
			return false;
		}
		if (cls.indexOf(' purchasable ') === -1) {
			return false;
		}
		if (cls.indexOf(' outofstock ') !== -1) {
			return false;
		}
		return true;
	}

	function permalink(el) {
		var a = el.querySelector('.course-title a, a.woocommerce-LoopProduct-link');
		return a ? a.getAttribute('href') || '' : '';
	}

	function ensureButton(el) {
		if (!el || el.querySelector('.nec-add-to-cart')) {
			return;
		}
		var id = productIdFrom(el);
		if (!id) {
			return;
		}
		var inner = el.querySelector('.course-item-inner') || el;
		var href = permalink(el);
		var ajax = isAjaxType(el);
		var url = href || ('?add-to-cart=' + id);
		if (ajax) {
			url = href
				? href + (href.indexOf('?') >= 0 ? '&' : '?') + 'add-to-cart=' + encodeURIComponent(id)
				: '?add-to-cart=' + encodeURIComponent(id);
		}

		var wrap = document.createElement('div');
		wrap.className = 'nec-add-to-cart-wrap';

		var a = document.createElement('a');
		a.className = 'button nec-add-to-cart' + (ajax ? ' add_to_cart_button ajax_add_to_cart product_type_simple' : '');
		a.href = url;
		a.setAttribute('data-product_id', id);
		a.setAttribute('data-quantity', '1');
		a.setAttribute('rel', 'nofollow');
		a.innerHTML = '<i class="fal fa-shopping-bag" aria-hidden="true"></i><span>' +
			(ajax ? TEXT_ADD : TEXT_VIEW) +
			'</span>';

		wrap.appendChild(a);
		inner.appendChild(wrap);
	}

	function enhance(root) {
		var scope = root && root.querySelectorAll ? root : document;
		var cards = scope.querySelectorAll ? scope.querySelectorAll('.course-item') : [];
		for (var i = 0; i < cards.length; i++) {
			ensureButton(cards[i]);
		}
	}

	function boot() {
		enhance(document);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	if ($ && $.fn) {
		$(document).on('initialized.owl.carousel translated.owl.carousel refreshed.owl.carousel', boot);
		$(document.body).on('updated_wc_div added_to_cart', boot);
	}
})(window.jQuery);
