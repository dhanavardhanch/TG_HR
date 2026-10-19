<?php
/**
 * PunchController - High-Speed Idempotent Offline Attendance Punch Sync Engine
 */

require_once __DIR__ . '/../../config/db.php';

class PunchController {

    public static function bulkPunches() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $deviceId = trim($input['device_id'] ?? ($_SERVER['HTTP_X_DEVICE_ID'] ?? 'DEFAULT-DEVICE'));
        $punches = $input['punches'] ?? $input['records'] ?? [];

        if (!is_array($punches) || empty($punches)) {
            jsonResponse([
                'status' => 'success',
                'success' => true,
                'message' => 'No punches to sync',
                'data' => [
                    'inserted_count' => 0,
                    'duplicates_ignored' => 0,
                    'synced_uuids' => []
                ]
            ]);
        }

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $insertedCount = 0;
        $duplicatesCount = 0;
        $syncedUuids = [];

        $stmt = $pdo->prepare("INSERT INTO attendance_punches 
            (uuid, worker_id, worker_code, worker_name, punch_type, shift_code, shift_name, punch_time, unit_id, device_id, confidence_score, liveness_passed, snapshot_url, latitude, longitude, location_accuracy, location_address, is_synced, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE shift_code = VALUES(shift_code), shift_name = VALUES(shift_name), unit_id = COALESCE(NULLIF(VALUES(unit_id), ''), unit_id), latitude = COALESCE(VALUES(latitude), latitude), longitude = COALESCE(VALUES(longitude), longitude), is_synced = 1, updated_at = NOW()");

        foreach ($punches as $p) {
            $uuid = trim($p['uuid'] ?? $p['offline_uuid'] ?? ('PUNCH-' . bin2hex(random_bytes(16))));
            $workerId = (int)($p['worker_id'] ?? $p['employee_id'] ?? 1);
            $workerCode = (string)($p['worker_code'] ?? $p['employee_code'] ?? $workerId);
            $workerName = (string)($p['worker_name'] ?? $p['full_name'] ?? "Worker #$workerCode");
            
            // Normalize punch type: IN or OUT (also handle check_in, check_out)
            $punchType = strtoupper($p['punch_type'] ?? 'IN');
            if ($punchType === 'CHECK_IN' || $punchType === 'CHECKIN') $punchType = 'IN';
            if ($punchType === 'CHECK_OUT' || $punchType === 'CHECKOUT') $punchType = 'OUT';

            $punchTime = $p['punch_time'] ?? $p['punch_timestamp'] ?? date('Y-m-d H:i:s');
            $punchDevice = $p['device_id'] ?? $deviceId;
            $confidence = (float)($p['confidence_score'] ?? 0.9500);
            $liveness = (!empty($p['liveness_passed']) && $p['liveness_passed'] !== false && $p['liveness_passed'] !== 'false') ? 1 : 0;
            $snapshotUrl = $p['snapshot_path'] ?? $p['snapshot_url'] ?? null;
            $latitude = (isset($p['latitude']) && $p['latitude'] !== '') ? (float)$p['latitude'] : null;
            $longitude = (isset($p['longitude']) && $p['longitude'] !== '') ? (float)$p['longitude'] : null;
            $accuracy = (isset($p['location_accuracy']) && $p['location_accuracy'] !== '') ? (float)$p['location_accuracy'] : null;
            $address = !empty($p['location_address']) ? (string)$p['location_address'] : null;

            // Resolve punch unit_id
            $punchUnit = trim($p['unit_id'] ?? $input['unit_id'] ?? '');
            if (empty($punchUnit) && !empty($punchDevice)) {
                $uStmt = $pdo->prepare("SELECT unit_id FROM kiosk_devices WHERE device_id = ? LIMIT 1");
                $uStmt->execute([$punchDevice]);
                $punchUnit = $uStmt->fetchColumn() ?: '';
                if (empty($punchUnit)) {
                    $uStmt2 = $pdo->prepare("SELECT unit_id FROM users WHERE bound_device_id = ? LIMIT 1");
                    $uStmt2->execute([$punchDevice]);
                    $punchUnit = $uStmt2->fetchColumn() ?: '';
                }
            }
            // Validate Worker Unit Assignment: Transferred workers cannot punch in previous/other units!
            $wStmt = $pdo->prepare("SELECT unit_id, worker_code, full_name FROM workers WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $wStmt->execute([$workerId]);
            $wRow = $wStmt->fetch(PDO::FETCH_ASSOC);
            if ($wRow) {
                $workerAssignedUnit = trim($wRow['unit_id'] ?? '');
                if (!empty($punchUnit) && !empty($workerAssignedUnit)) {
                    $normPunch = strtoupper(str_replace('-', '', $punchUnit));
                    $normAssigned = strtoupper(str_replace('-', '', $workerAssignedUnit));
                    if ($normPunch !== $normAssigned) {
                        // Block punch from wrong kiosk unit
                        logAudit($punchDevice, 'PUNCH_BLOCKED_UNIT_MISMATCH', 'ATTENDANCE_BATCH_SYNC', 'attendance_punches', $workerId, [
                            'worker_code' => $wRow['worker_code'],
                            'worker_name' => $wRow['full_name'],
                            'assigned_unit' => $workerAssignedUnit,
                            'attempted_unit' => $punchUnit,
                            'punch_time' => $punchTime
                        ]);
                        $duplicatesCount++;
                        continue;
                    }
                }
                if (empty($punchUnit)) {
                    $punchUnit = $workerAssignedUnit;
                }
            }

            // Automatic Shift Allocation by IST Punch Time from Shift Master
            $shiftInfo = self::determineShift($pdo, $punchTime, $workerId);
            $shiftCode = $shiftInfo['shift_code'];
            $shiftName = $shiftInfo['shift_name'];

            try {
                $stmt->execute([
                    $uuid,
                    $workerId,
                    $workerCode,
                    $workerName,
                    $punchType,
                    $shiftCode,
                    $shiftName,
                    $punchTime,
                    $punchUnit,
                    $punchDevice,
                    $confidence,
                    $liveness,
                    $snapshotUrl,
                    $latitude,
                    $longitude,
                    $accuracy,
                    $address
                ]);

                if ($stmt->rowCount() === 1) {
                    $insertedCount++;
                } else {
                    $duplicatesCount++;
                }
                $syncedUuids[] = $uuid;
            } catch (Exception $e) {
                error_log("Punch sync error for UUID $uuid: " . $e->getMessage());
            }
        }

        // Audit Log
        logAudit($deviceId, 'KIOSK_PUNCH_SYNC', 'ATTENDANCE_BATCH_SYNC', 'attendance_punches', count($syncedUuids), [
            'inserted' => $insertedCount,
            'duplicates_ignored' => $duplicatesCount
        ]);

        jsonResponse([
            'status' => 'success',
            'success' => true,
            'message' => 'Punches synced successfully',
            'data' => [
                'inserted_count' => $insertedCount,
                'duplicates_ignored' => $duplicatesCount,
                'synced_uuids' => $syncedUuids
            ],
            // Compatibility fields
            'synced_count' => count($syncedUuids)
        ]);
    }

    public static function singlePunch() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $deviceId = trim($input['device_id'] ?? ($_SERVER['HTTP_X_DEVICE_ID'] ?? 'DEFAULT-DEVICE'));
        
        $uuid = trim($input['uuid'] ?? ('PUNCH-' . bin2hex(random_bytes(16))));
        $workerId = (int)($input['worker_id'] ?? $input['employee_id'] ?? 1);
        $workerCode = (string)($input['worker_code'] ?? $workerId);
        $workerName = (string)($input['worker_name'] ?? "Worker #$workerCode");
        
        $punchType = strtoupper($input['punch_type'] ?? 'IN');
        if ($punchType === 'CHECK_IN') $punchType = 'IN';
        if ($punchType === 'CHECK_OUT') $punchType = 'OUT';

        $punchTime = $input['punch_time'] ?? $input['punch_timestamp'] ?? date('Y-m-d H:i:s');
        $confidence = (float)($input['confidence_score'] ?? 0.9500);
        $liveness = (!empty($input['liveness_passed'])) ? 1 : 0;
        $snapshot = $input['snapshot_path'] ?? $input['snapshot_url'] ?? null;

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        // Automatic Shift Allocation by IST Punch Time from Shift Master
        $shiftInfo = self::determineShift($pdo, $punchTime, $workerId);
        $shiftCode = $shiftInfo['shift_code'];
        $shiftName = $shiftInfo['shift_name'];

        // Resolve punch unit_id
        $punchUnit = trim($input['unit_id'] ?? '');
        if (empty($punchUnit) && !empty($deviceId)) {
            $uStmt = $pdo->prepare("SELECT unit_id FROM kiosk_devices WHERE device_id = ? LIMIT 1");
            $uStmt->execute([$deviceId]);
            $punchUnit = $uStmt->fetchColumn() ?: '';
            if (empty($punchUnit)) {
                $uStmt2 = $pdo->prepare("SELECT unit_id FROM users WHERE bound_device_id = ? LIMIT 1");
                $uStmt2->execute([$deviceId]);
                $punchUnit = $uStmt2->fetchColumn() ?: '';
            }
        }
        // Validate Worker Unit Assignment: Transferred workers cannot punch in previous/other units!
        $wStmt = $pdo->prepare("SELECT unit_id, worker_code, full_name FROM workers WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $wStmt->execute([$workerId]);
        $workerInfo = $wStmt->fetch(PDO::FETCH_ASSOC);
        if ($workerInfo) {
            $workerAssignedUnit = trim($workerInfo['unit_id'] ?? '');
            if (!empty($punchUnit) && !empty($workerAssignedUnit)) {
                $normPunch = strtoupper(str_replace('-', '', $punchUnit));
                $normAssigned = strtoupper(str_replace('-', '', $workerAssignedUnit));
                if ($normPunch !== $normAssigned) {
                    jsonError("Unit Restriction: Worker #{$workerInfo['worker_code']} ({$workerInfo['full_name']}) is assigned to $workerAssignedUnit. Cannot punch attendance at unit $punchUnit.", 422);
                }
            }
            if (empty($punchUnit)) {
                $punchUnit = $workerAssignedUnit;
            }
        }

        $latitude = (isset($input['latitude']) && $input['latitude'] !== '') ? (float)$input['latitude'] : null;
        $longitude = (isset($input['longitude']) && $input['longitude'] !== '') ? (float)$input['longitude'] : null;
        $accuracy = (isset($input['location_accuracy']) && $input['location_accuracy'] !== '') ? (float)$input['location_accuracy'] : null;
        $address = !empty($input['location_address']) ? (string)$input['location_address'] : null;

        $stmt = $pdo->prepare("INSERT INTO attendance_punches 
            (uuid, worker_id, worker_code, worker_name, punch_type, shift_code, shift_name, punch_time, unit_id, device_id, confidence_score, liveness_passed, snapshot_url, latitude, longitude, location_accuracy, location_address, is_synced, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE shift_code = VALUES(shift_code), shift_name = VALUES(shift_name), unit_id = COALESCE(NULLIF(VALUES(unit_id), ''), unit_id), latitude = COALESCE(VALUES(latitude), latitude), longitude = COALESCE(VALUES(longitude), longitude), is_synced = 1, updated_at = NOW()");
        $stmt->execute([
            $uuid,
            $workerId,
            $workerCode,
            $workerName,
            $punchType,
            $shiftCode,
            $shiftName,
            $punchTime,
            $punchUnit,
            $deviceId,
            $confidence,
            $liveness,
            $snapshot,
            $latitude,
            $longitude,
            $accuracy,
            $address
        ]);

        $punchId = $pdo->lastInsertId() ?: 1;

        jsonSuccess("Attendance punched successfully", [
            'punch_id' => $punchId,
            'uuid' => $uuid,
            'worker_id' => $workerId,
            'punch_type' => $punchType,
            'shift_code' => $shiftCode,
            'shift_name' => $shiftName,
            'punch_time' => $punchTime,
            'server_received_at' => date('c')
        ]);
    }

    /**
     * Determines shift based upon IST Time against Shift Master.
     * Shows only clean shift name without timings anywhere.
     */
    public static function determineShift($pdo, $punchTime, $workerId = null) {
        $time = date('H:i:s', strtotime($punchTime));
        $shifts = $pdo->query("SELECT shift_code, shift_name, start_time, end_time FROM shifts WHERE is_active = 1 AND deleted_at IS NULL ORDER BY start_time ASC")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($shifts)) {
            return ['shift_code' => 'GENERAL', 'shift_name' => 'General Shift'];
        }

        foreach ($shifts as $sh) {
            $start = $sh['start_time'];
            $end = $sh['end_time'];
            if ($start <= $end) {
                if ($time >= $start && $time < $end) {
                    $cleanName = preg_replace('/\s*\(.*?\)/', '', $sh['shift_name']);
                    return ['shift_code' => $sh['shift_code'], 'shift_name' => trim($cleanName)];
                }
            } else {
                // Overnight Shift (e.g. 22:00 - 06:00)
                if ($time >= $start || $time < $end) {
                    $cleanName = preg_replace('/\s*\(.*?\)/', '', $sh['shift_name']);
                    return ['shift_code' => $sh['shift_code'], 'shift_name' => trim($cleanName)];
                }
            }
        }

        // Check if worker has an assigned shift that matches Shift Master
        if ($workerId) {
            $wStmt = $pdo->prepare("SELECT shift FROM workers WHERE id = ? LIMIT 1");
            $wStmt->execute([$workerId]);
            $wShift = $wStmt->fetchColumn();
            if (!empty($wShift)) {
                $sStmt = $pdo->prepare("SELECT shift_code, shift_name FROM shifts WHERE (shift_name LIKE ? OR shift_code = ?) AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
                $sStmt->execute(["%$wShift%", $wShift]);
                $s = $sStmt->fetch(PDO::FETCH_ASSOC);
                if ($s) {
                    $cleanName = preg_replace('/\s*\(.*?\)/', '', $s['shift_name']);
                    return ['shift_code' => $s['shift_code'], 'shift_name' => trim($cleanName)];
                }
            }
        }

        $default = $shifts[0];
        $cleanName = preg_replace('/\s*\(.*?\)/', '', $default['shift_name']);
        return ['shift_code' => $default['shift_code'], 'shift_name' => trim($cleanName)];
    }
}
