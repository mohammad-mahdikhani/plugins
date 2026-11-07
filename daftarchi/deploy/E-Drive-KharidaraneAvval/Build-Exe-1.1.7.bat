@echo off
chcp 65001 >nul
echo برای preset خودکار و نسخه 1.1.7، exe را از همین سورس بسازید.
echo سورس: مخزن plugins / پوشه daftarchi یا one-project
echo.
echo روی ویندوز با Python 3.12:
echo   pip install -r requirements.txt pyinstaller
echo   pyinstaller --onefile --name Daftarchi --add-data "static;static" --add-data "templates;templates" app.py
echo.
echo خروجی dist\Daftarchi.exe را جایگزین Daftarchi.exe کنید.
pause
