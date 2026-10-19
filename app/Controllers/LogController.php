<?php
/**
 * LogController - System Error Logs Tracking Engine
 * Separates mobile app, web portal, API gateway, and sync errors
 */

require_once __DIR__ . '/../../config/db.php';

class LogController {

    public static function logError() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $source = trim($input['source'] ?? 'mobile_app');
        $severity = strtoupper(trim($input['severity'] ?? 'ERROR'));
        $message = trim($input['error_message'] ?? $input['message'] ?? 'Unknown error occurred');
        $stackTrace = $input['stack_trace'] ?? null;
        $userOrCode = $input['user_id_or_code'] ?? $input['worker_code'] ?? null;
        $deviceInfo = $input['device_info'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null);
        $route = $input['route_or_screen'] ?? $input['screen'] ?? null;

        $pdo = getDbConnection();
        $stmt = $pdo->prepare("INSERT INTO system_error_logs 
            (source, severity, error_message, stack_trace, user_id_or_code, device_info, route_or_screen, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$source, $severity, $message, $stackTrace, $userOrCode, $deviceInfo, $route]);
        $id = (int)$pdo->lastInsertId();

        jsonSuccess("Error logged successfully", ['id' => $id]);
    }

    public static function listLogs() {
        $pdo = getDbConnection();
        $source = $_GET['source'] ?? 'all';
        $severity = $_GET['severity'] ?? 'all';
        $search = trim($_GET['search'] ?? '');
        $limit = min(200, max(20, (int)($_GET['limit'] ?? 100)));

        $sql = "SELECT id, source, severity, error_message, stack_trace, user_id_or_code, device_info, route_or_screen,
                       DATE_FORMAT(created_at, '%d-%b-%Y %h:%i:%s %p') as formatted_time,
                       created_at
                FROM system_error_logs 
                WHERE 1=1";
        $params = [];

        if ($source !== 'all' && !empty($source)) {
            $sql .= " AND source = ?";
            $params[] = $source;
        }
        if ($severity !== 'all' && !empty($severity)) {
            $sql .= " AND severity = ?";
            $params[] = $severity;
        }
        if (!empty($search)) {
            $sql .= " AND (error_message LIKE ? OR user_id_or_code LIKE ? OR route_or_screen LIKE ? OR device_info LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY id DESC LIMIT $limit";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Source counts
        $counts = [
            'all' => (int)$pdo->query("SELECT COUNT(*) FROM system_error_logs")->fetchColumn(),
            'mobile_app' => (int)$pdo->query("SELECT COUNT(*) FROM system_error_logs WHERE source = 'mobile_app'")->fetchColumn(),
            'web_portal' => (int)$pdo->query("SELECT COUNT(*) FROM system_error_logs WHERE source = 'web_portal'")->fetchColumn(),
            'api' => (int)$pdo->query("SELECT COUNT(*) FROM system_error_logs WHERE source = 'api'")->fetchColumn(),
            'sync' => (int)$pdo->query("SELECT COUNT(*) FROM system_error_logs WHERE source = 'sync'")->fetchColumn(),
        ];

        jsonSuccess("Error logs retrieved", [
            'counts' => $counts,
            'logs' => $logs
        ]);
    }

    public static function clearLogs() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $source = $input['source'] ?? 'all';

        $pdo = getDbConnection();
        if ($source === 'all') {
            $pdo->exec("TRUNCATE TABLE system_error_logs");
        } else {
            $stmt = $pdo->prepare("DELETE FROM system_error_logs WHERE source = ?");
            $stmt->execute([$source]);
        }

        logAudit('WEB_PORTAL', 'HR Admin', 'CLEAR_ERROR_LOGS', 'system_error_logs', 0, ['source' => $source]);
        jsonSuccess("Error logs cleared successfully");
    }
}
