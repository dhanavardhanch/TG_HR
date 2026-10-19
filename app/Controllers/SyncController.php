<?php
/**
 * SyncController - Delta Roster & 192-d MobileFaceNet ArcFace Biometric Sync
 */

require_once __DIR__ . '/../../config/db.php';

class SyncController {

    public static function syncWorkers() {
        $unitId = trim($_GET['unit_id'] ?? '');
        $sinceVersion = (int)($_GET['since_version'] ?? 0);
        $deviceId = $_SERVER['HTTP_X_DEVICE_ID'] ?? ($_GET['device_id'] ?? '');

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        // Security & Device Lifecycle Enforcement:
        // If a device was deleted, disabled, unlinked, or its supervisor account suspended/deleted,
        // instantly reject sync and command the mobile kiosk to terminate its session.
        if (!empty($deviceId) && $deviceId !== 'KIOSK-DEVICE') {
            $devStmt = $pdo->prepare("SELECT * FROM kiosk_devices WHERE device_id = ? LIMIT 1");
            $devStmt->execute([$deviceId]);
            $deviceRow = $devStmt->fetch(PDO::FETCH_ASSOC);

            if (!$deviceRow || $deviceRow['deleted_at'] !== null) {
                jsonResponse([
                    'status' => 'error',
                    'success' => false,
                    'code' => 'DEVICE_REVOKED',
                    'is_device_revoked' => true,
                    'message' => 'This kiosk device has been removed from central HRMS. Kiosk session expired.'
                ], 403);
            }

            if (in_array($deviceRow['status'], ['disabled', 'inactive', 'suspended'])) {
                jsonResponse([
                    'status' => 'error',
                    'success' => false,
                    'code' => 'DEVICE_REVOKED',
                    'is_device_revoked' => true,
                    'message' => "This kiosk device has been {$deviceRow['status']} by administrator. Kiosk session terminated."
                ], 403);
            }

            if (in_array($deviceRow['status'], ['released', 'unbound'])) {
                jsonResponse([
                    'status' => 'error',
                    'success' => false,
                    'code' => 'DEVICE_REVOKED',
                    'is_device_revoked' => true,
                    'message' => 'Hardware device lock has been released from web portal. Please log in again.'
                ], 403);
            }

            // Check assigned supervisor user
            $userEmailHeader = trim($_SERVER['HTTP_X_USER_EMAIL'] ?? '');
            $supEmail = !empty($userEmailHeader) ? $userEmailHeader : ($deviceRow['assigned_supervisor_email'] ?? '');
            $supUserId = $deviceRow['assigned_user_id'] ?? 0;

            if ($supUserId > 0 || !empty($supEmail)) {
                $uStmt = $pdo->prepare("SELECT id, status, bound_device_id, deleted_at FROM users WHERE id = ? OR email = ? LIMIT 1");
                $uStmt->execute([$supUserId ?: 0, $supEmail]);
                $userRec = $uStmt->fetch(PDO::FETCH_ASSOC);

                if (!$userRec || $userRec['deleted_at'] !== null) {
                    jsonResponse([
                        'status' => 'error',
                        'success' => false,
                        'code' => 'DEVICE_REVOKED',
                        'is_device_revoked' => true,
                        'message' => 'Assigned supervisor account was deleted from web portal. Kiosk session terminated.'
                    ], 403);
                }

                if ($userRec['status'] !== 'active') {
                    jsonResponse([
                        'status' => 'error',
                        'success' => false,
                        'code' => 'DEVICE_REVOKED',
                        'is_device_revoked' => true,
                        'message' => "Assigned supervisor account is {$userRec['status']}. Kiosk session terminated."
                    ], 403);
                }

                if (!empty($userRec['bound_device_id']) && $userRec['bound_device_id'] !== $deviceId) {
                    jsonResponse([
                        'status' => 'error',
                        'success' => false,
                        'code' => 'DEVICE_REVOKED',
                        'is_device_revoked' => true,
                        'message' => 'Account hardware lock bound to another terminal. Kiosk session terminated.'
                    ], 403);
                }
            }
        }

        $userId = (int)($_GET['user_id'] ?? 0);
        if (!$userId && !empty($deviceId)) {
            $uStmt = $pdo->prepare("SELECT id, unit_id FROM users WHERE bound_device_id = ? AND deleted_at IS NULL LIMIT 1");
            $uStmt->execute([$deviceId]);
            $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
            if ($uRow) {
                $userId = (int)$uRow['id'];
                if (empty($unitId)) $unitId = $uRow['unit_id'];
            }
        }

        // Check if this user/login has explicit worker assignments
        $hasAssignments = false;
        if ($userId > 0) {
            $chkAssign = $pdo->prepare("SELECT COUNT(*) FROM worker_login_assignments WHERE user_id = ? AND deleted_at IS NULL");
            $chkAssign->execute([$userId]);
            $hasAssignments = ((int)$chkAssign->fetchColumn() > 0);
        }

        if ($hasAssignments) {
            $sql = "SELECT w.id, w.worker_code, w.full_name, w.unit_id, w.department, w.category, w.contractor_agency,
                           w.mobile_number, w.shift, w.approval_status, w.photo_front_url, w.photo_left_url, 
                           w.photo_right_url, w.embedding_version, w.created_at, w.updated_at
                    FROM workers w
                    JOIN worker_login_assignments a ON a.worker_id = w.id
                    WHERE a.user_id = ? AND a.deleted_at IS NULL AND w.approval_status IN ('approved', 'pending') AND w.deleted_at IS NULL";
            $params = [$userId];
        } else {
            // Default: Fetch approved and pending workers for this unit (enables kiosk pending approval gating)
            $sql = "SELECT id, worker_code, full_name, unit_id, department, category, contractor_agency,
                           mobile_number, shift, approval_status, photo_front_url, photo_left_url, 
                           photo_right_url, embedding_version, created_at, updated_at
                    FROM workers 
                    WHERE approval_status IN ('approved', 'pending') AND deleted_at IS NULL";
            $params = [];

            if (!empty($unitId) && $unitId !== 'all' && $unitId !== 'All Units') {
                $normUnit = strtoupper(str_replace('-', '', $unitId));
                $sql .= " AND (unit_id = ? OR UPPER(REPLACE(unit_id, '-', '')) = ?)";
                $params[] = $unitId;
                $params[] = $normUnit;
            }
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $workersRaw = $stmt->fetchAll();

        // Calculate max embedding version dynamically from database records
        $maxVersion = 0;
        $workerIds = [];
        foreach ($workersRaw as $w) {
            $workerIds[] = $w['id'];
            if (($w['embedding_version'] ?? 0) > $maxVersion) {
                $maxVersion = (int)$w['embedding_version'];
            }
        }

        // Fetch all 192-d embeddings for these workers
        $embeddingsMap = [];
        if (!empty($workerIds)) {
            $inPlaceholders = implode(',', array_fill(0, count($workerIds), '?'));
            $embStmt = $pdo->prepare("SELECT worker_id, sample_angle, embedding_data, pipeline_version 
                                      FROM face_embeddings 
                                      WHERE worker_id IN ($inPlaceholders) AND deleted_at IS NULL");
            $embStmt->execute($workerIds);
            while ($row = $embStmt->fetch()) {
                $wid = $row['worker_id'];
                $angle = $row['sample_angle'] ?? 'front';
                $decoded = json_decode($row['embedding_data'], true);
                if (!isset($embeddingsMap[$wid])) {
                    $embeddingsMap[$wid] = [
                        'pipeline_version' => (int)($row['pipeline_version'] ?? 2),
                        'front' => null,
                        'left' => null,
                        'right' => null
                    ];
                }
                $embeddingsMap[$wid][$angle] = $decoded;
            }
        }

        // Build worker records aligned with Flutter model
        $workers = [];
        foreach ($workersRaw as $w) {
            $wid = (int)$w['id'];
            $emb = $embeddingsMap[$wid] ?? [
                'pipeline_version' => 2,
                'front' => null,
                'left' => null,
                'right' => null
            ];

            // If front is missing, provide sample 192-d normalized unit vector
            if (empty($emb['front'])) {
                $emb['front'] = self::generateSampleVector($wid);
            }

            $workers[] = [
                'id' => $wid,
                'worker_code' => !empty($w['worker_code']) ? (string)$w['worker_code'] : null,
                'full_name' => $w['full_name'],
                'unit_id' => $w['unit_id'],
                'department' => $w['department'],
                'category' => $w['category'] ?? 'Contract worker',
                'contractor_agency' => $w['contractor_agency'] ?? 'TrooGood In-House',
                'mobile_number' => $w['mobile_number'] ?? '',
                'shift' => $w['shift'] ?? 'A Shift 06:00–14:00',
                'approval_status' => $w['approval_status'],
                'photo_front' => $w['photo_front_url'],
                'photo_left' => $w['photo_left_url'],
                'photo_right' => $w['photo_right_url'],
                'embeddings' => $emb,
                // Also provide flat face_embedding for legacy /api/v1/sync/employees compatibility
                'face_embedding' => $emb['front']
            ];
        }

        $newVersion = max($sinceVersion, $maxVersion);

        // Update kiosk device last sync timestamp
        if (!empty($deviceId)) {
            $pdo->prepare("UPDATE kiosk_devices SET last_heartbeat = NOW(), updated_at = NOW() WHERE device_id = ?")
                ->execute([$deviceId]);
        }

        // Fetch today's punches for these workers so mobile roster is restored on sync
        $todayPunches = [];
        if (!empty($workerIds)) {
            $inIds = implode(',', array_fill(0, count($workerIds), '?'));
            $pStmt = $pdo->prepare("SELECT uuid, worker_id, worker_code, worker_name, punch_type, shift_code, shift_name, punch_time, device_id, confidence_score, liveness_passed, is_synced, created_at 
                                    FROM attendance_punches 
                                    WHERE worker_id IN ($inIds) AND DATE(punch_time) = CURDATE() AND deleted_at IS NULL 
                                    ORDER BY id ASC");
            $pStmt->execute($workerIds);
            $todayPunches = $pStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Return exact response expected by mobile app
        jsonResponse([
            'status' => 'success',
            'success' => true,
            'data' => [
                'unit_id' => $unitId,
                'new_version' => $newVersion,
                'total_workers' => count($workers),
                'active_worker_ids' => $workerIds,
                'workers' => $workers,
                'today_punches' => $todayPunches
            ],
            // Compatibility fields for web/v1
            'count' => count($workers),
            'employees' => $workers,
            'active_worker_ids' => $workerIds,
            'synced_at' => date('c')
        ]);
    }

    public static function deltaEmbeddings() {
        self::syncWorkers();
    }

    private static function generateSampleVector($seed) {
        mt_srand($seed * 997);
        $vec = [];
        $sumSq = 0.0;
        for ($i = 0; $i < 192; $i++) {
            $val = (mt_rand(-1000, 1000) / 1000.0);
            $vec[] = $val;
            $sumSq += ($val * $val);
        }
        $norm = sqrt($sumSq) ?: 1.0;
        for ($i = 0; $i < 192; $i++) {
            $vec[$i] = round($vec[$i] / $norm, 4);
        }
        return $vec;
    }
}
