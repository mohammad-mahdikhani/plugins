<?php
/**
 * Template Name: پاکن منتال — صفحه اصلی
 * Lightweight front page layout (plugin).
 */

defined( 'ABSPATH' ) || exit;

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' );
$blog_url = get_post_type_archive_link( 'post' ) ?: home_url( '/blog/' );

?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'pm-home' ); ?>>
<?php wp_body_open(); ?>

<header class="pm-header">
	<div class="pm-container pm-header__inner">
		<nav class="pm-nav pm-nav--start" aria-label="<?php esc_attr_e( 'منوی اصلی', 'pakanmental-home' ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="is-active"><?php esc_html_e( 'خانه', 'pakanmental-home' ); ?></a>
			<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'فروشگاه', 'pakanmental-home' ); ?></a>
		</nav>
		<a class="pm-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php pm_picture( 'images/logo-pakanmental.webp', get_bloginfo( 'name' ), array( 'width' => 180, 'height' => 52, 'fetchpriority' => 'high', 'loading' => 'eager' ) ); ?>
		</a>
		<nav class="pm-nav pm-nav--end">
			<a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'مجله', 'pakanmental-home' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'تماس', 'pakanmental-home' ); ?></a>
		</nav>
		<details class="pm-menu-mobile">
			<summary><?php esc_html_e( 'منو', 'pakanmental-home' ); ?></summary>
			<div class="pm-menu-mobile__panel">
				<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'فروشگاه', 'pakanmental-home' ); ?></a>
				<a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'مجله', 'pakanmental-home' ); ?></a>
			</div>
		</details>
	</div>
</header>

<main id="pm-main">
	<section class="pm-hero" aria-label="<?php esc_attr_e( 'معرفی', 'pakanmental-home' ); ?>">
		<div class="pm-container">
			<div class="pm-hero__slider" data-pm-hero>
				<div class="pm-hero__track" id="pm-hero-track">
					<article class="pm-hero__slide">
						<div class="pm-hero__copy">
							<p class="pm-tags"><span>جدید</span><span>بردگیم</span><span>پازل</span></p>
							<h1><?php esc_html_e( 'سرگرمی رومیزی برای هر سلیقه', 'pakanmental-home' ); ?></h1>
							<p><?php esc_html_e( 'بازی فکری، پازل، اکشن فیگور و بازی‌های منتالی — یک فروشگاه، همهٔ لحظه‌های دورهم.', 'pakanmental-home' ); ?></p>
							<p class="pm-hero__actions">
								<a class="pm-btn pm-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'مشاهده فروشگاه', 'pakanmental-home' ); ?></a>
							</p>
						</div>
						<div class="pm-hero__media pm-hero__media--1" role="img" aria-label="<?php esc_attr_e( 'بنر معرفی', 'pakanmental-home' ); ?>"></div>
					</article>
					<article class="pm-hero__slide">
						<div class="pm-hero__copy">
							<p class="pm-tags"><span><?php esc_html_e( 'ترند', 'pakanmental-home' ); ?></span><span><?php esc_html_e( 'پازل', 'pakanmental-home' ); ?></span></p>
							<h1><?php esc_html_e( 'پازل‌های منتخب', 'pakanmental-home' ); ?></h1>
							<p><?php esc_html_e( 'از ۵۰۰ قطعه تا چالش‌های حرفه‌ای — به‌زودی در فروشگاه.', 'pakanmental-home' ); ?></p>
							<p class="pm-hero__actions"><a class="pm-btn pm-btn--primary" href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'دسته پازل', 'pakanmental-home' ); ?></a></p>
						</div>
						<div class="pm-hero__media pm-hero__media--2" role="img" aria-hidden="true"></div>
					</article>
					<article class="pm-hero__slide">
						<div class="pm-hero__copy">
							<p class="pm-tags"><span><?php esc_html_e( 'اکشن فیگور', 'pakanmental-home' ); ?></span></p>
							<h1><?php esc_html_e( 'کلکسیون فیگور', 'pakanmental-home' ); ?></h1>
							<p><?php esc_html_e( 'برای کلکسیونرها و هدیهٔ خاص.', 'pakanmental-home' ); ?></p>
						</div>
						<div class="pm-hero__media pm-hero__media--3" role="img" aria-hidden="true"></div>
					</article>
				</div>
				<div class="pm-hero__dots" role="tablist" aria-label="<?php esc_attr_e( 'اسلایدها', 'pakanmental-home' ); ?>">
					<button type="button" role="tab" aria-selected="true" aria-controls="pm-hero-track" data-pm-hero-dot="0"></button>
					<button type="button" role="tab" aria-selected="false" data-pm-hero-dot="1"></button>
					<button type="button" role="tab" aria-selected="false" data-pm-hero-dot="2"></button>
				</div>
			</div>
		</div>
	</section>

	<section class="pm-section" aria-labelledby="pm-cats-title">
		<div class="pm-container">
			<header class="pm-section__head">
				<div>
					<h2 id="pm-cats-title"><?php esc_html_e( 'دسته‌بندی فروشگاه', 'pakanmental-home' ); ?></h2>
					<p><?php esc_html_e( 'مسیر سریع به هر نوع سرگرمی رومیزی', 'pakanmental-home' ); ?></p>
				</div>
			</header>
			<?php echo pm_home_parent_categories_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</section>

	<?php
	PM_Template::render_product_row(
		array(
			'id'                 => 'pm-trend',
			'title'              => __( 'پرفروش‌ها و ترند', 'pakanmental-home' ),
			'subtitle'           => __( 'محبوب‌ترین انتخاب‌ها', 'pakanmental-home' ),
			'more'               => $shop_url,
			'orderby'            => 'popularity',
			'placeholder_prefix' => __( 'محصول', 'pakanmental-home' ),
			'badge'              => true,
			'alt'                => true,
		)
	);
	$pm_cat_i = 0;
	foreach ( pm_home_parent_category_terms() as $pm_cat ) {
		$pm_cat_link = get_term_link( $pm_cat );
		if ( is_wp_error( $pm_cat_link ) ) {
			$pm_cat_link = $shop_url;
		}
		PM_Template::render_product_row(
			array(
				'id'                 => 'pm-cat-' . (int) $pm_cat->term_id,
				'title'              => $pm_cat->name,
				'subtitle'           => __( 'جدیدترین محصولات این دسته', 'pakanmental-home' ),
				'more'               => $pm_cat_link,
				'term_id'            => (int) $pm_cat->term_id,
				'orderby'            => 'date',
				'placeholder_prefix' => $pm_cat->name,
				'alt'                => 0 === ( $pm_cat_i % 2 ),
				'allow_empty'        => true,
			)
		);
		++$pm_cat_i;
	}
	?>

	<section class="pm-section pm-section--alt" aria-labelledby="pm-blog-title">
		<div class="pm-container">
			<header class="pm-section__head">
				<div>
					<h2 id="pm-blog-title"><?php esc_html_e( 'مجله پاکن منتال', 'pakanmental-home' ); ?></h2>
					<p><?php esc_html_e( 'راهنما و مقالات', 'pakanmental-home' ); ?></p>
				</div>
				<a class="pm-more" href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'همه مطالب', 'pakanmental-home' ); ?></a>
			</header>
			<div class="pm-blog">
				<?php
				$posts = get_posts(
					array(
						'numberposts' => 3,
						'post_status' => 'publish',
					)
				);
				if ( $posts ) {
					foreach ( $posts as $post ) {
						setup_postdata( $post );
						?>
						<article class="pm-post">
							<a href="<?php the_permalink(); ?>">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php the_post_thumbnail( 'medium_large', array( 'class' => 'pm-post__img', 'loading' => 'lazy' ) ); ?>
								<?php else : ?>
									<div class="pm-post__ph" aria-hidden="true"></div>
								<?php endif; ?>
								<h3><?php the_title(); ?></h3>
								<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 14 ) ); ?></p>
							</a>
						</article>
						<?php
					}
					wp_reset_postdata();
				} else {
					for ( $i = 1; $i <= 3; $i++ ) {
						echo '<article class="pm-post pm-post--empty"><div class="pm-post__ph"></div><h3>' . esc_html__( 'مقاله نمونه', 'pakanmental-home' ) . ' ' . $i . '</h3><p>' . esc_html__( 'پس از انتشار مطلب، اینجا نمایش داده می‌شود.', 'pakanmental-home' ) . '</p></article>';
					}
				}
				?>
			</div>
		</div>
	</section>
</main>

<footer class="pm-footer">
	<div class="pm-container pm-footer__grid">
		<div class="pm-footer__brand">
			<?php pm_picture( 'images/logo-pakanmental.webp', '', array( 'width' => 140, 'height' => 56, 'loading' => 'lazy' ) ); ?>
			<p><?php esc_html_e( 'فروشگاه بازی فکری، بردگیم، پازل و سرگرمی رومیزی.', 'pakanmental-home' ); ?></p>
		</div>
		<div>
			<h2 class="pm-footer__title"><?php esc_html_e( 'فروشگاه', 'pakanmental-home' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'همه محصولات', 'pakanmental-home' ); ?></a></li>
			</ul>
		</div>
		<div>
			<h2 class="pm-footer__title"><?php esc_html_e( 'مجله', 'pakanmental-home' ); ?></h2>
			<ul>
				<li><a href="<?php echo esc_url( $blog_url ); ?>"><?php esc_html_e( 'مقالات', 'pakanmental-home' ); ?></a></li>
			</ul>
		</div>
	</div>
	<p class="pm-footer__copy">© <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
