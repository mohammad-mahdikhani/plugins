(function () {
  var root = document.querySelector("[data-pm-mega]");
  if (!root) return;

  var trigger = root.querySelector(".pm-mega__trigger");
  var panel = root.querySelector(".pm-mega__panel");
  var grid = root.querySelector("[data-pm-mega-grid]");
  var shopUrl = root.getAttribute("data-shop-url") || "/";
  if (!trigger || !panel || !grid) return;

  var closeTimer = null;

  function setOpen(open) {
    root.classList.toggle("is-open", open);
    trigger.setAttribute("aria-expanded", open ? "true" : "false");
    panel.hidden = !open;
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
        return (a.menu_order || 0) - (b.menu_order || 0) || a.name.localeCompare(b.name, "fa");
      });
    });
    return byParent;
  }

  function renderMenu(items) {
    var byParent = buildTree(items);
    var roots = byParent[0] || [];
    var skipSlugs = ["دسته-بندی-نشده", "%d8%af%d8%b3%d8%aa%d9%87-%d8%a8%d9%86%d8%af%d9%87-%d9%86%d8%b4%d8%af%d9%87"];
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
    if (window.matchMedia("(max-width: 900px)").matches) {
      e.preventDefault();
      setOpen(!root.classList.contains("is-open"));
    }
  });

  root.addEventListener("mouseenter", function () {
    if (!window.matchMedia("(max-width: 900px)").matches) {
      clearTimeout(closeTimer);
      setOpen(true);
    }
  });

  root.addEventListener("mouseleave", function () {
    if (!window.matchMedia("(max-width: 900px)").matches) {
      closeTimer = setTimeout(function () {
        setOpen(false);
      }, 220);
    }
  });

  trigger.addEventListener("focus", function () {
    if (!window.matchMedia("(max-width: 900px)").matches) {
      setOpen(true);
    }
  });

  document.addEventListener("click", function (e) {
    if (!root.contains(e.target)) {
      setOpen(false);
    }
  });

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") setOpen(false);
  });
})();
