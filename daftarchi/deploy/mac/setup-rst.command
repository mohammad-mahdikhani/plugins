#!/bin/bash
cd "$(dirname "$0")"
HERE="$(pwd)"
echo "دفترچی در حال نصب روی مک است..."
mkdir -p "$HOME/Applications"
STAGE="$HOME/Applications/.daftarchi-install"
rm -rf "$STAGE"
mkdir -p "$STAGE"
unzip -q -o "$HERE/payload.zip" -d "$STAGE"
SRC="$STAGE/Daftarchi-rst-Mac"
DEST="$HOME/Applications/Daftarchi-rst"
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
LAUNCH="$HOME/Desktop/دفترچی.command"
cat > "$LAUNCH" << EOF
#!/bin/bash
exec "$DEST/Start-Daftarchi.command"
EOF
chmod +x "$LAUNCH"
xattr -dr com.apple.quarantine "$LAUNCH" 2>/dev/null || true
echo
echo "نصب شد. دفترچی الان باز می‌شود."
echo "دفعه بعد فایل دفترچی.command را از دسکتاپ باز کنید."
echo "ورود اولیه: 09120000000 / daftarchi"
echo
exec "$DEST/Start-Daftarchi.command"
