<?php
/**
 * LeaveController - Statutory & Comp-Off Leave Calculation Engine
 * Formula: Closing Balance = Opening + Sunday/Holiday Worked (P) + Accrual (1.5) - Leaves Taken (A/L)
 */

require_once __DIR__ . '/../../config/db.php';

class LeaveController {

    public static function getLeaveCalculator() {
        $pdo = getDbConnection();
        $month = $_GET['month'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }

        $unit = $_GET['unit'] ?? 'all';
        $dept = $_GET['department'] ?? 'all';
        $monthlyAccrual = (float)($_GET['accrual_rate'] ?? 1.5); // Default 1.5 leaves per month

        $year = (int)substr($month, 0, 4);
        $daysInMonth = (int)date('t', strtotime("$month-01"));

        // 1. Fetch holidays for this month from holiday_calendar
        $hStmt = $pdo->prepare("SELECT holiday_date, holiday_name, unit_id FROM holiday_calendar WHERE holiday_date BETWEEN ? AND ? AND is_active = 1");
        $hStmt->execute(["$month-01", "$month-$daysInMonth"]);
        $holidays = $hStmt->fetchAll(PDO::FETCH_ASSOC);
        $holidayDates = [];
        foreach ($holidays as $h) {
            $holidayDates[$h['holiday_date']] = $h;
        }

        // 2. Fetch workers
        $sql = "SELECT id, worker_code, full_name, unit_id, department, category, contractor_agency, created_at 
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
        $sql .= " ORDER BY id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Fetch punches for this month
        $punchStmt = $pdo->prepare("SELECT worker_id, DATE(punch_time) as punch_date, punch_type, unit_id
                                    FROM attendance_punches 
                                    WHERE punch_time BETWEEN ? AND ? AND deleted_at IS NULL
                                    ORDER BY punch_time ASC");
        $punchStmt->execute(["$month-01 00:00:00", "$month-$daysInMonth 23:59:59"]);
        $punches = $punchStmt->fetchAll(PDO::FETCH_ASSOC);

        $punchedWorkerDates = [];
        foreach ($punches as $p) {
            $punchedWorkerDates[(int)$p['worker_id']][$p['punch_date']] = true;
        }

        // 4. Fetch overrides for this month
        $ovStmt = $pdo->prepare("SELECT worker_id, date, status FROM attendance_overrides WHERE date BETWEEN ? AND ?");
        $ovStmt->execute(["$month-01", "$month-$daysInMonth"]);
        $overrides = $ovStmt->fetchAll(PDO::FETCH_ASSOC);
        $overrideWorkerDates = [];
        foreach ($overrides as $ov) {
            $overrideWorkerDates[(int)$ov['worker_id']][$ov['date']] = $ov['status'];
        }

        $records = [];
        $totalEarnedLeavesAll = 0.0;
        $totalCompOffAll = 0;
        $totalHolidayWorkedAll = 0;

        foreach ($workers as $w) {
            $wId = (int)$w['id'];
            $wCode = $w['worker_code'] ?: (string)$wId;
            $wUnit = $w['unit_id'] ?: 'TGK-1';

            $openingBalance = 5.0; // Baseline starting balance
            $sundayWorkedCount = 0;
            $holidayWorkedCount = 0;
            $presentDays = 0;
            $absentDays = 0;
            $leavesTaken = 0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr = sprintf('%s-%02d', $month, $d);
                $isSunday = (date('N', strtotime($dateStr)) == 7);
                $isHoliday = isset($holidayDates[$dateStr]) && ($holidayDates[$dateStr]['unit_id'] === 'ALL' || $holidayDates[$dateStr]['unit_id'] === $wUnit);

                $hasPunch = !empty($punchedWorkerDates[$wId][$dateStr]);
                $overrideStatus = $overrideWorkerDates[$wId][$dateStr] ?? null;

                $finalStatus = '-';
                if ($overrideStatus) {
                    $finalStatus = $overrideStatus;
                } elseif ($hasPunch) {
                    $finalStatus = 'P';
                } else {
                    if ($isHoliday) $finalStatus = 'H';
                    elseif ($isSunday) $finalStatus = 'W';
                    else $finalStatus = 'A';
                }

                if ($finalStatus === 'P') {
                    $presentDays++;
                    if ($isSunday) {
                        // Working on Sunday awards attendance AND +1 leave credit!
                        $sundayWorkedCount++;
                    }
                    if ($isHoliday) {
                        // Working on Holiday awards attendance AND +1 leave credit!
                        $holidayWorkedCount++;
                    }
                } elseif ($finalStatus === 'A' || $finalStatus === 'L') {
                    $absentDays++;
                    if ($finalStatus === 'L') $leavesTaken++;
                }
            }

            // Calculations
            $compOffEarned = $sundayWorkedCount + $holidayWorkedCount;
            $totalCreditsEarned = $monthlyAccrual + $compOffEarned;
            $deductions = $leavesTaken > 0 ? $leavesTaken : 0; // leaves availed
            $closingBalance = max(0.0, round($openingBalance + $totalCreditsEarned - $deductions, 2));

            $totalCompOffAll += $sundayWorkedCount;
            $totalHolidayWorkedAll += $holidayWorkedCount;
            $totalEarnedLeavesAll += $compOffEarned;

            $records[] = [
                'worker_id' => $wId,
                'worker_code' => $wCode,
                'full_name' => $w['full_name'],
                'unit_id' => $w['unit_id'],
                'department' => $w['department'],
                'engagement_type' => $w['category'] ?? 'On Rolls',
                'opening_balance' => $openingBalance,
                'monthly_accrual' => $monthlyAccrual,
                'sunday_worked' => $sundayWorkedCount,
                'holiday_worked' => $holidayWorkedCount,
                'comp_off_added' => $compOffEarned,
                'total_earned' => $totalCreditsEarned,
                'leaves_availed' => $deductions,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'closing_balance' => $closingBalance
            ];
        }

        jsonSuccess("Leave calculation matrix loaded for $month", [
            'month' => $month,
            'accrual_rate' => $monthlyAccrual,
            'holidays_in_month' => array_values($holidayDates),
            'summary' => [
                'total_workers' => count($workers),
                'total_sunday_worked' => $totalCompOffAll,
                'total_holiday_worked' => $totalHolidayWorkedAll,
                'total_comp_off_granted' => $totalEarnedLeavesAll
            ],
            'records' => $records
        ]);
    }

    public static function exportCsv() {
        $pdo = getDbConnection();
        $month = $_GET['month'] ?? date('Y-m');
        $unit = $_GET['unit'] ?? 'all';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="leave_register_' . $month . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Worker Code', 'Employee Name', 'Unit', 'Department', 'Opening Balance', 
            'Monthly Accrual', 'Sunday Duty (+1)', 'Holiday Duty (+1)', 'Total Earned', 
            'Leaves Availed', 'Closing Balance'
        ]);

        // Get calculations
        $_GET['month'] = $month;
        $_GET['unit'] = $unit;
        // Run logic internally
        ob_start();
        self::getLeaveCalculator();
        $json = ob_get_clean();
        $data = json_decode($json, true);

        if (!empty($data['data']['records'])) {
            foreach ($data['data']['records'] as $r) {
                fputcsv($out, [
                    $r['worker_code'],
                    $r['full_name'],
                    $r['unit_id'],
                    $r['department'],
                    $r['opening_balance'],
                    $r['monthly_accrual'],
                    $r['sunday_worked'],
                    $r['holiday_worked'],
                    $r['total_earned'],
                    $r['leaves_availed'],
                    $r['closing_balance']
                ]);
            }
        }

        fclose($out);
        exit();
    }
}
