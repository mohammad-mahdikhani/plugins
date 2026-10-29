(function () {
  var header = document.querySelector(".pm-header");
  if (!header) {
    header = document.querySelector("header.e-flexbox");
  }
  if (!header) return;

  header.classList.add("pm-header");

  var threshold = 24;

  function onScroll() {
    var scrolled = window.scrollY > threshold;
    header.classList.toggle("is-scrolled", scrolled);
    document.documentElement.classList.toggle("pm-scrolled", scrolled);
  }

  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });
})();
