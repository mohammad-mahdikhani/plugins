add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		$image = pm_header_logo_image();
		$id    = isset( $image['id'] ) ? (int) $image['id'] : 0;
		$url   = $id ? (string) wp_get_attachment_image_url( $id, 'full' ) : '';
		if ( $url === '' && ! empty( $image['url'] ) ) {
			$url = (string) $image['url'];
		}
		$url = esc_url_raw( $url );
		if ( $url === '' ) {
			return;
		}
		$alt = isset( $image['alt'] ) ? (string) $image['alt'] : '';
		if ( $alt === '' && $id ) {
			$alt = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );
		}
		$payload = wp_json_encode(
			array(
				'url' => $url,
				'alt' => $alt,
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
		echo '<style id="pm-header-logo-css">.pm-hide-logo-widget .pm-header-logo-editable{position:absolute!important;width:1px!important;height:1px!important;margin:0!important;padding:0!important;overflow:hidden!important;clip:rect(0,0,0,0)!important;white-space:nowrap!important;border:0!important}</style>' . "\n";
		echo '<script id="pm-header-logo">';
		echo '(function(){if(location.search.indexOf("elementor-preview")===-1){document.documentElement.classList.add("pm-hide-logo-widget");}';
		echo 'var cfg=' . $payload . ';';
		echo 'function apply(){if(!cfg||!cfg.url)return;document.querySelectorAll(".pm-header__logo").forEach(function(link){link.querySelectorAll("source").forEach(function(node){node.remove();});var img=link.querySelector("img");if(!img){img=document.createElement("img");img.className="pm-logo__img";img.width=220;img.height=72;img.decoding="async";link.appendChild(img);}img.src=cfg.url;img.removeAttribute("srcset");if(cfg.alt)img.alt=cfg.alt;});}';
		echo 'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",apply);}else{apply();}})();';
		echo '</script>' . "\n";
	},
	4
);

if ( ! function_exists( 'pm_header_logo_image' ) ) {
	function pm_header_logo_image(): array {
		$raw = get_post_meta( 124, '_elementor_data', true );
		if ( ! is_string( $raw ) || $raw === '' ) {
			return array();
		}
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			return array();
		}
		$stack = $data;
		while ( $stack ) {
			$node = array_pop( $stack );
			if ( ! is_array( $node ) ) {
				continue;
			}
			$settings = isset( $node['settings'] ) && is_array( $node['settings'] ) ? $node['settings'] : array();
			$classes  = isset( $settings['_css_classes'] ) ? (string) $settings['_css_classes'] : '';
			$is_logo  = ( isset( $node['widgetType'] ) && $node['widgetType'] === 'image' )
				&& ( strpos( $classes, 'pm-header-logo-editable' ) !== false || ( isset( $node['id'] ) && $node['id'] === 'pmlogo01' ) );
			if ( $is_logo && isset( $settings['image'] ) && is_array( $settings['image'] ) ) {
				return $settings['image'];
			}
			if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
				foreach ( $node['elements'] as $child ) {
					$stack[] = $child;
				}
			}
		}
		return array();
	}
}
