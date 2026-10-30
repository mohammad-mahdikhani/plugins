# دیپلوی قالب پاکن منتال

## حالت پیش‌فرض: Elementor (قابل ویرایش دستی)

```bash
PYTHONPATH=/workspace/scripts python3 -m pm_deploy.deploy_all
```

یا مستقیم:

```bash
PYTHONPATH=/workspace/scripts python3 -m pm_deploy.deploy_elementor
```

این دستور:

- رنگ‌ها و **کلاس‌های سراسری Elementor** (Kit) را همگام می‌کند
- سکشن‌های کتابخانه **PM Header Elementor** (ID 124) و **PM Footer Elementor** (ID 125) می‌سازد
- صفحات **خانه، فروشگاه، سبد، مجله، تک‌محصول** را با **سه ویجت HTML** پر می‌کند (هدر / **محتوای قابل ویرایش** / فوتر) — همان `pm.css` + Kit رنگ‌های Elementor
- در Elementor ویجت **وسط** را برای تغییر متن و بخش‌های صفحه ویرایش کنید؛ ویجت **اول** شامل هدر و اسلات `data-pm-elementor-slot="mega-promo"` برای بنر مگامenu

### ویرایش در پنل

| بخش | مسیر |
|-----|------|
| صفحه اصلی | wp-admin → Pages → خانه → Edit with Elementor |
| فروشگاه / آرشیو | Pages → فروشگاه |
| تک محصول (نمونه) | Pages → نمونه محصول |
| هدر / فوتر جدا | Templates → Saved Templates → PM Header Elementor / PM Footer Elementor |

پس از نصب **Elementor Pro** می‌توانید همان هدر/فوتر را در Theme Builder به‌صورت سراسری assign کنید.

### 404 (همهٔ آدرس‌های ناموجود)

قالب فرزند **`hello-pakan`** را نصب و فعال کنید (بدون افزونهٔ جدا):

```bash
PYTHONPATH=/workspace/scripts python3 -m pm_deploy.build_theme_404
```

سپس zip را در **ظاهر → پوسته‌ها → بارگذاری** آپلود کنید.

### افزونه `pakanmental-home` (اینماد + REST)

برای **تنظیم اینماد از پیشخوان** (منوی «پاکن منتال»)، پوشه `pakanmental-home` را روی سرور در `wp-content/plugins/` به‌روز کنید (نسخه **1.1.0+**) و افزونه را فعال کنید. سپس یک بار دیپلوی Elementor را اجرا کنید تا JS فوتر/مگامenu در HTML صفحات باشد.

## حالت legacy (HTML block)

برای بازگشت به بلوک HTML یک‌تکه، در `deploy_all.py` خط `deploy_elementor()` را بردارید و مسیر `upsert_page` را فعال کنید.
