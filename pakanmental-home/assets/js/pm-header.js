(function () {
  var header = document.querySelector(".pm-header");
  if (!header) return;

  var threshold = 24;

  function onScroll() {
    var scrolled = window.scrollY > threshold;
    header.classList.toggle("is-scrolled", scrolled);
    document.documentElement.classList.toggle("pm-scrolled", scrolled);
  }

  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });

  if (document.querySelector(".pm-site--has-hero")) {
    document.documentElement.classList.add("pm-site--has-hero");
  }
})();
