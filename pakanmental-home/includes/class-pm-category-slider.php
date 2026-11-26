<?php
/**
 * Homepage hero: a 3D coverflow for each of the fullest product categories.
 */

defined( 'ABSPATH' ) || exit;

final class PM_Category_Slider {

	public static function init(): void {
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 18 );
	}

	public static function render(): void {
		if ( is_admin() || ! is_front_page() || ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$bands = self::bands();
		if ( ! $bands ) {
			return;
		}
		self::markup( $bands );
	}

	/**
	 * Up to four categories that actually contain products.
	 *
	 * Parent categories come first. A child category is added only when
	 * fewer than four parents have products, and its photos are offset so
	 * the ring is not a copy of the parent ring.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function bands(): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 12,
			)
		);
		if ( is_wp_error( $terms ) || ! $terms ) {
			return array();
		}

		$usable = array();
		foreach ( $terms as $term ) {
			if ( self::is_uncategorized( $term ) ) {
				continue;
			}
			$usable[] = $term;
		}

		$picked = array();
		foreach ( $usable as $term ) {
			if ( (int) $term->parent !== 0 ) {
				continue;
			}
			$picked[] = $term;
			if ( count( $picked ) >= 4 ) {
				break;
			}
		}
		if ( count( $picked ) < 4 ) {
			$seen = array();
			foreach ( $picked as $term ) {
				$seen[ (int) $term->term_id ] = true;
			}
			foreach ( $usable as $term ) {
				if ( isset( $seen[ (int) $term->term_id ] ) ) {
					continue;
				}
				$picked[] = $term;
				if ( count( $picked ) >= 4 ) {
					break;
				}
			}
		}

		$parent_ids = array();
		foreach ( $picked as $term ) {
			if ( (int) $term->parent === 0 ) {
				$parent_ids[ (int) $term->term_id ] = true;
			}
		}

		$bands = array();
		foreach ( $picked as $term ) {
			$offset   = isset( $parent_ids[ (int) $term->parent ] ) ? 8 : 0;
			$products = self::products( (int) $term->term_id, $offset );
			if ( count( $products ) < 4 ) {
				continue;
			}
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$bands[] = array(
				'name'     => $term->name,
				'url'      => $link,
				'count'    => self::digits( (string) (int) $term->count ),
				'products' => $products,
			);
		}
		return $bands;
	}

	/**
	 * @param WP_Term $term Category term.
	 */
	private static function is_uncategorized( $term ): bool {
		$name = html_entity_decode( (string) $term->name, ENT_QUOTES, 'UTF-8' );
		$slug = rawurldecode( (string) $term->slug );
		return in_array( $slug, array( 'uncategorized', 'دسته-بندی-نشده' ), true )
			|| in_array( $name, array( 'Uncategorized', 'دسته-بندی-نشده' ), true );
	}

	/**
	 * @return array<int, array{name: string, img: string}>
	 */
	private static function products( int $term_id, int $offset ): array {
		$query = new WP_Query(
			array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'posts_per_page'      => 8,
				'offset'              => $offset,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'tax_query'           => array(
					array(
						'taxonomy'         => 'product_cat',
						'field'            => 'term_id',
						'terms'            => $term_id,
						'include_children' => true,
					),
				),
			)
		);
		$items = array();
		foreach ( $query->posts as $post ) {
			$thumb = get_post_thumbnail_id( $post );
			$img   = $thumb ? wp_get_attachment_image_url( $thumb, 'medium_large' ) : '';
			if ( ! $img ) {
				continue;
			}
			$items[] = array(
				'name' => get_the_title( $post ),
				'img'  => $img,
			);
		}
		wp_reset_postdata();
		return $items;
	}

	private static function digits( string $value ): string {
		return strtr(
			$value,
			array(
				'0' => '۰',
				'1' => '۱',
				'2' => '۲',
				'3' => '۳',
				'4' => '۴',
				'5' => '۵',
				'6' => '۶',
				'7' => '۷',
				'8' => '۸',
				'9' => '۹',
			)
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $bands Category bands.
	 */
	private static function markup( array $bands ): void {
		$json = wp_json_encode( $bands );
		?>
		<style id="pm-cat3d-style">
		.pm-rev--cat3d{background:linear-gradient(180deg,#e7f4ef 0%,#fafaf9 70%)}
		.pm-rev--cat3d .pm-rev__viewport{height:auto!important;min-height:0!important;overflow:visible!important;background:transparent!important;box-shadow:none!important}
		.pm-rev--cat3d .pm-rev__slide,.pm-rev--cat3d .pm-rev__arrow,.pm-rev--cat3d .pm-rev__bullets{display:none!important}
		.pm-site--has-hero:has(.pm-rev--cat3d) .pm-header:not(.is-scrolled) .pm-header__bar{background:rgba(250,247,242,.92)!important;backdrop-filter:blur(16px);box-shadow:0 10px 28px rgba(30,67,57,.08);border-radius:28px}
		.pm-site--has-hero:has(.pm-rev--cat3d) .pm-header:not(.is-scrolled) .pm-seg__link,
		.pm-site--has-hero:has(.pm-rev--cat3d) .pm-header:not(.is-scrolled) .pm-seg__link.is-active,
		.pm-site--has-hero:has(.pm-rev--cat3d) .pm-header:not(.is-scrolled) .pm-search__icon,
		.pm-site--has-hero:has(.pm-rev--cat3d) .pm-header:not(.is-scrolled) .pm-header__icon-btn{color:#1e4339!important}
		.pm-site--has-hero:has(.pm-rev--cat3d) .pm-header:not(.is-scrolled) .pm-seg__link.is-active{box-shadow:inset 0 -2px 0 #3d8f78!important}
		.pm-site--has-hero:has(.pm-rev--cat3d) .pm-header:not(.is-scrolled) .pm-search__input{color:#1e4339!important;background:#fff!important}
		.pm-site--has-hero:has(.pm-rev--cat3d) .pm-header:not(.is-scrolled) .pm-search__input::placeholder{color:#2a5f52!important}
		.pm-cat3d{position:relative;z-index:2;width:min(1120px,calc(100% - 2rem));margin:0 auto;padding:clamp(5.5rem,12vw,7.5rem) 0 1.5rem}
		.pm-cat3d__intro{text-align:center;margin-bottom:.5rem}
		.pm-cat3d__intro h1{margin:0;color:#1e4339;font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800}
		.pm-cat3d__intro p{margin:.4rem 0 0;color:#2a5f52}
		.pm-cat3d__band{margin-top:1.25rem;padding:1rem 1rem .25rem;border-radius:28px;background:color-mix(in srgb,#fff 72%,#e8f6f1);box-shadow:0 10px 28px rgba(30,67,57,.08)}
		.pm-cat3d__head{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;padding-inline:.4rem}
		.pm-cat3d__kicker{margin:0;color:#3d8f78;font-size:.82rem;font-weight:700}
		.pm-cat3d__head h2{margin:.1rem 0 0;font-size:clamp(1.25rem,2.4vw,1.7rem);font-weight:800}
		.pm-cat3d__head h2 a{color:#1e4339}
		.pm-cat3d__stage{height:300px;display:flex;align-items:center;justify-content:center;perspective:1100px;overflow:hidden}
		.pm-cat3d__ring{position:relative;width:168px;height:214px;transform-style:preserve-3d;animation:pm-cat3d-spin 26s linear infinite}
		.pm-cat3d__band:hover .pm-cat3d__ring,.pm-cat3d__band:focus-within .pm-cat3d__ring{animation-play-state:paused}
		.pm-cat3d__card{position:absolute;inset:0;display:block;overflow:hidden;border-radius:18px;background:#fff;box-shadow:0 16px 34px rgba(30,67,57,.22);transform:rotateY(calc(var(--i) * 360deg / var(--n))) translateZ(250px);backface-visibility:hidden}
		.pm-cat3d__card img{width:100%;height:100%;object-fit:cover}
		@keyframes pm-cat3d-spin{from{transform:rotateY(0deg)}to{transform:rotateY(-360deg)}}
		@media(max-width:720px){
			.pm-cat3d__stage{height:250px}
			.pm-cat3d__ring{width:136px;height:176px}
			.pm-cat3d__card{transform:rotateY(calc(var(--i) * 360deg / var(--n))) translateZ(168px)}
			.pm-cat3d__head{align-items:flex-start;flex-direction:column}
		}
		@media(prefers-reduced-motion:reduce){
			.pm-cat3d__stage{height:auto;overflow:visible;perspective:none}
			.pm-cat3d__ring{width:100%;height:auto;display:flex;gap:.75rem;overflow-x:auto;animation:none;transform:none}
			.pm-cat3d__card{position:relative;inset:auto;flex:0 0 140px;height:176px;transform:none}
		}
		</style>
		<script id="pm-cat3d">
		(function () {
			if (window.pmCat3dBuilt) return;
			var bands = <?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			var root = document.querySelector(".pm-rev");
			if (!root || !bands || !bands.length) return;
			window.pmCat3dBuilt = 1;
			root.classList.add("pm-rev--cat3d");
			var viewport = root.querySelector(".pm-rev__viewport") || root;
			function esc(value) {
				return String(value == null ? "" : value).replace(/[&<>"']/g, function (ch) {
					return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[ch];
				});
			}
			var html = '<div class="pm-cat3d"><div class="pm-cat3d__intro"><h1>سرگرمی رومیزی برای هر سلیقه</h1><p>دسته‌های پرمحصول را ورق بزنید</p></div>';
			bands.forEach(function (band) {
				var n = band.products.length;
				var url = esc(band.url);
				var cards = band.products.map(function (product, index) {
					return '<a class="pm-cat3d__card" style="--i:' + index + '" href="' + url + '"><img src="' + esc(product.img) + '" alt="' + esc(product.name) + '"></a>';
				}).join("");
				html += '<section class="pm-cat3d__band"><div class="pm-cat3d__head"><div><p class="pm-cat3d__kicker">' + esc(band.count) + ' محصول</p><h2><a href="' + url + '">' + esc(band.name) + '</a></h2></div><a class="pm-btn pm-btn--primary" href="' + url + '">مشاهده دسته</a></div><div class="pm-cat3d__stage"><div class="pm-cat3d__ring" style="--n:' + n + '">' + cards + '</div></div></section>';
			});
			html += "</div>";
			viewport.insertAdjacentHTML("afterbegin", html);
		})();
		</script>
		<?php
	}
}
