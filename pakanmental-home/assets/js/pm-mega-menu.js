(function () {
  var root = document.querySelector("[data-pm-mega]");
  if (!root) return;

  var trigger = root.querySelector(".pm-mega__trigger");
  var panel = root.querySelector(".pm-mega__panel");
  var grid = root.querySelector("[data-pm-mega-grid]");
  var shopUrl = root.getAttribute("data-shop-url") || "/";
  if (!trigger || !panel || !grid) return;

  var closeTimer = null;
  var desktopMq = window.matchMedia("(min-width: 901px)");

  function isDesktop() {
    return desktopMq.matches;
  }

  function positionPanel() {
    var bar = document.querySelector(".pm-header__bar");
    if (!bar || !isDesktop()) {
      document.documentElement.style.removeProperty("--pm-mega-top");
      return;
    }
    var rect = bar.getBoundingClientRect();
    document.documentElement.style.setProperty(
      "--pm-mega-top",
      Math.round(rect.bottom + 10) + "px"
    );
  }

  function setOpen(open) {
    root.classList.toggle("is-open", open);
    trigger.setAttribute("aria-expanded", open ? "true" : "false");
    if (open) {
      panel.removeAttribute("hidden");
      positionPanel();
    } else {
      panel.setAttribute("hidden", "");
    }
  }

  function buildTree(items) {
    var byParent = {};
    items.forEach(function (item) {
      var pid = item.parent || 0;
      if (!byParent[pid]) byParent[pid] = [];
      byParent[pid].push(item);
    });
    Object.keys(byParent).forEach(function (key) {
      byParent[key].sort(function (a, b) {
        return (
          (a.menu_order || 0) - (b.menu_order || 0) ||
          a.name.localeCompare(b.name, "fa")
        );
      });
    });
    return byParent;
  }

  function renderMenu(items) {
    var byParent = buildTree(items);
    var roots = byParent[0] || [];
    var skipSlugs = [
      "دسته-بندی-نشده",
      "%d8%af%d8%b3%d8%aa%d9%87-%d8%a8%d9%86%d8%af%d9%87-%d9%86%d8%b4%d8%af%d9%87",
    ];
    roots = roots.filter(function (r) {
      return skipSlugs.indexOf(r.slug) === -1 && r.name.indexOf("نشده") === -1;
    });

    if (!roots.length) {
      grid.innerHTML =
        '<p class="pm-mega__empty">دسته‌بندی محصولات به‌زودی اینجا نمایش داده می‌شود.</p>';
      return;
    }

    var html = "";
    roots.forEach(function (rootCat) {
      var kids = byParent[rootCat.id] || [];
      html += '<div class="pm-mega__col">';
      html +=
        '<a class="pm-mega__parent" href="' +
        rootCat.link +
        '">' +
        rootCat.name +
        "</a>";
      if (kids.length) {
        html += '<ul class="pm-mega__sub">';
        kids.forEach(function (child) {
          html +=
            '<li><a href="' + child.link + '">' + child.name + "</a></li>";
        });
        html += "</ul>";
      }
      html += "</div>";
    });
    grid.innerHTML = html;
  }

  fetch("/wp-json/wp/v2/product_cat?per_page=100&hide_empty=false", {
    credentials: "same-origin",
  })
    .then(function (res) {
      return res.ok ? res.json() : [];
    })
    .then(renderMenu)
    .catch(function () {
      grid.innerHTML =
        '<p class="pm-mega__empty">بارگذاری دسته‌ها ممکن نشد. <a href="' +
        shopUrl +
        '">رفتن به فروشگاه</a></p>';
    });

  trigger.addEventListener("click", function (e) {
    if (isDesktop()) return;
    e.preventDefault();
    e.stopPropagation();
    setOpen(!root.classList.contains("is-open"));
  });

  root.addEventListener("mouseenter", function () {
    if (!isDesktop()) return;
    clearTimeout(closeTimer);
    setOpen(true);
  });

  root.addEventListener("mouseleave", function () {
    if (!isDesktop()) return;
    closeTimer = setTimeout(function () {
      setOpen(false);
    }, 280);
  });

  panel.addEventListener("mouseenter", function () {
    if (!isDesktop()) return;
    clearTimeout(closeTimer);
  });

  panel.addEventListener("click", function (e) {
    e.stopPropagation();
  });

  root.querySelectorAll("[data-pm-mega-close]").forEach(function (btn) {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      setOpen(false);
    });
  });

  document.addEventListener("click", function (e) {
    if (!root.contains(e.target)) setOpen(false);
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") setOpen(false);
  });

  window.addEventListener("resize", positionPanel);
  window.addEventListener("scroll", positionPanel, { passive: true });
  desktopMq.addEventListener("change", function () {
    setOpen(false);
    positionPanel();
  });
  positionPanel();
})();
