@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo دفترچی را کامل ببندید (خروج)، بعد این فایل را اجرا کنید.
echo.
if not exist "%~dp0sqlite3.exe" (
  echo sqlite3.exe کنار همین فایل نیست.
  pause
  exit /b 1
)
if not exist "%~dp0unlock.sql" (
  echo unlock.sql کنار همین فایل نیست.
  pause
  exit /b 1
)

set "FOUND=0"
if not "%~1"=="" (
  call :unlock "%~1"
  goto finish
)

call :scan "%USERPROFILE%"
call :scan "%LOCALAPPDATA%"
call :scan "%APPDATA%"
call :scan "C:\Daftarchi"
call :scan "D:\Daftarchi"
call :scan "E:\Daftarchi"
call :scan "C:\Program Files"
call :scan "C:\Program Files (x86)"

:finish
if "%FOUND%"=="0" (
  echo.
  echo daftarchi.db پیدا نشد.
  echo فایل daftarchi.db را روی همین bat بکشید، یا این پوشه را کنار Daftarchi.exe بگذارید.
)
echo.
pause
exit /b 0

:scan
if not exist "%~1" goto :eof
for /f "delims=" %%F in ('dir /s /b "%~1\daftarchi.db" 2^>nul') do call :unlock "%%F"
goto :eof

:unlock
echo باز کردن: %~1
"%~dp0sqlite3.exe" "%~1" < "%~dp0unlock.sql"
if errorlevel 1 (
  echo خطا. برنامه دفترچی هنوز باز است؟ اول خروج بزنید و دوباره اجرا کنید.
) else (
  echo انجام شد. دفترچی را دوباره باز کنید.
  set "FOUND=1"
)
echo.
goto :eof
