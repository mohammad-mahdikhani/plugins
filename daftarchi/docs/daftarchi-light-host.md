# هاست سبک دفترچی (موازی با LAN مغازه)

مسیر **A** دست‌نخورده می‌ماند: PC صندوق + `Start-Daftarchi.bat` + `http://IP:8080` روی Wi‑Fi.

مسیر **B** اختیاری است: همان Flask + SQLite روی **هاست ضعیف** (cPanel اشتراکی یا VPS با ۵۱۲ مگ تا ۱ گیگ) تا از اینترنت باز شود.

| مسیر | نقش |
|------|-----|
| **LAN مغازه (اصلی)** | Waitress، ۱۶ ترد، `0.0.0.0:8080`، آیکون دسکتاپ، مرورگر |
| **هاست سبک (موازی)** | gunicorn **یک** worker، SQLite WAL، بدون استخر ترد |

`DAFTARCHI_LIGHT` را روی PC صندوق نگذارید. نصب ویندوز مشتری همان است.

## RAM و چرا یک worker

SQLite یک نویسنده دارد. چند worker گunicorn روی همان `daftarchi.db` قفل و RAM اضافه می‌آورد.

| قطعه | بودجه تقریبی |
|------|----------------|
| یک CPython + Flask + gunicorn | ۸۰ تا ۱۵۰ مگ |
| کش SQLite در حالت سبک | حدود ۲ مگ (`PRAGMA cache_size=-2000`) |
| وردپرس + MySQL روی همان باکس ۱ گیگ | تنگ است؛ ساب‌دامین یا VPS جدا بهتر است |
| سقف systemd نمونه | `MemoryMax=280M` |

پروفایل سبک:

- `gunicorn`: `workers = 1`، `threads = 1`، `worker_class = sync`
- SQLite: `journal_mode=WAL` (همان LAN)، کش کوچک‌تر
- بدون ترد آیکون دسکتاپ و بدون باز کردن مرورگر
- حلقه بکاپ روزانه همان یک ترد خوابیده است، استخر نیست
- bind پیش‌فرض `127.0.0.1` (پشت پروکسی)، نه کل LAN

اگر gunicorn نصب نباشد، `python app.py` با `DAFTARCHI_LIGHT=1` Waitress را با **۱** ترد بالا می‌آورد — باز هم LAN پیش‌فرض نیست مگر خودتان env را بگذارید.

## کنار وردپرس webakery.ir یا ساب‌دامین

صفحه دفترچی `/api/` و `/static/` را از **ریشهٔ origin** می‌خواند. پوشهٔ `/daftarchi/` روی دامنهٔ وردپرس این مسیرها را به WordPress می‌دهد و برنامه می‌شکند.

**کار درست:** وردپرس روی `https://webakery.ir` بماند. دفترچی روی **ساب‌دامین** جدا.

```
https://webakery.ir          → WordPress / ووکامرس (بدون تغییر)
https://pos.webakery.ir      → gunicorn 127.0.0.1:8080  (یا پروکسی PHP)
```

در cPanel: Subdomains → `pos` روی `webakery.ir`. SSL همان AutoSSL.

چاپ حرارتی و کارت‌خوان روی این هاست کار نمی‌کنند (سخت‌افزار مغازه). موجودی، قیمت، فاکتور و ورود از مرورگر روی اینترنت کار می‌کنند. صندوق حضوری همان LAN است.

## VPS ۵۱۲ مگ / ۱ گیگ (SSH)

```bash
sudo useradd --system --home /opt/daftarchi --shell /usr/sbin/nologin daftarchi
sudo mkdir -p /opt/daftarchi
# سورس daftarchi/ را اینجا بگذارید
sudo python3 -m venv /opt/daftarchi/.venv
sudo /opt/daftarchi/.venv/bin/pip install -r /opt/daftarchi/requirements-light.txt
sudo mkdir -p /opt/daftarchi/data
sudo chown -R daftarchi:daftarchi /opt/daftarchi

sudo cp /opt/daftarchi/deploy/light-host/daftarchi.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now daftarchi
curl -sS http://127.0.0.1:8080/api/health
# {"ok": true, "name": "دفترچی", "host": "light", "sqlite": "wal", "threads": 1, ...}
```

بدون systemd:

```bash
export DAFTARCHI_LIGHT=1 DAFTARCHI_DATA=/opt/daftarchi/data
/opt/daftarchi/deploy/light-host/start-light.sh
```

سپس nginx یا Apache را از `deploy/light-host/nginx-subdomain.conf` یا `apache-subdomain.conf` روی `pos.webakery.ir` بگذارید. گواهی: `certbot` یا AutoSSL.

ورود اول همان LAN است: `09120000000` / `daftarchi` — رمز را عوض کنید.

## cPanel با «Setup Python App» (Passenger)

اگر پنل Python دارد:

1. Application root = پوشهٔ `daftarchi`
2. Startup file = `passenger_wsgi.py`
3. متغیرها: `DAFTARCHI_LIGHT=1`، `DAFTARCHI_DATA=…/data`، `DAFTARCHI_OPEN_BROWSER=0`، `DAFTARCHI_BIND=127.0.0.1`
4. Passenger را روی **یک** process نگه دارید (معادل یک worker)
5. دامنهٔ اپ را همان ساب‌دامین `pos.webakery.ir` بگذارید، نه document root وردپرس

`passenger_wsgi.py` فقط `wsgi:application` را صدا می‌زند؛ `wsgi.py` خودش `DAFTARCHI_LIGHT=1` می‌گذارد.

## فقط PHP / Apache (بدون Python عمومی)

خیلی از هاست‌های اشتراکی ایران Flask را در `public_html` اجرا نمی‌کنند. دو راه:

### ۱) پروکسی Apache (`mod_proxy`)

اگر WHM/Apache اجازهٔ `ProxyPass` داد، همان `apache-subdomain.conf`: ساب‌دامین → `http://127.0.0.1:8080`. هنوز باید gunicorn را با SSH یا Passenger روی همان سرور (یا VPS کناری) روشن کنید.

### ۲) `php-proxy.php` — وقتی ProxyPass بسته است

1. Python را روی **همین** ماشین (SSH) یا یک VPS ۵۱۲ مگ با `start-light.sh` روشن کنید.
2. در cPanel برای `pos.webakery.ir` یک document root خالی بسازید (جدا از `public_html` وردپرس).
3. `php-proxy.php` را به نام `index.php` آنجا بگذارید، `proxy-config.php` و `htaccess-subdomain.txt` را به `.htaccess` کنارش.
4. اگر gunicorn همان سرور است:

```php
return array(
    'backend' => 'http://127.0.0.1:8080',
    'allow_remote' => false,
    'timeout' => 60,
);
```

5. اگر Python روی VPS دیگری است، `backend` را URL همان VPS بگذارید و `allow_remote => true`. آدرس را از query نگیرید؛ فقط از این فایل.

وردپرس در `public_html/` دست نمی‌خورد. REST ووکامرس `/wp-json/` روی دامنهٔ اصلی می‌ماند.

## متغیرهای محیط

| متغیر | LAN پیش‌فرض | هاست سبک |
|--------|-------------|----------|
| `DAFTARCHI_LIGHT` | خاموش | `1` |
| `DAFTARCHI_BIND` | `0.0.0.0` | `127.0.0.1` |
| `DAFTARCHI_THREADS` | ۱۶ | ۱ |
| `DAFTARCHI_OPEN_BROWSER` | روی ویندوز `1` | `0` |
| `DAFTARCHI_LAN_CORS` | `1` | `0` (همان origin روی HTTPS) |
| `DAFTARCHI_COOKIE_SECURE` | — | `1` پشت HTTPS |
| `DAFTARCHI_DATA` | `data/` کنار برنامه | مسیر قابل نوشتن خارج از `public_html` اگر ممکن است |
| `PORT` | `8080` | `8080` فقط localhost |

## چیزی که این مسیر نیست

- جایگزین صندوق LAN نیست.
- چند فروشگاه روی یک SQLite هنوز نیست.
- چاپ و کارت‌خوان روی هاست ابری نیست.
- پوشهٔ `/pos/` روی `webakery.ir` بدون بازنویسی کل `/api` و `/static` کار نمی‌کند.

## فایل‌ها

| مسیر | نقش |
|------|-----|
| `wsgi.py` / `passenger_wsgi.py` | ورود gunicorn و cPanel |
| `requirements-light.txt` | Flask LAN + gunicorn |
| `deploy/light-host/gunicorn.conf.py` | ۱ worker سنک |
| `deploy/light-host/start-light.sh` | اجرا |
| `deploy/light-host/daftarchi.service` | systemd |
| `deploy/light-host/apache-subdomain.conf` | Apache کنار وردپرس |
| `deploy/light-host/nginx-subdomain.conf` | nginx |
| `deploy/light-host/php-proxy.php` | فال‌بک PHP |
