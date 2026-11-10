<?php
/**
 * WooCommerce brands as a submenu inside the header mega menu.
 */

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="pm-mega-brands">'
			. '.pm-mega__brands{margin-top:.9rem;padding-top:.75rem;border-top:1px solid rgba(30,67,57,.1)}'
			. '.pm-mega__brands-title{margin:0 0 .55rem;font-size:.82rem;font-weight:800;color:#1e4339}'
			. '.pm-mega__brand-list{list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:.4rem}'
			. '.pm-mega__brand-list a{display:inline-flex;align-items:center;padding:.38rem .8rem;border-radius:999px;background:#fff;border:1px solid #d5ebe3;color:#1e4339;font-size:.78rem;font-weight:700;line-height:1.3;text-decoration:none}'
			. '.pm-mega__brand-list a:hover{background:#e8f6f1;border-color:#4aa88e;color:#1e4339}'
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
<script id="pm-mega-brands-js">
(function () {
  if (document.documentElement.getAttribute("data-pm-brands-mounted")) return;
  document.documentElement.setAttribute("data-pm-brands-mounted", "1");
  var grids = document.querySelectorAll("[data-pm-mega-grid]");
  if (!grids.length) return;
  function esc(value) {
    return String(value == null ? "" : value).replace(/[&<>"']/g, function (ch) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
    });
  }
  fetch("/wp-json/wp/v2/product_brand?per_page=100&hide_empty=false", { credentials: "same-origin" })
    .then(function (res) { return res.ok ? res.json() : []; })
    .then(function (brands) {
      var list = (brands || []).filter(function (brand) { return brand && brand.name && brand.link; });
      list.sort(function (a, b) { return a.name.localeCompare(b.name, "fa"); });
      if (!list.length) return;
      var html = '<section class="pm-mega__brands" data-pm-mega-brands><p class="pm-mega__brands-title">برندها</p><ul class="pm-mega__brand-list">';
      list.forEach(function (brand) {
        html += '<li><a href="' + esc(brand.link) + '">' + esc(brand.name) + '</a></li>';
      });
      html += "</ul></section>";
      grids.forEach(function (grid) {
        if (grid.parentElement && !grid.parentElement.querySelector("[data-pm-mega-brands]")) {
          grid.insertAdjacentHTML("afterend", html);
        }
      });
    })
    .catch(function () {});
})();
</script>
		<?php
	},
	20
);
