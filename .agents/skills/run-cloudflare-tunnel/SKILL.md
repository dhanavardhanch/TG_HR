---
name: run-cloudflare-tunnel
description: |
  Starts Cloudflare Tunnel for TG_Laravel, generates a live public HTTPS URL, and connects TG_HR_Mobile_App over the internet.
---

# Run Cloudflare Tunnel Skill

Activate this skill when the user asks to "Run the cloud flare", "start tunnel", or connect mobile app with the local backend.

## Instructions
1. Start Cloudflare Tunnel pointing to local port 8000:
   - Run `E:\TG_HR_Mobile\TG_Laravel\start_cloudflare_tunnel.bat` (or use `E:\TG_HR_Mobile\TG_Laravel\tools\cloudflared.exe tunnel --url http://127.0.0.1:8000`).
2. Capture the public HTTPS URL:
   - Extract the generated URL ending in `trycloudflare.com` (e.g., `https://random-subdomain.trycloudflare.com`).
3. Update Mobile App Configuration:
   - Open `E:\TG_HR_Mobile\TG_HR_Mobile_App\lib\constants\api_endpoints.dart`.
   - Set `baseUrl` to `https://<subdomain>.trycloudflare.com/api/v1`.
4. Inform the user that the mobile app is now connected to the local backend live over HTTPS.

