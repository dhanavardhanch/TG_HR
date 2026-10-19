<?php
/**
 * Troogood HRMS Master Database & Utility Configuration
 * Database: Troogood_HR_APP
 * Compatible with XAMPP Local & BigRock cPanel Shared Hosting
 */

// Global CORS & JSON Headers
function initApiHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Device-ID, X-Device-UUID");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

// Load .env Configuration
function loadEnv() {
    static $env = null;
    if ($env !== null) return $env;

    $env = [
        'DB_HOST' => getenv('DB_HOST') ?: '127.0.0.1',
        'DB_PORT' => getenv('DB_PORT') ?: '3306',
        'DB_DATABASE' => getenv('DB_DATABASE') ?: 'Troogood_HR_APP',
        'DB_USERNAME' => getenv('DB_USERNAME') ?: 'root',
        'DB_PASSWORD' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
        'APP_NAME' => getenv('APP_NAME') ?: 'Troogood HRMS',
        'APP_URL' => getenv('APP_URL') ?: 'http://127.0.0.1:8000',
        'APP_DEBUG' => true
    ];

    $envFile = dirname(__DIR__) . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value, "\"' ");
            }
        }
    }
    return $env;
}

// Master PDO Database Connection (Singleton)
function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $env = loadEnv();
        $host = $env['DB_HOST'] ?? '127.0.0.1';
        $port = $env['DB_PORT'] ?? '3306';
        $db   = $env['DB_DATABASE'] ?? 'Troogood_HR_APP';
        $user = $env['DB_USERNAME'] ?? 'root';
        $pass = $env['DB_PASSWORD'] ?? '';
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        try {
            date_default_timezone_set('Asia/Kolkata');
            $pdo = new PDO($dsn, $user, $pass, $options);
            $pdo->exec("SET time_zone = '+05:30'");
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

// Forensic Audit Logger (Every table mutation logs here)
function logAudit($deviceId, $userIdentifier, $action, $entityType, $entityId, $details = null, $oldValues = null, $newValues = null) {
    if (empty($userIdentifier) || $userIdentifier === 'HR Admin' || $userIdentifier === 'admin' || $userIdentifier === 'WEB_PORTAL') {
        if (!empty($_SERVER['HTTP_X_USER_NAME'])) {
            $userIdentifier = trim($_SERVER['HTTP_X_USER_NAME']);
        } elseif (!empty($_SESSION['user']['full_name'])) {
            $userIdentifier = trim($_SESSION['user']['full_name']);
        } elseif (!empty($_SESSION['user']['name'])) {
            $userIdentifier = trim($_SESSION['user']['name']);
        } elseif ($deviceId === 'WEB_PORTAL' || $deviceId === 'WEB-PORTAL') {
            $userIdentifier = 'System Super Admin';
        }
    }
    $pdo = getDbConnection();
    if (!$pdo) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (device_id, user_identifier, action, entity_type, entity_id, details, old_values, new_values, ip_address, created_at, updated_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([
            $deviceId,
            $userIdentifier,
            $action,
            $entityType,
            (string)$entityId,
            is_array($details) ? json_encode($details) : $details,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

// JSON API Response Helper
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// JSON Success Helper
function jsonSuccess($message, $data = null, $statusCode = 200) {
    $res = [
        'status' => 'success',
        'success' => true,
        'message' => $message
    ];
    if ($data !== null) {
        $res['data'] = $data;
    }
    jsonResponse($res, $statusCode);
}

// JSON Error Helper
function jsonError($message, $statusCode = 400, $extra = []) {
    $res = array_merge([
        'status' => 'error',
        'success' => false,
        'message' => $message
    ], $extra);
    jsonResponse($res, $statusCode);
}
