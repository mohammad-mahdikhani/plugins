(function () {
	var LABEL = "قیمت";
	var UPDATED = "آخرین به‌روزرسانی:";
	var CURRENCY = "تومان";
	var CART = "اضافه به سبد خرید";
	var GALLERY = "مشاهده گالری تصاویر";

	function clean(value) {
		return (value || "").replace(/\s+/g, " ").trim();
	}

	function mainPrice() {
		return document.querySelector(".elementor-element-69a420b5 .price, body.single-product .summary p.price, body.single-product .elementor-widget-woocommerce-product-price .price");
	}

	function amountFrom(price) {
		var node = price.querySelector(".woocommerce-Price-amount");
		if (!node) {
			return "";
		}
		var clone = node.cloneNode(true);
		clone.querySelectorAll(".woocommerce-Price-currencySymbol, .as-price-spotlight").forEach(function (el) {
			el.remove();
		});
		return clean(clone.textContent).replace(new RegExp(CURRENCY, "g"), "").trim();
	}

	function dateFrom(price) {
		var time = price.querySelector("time.as-price-spotlight__date");
		if (!time) {
			return null;
		}
		var raw = clean(time.textContent);
		var parts = raw.split(/[:：]/);
		var date = clean(parts.length > 1 ? parts.slice(1).join(":") : raw);
		if (!date || date.indexOf("به‌روزرسانی") !== -1) {
			return null;
		}
		return {
			iso: time.getAttribute("datetime") || "",
			date: date
		};
	}

	function cartButton(form) {
		return form.querySelector(".single_add_to_cart_button, button[name='add-to-cart']");
	}

	function galleryButton(card) {
		var existing = document.querySelector(".as-gallery-btn");
		if (existing) {
			existing.type = "button";
			existing.textContent = GALLERY;
			return existing;
		}
		var gallery = document.querySelector(".woocommerce-product-gallery");
		if (!gallery) {
			return null;
		}
		var button = document.createElement("button");
		button.type = "button";
		button.className = "as-gallery-btn";
		button.textContent = GALLERY;
		button.addEventListener("click", function () {
			var trigger = gallery.querySelector(".woocommerce-product-gallery__trigger, a.woocommerce-product-gallery__trigger");
			if (trigger) {
				trigger.click();
				return;
			}
			var link = gallery.querySelector("a");
			if (link) {
				link.click();
			}
		});
		return button;
	}

	function build() {
		if (!document.body.classList.contains("single-product")) {
			return false;
		}
		var price = mainPrice();
		var form = document.querySelector(".elementor-element-1843cd7e form.cart, form.cart");
		if (!price || !form) {
			return false;
		}
		var amount = amountFrom(price);
		if (!amount) {
			return false;
		}

		var gallery = document.querySelector(".woocommerce-product-gallery");
		var card = document.querySelector(".as-buy");
		if (!card) {
			card = document.createElement("div");
			card.className = "as-buy as-purchase";
			var host = gallery ? gallery.parentNode : price.parentNode;
			if (!host) {
				return false;
			}
			host.appendChild(card);
		}
		card.classList.add("as-purchase");

		var meta = card.querySelector(".as-purchase__meta");
		if (!meta) {
			meta = document.createElement("div");
			meta.className = "as-purchase__meta";
			card.insertBefore(meta, card.firstChild);
		}

		var date = dateFrom(price);
		meta.textContent = "";
		var label = document.createElement("span");
		label.className = "as-purchase__label";
		label.textContent = LABEL;
		var amountRow = document.createElement("p");
		amountRow.className = "as-purchase__amount";
		var num = document.createElement("span");
		num.className = "as-purchase__num";
		num.textContent = amount;
		var currency = document.createElement("span");
		currency.className = "as-purchase__currency";
		currency.textContent = CURRENCY;
		amountRow.appendChild(num);
		amountRow.appendChild(currency);
		meta.appendChild(label);
		meta.appendChild(amountRow);
		if (date) {
			var updated = document.createElement("p");
			updated.className = "as-purchase__updated";
			var updatedLabel = document.createElement("span");
			updatedLabel.className = "as-purchase__updated-label";
			updatedLabel.textContent = UPDATED;
			var time = document.createElement("time");
			time.textContent = date.date;
			if (date.iso) {
				time.setAttribute("datetime", date.iso);
			}
			updated.appendChild(updatedLabel);
			updated.appendChild(time);
			meta.appendChild(updated);
		}

		var smashed = card.querySelector(".as-price");
		if (smashed) {
			smashed.remove();
		}

		var actions = card.querySelector(".as-purchase__actions");
		if (!actions) {
			actions = document.createElement("div");
			actions.className = "as-purchase__actions";
			card.appendChild(actions);
		}
		if (!actions.contains(form)) {
			actions.insertBefore(form, actions.firstChild);
		}
		var shell = document.querySelector(".elementor-element-1843cd7e");
		if (shell && !shell.contains(form)) {
			shell.classList.add("as-purchase-source");
		}

		var button = cartButton(form);
		if (button && button.childElementCount === 0) {
			var current = clean(button.textContent);
			if (!current || current === "خرید") {
				button.textContent = CART;
			}
		}

		var galleryControl = galleryButton(card);
		if (galleryControl && galleryControl.parentNode !== actions) {
			actions.appendChild(galleryControl);
		}

		Array.prototype.slice.call(card.children).forEach(function (node) {
			if (node === meta || node === actions) {
				return;
			}
			if (!node.querySelector || !node.querySelector("form")) {
				node.remove();
			}
		});

		var source = price.closest(".elementor-element-69a420b5, .elementor-widget-woocommerce-product-price, .elementor-widget-wl-single-product-price");
		if (source) {
			source.classList.add("as-purchase-source");
		}

		if (gallery && gallery.parentNode && card.parentNode !== gallery.parentNode) {
			gallery.parentNode.appendChild(card);
		}
		return true;
	}

	function boot() {
		var tries = 0;
		function tick() {
			tries += 1;
			build();
			if (tries < 24) {
				window.setTimeout(tick, 150);
			}
		}
		tick();
	}

	if (document.readyState === "loading") {
		document.addEventListener("DOMContentLoaded", boot);
	} else {
		boot();
	}
	window.addEventListener("load", boot);
	document.addEventListener("DOMContentLiteSpeedLoaded", boot);
})();
