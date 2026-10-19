# قالب فرزند Hello Elementor — الینور (Beauties / #341555)

این پکیج برای ساخت **صفحه اصلی** شبیه مرجع Beauties روی **Hello Elementor** + **Elementor** است.

## پیش‌نیاز

1. نصب و فعال‌سازی [Hello Elementor](https://wordpress.org/themes/hello-elementor/) (قالب والد)
2. افزونه **Elementor** (روی elinorstore.com نسخه 4.x فعال است)
3. **WooCommerce** برای شورت‌کدهای محصولات

## نصب قالب فرزند

1. پوشه `hello-elementor-elinor` را در `wp-content/themes/` کپی کنید (یا ZIP و از **ظاهر → قالب‌ها → افزودن**).
2. **Hello Elementor** را فعال نگه دارید؛ سپس **Elinor Hello Elementor Child** را فعال کنید.
3. اگر الان **Brandina** یا Woodmart فعال است، بعد از آماده شدن صفحه، قالب را به Hello Elementor عوض کنید.

## رنگ اصلی در Elementor

در **Elementor → Site Settings → Global Colors**:

| نام | مقدار |
|-----|--------|
| Primary | `#341555` |
| Secondary | `#e8a0c8` |
| Text | `#2b2233` |
| Accent | `#f3eaf8` |

در **Site Settings → Typography** فونت **Vazirmatn** را برای Body و Headings انتخاب کنید.

## فعال‌سازی صفحه اصلی (یکی از این روش‌ها)

### روش ۱ — قالب صفحه (سریع‌ترین)
1. صفحه «خانه» را ویرایش کنید.
2. در سایدبار: **قالب → الینور Beauties — صفحه اصلی**.
3. به‌روزرسانی کنید.

### روش ۲ — Elementor + شورت‌کود
1. **Import** فایل `elementor-templates/elinor-home-beauties.json`
2. صفحه را با **Elementor Canvas** باز کنید.
3. تمپلیت import شده فقط شامل `[elinor_beauties_home]` است (هدر، هیرو، محصولات، فوتر کامل).

### روش ۳ — شورت‌کود دستی
در ویجت **Shortcode** Elementor بنویسید: `[elinor_beauties_home]`

### پیش‌نمایش استاتیک (بدون وردپرس)
فایل `preview/index.html` را در مرورگر باز کنید (محصولات از API زنده elinorstore).

## تنظیم صفحه اصلی وردپرس

**تنظیمات → خواندن → یک صفحه ثابت** → صفحه «خانه».

## کلاس CSS در Elementor

روی سکشن‌ها در تب **Advanced → CSS Classes** می‌توانید کلاس‌های آماده را بگذارید:

- `ebh-hero` — بخش هیرو
- `ebh-section` — سکشن معمولی
- `ebh-section--muted` — پس‌زمینه روشن
- `ebh-deal` — بلوک پیشنهاد ویژه (بنفش `#341555`)

استایل‌ها در `assets/css/beauties-341555.css` فقط روی **صفحه اصلی** (`is_front_page`) بارگذاری می‌شوند.

## هدر و فوتر

با Hello Elementor معمولاً از **Elementor Theme Builder** هدر/فوتر جدا می‌سازید، یا از هدر پیش‌فرض Hello Elementor استفاده می‌کنید.

برای ظاهر شبیه mockup: منو، جستجو، سبد و دکمه ورود را در **Theme Builder → Header** طراحی کنید.

## یادداشت درباره سایت فعلی

در زمان بررسی، elinorstore از **Brandina + Elementor** استفاده می‌کرد. برای «قالب Hello Elementor» باید قالب فعال را به Hello Elementor (و این child) تغییر دهید؛ محتوای Elementor صفحات معمولاً حفظ می‌شود.
