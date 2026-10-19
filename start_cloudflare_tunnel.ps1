# Cloudflare Tunnel Automated Runner
Write-Host "=====================================================================" -ForegroundColor Cyan
Write-Host " Cloudflare Tunnel for TG_HR_Mobile_App Integration" -ForegroundColor Green
Write-Host " Forwarding: http://127.0.0.1:8000 -> Global HTTPS URL" -ForegroundColor Yellow
Write-Host "=====================================================================" -ForegroundColor Cyan

$toolsDir = Join-Path $PSScriptRoot "tools"
$cloudflaredExe = Join-Path $toolsDir "cloudflared.exe"

if (-not (Test-Path $cloudflaredExe)) {
    # Check if cloudflared is on PATH
    $cmd = Get-Command "cloudflared" -ErrorAction SilentlyContinue
    if ($cmd) {
        $cloudflaredExe = $cmd.Source
    } else {
        Write-Host "`n[DOWNLOADING] cloudflared.exe from Cloudflare GitHub releases..." -ForegroundColor Yellow
        New-Item -ItemType Directory -Force -Path $toolsDir | Out-Null
        $downloadUrl = "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe"
        try {
            [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
            Invoke-WebRequest -Uri $downloadUrl -OutFile $cloudflaredExe -UseBasicParsing
            Write-Host "[OK] Downloaded cloudflared.exe to $cloudflaredExe" -ForegroundColor Green
        } catch {
            Write-Host "[ERROR] Could not download cloudflared.exe automatically." -ForegroundColor Red
            Write-Host "Please download it manually from: $downloadUrl" -ForegroundColor Yellow
            Write-Host "And save it to: $cloudflaredExe" -ForegroundColor Yellow
            Exit 1
        }
    }
}

Write-Host "`n[STARTING TUNNEL] Exposing http://127.0.0.1:8000..." -ForegroundColor Cyan
Write-Host "Look for the *.trycloudflare.com URL below and copy it to TG_HR_Mobile_App! `n" -ForegroundColor Green
& $cloudflaredExe tunnel --url http://127.0.0.1:8000
