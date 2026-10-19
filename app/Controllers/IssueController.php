<?php
/**
 * IssueController - Helpdesk & Issue Reporting API for Kiosk & Web Portal
 */

require_once __DIR__ . '/../../config/db.php';

class IssueController {

    public static function createIssue() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $category = trim($input['category'] ?? 'General Issue');
        $severity = trim($input['severity'] ?? 'medium');
        $deviceId = trim($input['device_id'] ?? ($_SERVER['HTTP_X_DEVICE_ID'] ?? 'DEFAULT'));
        $unitId = trim($input['unit_id'] ?? '');
        $reportedBy = trim($input['reported_by_email'] ?? 'supervisor');
        $workerId = !empty($input['worker_id']) ? intval($input['worker_id']) : null;
        $workerCode = trim($input['worker_code'] ?? '');
        $workerName = trim($input['worker_name'] ?? '');

        if (empty($title) || empty($description)) {
            jsonError("Title and description are required to raise an issue.", 400);
        }

        $pdo = getDbConnection();
        if (!$pdo) {
            jsonError("Database unavailable", 500);
        }

        $ticketNumber = 'TICK-' . strtoupper(substr(uniqid(), -6)) . '-' . date('dmy');

        $stmt = $pdo->prepare("INSERT INTO support_issues 
            (ticket_number, device_id, unit_id, reported_by_email, worker_id, worker_code, worker_name, category, severity, title, description, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW(), NOW())");

        $stmt->execute([
            $ticketNumber,
            $deviceId,
            $unitId,
            $reportedBy,
            $workerId,
            $workerCode,
            $workerName,
            $category,
            $severity,
            $title,
            $description
        ]);

        $issueId = $pdo->lastInsertId();

        logAudit($deviceId, $reportedBy, 'RAISE_ISSUE', 'support_issues', $issueId, "Issue #$ticketNumber raised: $title");

        jsonSuccess("Support ticket raised successfully. Our backend team is reviewing.", [
            'id' => $issueId,
            'ticket_number' => $ticketNumber,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public static function listIssues() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $unitId = trim($_GET['unit_id'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $sql = "SELECT * FROM support_issues WHERE 1=1";
        $params = [];

        if (!empty($unitId)) {
            $sql .= " AND unit_id = ?";
            $params[] = $unitId;
        }

        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY id DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $issues = $stmt->fetchAll();

        jsonSuccess("Support issues fetched", [
            'total' => count($issues),
            'issues' => $issues
        ]);
    }

    public static function updateIssue() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = intval($input['id'] ?? 0);
        $status = trim($input['status'] ?? '');
        $notes = trim($input['resolution_notes'] ?? '');

        if (!$id || empty($status)) {
            jsonError("Issue ID and status are required.", 400);
        }

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $stmt = $pdo->prepare("UPDATE support_issues SET status = ?, resolution_notes = ?, resolved_at = IF(? = 'resolved', NOW(), resolved_at), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $notes, $status, $id]);

        jsonSuccess("Issue updated successfully.");
    }
}
