# Troogood HRMS - Backend & API Server
📁 Master Directory: `E:\TG_HR_Mobile\TG_Laravel`

This is the dedicated backend application for the **Troogood HRMS** ecosystem, cleanly segregated from the Flutter mobile app (`E:\TG_HR_Mobile\TG_HR_Mobile_App`).

---

## 🗂 Folder Architecture
```
E:\TG_HR_Mobile\
├── TG_HR_Mobile_App\          <-- Flutter Client Application (Kiosk & Employee)
│   ├── lib/
│   └── assets/
└── TG_Laravel\                <-- Backend API, Database, XAMPP, BigRock & Wireframes
    ├── screens/               <-- 🖼 DROP YOUR WIREFRAME SCREENS/IMAGES HERE!
    ├── agents/
    │   └── md/                <-- Skills for Cloudflare, Backend Runner & Wireframes
    ├── .agents/skills/        <-- Builtin IDE Skills
    ├── database/
    │   └── Troogood_HR_APP_schema.sql   <-- Full 14-table MySQL schema
    ├── public/
    │   ├── index.php          <-- REST API Router (CORS, Auth, Kiosk, Attendance)
    │   ├── .htaccess          <-- Apache rewrite rules for BigRock
    │   └── uploads/           <-- Uploaded photos & face captures
    ├── tools/                 <-- Cloudflare tunnel executable storage
    ├── start_backend.bat      <-- 1-Click: Starts XAMPP PHP on 127.0.0.1:8000
    ├── start_cloudflare_tunnel.bat <-- 1-Click: Exposes local server to HTTPS for Mobile
    ├── setup_database.bat     <-- 1-Click: Imports Troogood_HR_APP into XAMPP MySQL
    ├── TROOGOOD_HRMS_BACKEND_IMPLEMENTATION_AND_END_TO_END_GUIDE.md
    └── README.md
```

---

## 🚀 Quick Start Guide

### 1. Initialize Database (XAMPP)
1. Start **Apache** and **MySQL** in your **XAMPP Control Panel**.
2. Run **`setup_database.bat`** (or `.\setup_database.ps1`).
   - Creates database `Troogood_HR_APP` with all 14 tables, timestamps, and audit log tracking.

### 2. Start Local Backend
Run **`start_backend.bat`**.
- Starts PHP dev server at `http://127.0.0.1:8000`.
- Health check: `http://127.0.0.1:8000/api/health`.

### 3. Expose to Mobile App (Cloudflare Tunnel)
Run **`start_cloudflare_tunnel.bat`**.
- It creates a free, secure HTTPS URL (e.g. `https://xxxx.trycloudflare.com`).
- Copy that URL and update `E:\TG_HR_Mobile\TG_HR_Mobile_App\lib\constants\api_endpoints.dart`.
- Your real Android phone can now communicate with your local backend seamlessly!

### 4. Wireframes to Backend Code
- Drop wireframe screenshots into `screens/`.
- Tell the agent: *"Build the backend based on the screens in the screens folder"*.
- The agent will generate the corresponding controllers, models, and UI views.

### 5. Deploying Live to BigRock
Follow the detailed guide in `agents/md/BACKEND_RUN_AND_MANAGEMENT_SKILL.md` for cPanel MySQL import and file upload.
