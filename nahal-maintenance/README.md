# صفحه تعمیر سه‌بعدی نهال

افزونه وردپرس برای [nahal.website](https://nahal.website). وقتی سایت در حال به‌روزرسانی است، بازدیدکننده به‌جای صفحه سفید یک صحنه شیشه‌ای سه‌بعدی می‌بیند.

مدیر همچنان وارد پیشخوان می‌شود. با غیرفعال‌کردن افزونه، حالت تعمیر هم خاموش می‌شود.

## نصب

فایل آماده: [`../dist/nahal-maintenance.zip`](../dist/nahal-maintenance.zip)

در پیشخوان: **افزونه‌ها ← افزودن ← بارگذاری افزونه**.

راهنما: `INSTALL-FA.txt` و `GUIDE-FA.txt`

## تست بدون وردپرس

```bash
php nahal-maintenance/tests/run.php
php nahal-maintenance/tests/wp-stub.php
php nahal-maintenance/tests/build-preview.php
```

پیش‌نمایش صفحه:

```bash
php -S 127.0.0.1:8766 -t nahal-maintenance
```

سپس `http://127.0.0.1:8766/tests/preview-maintenance.html` را باز کنید.
