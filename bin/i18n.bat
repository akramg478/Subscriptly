@echo off
setlocal

set PLUGIN_DIR=%~dp0..
cd /d "%PLUGIN_DIR%"

set DOMAIN=subscriptly
set LANG_DIR=languages
set POT=%LANG_DIR%\%DOMAIN%.pot

if "%1"=="" goto usage
if /i "%1"=="pot" goto pot
if /i "%1"=="mo" goto mo
if /i "%1"=="all" goto all
if /i "%1"=="new" goto new
goto usage

:pot
echo Generating %POT% ...
wp i18n make-pot . %POT% --domain=%DOMAIN% --exclude=vendor,tests,node_modules
if errorlevel 1 exit /b 1
echo Done.
exit /b 0

:mo
echo Compiling .mo files in %LANG_DIR% ...
wp i18n make-mo %LANG_DIR%
if errorlevel 1 exit /b 1
echo Done.
exit /b 0

:all
call :pot
if errorlevel 1 exit /b 1
call :mo
exit /b 0

:new
if "%2"=="" (
  echo Usage: i18n.bat new LOCALE
  echo Example: i18n.bat new fr_FR
  exit /b 1
)
set LOCALE=%2
set PO=%LANG_DIR%\%DOMAIN%-%LOCALE%.po
if exist "%PO%" (
  echo File already exists: %PO%
  exit /b 1
)
copy /y "%POT%" "%PO%" >nul
echo Created %PO%
echo Edit translations, then run: i18n.bat mo
exit /b 0

:usage
echo Subscriptly translation tools
echo.
echo   i18n.bat pot          Generate languages/subscriptly.pot
echo   i18n.bat new fr_FR    Create a new .po from the .pot template
echo   i18n.bat mo           Compile all .po files to .mo
echo   i18n.bat all          Regenerate .pot and compile .mo files
exit /b 0
