@echo off
title Troogood HRMS - Cloudflare Tunnel
echo =====================================================================
echo  Starting Cloudflare Tunnel for TG_HR_Mobile_App
echo  Forwarding to: http://127.0.0.1:8000
echo =====================================================================

powershell -ExecutionPolicy Bypass -File "%~dp0start_cloudflare_tunnel.ps1"
pause
