<?php
/**
 * TrooGood HRMS - Complete Clean Database & Fresh Reset Script
 * Truncates all transactional data, wipes old test users/records,
 * and re-seeds fresh base configuration (Super Admin, Gate Incharge,
 * master units, shifts, departments, designations, contractors, banks, holidays, SMTP).
 */

require_once __DIR__ . '/../config/db.php';

$pdo = getDbConnection();
if (!$pdo) {
    die("Database connection failed\n");
}

echo "=======================================================\n";
echo "Starting Complete Clean Database Reset (Fresh Data Mode)\n";
echo "=======================================================\n\n";

$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

$transactionalTables = [
    'workers',
    'face_embeddings',
    'attendance_punches',
    'attendance_overrides',
    'approvals_inbox',
    'audit_logs',
    'candidate_documents',
    'candidates',
    'payroll_items',
    'payroll_runs',
    'support_issues',
    'system_error_logs',
    'unit_sync_log',
    'universal_registrations',
    'worker_login_assignments',
    'worker_transfers',
    'kiosk_devices',
    'notifications',
    'notification_reads',
    'user_roles',
    'user_unit_access',
    'users'
];

foreach ($transactionalTables as $tbl) {
    try {
        $pdo->exec("TRUNCATE TABLE `$tbl`");
        echo "✓ Truncated table: $tbl\n";
    } catch (Exception $e) {
        echo "! Note on $tbl: " . $e->getMessage() . "\n";
    }
}

// 1. Re-seed Fresh User Accounts
$pwdHash = password_hash('123456', PASSWORD_BCRYPT);

$pdo->prepare("INSERT INTO `users` (`id`, `email`, `password_hash`, `full_name`, `role`, `supervisor_pin`, `unit_id`, `status`, `created_at`, `updated_at`) VALUES
    (1, 'admin@troogood.in', ?, 'TrooGood Admin', 'super_admin', '1111', 'Corporate', 'active', NOW(), NOW()),
    (2, 'tgk2@gmail.com', ?, 'TGK-2 Supervisor', 'gate_incharge', '5555', 'TGK-2', 'active', NOW(), NOW())")
    ->execute([$pwdHash, $pwdHash]);
echo "✓ Seeded fresh user logins: 'admin@troogood.in' (Super Admin) & 'tgk2@gmail.com' (Gate Incharge, PIN 5555, password '123456')\n";

// 2. Clear & Seed 9 Official Units
try {
    $pdo->exec("TRUNCATE TABLE `units`");
    echo "✓ Truncated units table\n";
} catch (Exception $e) {}

$erpUnitsRaw = [
    ['code' => 'FAC-09',  'name' => 'Corporate', 'city' => 'Mumbai', 'state' => 'Maharashtra'],
    ['code' => 'FAC-13',  'name' => 'TG-Mega',   'city' => 'Kandhi', 'state' => 'Telangana'],
    ['code' => 'FAC-10',  'name' => 'TGH-1',     'city' => 'Hyderabad', 'state' => 'Telangana'],
    ['code' => 'FAC-01',  'name' => 'TGK-1',     'city' => 'Kompally', 'state' => 'Telangana'],
    ['code' => 'FAC-02',  'name' => 'TGK-2',     'city' => 'Kompally', 'state' => 'Telangana'],
    ['code' => 'FAC-03',  'name' => 'TGK-3',     'city' => 'Kompally', 'state' => 'Telangana'],
    ['code' => 'FAC-04',  'name' => 'TGK-4',     'city' => 'Kompally', 'state' => 'Telangana'],
    ['code' => 'FAC-05',  'name' => 'TGR',       'city' => 'Rajamahendravaram', 'state' => 'Andhra Pradesh'],
    ['code' => 'FAC-014', 'name' => 'TGT',       'city' => '', 'state' => ''],
];

$upsertUnit = $pdo->prepare("INSERT INTO units (id, unit_name, unit_code, city, state, is_active, last_synced_at, sync_status, created_at, updated_at)
                             VALUES (?, ?, ?, ?, ?, 1, NOW(), 'synced', NOW(), NOW())");
foreach ($erpUnitsRaw as $u) {
    $upsertUnit->execute([$u['name'], $u['name'], $u['code'], $u['city'], $u['state']]);
}
echo "✓ Initialized 9 Official Factory Units (Corporate, TG-Mega, TGH-1, TGK-1, TGK-2, TGK-3, TGK-4, TGR, TGT)\n";

// 3. Re-seed Shifts Master
$pdo->exec("TRUNCATE TABLE `shifts`");
$pdo->exec("INSERT INTO `shifts` (`shift_code`, `shift_name`, `start_time`, `end_time`, `grace_minutes`, `half_day_threshold_minutes`, `full_day_threshold_minutes`, `is_active`, `created_at`, `updated_at`) VALUES
    ('SHIFT_A', 'Shift A', '06:00:00', '14:00:00', 15, 240, 480, 1, NOW(), NOW()),
    ('SHIFT_B', 'Shift B', '14:00:00', '22:00:00', 15, 240, 480, 1, NOW(), NOW()),
    ('SHIFT_C', 'Shift C', '22:00:00', '06:00:00', 15, 240, 480, 1, NOW(), NOW()),
    ('GENERAL', 'General Shift', '09:00:00', '18:00:00', 15, 270, 540, 1, NOW(), NOW())");
echo "✓ Initialized Shift Master (Shift A, Shift B, Shift C, General Shift)\n";

// 4. Re-seed Departments Master
$pdo->exec("TRUNCATE TABLE `departments`");
$depts = [
    ['Chikki Production Line 1', 'PROD_L1', 'TGK-2'],
    ['Chikki Production Line 2', 'PROD_L2', 'TGK-2'],
    ['Packaging & Carton Packing', 'PACK', 'TGK-2'],
    ['Quality Assurance & Lab', 'QA_LAB', 'TGK-2'],
    ['Warehouse & Material Dispatch', 'WH_DISP', 'TGK-2'],
    ['Maintenance & Electrical', 'MAINT', 'TGK-2'],
    ['Accounts & Finance', 'FIN', null],
    ['HR & Admin', 'HR', null],
    ['Security & Safety', 'SEC', null],
];
$insDept = $pdo->prepare("INSERT INTO departments (department_name, department_code, unit_id, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())");
foreach ($depts as $d) {
    $insDept->execute([$d[0], $d[1], $d[2]]);
}
echo "✓ Initialized Departments Master\n";

// 5. Re-seed Designations Master linked to departments
$deptMap = $pdo->query("SELECT id, department_name FROM departments")->fetchAll(PDO::FETCH_KEY_PAIR);
$reverseDeptMap = array_flip($deptMap);

$pdo->exec("TRUNCATE TABLE `designations`");
$designations = [
    ['Machine Operator', 'PROD-OPR', 'Chikki Production Line 1'],
    ['Production Supervisor', 'PROD-SUP', 'Chikki Production Line 1'],
    ['Line Helper', 'PROD-HLP', 'Chikki Production Line 1'],
    ['Production Executive', 'PROD-EXEC', 'Chikki Production Line 1'],
    ['Plant Manager', 'PROD-MGR', 'Chikki Production Line 1'],
    ['Machine Operator', 'PROD-OPR-2', 'Chikki Production Line 2'],
    ['Production Supervisor', 'PROD-SUP-2', 'Chikki Production Line 2'],
    ['Line Helper', 'PROD-HLP-2', 'Chikki Production Line 2'],
    ['Packing Operator', 'PKG-OPR', 'Packaging & Carton Packing'],
    ['Packaging Supervisor', 'PKG-SUP', 'Packaging & Carton Packing'],
    ['Sorter / Carton Packer', 'PKG-SRT', 'Packaging & Carton Packing'],
    ['Sealing Specialist', 'PKG-SEAL', 'Packaging & Carton Packing'],
    ['Quality Inspector', 'QA-INSP', 'Quality Assurance & Lab'],
    ['QA Executive', 'QA-EXEC', 'Quality Assurance & Lab'],
    ['Lab Analyst', 'QA-LAB', 'Quality Assurance & Lab'],
    ['Quality Manager', 'QA-MGR', 'Quality Assurance & Lab'],
    ['Warehouse Executive', 'LOG-EXEC', 'Warehouse & Material Dispatch'],
    ['Forklift Operator', 'LOG-FORK', 'Warehouse & Material Dispatch'],
    ['Dispatch Coordinator', 'LOG-DISP', 'Warehouse & Material Dispatch'],
    ['Store Incharge', 'LOG-STR', 'Warehouse & Material Dispatch'],
    ['Maintenance Technician', 'MAINT-TECH', 'Maintenance & Electrical'],
    ['Electrical Technician', 'MAINT-ELEC', 'Maintenance & Electrical'],
    ['Mechanical Engineer', 'MAINT-MECH', 'Maintenance & Electrical'],
    ['Utility Incharge', 'MAINT-UTIL', 'Maintenance & Electrical'],
    ['Accounts Executive', 'FIN-EXEC', 'Accounts & Finance'],
    ['Billing Specialist', 'FIN-BILL', 'Accounts & Finance'],
    ['HR Executive', 'HR-EXEC', 'HR & Admin'],
    ['Admin Assistant', 'HR-ADM', 'HR & Admin'],
    ['Timekeeper', 'HR-TK', 'HR & Admin'],
    ['Gate Security Incharge', 'SEC-GATE', 'Security & Safety'],
    ['Security Guard', 'SEC-GRD', 'Security & Safety'],
    ['Safety Officer', 'SEC-SAFE', 'Security & Safety'],
];
$insDes = $pdo->prepare("INSERT INTO designations (department_id, designation_name, designation_code, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())");
foreach ($designations as $ds) {
    $deptId = $reverseDeptMap[$ds[2]] ?? null;
    $insDes->execute([$deptId, $ds[0], $ds[1]]);
}
echo "✓ Initialized Designations Master\n";

// 6. Re-seed Contractors Master
$pdo->exec("TRUNCATE TABLE `contractors`");
$contractors = [
    ['TrooGood In-House (Company Rolls)', 'TrooGood HR', '040-23456789', 'hr@troogood.in'],
    ['Consultant', 'External Consultant', '', 'consultant@troogood.in'],
    ['Intern', 'University Liaison', '', 'intern@troogood.in'],
    ['Sri Sai Manpower Services', 'Ramesh Rao', '9848011223', 'srisai@manpower.com'],
    ['Apex Logistics & Workforce', 'Satish Reddy', '9988776655', 'apex@workforce.in'],
    ['Venkateshwara Enterprises', 'Nagaraju K', '9701122334', 'venkat@enterprises.com'],
    ['Global Workforce Solutions', 'Praveen V', '9876543210', 'info@globalworkforce.in'],
    ['Balaji Security & Services', 'Murali Krishna', '9849988776', 'balaji@security.com'],
    ['Sunrise Facility Management', 'Kiran Kumar', '9912345678', 'sunrise@facilities.com'],
];
$insContr = $pdo->prepare("INSERT INTO contractors (contractor_name, contact_person, phone, email, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())");
foreach ($contractors as $c) {
    $insContr->execute([$c[0], $c[1], $c[2], $c[3]]);
}
echo "✓ Initialized Contractors Master (including In-House, Consultant & Intern)\n";

// 7. Re-seed Banks Master
$pdo->exec("TRUNCATE TABLE `banks`");
$banks = [
    ['State Bank of India', 'SBIN'],
    ['HDFC Bank', 'HDFC'],
    ['ICICI Bank', 'ICIC'],
    ['Axis Bank', 'UTIB'],
    ['Punjab National Bank', 'PUNB'],
    ['Bank of Baroda', 'BARB'],
    ['Kotak Mahindra Bank', 'KKBK'],
    ['Canara Bank', 'CNRB'],
    ['Union Bank of India', 'UBIN'],
    ['IndusInd Bank', 'INDB'],
    ['Federal Bank', 'FDRL'],
    ['Yes Bank', 'YESB'],
    ['IDFC First Bank', 'IDFB'],
    ['Indian Bank', 'IDIB'],
    ['Central Bank of India', 'CBIN']
];
$insBank = $pdo->prepare("INSERT INTO banks (bank_name, ifsc_prefix, is_active, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())");
foreach ($banks as $b) {
    $insBank->execute([$b[0], $b[1]]);
}
echo "✓ Initialized Banks Master\n";

// 8. Re-seed Holiday Calendar
$pdo->exec("TRUNCATE TABLE `holiday_calendar`");
$holidays = [
    ['2026-01-01', "New Year's Day", 'National', 'ALL'],
    ['2026-01-14', 'Makar Sankranti / Pongal', 'Regional', 'ALL'],
    ['2026-01-26', 'Republic Day', 'National', 'ALL'],
    ['2026-03-04', 'Holi', 'National', 'ALL'],
    ['2026-03-20', 'Ugadi / Telugu New Year', 'Regional', 'TGK-1,TGK-2,TGK-3,TGK-4,TGR,TGT,TGH-1,TG-Mega'],
    ['2026-04-03', 'Good Friday', 'National', 'ALL'],
    ['2026-04-14', 'Dr. B.R. Ambedkar Jayanti', 'National', 'ALL'],
    ['2026-05-01', 'May Day / International Labour Day', 'National', 'ALL'],
    ['2026-06-02', 'Telangana Formation Day', 'State', 'TGK-1,TGK-2,TGK-3,TGK-4,TGH-1,TG-Mega'],
    ['2026-08-15', 'Independence Day', 'National', 'ALL'],
    ['2026-09-04', 'Janmashtami', 'National', 'ALL'],
    ['2026-09-15', 'Ganesh Chaturthi', 'Regional', 'ALL'],
    ['2026-10-02', 'Mahatma Gandhi Jayanti', 'National', 'ALL'],
    ['2026-10-20', 'Dussehra / Vijaya Dashami', 'National', 'ALL'],
    ['2026-11-08', 'Diwali / Deepavali', 'National', 'ALL'],
    ['2026-11-09', 'Govardhan Puja', 'Regional', 'ALL'],
    ['2026-12-25', 'Christmas Day', 'National', 'ALL']
];
$insHol = $pdo->prepare("INSERT INTO holiday_calendar (holiday_date, holiday_name, holiday_type, year, unit_id, is_active, created_at, updated_at) VALUES (?, ?, ?, 2026, ?, 1, NOW(), NOW())");
foreach ($holidays as $h) {
    $insHol->execute([$h[0], $h[1], $h[2], $h[3]]);
}
echo "✓ Initialized 2026 Holiday Calendar\n";

// 9. Re-seed Active SMTP Settings
$pdo->exec("TRUNCATE TABLE `smtp_settings`");
$pdo->exec("INSERT INTO `smtp_settings` (`id`, `host`, `port`, `encryption`, `username`, `password`, `from_address`, `from_name`, `is_active`, `created_at`, `updated_at`) VALUES
    (1, 'troogood.in', 465, 'ssl', 'dailymails@troogood.in', 'TgMail@1234$#@!', 'dailymails@troogood.in', 'Troo Good Alerts', 1, NOW(), NOW())");
echo "✓ Initialized SMTP Outbound Mail Server settings\n";

// 10. Re-seed Roles and Permissions
$pdo->exec("TRUNCATE TABLE `roles`");
$pdo->exec("INSERT INTO `roles` (`id`, `role_code`, `role_name`, `description`, `is_system`, `user_count`, `created_at`, `updated_at`) VALUES
    (1, 'super_admin', 'Super Administrator', 'Full system control, multi-unit access, and security configuration', 1, 1, NOW(), NOW()),
    (2, 'hr_admin', 'HR Manager', 'Workforce management, attendance corrections, and onboarding pipeline', 1, 0, NOW(), NOW()),
    (3, 'plant_head', 'Plant Operations Head', 'Factory-level monitoring, roster verification, and daily reports', 1, 0, NOW(), NOW()),
    (4, 'gate_incharge', 'Security / Gate Incharge', 'Kiosk terminal hardware operator and gate pass control', 1, 1, NOW(), NOW()),
    (5, 'accounts_finance', 'Finance & Accounts', 'Payroll locking, salary calculations, and compliance reports', 1, 0, NOW(), NOW())");

$pdo->exec("TRUNCATE TABLE `permissions`");
$pdo->exec("INSERT INTO `permissions` (`id`, `code`, `module`, `action`, `description`, `created_at`) VALUES
    (1, 'workers.view', 'Workers', 'view', 'View worker profiles and directory', NOW()),
    (2, 'workers.create', 'Workers', 'create', 'Enroll new workers', NOW()),
    (3, 'workers.edit', 'Workers', 'edit', 'Update worker profiles and shifts', NOW()),
    (4, 'attendance.view', 'Attendance', 'view', 'View attendance punches and register', NOW()),
    (5, 'attendance.override', 'Attendance', 'override', 'Override attendance status', NOW()),
    (6, 'onboarding.view', 'Onboarding', 'view', 'View candidate onboarding pipeline', NOW()),
    (7, 'onboarding.invite', 'Onboarding', 'invite', 'Invite new candidates', NOW()),
    (8, 'approvals.view', 'Approvals', 'view', 'View approvals inbox', NOW()),
    (9, 'approvals.action', 'Approvals', 'action', 'Approve or reject requests', NOW()),
    (10, 'payroll.view', 'Payroll', 'view', 'View payroll calculations', NOW()),
    (11, 'payroll.run', 'Payroll', 'run', 'Process and finalize payroll runs', NOW()),
    (12, 'kiosks.view', 'Kiosks', 'view', 'View connected kiosk devices', NOW()),
    (13, 'kiosks.manage', 'Kiosks', 'manage', 'Authorize and reset kiosk device bindings', NOW()),
    (14, 'reports.export', 'Reports', 'export', 'Export biometric and payroll reports', NOW()),
    (15, 'settings.manage', 'Settings', 'manage', 'Manage system roles, masters, and SMTP', NOW())");

$pdo->exec("TRUNCATE TABLE `role_permissions`");
$modules = ['Dashboard', 'Workers', 'Attendance', 'Onboarding', 'Approvals', 'Payroll', 'Kiosks', 'Reports', 'Settings'];
$insRP = $pdo->prepare("INSERT INTO `role_permissions` (`role_id`, `module_name`, `menu_access`, `can_view`, `can_create`, `can_edit`, `can_delete`, `can_approve`, `can_export`, `can_lock`, `unit_access_json`, `created_at`, `updated_at`) VALUES (?, ?, 1, 1, 1, 1, 1, 1, 1, 1, '[\"ALL\"]', NOW(), NOW())");
foreach ($modules as $m) {
    // Super admin full access
    $insRP->execute([1, $m]);
    // HR Manager full operational access
    $insRP->execute([2, $m]);
}
echo "✓ Initialized Roles, Permissions & Role Permission Matrix\n";


$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\n=======================================================\n";
echo "SUCCESS: Database is now 100% clean and fresh!\n";
echo "- 0 Workers\n";
echo "- 0 Candidates\n";
echo "- 0 Punches / Overrides\n";
echo "- 0 Approvals\n";
echo "- 0 Payroll items\n";
echo "- 0 Audit logs / Notifications\n";
echo "- Clean Master Dropdowns (9 Synced Factories, Departments, Roles, Contractors, Banks, Shifts)\n";
echo "- Admin login: admin@troogood.in (password: 123456)\n";
echo "- Kiosk login: tgk2@gmail.com (password: 123456, PIN: 5555)\n";
echo "=======================================================\n";
