<?php
/**
 * Keep the open menu above the rest of the page.
 */

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		echo '<style id="pm-menu-z">'
			. 'html.pm-drawer-open .pm-header,html.pm-mega-open .pm-header,.pm-header:has(.pm-mega.is-open),.pm-header:has(.pm-drawer.is-open),body.pm-wc-shell .pm-header:has(.pm-mega.is-open),body.pm-wc-shell .pm-header:has(.pm-drawer.is-open){z-index:100000!important}'
			. 'html.pm-drawer-open .pm-drawer__backdrop,html.pm-drawer-open .pm-drawer__panel,html.pm-mega-open .pm-mega__panel,.pm-header:has(.pm-mega.is-open) .pm-mega__panel{z-index:100000!important}'
			. '</style>';
	},
	99
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		echo '<script id="pm-menu-z-js">'
			. '(function(){function sync(){var mega=document.querySelector(".pm-mega.is-open,[data-pm-mega].is-open");document.documentElement.classList.toggle("pm-mega-open",!!mega);} '
			. 'new MutationObserver(sync).observe(document.documentElement,{subtree:true,attributes:true,attributeFilter:["class"]});sync();})();'
			. '</script>';
	},
	99
);
