# دفترچی — تغییرات نسخه 1.1.7

این پوشه همان تغییرات شاخه `cursor/instant-woo-pos-receipt-3e51` مخزن `one-project` است (فایل‌های اصلی اپ).

## خلاصه

1. **همگام‌سازی فوری ووکامرس** — با گزینه «همگام‌سازی فوری قیمت و موجودی با سایت» در تنظیمات؛ بعد از تغییر قیمت/موجودی، خرید، فروش و ورود اکسل، ظرف حدود ۰.۳۵ ثانیه به ووکامرس push می‌شود (فقط کالاهایی که `woo_id` دارند).
2. **صندوق و بارکد** — اسکن بارکد/SKU برای کالای ساده یا متغیر (انتخاب نوع)؛ بازخورد toast.
3. **لینک پرداخت / QR** — دکمه «لینک / QR» در صندوق و API `POST /api/payments/quick-link`.
4. **فاکتور ۸ سانتی‌متری** — چاپ ساده با `@page 80mm` و فیلد یکتای `invoice_code` روی هر فاکتور.

برای انتشار: `version.json` را کنار `mail.php` روی هاست بگذارید و exe جدید بسازید.

## بسته ۲۰ مشتری VIP (درایو E)

- `deploy/E-Drive-KharidaraneAvval.zip` — کل بسته + `Daftarchi.exe`
- روی ویندوز: Extract → `Install-To-E.bat` → مسیر `E:\Daftarchi\KharidaraneAvval\`
- هر سایت: `sites\01-webakery\` … `20-vip-20\` با `preset.json` و `version-host.json` جدا
- اجرا: `Run-Daftarchi.bat` داخل همان پوشه (داده در `data\` جدا است)
- برای preset خودکار و 1.1.7: `Build-Exe-1.1.7.bat` را ببینید و exe را از سورس بسازید
