param(
    [string]$Version = "dev",
    [string]$IsccPath = "ISCC.exe"
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$installerScript = Join-Path $PSScriptRoot "desktop_flutter_installer.iss"
$exePath = Join-Path $root "build/windows/x64/runner/Release/desktop_flutter.exe"

if (-not (Test-Path $exePath)) {
    throw "Executable belum ada di $exePath. Jalankan 'flutter build windows' dulu."
}

$iscc = Get-Command $IsccPath -ErrorAction SilentlyContinue

if (-not $iscc) {
    throw "Inno Setup Compiler (ISCC.exe) tidak ditemukan. Install Inno Setup dulu lalu jalankan lagi."
}

& $iscc.Source "/DMyAppVersion=$Version" $installerScript

if ($LASTEXITCODE -ne 0) {
    throw "Gagal build installer. Exit code: $LASTEXITCODE"
}

Write-Host "Installer selesai dibuild di folder apps/desktop_flutter/release/installer"
