@echo off
setlocal enabledelayedexpansion

echo ===================================================
echo Running snpdf test suite on Windows
echo ===================================================

if not exist "..\snpdf.exe" (
    if exist "snpdf.exe" (
        set "BIN=snpdf.exe"
    ) else (
        echo Error: snpdf.exe not found. Please build it first:
        echo go build -ldflags="-s -w" -o snpdf.exe .
        exit /b 1
    )
) else (
    set "BIN=..\snpdf.exe"
)

"%BIN%" ^
  --content "test\content.html" ^
  --header "test\header.html" ^
  --footer "test\footer.html" ^
  --watermark "test\watermark.html" ^
  --watermark-opacity 1.0 ^
  --output "test\output.pdf" ^
  --paper A4 ^
  --margin-top 5mm ^
  --margin-bottom 5mm ^
  --margin-left 5mm ^
  --margin-right 5mm ^
  --header-height 25mm ^
  --footer-height 15mm ^
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
