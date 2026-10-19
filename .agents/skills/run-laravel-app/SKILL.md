---
name: run-laravel-app
description: |
  Runs the local Troogood HRMS backend server on XAMPP PHP (http://127.0.0.1:8000), checks database connectivity, and verifies health check endpoint.
---

# Run Laravel Backend App Skill

Activate this skill when the user asks to "Run the Laravel APP", "run backend", "start backend server", or test backend locally.

## Instructions
1. Run the local backend server from `E:\TG_HR_Mobile\TG_Laravel`:
   - Preferred PHP binary: `C:\xampp\php\php.exe`.
   - Command: `C:\xampp\php\php.exe -S 127.0.0.1:8000 -t E:\TG_HR_Mobile\TG_Laravel\public` (or run `E:\TG_HR_Mobile\TG_Laravel\start_backend.bat`).
2. Verify API status:
   - Request `GET http://127.0.0.1:8000/api/health`.
   - Confirm JSON response contains `"status": "online"` and database status.
3. If database is not initialized:
   - Execute `E:\TG_HR_Mobile\TG_Laravel\setup_database.bat` to import `database\Troogood_HR_APP_schema.sql` into XAMPP MySQL.

