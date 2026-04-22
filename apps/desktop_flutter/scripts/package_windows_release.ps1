param(
    [string]$Version = "dev"
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$exePath = Join-Path $root "build/windows/x64/runner/Release/desktop_flutter.exe"

if (-not (Test-Path $exePath)) {
    throw "File executable tidak ditemukan: $exePath. Jalankan 'flutter build windows' dulu."
}

$timestamp = Get-Date -Format "yyyyMMdd-HHmm"
$releaseDir = Join-Path $root "release/desktop_flutter_win64_${Version}_$timestamp"

New-Item -ItemType Directory -Path $releaseDir -Force | Out-Null

Copy-Item $exePath (Join-Path $releaseDir "desktop_flutter.exe") -Force

$readme = @"
Ready To Pict Desktop - Release Package

Isi paket:
- desktop_flutter.exe

Cara pakai:
1. Double click desktop_flutter.exe
2. Isi API Base URL (contoh: http://SERVER:8000/api/v1)
3. Login dengan akun owner atau cashier

Catatan:
- Pastikan backend Laravel aktif dan bisa diakses dari PC ini.
- Jika memakai firewall, izinkan koneksi ke host API.
"@

Set-Content -Path (Join-Path $releaseDir "README_RELEASE.txt") -Value $readme -Encoding UTF8

Write-Host "Release package dibuat di: $releaseDir"
