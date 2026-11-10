<?php
/**
 * Puzzle piece rows in the header mega menu.
 * 1000- and 500-piece rows open on hover. Brands only sit under 1000-piece.
 */

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="pm-mega-pieces">'
			. '.pm-mega__brands,.pm-puzzle-brands{display:none!important}'
			. '@media(min-width:901px){.pm-header .pm-mega__panel,.pm-header .pm-mega__inner{max-height:none!important;overflow:visible!important}}'
			. '.pm-header .pm-mega__acc{display:block;width:100%;max-width:100%;min-width:0;margin:0;border:0}'
			. '.pm-header .pm-mega__acc>summary{display:flex;align-items:center;justify-content:flex-start;gap:.35rem;width:100%;max-width:100%;min-width:0;cursor:pointer;list-style:none;padding:0}'
			. '.pm-header .pm-mega__acc>summary::-webkit-details-marker{display:none}'
			. '.pm-header .pm-mega__acc>summary::after{content:"+";flex:none;font-weight:800;line-height:1;color:#1e4339}'
			. '.pm-header .pm-mega__acc[open]>summary::after{content:"−"}'
			. '.pm-header .pm-mega__acc>summary a{flex:1}'
			. '.pm-header .pm-mega__acc>.pm-mega__sub{margin-top:.28rem;padding-inline-start:.75rem;border-inline-start:1px solid rgba(24,80,64,.15)}'
			. '</style>';
	},
	20
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
<script id="pm-mega-pieces-js">
(function () {
  var header = document.querySelector(".pm-header");
  if (!header || header.dataset.pmPieceMenu === "1") return;
  header.dataset.pmPieceMenu = "1";
  var brands = null;
  function text(node) {
    return (node.textContent || "").replace(/\s+/g, " ").trim();
  }
  function is1000(name) {
    return /(?:1000|۱۰۰۰)/.test(name) && /(?:تکه|تیکه)/.test(name);
  }
  function is500(name) {
    return /(?:^|[^0-9۰-۹])500(?:[^0-9۰-۹]|$)|۵۰۰/.test(name) && /(?:تکه|تیکه)/.test(name);
  }
  function canHover() {
    return window.matchMedia("(hover: hover) and (min-width: 901px)").matches;
  }
  function bindHover(details) {
    if (details.dataset.pmPieceHover === "1") return;
    details.dataset.pmPieceHover = "1";
    details.open = false;
    details.addEventListener("mouseenter", function () {
      if (!canHover()) return;
      details.open = true;
    });
    details.addEventListener("mouseleave", function () {
      if (!canHover()) return;
      details.open = false;
    });
    var summary = details.querySelector("summary");
    if (!summary) return;
    summary.addEventListener("click", function (event) {
      if (!canHover() || event.target.closest("a")) return;
      event.preventDefault();
    });
  }
  function pieceOf(anchor) {
    var li = anchor.closest("li");
    if (!li) return null;
    var existing = li.querySelector("details.pm-mega__acc");
    if (existing) {
      bindHover(existing);
      return existing;
    }
    var details = document.createElement("details");
    details.className = "pm-mega__acc";
    var summary = document.createElement("summary");
    summary.appendChild(anchor);
    details.appendChild(summary);
    li.appendChild(details);
    bindHover(details);
    return details;
  }
  function render() {
    header.querySelectorAll("[data-pm-mega-brands]").forEach(function (node) {
      node.remove();
    });
    header.querySelectorAll(".pm-puzzle-brands").forEach(function (node) {
      if (!node.closest("details.pm-mega__acc")) node.remove();
    });
    header.querySelectorAll("[data-pm-mega-grid] a").forEach(function (anchor) {
      if (anchor.closest("[data-pm-piece-brands]")) return;
      var name = text(anchor);
      if (is500(name)) pieceOf(anchor);
      if (!is1000(name) || !brands) return;
      var details = pieceOf(anchor);
      if (!details || details.querySelector("[data-pm-piece-brands]")) return;
      var list = document.createElement("ul");
      list.className = "pm-mega__sub";
      list.setAttribute("data-pm-piece-brands", "1");
      brands.forEach(function (brand) {
        var item = document.createElement("li");
        var link = document.createElement("a");
        link.textContent = brand.name;
        try {
          var url = new URL(brand.link, location.origin);
          if (url.origin !== location.origin) return;
          link.href = url.href;
        } catch (error) {
          return;
        }
        item.appendChild(link);
        list.appendChild(item);
      });
      details.appendChild(list);
    });
  }
  var queued = false;
  function schedule() {
    if (queued) return;
    queued = true;
    requestAnimationFrame(function () {
      queued = false;
      render();
    });
  }
  new MutationObserver(schedule).observe(header, { childList: true, subtree: true });
  fetch("/wp-json/wp/v2/product_brand?per_page=100&hide_empty=false", { credentials: "same-origin" })
    .then(function (res) { return res.ok ? res.json() : []; })
    .then(function (rows) {
      brands = (rows || []).filter(function (brand) { return brand && brand.name && brand.link; });
      brands.sort(function (a, b) { return a.name.localeCompare(b.name, "fa"); });
      render();
    })
    .catch(function () {});
  render();
})();
</script>
		<?php
	},
	20
);
