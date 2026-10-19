<?php
/**
 * AuthController - Hardware Device Lock, Kiosk Login & Device Authorization
 */

require_once __DIR__ . '/../../config/db.php';

class AuthController {

    public static function login() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $identifier = trim($input['identifier'] ?? $input['email'] ?? '');
        $password = trim($input['password'] ?? '');
        $deviceId = trim($input['device_id'] ?? $input['device_uuid'] ?? ($_SERVER['HTTP_X_DEVICE_ID'] ?? 'DEFAULT-DEVICE'));
        $unitId = trim($input['unit_id'] ?? '');

        // Capture kiosk device location if provided
        $latitude = isset($input['latitude']) && $input['latitude'] !== '' && $input['latitude'] !== null ? floatval($input['latitude']) : null;
        $longitude = isset($input['longitude']) && $input['longitude'] !== '' && $input['longitude'] !== null ? floatval($input['longitude']) : null;
        $locationAccuracy = isset($input['location_accuracy']) && $input['location_accuracy'] !== '' && $input['location_accuracy'] !== null ? floatval($input['location_accuracy']) : null;
        $locationAddress = !empty($input['location_address']) ? trim($input['location_address']) : (!empty($input['address']) ? trim($input['address']) : null);

        if (empty($identifier) || empty($password)) {
            jsonError("Email ID and password are required.", 400);
        }

        $pdo = getDbConnection();
        if (!$pdo) {
            jsonError("Database connection unavailable.", 500);
        }

        // Find user by email ID and include official role_name from roles table
        $stmt = $pdo->prepare("SELECT u.*, COALESCE(r.role_name, u.role) AS role_name 
                               FROM users u 
                               LEFT JOIN roles r ON u.role = r.role_code 
                               WHERE u.email = ? AND u.deleted_at IS NULL LIMIT 1");
        $stmt->execute([$identifier]);
        $user = $stmt->fetch();

        // Verify password against secure database hash
        $isValidPass = false;
        if ($user) {
            if (password_verify($password, $user['password_hash']) || 
                md5($password) === $user['password_hash']) {
                $isValidPass = true;
            }
        }

        if (!$isValidPass || !$user) {
            jsonError("Invalid email or password.", 401);
        }

        // Hardware Device Lock Verification
        $currentBound = $user['bound_device_id'] ?? null;
        if (!empty($currentBound) && $currentBound !== $deviceId) {
            jsonResponse([
                'status' => 'error',
                'success' => false,
                'message' => "Device Lock Violation: Account active on terminal {$currentBound}. Release from backend first.",
                'is_device_locked' => true
            ], 403);
        }

        // Assigned Unit from Database (Source of Truth)
        $assignedUnit = !empty($user['unit_id']) ? $user['unit_id'] : ($unitId ?: 'TGK-1');

        // If not bound, bind to this physical device hardware UUID (do NOT change user's unit_id)
        if (empty($currentBound) && !empty($deviceId) && isset($user['id'])) {
            $updateUser = $pdo->prepare("UPDATE users SET bound_device_id = ?, updated_at = NOW() WHERE id = ?");
            $updateUser->execute([$deviceId, $user['id']]);
            $currentBound = $deviceId;
        }

        // Update or register kiosk device hardware with location metadata
        if (!empty($deviceId)) {
            $stmtDev = $pdo->prepare("INSERT INTO kiosk_devices (device_id, unit_id, assigned_user_id, assigned_supervisor_email, status, last_heartbeat, last_ip_address, last_latitude, last_longitude, last_location_accuracy, last_location_address, last_login_at, created_at, updated_at)
                                      VALUES (?, ?, ?, ?, 'active', NOW(), ?, ?, ?, ?, ?, NOW(), NOW(), NOW())
                                      ON DUPLICATE KEY UPDATE unit_id = VALUES(unit_id),
                                                              assigned_user_id = VALUES(assigned_user_id), 
                                                              assigned_supervisor_email = VALUES(assigned_supervisor_email),
                                                              status = 'active',
                                                              last_heartbeat = NOW(),
                                                              last_ip_address = VALUES(last_ip_address),
                                                              last_latitude = COALESCE(VALUES(last_latitude), last_latitude),
                                                              last_longitude = COALESCE(VALUES(last_longitude), last_longitude),
                                                              last_location_accuracy = COALESCE(VALUES(last_location_accuracy), last_location_accuracy),
                                                              last_location_address = COALESCE(VALUES(last_location_address), last_location_address),
                                                              last_login_at = NOW(),
                                                              updated_at = NOW()");
            $stmtDev->execute([
                $deviceId, 
                $assignedUnit, 
                $user['id'], 
                $user['email'], 
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                $latitude,
                $longitude,
                $locationAccuracy,
                $locationAddress
            ]);
        }

        // Generate session token
        $token = 'tg_tok_' . bin2hex(random_bytes(16));

        // Audit Log with location details
        $auditDetails = 'Login successful with hardware lock';
        if ($latitude !== null && $longitude !== null) {
            $auditDetails .= " [Location: Lat: {$latitude}, Lng: {$longitude}, Acc: {$locationAccuracy}m, Addr: {$locationAddress}]";
        }
        logAudit($deviceId, $user['email'], 'KIOSK_LOGIN', 'users', $user['id'], $auditDetails);

        $roleName = !empty($user['role_name']) ? $user['role_name'] : 'Gate Security Incharge';

        jsonResponse([
            'status' => 'success',
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'user_name' => $user['full_name'] ?? 'Supervisor',
                'role' => $user['role'] ?? 'gate_incharge',
                'role_name' => $roleName,
                'supervisor_pin' => $user['supervisor_pin'] ?? '1234',
                'unit_id' => $assignedUnit,
                'bound_device_id' => $currentBound ?? $deviceId
            ]
        ], 200);
    }

    public static function verifyDevice() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $deviceId = trim($input['device_id'] ?? $input['device_uuid'] ?? ($_SERVER['HTTP_X_DEVICE_ID'] ?? ''));

        // Capture kiosk device location if provided in verify check
        $latitude = isset($input['latitude']) && $input['latitude'] !== '' && $input['latitude'] !== null ? floatval($input['latitude']) : null;
        $longitude = isset($input['longitude']) && $input['longitude'] !== '' && $input['longitude'] !== null ? floatval($input['longitude']) : null;
        $locationAccuracy = isset($input['location_accuracy']) && $input['location_accuracy'] !== '' && $input['location_accuracy'] !== null ? floatval($input['location_accuracy']) : null;
        $locationAddress = !empty($input['location_address']) ? trim($input['location_address']) : (!empty($input['address']) ? trim($input['address']) : null);

        if (empty($deviceId)) {
            jsonError("Device ID is required.", 400);
        }

        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT k.*, u.unit_name, u.city 
                               FROM kiosk_devices k 
                               LEFT JOIN units u ON k.unit_id = u.id 
                               WHERE k.device_id = ? AND k.deleted_at IS NULL LIMIT 1");
        $stmt->execute([$deviceId]);
        $device = $stmt->fetch();

        if ($device) {
            if ($device['status'] !== 'active') {
                jsonResponse([
                    'status' => 'error',
                    'success' => false,
                    'code' => 'DEVICE_REVOKED',
                    'is_device_revoked' => true,
                    'message' => "This kiosk device has been {$device['status']} by administrator."
                ], 403);
            }

            $pdo->prepare("UPDATE kiosk_devices SET last_heartbeat = NOW(), last_ip_address = ?, 
                                last_latitude = COALESCE(?, last_latitude),
                                last_longitude = COALESCE(?, last_longitude),
                                last_location_accuracy = COALESCE(?, last_location_accuracy),
                                last_location_address = COALESCE(?, last_location_address),
                                updated_at = NOW() WHERE id = ?")
                ->execute([$_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $latitude, $longitude, $locationAccuracy, $locationAddress, $device['id']]);

            jsonSuccess("Device verified", [
                'device_id' => $device['device_id'],
                'unit_id' => $device['unit_id'],
                'unit_name' => $device['unit_name'],
                'status' => $device['status'],
                'app_version' => $device['app_version']
            ]);
        }

        // Device not found in database (e.g. deleted by administrator)
        jsonResponse([
            'status' => 'error',
            'success' => false,
            'code' => 'DEVICE_REVOKED',
            'is_device_revoked' => true,
            'message' => 'This kiosk device has been deleted from the central HRMS.'
        ], 403);
    }

    public static function releaseDevice() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $email = trim($input['email'] ?? $input['identifier'] ?? '');
        $deviceId = trim($input['device_id'] ?? '');

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        if (!empty($email)) {
            $stmt = $pdo->prepare("UPDATE users SET bound_device_id = NULL, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$email]);
            logAudit($deviceId, $email, 'RELEASE_DEVICE', 'users', $email, "Device lock released for user $email");
        }

        if (!empty($deviceId)) {
            $stmt = $pdo->prepare("UPDATE kiosk_devices SET status = 'released', updated_at = NOW() WHERE device_id = ?");
            $stmt->execute([$deviceId]);
            logAudit($deviceId, $email, 'RELEASE_DEVICE', 'kiosk_devices', $deviceId, "Device $deviceId released");
        }

        jsonSuccess("Device lock released. Account can now be logged in on a new device.");
    }

    public static function logout() {
        jsonSuccess("Logged out successfully");
    }
}
