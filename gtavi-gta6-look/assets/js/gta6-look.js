(function () {
	'use strict';

	document.documentElement.classList.add('gtavi-gta6-js');

	// Smooth anchor scroll for in-page links (homepage sections).
	document.addEventListener('click', function (event) {
		var link = event.target.closest('a[href^="#"]');
		if (!link || link.getAttribute('href') === '#') {
			return;
		}
		var id = link.getAttribute('href').slice(1);
		var target = document.getElementById(id);
		if (!target) {
			return;
		}
		event.preventDefault();
		target.scrollIntoView({ behavior: 'smooth', block: 'start' });
	});
})();
