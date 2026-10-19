<?php
$host = '127.0.0.1';
$db   = 'Troogood_HR_APP';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "Connected to MySQL successfully.\n";

    // 1. Check existing tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Existing tables: " . implode(', ', $tables) . "\n\n";

    // 2. Create holiday_calendar table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `holiday_calendar` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `holiday_date` DATE NOT NULL,
            `holiday_name` VARCHAR(150) NOT NULL,
            `holiday_type` ENUM('National', 'Festival', 'Company', 'Optional') DEFAULT 'Festival',
            `year` INT NOT NULL,
            `unit_id` VARCHAR(50) DEFAULT 'ALL',
            `description` TEXT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_date_unit` (`holiday_date`, `unit_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table 'holiday_calendar' ensured.\n";

    // Seed 2026 standard Indian holidays if empty
    $count = $pdo->query("SELECT COUNT(*) FROM holiday_calendar WHERE year = 2026")->fetchColumn();
    if ($count == 0) {
        $holidays2026 = [
            ['2026-01-01', 'New Year\'s Day', 'Company', 2026, 'ALL', 'Annual company holiday'],
            ['2026-01-14', 'Makar Sankranti / Pongal', 'Festival', 2026, 'ALL', 'Harvest festival'],
            ['2026-01-26', 'Republic Day', 'National', 2026, 'ALL', 'National holiday'],
            ['2026-03-03', 'Holi', 'Festival', 2026, 'ALL', 'Festival of colors'],
            ['2026-03-20', 'Eid-ul-Fitr / Ramzan', 'Festival', 2026, 'ALL', 'Islamic festival'],
            ['2026-04-03', 'Good Friday', 'Festival', 2026, 'ALL', 'Christian holy day'],
            ['2026-04-14', 'Dr. Ambedkar Jayanti / Tamil New Year', 'National', 2026, 'ALL', 'Birth anniversary'],
            ['2026-05-01', 'May Day / Labor Day', 'National', 2026, 'ALL', 'International workers day'],
            ['2026-05-27', 'Bakrid / Eid al-Adha', 'Festival', 2026, 'ALL', 'Islamic festival'],
            ['2026-08-15', 'Independence Day', 'National', 2026, 'ALL', 'Indian Independence Day'],
            ['2026-08-26', 'Raksha Bandhan', 'Festival', 2026, 'ALL', 'Festival of siblings'],
            ['2026-09-04', 'Janmashtami', 'Festival', 2026, 'ALL', 'Birth of Lord Krishna'],
            ['2026-09-14', 'Ganesh Chaturthi', 'Festival', 2026, 'ALL', 'Lord Ganesha festival'],
            ['2026-10-02', 'Gandhi Jayanti', 'National', 2026, 'ALL', 'Father of the nation birthday'],
            ['2026-10-20', 'Dussehra / Vijayadashami', 'Festival', 2026, 'ALL', 'Victory of good over evil'],
            ['2026-11-08', 'Diwali / Deepavali', 'Festival', 2026, 'ALL', 'Festival of lights'],
            ['2026-12-25', 'Christmas Day', 'Festival', 2026, 'ALL', 'Christian celebration'],
        ];
        $stmt = $pdo->prepare("INSERT INTO holiday_calendar (holiday_date, holiday_name, holiday_type, year, unit_id, description) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($holidays2026 as $h) {
            $stmt->execute($h);
        }
        echo "Seeded 17 standard 2026 holidays into holiday_calendar.\n";
    }

    // 3. Create contractors table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `contractors` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `agency_name` VARCHAR(150) NOT NULL UNIQUE,
            `contact_person` VARCHAR(100) NULL,
            `contact_phone` VARCHAR(30) NULL,
            `contact_email` VARCHAR(100) NULL,
            `address` TEXT NULL,
            `status` ENUM('Active', 'Inactive') DEFAULT 'Active',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table 'contractors' ensured.\n";

    // Seed master contractors if empty
    $cCount = $pdo->query("SELECT COUNT(*) FROM contractors")->fetchColumn();
    if ($cCount == 0) {
        $defaultContractors = [
            ['Sri Sai Manpower', 'Ramesh Kumar', '9876543210', 'srisai@manpower.com', 'Active'],
            ['Apex Logistics & Staffing', 'Suresh Reddy', '9845012345', 'apex@logistics.in', 'Active'],
            ['Venkateshwara Enterprises', 'Venkat Rao', '9988776655', 'venkat@enterprises.org', 'Active'],
            ['Global Workforce Solutions', 'Amit Shah', '9123456780', 'amit@globalworkforce.com', 'Active'],
            ['Balaji Security & Services', 'Manoj Sharma', '9765432109', 'balaji@security.co', 'Active'],
            ['Sunrise Facility Management', 'Deepak Verma', '9654321870', 'deepak@sunrisemgmt.in', 'Active'],
        ];
        $stmt = $pdo->prepare("INSERT INTO contractors (agency_name, contact_person, contact_phone, contact_email, status) VALUES (?, ?, ?, ?, ?)");
        foreach ($defaultContractors as $c) {
            $stmt->execute($c);
        }
        echo "Seeded default contractors.\n";
    }

    // 4. Create system_error_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `system_error_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `source` ENUM('mobile_app', 'web_portal', 'api', 'sync') NOT NULL DEFAULT 'mobile_app',
            `severity` ENUM('ERROR', 'WARNING', 'CRITICAL', 'INFO') NOT NULL DEFAULT 'ERROR',
            `error_message` TEXT NOT NULL,
            `stack_trace` MEDIUMTEXT NULL,
            `user_id_or_code` VARCHAR(100) NULL,
            `device_info` VARCHAR(255) NULL,
            `route_or_screen` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Table 'system_error_logs' ensured.\n";

    // 5. Create notifications table and notification_reads
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(200) NOT NULL,
            `message` TEXT NOT NULL,
            `target_role` VARCHAR(50) DEFAULT 'ALL',
            `target_unit` VARCHAR(50) DEFAULT 'ALL',
            `type` ENUM('ANNOUNCEMENT', 'ALERT', 'UPDATE', 'SHIFT_CHANGE') DEFAULT 'ANNOUNCEMENT',
            `created_by` VARCHAR(100) DEFAULT 'HR Admin',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notification_reads` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `notification_id` INT NOT NULL,
            `reader_type` ENUM('mobile_kiosk', 'worker', 'admin') DEFAULT 'mobile_kiosk',
            `reader_id` VARCHAR(100) NOT NULL,
            `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_notif_read` (`notification_id`, `reader_type`, `reader_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Tables 'notifications' and 'notification_reads' ensured.\n";

    // Seed sample notification if empty
    $nCount = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    if ($nCount == 0) {
        $pdo->exec("INSERT INTO notifications (title, message, target_role, target_unit, type, created_by) VALUES
            ('System Update: Location Capture Enabled', 'Gate attendance punches now automatically capture GPS coordinates for audit verification.', 'ALL', 'ALL', 'UPDATE', 'HR Admin'),
            ('Upcoming Holiday Reminder', 'Declared factory holiday on 14th April (Ambedkar Jayanti). Overtime/comp-off rules apply for duty staff.', 'ALL', 'ALL', 'ANNOUNCEMENT', 'HR Operations')
        ");
        echo "Seeded sample notifications.\n";
    }

    // 6. Alter attendance_punches to add latitude & longitude
    $punchesCols = $pdo->query("SHOW COLUMNS FROM attendance_punches")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('latitude', $punchesCols)) {
        $pdo->exec("ALTER TABLE attendance_punches ADD COLUMN `latitude` DECIMAL(10, 8) NULL AFTER `punch_time`");
        echo "Added 'latitude' to attendance_punches.\n";
    }
    if (!in_array('longitude', $punchesCols)) {
        $pdo->exec("ALTER TABLE attendance_punches ADD COLUMN `longitude` DECIMAL(11, 8) NULL AFTER `latitude`");
        echo "Added 'longitude' to attendance_punches.\n";
    }
    if (!in_array('location_accuracy', $punchesCols)) {
        $pdo->exec("ALTER TABLE attendance_punches ADD COLUMN `location_accuracy` DOUBLE NULL AFTER `longitude`");
        echo "Added 'location_accuracy' to attendance_punches.\n";
    }
    if (!in_array('location_address', $punchesCols)) {
        $pdo->exec("ALTER TABLE attendance_punches ADD COLUMN `location_address` VARCHAR(255) NULL AFTER `location_accuracy`");
        echo "Added 'location_address' to attendance_punches.\n";
    }

    // 7. Alter workers to add attendance_app_mode, app_email, app_password_hash
    $workersCols = $pdo->query("SHOW COLUMNS FROM workers")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('attendance_app_mode', $workersCols)) {
        $pdo->exec("ALTER TABLE workers ADD COLUMN `attendance_app_mode` ENUM('kiosk', 'individual') DEFAULT 'kiosk'");
        echo "Added 'attendance_app_mode' to workers.\n";
    }
    if (!in_array('app_email', $workersCols)) {
        $pdo->exec("ALTER TABLE workers ADD COLUMN `app_email` VARCHAR(150) NULL");
        echo "Added 'app_email' to workers.\n";
    }
    if (!in_array('app_password_hash', $workersCols)) {
        $pdo->exec("ALTER TABLE workers ADD COLUMN `app_password_hash` VARCHAR(255) NULL");
        echo "Added 'app_password_hash' to workers.\n";
    }

    // 8. Inspect or add candidate invite fields
    $candidateTables = ['candidates', 'candidate_applications', 'candidate_invites'];
    $cTable = null;
    foreach ($candidateTables as $ct) {
        if (in_array($ct, $tables)) {
            $cTable = $ct;
            break;
        }
    }
    if (!$cTable) {
        // Create candidates table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `candidates` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `candidate_name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(150) NOT NULL,
                `mobile` VARCHAR(20) NULL,
                `invite_token` VARCHAR(64) NOT NULL UNIQUE,
                `app_password` VARCHAR(100) NULL,
                `unit_id` VARCHAR(50) DEFAULT 'TGK-1',
                `department` VARCHAR(100) NULL,
                `designation` VARCHAR(100) NULL,
                `status` ENUM('Invited', 'Submitted', 'Under Review', 'Verified', 'Offer Released', 'Accepted', 'Approved', 'Rejected') DEFAULT 'Invited',
                `submission_data` JSON NULL,
                `offer_salary` VARCHAR(50) NULL,
                `offer_letter_url` VARCHAR(255) NULL,
                `hr_notes` TEXT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        echo "Table 'candidates' created.\n";
    } else {
        $cols = $pdo->query("SHOW COLUMNS FROM `$cTable`")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('invite_token', $cols)) {
            $pdo->exec("ALTER TABLE `$cTable` ADD COLUMN `invite_token` VARCHAR(64) NULL");
        }
        if (!in_array('submission_data', $cols)) {
            $pdo->exec("ALTER TABLE `$cTable` ADD COLUMN `submission_data` JSON NULL");
        }
        if (!in_array('offer_salary', $cols)) {
            $pdo->exec("ALTER TABLE `$cTable` ADD COLUMN `offer_salary` VARCHAR(50) NULL");
        }
        if (!in_array('offer_letter_url', $cols)) {
            $pdo->exec("ALTER TABLE `$cTable` ADD COLUMN `offer_letter_url` VARCHAR(255) NULL");
        }
        echo "Table '$cTable' updated with invite and offer fields.\n";
    }

    echo "\nAll database migrations and updates completed successfully!\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
