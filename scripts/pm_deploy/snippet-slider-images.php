add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		$payload = wp_json_encode( pm_slider_images(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		echo '<style id="pm-slider-images-css">.pm-hide-slider-images .pm-slider-image{position:absolute!important;width:1px!important;height:1px!important;margin:0!important;padding:0!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}</style>' . "\n";
		echo '<script id="pm-slider-images">';
		echo '(function(){if(location.search.indexOf("elementor-preview")===-1){document.documentElement.classList.add("pm-hide-slider-images");}';
		echo 'var cfg=' . $payload . ';';
		echo 'function paint(slot,url){if(!slot||!url)return;document.querySelectorAll(".pm-rev__bg--"+slot).forEach(function(node){node.style.backgroundImage="url(\\""+url+"\\")";});}';
		echo 'function apply(){(cfg||[]).forEach(function(item){paint(item.slot,item.url);});document.querySelectorAll(".pm-slider-image").forEach(function(widget){var match=String(widget.className).match(/pm-slider-image--(\\d)/);var img=widget.querySelector("img");if(match&&img&&img.getAttribute("src"))paint(match[1],img.currentSrc||img.src);});}';
		echo 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",apply);}else{apply();}})();';
		echo '</script>' . "\n";
	},
	4
);

if ( ! function_exists( 'pm_slider_images' ) ) {
	function pm_slider_images(): array {
		$raw = get_post_meta( 13, '_elementor_data', true );
		if ( ! is_string( $raw ) || $raw === '' ) {
			return array();
		}
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}
		$found = array();
		$stack = $data;
		while ( $stack ) {
			$node = array_pop( $stack );
			if ( ! is_array( $node ) ) {
				continue;
			}
			$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
			$classes  = isset( $settings['_css_classes'] ) ? (string) $settings['_css_classes'] : '';
			$slot     = 0;
			foreach ( array( 1, 2, 3 ) as $index ) {
				if ( strpos( $classes, 'pm-slider-image--' . $index ) !== false ) {
					$slot = $index;
				}
			}
			if ( $slot && isset( $settings['image'] ) && is_array( $settings['image'] ) ) {
				$image = $settings['image'];
				$id    = isset( $image['id'] ) ? (int) $image['id'] : 0;
				$url   = $id ? (string) wp_get_attachment_image_url( $id, 'full' ) : '';
				if ( $url === '' && ! empty( $image['url'] ) ) {
					$url = (string) $image['url'];
				}
				$found[ $slot ] = array(
					'slot' => $slot,
					'url'  => esc_url_raw( $url ),
				);
			}
			if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
				foreach ( $node['elements'] as $child ) {
					$stack[] = $child;
				}
			}
		}
		ksort( $found );
		return array_values( $found );
	}
}
