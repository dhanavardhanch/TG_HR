<?php
/**
 * MailHelper - Enterprise Outbound SMTP Client & Mail Notification Dispatcher
 * Directly connects via SSL (port 465) / TLS to the configured SMTP Server.
 * Supports HTML templates, attachments, multi-recipients, and audit tracking.
 */

require_once __DIR__ . '/../../config/db.php';

class MailHelper {

    /**
     * Retrieve active SMTP configuration from database or .env
     */
    public static function getConfig() {
        $pdo = getDbConnection();
        if ($pdo) {
            try {
                $stmt = $pdo->query("SELECT * FROM smtp_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
                $cfg = $stmt->fetch();
                if ($cfg) {
                    return [
                        'host'         => $cfg['host'] ?: 'troogood.in',
                        'port'         => (int)($cfg['port'] ?: 465),
                        'encryption'   => strtolower($cfg['encryption'] ?: 'ssl'),
                        'username'     => $cfg['username'] ?: 'dailymails@troogood.in',
                        'password'     => $cfg['password'] ?: 'TgMail@1234$#@!',
                        'from_address' => $cfg['from_address'] ?: 'dailymails@troogood.in',
                        'from_name'    => $cfg['from_name'] ?: 'Troo Good Alerts'
                    ];
                }
            } catch (Exception $e) {
                error_log("Failed to load smtp_settings from DB: " . $e->getMessage());
            }
        }

        $env = loadEnv();
        return [
            'host'         => $env['MAIL_HOST'] ?? 'troogood.in',
            'port'         => (int)($env['MAIL_PORT'] ?? 465),
            'encryption'   => strtolower($env['MAIL_ENCRYPTION'] ?? 'ssl'),
            'username'     => $env['MAIL_USERNAME'] ?? 'dailymails@troogood.in',
            'password'     => $env['MAIL_PASSWORD'] ?? 'TgMail@1234$#@!',
            'from_address' => $env['MAIL_FROM_ADDRESS'] ?? 'dailymails@troogood.in',
            'from_name'    => $env['MAIL_FROM_NAME'] ?? 'Troo Good Alerts'
        ];
    }

    /**
     * Helper to read multi-line SMTP responses
     */
    private static function readResponse($fp) {
        $resp = '';
        while ($line = fgets($fp, 512)) {
            $resp .= $line;
            if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $resp;
    }

    /**
     * Core SMTP Dispatcher
     *
     * @param string|array $to Single email or array of emails
     * @param string $subject
     * @param string $htmlContent
     * @param array $attachments Array of ['name' => 'filename.pdf', 'content' => binary/string, 'type' => 'application/pdf']
     * @return array ['success' => bool, 'message' => string, 'error' => string|null]
     */
    public static function send($to, $subject, $htmlContent, $attachments = []) {
        $recipients = is_array($to) ? array_filter(array_map('trim', $to)) : [trim($to)];
        if (empty($recipients)) {
            return ['success' => false, 'error' => 'No valid recipient email provided'];
        }

        $cfg = self::getConfig();
        $host = $cfg['host'];
        $port = $cfg['port'];
        $encryption = $cfg['encryption'];
        $username = $cfg['username'];
        $password = $cfg['password'];
        $fromEmail = $cfg['from_address'];
        $fromName = $cfg['from_name'];

        $socketPrefix = ($encryption === 'ssl' || $port === 465) ? 'ssl://' : '';
        $remoteAddress = "{$socketPrefix}{$host}:{$port}";

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);

        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client($remoteAddress, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $context);

        if (!$fp) {
            $err = "SMTP Connection Failed to {$remoteAddress}: {$errstr} ({$errno})";
            error_log($err);
            logAudit('SYSTEM', $fromEmail, 'MAIL_ERROR', 'email', $recipients[0], $err);
            return ['success' => false, 'error' => $err];
        }

        try {
            // 1. Read Greeting Banner
            $banner = self::readResponse($fp);
            if (substr($banner, 0, 3) !== '220') {
                throw new Exception("Invalid greeting banner: " . trim($banner));
            }

            // 2. EHLO Handshake
            fputs($fp, "EHLO {$host}\r\n");
            $ehlo = self::readResponse($fp);
            if (substr($ehlo, 0, 3) !== '250') {
                throw new Exception("EHLO rejected: " . trim($ehlo));
            }

            // 3. STARTTLS if required and port 587
            if ($encryption === 'tls' && $port === 587) {
                fputs($fp, "STARTTLS\r\n");
                $tlsResp = self::readResponse($fp);
                if (substr($tlsResp, 0, 3) === '220') {
                    stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                    fputs($fp, "EHLO {$host}\r\n");
                    self::readResponse($fp);
                }
            }

            // 4. Authenticate
            fputs($fp, "AUTH LOGIN\r\n");
            $authResp = self::readResponse($fp);
            if (substr($authResp, 0, 3) !== '334') {
                throw new Exception("AUTH LOGIN rejected: " . trim($authResp));
            }

            fputs($fp, base64_encode($username) . "\r\n");
            $userResp = self::readResponse($fp);
            if (substr($userResp, 0, 3) !== '334') {
                throw new Exception("Username rejected: " . trim($userResp));
            }

            fputs($fp, base64_encode($password) . "\r\n");
            $passResp = self::readResponse($fp);
            if (substr($passResp, 0, 3) !== '235') {
                throw new Exception("Password authentication failed: " . trim($passResp));
            }

            // 5. MAIL FROM
            fputs($fp, "MAIL FROM:<{$fromEmail}>\r\n");
            $mailFromResp = self::readResponse($fp);
            if (substr($mailFromResp, 0, 3) !== '250') {
                throw new Exception("MAIL FROM rejected: " . trim($mailFromResp));
            }

            // 6. RCPT TO for all recipients
            foreach ($recipients as $rcpt) {
                fputs($fp, "RCPT TO:<{$rcpt}>\r\n");
                $rcptResp = self::readResponse($fp);
                if (substr($rcptResp, 0, 3) !== '250' && substr($rcptResp, 0, 3) !== '251') {
                    throw new Exception("Recipient <{$rcpt}> rejected: " . trim($rcptResp));
                }
            }

            // 7. DATA
            fputs($fp, "DATA\r\n");
            $dataResp = self::readResponse($fp);
            if (substr($dataResp, 0, 3) !== '354') {
                throw new Exception("DATA command rejected: " . trim($dataResp));
            }

            // 8. Build RFC 2822 MIME Message
            $boundary = "----=_Part_" . bin2hex(random_bytes(12));
            $msgId = "<" . time() . "." . bin2hex(random_bytes(8)) . "@{$host}>";
            $toHeader = implode(', ', $recipients);

            $headers = [];
            $headers[] = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>";
            $headers[] = "Reply-To: <{$fromEmail}>";
            $headers[] = "To: {$toHeader}";
            $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
            $headers[] = "Date: " . date('r');
            $headers[] = "Message-ID: {$msgId}";
            $headers[] = "X-Mailer: TrooGood HRMS Dispatcher v2.5";

            if (!empty($attachments)) {
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";
                
                $body = implode("\r\n", $headers) . "\r\n\r\n";
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Type: text/html; charset=UTF-8\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $body .= chunk_split(base64_encode($htmlContent)) . "\r\n";

                foreach ($attachments as $att) {
                    $attName = $att['name'] ?? 'attachment.dat';
                    $attType = $att['type'] ?? 'application/octet-stream';
                    $attContent = $att['content'] ?? '';
                    $body .= "--{$boundary}\r\n";
                    $body .= "Content-Type: {$attType}; name=\"{$attName}\"\r\n";
                    $body .= "Content-Disposition: attachment; filename=\"{$attName}\"\r\n";
                    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
                    $body .= chunk_split(base64_encode($attContent)) . "\r\n";
                }
                $body .= "--{$boundary}--\r\n";
            } else {
                $headers[] = "MIME-Version: 1.0";
                $headers[] = "Content-Type: text/html; charset=UTF-8";
                $headers[] = "Content-Transfer-Encoding: base64";

                $body = implode("\r\n", $headers) . "\r\n\r\n";
                $body .= chunk_split(base64_encode($htmlContent)) . "\r\n";
            }

            // Terminate DATA with <CRLF>.<CRLF>
            fputs($fp, $body . ".\r\n");
            $sendResp = self::readResponse($fp);
            if (substr($sendResp, 0, 3) !== '250') {
                throw new Exception("Message content rejected by SMTP: " . trim($sendResp));
            }

            // 9. QUIT
            fputs($fp, "QUIT\r\n");
            self::readResponse($fp);
            fclose($fp);

            logAudit('SYSTEM', $fromEmail, 'EMAIL_DISPATCHED', 'email', $recipients[0], [
                'subject' => $subject,
                'recipients' => $recipients,
                'smtp_server' => "{$host}:{$port}"
            ]);

            return [
                'success' => true,
                'message' => 'Email dispatched successfully via ' . $host
            ];

        } catch (Exception $e) {
            @fputs($fp, "QUIT\r\n");
            @fclose($fp);
            $err = $e->getMessage();
            error_log("SMTP Error: " . $err);
            logAudit('SYSTEM', $fromEmail, 'MAIL_ERROR', 'email', $recipients[0], $err);
            return ['success' => false, 'error' => $err];
        }
    }

    /**
     * Standard Branded HTML Template Wrapper
     */
    public static function wrapHtmlTemplate($title, $contentHtml, $actionButton = null) {
        $btnHtml = '';
        if ($actionButton && !empty($actionButton['url'])) {
            $btnText = htmlspecialchars($actionButton['text'] ?? 'Open Link');
            $btnUrl = htmlspecialchars($actionButton['url']);
            $btnHtml = "
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$btnUrl}' style='background: linear-gradient(135deg, #0F766E 0%, #0D9488 100%); color: #ffffff; padding: 13px 28px; text-decoration: none; font-weight: 700; font-size: 14px; border-radius: 8px; display: inline-block; box-shadow: 0 4px 12px rgba(13,148,136,0.3);'>
                        {$btnText} &rarr;
                    </a>
                </div>
            ";
        }

        $year = date('Y');

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$title}</title>
</head>
<body style='margin:0; padding:0; background-color:#F8FAFC; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color:#0F172A; line-height:1.6;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background-color:#F8FAFC; padding: 30px 15px;'>
        <tr>
            <td align='center'>
                <table width='600' cellpadding='0' cellspacing='0' style='background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 6px 20px rgba(0,0,0,0.06); border:1px solid #E2E8F0; max-width:600px; width:100%;'>
                    <!-- Header -->
                    <tr>
                        <td style='background: #0F172A; padding: 24px 30px; text-align: center; border-bottom: 3px solid #0D9488;'>
                            <div style='font-size: 22px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em;'>TrooGood HRMS</div>
                            <div style='font-size: 12px; color: #94A3B8; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px;'>Official Workforce & Gate Attendance Network</div>
                        </td>
                    </tr>
                    <!-- Main Body -->
                    <tr>
                        <td style='padding: 35px 35px 25px 35px;'>
                            <h2 style='font-size: 19px; font-weight: 800; color: #0F172A; margin: 0 0 16px 0; border-bottom: 1.5px solid #F1F5F9; padding-bottom: 10px;'>{$title}</h2>
                            <div style='font-size: 14px; color: #334155; line-height: 1.65;'>
                                {$contentHtml}
                            </div>
                            {$btnHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style='background-color: #F8FAFC; padding: 20px 30px; text-align: center; font-size: 12px; color: #64748B; border-top: 1px solid #E2E8F0;'>
                            <p style='margin: 0 0 6px 0;'><strong>TrooGood (Mformillet Foods Pvt Ltd)</strong> &bull; Central HR & Operations Department</p>
                            <p style='margin: 0; color: #94A3B8;'>This is an automated system notification dispatched via TrooGood Outbound Mail Server (troogood.in). Please do not reply directly to this email.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
";
    }

    // =========================================================================
    // SPECIFIC SYSTEM MAIL TRIGGERS
    // =========================================================================

    /**
     * Trigger 1: Candidate Onboarding Invitation Link or App Access Registration
     */
    public static function sendCandidateInvite($candidateEmail, $candidateName, $role, $unitId, $inviteUrl, $appPassword = null, $source = 'onboarding') {
        if (empty($candidateEmail)) return false;

        $isPeople = ($source === 'people');
        $subject = $isPeople
            ? "Your TrooGood Mobile App Access & Application Link ({$role} - {$unitId})"
            : "Welcome to TrooGood! Complete Your Joining Details ({$role} - {$unitId})";

        if ($isPeople) {
            $content = "
                <p>Dear <strong>{$candidateName}</strong>,</p>
                <p>Welcome to <strong>TrooGood ({$unitId})</strong>! Your employee application access and registration have been initialized.</p>
                <div style='background:#F8FAFC; border:1px solid #E2E8F0; padding:16px; border-radius:10px; margin:18px 0;'>
                    <div style='font-weight:800; font-size:13px; color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:6px; margin-bottom:10px;'>YOUR APPLICATION & APP ACCESS CREDENTIALS</div>
                    <div style='font-size:13px; color:#334155; margin-bottom:4px;'><strong>Position / Role:</strong> {$role} &bull; {$unitId}</div>
                    <div style='font-size:13px; color:#334155; margin-bottom:4px;'><strong>Username / Email:</strong> <code>{$candidateEmail}</code></div>
                    <div style='font-size:13px; color:#334155;'><strong>App Password / Access PIN:</strong> <code>" . htmlspecialchars($appPassword ?: 'TrooGood@2026') . "</code></div>
                </div>
                <p>Please click the button below to open your application and complete your employee registration:</p>
            ";
            $buttonText = 'Open Application Link';
        } else {
            $content = "
                <p>Dear <strong>{$candidateName}</strong>,</p>
                <p>Congratulations on your upcoming role as <strong>{$role}</strong> at <strong>TrooGood Facility {$unitId}</strong>!</p>
                <p>To enable statutory compliance, factory gate biometric pass, and payroll enrollment (EPFO/ESIC), please complete your 6-step online joining form using the link below:</p>
                <div style='background:#F1F5F9; border-left:4px solid #0D9488; padding:14px 18px; margin:18px 0; border-radius:6px;'>
                    <div style='font-size:12px; color:#64748B; font-weight:700;'>YOUR JOINING DETAILS</div>
                    <div style='font-size:14px; font-weight:700; color:#0F172A; margin-top:4px;'>Position: {$role} &bull; Factory: {$unitId}</div>
                    <div style='font-size:12px; color:#475569; margin-top:2px;'>Documents Needed: Aadhaar Card, Bank Proof, EPF details (if any).</div>
                </div>
                <p>Please click the secure button below to begin your onboarding:</p>
            ";
            $buttonText = 'Complete Joining Form Online';
        }

        $fullUrl = (strpos($inviteUrl, 'http') === 0) ? $inviteUrl : ((loadEnv()['APP_URL'] ?? 'http://127.0.0.1:8000') . $inviteUrl);
        $html = self::wrapHtmlTemplate($isPeople ? "Candidate Application & App Access" : "Candidate Onboarding Invitation", $content, [
            'text' => $buttonText,
            'url'  => $fullUrl
        ]);

        return self::send($candidateEmail, $subject, $html);
    }

    /**
     * Trigger 2: Candidate Submission Received (Confirmation to candidate & HR)
     */
    public static function sendCandidateSubmissionConfirmation($candidateEmail, $candidateName, $position, $unitId) {
        if (empty($candidateEmail)) return false;

        $subject = "Onboarding Application Received - TrooGood HRMS";
        $content = "
            <p>Dear <strong>{$candidateName}</strong>,</p>
            <p>Thank you for submitting your statutory onboarding profile for <strong>{$position}</strong> at <strong>{$unitId}</strong>.</p>
            <p>Your details and uploaded documents have been successfully queued in the TrooGood HR Operations verification pipeline.</p>
            <div style='background:#ECFDF5; border:1px solid #A7F3D0; padding:14px; border-radius:8px; margin:16px 0;'>
                <div style='font-weight:700; color:#065F46; font-size:13px;'>✓ STATUS: SUBMITTED - PENDING HR APPROVAL</div>
                <div style='color:#047857; font-size:12px; margin-top:4px;'>Our HR team will verify your Aadhaar, bank records, and statutory undertakings. You will receive an offer letter and gate access confirmation shortly.</div>
            </div>
            <p>If you have any questions, please contact the factory HR Operations supervisor.</p>
        ";

        $html = self::wrapHtmlTemplate("Application Received Successfully", $content);
        return self::send($candidateEmail, $subject, $html);
    }

    /**
     * Trigger 3: Individual Attendance Mobile App Access Credentials
     */
    public static function sendAppCredentials($employeeEmail, $employeeName, $workerCode, $tempPassword, $apkUrl) {
        if (empty($employeeEmail)) return false;

        $subject = "Your TrooGood Mobile Kiosk & Attendance App Credentials";
        $content = "
            <p>Dear <strong>{$employeeName}</strong>,</p>
            <p>Your individual mobile attendance account has been activated on the TrooGood HRMS network.</p>
            <div style='background:#F8FAFC; border:1px solid #E2E8F0; padding:16px; border-radius:10px; margin:18px 0;'>
                <div style='font-weight:800; font-size:13px; color:#0F172A; border-bottom:1px solid #E2E8F0; padding-bottom:6px; margin-bottom:10px;'>APP LOGIN CREDENTIALS</div>
                <div style='font-size:13px; color:#334155; margin-bottom:4px;'><strong>Worker Code:</strong> <code>{$workerCode}</code></div>
                <div style='font-size:13px; color:#334155; margin-bottom:4px;'><strong>Username / Email:</strong> <code>{$employeeEmail}</code></div>
                <div style='font-size:13px; color:#334155;'><strong>Password:</strong> <code>{$tempPassword}</code></div>
            </div>
            <p>Please download and install the official TrooGood Mobile Attendance APK on your Android device to start punching gate attendance:</p>
        ";

        $html = self::wrapHtmlTemplate("Mobile Attendance App Access", $content, [
            'text' => 'Download TrooGood Mobile App (APK)',
            'url'  => $apkUrl
        ]);

        return self::send($employeeEmail, $subject, $html);
    }

    /**
     * Trigger 4: Critical Broadcast Announcements to Supervisors & Managers
     */
    public static function sendBroadcastAlert($recipients, $title, $message, $priority, $targetUnit) {
        if (empty($recipients)) return false;

        $subject = "[TrooGood Alert] {$title} (" . strtoupper($priority) . ")";
        $color = ($priority === 'critical') ? '#EF4444' : (($priority === 'high') ? '#F59E0B' : '#2563EB');
        
        $content = "
            <div style='display:inline-block; background:{$color}; color:white; font-size:11px; font-weight:800; padding:3px 10px; border-radius:4px; text-transform:uppercase; margin-bottom:12px;'>
                {$priority} Announcement &bull; {$targetUnit}
            </div>
            <div style='font-size:15px; color:#0F172A; font-weight:700; margin-bottom:10px;'>{$title}</div>
            <div style='background:#F8FAFC; border:1px solid #E2E8F0; padding:16px; border-radius:8px; font-size:13px; color:#334155;'>
                " . nl2br(htmlspecialchars($message)) . "
            </div>
            <p style='font-size:12px; color:#64748B; margin-top:16px;'>This announcement has been broadcasted to all mobile gate kiosks and web management consoles across {$targetUnit}.</p>
        ";

        $html = self::wrapHtmlTemplate("Broadcast Alert Notification", $content);
        return self::send($recipients, $subject, $html);
    }

    /**
     * Trigger 5: Daily Shift Attendance & Operations Summary Report
     */
    public static function sendDailyOperationsReport($recipients, $reportDate, $stats, $unitBreakdown = []) {
        if (empty($recipients)) return false;

        $subject = "TrooGood HRMS: Daily Operations & Attendance Report ({$reportDate})";

        $totalPresent = $stats['total_present'] ?? 0;
        $totalWorkers = $stats['total_workers'] ?? 0;
        $attendanceRate = $totalWorkers > 0 ? round(($totalPresent / $totalWorkers) * 100, 1) : 0;
        $totalUnits = count($unitBreakdown);

        $tableRows = '';
        foreach ($unitBreakdown as $u) {
            $tableRows .= "
                <tr>
                    <td style='padding:8px 12px; border-bottom:1px solid #E2E8F0; font-weight:700;'>{$u['unit_id']}</td>
                    <td style='padding:8px 12px; border-bottom:1px solid #E2E8F0;'>{$u['unit_name']}</td>
                    <td style='padding:8px 12px; border-bottom:1px solid #E2E8F0; text-align:center;'>{$u['enrolled_count']}</td>
                    <td style='padding:8px 12px; border-bottom:1px solid #E2E8F0; text-align:center; font-weight:700; color:#059669;'>{$u['present_count']}</td>
                    <td style='padding:8px 12px; border-bottom:1px solid #E2E8F0; text-align:center;'>{$u['rate']}%</td>
                </tr>
            ";
        }

        $content = "
            <p>Here is the daily shift operations and gate attendance summary for <strong>{$reportDate}</strong> across all active manufacturing units.</p>
            <table width='100%' cellpadding='0' cellspacing='0' style='margin:18px 0;'>
                <tr>
                    <td width='32%' style='background:#F1F5F9; padding:14px; border-radius:8px; text-align:center;'>
                        <div style='font-size:11px; color:#64748B; font-weight:700;'>TOTAL WORKFORCE</div>
                        <div style='font-size:22px; font-weight:800; color:#0F172A; margin-top:4px;'>{$totalWorkers}</div>
                    </td>
                    <td width='2%'></td>
                    <td width='32%' style='background:#ECFDF5; padding:14px; border-radius:8px; text-align:center; border:1px solid #A7F3D0;'>
                        <div style='font-size:11px; color:#059669; font-weight:700;'>TOTAL PRESENT</div>
                        <div style='font-size:22px; font-weight:800; color:#047857; margin-top:4px;'>{$totalPresent}</div>
                    </td>
                    <td width='2%'></td>
                    <td width='32%' style='background:#EFF6FF; padding:14px; border-radius:8px; text-align:center; border:1px solid #BFDBFE;'>
                        <div style='font-size:11px; color:#2563EB; font-weight:700;'>ATTENDANCE RATE</div>
                        <div style='font-size:22px; font-weight:800; color:#1D4ED8; margin-top:4px;'>{$attendanceRate}%</div>
                    </td>
                </tr>
            </table>

            <h3 style='font-size:14px; font-weight:800; margin:20px 0 10px 0;'>Factory Unit Breakdown</h3>
            <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse; font-size:12px;'>
                <thead>
                    <tr style='background:#F8FAFC;'>
                        <th style='padding:8px 12px; text-align:left; border-bottom:2px solid #E2E8F0;'>UNIT</th>
                        <th style='padding:8px 12px; text-align:left; border-bottom:2px solid #E2E8F0;'>FACTORY</th>
                        <th style='padding:8px 12px; text-align:center; border-bottom:2px solid #E2E8F0;'>ENROLLED</th>
                        <th style='padding:8px 12px; text-align:center; border-bottom:2px solid #E2E8F0;'>PRESENT</th>
                        <th style='padding:8px 12px; text-align:center; border-bottom:2px solid #E2E8F0;'>RATE</th>
                    </tr>
                </thead>
                <tbody>
                    {$tableRows}
                </tbody>
            </table>
        ";

        $html = self::wrapHtmlTemplate("Daily Operations & Attendance Report", $content);
        return self::send($recipients, $subject, $html);
    }

    /**
     * Trigger 6: Candidate Offer Letter Release
     */
    public static function sendOfferLetterRelease($candidateEmail, $candidateName, $role, $unitId, $salary, $offerLetterUrl, $notes = '') {
        if (empty($candidateEmail)) return false;

        $subject = "Congratulations! TrooGood Official Offer of Employment ({$role})";
        $notesHtml = !empty($notes) ? "<p style='background:#F1F5F9; padding:12px; border-radius:6px; font-size:13px; color:#475569;'><strong>Note from HR:</strong> " . htmlspecialchars($notes) . "</p>" : "";

        $content = "
            <p>Dear <strong>{$candidateName}</strong>,</p>
            <p>We are delighted to extend an official Offer of Employment for the position of <strong>{$role}</strong> at <strong>TrooGood Facility {$unitId}</strong>!</p>
            <div style='background:#F0FDF4; border:1px solid #BBF7D0; padding:18px; border-radius:10px; margin:18px 0;'>
                <div style='font-size:12px; font-weight:700; color:#15803D; text-transform:uppercase; letter-spacing:0.5px;'>OFFER DETAILS</div>
                <div style='font-size:16px; font-weight:800; color:#166534; margin-top:6px;'>Monthly Gross Compensation: ₹" . number_format((float)str_replace(['₹', ',', ' '], '', $salary), 2) . " / month</div>
                <div style='font-size:13px; color:#15803D; margin-top:4px;'>Role: {$role} &bull; Unit: {$unitId}</div>
            </div>
            {$notesHtml}
            <p>Please review your official offer letter by clicking the link below and follow the instructions to confirm your acceptance.</p>
        ";

        $fullUrl = (strpos($offerLetterUrl, 'http') === 0) ? $offerLetterUrl : ((loadEnv()['APP_URL'] ?? 'http://127.0.0.1:8000') . $offerLetterUrl);
        $action = !empty($offerLetterUrl) ? [
            'text' => 'View & Download Offer Letter',
            'url'  => $fullUrl
        ] : null;

        $html = self::wrapHtmlTemplate("Offer of Employment", $content, $action);
        return self::send($candidateEmail, $subject, $html);
    }
}
