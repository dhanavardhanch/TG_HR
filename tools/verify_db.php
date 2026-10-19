<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDbConnection();

echo "=== TABLE ROW COUNTS ===\n";
$tables = [
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
    'universal_registrations',
    'worker_login_assignments',
    'worker_transfers',
    'kiosk_devices',
    'units',
    'shifts',
    'users'
];

foreach ($tables as $t) {
    $cnt = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    echo sprintf("%-28s : %d rows\n", $t, $cnt);
}

echo "\n=== UNITS IN DB ===\n";
$units = $pdo->query("SELECT id, unit_name, unit_code, city, state, is_active FROM units ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
foreach ($units as $u) {
    echo sprintf("%-15s | %-15s | %-10s | %-20s | %-15s | active: %d\n",
        $u['id'], $u['unit_name'], $u['unit_code'], $u['city'], $u['state'], $u['is_active']
    );
}

echo "\n=== SHIFTS IN DB ===\n";
$shifts = $pdo->query("SELECT id, shift_code, shift_name, start_time, end_time, is_active FROM shifts ORDER BY start_time")->fetchAll(PDO::FETCH_ASSOC);
foreach ($shifts as $s) {
    echo sprintf("%-10s | %-20s | %s - %s | active: %d\n",
        $s['shift_code'], $s['shift_name'], $s['start_time'], $s['end_time'], $s['is_active']
    );
}
