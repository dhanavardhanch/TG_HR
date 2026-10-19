# TrooGood HRMS Kiosk Backend API Specification (PHP / MySQL XAMPP)

> **Version**: 2.1.0  
> **Target Server**: Apache / PHP 8.x (XAMPP)  
> **Target Database**: MySQL / MariaDB (phpMyAdmin)  
> **Mobile Client**: Flutter Android Kiosk Application  
> **Biometric AI Engine**: MobileFaceNet ArcFace 192-d Unit Vectors (Pipeline v2)

---

## 1. Database Schema (SQL for phpMyAdmin)

Run the following SQL in phpMyAdmin to create the database tables:

```sql
CREATE DATABASE IF NOT EXISTS `troogood_hrms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `troogood_hrms`;

-- 1. Factory Units Table
CREATE TABLE IF NOT EXISTS `factory_units` (
  `id` VARCHAR(32) NOT NULL PRIMARY KEY,
  `unit_name` VARCHAR(128) NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Kiosk Devices Table (Hardware Locked)
CREATE TABLE IF NOT EXISTS `kiosk_devices` (
  `device_id` VARCHAR(64) NOT NULL PRIMARY KEY,
  `unit_id` VARCHAR(32) NOT NULL,
  `assigned_user_id` INT DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'released') DEFAULT 'active',
  `app_version` VARCHAR(16) DEFAULT '2.1.0',
  `last_heartbeat` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`unit_id`) REFERENCES `factory_units`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Users / Supervisors Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(128) NOT NULL UNIQUE,
  `mobile_number` VARCHAR(20) DEFAULT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(128) NOT NULL,
  `role` ENUM('admin', 'supervisor', 'kiosk') DEFAULT 'supervisor',
  `supervisor_pin` VARCHAR(8) DEFAULT '1234',
  `bound_device_id` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Factory Workers Table
CREATE TABLE IF NOT EXISTS `workers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `worker_code` VARCHAR(32) NOT NULL UNIQUE,
  `full_name` VARCHAR(128) NOT NULL,
  `unit_id` VARCHAR(32) NOT NULL,
  `department` VARCHAR(64) NOT NULL,
  `category` ENUM('Contract worker', 'Permanent', 'Trainee') DEFAULT 'Contract worker',
  `contractor_agency` VARCHAR(128) DEFAULT NULL,
  `mobile_number` VARCHAR(20) DEFAULT NULL,
  `shift` VARCHAR(64) DEFAULT 'A Shift 06:00–14:00',
  `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
  `rejection_reason` VARCHAR(255) DEFAULT NULL,
  `photo_front_url` VARCHAR(255) DEFAULT NULL,
  `photo_left_url` VARCHAR(255) DEFAULT NULL,
  `photo_right_url` VARCHAR(255) DEFAULT NULL,
  `embedding_version` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`unit_id`) REFERENCES `factory_units`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Face Embeddings Table (192-d JSON Float Array)
CREATE TABLE IF NOT EXISTS `face_embeddings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `worker_id` INT NOT NULL,
  `sample_angle` ENUM('front', 'left', 'right', 'smile') NOT NULL,
  `embedding_data` LONGTEXT NOT NULL COMMENT 'JSON array of 192 float32 values',
  `pipeline_version` INT DEFAULT 2,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Attendance Punches Table
CREATE TABLE IF NOT EXISTS `attendance_punches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Client UUID for deduplication',
  `worker_id` INT NOT NULL,
  `worker_code` VARCHAR(32) NOT NULL,
  `worker_name` VARCHAR(128) NOT NULL,
  `punch_type` ENUM('IN', 'OUT') NOT NULL,
  `punch_time` DATETIME NOT NULL,
  `device_id` VARCHAR(64) NOT NULL,
  `confidence_score` DECIMAL(5,4) NOT NULL,
  `liveness_passed` TINYINT(1) DEFAULT 1,
  `snapshot_url` VARCHAR(255) DEFAULT NULL,
  `is_manual` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Default Units & Admin Account
INSERT INTO `factory_units` (`id`, `unit_name`, `location`) VALUES
('TGK-1', 'TGK-1 Hyderabad Plant', 'Hyderabad'),
('TGK-2', 'TGK-2 Factory Gate Kiosk', 'Secunderabad'),
('TGK-3', 'TGK-3 Processing Plant', 'Medchal');

INSERT INTO `users` (`email`, `mobile_number`, `password_hash`, `full_name`, `role`, `supervisor_pin`) VALUES
('admin@troogood.com', '9876543210', MD5('admin123'), 'TrooGood Supervisor', 'supervisor', '1234');
```

---

## 2. API Endpoints Reference

### 2.1. Login & Single-Device Lock
* **Endpoint**: `POST /auth/login.php`
* **Purpose**: Authenticates credentials and locks account to physical hardware `device_id`.

#### Request Headers:
```http
Content-Type: application/json
X-Device-ID: TGK2-GATE-01
```

#### Request Payload:
```json
{
  "identifier": "admin@troogood.com",
  "password": "admin123",
  "device_id": "TGK2-GATE-01",
  "unit_id": "TGK-2"
}
```

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "token": "tg_tok_98f12a43b2c019d8",
    "user_name": "Supervisor (TGK-2)",
    "role": "supervisor",
    "supervisor_pin": "1234",
    "unit_id": "TGK-2",
    "bound_device_id": "TGK2-GATE-01"
  }
}
```

#### Error Response: Device Lock Mismatch (`403 Forbidden`):
```json
{
  "status": "error",
  "message": "Device Lock Violation: Account active on terminal TGK1-TERMINAL-04. Release from backend first.",
  "is_device_locked": true
}
```

#### Error Response: Invalid Credentials (`401 Unauthorized`):
```json
{
  "status": "error",
  "message": "Invalid email/mobile number or password."
}
```

---

### 2.2. Kiosk Delta / Full Sync
* **Endpoint**: `GET /kiosk/sync.php?unit_id=TGK-2&since_version=46`
* **Purpose**: Downloads all approved workers and their 192-d vectors for the assigned factory unit.

#### Request Headers:
```http
X-Device-ID: TGK2-GATE-01
```

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "data": {
    "unit_id": "TGK-2",
    "new_version": 47,
    "total_workers": 312,
    "workers": [
      {
        "id": 189,
        "worker_code": "189",
        "full_name": "Chandra Bose",
        "unit_id": "TGK-2",
        "department": "Production",
        "category": "Contract worker",
        "contractor_agency": "Sri Sai Manpower",
        "mobile_number": "9876543210",
        "shift": "A Shift 06:00–14:00",
        "approval_status": "approved",
        "embeddings": {
          "pipeline_version": 2,
          "front": [0.034, -0.012, 0.089, "... 192 float values ..."],
          "left": [0.031, -0.015, 0.091, "... 192 float values ..."],
          "right": [0.036, -0.010, 0.085, "... 192 float values ..."]
        }
      }
    ]
  }
}
```

---

### 2.3. Bulk Attendance Punch Upload
* **Endpoint**: `POST /punches/bulk.php`
* **Purpose**: Receives offline queued punch records from SQLite and saves them in MySQL.

#### Request Payload:
```json
{
  "device_id": "TGK2-GATE-01",
  "punches": [
    {
      "uuid": "7a94b321-c21d-4001-a1b9-8e27c1f1092a",
      "worker_id": 189,
      "worker_code": "189",
      "worker_name": "Chandra Bose",
      "punch_type": "IN",
      "punch_time": "2026-10-01 07:40:12",
      "device_id": "TGK2-GATE-01",
      "confidence_score": 0.9642,
      "liveness_passed": true,
      "snapshot_path": "punch_snapshots/189_171123456.jpg"
    }
  ]
}
```

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "message": "Punches synced successfully",
  "data": {
    "inserted_count": 1,
    "duplicates_ignored": 0,
    "synced_uuids": ["7a94b321-c21d-4001-a1b9-8e27c1f1092a"]
  }
}
```

---

### 2.4. New Worker Biometric Enrollment
* **Endpoint**: `POST /employees/enroll.php`
* **Purpose**: Registers worker details and 3-angle 192-d vectors into the system with `pending` approval status.

#### Request Payload:
```json
{
  "worker_code": "192",
  "full_name": "Ravi Teja",
  "unit_id": "TGK-2",
  "department": "Production",
  "category": "Contract worker",
  "contractor_agency": "Sri Sai Manpower",
  "mobile_number": "9848011223",
  "shift": "A Shift 06:00–14:00",
  "embeddings": {
    "pipeline_version": 2,
    "front": [0.021, -0.045, "... 192 float32 ..."],
    "left": [0.019, -0.048, "... 192 float32 ..."],
    "right": [0.023, -0.042, "... 192 float32 ..."]
  }
}
```

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "message": "Worker enrolled and queued for supervisor review",
  "data": {
    "worker_id": 192,
    "approval_status": "pending"
  }
}
```

---

### 2.5. Approve / Reject Worker Enrollment
* **Endpoint**: `POST /employees/approve.php` / `POST /employees/reject.php`
* **Purpose**: Approves or rejects pending workers and increments embedding version.

#### Request Payload:
```json
{
  "worker_id": 192,
  "status": "approved",
  "device_id": "TGK2-GATE-01"
}
```

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "message": "Worker approved. Unit embedding version incremented to v48",
  "data": {
    "worker_id": 192,
    "new_embedding_version": 48
  }
}
```

---

### 2.6. Release Device Lock (Backend Admin Action)
* **Endpoint**: `POST /auth/release_device.php`
* **Purpose**: Clears the `bound_device_id` on the supervisor account so they can bind a new mobile terminal.

#### Request Payload:
```json
{
  "email": "admin@troogood.com"
}
```

#### Success Response (`200 OK`):
```json
{
  "status": "success",
  "message": "Device lock released. Account can now be logged in on a new device."
}
```
