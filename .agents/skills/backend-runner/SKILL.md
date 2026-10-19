---
name: backend-runner
description: Run and deploy Troogood HRMS backend on XAMPP and BigRock
---

# Backend Run & Management Skill (XAMPP Local & BigRock Production)

## Overview
This skill guides running and managing the Troogood HRMS backend:
1. **Local Environment**: XAMPP (PHP 8.0+ CLI + MySQL on port 3306).
2. **Production Environment**: BigRock cPanel Shared Hosting (Apache + MySQL 5.7/8.0).
3. **Database**: `Troogood_HR_APP` with full soft deletes (`created_at`, `updated_at`, `deleted_at`) and forensic `audit_logs`.

---

## Part 1: Local Development on XAMPP

### Prerequisites
- XAMPP installed at `C:\xampp`.
- Start **Apache** and **MySQL** from the XAMPP Control Panel.

### 1-Click Database Setup
Run **`setup_database.bat`** (or `.\setup_database.ps1`).
This will:
- Connect to local MySQL (`C:\xampp\mysql\bin\mysql.exe -u root`).
- Create database `Troogood_HR_APP`.
- Execute `database\Troogood_HR_APP_schema.sql` with all 14 tables and initial seed data.

### 1-Click Backend Server
Run **`start_backend.bat`** (or `.\start_backend.ps1`).
This runs:
```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public
```
The backend is now live on `http://127.0.0.1:8000`.

---

## Part 2: Deploying to BigRock Server

### Step 1: Database Setup on BigRock cPanel
1. Log into your **BigRock cPanel**.
2. Under **Databases**, open **MySQL Database Wizard**:
   - Create database name: `troogood_hrms` (or `cpaneluser_Troogood_HR_APP`).
   - Create MySQL user with a strong password.
   - Assign user to the database with **ALL PRIVILEGES**.
3. Open **phpMyAdmin** in cPanel.
4. Select the newly created database and click **Import**.
5. Upload `E:\TG_HR_Mobile\TG_Laravel\database\Troogood_HR_APP_schema.sql` and click **Go**.

### Step 2: Upload Files
1. In cPanel **File Manager** (or via FTP using FileZilla):
   - For a subdomain (e.g. `api.troogood.com`): Set the document root to the folder containing `public/` (or upload files into the subdomain folder).
2. Upload the contents of `TG_Laravel` (exclude `.git`, `tools/`).
3. Set permissions:
   - `public/uploads/` directory -> `755` or `775` (writable for face sample images).

### Step 3: Configure Environment
1. In the root directory on BigRock, edit `.env`:
   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://api.yourdomain.com

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=your_bigrock_db_name
   DB_USERNAME=your_bigrock_db_user
   DB_PASSWORD=your_bigrock_db_password
   ```

---

## Part 3: Architecture Segregation
The master folder `E:\TG_HR_Mobile` maintains a clean separation:
- `E:\TG_HR_Mobile\TG_HR_Mobile_App`: Flutter mobile client (Kiosk & Employee modes).
- `E:\TG_HR_Mobile\TG_Laravel`: Backend API, database migrations, admin panel, Cloudflare tunnel, and screen wireframes.
