<?php
/**
 * Show every product image under the featured image.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Product_Gallery {

	public static function init(): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		add_filter( 'woocommerce_single_product_flexslider_enabled', '__return_false' );
		add_action( 'wp_footer', array( __CLASS__, 'script' ), 40 );
	}

	public static function script(): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		?>
		<script id="pm-product-gallery">
		(function () {
			var slides = document.querySelectorAll(".woocommerce-product-gallery__image");
			if (slides.length < 2) return;
			var host = document.querySelector(".pm-product-gallery");
			var main = document.querySelector(".pm-product-gallery__main");
			if (!host && main) host = main.parentElement;
			if (!host) {
				var featured = document.querySelector(".elementor-widget-theme-post-featured-image, .elementor-widget-image");
				host = featured || document.querySelector(".woocommerce-product-gallery");
				main = host ? host.querySelector("img") : null;
			}
			if (!host || host.querySelector(".pm-product-gallery__thumbs")) return;
			var row = document.createElement("div");
			row.className = "pm-product-gallery__thumbs";
			function targetImage() {
				if (main && main.tagName === "IMG") return main;
				return (main && main.querySelector) ? main.querySelector("img") : host.querySelector("img");
			}
			slides.forEach(function (slide, index) {
				var img = slide.querySelector("img");
				if (!img) return;
				var full = img.getAttribute("data-large_image") || img.getAttribute("data-src") || img.currentSrc || img.src;
				var btn = document.createElement("button");
				btn.type = "button";
				btn.setAttribute("aria-label", "تصویر " + (index + 1));
				if (index === 0) btn.className = "is-active";
				var thumb = document.createElement("img");
				thumb.alt = "";
				thumb.src = img.getAttribute("data-thumb") || img.currentSrc || img.src;
				btn.appendChild(thumb);
				btn.addEventListener("click", function () {
					var target = targetImage();
					if (target) {
						target.src = full;
						target.removeAttribute("srcset");
					}
					row.querySelectorAll("button").forEach(function (node) { node.classList.remove("is-active"); });
					btn.classList.add("is-active");
				});
				row.appendChild(btn);
			});
			if (main && main.parentNode === host) host.insertBefore(row, main.nextSibling);
			else host.appendChild(row);
		})();
		</script>
		<?php
	}
}
