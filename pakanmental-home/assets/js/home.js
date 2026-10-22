(function () {
  var root = document.querySelector(".pm-home");
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

    var dot = e.target.closest("[data-pm-hero-dot]");
    if (dot) {
      var hero = root.querySelector(".pm-hero__track");
      if (!hero) return;
      var index = parseInt(dot.getAttribute("data-pm-hero-dot"), 10) || 0;
      hero.scrollTo({ left: hero.clientWidth * index, behavior: reduced ? "auto" : "smooth" });
      syncHeroDots(index);
    }
  });

  var heroTrack = root.querySelector(".pm-hero__track");
  if (heroTrack) {
    heroTrack.addEventListener(
      "scroll",
      function () {
        var i = Math.round(heroTrack.scrollLeft / heroTrack.clientWidth);
        syncHeroDots(i);
      },
      { passive: true }
    );

    if (!reduced) {
      var slide = 0;
      setInterval(function () {
        slide = (slide + 1) % 3;
        heroTrack.scrollTo({ left: heroTrack.clientWidth * slide, behavior: "smooth" });
        syncHeroDots(slide);
      }, 7000);
    }
  }

  function syncHeroDots(active) {
    root.querySelectorAll("[data-pm-hero-dot]").forEach(function (btn) {
      var on = parseInt(btn.getAttribute("data-pm-hero-dot"), 10) === active;
      btn.setAttribute("aria-selected", on ? "true" : "false");
    });
  }
})();
