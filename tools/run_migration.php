<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDbConnection();
foreach (['departments', 'designations', 'contractors', 'reporting_managers', 'banks'] as $tbl) {
    echo "=== $tbl (Count: " . $pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn() . ") ===\n";
    $rows = $pdo->query("SELECT * FROM `$tbl` LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
}
