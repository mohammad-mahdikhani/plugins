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
- صفحات **خانه، فروشگاه (آرشیو)، سبد، مجله، تک‌محصول** را با ویجت‌های V4 المنتور پر می‌کند
- بلوک HTML قدیمی را از محتوای وردپرس حذف می‌کند (`elementor_canvas`)

### ویرایش در پنل

| بخش | مسیر |
|-----|------|
| صفحه اصلی | wp-admin → Pages → خانه → Edit with Elementor |
| فروشگاه / آرشیو | Pages → فروشگاه |
| تک محصول (نمونه) | Pages → نمونه محصول |
| هدر / فوتر جدا | Templates → Saved Templates → PM Header Elementor / PM Footer Elementor |

پس از نصب **Elementor Pro** می‌توانید همان هدر/فوتر را در Theme Builder به‌صورت سراسری assign کنید.

## حالت legacy (HTML block)

برای بازگشت به بلوک HTML یک‌تکه، در `deploy_all.py` خط `deploy_elementor()` را بردارید و مسیر `upsert_page` را فعال کنید.
