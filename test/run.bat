@echo off
setlocal enabledelayedexpansion

echo ===================================================
echo Running snpdf test suite on Windows
echo ===================================================

rem Resolve paths from this script's own location so the test works no matter
rem which directory it is launched from.
set "ROOT=%~dp0.."

if exist "%ROOT%\snpdf.exe" (
    set "BIN=%ROOT%\snpdf.exe"
) else (
    echo Error: snpdf.exe not found in %ROOT%. Please build it first:
    echo   go build -ldflags="-s -w" -o snpdf.exe .
    exit /b 1
)

"%BIN%" ^
  --content "%ROOT%\test\content.html" ^
  --header "%ROOT%\test\header.html" ^
  --footer "%ROOT%\test\footer.html" ^
  --watermark "%ROOT%\test\watermark.html" ^
  --watermark-opacity 0.35 ^
  --output "%ROOT%\test\output.pdf" ^
  --paper A4 ^
  --margin 5mm ^
  --header-height 25mm ^
  --footer-height 15mm ^
  --title "snpdf test document" ^
  --orientation portrait

if %ERRORLEVEL% equ 0 (
    echo.
    echo ===================================================
    echo SUCCESS Output PDF generated at test\output.pdf
    echo ===================================================
) else (
    echo.
    echo ===================================================
    echo FAILED with exit code %ERRORLEVEL%
    echo ===================================================
    exit /b %ERRORLEVEL%
)
