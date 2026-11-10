<?php
/**
 * Quantity and add-to-cart on homepage carousel cards.
 */

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="pm-card-buy">'
			. '.pm-card{display:flex;flex-direction:column}'
			. '.pm-card .pm-product__buy{display:flex;flex-direction:column;align-items:stretch;gap:.4rem;margin:.45rem 0 0;padding:0}'
			. '.pm-card .pm-product__stepper{display:inline-flex;direction:ltr;align-items:center;align-self:flex-start;height:32px;padding:0 .1rem;border:1px solid #d5ebe3;border-radius:999px;background:#fff}'
			. '.pm-card .pm-product__stepper button{width:22px;height:22px;border:0;background:transparent;color:#1e4339;border-radius:50%;font:700 1rem/1 inherit;cursor:pointer;padding:0}'
			. '.pm-card .pm-product__stepper input.qty{width:1.7rem!important;height:auto!important;min-height:0!important;margin:0!important;padding:0!important;border:0!important;background:transparent!important;box-shadow:none!important;text-align:center;font:700 .78rem/1 inherit;color:#1e4339;-moz-appearance:textfield;appearance:textfield}'
			. '.pm-card .pm-product__stepper input.qty::-webkit-outer-spin-button,.pm-card .pm-product__stepper input.qty::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}'
			. '.pm-card .pm-product__buy .pm-product__add{width:100%!important;height:32px!important;min-height:32px!important;margin:0!important;padding:0 .45rem!important;border:0!important;border-radius:999px!important;background:#4aa88e!important;color:#fff!important;font-family:inherit!important;font-size:.78rem!important;font-weight:700!important;line-height:1!important;box-shadow:none!important;white-space:nowrap}'
			. '</style>';
	},
	20
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
<script id="pm-card-buy-js">
(function () {
  var catalog = null;
  var loading = null;
  function pathOf(url) {
    try {
      return new URL(url, location.origin).pathname.replace(/\/+$/, "");
    } catch (error) {
      return "";
    }
  }
  function loadCatalog() {
    if (catalog) return Promise.resolve(catalog);
    if (loading) return loading;
    loading = fetch("/wp-json/wc/store/v1/products?per_page=100", {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    })
      .then(function (res) { return res.ok ? res.json() : []; })
      .then(function (rows) {
        catalog = {};
        (rows || []).forEach(function (product) {
          if (!product || !product.permalink || !product.id) return;
          catalog[pathOf(product.permalink)] = product;
        });
        return catalog;
      })
      .catch(function () {
        catalog = {};
        return catalog;
      });
    return loading;
  }
  function attach(card, product) {
    if (!card || card.querySelector(".pm-product__buy")) return;
    var link = card.querySelector("a.pm-card__link, a.pm-product__link");
    if (!link) return;
    var form = document.createElement("form");
    form.className = "pm-product__buy cart";
    form.method = "post";
    form.action = link.href;
    form.innerHTML =
      '<div class="pm-product__stepper">' +
      '<button type="button" data-pm-qty-step="-1" aria-label="کم کردن">−</button>' +
      '<input class="qty" type="number" name="quantity" value="1" min="1" inputmode="numeric" aria-label="تعداد">' +
      '<button type="button" data-pm-qty-step="1" aria-label="زیاد کردن">+</button>' +
      "</div>" +
      '<button type="submit" class="pm-product__add single_add_to_cart_button" name="add-to-cart" value="' +
      String(product.id) +
      '">افزودن به سبد خرید</button>';
    var badge = card.querySelector(".pm-card__badge");
    if (badge) card.insertBefore(form, badge);
    else link.insertAdjacentElement("afterend", form);
  }
  function mount(card) {
    if (!card || card.dataset.pmBuyReady || card.querySelector(".pm-product__buy")) return;
    if (!card.classList.contains("pm-card")) return;
    var link = card.querySelector("a.pm-card__link");
    if (!link) return;
    card.dataset.pmBuyReady = "1";
    loadCatalog().then(function (map) {
      var product = map[pathOf(link.href)];
      if (!product || product.type !== "simple" || product.is_in_stock === false || product.is_purchasable === false) return;
      attach(card, product);
    });
  }
  function scan() {
    document.querySelectorAll(".pm-card").forEach(mount);
  }
  new MutationObserver(scan).observe(document.documentElement, { childList: true, subtree: true });
  scan();
})();
</script>
		<?php
	},
	20
);
