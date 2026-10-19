<?php
require_once __DIR__ . '/../config/db.php';
$pdo = getDbConnection();
$users = $pdo->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
echo "USERS:\n";
print_r($users);

$roles = $pdo->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
echo "ROLES:\n";
print_r($roles);
