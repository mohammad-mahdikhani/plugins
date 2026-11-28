#!/usr/bin/env python3
"""Build static preview HTML for Daftarchi landing (no WordPress)."""
from pathlib import Path

root = Path(__file__).resolve().parent
php = (root / "daftarchi-landing.php").read_text(encoding="utf-8")
start = php.index('<div class="daftarchi-landing"')
end = php.rindex("</div>\n    <?php")
html = php[start:end + len("</div>")]
replacements = [
    ("<?php echo (int) $trial; ?>", "7"),
    ("<?php echo esc_html($cfg['brand_name']); ?>", "دفترچی"),
    ("<?php echo esc_html($cfg['org_name']); ?>", "وب‌بیکری"),
    ("<?php echo esc_html(parse_url($support, PHP_URL_HOST) ?: 'webakery.ir'); ?>", "webakery.ir"),
]
import re

html = re.sub(r"<\?php echo esc_url\(\$cfg\['download_fallback'\]\); \?>", "https://webakery.ir/wp-content/uploads/daftarchi-releases/", html)
html = re.sub(r"<\?php echo \$download; \?>", "https://webakery.ir/wp-content/uploads/daftarchi-releases/", html)
html = re.sub(r"<\?php echo esc_url\(\$cfg\['support_url'\]\); \?>", "https://webakery.ir", html)
html = re.sub(r"<\?php echo \$support; \?>", "https://webakery.ir", html)
for a, b in replacements:
    html = html.replace(a, b)
html = re.sub(r"<\?php[^?]*\?>", "", html)

doc = f"""<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>دفترچی — پیش‌نمایش لندینگ</title>
  <link rel="stylesheet" href="daftarchi-landing.css">
</head>
<body>
{html}
</body>
</html>
"""
out = root / "daftarchi-landing-preview.html"
out.write_text(doc, encoding="utf-8")
print(f"Wrote {out} ({len(doc)} bytes)")