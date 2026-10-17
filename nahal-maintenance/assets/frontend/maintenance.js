(function () {
	var world = document.getElementById("nm-world");
	var card = document.getElementById("nm-card");
	if (!world && !card) {
		return;
	}

	var reduce = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
	if (reduce) {
		return;
	}

	var targetX = 0;
	var targetY = 0;
	var currentX = 0;
	var currentY = 0;
	var hovering = false;

	function onMove(event) {
		var point = event.touches && event.touches[0] ? event.touches[0] : event;
		var x = point.clientX / window.innerWidth;
		var y = point.clientY / window.innerHeight;
		targetX = (x - 0.5) * 2;
		targetY = (y - 0.5) * 2;
		hovering = true;
	}

	function onLeave() {
		targetX = 0;
		targetY = 0;
		hovering = false;
	}

	function tick() {
		currentX += (targetX - currentX) * 0.08;
		currentY += (targetY - currentY) * 0.08;

		if (world) {
			var wx = 18 - currentY * 8;
			var wy = -22 + currentX * 12;
			world.style.transform = "rotateX(" + wx.toFixed(2) + "deg) rotateY(" + wy.toFixed(2) + "deg)";
		}

		if (card) {
			var cx = (hovering ? -currentY * 6 : 0).toFixed(2);
			var cy = (hovering ? currentX * 8 : 0).toFixed(2);
			card.style.setProperty("--nm-tilt-x", cx + "deg");
			card.style.setProperty("--nm-tilt-y", cy + "deg");
		}

		window.requestAnimationFrame(tick);
	}

	window.addEventListener("mousemove", onMove, { passive: true });
	window.addEventListener("touchmove", onMove, { passive: true });
	window.addEventListener("mouseleave", onLeave);
	window.requestAnimationFrame(tick);
})();
