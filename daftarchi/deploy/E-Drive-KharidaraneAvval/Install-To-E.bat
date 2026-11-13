@echo off
chcp 65001 >nul
setlocal
set "TARGET=E:\Daftarchi\KharidaraneAvval"
echo.
echo === نصب دفترچی — ۲۰ مشتری VIP ===
echo مقصد: %TARGET%
echo.
if not exist "%TARGET%" mkdir "%TARGET%"
xcopy /E /Y /I "%~dp0*" "%TARGET%\" >nul
if errorlevel 1 (
  echo کپی ناموفق بود.
  pause
  exit /b 1
)
echo.
echo نصب روی درایو E انجام شد.
echo.
echo   %TARGET%\Daftarchi.exe          فایل برنامه
echo   %TARGET%\sites\01-webakery\      اجرای فروشگاه نمونه
echo.
echo برای هر فروشگاه، Run-Daftarchi.bat داخل همان پوشه را بزنید.
echo preset.json را ویرایش کنید؛ بار اول تنظیمات اعمال می‌شود.
echo.
pause
