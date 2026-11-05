(function () {
  var root = document.querySelector(".pm-site");
  if (!root) return;

  var reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  root.addEventListener("click", function (e) {
    var scrollBtn = e.target.closest("[data-pm-scroll]");
    if (scrollBtn) {
      var id = scrollBtn.getAttribute("data-pm-scroll");
      var track = document.getElementById(id);
      if (!track) return;
      var step = Math.min(280, track.clientWidth * 0.85);
      var dir = scrollBtn.getAttribute("data-pm-dir") === "next" ? -step : step;
      track.scrollBy({ left: dir, behavior: reduced ? "auto" : "smooth" });
      return;
    }

    if (e.target.closest("[data-pm-hero-prev]")) {
      heroStep(-1);
      return;
    }
    if (e.target.closest("[data-pm-hero-next]")) {
      heroStep(1);
      return;
    }

    var dot = e.target.closest("[data-pm-hero-dot]");
    if (dot) {
      goHero(parseInt(dot.getAttribute("data-pm-hero-dot"), 10) || 0);
    }
  });

  var slides = root.querySelectorAll(".pm-rev__slide");
  var heroIndex = 0;
  var heroTimer;

  function goHero(i) {
    if (!slides.length) return;
    heroIndex = (i + slides.length) % slides.length;
    slides.forEach(function (s, idx) {
      s.classList.toggle("is-active", idx === heroIndex);
    });
    root.querySelectorAll("[data-pm-hero-dot]").forEach(function (btn) {
      var on = parseInt(btn.getAttribute("data-pm-hero-dot"), 10) === heroIndex;
      btn.setAttribute("aria-selected", on ? "true" : "false");
    });
    resetHeroAutoplay();
  }

  function heroStep(delta) {
    goHero(heroIndex + delta);
  }

  function resetHeroAutoplay() {
    clearInterval(heroTimer);
    if (reduced || slides.length < 2) return;
    heroTimer = setInterval(function () {
      heroStep(1);
    }, 6500);
  }

  if (slides.length) {
    goHero(0);
  }
})();
