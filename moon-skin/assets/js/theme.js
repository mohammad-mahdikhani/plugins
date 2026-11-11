(function () {
  var cfg = window.moonSkin || {};

  function paintNight() {
    var on = document.documentElement.classList.contains("is-night");
    document.querySelectorAll(".night").forEach(function (b) {
      b.setAttribute("aria-pressed", on ? "true" : "false");
      var s = b.querySelector("span");
      if (s) s.textContent = on ? (cfg.i18n && cfg.i18n.day) || "حالت روز" : (cfg.i18n && cfg.i18n.night) || "حالت شب";
    });
  }
  document.addEventListener("click", function (e) {
    var b = e.target.closest(".night");
    if (!b) return;
    e.preventDefault();
    var on = !document.documentElement.classList.contains("is-night");
    document.documentElement.classList.toggle("is-night", on);
    try { localStorage.setItem("moon_night", on ? "1" : "0"); } catch (err) {}
    paintNight();
  });
  paintNight();

  var mega = document.querySelector(".moon-mega");
  var prod = document.querySelector(".moon-prod");
  var panel = document.querySelector(".moon-panel");
  if (mega && prod) {
    var hold;
    function arm() { clearTimeout(hold); mega.classList.add("is-open"); }
    function fade() {
      if (window.matchMedia("(max-width:860px)").matches) return;
      clearTimeout(hold);
      hold = setTimeout(function () { mega.classList.remove("is-open"); }, 700);
    }
    mega.addEventListener("mouseenter", function () { if (!window.matchMedia("(max-width:860px)").matches) arm(); });
    mega.addEventListener("mouseleave", fade);
    if (panel) {
      panel.addEventListener("mouseenter", function () { if (!window.matchMedia("(max-width:860px)").matches) arm(); });
      panel.addEventListener("mouseleave", fade);
    }
    prod.addEventListener("click", function (e) {
      if (window.matchMedia("(max-width:860px)").matches) {
        e.preventDefault();
        mega.classList.toggle("is-open");
      }
    });
    document.addEventListener("click", function (e) {
      if (!mega.classList.contains("is-open")) return;
      if (e.target.closest(".moon-mega") || e.target.closest(".moon-panel")) return;
      clearTimeout(hold);
      mega.classList.remove("is-open");
    });
  }

  var release = document.querySelector(".moon-release");
  if (release) {
    var id = release.getAttribute("data-id") || "";
    try {
      if (id && localStorage.getItem("moon_release_seen") === id) release.remove();
    } catch (err) {}
    var close = release.querySelector(".moon-release-x");
    if (close) close.addEventListener("click", function () {
      try { localStorage.setItem("moon_release_seen", id); } catch (err) {}
      release.remove();
    });
  }

  function bumpQty(input, dir) {
    var min = Number(input.getAttribute("min"));
    if (!min || min < 1) min = 1;
    var max = input.getAttribute("max");
    var n = (parseInt(input.value, 10) || min) + dir;
    if (n < min) n = min;
    if (max !== null && max !== "" && Number(max) > 0 && n > Number(max)) n = Number(max);
    input.value = String(n);
    input.dispatchEvent(new Event("change", { bubbles: true }));
  }
  function enhance(box) {
    var input = box.querySelector("input.qty");
    if (!input || input.type === "hidden" || box.querySelector(".moon-q")) return;
    box.classList.add("moon-stepper");
    var minus = document.createElement("button");
    minus.type = "button";
    minus.className = "moon-q";
    minus.setAttribute("data-dir", "-1");
    minus.textContent = "−";
    var plus = document.createElement("button");
    plus.type = "button";
    plus.className = "moon-q";
    plus.setAttribute("data-dir", "1");
    plus.textContent = "+";
    box.insertBefore(minus, input);
    input.after(plus);
  }
  document.querySelectorAll(".quantity").forEach(enhance);
  document.addEventListener("click", function (e) {
    var btn = e.target.closest(".moon-q");
    if (!btn) return;
    var input = btn.parentNode.querySelector("input.qty");
    if (!input) return;
    e.preventDefault();
    bumpQty(input, Number(btn.getAttribute("data-dir")) || 0);
  });

  if (document.body.classList.contains("woocommerce-checkout")) {
    if (window.jQuery && window.jQuery.fn && !window.jQuery.fn.selectWoo) {
      window.jQuery.fn.selectWoo = function () { return this; };
    }
    document.addEventListener("change", function (e) {
      if (!e.target || !e.target.matches || !e.target.matches("select[id$=_state]")) return;
      if (window.jQuery) window.jQuery(e.target).trigger("select2:select");
    });

    document.addEventListener("click", function (e) {
      var hit = e.target.closest(".woocommerce-form-coupon-toggle, a.showcoupon");
      if (!hit) return;
      e.preventDefault();
      var form = document.getElementById("woocommerce-checkout-form-coupon") || document.querySelector("form.checkout_coupon");
      if (!form) return;
      var open = form.style.display === "none" || getComputedStyle(form).display === "none";
      form.style.display = open ? "block" : "none";
      var link = document.querySelector("a.showcoupon");
      if (link) link.setAttribute("aria-expanded", open ? "true" : "false");
      if (open) {
        var field = form.querySelector("[name=coupon_code]");
        if (field) field.focus();
      }
    }, true);
    document.addEventListener("submit", function (e) {
      var form = e.target;
      if (!form || !form.classList || !form.classList.contains("checkout_coupon")) return;
      if (!window.wc_checkout_params) return;
      e.preventDefault();
      var code = form.querySelector("[name=coupon_code]");
      var btn = form.querySelector("[type=submit]");
      if (btn) btn.disabled = true;
      var body = new URLSearchParams();
      body.set("coupon_code", code ? code.value : "");
      body.set("security", wc_checkout_params.apply_coupon_nonce || "");
      var url = String(wc_checkout_params.wc_ajax_url || cfg.ajax || "/?wc-ajax=%%endpoint%%").replace("%%endpoint%%", "apply_coupon");
      fetch(url, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: body.toString() })
        .then(function (r) { return r.text(); })
        .then(function (html) {
          var prev = form.previousElementSibling;
          if (prev && (prev.classList.contains("woocommerce-error") || prev.classList.contains("woocommerce-message"))) prev.remove();
          form.insertAdjacentHTML("beforebegin", html);
          if (html.indexOf("woocommerce-error") === -1) { window.location.reload(); return; }
          if (btn) btn.disabled = false;
        })
        .catch(function () { if (btn) btn.disabled = false; HTMLFormElement.prototype.submit.call(form); });
    }, true);
    document.addEventListener("click", function (e) {
      var link = e.target.closest("a.woocommerce-remove-coupon");
      if (!link || !window.wc_checkout_params) return;
      e.preventDefault();
      var body = new URLSearchParams();
      body.set("coupon", link.getAttribute("data-coupon") || "");
      body.set("security", wc_checkout_params.remove_coupon_nonce || "");
      var url = String(wc_checkout_params.wc_ajax_url || "/?wc-ajax=%%endpoint%%").replace("%%endpoint%%", "remove_coupon");
      fetch(url, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded" }, body: body.toString() })
        .then(function (r) { return r.text(); })
        .then(function (html) { if (html.indexOf("woocommerce-error") === -1) window.location.reload(); });
    }, true);

    var form = document.querySelector("form.checkout");
    var texts = cfg.i18n || {};
    if (String(cfg.steps) !== "0" && form && document.getElementById("customer_details")) {
      var bar = document.createElement("ol");
      bar.className = "moon-steps";
      [["1", texts.step1 || "مشخصات"], ["2", texts.step2 || "ارسال"], ["3", texts.step3 || "پرداخت"]].forEach(function (pair) {
        var li = document.createElement("li");
        li.setAttribute("data-go", pair[0]);
        var b = document.createElement("b");
        b.textContent = pair[0];
        var s = document.createElement("span");
        s.textContent = pair[1];
        li.appendChild(b);
        li.appendChild(s);
        bar.appendChild(li);
      });
      form.insertBefore(bar, form.firstChild);
      var lead = document.createElement("div");
      lead.className = "moon-ship-lead";
      function syncLead() {
        var input = document.querySelector("#shipping_method input:checked");
        var label = input ? document.querySelector('label[for="' + input.id + '"]') : null;
        if (!label) label = document.querySelector("#order_review tr.shipping td");
        var text = label ? (label.textContent || "").replace(/\s+/g, " ").trim() : "";
        lead.replaceChildren();
        var title = document.createElement("b");
        title.textContent = texts.ship || "روش ارسال";
        var span = document.createElement("span");
        span.textContent = text;
        lead.appendChild(title);
        lead.appendChild(span);
      }
      syncLead();
      var review = document.getElementById("order_review");
      var anchor = document.getElementById("customer_details");
      if (review && anchor && anchor.parentNode) anchor.parentNode.insertBefore(review, anchor);
      if (review && review.parentNode) review.parentNode.insertBefore(lead, review);
      var nav = document.createElement("div");
      nav.className = "moon-step-nav";
      var back = document.createElement("button");
      back.type = "button";
      back.className = "moon-back";
      back.textContent = texts.back || "بازگشت";
      var next = document.createElement("button");
      next.type = "button";
      next.className = "moon-next";
      next.textContent = texts.next || "ادامه";
      nav.appendChild(back);
      nav.appendChild(next);
      form.appendChild(nav);
      var step = 1;
      function show(n) {
        step = n;
        document.body.classList.remove("is-step-1", "is-step-2", "is-step-3");
        form.classList.remove("is-step-1", "is-step-2", "is-step-3");
        document.body.classList.add("is-step-" + n);
        form.classList.add("is-step-" + n);
        bar.querySelectorAll("li").forEach(function (li) {
          var num = Number(li.getAttribute("data-go"));
          li.classList.toggle("is-on", num === n);
          li.classList.toggle("is-done", num < n);
        });
        nav.querySelector(".moon-back").hidden = n === 1;
        nav.querySelector(".moon-next").hidden = n === 3;
        syncLead();
      }
      nav.addEventListener("click", function (e) {
        if (e.target.closest(".moon-next") && step < 3) show(step + 1);
        else if (e.target.closest(".moon-back") && step > 1) show(step - 1);
      });
      bar.addEventListener("click", function (e) {
        var li = e.target.closest("li");
        if (!li) return;
        var n = Number(li.getAttribute("data-go"));
        if (n <= step + 1) show(n);
      });
      if (window.jQuery) {
        window.jQuery(document.body).on("updated_checkout", function () { syncLead(); });
      }
      show(1);
    }
  }

  var box = document.getElementById("moon-draw");
  if (!box || !cfg.cart) return;
  var itemsEl = document.getElementById("moon-draw-items");
  var foot = document.getElementById("moon-draw-foot");
  var nonce = "";
  function fa(n) {
    var s = String(Math.round(Number(n) || 0));
    return s.replace(/\B(?=(\d{3})+(?!\d))/g, ",").replace(/\d/g, function (d) { return "۰۱۲۳۴۵۶۷۸۹"[d]; }).replace(/,/g, "٬");
  }
  function openDraw() {
    box.hidden = false;
    requestAnimationFrame(function () { box.classList.add("on"); });
    paint();
    document.documentElement.style.overflow = "hidden";
  }
  function closeDraw() {
    box.classList.remove("on");
    setTimeout(function () { if (!box.classList.contains("on")) box.hidden = true; }, 420);
    document.documentElement.style.overflow = "";
  }
  function req(url, opt) {
    opt = opt || {};
    opt.credentials = "same-origin";
    opt.headers = Object.assign({}, opt.headers || {});
    if (nonce) opt.headers.Nonce = nonce;
    return fetch(url, opt).then(function (r) {
      var n = r.headers.get("Nonce") || r.headers.get("X-WC-Store-API-Nonce");
      if (n) nonce = n;
      return r.json().catch(function () { return {}; });
    });
  }
  function unit(item) {
    var minor = item.prices && item.prices.currency_minor_unit ? Math.pow(10, item.prices.currency_minor_unit) : 1;
    var price = item.prices && item.prices.price ? Number(item.prices.price) / minor : 0;
    return price;
  }
  function paint() {
    return req(cfg.cart).then(function (cart) {
      var items = cart.items || [];
      var count = 0;
      items.forEach(function (item) { count += Number(item.quantity) || 0; });
      document.querySelectorAll("[data-moon-count]").forEach(function (el) { el.textContent = String(count); });
      if (!items.length) {
        itemsEl.innerHTML = "<p>" + ((cfg.i18n && cfg.i18n.empty) || "سبد خرید خالی است.") + "</p>";
        foot.innerHTML = "";
        return;
      }
      itemsEl.innerHTML = items.map(function (item) {
        var img = item.images && item.images[0] ? item.images[0].thumbnail || item.images[0].src : "";
        return '<article data-key="' + item.key + '">' +
          (img ? '<img src="' + img + '" alt="">' : "") +
          "<div><b>" + (item.name || "") + "</b><span>" + fa(unit(item)) + "</span>" +
          '<div class="moon-draw-qty"><button type="button" data-draw="-1">−</button><em>' + item.quantity + '</em><button type="button" data-draw="1">+</button></div></div>' +
          '<button type="button" data-remove aria-label="حذف">×</button></article>';
      }).join("");
      var totalMinor = cart.totals && cart.totals.currency_minor_unit ? Math.pow(10, cart.totals.currency_minor_unit) : 1;
      var total = cart.totals && cart.totals.total_price ? Number(cart.totals.total_price) / totalMinor : 0;
      foot.innerHTML = "<b>" + fa(total) + "</b><a href=\"" + ((cart.checkoutUrl) || (cfg.home + "checkout/")) + "\">" + ((cfg.i18n && cfg.i18n.pay) || "تسویه حساب") + "</a>";
    });
  }
  function change(key, quantity) {
    if (quantity < 1) {
      return req(cfg.cart + "/items/" + encodeURIComponent(key), { method: "DELETE" }).then(paint);
    }
    return req(cfg.cart + "/update-item", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ key: key, quantity: quantity })
    }).then(paint);
  }
  document.addEventListener("click", function (e) {
    if (e.target.closest("[data-moon-cart]")) { e.preventDefault(); openDraw(); return; }
    if (e.target.closest("#moon-draw [data-close]")) { closeDraw(); return; }
    var add = e.target.closest("[data-add]");
    if (add) {
      e.preventDefault();
      var id = Number(add.getAttribute("data-add"));
      req(cfg.cart).then(function () {
        return req(cfg.cart + "/add-item", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id: id, quantity: 1 }) });
      }).then(openDraw);
      return;
    }
    var row = e.target.closest("#moon-draw article");
    if (!row) return;
    var key = row.getAttribute("data-key");
    var dir = e.target.closest("[data-draw]");
    if (dir) {
      var now = Number(row.querySelector("em").textContent) || 1;
      change(key, now + Number(dir.getAttribute("data-draw")));
    }
    if (e.target.closest("[data-remove]")) change(key, 0);
  });
  document.addEventListener("submit", function (e) {
    var form = e.target;
    if (!form || !form.classList || !form.classList.contains("cart")) return;
    var btn = form.querySelector("[name=add-to-cart]");
    if (!btn || form.querySelector("[name=variation_id]")) return;
    e.preventDefault();
    var qty = form.querySelector("[name=quantity]");
    var id = Number(btn.value);
    req(cfg.cart).then(function () {
      return req(cfg.cart + "/add-item", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: id, quantity: qty ? Number(qty.value) || 1 : 1 })
      });
    }).then(openDraw);
  });
  req(cfg.cart).then(paint);
})();
