دفترچی روی هاست ضعیف (موازی با LAN)
=====================================

مسیر اصلی مغازه عوض نمی‌شود: PC صندوق + Start-Daftarchi.bat + http://IP:8080
این پوشه فقط وقتی است که بخواهید همان برنامه را روی هاست ضعیف
(cPanel اشتراکی یا VPS با ۵۱۲ مگ تا ۱ گیگ) هم بالا بیاورید.

راهنمای کامل:
  daftarchi/docs/daftarchi-light-host.md

خلاصه VPS:
  export DAFTARCHI_LIGHT=1
  ./deploy/light-host/start-light.sh
  gunicorn: ۱ worker، SQLite WAL، بدون استخر ترد

کنار وردپرس webakery.ir:
  وردپرس روی دامنه اصلی می‌ماند.
  ساب‌دامین pos.webakery.ir را به gunicorn روی 127.0.0.1:8080 پروکسی کنید.
  اگر هاست فقط PHP/Apache دارد: php-proxy.php را ریشهٔ همان ساب‌دامین بگذارید.
