<?php
/**
 * Google Search Console site verification.
 */

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		echo '<meta name="google-site-verification" content="Nthagzw4_dWwVTDASgHl9vPJHXjlxf-IhI3IDVzi4JI" />' . "\n";
	},
	1
);
