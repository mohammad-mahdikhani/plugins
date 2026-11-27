<?php
/**
 * Public marketing landing for Daftarchi on webakery.ir.
 * Does not touch admin menus, REST routes, or other Daftarchi snippets.
 *
 * Usage:
 *   1) Add this file as a Code Snippet (run everywhere), OR require from child theme.
 *   2) Upload daftarchi-landing.css to wp-content/uploads/daftarchi-landing/
 *   3) Create a WordPress page and place shortcode [daftarchi_landing]
 *      or assign template "دفترچی — لندینگ" (if theme template is installed).
 */

if (!defined('ABSPATH')) {
    exit;
}

function daftarchi_landing_config() {
    $uploads = wp_upload_dir();
  $default_css = trailingslashit($uploads['baseurl']) . 'daftarchi-landing/daftarchi-landing.css';
    return apply_filters(
        'daftarchi_landing_config',
        array(
            'css_url' => $default_css,
            'version_json' => home_url('/daftarchi/version.json'),
            'download_fallback' => 'https://webakery.ir/wp-content/uploads/daftarchi-releases/',
            'support_url' => home_url('/'),
            'brand_name' => 'دفترچی',
            'org_name' => 'وب‌بیکری',
            'trial_days' => 7,
        )
    );
}

function daftarchi_landing_should_load_assets() {
    if (!is_singular()) {
        return false;
    }
    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return false;
    }
    if (has_shortcode($post->post_content, 'daftarchi_landing')) {
        return true;
    }
    return get_page_template_slug($post) === 'daftarchi-landing.php';
}

function daftarchi_landing_enqueue_assets() {
    if (!daftarchi_landing_should_load_assets()) {
        return;
    }
    $cfg = daftarchi_landing_config();
    wp_enqueue_style(
        'daftarchi-landing',
        esc_url($cfg['css_url']),
        array(),
        '1.0.0'
    );
}

function daftarchi_landing_footer_script() {
    if (!daftarchi_landing_should_load_assets()) {
        return;
    }
    $cfg = daftarchi_landing_config();
    $inline = '(function(){var u=' . wp_json_encode($cfg['version_json']) . ';'
        . 'document.querySelectorAll("[data-dl-download]").forEach(function(a){'
        . 'fetch(u,{cache:"no-store"}).then(function(r){return r.json()}).then(function(j){'
        . 'var url=j&&(j.full_url||j.url||j.app_url);if(url)a.href=url;'
        . '}).catch(function(){});});})();';
    echo '<script id="daftarchi-landing-js">' . $inline . '</script>';
}

add_action('wp_footer', 'daftarchi_landing_footer_script');

add_action('wp_enqueue_scripts', 'daftarchi_landing_enqueue_assets');

function daftarchi_landing_body_class($classes) {
    if (daftarchi_landing_should_load_assets()) {
        $classes[] = 'daftarchi-landing-page';
    }
    return $classes;
}

add_filter('body_class', 'daftarchi_landing_body_class');

function daftarchi_landing_register_template($templates) {
    $templates['daftarchi-landing.php'] = 'دفترچی — لندینگ';
    return $templates;
}

add_filter('theme_page_templates', 'daftarchi_landing_register_template');

function daftarchi_landing_template_include($template) {
    if (is_page() && get_page_template_slug() === 'daftarchi-landing.php') {
        $file = __DIR__ . '/daftarchi-landing-template.php';
        if (is_readable($file)) {
            return $file;
        }
    }
    return $template;
}

add_filter('template_include', 'daftarchi_landing_template_include', 99);

function daftarchi_landing_markup() {
    $cfg = daftarchi_landing_config();
    $trial = (int) $cfg['trial_days'];
    $download = esc_url($cfg['download_fallback']);
    $support = esc_url($cfg['support_url']);

    ob_start();
    ?>
<div class="daftarchi-landing" id="daftarchi-landing-top">
  <header class="dl-nav">
    <div class="dl-nav__inner">
      <a class="dl-brand" href="#daftarchi-landing-top">
        <span class="dl-brand__icon" aria-hidden="true">📒</span>
        <span><?php echo esc_html($cfg['brand_name']); ?></span>
      </a>
      <nav class="dl-nav__links" aria-label="ناوبری اصلی">
        <a href="#dl-features">امکانات</a>
        <a href="#dl-business">انواع کسب‌وکار</a>
        <a href="#dl-pricing">اشتراک</a>
        <a href="#dl-faq">سوالات</a>
      </nav>
      <a class="dl-btn dl-btn--primary" data-dl-download href="<?php echo $download; ?>">دانلود رایگان</a>
    </div>
  </header>

  <section class="dl-hero">
    <div class="dl-wrap dl-hero__grid">
      <div>
        <span class="dl-eyebrow">صندوق، انبار و حسابداری — روی رایانه خودتان</span>
        <h1>فروش سریع‌تر، موجودی دقیق‌تر، فروشگاه آنلاین هم‌قدم با حضوری</h1>
        <p class="dl-hero__lead">
          دفترچی نرم‌افزار صندوق فروش (POS)، بارکدخوان، فاکتور و رسید حرارتی، انبار، چک، حسابداری،
          نمودار فروش، کارت‌خوان پرداخت نوین، همگام‌سازی ووکامرس، پیامک، پشتیبان‌گیری و تقویم جلالی است —
          برای هر نوع مغازه و فروشگاه، بدون وابستگی دائم به اینترنت برای فروش نقدی.
        </p>
        <div class="dl-hero__cta">
          <a class="dl-btn dl-btn--primary dl-btn--large" data-dl-download href="<?php echo $download; ?>">شروع <?php echo (int) $trial; ?> روز رایگان</a>
          <a class="dl-btn dl-btn--ghost dl-btn--large" href="#dl-features">مشاهده امکانات</a>
        </div>
        <p class="dl-hero__note">بدون کارت بانکی برای دوره آزمایشی · <strong>تمدید روزانه اشتراک</strong> داخل برنامه · داده روی رایانه خودتان می‌ماند</p>
      </div>
      <aside class="dl-hero__panel" aria-labelledby="dl-hero-panel-title">
        <h2 id="dl-hero-panel-title">چرا صاحبان فروشگاه دفترچی را انتخاب می‌کنند</h2>
        <ul class="dl-checklist">
          <li>فروش با اسکن بارکد یا SKU — کالای ساده و متغیر (رنگ، سایز)</li>
          <li>چاپ فاکتور ۸۰ میلی‌متری و کد یکتا روی هر فاکتور</li>
          <li>موجودی صفر فوراً روی ووکامرس — همگام‌سازی لحظه‌ای قیمت و انبار</li>
          <li>کارت‌خوان روی شبکه محلی؛ رمز مشتری هرگز به نرم‌افزار نمی‌آید</li>
          <li>خواندن عکس فاکتور با هوش مصنوعی (بسته Pro) برای ورود خرید</li>
          <li>پشتیبانی و گزارش خطا از داخل برنامه به تیم وب‌بیکری</li>
        </ul>
      </aside>
    </div>
  </section>

  <div class="dl-trust">
    <div class="dl-wrap dl-trust__grid">
      <div class="dl-trust__item"><strong><?php echo (int) $trial; ?> روز</strong><span>آزمایش رایگان</span></div>
      <div class="dl-trust__item"><strong>RTL</strong><span>تقویم و تاریخ شمسی</span></div>
      <div class="dl-trust__item"><strong>SQLite</strong><span>داده محلی امن</span></div>
      <div class="dl-trust__item"><strong>Woo</strong><span>همگام با ووکامرس</span></div>
    </div>
  </div>

  <section id="dl-business">
    <div class="dl-wrap">
      <div class="dl-section-head">
        <h2>برای هر نوع کسب‌وکار</h2>
        <p>خرده‌فروشی، رستوران، داروخانه، پوشاک، لوازم دیجیتال، سوپرمارکت، آرایشی، کتاب، کافه و ده‌ها vertical دیگر — یک نصب، یک پایگاه داده، چند صندوق هم‌زمان.</p>
      </div>
      <div class="dl-types">
        <div class="dl-type"><span>🛒</span>سوپرمارکت و خواربار</div>
        <div class="dl-type"><span>👗</span>پوشاک و مد</div>
        <div class="dl-type"><span>📱</span>لوازم دیجیتال</div>
        <div class="dl-type"><span>☕</span>کافه و رستوران</div>
        <div class="dl-type"><span>💄</span>آرایشی و بهداشتی</div>
        <div class="dl-type"><span>💊</span>داروخانه</div>
        <div class="dl-type"><span>🔧</span>ابزار و یراق</div>
        <div class="dl-type"><span>📚</span>کتاب و لوازم‌التحریر</div>
        <div class="dl-type"><span>🏠</span>دکور و خانه</div>
        <div class="dl-type"><span>🐾</span>پت‌شاپ</div>
        <div class="dl-type"><span>🚗</span>لوازم خودرو</div>
        <div class="dl-type"><span>🎁</span>هدایا و صنایع‌دستی</div>
      </div>
    </div>
  </section>

  <section id="dl-features">
    <div class="dl-wrap">
      <div class="dl-section-head">
        <h2>امکانات کامل در یک برنامه</h2>
        <p>از لحظهٔ ورود بارکد تا بستن صندوق، ارسال به مودیان و آپدیت خودکار — همه‌چیز برای تیم فروش و مدیر در یک رابط فارسی.</p>
      </div>
      <div class="dl-features">
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">🏪</div>
          <h3>صندوق POS و بارکد</h3>
          <p>سبد فروش، اسکن بارکد فاکتور و کالا، نقد، کارت، درگاه وب‌بیکری، نسیه، لینک پرداخت و QR — با باز و بسته کردن کشوی صندوق.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">🧾</div>
          <h3>فاکتور، رسید و چاپ</h3>
          <p>کد فاکتور یکتا، چاپ حرارتی ۸۰mm، اصلاح و حذف با برگشت موجودی، فیلتر تاریخ شمسی.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">📦</div>
          <h3>انبار و موجودی</h3>
          <p>تعدیل، خرید، کالای متغیر، ورود/خروج اکسل، موجودی جدا برای هر نوع (رنگ، سایز).</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">📊</div>
          <h3>پیشخوان و نمودار فروش</h3>
          <p>جمع فروش روز، هفته و ماه — دید مدیریتی بدون خروج از مغازه.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">💳</div>
          <h3>کارت‌خوان پرداخت نوین</h3>
          <p>اتصال IP روی پورت ۱۳۶۲؛ تا تأیید دستگاه فاکتور ساخته نمی‌شود و بقیه صندوق‌ها باز می‌مانند.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">🌐</div>
          <h3>ووکامرس</h3>
          <p>ارسال و دریافت کالا، push فوری قیمت و موجودی پس از فروش یا تغییر انبار — صفر روی سایت یعنی صفر واقعی.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">📅</div>
          <h3>تقویم جلالی</h3>
          <p>ماه شمسی، شنبه اول هفته، فروش و چک روزانه، سررسید و فیلتر فاکتور با تاریخ فارسی.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">📷</div>
          <h3>اسکن عکس فاکتور (Pro)</h3>
          <p>خواندن فاکتور دست‌نویس یا چاپی با هوش مصنوعی؛ ثبت خرید و افزایش موجودی پس از تأیید شما.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">💬</div>
          <h3>پیامک مشتری</h3>
          <p>شارژ پنل، الگوهای آماده، متغیر نام، مبلغ و شماره فاکتور — از داخل تنظیمات فروشگاه.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">🧮</div>
          <h3>حسابداری و چک</h3>
          <p>حساب‌ها، سند، چک وارده و صادره و وصول — برای کسب‌وکارهایی که فراتر از صندوق ساده نیاز دارند.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">🏛️</div>
          <h3>سامانه مودیان</h3>
          <p>شناسه حافظه، کد اقتصادی و کلید خصوصی در تنظیمات؛ ارسال جدا پس از فروش.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">💾</div>
          <h3>پشتیبان‌گیری و به‌روزرسانی</h3>
          <p>داده در SQLite محلی؛ دریافت نسخه جدید از کانال وب‌بیکری با حفظ فروش‌ها.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">👥</div>
          <h3>نقش‌ها و باشگاه مشتری</h3>
          <p>مدیر، صندوقدار، مسئول موجودی؛ باشگاه مشتری و دریافت طلب.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">🎨</div>
          <h3>ظاهر و حالت فروشگاه</h3>
          <p>تم روشن، تیره، کرم و سبز؛ حالت حضوری، آنلاین یا ترکیبی.</p>
        </article>
        <article class="dl-feature">
          <div class="dl-feature__icon" aria-hidden="true">🛡️</div>
          <h3>پشتیبانی داخل برنامه</h3>
          <p>چت و گزارش خطا با اسکرین‌شات مستقیم به پیشخوان وب‌بیکری — بدون وابستگی به تلگرام.</p>
        </article>
      </div>
    </div>
  </section>

  <section>
    <div class="dl-wrap">
      <div class="dl-section-head">
        <h2>راه‌اندازی در سه قدم</h2>
      </div>
      <div class="dl-steps">
        <div class="dl-step">
          <div>
            <h3>دانلود و نصب</h3>
            <p>بسته ویندوز، مک یا لینوکس را از وب‌بیکری بگیرید و روی رایانه فروشگاه اجرا کنید. آدرس محلی: ۱۲۷.۰.۰.۱:۸۰۸۰</p>
          </div>
        </div>
        <div class="dl-step">
          <div>
            <h3>تنظیم فروشگاه</h3>
            <p>نام مغازه، ووکامرس، کارت‌خوان، درگاه و پیامک را در تنظیمات وارد کنید. <?php echo (int) $trial; ?> روز اول بدون خرید اشتراک کار می‌کند.</p>
          </div>
        </div>
        <div class="dl-step">
          <div>
            <h3>فروش و رشد</h3>
            <p>بارکد بزنید، فاکتور چاپ کنید، موجودی و سایت را همزمان به‌روز نگه دارید. اشتراک و Pro را هر زمان از داخل برنامه تمدید کنید.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="dl-pricing">
    <div class="dl-wrap">
      <div class="dl-section-head">
        <h2>اشتراک شفاف — پرداخت با زیبال</h2>
        <p>پس از دوره آزمایشی، از صفحه لایسنس داخل برنامه یکی از طرح‌ها را انتخاب کنید. تمدید روزانه و شفاف است.</p>
      </div>
      <div class="dl-pricing">
        <article class="dl-plan">
          <h3>سه‌ماهه</h3>
          <p class="dl-plan__price">۶٬۰۰۰٬۰۰۰ تومان</p>
          <p>مناسب شروع و فصل‌های پرفروش</p>
        </article>
        <article class="dl-plan dl-plan--highlight">
          <span class="dl-plan__badge">پرطرفدار</span>
          <h3>شش‌ماهه</h3>
          <p class="dl-plan__price">۱۲٬۰۰۰٬۰۰۰ تومان</p>
          <p>تعادل بهتر هزینه و زمان</p>
        </article>
        <article class="dl-plan">
          <h3>یک‌ساله</h3>
          <p class="dl-plan__price">۱۵٬۰۰۰٬۰۰۰ تومان</p>
          <p>برای فروشگاه‌های پایدار سالانه</p>
        </article>
        <article class="dl-plan">
          <h3>پنجاه‌ساله</h3>
          <p class="dl-plan__price">۱۵٬۰۰۰٬۰۰۰ تومان</p>
          <p>ظرفیت ۵۰ فروش — بسته ویژه</p>
        </article>
      </div>
      <div class="dl-trial-banner">
        <h3>هنوز مطمئن نیستید؟</h3>
        <p><?php echo (int) $trial; ?> روز کامل با همه امکانات صندوق و انبار — بدون پرداخت اولیه.</p>
        <a class="dl-btn dl-btn--ghost dl-btn--large" data-dl-download href="<?php echo $download; ?>">دانلود و شروع آزمایشی</a>
      </div>
    </div>
  </section>

  <section class="dl-proof">
    <div class="dl-wrap">
      <div class="dl-section-head">
        <h2>فروشگاه‌ها چه می‌گویند</h2>
        <p>نمونه‌هایی از نیازهایی که دفترچی در عمل پوشش می‌دهد — جایگزین با نقل‌قول مشتریان واقعی در ویرایشگر وردپرس.</p>
      </div>
      <div class="dl-quotes">
        <figure class="dl-quote">
          <blockquote>«بعد از دفترچی، موجودی سایت با ویترین همیشه یکی است؛ دیگر «موجود بود ولی در سیستم نبود» نداریم.»</blockquote>
          <cite>— مدیر فروشگاه پوشاک (نمونه)</cite>
        </figure>
        <figure class="dl-quote">
          <blockquote>«اسکن بارکد و کارت‌خوان روی شبکه خودمان سرعت صف صندوق را دو برابر کرد.»</blockquote>
          <cite>— صندوقدار سوپرمارکت (نمونه)</cite>
        </figure>
        <figure class="dl-quote">
          <blockquote>«تقویم شمسی و چک‌ها برای حسابداری مغازه‌مان همان چیزی بود که در نرم‌افزارهای خارجی پیدا نمی‌کردیم.»</blockquote>
          <cite>— مالک فروشگاه لوازم خانگی (نمونه)</cite>
        </figure>
      </div>
    </div>
  </section>

  <section id="dl-faq">
    <div class="dl-wrap">
      <div class="dl-section-head">
        <h2>سوالات متداول</h2>
      </div>
      <div class="dl-faq">
        <details>
          <summary>آیا بدون اینترنت هم می‌توان فروخت؟</summary>
          <p>بله — فروش نقدی و محلی به اینترنت وابسته نیست. برای ووکامرس، درگاه، پیامک و خواندن عکس فاکتور به اتصال نیاز دارید.</p>
        </details>
        <details>
          <summary>داده کجا ذخیره می‌شود؟</summary>
          <p>در فایل SQLite روی همان رایانه فروشگاه، در پوشه data کنار برنامه. هر شعبه نصب جدا دارد.</p>
        </details>
        <details>
          <summary>چند صندوق هم‌زمان؟</summary>
          <p>چند مرورگر یا رایانه روی شبکه محلی می‌توانند به یک نصب وصل شوند؛ برای چند شعبه فیزیکی نصب جدا لازم است.</p>
        </details>
        <details>
          <summary>تفاوت اشتراک و بسته Pro چیست؟</summary>
          <p>اشتراک برای استفاده از خود برنامه پس از دوره آزمایشی است. Pro توکن جدا برای خواندن عکس فاکتور با هوش مصنوعی دارد.</p>
        </details>
        <details>
          <summary>آپدیت چطور می‌آید؟</summary>
          <p>از منوی به‌روزرسانی داخل برنامه، از کانال customer روی webakery.ir. فروش‌ها حفظ می‌شوند.</p>
        </details>
      </div>
    </div>
  </section>

  <section class="dl-cta-final">
    <div class="dl-wrap">
      <h2>همین امروز صندوق خود را حرفه‌ای کنید</h2>
      <p>دانلود رایگان · <?php echo (int) $trial; ?> روز آزمایش · پشتیبانی <?php echo esc_html($cfg['org_name']); ?></p>
      <a class="dl-btn dl-btn--primary dl-btn--large" data-dl-download href="<?php echo $download; ?>">دانلود دفترچی</a>
    </div>
  </section>

  <footer class="dl-footer">
    <p>
      <?php echo esc_html($cfg['brand_name']); ?> · محصول <?php echo esc_html($cfg['org_name']); ?>
      · <a href="<?php echo $support; ?>"><?php echo esc_html(parse_url($support, PHP_URL_HOST) ?: 'webakery.ir'); ?></a>
    </p>
  </footer>
</div>
    <?php
    return ob_get_clean();
}

function daftarchi_landing_shortcode() {
    return daftarchi_landing_markup();
}

add_shortcode('daftarchi_landing', 'daftarchi_landing_shortcode');