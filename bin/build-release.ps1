param (
    [string]$Tag = "v1.0.0"
)

$ErrorActionPreference = "Stop"

Write-Host "===================================================" -ForegroundColor Cyan
Write-Host " Building cross-platform release binaries ($Tag)   " -ForegroundColor Cyan
Write-Host "===================================================" -ForegroundColor Cyan

$RootDir = "$PSScriptRoot\.."
$BinDir = "$RootDir\bin"
$DistDir = "$RootDir\dist"

if (Test-Path $DistDir) {
    Remove-Item -Recurse -Force $DistDir
}
New-Item -ItemType Directory -Path $DistDir | Out-Null

$Targets = @(
    @{ OS = "linux";   Arch = "amd64"; Binary = "pdf";     Archive = "pdf_linux_amd64.tar.gz" },
    @{ OS = "linux";   Arch = "arm64"; Binary = "pdf";     Archive = "pdf_linux_arm64.tar.gz" },
    @{ OS = "darwin";  Arch = "amd64"; Binary = "pdf";     Archive = "pdf_darwin_amd64.tar.gz" },
    @{ OS = "darwin";  Arch = "arm64"; Binary = "pdf";     Archive = "pdf_darwin_arm64.tar.gz" },
    @{ OS = "windows"; Arch = "amd64"; Binary = "pdf.exe"; Archive = "pdf_windows_amd64.zip" },
    @{ OS = "windows"; Arch = "arm64"; Binary = "pdf.exe"; Archive = "pdf_windows_arm64.zip" }
)

foreach ($target in $Targets) {
    $os = $target.OS
    $arch = $target.Arch
    $binary = $target.Binary
    $archive = $target.Archive

    Write-Host "--> Building $os/$arch..." -ForegroundColor Yellow

    $tempBuildDir = Join-Path $DistDir "temp_$os`_$arch"
    New-Item -ItemType Directory -Path $tempBuildDir | Out-Null
    $outputPath = Join-Path $tempBuildDir $binary

    $env:GOOS = $os
    $env:GOARCH = $arch
    $env:CGO_ENABLED = "0"

    Push-Location $BinDir
    try {
        go build -ldflags="-s -w -X main.Version=$Tag" -o $outputPath .
    }
    finally {
        Pop-Location
    }

    $archivePath = Join-Path $DistDir $archive
    if ($archive.EndsWith(".zip")) {
        Compress-Archive -Path $outputPath -DestinationPath $archivePath -Force
    }
    elseif ($archive.EndsWith(".tar.gz")) {
        tar -czf $archivePath -C $tempBuildDir $binary
    }

    Remove-Item -Recurse -Force $tempBuildDir
    Write-Host "    [OK] Created $archive" -ForegroundColor Green
}

Remove-Item Env:\GOOS -ErrorAction SilentlyContinue
Remove-Item Env:\GOARCH -ErrorAction SilentlyContinue
Remove-Item Env:\CGO_ENABLED -ErrorAction SilentlyContinue

Write-Host "`n===================================================" -ForegroundColor Cyan
Write-Host " All release packages created in ./dist folder:" -ForegroundColor Cyan
Get-ChildItem $DistDir | Select-Object Name, Length | Format-Table -AutoSize
Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "You can now upload the files from ./dist to GitHub Release $Tag manually or using gh CLI:" -ForegroundColor Yellow
Write-Host "  gh release create $Tag .\dist\* --title $Tag --notes `"Release $Tag`"" -ForegroundColor Gray
