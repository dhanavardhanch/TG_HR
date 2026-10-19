<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDbConnection();

$pdo->exec("ALTER TABLE workers MODIFY worker_code VARCHAR(50) NULL DEFAULT NULL");
$pdo->exec("ALTER TABLE universal_registrations MODIFY employee_code VARCHAR(50) NULL DEFAULT NULL");

echo "Altered workers.worker_code and universal_registrations.employee_code to NULLABLE successfully.\n";

$stmt = $pdo->query("SHOW COLUMNS FROM workers LIKE 'worker_code'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
