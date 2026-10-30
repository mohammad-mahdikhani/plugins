@echo off
chcp 65001 >nul
set "TARGET=E:\Daftarchi\Test"
echo نصب کیت تست در %TARGET%
if not exist "%TARGET%" mkdir "%TARGET%"
xcopy /E /Y /I "%~dp0*" "%TARGET%\"
echo.
echo تمام. اجرا: %TARGET%\Run-Test.bat
echo ورود اول: 09120000000 / daftarchi
echo نام روی فاکتور: فروشگاه تست
pause
