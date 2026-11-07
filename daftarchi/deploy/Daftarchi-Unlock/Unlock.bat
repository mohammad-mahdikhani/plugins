@echo off
cd /d "%~dp0"
echo Close Daftarchi, then this window will unlock it.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0Unlock.ps1" %*
echo.
echo Log: %~dp0unlock-log.txt
pause
