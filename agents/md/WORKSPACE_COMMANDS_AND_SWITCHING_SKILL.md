# Workspace Commands & App Switching Skill

This document defines the 5 primary conversational commands to operate seamlessly between the Flutter Mobile App and the Laravel Backend.

---

## 1. "Work on TG_Laravel"
- **Context**: Focuses all operations on `E:\TG_HR_Mobile\TG_Laravel`.
- **Scope**:
  - Backend REST API endpoints (`public/index.php` and Laravel routes).
  - MySQL Database (`Troogood_HR_APP`) tables, migrations, and seeds.
  - Wireframe analysis from [screens](file:///e:/TG_HR_Mobile/TG_Laravel/screens) folder.
  - XAMPP local testing and BigRock server deployment scripts & `.env`.

---

## 2. "Work on TG_HR_Mobile_APP"
- **Context**: Focuses all operations on `E:\TG_HR_Mobile\TG_HR_Mobile_App`.
- **Scope**:
  - Flutter Mobile Application (Kiosk Attendance, Employee Portal, Admin).
  - MobileFaceNet 192-d ArcFace facial recognition pipeline.
  - Offline SQLite database, batch sync engine, camera overlays.
  - API endpoint configuration in [api_endpoints.dart](file:///e:/TG_HR_Mobile/TG_HR_Mobile_App/lib/constants/api_endpoints.dart).

---

## 3. "Run the Laravel APP" (Run the backend)
- **Context**: Starts the local PHP backend server on port 8000 using XAMPP PHP.
- **Execution**:
  Runs `C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public` from `E:\TG_HR_Mobile\TG_Laravel`.
- **Verification**: Tests `http://127.0.0.1:8000/api/health` and confirms DB connectivity.

---

## 4. "Run the cloud flare" (Run Cloudflare Tunnel)
- **Context**: Starts Cloudflare Tunnel targeting `http://127.0.0.1:8000`.
- **Execution**:
  Runs `cloudflared tunnel --url http://127.0.0.1:8000`.
- **Output**: Extracts the generated public HTTPS URL (e.g., `https://random-words.trycloudflare.com`).
- **Mobile Integration**: Updates `baseUrl` in [api_endpoints.dart](file:///e:/TG_HR_Mobile/TG_HR_Mobile_App/lib/constants/api_endpoints.dart) so mobile devices connect seamlessly.

---

## 5. "Run the mobile app"
- **Context**: Launches the Flutter application on your connected Android tablet/phone or emulator.
- **Execution**:
  Runs `flutter run` inside `E:\TG_HR_Mobile\TG_HR_Mobile_App`.
