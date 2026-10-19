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

	var header = document.querySelector(".ebh-header");
	var burger = document.querySelector("[data-mega-toggle]");
	if (burger && header) {
		burger.addEventListener("click", function () {
			header.classList.toggle("is-open");
		});
	}

	document.querySelectorAll(".ebh-mega__trigger").forEach(function (trigger) {
		trigger.addEventListener("click", function (event) {
			if (window.matchMedia("(max-width: 1024px)").matches) {
				event.preventDefault();
				trigger.parentElement.classList.toggle("is-open");
			}
		});
	});

	document.querySelectorAll("[data-slider]").forEach(function (slider) {
		var slides = Array.prototype.slice.call(slider.querySelectorAll(".ebh-slide"));
		if (!slides.length) return;
		var index = 0;
		var dotsWrap = slider.querySelector("[data-slider-dots]");

		function go(i) {
			index = (i + slides.length) % slides.length;
			slides.forEach(function (slide, n) {
				slide.classList.toggle("is-active", n === index);
			});
			if (dotsWrap) {
				Array.prototype.forEach.call(dotsWrap.children, function (dot, n) {
					dot.classList.toggle("is-active", n === index);
				});
			}
		}

		if (dotsWrap) {
			slides.forEach(function (_, n) {
				var dot = document.createElement("button");
				dot.type = "button";
				dot.setAttribute("aria-label", "اسلاید " + (n + 1));
				if (n === 0) dot.className = "is-active";
				dot.addEventListener("click", function () {
					go(n);
				});
				dotsWrap.appendChild(dot);
			});
		}

		slider.querySelectorAll("[data-slide]").forEach(function (btn) {
			btn.addEventListener("click", function () {
				go(index + (btn.getAttribute("data-slide") === "next" ? 1 : -1));
			});
		});

		setInterval(function () {
			go(index + 1);
		}, 7000);
	});
})();
