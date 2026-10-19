<?php
/**
 * WebPortalController - Powers Web Wireframes W01 through W07
 * Returns dynamic JSON metrics and data tables for the management panel
 */

require_once __DIR__ . '/../../config/db.php';

class WebPortalController {

    // W01: Dashboard Data
    public static function getDashboardData() {
        $pdo = getDbConnection();
        $unit = $_GET['unit'] ?? 'all';
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');

        // Unit filter clause
        $unitClause = "";
        $unitParam = [];
        if ($unit !== 'all' && !empty($unit)) {
            $unitClause = " AND unit_id = " . $pdo->quote($unit);
        }

        // 1. Metric Cards (Pure live data from DB)
        $totalWorkers = (int)$pdo->query("SELECT COUNT(*) FROM workers WHERE approval_status = 'approved' AND deleted_at IS NULL $unitClause")->fetchColumn();
        
        $presentQuery = "SELECT COUNT(DISTINCT a.worker_id) 
                         FROM attendance_punches a 
                         JOIN workers w ON a.worker_id = w.id 
                         WHERE DATE(a.punch_time) = '$today' AND a.punch_type = 'IN' AND w.deleted_at IS NULL $unitClause";
        $presentToday = (int)$pdo->query($presentQuery)->fetchColumn();
        
        $onLeave = 0; // Live approved leaves
        try {
            $onLeave = (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'leave' AND status = 'approved' AND '$today' BETWEEN dates_summary AND dates_summary $unitClause")->fetchColumn();
        } catch (Exception $e) { $onLeave = 0; }

        $absent = max(0, $totalWorkers - $presentToday - $onLeave);
        $otHours = 0;
        
        // Sum total salary of approved workers
        $salarySum = (float)$pdo->query("SELECT COALESCE(SUM(monthly_salary), 0) FROM workers WHERE approval_status = 'approved' AND deleted_at IS NULL $unitClause")->fetchColumn();
        $payrollCostFormatted = $salarySum > 0 ? ("₹ " . number_format($salarySum / 100000, 2) . " L") : "₹ 0";

        // 2. Unit-wise attendance today (Dynamic from DB)
        $units = $pdo->query("SELECT id, unit_name, unit_code FROM units WHERE is_active = 1 ORDER BY id ASC")->fetchAll();
        $unitWise = [];
        foreach ($units as $u) {
            $uCode = $u['id'];
            $hc = (int)$pdo->query("SELECT COUNT(*) FROM workers WHERE unit_id = " . $pdo->quote($uCode) . " AND approval_status = 'approved' AND deleted_at IS NULL")->fetchColumn();
            $pres = (int)$pdo->query("SELECT COUNT(DISTINCT a.worker_id) FROM attendance_punches a JOIN workers w ON a.worker_id = w.id WHERE w.unit_id = " . $pdo->quote($uCode) . " AND DATE(a.punch_time) = '$today' AND a.punch_type = 'IN'")->fetchColumn();
            $lv = 0;
            $ab = max(0, $hc - $pres - $lv);
            $pct = $hc > 0 ? round(($pres / $hc) * 100) : 0;

            $unitWise[] = [
                'unit' => $uCode,
                'name' => $u['unit_name'],
                'headcount' => $hc,
                'present' => $pres,
                'absent' => $ab,
                'leave' => $lv,
                'present_pct' => $pct . '%'
            ];
        }

        // 3. Pending approvals counts (W01 & W05)
        $leaveCount = (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'leave' AND status = 'pending'")->fetchColumn();
        $advCount = (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'advance' AND status = 'pending'")->fetchColumn();
        $corrCount = (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'correction' AND status = 'pending'")->fetchColumn();
        $kioskCount = (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'kiosk_worker' AND status = 'pending'")->fetchColumn();
        $exitCount = (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'exit_noc' AND status = 'pending'")->fetchColumn();
        $docVerifyCount = (int)$pdo->query("SELECT COUNT(*) FROM candidate_documents WHERE verification_status = 'pending'")->fetchColumn();
        $totalPending = $leaveCount + $advCount + $corrCount + $kioskCount + $exitCount;

        // 4. Live Punches Feed
        $livePunches = $pdo->query("SELECT a.worker_name, a.worker_code, a.shift_name, a.punch_type, a.device_id, 
                                           TIME_FORMAT(a.punch_time, '%h:%i %p') as punch_time_fmt 
                                    FROM attendance_punches a
                                    ORDER BY a.punch_time DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

        // 5. Onboarding Pipeline Stats
        $onboardingStats = [
            'invited' => (int)$pdo->query("SELECT COUNT(*) FROM candidates WHERE stage = 'invited' AND deleted_at IS NULL")->fetchColumn(),
            'details_submitted' => (int)$pdo->query("SELECT COUNT(*) FROM candidates WHERE stage = 'details_submitted' AND deleted_at IS NULL")->fetchColumn(),
            'docs_verification' => (int)$pdo->query("SELECT COUNT(*) FROM candidates WHERE stage = 'docs_verification' AND deleted_at IS NULL")->fetchColumn(),
            'offer_released' => (int)$pdo->query("SELECT COUNT(*) FROM candidates WHERE stage = 'offer_released' AND deleted_at IS NULL")->fetchColumn(),
            'offer_accepted' => (int)$pdo->query("SELECT COUNT(*) FROM candidates WHERE stage = 'offer_accepted' AND deleted_at IS NULL")->fetchColumn(),
            'joined' => (int)$pdo->query("SELECT COUNT(*) FROM candidates WHERE stage = 'joined' AND deleted_at IS NULL")->fetchColumn()
        ];

        jsonSuccess("Dashboard data loaded", [
            'metrics' => [
                'headcount' => $totalWorkers,
                'present_today' => $presentToday,
                'present_pct' => ($totalWorkers > 0 ? round(($presentToday / $totalWorkers) * 100) : 0) . '%',
                'absent' => $absent,
                'on_leave' => $onLeave,
                'ot_hours' => $otHours,
                'payroll_cost' => $payrollCostFormatted
            ],
            'unit_wise' => $unitWise,
            'pending_approvals' => [
                'leave_requests' => $leaveCount,
                'salary_advances' => $advCount,
                'attendance_corrections' => $corrCount,
                'kiosk_worker_registrations' => $kioskCount,
                'candidate_docs_to_verify' => $docVerifyCount,
                'exit_clearances_open' => $exitCount,
                'total_pending' => $totalPending
            ],
            'live_punches' => $livePunches,
            'onboarding_pipeline' => $onboardingStats,
            'payroll_status' => [
                'month' => date('F Y'),
                'attendance_lock' => 'Due ' . date('t M', strtotime($today)),
                'payroll_run' => 'Pending',
                'payslips' => 'Scheduled'
            ]
        ]);
    }

    // W02: Attendance Register Matrix Data (Dynamic Month & Real Kiosk Punches)
    public static function getAttendanceMatrix() {
        $pdo = getDbConnection();
        $month = $_GET['month'] ?? date('Y-m'); // e.g. 2026-10
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $shift = $_GET['shift'] ?? 'all';
        $dept = $_GET['department'] ?? 'all';
        $unit = $_GET['unit'] ?? 'all';

        // Calculate days in the selected month
        $daysInMonth = (int)date('t', strtotime("$month-01"));
        $todayDate = date('Y-m-d');
        $currentMonthStr = date('Y-m');

        // Dynamic Day Headers (1 to 31 with correct Day of Week)
        $dayHeaders = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = sprintf('%s-%02d', $month, $d);
            $dayHeaders[] = [
                'day' => $d,
                'date' => $dateStr,
                'dow' => substr(date('D', strtotime($dateStr)), 0, 1) // M, T, W, T, F, S, S
            ];
        }

        // Build worker query
        $sql = "SELECT id, worker_code, full_name, unit_id, department, shift, mobile_number, created_at 
                FROM workers 
                WHERE approval_status = 'approved' AND deleted_at IS NULL";
        $params = [];

        if ($unit !== 'all' && !empty($unit)) {
            $sql .= " AND unit_id = ?";
            $params[] = $unit;
        }
        if ($dept !== 'all' && !empty($dept)) {
            $sql .= " AND department = ?";
            $params[] = $dept;
        }
        if ($shift !== 'all' && !empty($shift)) {
            $sStmt = $pdo->prepare("SELECT shift_name FROM shifts WHERE shift_code = ? LIMIT 1");
            $sStmt->execute([$shift]);
            $resolvedName = $sStmt->fetchColumn();

            if ($resolvedName) {
                $sql .= " AND (shift LIKE ? OR shift LIKE ? OR shift = ?)";
                $params[] = "%$shift%";
                $params[] = "%$resolvedName%";
                $params[] = $shift;
            } else {
                $sql .= " AND (shift LIKE ? OR shift = ?)";
                $params[] = "%$shift%";
                $params[] = $shift;
            }
        }
        $sql .= " ORDER BY id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch declared holidays for the selected month from holiday_calendar
        $hStmt = $pdo->prepare("SELECT holiday_date, holiday_name, holiday_type, unit_id FROM holiday_calendar WHERE holiday_date BETWEEN ? AND ? AND is_active = 1");
        $hStmt->execute(["$month-01", "$month-$daysInMonth"]);
        $monthHolidays = [];
        foreach ($hStmt->fetchAll(PDO::FETCH_ASSOC) as $mh) {
            $monthHolidays[$mh['holiday_date']] = $mh;
        }

        // Fetch all punches for the selected month (including punch unit_id and GPS coordinates)
        $startDate = "$month-01 00:00:00";
        $endDate = "$month-$daysInMonth 23:59:59";
        $punchStmt = $pdo->prepare("SELECT worker_id, worker_code, punch_type, shift_code, shift_name, 
                                           punch_time, DATE(punch_time) as punch_date, device_id, unit_id,
                                           latitude, longitude, location_accuracy, location_address 
                                    FROM attendance_punches 
                                    WHERE punch_time BETWEEN ? AND ? AND deleted_at IS NULL
                                    ORDER BY punch_time ASC");
        $punchStmt->execute([$startDate, $endDate]);
        $allPunches = $punchStmt->fetchAll(PDO::FETCH_ASSOC);

        // Index punches by [worker_id][punch_date]
        $punchesByWorkerDate = [];
        foreach ($allPunches as $p) {
            $wId = (int)$p['worker_id'];
            $pDate = $p['punch_date'];
            if (!isset($punchesByWorkerDate[$wId])) {
                $punchesByWorkerDate[$wId] = [];
            }
            if (!isset($punchesByWorkerDate[$wId][$pDate])) {
                $punchesByWorkerDate[$wId][$pDate] = [];
            }
            $punchesByWorkerDate[$wId][$pDate][] = $p;
        }

        // Fetch all supervisor overrides for the selected month
        $ovStmt = $pdo->prepare("SELECT worker_id, date, status, unit_id, reason, overridden_by 
                                 FROM attendance_overrides 
                                 WHERE date BETWEEN ? AND ?");
        $ovStmt->execute(["$month-01", "$month-$daysInMonth"]);
        $allOverrides = $ovStmt->fetchAll(PDO::FETCH_ASSOC);
        $overridesByWorkerDate = [];
        foreach ($allOverrides as $ov) {
            $overridesByWorkerDate[(int)$ov['worker_id']][$ov['date']] = $ov;
        }

        $matrixRows = [];
        foreach ($workers as $w) {
            $wId = (int)$w['id'];
            $wCode = $w['worker_code'] ?: (string)$wId;
            $workerCreatedDate = !empty($w['created_at']) ? substr($w['created_at'], 0, 10) : '2000-01-01';

            $daysArray = [];
            $daysDetails = [];
            $pDays = 0.0;
            $sunDays = 0.0;
            $paidDays = 0.0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr = sprintf('%s-%02d', $month, $d);
                $isSunday = (date('N', strtotime($dateStr)) == 7);
                $isHoliday = isset($monthHolidays[$dateStr]) && ($monthHolidays[$dateStr]['unit_id'] === 'ALL' || $monthHolidays[$dateStr]['unit_id'] === ($w['unit_id'] ?? 'TGK-1'));
                $holidayName = $isHoliday ? $monthHolidays[$dateStr]['holiday_name'] : null;
                $isFuture = ($dateStr > $todayDate);
                $isBeforeJoin = ($dateStr < $workerCreatedDate);

                $dayPunches = $punchesByWorkerDate[$wId][$dateStr] ?? [];
                $override = $overridesByWorkerDate[$wId][$dateStr] ?? null;
                
                $status = '-';
                $punchInTime = null;
                $punchOutTime = null;
                $durationHours = 0;
                $punchUnit = null;
                $punchLat = null;
                $punchLng = null;
                $punchAddress = null;

                if (!empty($dayPunches)) {
                    // Worker punched on this day
                    foreach ($dayPunches as $dp) {
                        if ($dp['punch_type'] === 'IN' && !$punchInTime) {
                            $punchInTime = substr($dp['punch_time'], 11, 5);
                            $punchLat = $dp['latitude'] ?? null;
                            $punchLng = $dp['longitude'] ?? null;
                            $punchAddress = $dp['location_address'] ?? null;
                        } elseif ($dp['punch_type'] === 'OUT') {
                            $punchOutTime = substr($dp['punch_time'], 11, 5);
                            if (!$punchLat) $punchLat = $dp['latitude'] ?? null;
                            if (!$punchLng) $punchLng = $dp['longitude'] ?? null;
                        }
                        if (!empty($dp['unit_id']) && !$punchUnit) {
                            $punchUnit = $dp['unit_id'];
                        }
                    }

                    if ($punchInTime && $punchOutTime) {
                        $inTs = strtotime("$dateStr $punchInTime");
                        $outTs = strtotime("$dateStr $punchOutTime");
                        if ($outTs >= $inTs) {
                            $durationHours = round(($outTs - $inTs) / 3600, 1);
                        }
                    }

                    // Worker who got attendance (even on Sunday or Holiday) gets P (Full Day)
                    // Punches on Sunday or Holiday also qualify for +1 Comp-Off / Leave Credit in the Leave Calculator!
                    $status = 'P';
                } else {
                    if ($isHoliday) {
                        // Official declared holiday from holiday_calendar
                        $status = 'H';
                    } elseif ($isSunday) {
                        // If no punch on Sunday, worker gets W (Weekly Off)
                        $status = 'W';
                    } elseif ($isFuture || $isBeforeJoin) {
                        $status = '-';
                    } else {
                        $status = 'A'; // Absent
                    }
                }

                // If supervisor override exists, override the calculated status
                $isOverridden = false;
                $overrideReason = null;
                $effectiveUnit = $punchUnit ?: ($w['unit_id'] ?? 'TGK-1');

                if ($override) {
                    $isOverridden = true;
                    $overrideReason = $override['reason'];
                    if (!empty($override['unit_id'])) {
                        $effectiveUnit = $override['unit_id'];
                    }
                    $status = strtoupper($override['status']);
                }

                // Tally Present, Sunday, and Total Paid days
                if ($status === 'P') {
                    $pDays += 1.0;
                } elseif ($status === 'HD') {
                    $pDays += 0.5;
                } elseif ($status === 'W' || $status === 'H') {
                    $sunDays += 1.0;
                }

                $daysArray[] = $status;
                $daysDetails[] = [
                    'day' => $d,
                    'date' => $dateStr,
                    'status' => $status,
                    'unit_id' => ($status === 'P' || $status === 'HD') ? $effectiveUnit : null,
                    'punch_in' => $punchInTime,
                    'punch_out' => $punchOutTime,
                    'duration' => $durationHours ? ($durationHours . 'h') : null,
                    'punch_count' => count($dayPunches),
                    'is_overridden' => $isOverridden,
                    'override_reason' => $overrideReason,
                    'is_sunday' => $isSunday,
                    'is_holiday' => $isHoliday,
                    'holiday_name' => $holidayName,
                    'latitude' => $punchLat,
                    'longitude' => $punchLng,
                    'location_address' => $punchAddress
                ];
            }

            $paidDays = $pDays + $sunDays;
            $cleanShift = preg_replace('/\s*\([^)]*\)/', '', $w['shift'] ?? 'General Shift');
            $cleanShift = trim($cleanShift) ?: 'General Shift';

            $matrixRows[] = [
                'worker_id' => $wId,
                'worker_code' => $wCode,
                'full_name' => $w['full_name'],
                'mobile_number' => $w['mobile_number'] ?? '',
                'unit_id' => $w['unit_id'],
                'department' => $w['department'] ?? 'Factory Operations',
                'shift' => $cleanShift,
                'days' => $daysArray,
                'days_details' => $daysDetails,
                'p_days' => $pDays,
                'sunday_days' => $sunDays,
                'paid_days' => $paidDays
            ];
        }

        // CSV Export if requested
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="attendance_' . $month . '.csv"');
            $out = fopen('php://output', 'w');

            // Header row: ID, Employee Code, Employee Name, Unit, Shift, 1..31, Present, Sundays, Total
            $header = ['ID', 'Employee Code', 'Employee Name', 'Unit', 'Shift'];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $header[] = (string)$d;
            }
            $header[] = 'Present';
            $header[] = 'Sundays';
            $header[] = 'Total';
            fputcsv($out, $header);

            // Data rows
            foreach ($matrixRows as $row) {
                $csvRow = [
                    $row['worker_id'],
                    $row['worker_code'],
                    $row['full_name'],
                    $row['unit_id'],
                    $row['shift']
                ];
                foreach ($row['days'] as $dayStatus) {
                    $csvRow[] = $dayStatus;
                }
                $csvRow[] = $row['p_days'];
                $csvRow[] = $row['sunday_days'];
                $csvRow[] = $row['paid_days'];
                fputcsv($out, $csvRow);
            }
            fclose($out);
            exit;
        }

        jsonSuccess("Attendance matrix loaded", [
            'month' => $month,
            'month_label' => date('F Y', strtotime("$month-01")),
            'days_in_month' => $daysInMonth,
            'days_header' => $dayHeaders,
            'rows' => $matrixRows,
            'total_workers' => count($matrixRows),
            'legend' => [
                'P' => 'Present',
                'A' => 'Absent',
                'W' => 'Weekly off',
                'L' => 'Leave',
                'HD' => 'Half day',
                'H' => 'Holiday',
                '-' => 'Future / Not enrolled'
            ]
        ]);
    }

    // W02: Override Attendance Cell (Persisted to attendance_overrides and attendance_punches)
    public static function overrideAttendanceCell() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $workerId = (int)($input['worker_id'] ?? 0);
        $day = (int)($input['day'] ?? 1);
        $month = trim($input['month'] ?? date('Y-m'));
        $dateStr = trim($input['date'] ?? sprintf('%s-%02d', $month, $day));
        $newStatus = strtoupper(trim($input['status'] ?? 'P'));
        $unitId = trim($input['unit_id'] ?? '');
        $reason = trim($input['reason'] ?? 'Manual supervisor correction');
        $overriddenBy = trim($_SESSION['user']['full_name'] ?? 'HR Supervisor');

        if (!$workerId) {
            jsonError("Worker ID is required", 422);
        }

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        // Fetch worker info to verify and get default unit if not provided
        $wStmt = $pdo->prepare("SELECT worker_code, full_name, unit_id FROM workers WHERE id = ?");
        $wStmt->execute([$workerId]);
        $worker = $wStmt->fetch(PDO::FETCH_ASSOC);
        if (!$worker) {
            jsonError("Worker not found", 404);
        }

        if (empty($unitId)) {
            $unitId = $worker['unit_id'] ?? 'TGK-1';
        }

        // 1. Insert/Update into attendance_overrides table
        $stmt = $pdo->prepare("INSERT INTO attendance_overrides 
            (worker_id, date, status, unit_id, reason, overridden_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                unit_id = VALUES(unit_id), 
                reason = VALUES(reason), 
                overridden_by = VALUES(overridden_by), 
                updated_at = NOW()");
        $stmt->execute([$workerId, $dateStr, $newStatus, $unitId, $reason, $overriddenBy]);

        // 2. If status is P or HD, ensure attendance_punches record exists
        if ($newStatus === 'P' || $newStatus === 'HD') {
            $chkPunch = $pdo->prepare("SELECT COUNT(*) FROM attendance_punches WHERE worker_id = ? AND DATE(punch_time) = ? AND deleted_at IS NULL");
            $chkPunch->execute([$workerId, $dateStr]);
            if ((int)$chkPunch->fetchColumn() === 0) {
                $wCode = $worker['worker_code'] ?: (string)$workerId;
                $wName = $worker['full_name'] ?: "Worker #$wCode";

                $inTime = "$dateStr 09:00:00";
                $outTime = ($newStatus === 'HD') ? "$dateStr 13:00:00" : "$dateStr 17:00:00";

                $pdo->prepare("INSERT INTO attendance_punches 
                    (uuid, worker_id, worker_code, worker_name, punch_type, shift_code, shift_name, punch_time, unit_id, device_id, confidence_score, liveness_passed, is_manual, manual_reason, is_synced, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'IN', 'GEN', 'General Shift', ?, ?, 'PORTAL-OVERRIDE', 1.0000, 1, 1, ?, 1, NOW(), NOW())")
                    ->execute(['OVR-IN-' . bin2hex(random_bytes(8)), $workerId, $wCode, $wName, $inTime, $unitId, $reason]);

                $pdo->prepare("INSERT INTO attendance_punches 
                    (uuid, worker_id, worker_code, worker_name, punch_type, shift_code, shift_name, punch_time, unit_id, device_id, confidence_score, liveness_passed, is_manual, manual_reason, is_synced, created_at, updated_at)
                    VALUES (?, ?, ?, ?, 'OUT', 'GEN', 'General Shift', ?, ?, 'PORTAL-OVERRIDE', 1.0000, 1, 1, ?, 1, NOW(), NOW())")
                    ->execute(['OVR-OUT-' . bin2hex(random_bytes(8)), $workerId, $wCode, $wName, $outTime, $unitId, $reason]);
            }
        } elseif ($newStatus === 'A') {
            // Soft delete existing punches if overridden to Absent
            $pdo->prepare("UPDATE attendance_punches SET deleted_at = NOW() WHERE worker_id = ? AND DATE(punch_time) = ? AND deleted_at IS NULL")
                ->execute([$workerId, $dateStr]);
        }

        logAudit('WEB_PORTAL', $overriddenBy, 'OVERRIDE_ATTENDANCE', 'attendance_overrides', "$workerId:$dateStr", [
            'worker_id' => $workerId,
            'date' => $dateStr,
            'new_status' => $newStatus,
            'unit_id' => $unitId,
            'reason' => $reason
        ]);

        jsonSuccess("Attendance override saved successfully for $dateStr", [
            'worker_id' => $workerId,
            'date' => $dateStr,
            'status' => $newStatus,
            'unit_id' => $unitId
        ]);
    }

    // W02b: Bulk Attendance CSV Import (Matching Export CSV Format)
    public static function importAttendanceCsv() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $month = trim($_POST['month'] ?? date('Y-m'));
        $overriddenBy = trim($_SESSION['user']['full_name'] ?? 'HR Admin (CSV Import)');

        $csvContent = '';
        if (!empty($_FILES['file']['tmp_name'])) {
            $csvContent = file_get_contents($_FILES['file']['tmp_name']);
        } else {
            $raw = file_get_contents('php://input');
            $input = json_decode($raw, true);
            if (!empty($input['csv_data'])) {
                $csvContent = $input['csv_data'];
                if (!empty($input['month'])) $month = trim($input['month']);
            } elseif (!empty($raw) && strpos($raw, ',') !== false) {
                $csvContent = $raw;
            }
        }

        if (empty($csvContent)) {
            jsonError("No CSV file or data provided", 400);
        }

        $lines = explode("\n", str_replace("\r", "", $csvContent));
        $headerLine = array_shift($lines);
        if (!$headerLine) {
            jsonError("CSV file is empty", 400);
        }

        $headers = str_getcsv($headerLine);
        $headerMap = [];
        $dayCols = [];

        foreach ($headers as $idx => $h) {
            $cleanH = trim($h);
            $lowerH = strtolower($cleanH);
            if ($lowerH === 'id' || $lowerH === 'worker id' || $lowerH === 'employee id') {
                $headerMap['id'] = $idx;
            } elseif ($lowerH === 'employee code' || $lowerH === 'worker code' || $lowerH === 'code') {
                $headerMap['code'] = $idx;
            } elseif ($lowerH === 'unit') {
                $headerMap['unit'] = $idx;
            } elseif (is_numeric($cleanH) && (int)$cleanH >= 1 && (int)$cleanH <= 31) {
                $dayCols[(int)$cleanH] = $idx;
            } elseif (preg_match('/^day\s*(\d+)$/i', $cleanH, $m) && (int)$m[1] >= 1 && (int)$m[1] <= 31) {
                $dayCols[(int)$m[1]] = $idx;
            }
        }

        if (!isset($headerMap['id']) && !isset($headerMap['code'])) {
            jsonError("Invalid CSV headers. Must contain 'ID' or 'Employee Code' and day columns (1..31)", 422);
        }
        if (empty($dayCols)) {
            jsonError("No day columns (1..31) found in CSV header", 422);
        }

        // Cache all workers for fast lookup
        $workersRaw = $pdo->query("SELECT id, worker_code, full_name, unit_id FROM workers WHERE deleted_at IS NULL")->fetchAll(PDO::FETCH_ASSOC);
        $workersById = [];
        $workersByCode = [];
        foreach ($workersRaw as $wr) {
            $workersById[(int)$wr['id']] = $wr;
            $workersByCode[strtoupper(trim($wr['worker_code']))] = $wr;
        }

        $stmtOverride = $pdo->prepare("INSERT INTO attendance_overrides 
            (worker_id, date, status, unit_id, reason, overridden_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'Bulk CSV Import', ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                unit_id = VALUES(unit_id), 
                reason = VALUES(reason), 
                overridden_by = VALUES(overridden_by), 
                updated_at = NOW()");

        $updatedDays = 0;
        $processedWorkers = 0;

        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $row = str_getcsv($line);
            if (empty($row)) continue;

            $worker = null;
            if (isset($headerMap['id']) && isset($row[$headerMap['id']]) && is_numeric($row[$headerMap['id']])) {
                $wid = (int)$row[$headerMap['id']];
                $worker = $workersById[$wid] ?? null;
            }
            if (!$worker && isset($headerMap['code']) && !empty($row[$headerMap['code']])) {
                $wcode = strtoupper(trim($row[$headerMap['code']]));
                $worker = $workersByCode[$wcode] ?? null;
            }

            if (!$worker) continue;

            $wId = (int)$worker['id'];
            $wUnit = isset($headerMap['unit']) && !empty($row[$headerMap['unit']]) 
                ? trim($row[$headerMap['unit']]) 
                : ($worker['unit_id'] ?? 'TGK-1');
            $workerChanged = false;

            foreach ($dayCols as $dayNum => $colIdx) {
                if (!isset($row[$colIdx])) continue;
                $val = strtoupper(trim($row[$colIdx]));
                if ($val === '' || $val === '-') continue;

                // Validate allowed status
                if (!in_array($val, ['P', 'HD', 'A', 'W', 'L', 'H'])) continue;

                $dateStr = sprintf('%s-%02d', $month, $dayNum);
                $stmtOverride->execute([$wId, $dateStr, $val, $wUnit, $overriddenBy]);

                // Sync punches
                if ($val === 'P' || $val === 'HD') {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM attendance_punches WHERE worker_id = ? AND DATE(punch_time) = ? AND deleted_at IS NULL");
                    $chk->execute([$wId, $dateStr]);
                    if ((int)$chk->fetchColumn() === 0) {
                        $wCode = $worker['worker_code'] ?: (string)$wId;
                        $wName = $worker['full_name'] ?: "Worker #$wCode";
                        $inTime = "$dateStr 09:00:00";
                        $outTime = ($val === 'HD') ? "$dateStr 13:00:00" : "$dateStr 17:00:00";

                        $pdo->prepare("INSERT INTO attendance_punches 
                            (uuid, worker_id, worker_code, worker_name, punch_type, shift_code, shift_name, punch_time, unit_id, device_id, confidence_score, liveness_passed, is_manual, manual_reason, is_synced, created_at, updated_at)
                            VALUES (?, ?, ?, ?, 'IN', 'GEN', 'General Shift', ?, ?, 'CSV-IMPORT', 1.0000, 1, 1, 'Bulk CSV Import', 1, NOW(), NOW())")
                            ->execute(['CSV-IN-' . bin2hex(random_bytes(8)), $wId, $wCode, $wName, $inTime, $wUnit]);

                        $pdo->prepare("INSERT INTO attendance_punches 
                            (uuid, worker_id, worker_code, worker_name, punch_type, shift_code, shift_name, punch_time, unit_id, device_id, confidence_score, liveness_passed, is_manual, manual_reason, is_synced, created_at, updated_at)
                            VALUES (?, ?, ?, ?, 'OUT', 'GEN', 'General Shift', ?, ?, 'CSV-IMPORT', 1.0000, 1, 1, 'Bulk CSV Import', 1, NOW(), NOW())")
                            ->execute(['CSV-OUT-' . bin2hex(random_bytes(8)), $wId, $wCode, $wName, $outTime, $wUnit]);
                    }
                } elseif ($val === 'A') {
                    $pdo->prepare("UPDATE attendance_punches SET deleted_at = NOW() WHERE worker_id = ? AND DATE(punch_time) = ? AND deleted_at IS NULL")
                        ->execute([$wId, $dateStr]);
                }

                $updatedDays++;
                $workerChanged = true;
            }

            if ($workerChanged) $processedWorkers++;
        }

        logAudit('WEB_PORTAL', $overriddenBy, 'ATTENDANCE_CSV_IMPORT', 'attendance_overrides', $updatedDays, [
            'month' => $month,
            'updated_days' => $updatedDays,
            'processed_workers' => $processedWorkers
        ]);

        jsonSuccess("Attendance imported successfully: updated $updatedDays day records for $processedWorkers employee(s).", [
            'updated_days' => $updatedDays,
            'processed_workers' => $processedWorkers,
            'month' => $month
        ]);
    }

    // W03: Onboarding Pipeline Data
    public static function getOnboardingPipeline() {
        $pdo = getDbConnection();
        $candidates = $pdo->query("SELECT * FROM candidates WHERE deleted_at IS NULL ORDER BY id DESC")->fetchAll();

        $stages = [
            'invited' => [],
            'details_submitted' => [],
            'docs_verification' => [],
            'offer_released' => [],
            'offer_accepted' => [],
            'joined' => []
        ];

        $seenEmails = [];
        foreach ($candidates as $c) {
            $email = strtolower(trim($c['email'] ?? ''));
            // Deduplicate candidates so each person only appears in their latest active stage
            if (!empty($email) && isset($seenEmails[$email])) {
                continue;
            }
            if (!empty($email)) {
                $seenEmails[$email] = true;
            }

            $st = $c['stage'] ?? 'invited';
            if (isset($stages[$st])) {
                $stages[$st][] = $c;
            }
        }

        jsonSuccess("Onboarding pipeline loaded", [
            'stages' => $stages,
            'counts' => array_map(function($arr) { return count($arr); }, $stages)
        ]);
    }

    // W04: Candidate Verification Details
    public static function getCandidateVerification($candidateId) {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$candidateId]);
        $cand = $stmt->fetch();

        if (!$cand) {
            jsonError("Candidate #$candidateId not found.", 404);
        }

        $docStmt = $pdo->prepare("SELECT * FROM candidate_documents WHERE candidate_id = ?");
        $docStmt->execute([(int)$candidateId]);
        $docs = $docStmt->fetchAll() ?: [];

        jsonSuccess("Candidate verification loaded", [
            'candidate' => $cand,
            'documents' => $docs,
            'verified_count' => count(array_filter($docs, function($d) { return $d['verification_status'] === 'verified'; })),
            'total_docs' => count($docs)
        ]);
    }

    // W04: Update Document Status
    public static function updateDocVerification() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $docId = (int)($input['document_id'] ?? 0);
        $status = trim($input['status'] ?? 'verified');
        $remarks = trim($input['remarks'] ?? '');

        $pdo = getDbConnection();
        $pdo->prepare("UPDATE candidate_documents SET verification_status = ?, rejection_reason = ?, verified_at = NOW(), verified_by = 'HR Verifier' WHERE id = ?")
            ->execute([$status, $remarks, $docId]);

        logAudit('WEB_PORTAL', 'HR_VERIFIER', 'VERIFY_CANDIDATE_DOC', 'candidate_documents', $docId, [
            'status' => $status,
            'remarks' => $remarks
        ]);

        jsonSuccess("Document $status successfully");
    }

    // W05: Approvals Inbox
    public static function getApprovalsInbox() {
        $pdo = getDbConnection();
        $type = $_GET['type'] ?? 'all';

        $sql = "SELECT * FROM approvals_inbox WHERE deleted_at IS NULL";
        $params = [];
        if ($type !== 'all' && !empty($type)) {
            $sql .= " AND approval_type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        // Categorized counts
        $counts = [
            'all' => (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE status = 'pending'")->fetchColumn(),
            'leave' => (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'leave' AND status = 'pending'")->fetchColumn(),
            'advance' => (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'advance' AND status = 'pending'")->fetchColumn(),
            'correction' => (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'correction' AND status = 'pending'")->fetchColumn(),
            'kiosk_worker' => (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'kiosk_worker' AND status = 'pending'")->fetchColumn(),
            'exit_noc' => (int)$pdo->query("SELECT COUNT(*) FROM approvals_inbox WHERE approval_type = 'exit_noc' AND status = 'pending'")->fetchColumn()
        ];

        jsonSuccess("Approvals inbox loaded", [
            'counts' => $counts,
            'items' => $items
        ]);
    }

    // W05: Action Approval Item
    public static function actionApprovalItem() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);
        $action = trim($input['action'] ?? 'approved'); // 'approved' or 'rejected'
        $comment = trim($input['comment'] ?? '');

        $pdo = getDbConnection();

        // Support actioning directly by worker_id (e.g. from People Master Profile view)
        if (!$id && !empty($input['worker_id'])) {
            $wid = (int)$input['worker_id'];
            $findSt = $pdo->prepare("SELECT id FROM approvals_inbox WHERE worker_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
            $findSt->execute([$wid]);
            $foundId = (int)$findSt->fetchColumn();
            if ($foundId) {
                $id = $foundId;
            } else {
                // If no pending approvals_inbox item, action directly on workers table
                if ($action === 'approved') {
                    $pdo->prepare("UPDATE workers SET approval_status = 'approved', embedding_version = embedding_version + 1, updated_at = NOW() WHERE id = ?")
                        ->execute([$wid]);
                    $pdo->prepare("UPDATE universal_registrations SET updated_at = NOW() WHERE worker_id = ?")
                        ->execute([$wid]);
                } else if ($action === 'rejected') {
                    $pdo->prepare("UPDATE workers SET approval_status = 'rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$comment, $wid]);
                }
                logAudit('WEB_PORTAL', 'HR_MANAGER', strtoupper($action) . '_WORKER_DIRECT', 'workers', $wid, $comment);
                jsonSuccess("Worker #$wid status updated to $action directly", ['worker_id' => $wid, 'status' => $action]);
            }
        }

        if (!$id) {
            jsonError("Invalid approval item ID or worker ID", 400);
        }

        $stmt = $pdo->prepare("UPDATE approvals_inbox SET status = ?, approver_comment = ?, actioned_by = 'HR Manager', actioned_at = NOW() WHERE id = ?");
        $stmt->execute([$action, $comment, $id]);

        // Fetch approval item to synchronize corresponding entity
        $itemStmt = $pdo->prepare("SELECT * FROM approvals_inbox WHERE id = ?");
        $itemStmt->execute([$id]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        $workerId = null;
        $workerCode = null;

        if ($item) {
            $workerId = !empty($item['worker_id']) ? (int)$item['worker_id'] : 0;
            $workerCode = trim($item['employee_code'] ?? '');

            // Fallback: If workerId is 0, attempt lookup by employee_code or worker_name
            if (!$workerId && !empty($workerCode) && $workerCode !== 'Not Assigned') {
                $f = $pdo->prepare("SELECT id FROM workers WHERE worker_code = ? LIMIT 1");
                $f->execute([$workerCode]);
                $workerId = (int)$f->fetchColumn();
            }
            if (!$workerId && !empty($item['employee_name'])) {
                $f = $pdo->prepare("SELECT id, worker_code FROM workers WHERE full_name = ? AND unit_id = ? ORDER BY id DESC LIMIT 1");
                $f->execute([$item['employee_name'], $item['unit_id']]);
                $row = $f->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    $workerId = (int)$row['id'];
                    if (empty($workerCode)) $workerCode = trim($row['worker_code'] ?? '');
                }
            }

            // 1. KIOSK WORKER APPROVAL
            if ($item['approval_type'] === 'kiosk_worker' || $workerId > 0) {
                if ($action === 'approved') {
                    // Only synchronize employee code if it is a real assigned code (not 'Not Assigned' and not numeric id)
                    if (!empty($workerCode) && $workerCode !== 'Not Assigned' && $workerCode !== (string)$workerId && $workerId > 0) {
                        $pdo->prepare("UPDATE workers SET worker_code = ? WHERE id = ? AND (worker_code IS NULL OR worker_code = '')")
                            ->execute([$workerCode, $workerId]);
                        $pdo->prepare("UPDATE approvals_inbox SET employee_code = ? WHERE id = ?")
                            ->execute([$workerCode, $id]);
                        $pdo->prepare("UPDATE universal_registrations SET employee_code = ? WHERE worker_id = ? AND (employee_code IS NULL OR employee_code = '')")
                            ->execute([$workerCode, $workerId]);
                    }

                    if ($workerId > 0) {
                        $pdo->prepare("UPDATE workers SET approval_status = 'approved', embedding_version = embedding_version + 1, updated_at = NOW() WHERE id = ?")
                            ->execute([$workerId]);
                        $pdo->prepare("UPDATE universal_registrations SET updated_at = NOW() WHERE worker_id = ?")
                            ->execute([$workerId]);
                    }
                } else if ($action === 'rejected') {
                    if ($workerId > 0) {
                        $pdo->prepare("UPDATE workers SET approval_status = 'rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?")
                            ->execute([$comment, $workerId]);
                    }
                }
            }

            // 2. LEAVE REQUEST APPROVAL
            if ($item['approval_type'] === 'leave' && $workerId > 0) {
                $pdo->prepare("UPDATE leave_requests SET status = ?, approver_comment = ?, updated_at = NOW() WHERE worker_id = ? AND status = 'pending'")
                    ->execute([$action, $comment, $workerId]);
            }

            // 3. SALARY ADVANCE APPROVAL
            if ($item['approval_type'] === 'advance' && $workerId > 0) {
                $pdo->prepare("UPDATE salary_advances SET status = ?, updated_at = NOW() WHERE worker_id = ? AND status = 'pending'")
                    ->execute([$action, $workerId]);
            }
        }

        logAudit('WEB_PORTAL', 'HR_MANAGER', strtoupper($action) . '_APPROVAL', 'approvals_inbox', $id, $comment);

        jsonSuccess("Approval item #$id has been $action successfully", [
            'id' => $id,
            'action' => $action,
            'worker_id' => $workerId,
            'worker_code' => $workerCode
        ]);
    }

    // W06: Payroll Run Data
    public static function getPayrollRun() {
        $pdo = getDbConnection();
        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $unit = $_GET['unit'] ?? 'all';

        $unitClause = "";
        if ($unit !== 'all' && !empty($unit)) {
            $unitClause = " AND unit_id = " . $pdo->quote($unit);
        }

        $monthLabel = date('F Y', strtotime("$month-01"));
        $daysInMonth = (int)date('t', strtotime("$month-01"));

        // Check if an existing approved/locked payroll run is recorded
        $run = $pdo->query("SELECT * FROM payroll_runs WHERE month_year = '$month' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        $workers = $pdo->query("SELECT id, worker_code, full_name, unit_id, monthly_salary, designation 
                                FROM workers 
                                WHERE approval_status = 'approved' AND deleted_at IS NULL $unitClause 
                                ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        $totalGross = 0.0;
        $totalStatutory = 0.0;
        $totalAdvance = 0.0;
        $totalNet = 0.0;
        $varianceFlags = 0;

        foreach ($workers as $w) {
            $wId = (int)$w['id'];
            $wCode = $w['worker_code'] ?: (string)$wId;
            $salary = (float)($w['monthly_salary'] ?? 18000.00);
            
            // Calculate punches in this month
            $punchesCount = (int)$pdo->query("SELECT COUNT(DISTINCT DATE(punch_time)) FROM attendance_punches WHERE worker_id = $wId AND DATE(punch_time) LIKE '$month%' AND punch_type = 'IN'")->fetchColumn();
            
            // Paid days calculation: Sunday weekly offs + present days
            $sundays = 0;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                if (date('N', strtotime(sprintf('%s-%02d', $month, $d))) == 7) $sundays++;
            }
            $paidDays = min($daysInMonth, $punchesCount > 0 ? ($punchesCount + $sundays) : 0);
            
            $proratedGross = $paidDays > 0 ? round(($salary / $daysInMonth) * $paidDays, 2) : 0.00;
            
            // Deductions
            $pf = ($proratedGross > 0) ? round(min(15000, $proratedGross) * 0.12, 2) : 0.00;
            $esi = ($proratedGross > 0 && $proratedGross <= 21000) ? round($proratedGross * 0.0075, 2) : 0.00;
            $pt = ($proratedGross >= 15000) ? 200.00 : 0.00;
            $tds = 0.00;
            $adv = 0.00;

            $statutory = $pf + $esi + $pt + $tds;
            $netPay = max(0, $proratedGross - $statutory - $adv);

            $varFlag = ($paidDays < 15 && $paidDays > 0) ? 'LOP Flag' : 'OK';
            if ($varFlag !== 'OK') $varianceFlags++;

            $items[] = [
                'worker_id' => $wId,
                'employee_code' => $wCode,
                'employee_name' => $w['full_name'],
                'unit_id' => $w['unit_id'],
                'paid_days' => $paidDays,
                'gross_pay' => $proratedGross,
                'pf_deduction' => $pf,
                'esi_deduction' => $esi,
                'pt_deduction' => $pt,
                'tds_deduction' => $tds,
                'advance_deduction' => $adv,
                'net_pay' => $netPay,
                'variance_flag' => $varFlag
            ];

            $totalGross += $proratedGross;
            $totalStatutory += $statutory;
            $totalAdvance += $adv;
            $totalNet += $netPay;
        }

        if (!$run) {
            $run = [
                'id' => 1,
                'month_year' => $month,
                'run_title' => "Payroll run · $monthLabel",
                'stage' => count($workers) > 0 ? 'preview_variances' : 'select_units',
                'total_employees' => count($workers),
                'gross_total' => $totalGross,
                'statutory_total' => $totalStatutory,
                'advance_recovery_total' => $totalAdvance,
                'net_pay_total' => $totalNet,
                'variance_flags_count' => $varianceFlags,
                'status' => count($workers) > 0 ? 'under_review' : 'draft'
            ];
        }

        jsonSuccess("Payroll run loaded", [
            'month' => $month,
            'month_label' => $monthLabel,
            'run' => $run,
            'items' => $items,
            'steps' => [
                ['num' => 1, 'title' => 'Select units', 'status' => 'completed'],
                ['num' => 2, 'title' => 'Attendance locked', 'status' => count($workers) > 0 ? 'completed' : 'pending'],
                ['num' => 3, 'title' => 'Preview & variances', 'status' => count($workers) > 0 ? 'active' : 'pending'],
                ['num' => 4, 'title' => 'Approve', 'status' => 'pending'],
                ['num' => 5, 'title' => 'Lock payroll', 'status' => 'pending'],
                ['num' => 6, 'title' => 'Payslips & bank file', 'status' => 'pending']
            ]
        ]);
    }

    // W07: Roles & Permissions Matrix
    public static function getRolesMatrix() {
        $pdo = getDbConnection();
        $roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
        $selectedRoleCode = $_GET['role'] ?? 'hr_manager';

        $roleObj = null;
        foreach ($roles as $r) {
            if ($r['role_code'] === $selectedRoleCode) {
                $roleObj = $r;
                break;
            }
        }
        if (!$roleObj) $roleObj = $roles[1] ?? $roles[0];

        $permStmt = $pdo->prepare("SELECT * FROM role_permissions WHERE role_id = ?");
        $permStmt->execute([$roleObj['id']]);
        $perms = $permStmt->fetchAll();

        // Standard modules for W07
        $modules = ['Employees', 'Attendance', 'Leaves', 'Advances', 'Onboarding', 'Payroll', 'Exit & F&F', 'Kiosks', 'Roles & Access'];
        $permMap = [];
        foreach ($perms as $p) {
            $permMap[$p['module_name']] = $p;
        }

        $matrix = [];
        foreach ($modules as $mod) {
            $m = $permMap[$mod] ?? [
                'can_view' => 1,
                'can_create' => ($mod === 'Payroll' || $mod === 'Roles & Access') ? 0 : 1,
                'can_edit' => ($mod === 'Payroll' || $mod === 'Roles & Access') ? 0 : 1,
                'can_approve' => in_array($mod, ['Attendance', 'Leaves', 'Advances', 'Onboarding', 'Exit & F&F']) ? 1 : 0,
                'can_export' => 1,
                'can_lock' => ($mod === 'Attendance') ? 1 : 0,
            ];
            $matrix[] = [
                'module' => $mod,
                'can_view' => (bool)$m['can_view'],
                'can_create' => (bool)$m['can_create'],
                'can_edit' => (bool)$m['can_edit'],
                'can_approve' => (bool)$m['can_approve'],
                'can_export' => (bool)$m['can_export'],
                'can_lock' => (bool)$m['can_lock']
            ];
        }

        $allUnitIds = $pdo->query("SELECT id FROM units WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

        jsonSuccess("Roles & permissions loaded", [
            'roles' => $roles,
            'selected_role' => $roleObj,
            'permissions_matrix' => $matrix,
            'unit_access' => $allUnitIds ?: ['Corporate', 'TG-Mega', 'TGH-1', 'TGK-1', 'TGK-2', 'TGK-3', 'TGK-4', 'TGR', 'TGT']
        ]);
    }

    // -------------------------------------------------------------------------
    // W08 / Master Settings: Shift Master with IST Timings
    // -------------------------------------------------------------------------
    public static function getShifts() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        date_default_timezone_set('Asia/Kolkata');
        $currentTimeIst = date('H:i:s');
        $currentDateIst = date('d M Y');

        $shifts = $pdo->query("SELECT * FROM shifts WHERE deleted_at IS NULL ORDER BY start_time ASC")->fetchAll(PDO::FETCH_ASSOC);

        $activeShiftCode = null;
        $formattedShifts = [];

        foreach ($shifts as $s) {
            $start = $s['start_time'];
            $end = $s['end_time'];
            $isCurrent = false;

            if ($s['is_active']) {
                if ($start <= $end) {
                    if ($currentTimeIst >= $start && $currentTimeIst < $end) {
                        $isCurrent = true;
                        $activeShiftCode = $s['shift_code'];
                    }
                } else {
                    // Overnight Shift (e.g. 22:00 to 06:00)
                    if ($currentTimeIst >= $start || $currentTimeIst < $end) {
                        $isCurrent = true;
                        $activeShiftCode = $s['shift_code'];
                    }
                }
            }

            // Duration calculation
            $startTs = strtotime("2026-01-01 $start");
            $endTs = strtotime("2026-01-01 $end");
            if ($endTs < $startTs) $endTs += 86400;
            $durationHours = round(($endTs - $startTs) / 3600, 1);

            $formattedShifts[] = [
                'id' => (int)$s['id'],
                'shift_code' => $s['shift_code'],
                'shift_name' => $s['shift_name'],
                'start_time' => $start,
                'end_time' => $end,
                'start_time_fmt' => date('h:i A', strtotime("2026-01-01 $start")),
                'end_time_fmt' => date('h:i A', strtotime("2026-01-01 $end")),
                'timing_label' => date('h:i A', strtotime("2026-01-01 $start")) . ' – ' . date('h:i A', strtotime("2026-01-01 $end")),
                'duration_hours' => $durationHours . ' hrs',
                'grace_minutes' => (int)$s['grace_minutes'],
                'half_day_threshold_minutes' => (int)$s['half_day_threshold_minutes'],
                'full_day_threshold_minutes' => (int)$s['full_day_threshold_minutes'],
                'is_active' => (bool)$s['is_active'],
                'is_current_ist' => $isCurrent
            ];
        }

        jsonSuccess("Shifts retrieved successfully", [
            'shifts' => $formattedShifts,
            'current_ist_time' => date('h:i:s A'),
            'current_ist_date' => $currentDateIst,
            'active_shift_code' => $activeShiftCode,
            'total_shifts' => count($formattedShifts)
        ]);
    }

    public static function saveShift() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input)) $input = $_POST;
        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $code = strtoupper(trim($input['shift_code'] ?? ''));
        $name = trim($input['shift_name'] ?? '');
        $start = trim($input['start_time'] ?? '09:00:00');
        $end = trim($input['end_time'] ?? '17:30:00');
        $grace = isset($input['grace_minutes']) ? (int)$input['grace_minutes'] : 15;
        $halfDay = isset($input['half_day_threshold_minutes']) ? (int)$input['half_day_threshold_minutes'] : 240;
        $fullDay = isset($input['full_day_threshold_minutes']) ? (int)$input['full_day_threshold_minutes'] : 480;
        $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;

        if (empty($code) || empty($name) || empty($start) || empty($end)) {
            jsonError("Shift Code, Name, Start Time, and End Time are required", 422);
        }

        if (strlen($start) == 5) $start .= ':00';
        if (strlen($end) == 5) $end .= ':00';

        if ($id) {
            $stmt = $pdo->prepare("UPDATE shifts SET 
                shift_code = ?, shift_name = ?, start_time = ?, end_time = ?, 
                grace_minutes = ?, half_day_threshold_minutes = ?, full_day_threshold_minutes = ?, 
                is_active = ?, updated_at = NOW() 
                WHERE id = ?");
            $stmt->execute([$code, $name, $start, $end, $grace, $halfDay, $fullDay, $isActive, $id]);
            logAudit('PORTAL-ADMIN', 'SHIFT_UPDATE', "Updated Shift: $code ($name)", 'shifts', $id);
            jsonSuccess("Shift updated successfully", ['id' => $id]);
        } else {
            $chk = $pdo->prepare("SELECT id FROM shifts WHERE shift_code = ? AND deleted_at IS NULL LIMIT 1");
            $chk->execute([$code]);
            if ($chk->fetch()) {
                jsonError("Shift code '$code' already exists", 409);
            }

            $stmt = $pdo->prepare("INSERT INTO shifts 
                (shift_code, shift_name, start_time, end_time, grace_minutes, half_day_threshold_minutes, full_day_threshold_minutes, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$code, $name, $start, $end, $grace, $halfDay, $fullDay, $isActive]);
            $newId = (int)$pdo->lastInsertId();
            logAudit('PORTAL-ADMIN', 'SHIFT_CREATE', "Created Shift: $code ($name)", 'shifts', $newId);
            jsonSuccess("Shift created successfully", ['id' => $newId]);
        }
    }

    public static function toggleShiftStatus() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($input)) $input = $_POST;
        $id = (int)($input['id'] ?? $input['shift_id'] ?? $_GET['id'] ?? 0);
        if (!$id) jsonError("Valid Shift ID required", 422);

        $stmt = $pdo->prepare("UPDATE shifts SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);

        $newStatus = (int)$pdo->query("SELECT is_active FROM shifts WHERE id = $id")->fetchColumn();
        logAudit('PORTAL-ADMIN', 'SHIFT_TOGGLE', "Toggled shift #$id active status to $newStatus", 'shifts', $id);

        jsonSuccess("Shift status updated", ['id' => $id, 'is_active' => (bool)$newStatus]);
    }
}
