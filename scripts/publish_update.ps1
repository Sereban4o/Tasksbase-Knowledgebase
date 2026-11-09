param(
    [Parameter(Mandatory)] [ValidateSet('android', 'windows')] [string]$Platform,
    [Parameter(Mandatory)] [string]$File,
    [Parameter(Mandatory)] [string]$Version,
    [int]$VersionCode = 0,
    [string]$Notes = "Обновление"
)

# Publishes a new build to the update server directory:
#   updates/<platform>/<file>  +  updates/<platform>/latest.json
#
# Example:
#   .\scripts\publish_update.ps1 -Platform android -File .\app-release.apk -Version 1.0.1 -VersionCode 2 -Notes "Fixes"

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$dir = Join-Path $root "updates/$Platform"
New-Item -ItemType Directory -Force -Path $dir | Out-Null

$src = (Resolve-Path $File).Path
$name = Split-Path -Leaf $src
$dest = Join-Path $dir $name
Copy-Item $src $dest -Force

$hash = (Get-FileHash $dest -Algorithm SHA256).Hash.ToLower()

$manifest = [ordered]@{
    platform    = $Platform
    version     = $Version
    versionCode = $VersionCode
    path        = "/updates/$Platform/$name"
    notes       = $Notes
    sha256      = $hash
}

$json = $manifest | ConvertTo-Json
# Write UTF-8 WITHOUT BOM (BOM breaks strict JSON parsers in the clients).
$latestPath = Join-Path $dir 'latest.json'
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($latestPath, $json, $utf8NoBom)

Write-Host "Published $Platform $Version -> $dest"
Write-Host "Manifest: updates/$Platform/latest.json"
