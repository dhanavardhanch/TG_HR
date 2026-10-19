-- ==============================================================================
-- TROOGOOD HRMS DATABASE SCHEMA & MASTER MIGRATIONS
-- Database: Troogood_HR_APP
-- Compatible with: MySQL 5.7+, MySQL 8.0+, MariaDB 10.3+ (XAMPP & BigRock cPanel)
-- Timestamp Standards: id, created_at, updated_at, deleted_at (Soft Deletes)
-- Audit Rule: Forensic logging on all write operations to audit_logs
-- Mobile Biometric Specification: MobileFaceNet ArcFace 192-d Unit Vectors
-- ==============================================================================

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

-- View alias for factory_units compatibility
CREATE OR REPLACE VIEW `factory_units` AS SELECT * FROM `units`;

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
-- 4. USERS / SUPERVISORS / ADMINS TABLE
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `mobile_number` VARCHAR(30) NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `role` ENUM('super_admin', 'hr_manager', 'unit_hr', 'plant_head', 'supervisor', 'accounts', 'dept_noc', 'kiosk') NOT NULL DEFAULT 'supervisor',
    `supervisor_pin` VARCHAR(10) DEFAULT '1234',
    `unit_id` VARCHAR(50) DEFAULT 'TGK-2',
    `bound_device_id` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_device` (`bound_device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. KIOSK HARDWARE DEVICES TABLE (HARDWARE-LOCKED AT FACTORY GATE)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `kiosk_devices`;
CREATE TABLE `kiosk_devices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `device_id` VARCHAR(100) NOT NULL UNIQUE,
    `unit_id` VARCHAR(50) NOT NULL,
    `gate_name` VARCHAR(100) NOT NULL DEFAULT 'Main Security Gate',
    `assigned_user_id` INT DEFAULT NULL,
    `assigned_supervisor_email` VARCHAR(150) NULL,
    `status` ENUM('active', 'inactive', 'suspended', 'released') NOT NULL DEFAULT 'active',
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
-- 6. WORKERS & EMPLOYEES MASTER TABLE
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
    `mobile_number` VARCHAR(30) NOT NULL,
    `alternate_mobile` VARCHAR(30) NULL,
    `email_address` VARCHAR(120) NULL,
    `date_of_birth` DATE NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL DEFAULT 'Male',
    `blood_group` VARCHAR(10) NULL,
    `shift` VARCHAR(100) NOT NULL DEFAULT 'A Shift 06:00–14:00',
    `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    `approved_by` VARCHAR(100) NULL,
    `approved_at` DATETIME NULL,
    `rejection_reason` VARCHAR(255) NULL,
    `photo_front_url` VARCHAR(255) NULL,
    `photo_left_url` VARCHAR(255) NULL,
    `photo_right_url` VARCHAR(255) NULL,
    `aadhaar_front_url` VARCHAR(255) NULL,
    `aadhaar_back_url` VARCHAR(255) NULL,
    `cheque_passbook_url` VARCHAR(255) NULL,
    `embedding_version` INT DEFAULT 1,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE CASCADE,
    INDEX `idx_worker_sync` (`unit_id`, `approval_status`, `updated_at`, `deleted_at`),
    INDEX `idx_worker_search` (`worker_code`, `full_name`, `mobile_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. BIOMETRIC FACE EMBEDDINGS TABLE (192-D MOBILEFACENET ARCFACE VECTORS)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `face_embeddings`;
CREATE TABLE `face_embeddings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `worker_id` INT NOT NULL,
    `sample_angle` ENUM('front', 'left', 'right', 'up', 'down', 'smile', 'neutral', 'lock') NOT NULL DEFAULT 'front',
    `sample_no` INT NOT NULL DEFAULT 1,
    `embedding_data` LONGTEXT NOT NULL COMMENT 'JSON array of 192 float32 values',
    `pipeline_version` INT NOT NULL DEFAULT 2, -- Pipeline v2 (ArcFace 112x112)
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE,
    INDEX `idx_emb_worker` (`worker_id`, `sample_angle`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 8. ATTENDANCE PUNCHES TABLE (IDEMPOTENT ATTENDANCE LEDGER)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `attendance_punches`;
CREATE TABLE `attendance_punches` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `uuid` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Client UUID for deduplication',
    `worker_id` INT NOT NULL,
    `worker_code` VARCHAR(50) NOT NULL,
    `worker_name` VARCHAR(150) NOT NULL,
    `punch_type` ENUM('IN', 'OUT') NOT NULL,
    `punch_time` DATETIME NOT NULL,
    `device_id` VARCHAR(100) NOT NULL,
    `confidence_score` DECIMAL(6, 4) NOT NULL DEFAULT 1.0000,
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
-- 9. UNIVERSAL 7-STEP REGISTRATIONS & STATUTORY EPFO FORM 11 DETAILS
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
    `emergency_mobile` VARCHAR(30) NOT NULL,
    `aadhaar_number` VARCHAR(30) NOT NULL,
    `pan_card_number` VARCHAR(30) NULL,
    `bank_name_branch` VARCHAR(150) NOT NULL,
    `bank_account_number` VARCHAR(50) NOT NULL,
    `ifsc_code` VARCHAR(30) NOT NULL,
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
-- 10. CANDIDATES & ONBOARDING PIPELINE (W03 & W04)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `candidates`;
CREATE TABLE `candidates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `candidate_name` VARCHAR(150) NOT NULL,
    `position` VARCHAR(100) NOT NULL,
    `unit_id` VARCHAR(50) NOT NULL,
    `mobile_number` VARCHAR(30) NOT NULL,
    `email` VARCHAR(150) NULL,
    `stage` ENUM('invited', 'details_submitted', 'docs_verification', 'offer_released', 'offer_accepted', 'joined') NOT NULL DEFAULT 'invited',
    `sub_step` VARCHAR(50) DEFAULT 'Step 1/6',
    `pan_number` VARCHAR(30) NULL,
    `aadhaar_number` VARCHAR(30) NULL,
    `bank_account` VARCHAR(50) NULL,
    `uan_number` VARCHAR(50) NULL,
    `portal_login_status` ENUM('not_logged_in', 'password_reset_pending', 'password_reset_completed') DEFAULT 'not_logged_in',
    `offer_accepted_at` DATE NULL,
    `joining_date` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`unit_id`) REFERENCES `units`(`id`) ON DELETE CASCADE,
    INDEX `idx_cand_stage` (`stage`, `unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `candidate_documents`;
CREATE TABLE `candidate_documents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `candidate_id` INT NOT NULL,
    `document_type` ENUM('passport_photo', 'aadhaar_card', 'pan_card', 'bank_passbook', 'education_certificates') NOT NULL,
    `document_name` VARCHAR(150) NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `verification_status` ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    `rejection_reason` TEXT NULL,
    `verified_by` VARCHAR(100) NULL,
    `verified_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 11. UNIFIED APPROVALS INBOX (W05)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `approvals_inbox`;
CREATE TABLE `approvals_inbox` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `approval_type` ENUM('leave', 'advance', 'correction', 'kiosk_worker', 'exit_noc') NOT NULL,
    `worker_id` INT NULL,
    `employee_code` VARCHAR(50) NOT NULL,
    `employee_name` VARCHAR(150) NOT NULL,
    `unit_id` VARCHAR(50) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `details` TEXT NOT NULL,
    `amount` DECIMAL(10,2) NULL,
    `tenure_months` INT NULL,
    `dates_summary` VARCHAR(100) NULL,
    `days_count` DECIMAL(4,1) NULL,
    `balance_before` DECIMAL(5,1) NULL,
    `balance_after` DECIMAL(5,1) NULL,
    `team_on_leave_count` INT DEFAULT 0,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `current_step` VARCHAR(100) DEFAULT 'Reporting Manager',
    `next_step` VARCHAR(100) DEFAULT 'HR Auto-notified',
    `approver_comment` TEXT NULL,
    `actioned_by` VARCHAR(150) NULL,
    `actioned_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    INDEX `idx_appr_type_status` (`approval_type`, `status`, `unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 12. PAYROLL PROCESSING ENGINE (W06)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `payroll_runs`;
CREATE TABLE `payroll_runs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `month_year` VARCHAR(20) NOT NULL, -- e.g. '2026-09'
    `run_title` VARCHAR(100) NOT NULL,
    `stage` ENUM('select_units', 'attendance_locked', 'preview_variances', 'approve', 'lock_payroll', 'completed') NOT NULL DEFAULT 'preview_variances',
    `total_employees` INT NOT NULL DEFAULT 0,
    `gross_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `statutory_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `advance_recovery_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `net_pay_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `variance_flags_count` INT NOT NULL DEFAULT 0,
    `status` ENUM('draft', 'under_review', 'approved', 'locked', 'paid') NOT NULL DEFAULT 'under_review',
    `approved_by` VARCHAR(100) NULL,
    `approved_at` DATETIME NULL,
    `locked_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY `uk_month_run` (`month_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payroll_items`;
CREATE TABLE `payroll_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `payroll_run_id` INT NOT NULL,
    `worker_id` INT NOT NULL,
    `employee_code` VARCHAR(50) NOT NULL,
    `employee_name` VARCHAR(150) NOT NULL,
    `unit_id` VARCHAR(50) NOT NULL,
    `paid_days` DECIMAL(4,1) NOT NULL DEFAULT 0.0,
    `gross_pay` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `pf_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `esi_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `pt_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `tds_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `advance_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `net_pay` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `variance_flag` VARCHAR(50) DEFAULT 'OK',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`payroll_run_id`) REFERENCES `payroll_runs`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 13. ROLE-BASED ACCESS CONTROL (W07)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_code` VARCHAR(50) NOT NULL UNIQUE,
    `role_name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL,
    `is_system` TINYINT(1) NOT NULL DEFAULT 0,
    `user_count` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `role_id` INT NOT NULL,
    `module_name` VARCHAR(50) NOT NULL, -- Employees, Attendance, Leaves, Advances, Onboarding, Payroll, Exit & F&F, Kiosks, Roles & Access
    `can_view` TINYINT(1) NOT NULL DEFAULT 1,
    `can_create` TINYINT(1) NOT NULL DEFAULT 0,
    `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
    `can_approve` TINYINT(1) NOT NULL DEFAULT 0,
    `can_export` TINYINT(1) NOT NULL DEFAULT 0,
    `can_lock` TINYINT(1) NOT NULL DEFAULT 0,
    `unit_access_json` JSON NULL, -- Array of allowed unit codes ['TGK-1', 'TGK-2', ...]
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uk_role_mod` (`role_id`, `module_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 14. ENTERPRISE AUDIT LOGS TABLE (AUDIT TRAIL FOR EVERY EVENT)
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
-- SEED DATA (UNITS, DEPARTMENTS, SHIFTS, ROLES, ADMIN, WORKERS & VECTORS)
-- ============================================================================

INSERT INTO `units` (`id`, `unit_name`, `unit_code`, `address`, `city`, `state`, `pincode`) VALUES
('Corporate', 'Corporate', 'FAC-09', 'Corporate Office, Mumbai', 'Mumbai', 'Maharashtra', '400001'),
('TG-Mega', 'TG-Mega', 'FAC-13', 'TG Mega Plant, Kandhi', 'Kandhi', 'Telangana', '502285'),
('TGH-1', 'TGH-1', 'FAC-10', 'TGH-1 Plant', 'Hyderabad', 'Telangana', '500051'),
('TGK-1', 'TGK-1', 'FAC-01', 'Road 12, IDA Cherlapally', 'Hyderabad', 'Telangana', '500051'),
('TGK-2', 'TGK-2', 'FAC-02', 'Plot 45, Phase II, IDA Mallapur', 'Hyderabad', 'Telangana', '500076'),
('TGK-3', 'TGK-3', 'FAC-03', 'Sector 8, Medchal Industrial Area', 'Hyderabad', 'Telangana', '501401'),
('TGK-4', 'TGK-4', 'FAC-04', 'Patancheru Industrial Estate', 'Hyderabad', 'Telangana', '502319'),
('TGR', 'TGR', 'FAC-05', 'Central Roastery, Rajamahendravaram', 'Rajamahendravaram', 'Andhra Pradesh', '533101'),
('TGT', 'TGT', 'FAC-014', 'Transit Unit', 'Hyderabad', 'Telangana', '500001')
ON DUPLICATE KEY UPDATE `unit_name`=VALUES(`unit_name`);

INSERT INTO `departments` (`unit_id`, `department_name`, `department_code`) VALUES
('TGK-2', 'Chikki Production Line 1', 'PROD_L1'),
('TGK-2', 'Chikki Production Line 2', 'PROD_L2'),
('TGK-2', 'Packaging & Carton Packing', 'PACK'),
('TGK-2', 'Quality Assurance & Lab', 'QA_LAB'),
('TGK-2', 'Warehouse & Material Dispatch', 'WH_DISP'),
('TGK-2', 'Maintenance & Electrical', 'MAINT'),
('TGK-1', 'Production Unit 1', 'PROD_U1'),
('TGK-3', 'Nut Processing Line', 'NUT_PROC')
ON DUPLICATE KEY UPDATE `department_name`=VALUES(`department_name`);

INSERT INTO `shifts` (`shift_code`, `shift_name`, `start_time`, `end_time`, `grace_minutes`) VALUES
('SHIFT_A', 'A Shift (06:00–14:00)', '06:00:00', '14:00:00', 15),
('SHIFT_B', 'B Shift (14:00–22:00)', '14:00:00', '22:00:00', 15),
('SHIFT_C', 'C Shift (22:00–06:00)', '22:00:00', '06:00:00', 15),
('GENERAL', 'General Factory Shift', '09:00:00', '17:30:00', 15)
ON DUPLICATE KEY UPDATE `shift_name`=VALUES(`shift_name`);

-- Seed Users (Admin & Supervisors)
INSERT INTO `users` (`email`, `mobile_number`, `password_hash`, `full_name`, `role`, `supervisor_pin`, `unit_id`) VALUES
('admin@troogood.in', '9876543210', '$2y$10$w850M.l4vJ17rW8fM7Q3O.Z30Gk9uU58W2O1H3mO7S2Ww0F8kG8Gy', 'TrooGood Admin', 'super_admin', '1234', 'Corporate'),
('tgk2@gmail.com', '9876543211', '$2y$10$w850M.l4vJ17rW8fM7Q3O.Z30Gk9uU58W2O1H3mO7S2Ww0F8kG8Gy', 'TGK-2 Supervisor', 'gate_incharge', '5555', 'TGK-2')
ON DUPLICATE KEY UPDATE `full_name`=VALUES(`full_name`);

-- Seed Roles for W07
INSERT INTO `roles` (`role_code`, `role_name`, `description`, `is_system`, `user_count`) VALUES
('super_admin', 'Super Admin', 'Full system privileges across all units', 1, 1),
('gate_incharge', 'Gate Incharge', 'Factory gate attendance, kiosk operations', 0, 1),
('hr_manager', 'HR Manager', 'Organization-wide HR, payroll & approvals', 0, 0)
ON DUPLICATE KEY UPDATE `role_name`=VALUES(`role_name`);

-- Seed Role Permissions for HR Manager (W07 Wireframe representation)
INSERT INTO `role_permissions` (`role_id`, `module_name`, `can_view`, `can_create`, `can_edit`, `can_approve`, `can_export`, `can_lock`, `unit_access_json`) VALUES
(2, 'Employees', 1, 1, 1, 0, 1, 0, '["Corporate","TG-Mega","TGH-1","TGK-1","TGK-2","TGK-3","TGK-4","TGR","TGT"]'),
(2, 'Attendance', 1, 1, 1, 1, 1, 1, '["Corporate","TG-Mega","TGH-1","TGK-1","TGK-2","TGK-3","TGK-4","TGR","TGT"]'),
(2, 'Leaves', 1, 1, 1, 1, 1, 0, '["Corporate","TG-Mega","TGH-1","TGK-1","TGK-2","TGK-3","TGK-4","TGR","TGT"]'),
(2, 'Advances', 1, 1, 1, 1, 1, 0, '["Corporate","TG-Mega","TGH-1","TGK-1","TGK-2","TGK-3","TGK-4","TGR","TGT"]'),
(2, 'Onboarding', 1, 1, 1, 1, 1, 0, '["Corporate","TG-Mega","TGH-1","TGK-1","TGK-2","TGK-3","TGK-4","TGR","TGT"]'),
(2, 'Payroll', 1, 0, 0, 0, 1, 0, '["Corporate","TG-Mega","TGH-1","TGK-1","TGK-2","TGK-3","TGK-4","TGR","TGT"]'),
(2, 'Exit & F&F', 1, 1, 1, 1, 1, 0, '["Corporate","TG-Mega","TGH-1","TGK-1","TGK-2","TGK-3","TGK-4","TGR","TGT"]'),
(2, 'Kiosks', 1, 1, 1, 0, 1, 0, '["Corporate","TG-Mega","TGH-1","TGK-1","TGK-2","TGK-3","TGK-4","TGR","TGT"]'),
(2, 'Roles & Access', 1, 0, 0, 0, 1, 0, '["Corporate","TG-Mega","TGH-1","TGK-1","TGK-2","TGK-3","TGK-4","TGR","TGT"]')
ON DUPLICATE KEY UPDATE `can_view`=1;

-- Clean Production Database Schema Initialized (No dummy data seeded)
