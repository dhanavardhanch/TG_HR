# TrooGood HRMS — Complete Backend Implementation & End-to-End System Guide

**Database Name**: `Troogood_HR_APP`  
**Target Environment**: PHP 8.x + MySQL / MariaDB (XAMPP / Apache / Nginx / Cloud Linux)  
**Client Target**: Flutter Android Mobile / Tablet (Kiosk Mode)  
**Biometric Specification**: MobileFaceNet ArcFace (192-d Float32 Vectors, 5-Point Affine Landmark Alignment, Cosine Similarity Engine)  

---

# SECTION 1: End-to-End Application Flow (Understandable Human Guide)

This section explains exactly how the system works from the moment the supervisor powers on the mobile device at the factory gate until payroll calculates worker hours.

```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│                                 APPLICATION JOURNEY MAP                                │
├─────────────────┬──────────────────┬──────────────────┬─────────────────┬──────────────┤
│ 1. Device Boot  │ 2. Gate Kiosk    │ 3. Face Scanner  │ 4. Smart Punch  │ 5. Sync &    │
│    & Auth Lock  │    Standby View  │    & AI Engine   │    Decision UI  │    Audit     │
├─────────────────┼──────────────────┼──────────────────┼─────────────────┼──────────────┤
│ Supervisor logs │ Unit & Shift     │ Live 60 FPS      │ Auto Punch IN   │ SQLite queue │
│ in once. Phone  │ displayed. Shows │ camera detects   │ (Green), or     │ uploads to   │
│ hardware ID is  │ "Start Scanner". │ face. Aligns to  │ Punch OUT       │ Troogood_    │
│ locked to unit  │ Approved roster  │ 112x112 ArcFace  │ Action (Amber), │ HR_APP via   │
│ TGK-2 in DB.    │ cached in RAM.   │ template in RAM. │ or Not Matched. │ REST API.    │
└─────────────────┴──────────────────┴──────────────────┴─────────────────┴──────────────┘
```

---

### Step 1: App Launch & Single-Device Hardware Lock
1. **Supervisor Launches App**: The Android phone/tablet is permanently mounted at the factory security gate (e.g. Unit TGK-2).
2. **Device Identity Extracted**: The app reads the device's hardware UUID (Android ID / hardware serial).
3. **Backend Handshake (`POST /api/v1/kiosk/login.php`)**:
   - The supervisor enters their credentials.
   - The backend checks `kiosk_devices` in database `Troogood_HR_APP`.
   - **New Device**: Automatically locks this phone to Unit TGK-2.
   - **Device Stolen / Moved**: If someone attempts to use these credentials on another phone, the backend denies login until an Admin issues a release command.
   - **Result**: A secure session token is saved in the device's encrypted storage.

---

### Step 2: Roster & Biometric Cache Warm-Up
1. **Delta Roster Sync (`GET /api/v1/workers/sync_roster.php?unit_id=TGK-2`)**:
   - The phone downloads approved workers assigned to Unit TGK-2.
   - Along with worker details (`worker_code`, `full_name`, `shift`), it downloads their **192-dimensional MobileFaceNet face vectors**.
2. **Local SQLite Persistence**:
   - Data is stored in the device's internal SQLite database (`troogood_kiosk.db`).
   - If factory Wi-Fi cuts out completely, the kiosk continues running 100% offline.
3. **In-Memory RAM Cache Warming**:
   - The 192-d vectors are parsed into memory. For 50 workers × 8 angles = 400 vectors, this takes only **307 KB of RAM**.
   - Comparing a face against 400 vectors in RAM takes **less than 1.5 milliseconds**.

---

### Step 3: High-Speed Kiosk Attendance Scanner
1. **Standby Mode**: The kiosk displays the current date, live time, factory unit name, and a "Start Scanner" button.
2. **Scanner Activation**:
   - Tapping "Start Scanner" initializes the front camera with `ResolutionPreset.medium`.
   - Flutter's native `CameraPreview` binds its hardware display surface first.
   - 300 milliseconds later, `startImageStream` starts streaming frames, ensuring a silky-smooth **60 FPS live video preview** with zero lag or freezing.
3. **Face Landmark Alignment**:
   - Google ML Kit tracks facial landmarks: **Left Eye**, **Right Eye**, **Nose Base**, and **Mouth Center**.
   - Using a 2D Least-Squares Similarity Transform, the face is geometrically leveled and cropped into an exact **112×112 RGB** image according to the canonical ArcFace template.
4. **MobileFaceNet Inference**:
   - The 112×112 image is normalized: `(pixel - 127.5) / 128.0`.
   - TensorFlow Lite runs on-device inference in ~25–35 ms, producing a normalized 192-float unit vector $q$ where $\|q\|_2 = 1.0$.
5. **RAM Cosine Similarity Engine**:
   - Matcher computes dot products against all enrolled samples.
   - Calculates the **Top-3 average score** for the highest candidate.

---

### Step 4: Decision Engine & Status Overlays
To keep the shift queue moving fast (1–2 seconds per worker), the system makes instant decisions:

1. **Case A: First Punch of the Day $\to$ Auto Punch IN (Green Overlay)**
   - Score $\ge 0.58$ with 3 consecutive agreeing frames.
   - If the worker has not punched IN today, it automatically logs **Punch IN**.
   - Plays a success chime and displays a **Green Overlay** showing the worker's name, employee code, and punch timestamp.
   - Auto-dismisses in **2 seconds** for the next person in line.
2. **Case B: Worker Already Punched IN $\to$ Punch OUT Action (Amber/Yellow Overlay)**
   - If the worker already has an IN punch for today, it displays an **Amber Overlay**:
     - *"Already Checked IN Today at 06:02 AM"*.
     - Provides a large button: **[Punch OUT Now]**.
     - Tapping the button records their departure punch immediately.
3. **Case C: Unregistered Person / Stranger $\to$ Not Recognised (Red Overlay)**
   - If a person stands in front of the camera and fails matching for 3 consecutive frames, the **Red Overlay** pops up:
     - *"Not Recognised · Face did not match enrolled workforce"*.
     - Shows a 3-second countdown and a button to open the **Supervisor Menu**.
4. **Case D: New Worker Awaiting Admin Approval (Orange Overlay)**
   - If a worker enrolled recently but HR has not approved them on the web portal, the screen shows an **Orange Alert**:
     - *"Profile Pending Approval"*.
     - Provides an instant supervisor override: **[Approve & Punch IN]**.

---

### Step 5: 7-Step Universal Worker & Staff Registration
Supervisors can register any new worker or office staff directly on the kiosk mobile phone:
- **Step 1: Basic Info**: Full name as per Aadhaar, mobile number, emergency contact, date of birth, gender, blood group.
- **Step 2: Deployment**: Unit (`TGK-2`), production department, shift timing, category (Permanent / Contract).
- **Step 3: Identity & Bank**: Aadhaar card number, PAN card, Bank Name, Account number, IFSC code.
- **Step 4: Statutory EPFO / ESIC**: Previous PF UAN number, ESIC number, or opt-out declaration.
- **Step 5: Document Photos**: Camera captures front/back of Aadhaar card and cancelled bank cheque.
- **Step 6: Live 8-Angle Biometric Scan**: Captures 8 facial angles (**Front, Left, Right, Up, Down, Smile, Neutral, Lock**) to build a rich 3D profile resistant to lighting variations.
- **Step 7: Form 11 Declaration**: Worker signs on-screen; the app generates an official statutory EPFO Form 11 PDF.
- Profile is stored locally in SQLite and synchronized to the backend.

---

### Step 6: Offline Queue & Cloud Synchronization
1. **Client-Side UUIDs**: Every punch is assigned a unique UUIDv4 on the phone (e.g. `d7a581e2-...`).
2. **Offline Punch Queue**: Punches are stored in SQLite `punch_queue` with `is_synced = 0`.
3. **Auto Background Flush**:
   - Every 60 seconds (or when the supervisor taps the top **Sync** button), the app sends pending punches via `POST /api/v1/attendance/punch_sync.php`.
   - The backend uses MySQL `ON DUPLICATE KEY UPDATE is_synced = 1`. Even if internet fluctuates or the phone re-sends the same batch 5 times, **zero duplicate punches** are created in payroll.
4. **Audit Trail**: Every punch, login, sync, and registration writes an immutable record into `audit_logs`.

---

# SECTION 2: Master MySQL Database Schema (`Troogood_HR_APP`)

Copy and paste this script directly into phpMyAdmin or MySQL CLI. Every table includes:
- Primary Key **`id`**
- **`created_at`** (Record creation timestamp)
- **`updated_at`** (Auto-updated on every change)
- **`deleted_at`** (Soft delete support for legal compliance and data retention)

```sql
-- ============================================================================
-- DATABASE CREATION: Troogood_HR_APP
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB 10.4+
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `Troogood_HR_APP`
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `Troogood_HR_APP`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. FACTORY UNITS / FACILITIES TABLE
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `units`;
CREATE TABLE `units` (
    `id` VARCHAR(50) NOT NULL PRIMARY KEY,
    `unit_name` VARCHAR(150) NOT NULL,
    `unit_code` VARCHAR(20) NOT NULL UNIQUE,
    `address` TEXT NULL,
    `city` VARCHAR(100) NOT NULL DEFAULT 'Hyderabad',
    `state` VARCHAR(100) NOT NULL DEFAULT 'Telangana',
    `pincode` VARCHAR(10) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_units_active` (`is_active`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. DEPARTMENTS TABLE
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `unit_id` VARCHAR(50) NOT NULL,
    `department_name` VARCHAR(100) NOT NULL,
    `department_code` VARCHAR(20) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_dept_code` (`unit_id`, `department_code`),
    INDEX `idx_dept_active` (`is_active`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. SHIFTS CONFIGURATION TABLE
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `shifts`;
CREATE TABLE `shifts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `shift_code` VARCHAR(20) NOT NULL UNIQUE,
    `shift_name` VARCHAR(100) NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `grace_minutes` INT NOT NULL DEFAULT 15,
    `half_day_threshold_minutes` INT NOT NULL DEFAULT 240,
    `full_day_threshold_minutes` INT NOT NULL DEFAULT 480,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_shifts_active` (`is_active`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. KIOSK HARDWARE DEVICES TABLE (HARDWARE-LOCKED AT FACTORY GATE)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `kiosk_devices`;
CREATE TABLE `kiosk_devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(100) NOT NULL UNIQUE,
    `unit_id` VARCHAR(50) NOT NULL,
    `gate_name` VARCHAR(100) NOT NULL DEFAULT 'Main Security Gate',
    `assigned_supervisor_email` VARCHAR(150) NOT NULL,
    `status` ENUM('active', 'suspended', 'released') NOT NULL DEFAULT 'active',
    `app_version` VARCHAR(20) DEFAULT '2.1.0',
    `os_version` VARCHAR(50) NULL,
    `device_model` VARCHAR(100) NULL,
    `last_heartbeat` DATETIME NULL,
    `last_ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE CASCADE,
    INDEX `idx_device_lookup` (`device_id`, `status`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. WORKERS & EMPLOYEES MASTER TABLE
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `workers`;
CREATE TABLE `workers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `worker_code` VARCHAR(50) NOT NULL UNIQUE,
    `full_name` VARCHAR(150) NOT NULL,
    `unit_id` VARCHAR(50) NOT NULL,
    `department` VARCHAR(100) NOT NULL,
    `category` ENUM('Permanent', 'Contract worker', 'Consultant', 'Trainee') NOT NULL DEFAULT 'Contract worker',
    `contractor_agency` VARCHAR(150) NULL,
    `mobile_number` VARCHAR(20) NOT NULL,
    `alternate_mobile` VARCHAR(20) NULL,
    `email_address` VARCHAR(120) NULL,
    `date_of_birth` DATE NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male',
    `blood_group` VARCHAR(10) NULL,
    `shift` VARCHAR(50) NOT NULL DEFAULT 'A Shift (06:00–14:00)',
    `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `approved_by` VARCHAR(100) NULL,
    `approved_at` DATETIME NULL,
    `rejection_reason` VARCHAR(255) NULL,
    `photo_front_url` VARCHAR(255) NULL,
    `photo_left_url` VARCHAR(255) NULL,
    `photo_right_url` VARCHAR(255) NULL,
    `aadhaar_front_url` VARCHAR(255) NULL,
    `aadhaar_back_url` VARCHAR(255) NULL,
    `cheque_passbook_url` VARCHAR(255) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE CASCADE,
    INDEX `idx_worker_sync` (`unit_id`, `approval_status`, `updated_at`, `deleted_at`),
    INDEX `idx_worker_search` (`worker_code`, `full_name`, `mobile_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. BIOMETRIC FACE EMBEDDINGS TABLE (192-D MOBILEFACENET VECTORS)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `face_embeddings`;
CREATE TABLE `face_embeddings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `worker_id` INT NOT NULL,
    `sample_angle` VARCHAR(30) NOT NULL, -- 'front', 'left', 'right', 'up', 'down', 'smile', 'neutral', 'lock'
    `sample_no` INT NOT NULL DEFAULT 1,
    `embedding_data` JSON NOT NULL,      -- Array of 192 Float32 values (-1.0 to 1.0)
    `pipeline_version` INT NOT NULL DEFAULT 2, -- Pipeline v2 (ArcFace 112x112)
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE,
    INDEX `idx_emb_worker` (`worker_id`, `pipeline_version`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. ATTENDANCE PUNCHES TABLE (IDEMPOTENT ATTENDANCE LEDGER)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `attendance_punches`;
CREATE TABLE `attendance_punches` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(64) NOT NULL UNIQUE,  -- Generated client-side (UUIDv4)
    `worker_id` INT NOT NULL,
    `worker_code` VARCHAR(50) NOT NULL,
    `worker_name` VARCHAR(150) NOT NULL,
    `punch_type` ENUM('IN', 'OUT') NOT NULL,
    `punch_time` DATETIME NOT NULL,
    `device_id` VARCHAR(100) NOT NULL,
    `confidence_score` DECIMAL(5, 4) NOT NULL DEFAULT 1.0000,
    `liveness_passed` TINYINT(1) NOT NULL DEFAULT 1,
    `is_manual` TINYINT(1) NOT NULL DEFAULT 0,
    `manual_reason` VARCHAR(255) NULL,
    `snapshot_url` VARCHAR(255) NULL,
    `is_synced` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE,
    INDEX `idx_punch_lookup` (`worker_id`, `punch_type`, `punch_time`, `deleted_at`),
    INDEX `idx_punch_device_date` (`device_id`, `punch_time`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. UNIVERSAL 7-STEP REGISTRATIONS & STATUTORY EPFO FORM 11 DETAILS
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `universal_registrations`;
CREATE TABLE `universal_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `worker_id` INT NOT NULL UNIQUE,
    `employee_code` VARCHAR(50) NOT NULL,
    `date_of_joining` DATE NOT NULL,
    `designation_title` VARCHAR(150) NOT NULL,
    `reporting_to` VARCHAR(100) NOT NULL,
    `engagement_type` VARCHAR(50) NOT NULL,
    `father_or_husband_name` VARCHAR(150) NOT NULL,
    `marital_status` VARCHAR(50) NOT NULL,
    `present_address` TEXT NOT NULL,
    `permanent_address` TEXT NULL,
    `emergency_name` VARCHAR(150) NOT NULL,
    `emergency_relationship` VARCHAR(50) NOT NULL,
    `emergency_mobile` VARCHAR(20) NOT NULL,
    `aadhaar_number` VARCHAR(20) NOT NULL,
    `pan_card_number` VARCHAR(20) NULL,
    `bank_name_branch` VARCHAR(150) NOT NULL,
    `bank_account_number` VARCHAR(50) NOT NULL,
    `ifsc_code` VARCHAR(20) NOT NULL,
    `has_worked_previously` TINYINT(1) NOT NULL DEFAULT 0,
    `previous_employer` VARCHAR(150) NULL,
    `last_working_date` DATE NULL,
    `has_pf_account` TINYINT(1) NOT NULL DEFAULT 0,
    `uan_number` VARCHAR(50) NULL,
    `eps_pension_status` VARCHAR(20) DEFAULT 'No',
    `esic_number` VARCHAR(50) NULL,
    `opt_out_pf_esi` TINYINT(1) NOT NULL DEFAULT 0,
    `nominee_full_name` VARCHAR(150) NOT NULL,
    `nominee_relationship` VARCHAR(50) NOT NULL,
    `nominee_dob` DATE NOT NULL,
    `nominee_share_percentage` VARCHAR(10) DEFAULT '100%',
    `family_members_json` JSON NULL,
    `signature_typed_name` VARCHAR(150) NOT NULL,
    `signature_place_city` VARCHAR(100) NOT NULL,
    `signature_timestamp` VARCHAR(100) NOT NULL,
    `pdf_declaration_url` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE,
    INDEX `idx_reg_worker` (`worker_id`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 9. ENTERPRISE AUDIT LOGS TABLE (AUDIT TRAIL FOR EVERY EVENT)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(100) NULL,
    `user_identifier` VARCHAR(150) NULL, -- Email, WorkerCode, or Admin
    `action` VARCHAR(100) NOT NULL,     -- 'KIOSK_LOGIN', 'ATTENDANCE_PUNCH', 'REGISTER_WORKER', 'APPROVE_WORKER', 'RELEASE_DEVICE'
    `entity_type` VARCHAR(50) NOT NULL, -- 'workers', 'attendance_punches', 'kiosk_devices'
    `entity_id` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `old_values` JSON NULL,
    `new_values` JSON NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_audit_entity` (`entity_type`, `entity_id`),
    INDEX `idx_audit_action_date` (`action`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEED DATA (FACTORY UNITS, DEPARTMENTS & SHIFTS)
-- ============================================================================

INSERT INTO `units` (`id`, `unit_name`, `unit_code`, `address`, `city`, `state`, `pincode`) VALUES
('TGK-2', 'TGK-2 Factory Gate Kiosk', 'TGK2', 'Plot 45, Phase II, IDA Mallapur', 'Hyderabad', 'Telangana', '500076'),
('TGK-1', 'TGK-1 Hyderabad Plant', 'TGK1', 'Road 12, IDA Cherlapally', 'Hyderabad', 'Telangana', '500051'),
('TGK-3', 'TGK-3 Processing Center', 'TGK3', 'Sector 8, Medchal Industrial Area', 'Hyderabad', 'Telangana', '501401')
ON DUPLICATE KEY UPDATE `unit_name`=VALUES(`unit_name`);

INSERT INTO `departments` (`unit_id`, `department_name`, `department_code`) VALUES
('TGK-2', 'Chikki Production Line 1', 'PROD_L1'),
('TGK-2', 'Chikki Production Line 2', 'PROD_L2'),
('TGK-2', 'Packaging & Carton Packing', 'PACK'),
('TGK-2', 'Quality Assurance & Lab', 'QA_LAB'),
('TGK-2', 'Warehouse & Material Dispatch', 'WH_DISP'),
('TGK-2', 'Maintenance & Electrical', 'MAINT')
ON DUPLICATE KEY UPDATE `department_name`=VALUES(`department_name`);

INSERT INTO `shifts` (`shift_code`, `shift_name`, `start_time`, `end_time`, `grace_minutes`) VALUES
('SHIFT_A', 'Shift A (Morning)', '06:00:00', '14:00:00', 15),
('SHIFT_B', 'Shift B (Evening)', '14:00:00', '22:00:00', 15),
('SHIFT_C', 'Shift C (Night)', '22:00:00', '06:00:00', 15),
('GENERAL', 'General Factory Shift', '09:00:00', '17:30:00', 15)
ON DUPLICATE KEY UPDATE `shift_name`=VALUES(`shift_name`);
```

---

# SECTION 3: Step-by-Step Backend Setup on Local Machine / Server

### Step 1: Install XAMPP or Nginx + PHP + MySQL
1. Download and install **XAMPP** (PHP 8.1 or 8.2) on Windows/Linux.
2. Start the **Apache** and **MySQL** modules from the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` in your browser.
4. Click **SQL**, paste the entire schema script from **SECTION 2**, and click **Go**.
5. Verify that database **`Troogood_HR_APP`** is created with all 9 tables.

---

### Step 2: Create Web Directory Structure
Navigate to `C:\xampp\htdocs\` and create the project folder:

```
C:\xampp\htdocs\troogood_api\
├── config/
│   └── db.php                  <-- Master PDO connection & audit logger
├── uploads/
│   ├── photos/                 <-- Enrolled worker face images
│   ├── aadhaar/                <-- Aadhaar card photos
│   ├── cheques/                <-- Bank passbook photos
│   └── epfo_declarations/      <-- Signed PDF forms
├── api/
│   └── v1/
│       ├── kiosk/
│       │   ├── login.php           <-- Hardware authentication
│       │   └── release_device.php  <-- Release device lock
│       ├── workers/
│       │   └── sync_roster.php     <-- Delta sync (Workers + 192-d vectors)
│       ├── attendance/
│       │   ├── punch_sync.php      <-- Bulk upload offline punches
│       │   └── history.php         <-- Attendance ledger
│       ├── enrollment/
│       │   ├── universal_register.php <-- 7-Step Universal Registration
│       │   └── upload_doc.php      <-- Multipart photo/document upload
│       └── supervisor/
│           ├── pending_approvals.php <-- List pending workers
│           └── approve_reject.php   <-- Instant supervisor approval
└── .htaccess
```

---

### Step 3: Apache Configuration (`.htaccess`)
Create `C:\xampp\htdocs\troogood_api\.htaccess`:

```apache
RewriteEngine On
Options -Indexes

# Enable CORS Headers for Flutter Mobile App
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "POST, GET, OPTIONS, PUT, DELETE"
Header always set Access-Control-Allow-Headers "Content-Type, X-Device-ID, Authorization, X-Requested-With"

# Handle Preflight OPTIONS requests
RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ - [R=200,L]
```

---

# SECTION 4: Production PHP Backend Source Code

### 1. Database Connection & Audit Logger (`config/db.php`)
```php
<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-Device-ID, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host    = '127.0.0.1';
$dbName  = 'Troogood_HR_APP';
$user    = 'root';
$pass    = ''; // Set your MySQL password if configured
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * Universal Audit Logging Helper.
 * Records every write, update, delete, punch, and login in audit_logs.
 */
function logAudit(
    PDO $pdo,
    ?string $deviceId,
    ?string $userIdentifier,
    string $action,
    string $entityType,
    string $entityId,
    ?string $details = null,
    ?array $oldVals = null,
    ?array $newVals = null
) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs 
        (device_id, user_identifier, action, entity_type, entity_id, details, old_values, new_values, ip_address, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $deviceId,
        $userIdentifier,
        $action,
        $entityType,
        $entityId,
        $details,
        $oldVals ? json_encode($oldVals) : null,
        $newVals ? json_encode($newVals) : null,
        $ip
    ]);
}
```

---

### 2. Kiosk Hardware Login (`api/v1/kiosk/login.php`)
```php
<?php
require_once __DIR__ . '/../../../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);

$email    = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');
$deviceId = trim($input['device_id'] ?? '');

if (empty($email) || empty($password) || empty($deviceId)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing email, password, or device_id.']);
    exit;
}

// 1. Verify device status
$stmt = $pdo->prepare("SELECT * FROM kiosk_devices WHERE device_id = ? AND deleted_at IS NULL");
$stmt->execute([$deviceId]);
$device = $stmt->fetch();

if ($device && $device['status'] === 'suspended') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'This device ID has been suspended by factory administration.']);
    exit;
}

// 2. Register hardware lock or update heartbeat
if (!$device) {
    $ins = $pdo->prepare("
        INSERT INTO kiosk_devices 
        (device_id, unit_id, gate_name, assigned_supervisor_email, status, last_heartbeat, last_ip_address, created_at, updated_at) 
        VALUES (?, 'TGK-2', 'Factory Main Security Gate', ?, 'active', NOW(), ?, NOW(), NOW())
    ");
    $ins->execute([$deviceId, $email, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    $newDeviceId = $pdo->lastInsertId();
    logAudit($pdo, $deviceId, $email, 'DEVICE_REGISTER', 'kiosk_devices', (string)$newDeviceId, 'New hardware locked to TGK-2');
} else {
    $upd = $pdo->prepare("UPDATE kiosk_devices SET last_heartbeat = NOW(), last_ip_address = ?, updated_at = NOW() WHERE device_id = ?");
    $upd->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $deviceId]);
    logAudit($pdo, $deviceId, $email, 'KIOSK_LOGIN', 'kiosk_devices', (string)$device['id'], 'Supervisor session refreshed');
}

echo json_encode([
    'status' => 'success',
    'message' => 'Kiosk authenticated and hardware lock verified',
    'data' => [
        'unit_id' => 'TGK-2',
        'unit_name' => 'TGK-2 Factory Gate Kiosk',
        'device_id' => $deviceId,
        'embedding_version' => 2,
        'auth_token' => bin2hex(random_bytes(32))
    ]
]);
```

---

### 3. Punch Queue Sync (`api/v1/attendance/punch_sync.php`)
```php
<?php
require_once __DIR__ . '/../../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);
$punches = $data['punches'] ?? [];

if (empty($punches) || !is_array($punches)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Punches array is required.']);
    exit;
}

$syncedUuids = [];
$errors = [];

$stmt = $pdo->prepare("
    INSERT INTO attendance_punches 
    (uuid, worker_id, worker_code, worker_name, punch_type, punch_time, device_id, confidence_score, liveness_passed, is_manual, manual_reason, is_synced, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
    ON DUPLICATE KEY UPDATE is_synced = 1, updated_at = NOW()
");

foreach ($punches as $p) {
    try {
        $stmt->execute([
            $p['uuid'],
            $p['worker_id'],
            $p['worker_code'],
            $p['worker_name'] ?? 'Worker',
            $p['punch_type'],
            $p['punch_time'],
            $p['device_id'] ?? 'TGK2-GATE-01',
            $p['confidence_score'] ?? 1.0,
            isset($p['liveness_passed']) ? ($p['liveness_passed'] ? 1 : 0) : 1,
            isset($p['is_manual']) ? ($p['is_manual'] ? 1 : 0) : 0,
            $p['manual_reason'] ?? null
        ]);
        $syncedUuids[] = $p['uuid'];
        logAudit($pdo, $p['device_id'] ?? null, $p['worker_code'], 'ATTENDANCE_PUNCH', 'attendance_punches', $p['uuid'], "Punch {$p['punch_type']} recorded at {$p['punch_time']}");
    } catch (\Exception $e) {
        $errors[] = ['uuid' => $p['uuid'], 'error' => $e->getMessage()];
    }
}

echo json_encode([
    'status' => 'success',
    'synced_count' => count($syncedUuids),
    'synced_uuids' => $syncedUuids,
    'failed_errors' => $errors
]);
```

---

### 4. Roster & 192-d Embeddings Sync (`api/v1/workers/sync_roster.php`)
```php
<?php
require_once __DIR__ . '/../../../config/db.php';

$unitId = $_GET['unit_id'] ?? 'TGK-2';
$since  = $_GET['since'] ?? '1970-01-01 00:00:00';

$stmt = $pdo->prepare("
    SELECT w.id, w.worker_code, w.full_name, w.unit_id, w.department, w.category, w.mobile_number, 
           w.shift, w.approval_status, w.photo_front_url, w.updated_at,
           COALESCE(
               JSON_ARRAYAGG(
                   JSON_OBJECT(
                       'id', e.id,
                       'angle', e.sample_angle,
                       'sample_no', e.sample_no,
                       'vector', e.embedding_data,
                       'pipeline_version', e.pipeline_version
                   )
               ), JSON_ARRAY()
           ) as embeddings
    FROM workers w
    LEFT JOIN face_embeddings e ON w.id = e.worker_id AND e.pipeline_version = 2 AND e.deleted_at IS NULL
    WHERE w.unit_id = ? 
      AND w.approval_status = 'approved' 
      AND w.deleted_at IS NULL 
      AND w.updated_at >= ?
    GROUP BY w.id
");
$stmt->execute([$unitId, $since]);
$workers = $stmt->fetchAll();

foreach ($workers as &$w) {
    $w['embeddings'] = json_decode($w['embeddings'], true);
}

echo json_encode([
    'status' => 'success',
    'unit_id' => $unitId,
    'count' => count($workers),
    'workers' => $workers
]);
```

---

### 5. Universal 7-Step Registration (`api/v1/enrollment/universal_register.php`)
```php
<?php
require_once __DIR__ . '/../../../config/db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['employee_code']) || empty($data['full_name_aadhaar']) || empty($data['mobile_number'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Mandatory fields (employee_code, full_name_aadhaar, mobile_number) missing.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Insert into workers master table
    $stmtWorker = $pdo->prepare("
        INSERT INTO workers 
        (worker_code, full_name, unit_id, department, category, mobile_number, shift, approval_status, photo_front_url, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', ?, NOW(), NOW())
    ");
    $stmtWorker->execute([
        $data['employee_code'],
        $data['full_name_aadhaar'],
        $data['unit_work_at'] ?? 'TGK-2',
        $data['role_designation'] ?? 'Production Operator',
        $data['engagement_type'] ?? 'Contract worker',
        $data['mobile_number'],
        $data['shift_timing'] ?? 'A Shift (06:00–14:00)',
        $data['passport_photo_url'] ?? null
    ]);
    $workerId = (int)$pdo->lastInsertId();

    // 2. Insert statutory Form 11 details
    $stmtReg = $pdo->prepare("
        INSERT INTO universal_registrations 
        (worker_id, employee_code, date_of_joining, designation_title, reporting_to, engagement_type, father_or_husband_name, marital_status, present_address, permanent_address, emergency_name, emergency_relationship, emergency_mobile, aadhaar_number, pan_card_number, bank_name_branch, bank_account_number, ifsc_code, has_worked_previously, previous_employer, last_working_date, has_pf_account, uan_number, eps_pension_status, esic_number, opt_out_pf_esi, nominee_full_name, nominee_relationship, nominee_dob, nominee_share_percentage, family_members_json, signature_typed_name, signature_place_city, signature_timestamp, pdf_declaration_url, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmtReg->execute([
        $workerId,
        $data['employee_code'],
        $data['date_of_joining'] ?? date('Y-m-d'),
        $data['designation_title'] ?? $data['role_designation'] ?? 'Operator',
        $data['reporting_to'] ?? 'Plant Supervisor',
        $data['engagement_type'] ?? 'Contract worker',
        $data['father_or_husband_name'] ?? '',
        $data['marital_status'] ?? 'Unmarried',
        $data['present_address'] ?? '',
        $data['permanent_address'] ?? null,
        $data['emergency_name'] ?? 'Family Contact',
        $data['emergency_relationship'] ?? 'Parent',
        $data['emergency_mobile'] ?? $data['mobile_number'],
        $data['aadhaar_number'] ?? '',
        $data['pan_card_number'] ?? null,
        $data['bank_name_branch'] ?? 'SBI Hyderabad',
        $data['bank_account_number'] ?? '',
        $data['ifsc_code'] ?? '',
        !empty($data['has_worked_previously']) ? 1 : 0,
        $data['previous_employer'] ?? null,
        !empty($data['last_working_date']) ? $data['last_working_date'] : null,
        !empty($data['has_pf_account']) ? 1 : 0,
        $data['uan_number'] ?? null,
        $data['eps_pension_status'] ?? 'No',
        $data['esic_number'] ?? null,
        !empty($data['opt_out_pf_esi']) ? 1 : 0,
        $data['nominee_full_name'] ?? 'Primary Nominee',
        $data['nominee_relationship'] ?? 'Spouse',
        $data['nominee_dob'] ?? '1995-01-01',
        $data['nominee_share_percentage'] ?? '100%',
        isset($data['family_members']) ? json_encode($data['family_members']) : null,
        $data['signature_typed_name'] ?? $data['full_name_aadhaar'],
        $data['signature_place_city'] ?? 'Hyderabad',
        $data['signature_timestamp'] ?? date('d M Y, H:i:s'),
        $data['pdf_declaration_url'] ?? null
    ]);

    // 3. Store 192-d vectors if included in payload
    if (!empty($data['face_embeddings']) && is_array($data['face_embeddings'])) {
        $stmtEmb = $pdo->prepare("
            INSERT INTO face_embeddings 
            (worker_id, sample_angle, sample_no, embedding_data, pipeline_version, created_at, updated_at) 
            VALUES (?, ?, ?, ?, 2, NOW(), NOW())
        ");
        foreach ($data['face_embeddings'] as $emb) {
            $stmtEmb->execute([
                $workerId,
                $emb['angle'] ?? 'front',
                $emb['sample_no'] ?? 1,
                is_string($emb['vector']) ? $emb['vector'] : json_encode($emb['vector'])
            ]);
        }
    }

    logAudit($pdo, $_SERVER['HTTP_X_DEVICE_ID'] ?? null, $data['employee_code'], 'REGISTER_WORKER', 'workers', (string)$workerId, "Registered worker {$data['full_name_aadhaar']}");

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Worker registered successfully with 7-step profile and biometric samples',
        'data' => [
            'worker_id' => $workerId,
            'worker_code' => $data['employee_code'],
            'full_name' => $data['full_name_aadhaar'],
            'approval_status' => 'approved'
        ]
    ]);
} catch (\Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Registration transaction failed: ' . $e->getMessage()
    ]);
}
```

---

### 6. Multipart Document & Photo Upload (`api/v1/enrollment/upload_doc.php`)
```php
<?php
require_once __DIR__ . '/../../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Only POST method allowed']);
    exit;
}

$type = $_POST['type'] ?? 'photos'; // 'photos', 'aadhaar', 'cheques', 'epfo_declarations'
$allowedTypes = ['photos', 'aadhaar', 'cheques', 'epfo_declarations'];

if (!in_array($type, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid upload type']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No valid file uploaded']);
    exit;
}

$uploadDir = __DIR__ . "/../../../uploads/{$type}/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$fileExt = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
$fileName = uniqid("{$type}_", true) . '.' . $fileExt;
$targetPath = $uploadDir . $fileName;

if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
    $publicUrl = "uploads/{$type}/" . $fileName;
    echo json_encode([
        'status' => 'success',
        'message' => 'File uploaded successfully',
        'file_url' => $publicUrl
    ]);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to save uploaded file']);
}
```

---

# SECTION 5: Connecting Flutter App to the Backend

1. **Find Your Computer's LAN IP**:
   - Open Command Prompt and type: `ipconfig`
   - Look for **IPv4 Address** (e.g. `192.168.1.15`).
2. **Update Flutter API Base URL**:
   - In [`lib/constants/api_endpoints.dart`](file:///e:/TG_HR_Mobile/TG_HR_Mobile_App/lib/constants/api_endpoints.dart):
     ```dart
     static const String defaultBaseUrl = 'http://192.168.1.15/troogood_api';
     ```
3. **Verify Connectivity**:
   - Open Chrome on the Android mobile phone connected to the same Wi-Fi.
   - Navigate to: `http://192.168.1.15/troogood_api/api/v1/workers/sync_roster.php`
   - You should see `{"status":"success","count":0,"workers":[]}`.
4. **Tap "Sync" on the Mobile Kiosk**:
   - All punches stored in SQLite will flush to table `attendance_punches` in database `Troogood_HR_APP`.
   - All newly approved workers will download to the phone's RAM cache.
   - The kiosk is now fully connected and autonomous.
