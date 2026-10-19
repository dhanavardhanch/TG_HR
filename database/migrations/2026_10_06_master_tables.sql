-- Migration: Master Tables for Department, Designation, Contractor, Reporting Manager, and Banks
-- Date: 2026-10-06

CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_name` VARCHAR(100) NOT NULL UNIQUE,
    `department_code` VARCHAR(50) DEFAULT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `designations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `department_id` INT NULL,
    `designation_name` VARCHAR(100) NOT NULL,
    `designation_code` VARCHAR(50) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_dept_id` (`department_id`),
    CONSTRAINT `fk_designation_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contractors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `contractor_name` VARCHAR(150) NOT NULL UNIQUE,
    `contact_person` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reporting_managers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `manager_name` VARCHAR(100) NOT NULL,
    `department_id` INT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_mgr_dept_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `banks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `bank_name` VARCHAR(150) NOT NULL UNIQUE,
    `ifsc_prefix` VARCHAR(20) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Seed Data
INSERT IGNORE INTO `departments` (`id`, `department_name`, `department_code`, `description`) VALUES
(1, 'Production', 'PROD', 'Core food processing & manufacturing operations'),
(2, 'Packaging', 'PKG', 'Primary, secondary packaging and carton packing'),
(3, 'Quality Assurance', 'QA', 'Quality control, food safety and lab analytics'),
(4, 'Logistics & Warehouse', 'LOG', 'Raw material receiving, finished goods storage & dispatch'),
(5, 'Maintenance', 'MAINT', 'Plant machinery, electrical, utility maintenance'),
(6, 'Accounts & Finance', 'FIN', 'Billing, accounts and financial reconciliation'),
(7, 'HR & Admin', 'HR', 'Human resources, plant administration and compliance'),
(8, 'IT & Systems', 'IT', 'Software, kiosk devices, biometrics and network systems'),
(9, 'Security & Safety', 'SEC', 'Factory gate security, access control and EHS');

INSERT IGNORE INTO `designations` (`id`, `department_id`, `designation_name`, `designation_code`) VALUES
(1, 1, 'Machine Operator', 'PROD-OPR'),
(2, 1, 'Production Supervisor', 'PROD-SUP'),
(3, 1, 'Line Helper', 'PROD-HLP'),
(4, 1, 'Production Executive', 'PROD-EXEC'),
(5, 1, 'Plant Manager', 'PROD-MGR'),
(6, 2, 'Packing Operator', 'PKG-OPR'),
(7, 2, 'Packaging Supervisor', 'PKG-SUP'),
(8, 2, 'Sorter / Packer', 'PKG-SRT'),
(9, 2, 'Sealing Specialist', 'PKG-SEAL'),
(10, 3, 'Quality Inspector', 'QA-INSP'),
(11, 3, 'QA Executive', 'QA-EXEC'),
(12, 3, 'Lab Analyst', 'QA-LAB'),
(13, 3, 'Quality Manager', 'QA-MGR'),
(14, 4, 'Warehouse Executive', 'LOG-EXEC'),
(15, 4, 'Forklift Operator', 'LOG-FORK'),
(16, 4, 'Dispatch Coordinator', 'LOG-DISP'),
(17, 4, 'Store Incharge', 'LOG-STR'),
(18, 5, 'Maintenance Technician', 'MAINT-TECH'),
(19, 5, 'Electrical Technician', 'MAINT-ELEC'),
(20, 5, 'Mechanical Engineer', 'MAINT-MECH'),
(21, 5, 'Utility Incharge', 'MAINT-UTIL'),
(22, 6, 'Accounts Executive', 'FIN-EXEC'),
(23, 6, 'Billing Specialist', 'FIN-BILL'),
(24, 7, 'HR Executive', 'HR-EXEC'),
(25, 7, 'Admin Assistant', 'HR-ADM'),
(26, 7, 'Timekeeper', 'HR-TK'),
(27, 8, 'IT Executive', 'IT-EXEC'),
(28, 8, 'Systems Engineer', 'IT-SYS'),
(29, 9, 'Gate Security Incharge', 'SEC-GATE'),
(30, 9, 'Security Guard', 'SEC-GRD'),
(31, 9, 'Safety Officer', 'SEC-SAFE');

INSERT IGNORE INTO `contractors` (`id`, `contractor_name`, `contact_person`, `phone`, `email`) VALUES
(1, 'Sri Sai Manpower Services', 'Ramesh Rao', '9848011223', 'srisai@manpower.com'),
(2, 'Apex Logistics & Workforce', 'Satish Reddy', '9988776655', 'apex@workforce.in'),
(3, 'Venkateshwara Enterprises', 'Nagaraju K', '9701122334', 'venkat@enterprises.com'),
(4, 'Global Workforce Solutions', 'Praveen V', '9876543210', 'info@globalworkforce.in'),
(5, 'Balaji Security & Services', 'Murali Krishna', '9849988776', 'balaji@security.com'),
(6, 'Sunrise Facility Management', 'Kiran Kumar', '9912345678', 'sunrise@facilities.com');

INSERT IGNORE INTO `reporting_managers` (`id`, `manager_name`, `department_id`, `phone`, `email`) VALUES
(1, 'CHANDRA', 1, '9848012345', 'chandra@troogood.com'),
(2, 'SURESH KUMAR', 1, '9848023456', 'suresh@troogood.com'),
(3, 'RAJESH SHARMA', 4, '9848034567', 'rajesh@troogood.com'),
(4, 'VENKAT RAO', 1, '9848045678', 'venkat@troogood.com'),
(5, 'LAKSHMI NARAYANA', 3, '9848056789', 'lakshmi@troogood.com'),
(6, 'ANAND REDDY', 7, '9848067890', 'anand@troogood.com');

INSERT IGNORE INTO `banks` (`id`, `bank_name`, `ifsc_prefix`) VALUES
(1, 'State Bank of India', 'SBIN'),
(2, 'HDFC Bank', 'HDFC'),
(3, 'ICICI Bank', 'ICIC'),
(4, 'Axis Bank', 'UTIB'),
(5, 'Punjab National Bank', 'PUNB'),
(6, 'Bank of Baroda', 'BARB'),
(7, 'Canara Bank', 'CNRB'),
(8, 'Union Bank of India', 'UBIN'),
(9, 'Kotak Mahindra Bank', 'KKBK'),
(10, 'IndusInd Bank', 'INDB'),
(11, 'Indian Bank', 'IDIB'),
(12, 'Central Bank of India', 'CBIN'),
(13, 'Yes Bank', 'YESB'),
(14, 'Bank of India', 'BKID'),
(15, 'Federal Bank', 'FDRL');
