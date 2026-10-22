(function () {
  var root = document.querySelector(".pm-shop");
  if (!root) return;

  var panel = root.querySelector(".pm-filters");
  var openBtn = root.querySelector("[data-pm-filter-open]");
  var closeBtn = root.querySelector("[data-pm-filter-close]");
  var backdrop = root.querySelector(".pm-filters-backdrop");

  function openFilters() {
    if (!panel) return;
    panel.classList.add("is-open");
    document.body.classList.add("pm-filters-open");
  }

  function closeFilters() {
    if (!panel) return;
    panel.classList.remove("is-open");
    document.body.classList.remove("pm-filters-open");
  }

  if (openBtn) openBtn.addEventListener("click", openFilters);
  if (closeBtn) closeBtn.addEventListener("click", closeFilters);
  if (backdrop) backdrop.addEventListener("click", closeFilters);

  var priceMin = root.querySelector("#pm-price-min");
  var priceMax = root.querySelector("#pm-price-max");
  var priceRange = root.querySelector("#pm-price-range");
  var priceOut = root.querySelector("[data-pm-price-out]");

  function syncPrice() {
    if (!priceMin || !priceMax || !priceOut) return;
    var min = parseInt(priceMin.value || "0", 10);
    var max = parseInt(priceMax.value || "0", 10);
    if (priceRange) priceRange.value = max;
    priceOut.textContent =
      min.toLocaleString("fa-IR") + " – " + max.toLocaleString("fa-IR") + " تومان";
  }

  [priceMin, priceMax, priceRange].forEach(function (el) {
    if (!el) return;
    el.addEventListener("input", syncPrice);
  });
  syncPrice();

  var reset = root.querySelector("[data-pm-filter-reset]");
  if (reset) {
    reset.addEventListener("click", function () {
      var form = root.querySelector("#pm-shop-filters");
      if (form) form.reset();
      syncPrice();
    });
  }
})();
