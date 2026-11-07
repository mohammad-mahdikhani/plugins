(function () {
  "use strict";

  var cfg = window.pmCart || {};
  var storeRoot = (cfg.storeApi || "/wp-json/wc/store/v1").replace(/\/$/, "");

  function escapeHtml(str) {
    return String(str || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function imageSrc(product) {
    var images = product.images || [];
    if (!images.length) return "";
    return images[0].thumbnail || images[0].src || "";
  }

  function priceHtml(product) {
    return product.price_html || "";
  }

  function shopCard(product) {
    var src = imageSrc(product);
    var thumb = src
      ? '<img class="pm-product__thumb" src="' +
        escapeHtml(src) +
        '" alt="' +
        escapeHtml(product.name) +
        '" loading="lazy" width="300" height="300" />'
      : '<div class="pm-product__thumb" aria-hidden="true"></div>';
    return (
      '<article class="pm-product"><a class="pm-product__link" href="' +
      escapeHtml(product.permalink) +
      '">' +
      thumb +
      '<h3 class="pm-product__title">' +
      escapeHtml(product.name) +
      "</h3>" +
      '<p class="pm-product__price">' +
      priceHtml(product) +
      "</p></a></article>"
    );
  }

  function homeCard(product, badge) {
    var src = imageSrc(product);
    var media = src
      ? '<img class="pm-card__img" src="' +
        escapeHtml(src) +
        '" alt="' +
        escapeHtml(product.name) +
        '" loading="lazy" width="300" height="300" />'
      : '<div class="pm-card__ph" aria-hidden="true"></div>';
    var badgeHtml = badge ? '<span class="pm-card__badge">ترند</span>' : "";
    return (
      '<article class="pm-card"><a class="pm-card__link" href="' +
      escapeHtml(product.permalink) +
      '">' +
      media +
      '<h3 class="pm-card__title">' +
      escapeHtml(product.name) +
      "</h3>" +
      '<p class="pm-card__price">' +
      priceHtml(product) +
      "</p></a>" +
      badgeHtml +
      "</article>"
    );
  }

  function fetchProducts(params) {
    var qs = new URLSearchParams(params);
    return fetch(storeRoot + "/products?" + qs.toString(), {
      credentials: "same-origin",
      headers: { Accept: "application/json" },
    }).then(function (res) {
      if (!res.ok) throw new Error("catalog");
      return res.json();
    });
  }

  function fillGrid(el) {
    var perPage = el.getAttribute("data-per-page") || "12";
    var orderby = el.getAttribute("data-orderby") || "date";
    var category = el.getAttribute("data-category") || "";
    var params = { per_page: perPage, orderby: orderby, order: "desc" };
    if (category) params.category = category;
    fetchProducts(params)
      .then(function (list) {
        if (!list || !list.length) return;
        el.innerHTML = list.map(shopCard).join("");
      })
      .catch(function () {});
  }

  function fillRow(el) {
    var section = el.closest(".pm-section");
    var perPage = el.getAttribute("data-per-page") || "8";
    var orderby = el.getAttribute("data-orderby") || "date";
    var category = el.getAttribute("data-category") || "";
    var badge = el.getAttribute("data-badge") === "1";
    var params = { per_page: perPage, orderby: orderby, order: "desc" };
    if (category) params.category = category;
    fetchProducts(params)
      .then(function (list) {
        if (!list || !list.length) {
          if (category && section) section.setAttribute("hidden", "hidden");
          return;
        }
        if (section) section.removeAttribute("hidden");
        el.innerHTML = list
          .map(function (product, index) {
            return homeCard(product, badge && index < 2);
          })
          .join("");
      })
      .catch(function () {});
  }

  document.querySelectorAll("[data-pm-product-grid]").forEach(fillGrid);
  document.querySelectorAll("[data-pm-product-row]").forEach(fillRow);
})();
