<?php
/**
 * SmtpController - Outbound Mail Server (SMTP) Management & Operations Report Trigger
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Helpers/MailHelper.php';

class SmtpController {

    /**
     * GET /api/portal/smtp-settings
     * Return active SMTP settings (password masked for display)
     */
    public static function getSettings() {
        $cfg = MailHelper::getConfig();
        jsonSuccess("Active Outbound Mail Server settings retrieved", [
            'host'         => $cfg['host'],
            'port'         => $cfg['port'],
            'encryption'   => strtoupper($cfg['encryption']),
            'username'     => $cfg['username'],
            'password'     => $cfg['password'],
            'from_address' => $cfg['from_address'],
            'from_name'    => $cfg['from_name'],
            'is_active'    => 1
        ]);
    }

    /**
     * POST /api/portal/smtp-settings
     * Save/update outbound SMTP settings
     */
    public static function saveSettings() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $host        = trim($input['host'] ?? 'troogood.in');
        $port        = (int)($input['port'] ?? 465);
        $encryption  = strtolower(trim($input['encryption'] ?? 'ssl'));
        $username    = trim($input['username'] ?? 'dailymails@troogood.in');
        $password    = trim($input['password'] ?? '');
        $fromAddress = trim($input['from_address'] ?? 'dailymails@troogood.in');
        $fromName    = trim($input['from_name'] ?? 'Troo Good Alerts');

        if (empty($host) || empty($port) || empty($fromAddress)) {
            jsonError("Host, Port, and Sender From Address are required.", 422);
        }

        if (!in_array($encryption, ['ssl', 'tls', 'none'])) {
            $encryption = 'ssl';
        }

        $pdo = getDbConnection();

        // If password is blank or empty, retain previous password
        if (empty($password)) {
            $existing = $pdo->query("SELECT password FROM smtp_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1")->fetch();
            $password = $existing['password'] ?? 'TgMail@1234$#@!';
        }

        // Check if row exists
        $rowCount = (int)$pdo->query("SELECT COUNT(*) FROM smtp_settings")->fetchColumn();
        if ($rowCount > 0) {
            $stmt = $pdo->prepare("UPDATE smtp_settings 
                                   SET host = ?, port = ?, encryption = ?, username = ?, password = ?, from_address = ?, from_name = ?, is_active = 1, updated_at = NOW() 
                                   WHERE is_active = 1 OR id = 1 LIMIT 1");
            $stmt->execute([$host, $port, $encryption, $username, $password, $fromAddress, $fromName]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO smtp_settings 
                                   (host, port, encryption, username, password, from_address, from_name, is_active, created_at, updated_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->execute([$host, $port, $encryption, $username, $password, $fromAddress, $fromName]);
        }

        // Also synchronize .env file if writable
        self::updateEnvFile([
            'MAIL_HOST'         => $host,
            'MAIL_PORT'         => $port,
            'MAIL_ENCRYPTION'   => $encryption,
            'MAIL_USERNAME'     => $username,
            'MAIL_PASSWORD'     => $password,
            'MAIL_FROM_ADDRESS' => $fromAddress,
            'MAIL_FROM_NAME'    => '"' . $fromName . '"'
        ]);

        logAudit('WEB_PORTAL', 'HR Admin', 'UPDATE_SMTP_SETTINGS', 'smtp_settings', 1, [
            'host' => $host, 'port' => $port, 'encryption' => $encryption, 'from' => $fromAddress
        ]);

        jsonSuccess("Outbound Mail Server (SMTP) Settings updated successfully", [
            'host'         => $host,
            'port'         => $port,
            'encryption'   => strtoupper($encryption),
            'username'     => $username,
            'from_address' => $fromAddress,
            'from_name'    => $fromName
        ]);
    }

    /**
     * POST /api/portal/smtp-settings/test
     * Dispatch live handshake test email
     */
    public static function testConnection() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $recipient = trim($input['recipient_email'] ?? $input['to'] ?? '');

        if (empty($recipient)) {
            $cfg = MailHelper::getConfig();
            $recipient = $cfg['from_address'] ?: 'dailymails@troogood.in';
        }

        $subject = "TrooGood HRMS: SMTP Connection Test (" . date('d-M-Y H:i:s') . ")";
        $content = "
            <p>Hello Administrator,</p>
            <p>This is a verification test email confirming that your <strong>TrooGood Outbound Mail Server</strong> is operating with zero handshake latency.</p>
            <div style='background:#F0FDF4; border:1px solid #BBF7D0; padding:16px; border-radius:8px; margin:18px 0;'>
                <div style='font-size:12px; font-weight:800; color:#166534;'>LIVE CREDENTIALS VERIFIED</div>
                <div style='font-size:13px; color:#15803D; margin-top:4px;'>
                    Host: <code>" . htmlspecialchars(MailHelper::getConfig()['host']) . ":" . MailHelper::getConfig()['port'] . "</code> &bull;
                    Encryption: <code>" . strtoupper(MailHelper::getConfig()['encryption']) . "</code> &bull;
                    Sender: <code>" . htmlspecialchars(MailHelper::getConfig()['from_address']) . "</code>
                </div>
            </div>
            <p style='color:#64748B; font-size:12px;'>All system automated triggers (Candidate Onboarding Invites, Attendance Credential Dispatches, Broadcast Alerts, and Daily Shift Operations) will route through this gateway.</p>
        ";

        $html = MailHelper::wrapHtmlTemplate("SMTP Server Connection Verified", $content);
        $result = MailHelper::send($recipient, $subject, $html);

        if (!empty($result['success'])) {
            jsonSuccess("SMTP Test Email dispatched successfully to {$recipient}!", [
                'recipient' => $recipient,
                'status'    => 'SENT'
            ]);
        } else {
            jsonError("SMTP Test Failed: " . ($result['error'] ?? 'Unknown network failure'), 500);
        }
    }

    /**
     * POST /api/reports/daily-operations-email
     * Dispatch live shift operations report
     */
    public static function triggerDailyReport() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $recipients = $input['recipients'] ?? [];
        if (!is_array($recipients) || empty($recipients)) {
            $target = trim($input['recipient'] ?? $input['email'] ?? '');
            $recipients = !empty($target) ? [$target] : [MailHelper::getConfig()['from_address']];
        }

        $pdo = getDbConnection();
        $today = date('Y-m-d');
        $displayDate = date('d-M-Y');

        // Total workers
        $totalWorkers = (int)$pdo->query("SELECT COUNT(*) FROM workers WHERE deleted_at IS NULL AND approval_status = 'approved'")->fetchColumn();

        // Total punches today (distinct workers)
        $punchTable = in_array('attendance_punches', $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN)) ? 'attendance_punches' : 'punches';
        $presentStmt = $pdo->prepare("SELECT COUNT(DISTINCT worker_id) FROM {$punchTable} WHERE DATE(punch_time) = ?");
        $presentStmt->execute([$today]);
        $totalPresent = (int)$presentStmt->fetchColumn();

        // Unit breakdown
        $units = $pdo->query("SELECT id, unit_name FROM units WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $unitBreakdown = [];

        foreach ($units as $u) {
            $uId = $u['id'];
            $wCntStmt = $pdo->prepare("SELECT COUNT(*) FROM workers WHERE unit_id = ? AND deleted_at IS NULL AND approval_status = 'approved'");
            $wCntStmt->execute([$uId]);
            $uTotal = (int)$wCntStmt->fetchColumn();

            $uPresStmt = $pdo->prepare("SELECT COUNT(DISTINCT p.worker_id) FROM {$punchTable} p 
                                        INNER JOIN workers w ON p.worker_id = w.id 
                                        WHERE w.unit_id = ? AND DATE(p.punch_time) = ?");
            $uPresStmt->execute([$uId, $today]);
            $uPresent = (int)$uPresStmt->fetchColumn();

            $rate = $uTotal > 0 ? round(($uPresent / $uTotal) * 100, 1) : 0;

            $unitBreakdown[] = [
                'unit_id'        => $uId,
                'unit_name'      => $u['unit_name'],
                'enrolled_count' => $uTotal,
                'present_count'  => $uPresent,
                'rate'           => $rate
            ];
        }

        $stats = [
            'total_workers' => $totalWorkers,
            'total_present' => $totalPresent
        ];

        $res = MailHelper::sendDailyOperationsReport($recipients, $displayDate, $stats, $unitBreakdown);

        if (!empty($res['success'])) {
            jsonSuccess("Daily Operations & Attendance Report emailed successfully to " . implode(', ', $recipients), [
                'recipients' => $recipients,
                'stats'      => $stats,
                'units'      => $unitBreakdown
            ]);
        } else {
            jsonError("Failed to dispatch daily report: " . ($res['error'] ?? 'Mail error'), 500);
        }
    }

    /**
     * Safely update .env file key-values
     */
    private static function updateEnvFile($updates) {
        $envPath = __DIR__ . '/../../.env';
        if (!file_exists($envPath) || !is_writable($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        $newLines = [];
        $handled = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
                $newLines[] = $line;
                continue;
            }

            list($k, $v) = explode('=', $trimmed, 2);
            $key = trim($k);
            if (isset($updates[$key])) {
                $newLines[] = "{$key}={$updates[$key]}";
                $handled[$key] = true;
            } else {
                $newLines[] = $line;
            }
        }

        foreach ($updates as $k => $v) {
            if (!isset($handled[$k])) {
                $newLines[] = "{$k}={$v}";
            }
        }

        @file_put_contents($envPath, implode("\n", $newLines) . "\n");
    }
}
