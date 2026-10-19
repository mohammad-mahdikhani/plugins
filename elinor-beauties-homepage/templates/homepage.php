<?php
/**
 * Full-width Beauties-style homepage for Elinor Store.
 *
 * @package Elinor_Beauties_Homepage
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

$products     = Elinor_Beauties_Homepage::get_featured_products(8);
$deal_product = Elinor_Beauties_Homepage::get_on_sale_products(1);
$deal         = $deal_product[0] ?? ($products[0] ?? null);
$categories   = Elinor_Beauties_Homepage::get_category_cards(4);
$posts        = Elinor_Beauties_Homepage::get_magazine_posts(4);

$shop_url    = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/');
$account_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/my-account/');
$cart_url    = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
$logo_id     = (int) get_theme_mod('custom_logo');
$logo_url    = $logo_id ? (string) wp_get_attachment_image_url($logo_id, 'medium') : 'https://elinorstore.com/wp-content/uploads/2026/08/%D9%84%D9%88%DA%AF%D9%88.jpg';

$hero_image = 'https://elinorstore.com/wp-content/uploads/2026/01/IMG_3467-2-1024x533.jpeg';

$reviews = [
	[
		'text'  => 'کیفیت محصولات عالیه و بسته‌بندی خیلی شیک بود. حتماً دوباره خرید می‌کنم.',
		'name'  => 'سارا محمدی',
		'stars' => 5,
	],
	[
		'text'  => 'ارسال سریع و مشاوره خوب قبل از خرید. رژ لب‌ها کاملاً مطابق توضیحات سایت بود.',
		'name'  => 'نیلوفر رضایی',
		'stars' => 5,
	],
	[
		'text'  => 'برای پوست حساسم سرم آنوا رو گرفتم؛ بعد از یک هفته تفاوت محسوس بود.',
		'name'  => 'مریم کاظمی',
		'stars' => 5,
	],
];

?><!DOCTYPE html>
<html <?php language_attributes(); ?> dir="rtl">
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class('elinor-bh-body'); ?>>
<?php wp_body_open(); ?>

<div class="elinor-bh">
	<header class="ebh-header">
		<div class="ebh-container ebh-header__inner">
			<a class="ebh-logo" href="<?php echo esc_url(home_url('/')); ?>">
				<img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" width="140" height="48" />
			</a>
			<nav class="ebh-nav" aria-label="<?php esc_attr_e('منوی اصلی', 'elinor-beauties-homepage'); ?>">
				<a href="<?php echo esc_url(home_url('/')); ?>">خانه</a>
				<a href="<?php echo esc_url($shop_url); ?>">فروشگاه</a>
				<a href="<?php echo esc_url($shop_url); ?>?on_sale=1">تخفیف‌ها</a>
				<a href="<?php echo esc_url(home_url('/blog/')); ?>">مجله زیبایی</a>
			</nav>
			<form class="ebh-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
				<input type="search" name="s" placeholder="جستجو در محصولات..." value="<?php echo esc_attr(get_search_query()); ?>" />
				<input type="hidden" name="post_type" value="product" />
				<button type="submit" aria-label="جستجو">
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm10 2-6.2-6.2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
				</button>
			</form>
			<div class="ebh-header__actions">
				<a class="ebh-icon-btn" href="<?php echo esc_url($cart_url); ?>" aria-label="سبد خرید">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L6 6Zm3 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm8 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.8"/></svg>
				</a>
				<a class="ebh-btn ebh-btn--solid" href="<?php echo esc_url($account_url); ?>">ورود</a>
			</div>
		</div>
	</header>

	<main>
		<section class="ebh-hero">
			<div class="ebh-container ebh-hero__grid">
				<div class="ebh-hero__content">
					<p class="ebh-eyebrow">فروشگاه اینترنتی الینور استور</p>
					<h1 class="ebh-hero__title">
						صورت شما <em>لایق</em> است تا <em>بیشتر بدرخشد</em>
					</h1>
					<p class="ebh-hero__text">
						لوازم آرایشی و مراقبت پوست اورجینال با ارسال سریع؛ هر روز با محصولات ما، هر جا بدرخشید.
					</p>
					<div class="ebh-hero__cta">
						<a class="ebh-btn ebh-btn--solid ebh-btn--lg" href="<?php echo esc_url($shop_url); ?>">مشاهده فروشگاه</a>
						<a class="ebh-btn ebh-btn--ghost" href="#ebh-deal">پیشنهاد ویژه</a>
					</div>
					<ul class="ebh-stats">
						<li><strong>+۲هزار</strong><span>محصول متنوع</span></li>
						<li><strong>۴.۸</strong><span>رضایت مشتریان</span></li>
						<li><strong>+۸</strong><span>دسته‌بندی</span></li>
					</ul>
				</div>
				<div class="ebh-hero__visual">
					<div class="ebh-hero__ring"></div>
					<div class="ebh-hero__frame">
						<img src="<?php echo esc_url($hero_image); ?>" alt="محصولات زیبایی الینور استور" loading="eager" />
					</div>
					<div class="ebh-badge">تا ۲۵٪ تخفیف</div>
				</div>
			</div>
		</section>

		<section class="ebh-section" id="ebh-categories">
			<div class="ebh-container">
				<h2 class="ebh-section__title">کشف با دسته‌بندی‌ها</h2>
				<div class="ebh-categories">
					<?php foreach ($categories as $cat) : ?>
						<a class="ebh-cat" href="<?php echo esc_url($cat['link']); ?>">
							<div class="ebh-cat__img">
								<img src="<?php echo esc_url($cat['image']); ?>" alt="<?php echo esc_attr($cat['name']); ?>" loading="lazy" />
							</div>
							<h3><?php echo esc_html($cat['name']); ?></h3>
							<span>مشاهده همه</span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="ebh-section ebh-section--muted">
			<div class="ebh-container">
				<div class="ebh-section__head">
					<h2 class="ebh-section__title">پرطرفدارترین محصولات</h2>
					<div class="ebh-carousel-nav" data-carousel="products">
						<button type="button" data-dir="prev" aria-label="قبلی">‹</button>
						<button type="button" data-dir="next" aria-label="بعدی">›</button>
					</div>
				</div>
				<div class="ebh-products" data-carousel-track="products">
					<?php foreach ($products as $product) :
						$link  = $product->get_permalink();
						$img   = wp_get_attachment_image_url($product->get_image_id(), 'medium_large') ?: wc_placeholder_img_src();
						$badge = $product->is_on_sale() ? 'تخفیف' : 'جدید';
						?>
						<article class="ebh-product">
							<div class="ebh-product__top">
								<span class="ebh-tag"><?php echo esc_html($badge); ?></span>
								<button type="button" class="ebh-wish" aria-label="علاقه‌مندی">♡</button>
							</div>
							<a href="<?php echo esc_url($link); ?>" class="ebh-product__img">
								<img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" loading="lazy" />
							</a>
							<h3><a href="<?php echo esc_url($link); ?>"><?php echo esc_html($product->get_name()); ?></a></h3>
							<div class="ebh-product__price"><?php echo wp_kses_post(Elinor_Beauties_Homepage::format_price_html($product)); ?></div>
							<a class="ebh-link" href="<?php echo esc_url($link); ?>">افزودن به سبد</a>
						</article>
					<?php endforeach; ?>
				</div>
				<div class="ebh-center">
					<a class="ebh-btn ebh-btn--outline" href="<?php echo esc_url($shop_url); ?>">مشاهده همه</a>
				</div>
			</div>
		</section>

		<section class="ebh-deal" id="ebh-deal">
			<div class="ebh-container">
				<h2 class="ebh-deal__title">پیشنهاد ویژه امروز</h2>
				<?php if ($deal) :
					$deal_link = $deal->get_permalink();
					$deal_img  = wp_get_attachment_image_url($deal->get_image_id(), 'large') ?: wc_placeholder_img_src();
					?>
					<div class="ebh-deal__card">
						<div class="ebh-deal__media">
							<img src="<?php echo esc_url($deal_img); ?>" alt="<?php echo esc_attr($deal->get_name()); ?>" />
						</div>
						<div class="ebh-deal__body">
							<?php if ($deal->is_on_sale()) : ?>
								<span class="ebh-tag ebh-tag--light">فروش ویژه</span>
							<?php endif; ?>
							<h3><?php echo esc_html($deal->get_name()); ?></h3>
							<div class="ebh-deal__price"><?php echo wp_kses_post(Elinor_Beauties_Homepage::format_price_html($deal)); ?></div>
							<p class="ebh-deal__desc"><?php echo esc_html(wp_trim_words($deal->get_short_description() ?: $deal->get_description(), 22)); ?></p>
							<a class="ebh-btn ebh-btn--solid ebh-btn--lg" href="<?php echo esc_url($deal_link); ?>">افزودن به سبد خرید</a>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<section class="ebh-section">
			<div class="ebh-container ebh-bestsellers">
				<div class="ebh-bestsellers__banner">
					<img src="<?php echo esc_url($hero_image); ?>" alt="الینور استور" />
					<div class="ebh-bestsellers__overlay">
						<h2>پرفروش‌ترین‌های هفته</h2>
						<p>انتخاب‌های محبوب مشتریان الینور</p>
						<a class="ebh-btn ebh-btn--solid" href="<?php echo esc_url($shop_url); ?>">خرید کنید</a>
					</div>
				</div>
				<div class="ebh-bestsellers__grid">
					<?php
					$grid_products = array_slice($products, 0, 4);
					foreach ($grid_products as $product) :
						$link = $product->get_permalink();
						$img  = wp_get_attachment_image_url($product->get_image_id(), 'medium') ?: wc_placeholder_img_src();
						?>
						<article class="ebh-mini-product">
							<a href="<?php echo esc_url($link); ?>" class="ebh-mini-product__img">
								<img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" loading="lazy" />
							</a>
							<div>
								<h3><a href="<?php echo esc_url($link); ?>"><?php echo esc_html(wp_trim_words($product->get_name(), 6)); ?></a></h3>
								<div class="ebh-mini-product__price"><?php echo wp_kses_post(Elinor_Beauties_Homepage::format_price_html($product)); ?></div>
								<a class="ebh-btn ebh-btn--small" href="<?php echo esc_url($link); ?>">افزودن</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<?php if (!empty($posts)) : ?>
		<section class="ebh-section ebh-section--muted">
			<div class="ebh-container">
				<h2 class="ebh-section__title">مجله زیبایی</h2>
				<div class="ebh-magazine">
					<?php foreach ($posts as $post) :
						$thumb = get_the_post_thumbnail_url($post, 'medium_large') ?: $hero_image;
						?>
						<a class="ebh-mag-card" href="<?php echo esc_url(get_permalink($post)); ?>">
							<img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title($post)); ?>" loading="lazy" />
							<div>
								<h3><?php echo esc_html(get_the_title($post)); ?></h3>
								<span>مطالعه بیشتر</span>
							</div>
						</a>
					<?php endforeach; ?>
				</div>
				<div class="ebh-center">
					<a class="ebh-btn ebh-btn--outline" href="<?php echo esc_url(home_url('/blog/')); ?>">مشاهده همه</a>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<section class="ebh-section">
			<div class="ebh-container">
				<h2 class="ebh-section__title">نظرات مشتریان</h2>
				<div class="ebh-reviews" data-carousel-track="reviews">
					<?php foreach ($reviews as $review) : ?>
						<blockquote class="ebh-review">
							<div class="ebh-stars" aria-hidden="true"><?php echo str_repeat('★', (int) $review['stars']); ?></div>
							<p><?php echo esc_html($review['text']); ?></p>
							<cite><?php echo esc_html($review['name']); ?></cite>
						</blockquote>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="ebh-section ebh-instagram">
			<div class="ebh-container">
				<h2 class="ebh-section__title">اینستاگرام</h2>
				<div class="ebh-insta-grid">
					<?php
					$insta_imgs = array_slice($products, 0, 4);
					foreach ($insta_imgs as $i => $product) :
						$img = wp_get_attachment_image_url($product->get_image_id(), 'medium') ?: wc_placeholder_img_src();
						?>
						<a class="ebh-insta-item<?php echo $i === 1 ? ' is-highlight' : ''; ?>" href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer">
							<img src="<?php echo esc_url($img); ?>" alt="" loading="lazy" />
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	</main>

	<footer class="ebh-footer">
		<div class="ebh-container ebh-footer__top">
			<div>
				<h2>عضویت در خبرنامه</h2>
				<p>تخفیف‌ها و محصولات جدید را زودتر از همه دریافت کنید.</p>
				<form class="ebh-newsletter" action="#" method="post" onsubmit="return false;">
					<input type="email" placeholder="ایمیل شما" required />
					<button type="submit" aria-label="ارسال">→</button>
				</form>
			</div>
			<div class="ebh-footer__cols">
				<div>
					<h3>فروشگاه</h3>
					<a href="<?php echo esc_url($shop_url); ?>">همه محصولات</a>
					<a href="<?php echo esc_url($shop_url); ?>?on_sale=1">تخفیف‌ها</a>
				</div>
				<div>
					<h3>درباره</h3>
					<a href="<?php echo esc_url(home_url('/about/')); ?>">درباره ما</a>
					<a href="<?php echo esc_url(home_url('/contact/')); ?>">تماس</a>
				</div>
				<div>
					<h3>پشتیبانی</h3>
					<a href="<?php echo esc_url(home_url('/faq/')); ?>">سوالات متداول</a>
					<a href="<?php echo esc_url(home_url('/shipping/')); ?>">ارسال و مرجوعی</a>
				</div>
			</div>
		</div>
		<div class="ebh-container ebh-footer__bottom">
			<p>© <?php echo esc_html(gmdate('Y')); ?> <?php bloginfo('name'); ?>. تمامی حقوق محفوظ است.</p>
			<div>
				<a href="<?php echo esc_url(home_url('/terms/')); ?>">قوانین</a>
				<a href="<?php echo esc_url(home_url('/privacy/')); ?>">حریم خصوصی</a>
			</div>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
