<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/EnrollmentController.php';

$pdo = getDbConnection();
$workers = $pdo->query("SELECT id, worker_code, full_name, unit_id FROM workers WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($workers) . " workers. Auto-mapping to portal logins...\n";
foreach ($workers as $w) {
    EnrollmentController::autoMapWorkerToUnitLogins($pdo, $w['id'], $w['unit_id'], 'INIT-MIGRATION');
    echo "Worker #{$w['id']} ({$w['full_name']} - Unit: {$w['unit_id']}) mapped.\n";
}

echo "\nCURRENT WORKER LOGIN ASSIGNMENTS:\n";
print_r($pdo->query("SELECT a.id, a.worker_id, w.full_name as worker_name, a.user_id, u.full_name as user_name, u.unit_id as user_unit
                     FROM worker_login_assignments a
                     JOIN workers w ON w.id = a.worker_id
                     JOIN users u ON u.id = a.user_id
                     WHERE a.deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC));
