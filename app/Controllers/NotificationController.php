<?php
/**
 * NotificationController - Multi-channel Notifications for Mobile Kiosk and Web Portal
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Helpers/MailHelper.php';

class NotificationController {

    public static function getActiveNotifications() {
        $pdo = getDbConnection();
        $readerType = $_GET['reader_type'] ?? 'mobile_kiosk';
        $readerId = trim($_GET['reader_id'] ?? ($_SERVER['HTTP_X_DEVICE_ID'] ?? 'DEFAULT-DEVICE'));
        $unit = $_GET['unit_id'] ?? 'ALL';

        $sql = "SELECT n.id, n.title, n.message, n.type, n.target_role, n.target_unit, n.created_by,
                       DATE_FORMAT(n.created_at, '%d-%b-%Y %h:%i %p') as formatted_date,
                       (CASE WHEN nr.id IS NOT NULL THEN 1 ELSE 0 END) as is_read
                FROM notifications n
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id 
                     AND nr.reader_type = ? AND nr.reader_id = ?
                WHERE (? = 'ALL' 
                   OR n.target_unit = 'ALL' 
                   OR n.target_unit = ? 
                   OR FIND_IN_SET(?, REPLACE(n.target_unit, ' ', '')) > 0)
                ORDER BY n.id DESC LIMIT 50";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$readerType, $readerId, $unit, $unit, $unit]);
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $unreadCount = 0;
        foreach ($notifs as $item) {
            if ($item['is_read'] == 0) $unreadCount++;
        }

        jsonSuccess("Active notifications loaded", [
            'unread_count' => $unreadCount,
            'notifications' => $notifs
        ]);
    }

    public static function markAsRead() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $notifId = (int)($input['notification_id'] ?? 0);
        $readerType = trim($input['reader_type'] ?? 'mobile_kiosk');
        $readerId = trim($input['reader_id'] ?? ($_SERVER['HTTP_X_DEVICE_ID'] ?? 'DEFAULT-DEVICE'));

        if (!$notifId) jsonError("Notification ID is required", 422);

        $pdo = getDbConnection();
        $stmt = $pdo->prepare("INSERT INTO notification_reads (notification_id, reader_type, reader_id, read_at)
                                VALUES (?, ?, ?, NOW())
                                ON DUPLICATE KEY UPDATE read_at = NOW()");
        $stmt->execute([$notifId, $readerType, $readerId]);

        jsonSuccess("Notification marked as read");
    }

    public static function broadcastNotification() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $title = trim($input['title'] ?? '');
        $message = trim($input['message'] ?? '');
        $type = trim($input['type'] ?? 'ANNOUNCEMENT');
        $targetRole = trim($input['target_role'] ?? 'ALL');
        $sendEmail = !empty($input['send_email']) || ($type === 'ALERT');
        
        $rawUnit = $input['target_units'] ?? $input['target_unit'] ?? 'ALL';
        if (is_array($rawUnit)) {
            $cleaned = array_values(array_filter(array_map('trim', $rawUnit)));
            if (in_array('ALL', $cleaned) || empty($cleaned)) {
                $targetUnit = 'ALL';
            } else {
                $targetUnit = implode(',', $cleaned);
            }
        } else {
            $targetUnit = trim((string)$rawUnit);
            if (empty($targetUnit)) $targetUnit = 'ALL';
        }

        $createdBy = trim($input['created_by'] ?? ($_SERVER['HTTP_X_USER_NAME'] ?? ($_SESSION['user']['full_name'] ?? 'System Super Admin')));
        if (empty($createdBy)) $createdBy = 'System Super Admin';

        if (empty($title) || empty($message)) {
            jsonError("Title and message are required", 422);
        }

        $pdo = getDbConnection();
        $stmt = $pdo->prepare("INSERT INTO notifications (title, message, target_role, target_unit, type, created_by, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$title, $message, $targetRole, $targetUnit, $type, $createdBy]);
        $id = (int)$pdo->lastInsertId();

        logAudit('WEB_PORTAL', $createdBy, 'BROADCAST_NOTIFICATION', 'notifications', $id, ['title' => $title, 'unit' => $targetUnit]);

        // Email broadcast trigger
        $mailSent = false;
        $recipientEmails = [];
        if ($sendEmail) {
            try {
                // Collect supervisor & incharge emails from users table
                $uSql = "SELECT DISTINCT email FROM users WHERE status = 'active' AND email IS NOT NULL AND email != ''";
                if ($targetUnit !== 'ALL') {
                    $uSql .= " AND (unit_id = ? OR role IN ('super_admin', 'hr_manager'))";
                    $uStmt = $pdo->prepare($uSql);
                    $uStmt->execute([$targetUnit]);
                } else {
                    $uStmt = $pdo->query($uSql);
                }
                $userEmails = $uStmt ? $uStmt->fetchAll(PDO::FETCH_COLUMN) : [];

                // Collect employee app emails if applicable
                $wEmails = [];
                if ($targetUnit === 'ALL') {
                    $wStmt = $pdo->query("SELECT DISTINCT app_email FROM workers WHERE attendance_app_mode = 'individual' AND app_email IS NOT NULL AND app_email != '' LIMIT 100");
                    $wEmails = $wStmt ? $wStmt->fetchAll(PDO::FETCH_COLUMN) : [];
                } else {
                    $wStmt = $pdo->prepare("SELECT DISTINCT app_email FROM workers WHERE unit_id = ? AND attendance_app_mode = 'individual' AND app_email IS NOT NULL AND app_email != '' LIMIT 100");
                    $wStmt->execute([$targetUnit]);
                    $wEmails = $wStmt ? $wStmt->fetchAll(PDO::FETCH_COLUMN) : [];
                }

                $recipientEmails = array_values(array_unique(array_filter(array_merge($userEmails, $wEmails))));
                if (!empty($recipientEmails)) {
                    $mailResult = MailHelper::sendBroadcastAlert($recipientEmails, $title, $message, strtolower($type), $targetUnit);
                    $mailSent = !empty($mailResult['success']);
                }
            } catch (Exception $e) {
                error_log("Failed to dispatch broadcast alert email: " . $e->getMessage());
            }
        }

        jsonSuccess("Notification broadcasted successfully" . ($mailSent ? " & emailed to " . count($recipientEmails) . " recipients" : ""), [
            'id' => $id,
            'mail_sent' => $mailSent,
            'recipients_count' => count($recipientEmails)
        ]);
    }
}
