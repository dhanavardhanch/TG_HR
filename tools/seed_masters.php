<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDbConnection();

// Allow unit_id in departments to be NULL and drop FK if it restricts global departments
try {
    $pdo->exec("ALTER TABLE departments DROP FOREIGN KEY departments_ibfk_1");
} catch (\Exception $e) {}

try {
    $pdo->exec("ALTER TABLE departments MODIFY COLUMN unit_id VARCHAR(50) NULL DEFAULT NULL");
} catch (\Exception $e) {}

// Insert clean general departments if not existing
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

foreach ($depts as $d) {
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE department_name = ? LIMIT 1");
    $stmt->execute([$d[0]]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO departments (department_name, department_code, unit_id, is_active) VALUES (?, ?, ?, 1)")
            ->execute([$d[0], $d[1], $d[2]]);
    }
}

// Fetch departments map
$deptMap = $pdo->query("SELECT id, department_name FROM departments")->fetchAll(PDO::FETCH_KEY_PAIR);
$reverseDeptMap = array_flip($deptMap);

// Seed Designations linked to departments
$designations = [
    // Production Line 1 & 2
    ['Machine Operator', 'PROD-OPR', 'Chikki Production Line 1'],
    ['Production Supervisor', 'PROD-SUP', 'Chikki Production Line 1'],
    ['Line Helper', 'PROD-HLP', 'Chikki Production Line 1'],
    ['Production Executive', 'PROD-EXEC', 'Chikki Production Line 1'],
    ['Plant Manager', 'PROD-MGR', 'Chikki Production Line 1'],
    ['Machine Operator', 'PROD-OPR-2', 'Chikki Production Line 2'],
    ['Production Supervisor', 'PROD-SUP-2', 'Chikki Production Line 2'],
    ['Line Helper', 'PROD-HLP-2', 'Chikki Production Line 2'],
    // Packaging
    ['Packing Operator', 'PKG-OPR', 'Packaging & Carton Packing'],
    ['Packaging Supervisor', 'PKG-SUP', 'Packaging & Carton Packing'],
    ['Sorter / Carton Packer', 'PKG-SRT', 'Packaging & Carton Packing'],
    ['Sealing Specialist', 'PKG-SEAL', 'Packaging & Carton Packing'],
    // QA & Lab
    ['Quality Inspector', 'QA-INSP', 'Quality Assurance & Lab'],
    ['QA Executive', 'QA-EXEC', 'Quality Assurance & Lab'],
    ['Lab Analyst', 'QA-LAB', 'Quality Assurance & Lab'],
    ['Quality Manager', 'QA-MGR', 'Quality Assurance & Lab'],
    // Warehouse & Dispatch
    ['Warehouse Executive', 'LOG-EXEC', 'Warehouse & Material Dispatch'],
    ['Forklift Operator', 'LOG-FORK', 'Warehouse & Material Dispatch'],
    ['Dispatch Coordinator', 'LOG-DISP', 'Warehouse & Material Dispatch'],
    ['Store Incharge', 'LOG-STR', 'Warehouse & Material Dispatch'],
    // Maintenance
    ['Maintenance Technician', 'MAINT-TECH', 'Maintenance & Electrical'],
    ['Electrical Technician', 'MAINT-ELEC', 'Maintenance & Electrical'],
    ['Mechanical Engineer', 'MAINT-MECH', 'Maintenance & Electrical'],
    ['Utility Incharge', 'MAINT-UTIL', 'Maintenance & Electrical'],
    // Accounts
    ['Accounts Executive', 'FIN-EXEC', 'Accounts & Finance'],
    ['Billing Specialist', 'FIN-BILL', 'Accounts & Finance'],
    // HR & Admin
    ['HR Executive', 'HR-EXEC', 'HR & Admin'],
    ['Admin Assistant', 'HR-ADM', 'HR & Admin'],
    ['Timekeeper', 'HR-TK', 'HR & Admin'],
    // Security & Safety
    ['Gate Security Incharge', 'SEC-GATE', 'Security & Safety'],
    ['Security Guard', 'SEC-GRD', 'Security & Safety'],
    ['Safety Officer', 'SEC-SAFE', 'Security & Safety'],
];

$pdo->exec("TRUNCATE TABLE designations");
$insDes = $pdo->prepare("INSERT INTO designations (department_id, designation_name, designation_code, is_active) VALUES (?, ?, ?, 1)");
foreach ($designations as $ds) {
    $deptId = $reverseDeptMap[$ds[2]] ?? null;
    $insDes->execute([$deptId, $ds[0], $ds[1]]);
}

// Seed Contractors
$contractors = [
    ['Sri Sai Manpower Services', 'Ramesh Rao', '9848011223', 'srisai@manpower.com'],
    ['Apex Logistics & Workforce', 'Satish Reddy', '9988776655', 'apex@workforce.in'],
    ['Venkateshwara Enterprises', 'Nagaraju K', '9701122334', 'venkat@enterprises.com'],
    ['Global Workforce Solutions', 'Praveen V', '9876543210', 'info@globalworkforce.in'],
    ['Balaji Security & Services', 'Murali Krishna', '9849988776', 'balaji@security.com'],
    ['Sunrise Facility Management', 'Kiran Kumar', '9912345678', 'sunrise@facilities.com'],
];
$pdo->exec("TRUNCATE TABLE contractors");
$insContr = $pdo->prepare("INSERT INTO contractors (contractor_name, contact_person, phone, email, is_active) VALUES (?, ?, ?, ?, 1)");
foreach ($contractors as $c) {
    $insContr->execute($c);
}

// Seed Reporting Managers
$managers = [
    ['CHANDRA', 'Chikki Production Line 1', '9848012345', 'chandra@troogood.com'],
    ['SURESH KUMAR', 'Chikki Production Line 2', '9848023456', 'suresh@troogood.com'],
    ['RAJESH SHARMA', 'Warehouse & Material Dispatch', '9848034567', 'rajesh@troogood.com'],
    ['VENKAT RAO', 'Packaging & Carton Packing', '9848045678', 'venkat@troogood.com'],
    ['LAKSHMI NARAYANA', 'Quality Assurance & Lab', '9848056789', 'lakshmi@troogood.com'],
    ['ANAND REDDY', 'HR & Admin', '9848067890', 'anand@troogood.com'],
];
$pdo->exec("TRUNCATE TABLE reporting_managers");
$insMgr = $pdo->prepare("INSERT INTO reporting_managers (manager_name, department_id, phone, email, is_active) VALUES (?, ?, ?, ?, 1)");
foreach ($managers as $m) {
    $deptId = $reverseDeptMap[$m[1]] ?? null;
    $insMgr->execute([$m[0], $deptId, $m[2], $m[3]]);
}

// Seed Banks
$banks = [
    ['State Bank of India', 'SBIN'],
    ['HDFC Bank', 'HDFC'],
    ['ICICI Bank', 'ICIC'],
    ['Axis Bank', 'UTIB'],
    ['Punjab National Bank', 'PUNB'],
    ['Bank of Baroda', 'BARB'],
    ['Canara Bank', 'CNRB'],
    ['Union Bank of India', 'UBIN'],
    ['Kotak Mahindra Bank', 'KKBK'],
    ['IndusInd Bank', 'INDB'],
    ['Indian Bank', 'IDIB'],
    ['Central Bank of India', 'CBIN'],
    ['Yes Bank', 'YESB'],
    ['Bank of India', 'BKID'],
    ['Federal Bank', 'FDRL'],
];
$pdo->exec("TRUNCATE TABLE banks");
$insBank = $pdo->prepare("INSERT INTO banks (bank_name, ifsc_prefix, is_active) VALUES (?, ?, 1)");
foreach ($banks as $b) {
    $insBank->execute($b);
}

echo "Master tables seeded successfully!\n";
foreach (['departments', 'designations', 'contractors', 'reporting_managers', 'banks'] as $tbl) {
    echo "$tbl: " . $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn() . " rows\n";
}
