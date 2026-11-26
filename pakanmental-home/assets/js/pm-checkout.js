(function ($) {
  "use strict";

  var cfg = window.pmCheckout || {};

  function update(wrap, qty) {
    var key = wrap.getAttribute("data-key");
    if (!key || wrap.classList.contains("is-busy")) return;
    wrap.classList.add("is-busy");
    $.post("/?wc-ajax=pm_checkout_item", {
      key: key,
      qty: qty,
      nonce: cfg.nonce || "",
    })
      .done(function (res) {
        if (res && res.data && res.data.redirect) {
          window.location.href = res.data.redirect;
          return;
        }
        $(document.body).trigger("update_checkout");
      })
      .always(function () {
        wrap.classList.remove("is-busy");
      });
  }

  $(document).on("click", ".pm-co-qty [data-pm-qty], .pm-co-qty [data-pm-remove]", function (event) {
    event.preventDefault();
    var btn = event.currentTarget;
    var wrap = btn.closest(".pm-co-qty");
    var input = wrap.querySelector(".pm-co-qty__input");
    if (!wrap || !input) return;
    if (btn.hasAttribute("data-pm-remove")) {
      update(wrap, 0);
      return;
    }
    var qty = parseInt(input.value, 10) || 1;
    qty += parseInt(btn.getAttribute("data-pm-qty"), 10) || 0;
    var min = parseInt(input.getAttribute("min") || "1", 10);
    var max = input.getAttribute("max");
    if (qty < min) qty = min;
    if (max && qty > parseInt(max, 10)) qty = parseInt(max, 10);
    input.value = String(qty);
    update(wrap, qty);
  });
})(jQuery);
