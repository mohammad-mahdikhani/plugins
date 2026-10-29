(function () {
  "use strict";

  var cfg = window.pmCart || {};
  var storeRoot = (cfg.storeApi || "/wp-json/wc/store/v1").replace(/\/$/, "");
  var cartUrl = cfg.cartUrl || "/";
  var checkoutUrl = cfg.checkoutUrl || "/checkout/";
  var nonce = "";

  var mini = document.querySelector("[data-pm-mini-cart]");
  if (!mini) return;

  var bodyEl = mini.querySelector("[data-pm-mini-cart-body]");
  var subtotalEl = mini.querySelector("[data-pm-mini-cart-subtotal]");
  var openEls = document.querySelectorAll("[data-pm-cart-open], .pm-cart[data-pm-cart-open], a.pm-cart");
  var countEls = document.querySelectorAll("[data-pm-cart-count]");
  var busy = false;

  function faNum(n) {
    try {
      return Number(n).toLocaleString("fa-IR");
    } catch (e) {
      return String(n);
    }
  }

  function formatMoney(amountStr, cart) {
    var totals = cart && cart.totals ? cart.totals : {};
    var sym = totals.currency_symbol || "ریال";
    var minor = typeof totals.currency_minor_unit === "number" ? totals.currency_minor_unit : 0;
    var n = parseFloat(amountStr || "0", 10);
    if (minor > 0) n = n / Math.pow(10, minor);
    return faNum(Math.round(n)) + " " + sym;
  }

  function captureNonce(res) {
    var h = res.headers.get("Nonce");
    if (h) nonce = h;
  }

  function storeFetch(path, options) {
    options = options || {};
    var headers = options.headers || {};
    headers["Content-Type"] = "application/json";
    if (nonce) headers.Nonce = nonce;
    return fetch(storeRoot + path, {
      method: options.method || "GET",
      credentials: "same-origin",
      headers: headers,
      body: options.body ? JSON.stringify(options.body) : undefined,
    }).then(function (res) {
      captureNonce(res);
      if (!res.ok) {
        return res.json().catch(function () {
          return {};
        }).then(function (err) {
          var msg = (err && err.message) || "cart_error";
          throw new Error(msg);
        });
      }
      return res.json();
    });
  }

  function setCount(n) {
    var num = parseInt(n, 10) || 0;
    countEls.forEach(function (el) {
      if (!num) {
        el.textContent = "";
        el.hidden = true;
        return;
      }
      el.textContent = faNum(num);
      el.hidden = false;
    });
  }

  function setOpen(open) {
    mini.hidden = !open;
    mini.classList.toggle("is-open", open);
    document.documentElement.classList.toggle("pm-mini-cart-open", open);
    if (open) refreshCart(true);
  }

  function lineImage(item) {
    if (item.images && item.images[0] && item.images[0].thumbnail) {
      return item.images[0].thumbnail;
    }
    if (item.images && item.images[0] && item.images[0].src) {
      return item.images[0].src;
    }
    return "";
  }

  function renderCart(cart) {
    if (!bodyEl) return;
    var items = cart.items || [];
    setCount(items.reduce(function (sum, it) {
      return sum + (parseInt(it.quantity, 10) || 0);
    }, 0));

    if (!items.length) {
      bodyEl.innerHTML =
        '<div class="pm-mini-cart__empty">' +
        "<p>سبد خرید شما خالی است.</p>" +
        '<a class="pm-btn pm-btn--primary pm-mini-cart__shop" href="' +
        (cfg.shopUrl || "/") +
        '">رفتن به فروشگاه</a></div>';
      if (subtotalEl) subtotalEl.textContent = formatMoney("0", cart);
      return;
    }

    var html = '<ul class="pm-mini-cart__list">';
    items.forEach(function (item) {
      var img = lineImage(item);
      var price = item.totals && item.totals.line_subtotal ? item.totals.line_subtotal : item.prices && item.prices.price;
      html +=
        '<li class="pm-mini-cart__line" data-key="' +
        item.key +
        '">' +
        (img ? '<img class="pm-mini-cart__thumb" src="' + img + '" alt="" loading="lazy" />' : '<span class="pm-mini-cart__thumb pm-mini-cart__thumb--empty"></span>') +
        '<div class="pm-mini-cart__meta">' +
        '<span class="pm-mini-cart__name">' +
        item.name +
        "</span>" +
        '<span class="pm-mini-cart__price">' +
        formatMoney(price, cart) +
        "</span>" +
        '<div class="pm-mini-cart__qty">' +
        '<button type="button" class="pm-mini-cart__qty-btn" data-pm-qty-delta="-1" aria-label="کم کردن">−</button>' +
        '<span class="pm-mini-cart__qty-val">' +
        faNum(item.quantity) +
        "</span>" +
        '<button type="button" class="pm-mini-cart__qty-btn" data-pm-qty-delta="1" aria-label="زیاد کردن">+</button>' +
        '<button type="button" class="pm-mini-cart__remove" data-pm-remove aria-label="حذف">×</button>' +
        "</div></div></li>";
    });
    html += "</ul>";
    bodyEl.innerHTML = html;

    if (subtotalEl) {
      var sub = cart.totals && cart.totals.total_items ? cart.totals.total_items : "0";
      subtotalEl.textContent = formatMoney(sub, cart);
    }
  }

  function refreshCart(silent) {
    if (busy) return Promise.resolve();
    busy = true;
    if (!silent && bodyEl) {
      bodyEl.innerHTML = '<p class="pm-mini-cart__loading">در حال بارگذاری…</p>';
    }
    return storeFetch("/cart")
      .then(function (cart) {
        renderCart(cart);
        busy = false;
        return cart;
      })
      .catch(function () {
        busy = false;
        if (bodyEl) {
          bodyEl.innerHTML =
            '<p class="pm-mini-cart__error">سبد را نتوانستیم بارگذاری کنیم. ' +
            '<a href="' +
            cartUrl +
            '">صفحه سبد</a></p>';
        }
      });
  }

  function updateQty(key, quantity) {
    if (quantity < 1) return removeLine(key);
    busy = true;
    return storeFetch("/cart/update-item", {
      method: "POST",
      body: { key: key, quantity: quantity },
    })
      .then(function (cart) {
        renderCart(cart);
        busy = false;
        document.dispatchEvent(new CustomEvent("pm_cart_updated", { detail: cart }));
      })
      .catch(function () {
        busy = false;
        refreshCart(true);
      });
  }

  function removeLine(key) {
    busy = true;
    return storeFetch("/cart/remove-item", {
      method: "POST",
      body: { key: key },
    })
      .then(function (cart) {
        renderCart(cart);
        busy = false;
        document.dispatchEvent(new CustomEvent("pm_cart_updated", { detail: cart }));
      })
      .catch(function () {
        busy = false;
        refreshCart(true);
      });
  }

  function addProduct(productId, quantity) {
    busy = true;
    return storeFetch("/cart/add-item", {
      method: "POST",
      body: { id: productId, quantity: quantity || 1 },
    })
      .then(function (cart) {
        renderCart(cart);
        busy = false;
        setOpen(true);
        document.dispatchEvent(new CustomEvent("pm_cart_updated", { detail: cart }));
        return cart;
      })
      .catch(function () {
        busy = false;
        throw new Error("add_failed");
      });
  }

  openEls.forEach(function (el) {
    el.addEventListener("click", function (e) {
      if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
      e.preventDefault();
      setOpen(true);
    });
  });

  mini.querySelectorAll("[data-pm-cart-close]").forEach(function (el) {
    el.addEventListener("click", function () {
      setOpen(false);
    });
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && mini.classList.contains("is-open")) setOpen(false);
  });

  if (bodyEl) {
    bodyEl.addEventListener("click", function (e) {
      var line = e.target.closest(".pm-mini-cart__line");
      if (!line) return;
      var key = line.getAttribute("data-key");
      if (e.target.closest("[data-pm-remove]")) {
        e.preventDefault();
        removeLine(key);
        return;
      }
      var deltaBtn = e.target.closest("[data-pm-qty-delta]");
      if (deltaBtn) {
        e.preventDefault();
        var delta = parseInt(deltaBtn.getAttribute("data-pm-qty-delta"), 10) || 0;
        var valEl = line.querySelector(".pm-mini-cart__qty-val");
        var q = parseInt((valEl && valEl.textContent.replace(/[^\d]/g, "")) || "1", 10);
        updateQty(key, q + delta);
      }
    });
  }

  document.body.addEventListener("submit", function (e) {
    var form = e.target;
    if (!form || form.tagName !== "FORM") return;
    if (!form.classList.contains("cart")) return;
    if (!document.querySelector(".pm-site") && !document.querySelector(".pm-header")) return;
    var addInput = form.querySelector('[name="add-to-cart"]');
    var btn = form.querySelector(".single_add_to_cart_button");
    var productId = addInput ? addInput.value : btn ? btn.value : null;
    if (!productId) return;
    e.preventDefault();
    var qtyInput = form.querySelector("input.qty");
    var qty = qtyInput ? parseInt(qtyInput.value, 10) || 1 : 1;
    addProduct(parseInt(productId, 10), qty).catch(function () {
      form.submit();
    });
  });

  document.body.addEventListener("click", function (e) {
    var btn = e.target.closest(".add_to_cart_button, .ajax_add_to_cart");
    if (!btn || btn.classList.contains("product_type_variable")) return;
    var productId = btn.getAttribute("data-product_id") || btn.value;
    if (!productId) return;
    if (btn.tagName === "A" && btn.href && btn.href.indexOf("add-to-cart") === -1 && !btn.classList.contains("ajax_add_to_cart")) {
      return;
    }
    e.preventDefault();
    addProduct(parseInt(productId, 10), 1).catch(function () {
      if (btn.href) window.location.href = btn.href;
    });
  });

  refreshCart(true);

  if (window.jQuery) {
    window.jQuery(document.body).on("added_to_cart removed_from_cart", function () {
      refreshCart(true);
    });
  }

  window.pmCartRefresh = refreshCart;
  window.pmCartAdd = addProduct;
})();
