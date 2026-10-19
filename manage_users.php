<?php
/**
 * TrooGood HRMS - User & Device Management CLI Tool
 * Usage:
 *   php manage_users.php list
 *   php manage_users.php set-password <email_or_mobile> <new_password>
 *   php manage_users.php create <full_name> <email> <mobile> <password> <role> <pin> [unit_id]
 *   php manage_users.php release-device <email_or_mobile>
 *   php manage_users.php devices
 */

require_once __DIR__ . '/config/db.php';

$pdo = getDbConnection();
if (!$pdo) {
    echo "❌ Database connection failed. Please check MySQL in XAMPP.\n";
    exit(1);
}

$cmd = $argv[1] ?? 'list';

switch ($cmd) {
    case 'list':
        $stmt = $pdo->query("SELECT id, full_name, email, mobile_number, role, supervisor_pin, bound_device_id, unit_id, updated_at FROM users WHERE deleted_at IS NULL");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n================================ TrooGood HRMS Users ================================\n";
        printf("%-4s | %-20s | %-22s | %-12s | %-12s | %-6s | %-16s | %-6s\n", "ID", "Name", "Email", "Mobile", "Role", "PIN", "Bound Device", "Unit");
        echo str_repeat("-", 108) . "\n";
        foreach ($users as $u) {
            printf("%-4s | %-20s | %-22s | %-12s | %-12s | %-6s | %-16s | %-6s\n",
                $u['id'],
                substr($u['full_name'], 0, 20),
                substr($u['email'], 0, 22),
                $u['mobile_number'] ?? '-',
                $u['role'],
                $u['supervisor_pin'] ?? '-',
                $u['bound_device_id'] ?: '(unbound)',
                $u['unit_id'] ?? '-'
            );
        }
        echo str_repeat("=", 108) . "\n\n";
        break;

    case 'devices':
        $stmt = $pdo->query("SELECT id, device_id, unit_id, assigned_supervisor_email, status, last_heartbeat, last_ip_address FROM kiosk_devices WHERE deleted_at IS NULL ORDER BY updated_at DESC");
        $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n============================= Registered Kiosk Devices =============================\n";
        printf("%-4s | %-18s | %-8s | %-24s | %-8s | %-20s | %-15s\n", "ID", "Device ID", "Unit", "Assigned User", "Status", "Last Active", "IP Address");
        echo str_repeat("-", 112) . "\n";
        foreach ($devices as $d) {
            printf("%-4s | %-18s | %-8s | %-24s | %-8s | %-20s | %-15s\n",
                $d['id'],
                $d['device_id'],
                $d['unit_id'],
                $d['assigned_supervisor_email'] ?? '-',
                $d['status'],
                $d['last_heartbeat'] ?? '-',
                $d['last_ip_address'] ?? '-'
            );
        }
        echo str_repeat("=", 112) . "\n\n";
        break;

    case 'set-password':
        $target = $argv[2] ?? '';
        $newPass = $argv[3] ?? '';
        if (empty($target) || empty($newPass)) {
            echo "Usage: php manage_users.php set-password <email_or_mobile> <new_password>\n";
            exit(1);
        }
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ? OR mobile_number = ?");
        $stmt->execute([$hash, $target, $target]);
        if ($stmt->rowCount() > 0) {
            echo "✅ Password successfully updated for '$target'.\n";
        } else {
            echo "⚠️ No user found with identifier '$target'.\n";
        }
        break;

    case 'create':
        $name = $argv[2] ?? '';
        $email = $argv[3] ?? '';
        $mobile = $argv[4] ?? '';
        $password = $argv[5] ?? '';
        $role = $argv[6] ?? 'supervisor';
        $pin = $argv[7] ?? '1234';
        $unit = $argv[8] ?? 'TGK-1';

        if (empty($name) || empty($email) || empty($password)) {
            echo "Usage: php manage_users.php create <full_name> <email> <mobile> <password> [role] [supervisor_pin] [unit_id]\n";
            echo "Example: php manage_users.php create \"Kiosk Gate 1\" \"kiosk1@troogood.com\" \"9876500001\" \"tgpass123\" \"supervisor\" \"1234\" \"TGK-1\"\n";
            exit(1);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, mobile_number, password_hash, role, supervisor_pin, unit_id, status, created_at, updated_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())");
        try {
            $stmt->execute([$name, $email, $mobile, $hash, $role, $pin, $unit]);
            echo "✅ User '$name' ($email) created successfully! ID: " . $pdo->lastInsertId() . "\n";
        } catch (PDOException $e) {
            echo "❌ Error creating user: " . $e->getMessage() . "\n";
        }
        break;

    case 'release-device':
        $target = $argv[2] ?? '';
        if (empty($target)) {
            echo "Usage: php manage_users.php release-device <email_or_mobile>\n";
            exit(1);
        }
        $stmt = $pdo->prepare("UPDATE users SET bound_device_id = NULL, updated_at = NOW() WHERE email = ? OR mobile_number = ?");
        $stmt->execute([$target, $target]);
        echo "✅ Released device hardware lock for '$target'. Account can now be bound to a new device.\n";
        break;

    default:
        echo "TrooGood HRMS User Management CLI\n";
        echo "Commands:\n";
        echo "  php manage_users.php list                                         - View all users and bound device IDs\n";
        echo "  php manage_users.php devices                                      - View registered kiosk tablet devices\n";
        echo "  php manage_users.php set-password <email/mobile> <password>        - Change a user password\n";
        echo "  php manage_users.php create <name> <email> <mobile> <pass> [role] [pin] [unit] - Add new user\n";
        echo "  php manage_users.php release-device <email/mobile>                - Unbind device lock for a user\n";
        break;
}
