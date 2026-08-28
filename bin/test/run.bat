@echo off
setlocal enabledelayedexpansion

echo ===================================================
echo Running snpdf test suite on Windows
echo ===================================================

rem Resolve paths from this script's own location so the test works no matter
rem which directory it is launched from.
set "ROOT=%~dp0.."

if exist "%ROOT%\pdf.exe" (
    set "BIN=%ROOT%\pdf.exe"
) else if exist "%ROOT%\snpdf.exe" (
    set "BIN=%ROOT%\snpdf.exe"
) else (
    echo Error: pdf.exe not found in %ROOT%. Please build it first:
    echo   go build -ldflags="-s -w" -o pdf.exe .
    exit /b 1
)

rem Millisecond timestamp before the run. PowerShell is used instead of %TIME%
rem because %TIME% is locale-dependent and wraps at midnight.
for /f %%T in ('powershell -NoProfile -Command "[DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds()"') do set "T_START=%%T"

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
  --header-spacing 8mm ^
  --footer-spacing 0 ^
  --title "snpdf test document" ^
  --orientation portrait

rem Capture the exit code first: any later command overwrites ERRORLEVEL.
set "RC=%ERRORLEVEL%"

rem The elapsed maths runs in PowerShell too: cmd's set /a is 32-bit and a
rem Unix millisecond timestamp saturates it, which would always report 0.
for /f %%T in ('powershell -NoProfile -Command "[DateTimeOffset]::UtcNow.ToUnixTimeMilliseconds() - %T_START%"') do set "ELAPSED_MS=%%T"
for /f %%T in ('powershell -NoProfile -Command "'{0:N2}' -f (%ELAPSED_MS%/1000)"') do set "ELAPSED_S=%%T"

if %RC% equ 0 (
    echo.
    echo ===================================================
    echo SUCCESS Output PDF generated at test\output.pdf
    echo Elapsed: %ELAPSED_S%s ^(%ELAPSED_MS% ms^)
    echo ===================================================
) else (
    echo.
    echo ===================================================
    echo FAILED with exit code %RC%
    echo Elapsed: %ELAPSED_S%s ^(%ELAPSED_MS% ms^)
    echo ===================================================
    exit /b %RC%
)
