(function () {
	function scrollCarousel(name, direction) {
		var track = document.querySelector('[data-carousel-track="' + name + '"]');
		if (!track) return;
		var amount = Math.max(track.clientWidth * 0.75, 280);
		track.scrollBy({ left: direction * amount * -1, behavior: "smooth" });
	}

	document.querySelectorAll("[data-carousel]").forEach(function (nav) {
		var name = nav.getAttribute("data-carousel");
		nav.querySelectorAll("button[data-dir]").forEach(function (btn) {
			btn.addEventListener("click", function () {
				var dir = btn.getAttribute("data-dir") === "next" ? 1 : -1;
				scrollCarousel(name, dir);
			});
		});
	});
})();
