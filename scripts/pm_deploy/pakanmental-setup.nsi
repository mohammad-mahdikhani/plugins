Unicode True

!include "MUI2.nsh"

!ifndef ZIPFILE
  !error "ZIPFILE is required"
!endif
!ifndef GUIDEFILE
  !error "GUIDEFILE is required"
!endif
!ifndef OUTFILE
  !error "OUTFILE is required"
!endif

Name "پاکان منتال"
OutFile "${OUTFILE}"
InstallDir "$DESKTOP\PakanMental"
RequestExecutionLevel user
ShowInstDetails show

!define MUI_ABORTWARNING
!define MUI_WELCOMEPAGE_TITLE "نصب فایل افزونه پاکان منتال"
!define MUI_WELCOMEPAGE_TEXT "این برنامه فایل نصب افزونه را روی ویندوز شما می‌گذارد.$\r$\n$\r$\nبعد از پایان، در پیشخوان وردپرس بروید به افزونه‌ها، افزودن، بارگذاری افزونه و فایل pakanmental-shell.zip را انتخاب کنید.$\r$\n$\r$\n$_CLICK"
!define MUI_FINISHPAGE_TITLE "فایل نصب آماده است"
!define MUI_FINISHPAGE_TEXT "فایل pakanmental-shell.zip و راهنمای فارسی در پوشه انتخاب‌شده قرار گرفت.$\r$\n$\r$\nبرای نصب روی سایت، همان zip را از پیشخوان وردپرس بارگذاری کنید."
!define MUI_FINISHPAGE_RUN
!define MUI_FINISHPAGE_RUN_TEXT "باز کردن پوشه فایل نصب"
!define MUI_FINISHPAGE_RUN_FUNCTION OpenInstallFolder

!insertmacro MUI_PAGE_WELCOME
!insertmacro MUI_PAGE_DIRECTORY
!insertmacro MUI_PAGE_INSTFILES
!insertmacro MUI_PAGE_FINISH
!insertmacro MUI_UNPAGE_CONFIRM
!insertmacro MUI_UNPAGE_INSTFILES
!insertmacro MUI_LANGUAGE "Farsi"

Function OpenInstallFolder
  ExecShell "open" "$INSTDIR"
FunctionEnd

Section "نصب" SecMain
  SetOutPath "$INSTDIR"
  File "/oname=pakanmental-shell.zip" "${ZIPFILE}"
  File "/oname=راهنما.txt" "${GUIDEFILE}"
  WriteUninstaller "$INSTDIR\حذف.exe"
SectionEnd

Section "Uninstall"
  Delete "$INSTDIR\pakanmental-shell.zip"
  Delete "$INSTDIR\راهنما.txt"
  Delete "$INSTDIR\حذف.exe"
  RMDir "$INSTDIR"
SectionEnd
