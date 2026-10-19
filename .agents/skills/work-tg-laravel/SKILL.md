---
name: work-tg-laravel
description: |
  Switch context to work on the TG_Laravel backend (E:\TG_HR_Mobile\TG_Laravel), including PHP/Laravel APIs, MySQL database Troogood_HR_APP, wireframe mockups in screens/, and BigRock server deployment.
---

# Work on TG_Laravel Backend Skill

Activate this skill when the user asks to "work on TG_Laravel", work on the backend, update APIs, manage the database, inspect wireframes in `screens/`, or configure deployment.

## Base Directory
All backend work is centered in:
`E:\TG_HR_Mobile\TG_Laravel`

## Core Responsibilities & Workflows
1. **Wireframes to Backend**:
   - Inspect images inside `E:\TG_HR_Mobile\TG_Laravel\screens\`.
   - Extract form inputs, data tables, metrics, and actions.
   - Build corresponding database tables, controllers, and API routes.
2. **Database Integrity**:
   - Database name: `Troogood_HR_APP`.
   - Every table MUST have: `id`, `created_at`, `updated_at`, `deleted_at`.
   - Forensic tracking via `audit_logs` table.
   - Master schema: `E:\TG_HR_Mobile\TG_Laravel\database\Troogood_HR_APP_schema.sql`.
3. **API Endpoints**:
   - Main router: `E:\TG_HR_Mobile\TG_Laravel\public\index.php`.
   - Core endpoints: `/api/health`, `/api/v1/auth/login`, `/api/v1/kiosk/verify-device`, `/api/v1/sync/employees`, `/api/v1/attendance/punch`, `/api/v1/attendance/batch-sync`.
4. **Environment & Deployment**:
   - Local: XAMPP PHP 8.0 (`C:\xampp\php\php.exe`) + MySQL on port 3306.
   - Live: BigRock cPanel shared hosting (MySQL import + `.htaccess` rewrite + `.env`).

