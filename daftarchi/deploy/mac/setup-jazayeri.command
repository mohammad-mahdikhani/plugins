#!/bin/bash
cd "$(dirname "$0")"
HERE="$(pwd)"
echo "دفترچی آرقای جزایری در حال نصب روی مک است..."
mkdir -p "$HOME/Applications"
STAGE="$HOME/Applications/.daftarchi-jazayeri-install"
rm -rf "$STAGE"
mkdir -p "$STAGE"
unzip -q -o "$HERE/payload.zip" -d "$STAGE"
SRC="$STAGE/Daftarchi-Jazayeri"
DEST="$HOME/Applications/Daftarchi-Jazayeri"
if [ ! -d "$SRC" ]; then
  echo "فایل نصب داخل دیسک کامل نیست."
  read -r _
  exit 1
fi
rm -rf "$DEST"
mv "$SRC" "$DEST"
rm -rf "$STAGE"
xattr -dr com.apple.quarantine "$DEST" 2>/dev/null || true
chmod +x "$DEST/Start-Daftarchi.command" 2>/dev/null || true
find "$DEST/python-arm64/bin" "$DEST/python-x64/bin" -type f -exec chmod +x {} \; 2>/dev/null || true
LAUNCH="$HOME/Desktop/دفترچی-آرقای-جزایری.command"
cat > "$LAUNCH" << EOF
#!/bin/bash
exec "$DEST/Start-Daftarchi.command"
EOF
chmod +x "$LAUNCH"
xattr -dr com.apple.quarantine "$LAUNCH" 2>/dev/null || true
echo
echo "نصب شد. دفترچی الان باز می‌شود."
echo "دفعه بعد فایل دفترچی-آرقای-جزایری.command را از دسکتاپ باز کنید."
echo "ورود اولیه: 09120000000 / daftarchi"
echo "این نسخه برای آرقای جزایری است و محدودیت ۷ روز ندارد."
echo
exec "$DEST/Start-Daftarchi.command"
