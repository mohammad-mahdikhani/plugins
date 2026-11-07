(function () {
  var drawer = document.querySelector("[data-pm-drawer]");
  if (!drawer) return;

  var openBtns = document.querySelectorAll("[data-pm-drawer-open]");
  var closeBtns = drawer.querySelectorAll("[data-pm-drawer-close]");
  var backdrop = drawer.querySelector(".pm-drawer__backdrop");

  function setOpen(open) {
    drawer.hidden = !open;
    drawer.classList.toggle("is-open", open);
    document.documentElement.classList.toggle("pm-drawer-open", open);
    openBtns.forEach(function (btn) {
      btn.setAttribute("aria-expanded", open ? "true" : "false");
    });
  }

  openBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      setOpen(true);
    });
  });

  closeBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      setOpen(false);
    });
  });

  if (backdrop) {
    backdrop.addEventListener("click", function () {
      setOpen(false);
    });
  }

  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") setOpen(false);
  });
})();

(function () {
  if (!document.querySelector(".pm-dock")) return;
  function syncDockPad() {
    var mobile = window.matchMedia("(max-width: 900px)").matches;
    document.documentElement.classList.toggle("pm-has-dock", mobile);
  }
  syncDockPad();
  window.matchMedia("(max-width: 900px)").addEventListener("change", syncDockPad);
})();
