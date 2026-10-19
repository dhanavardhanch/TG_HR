<?php
/**
 * MasterController - Comprehensive Management for Departments, Designations, Contractors, Reporting Managers & Banks
 * Supports REST APIs for Kiosk Mobile Sync, Portal Search/CRUD, and CSV/Excel Upload & Download.
 */

require_once __DIR__ . '/../../config/db.php';

class MasterController {

    /**
     * Unified payload for Flutter Universal Registration Screen
     * GET /api/masters/registration-masters
     */
    public static function getRegistrationMasters() {
        $pdo = getDbConnection();
        if (!$pdo) {
            jsonError("Database connection unavailable", 500);
        }

        // Active Departments
        $depts = $pdo->query("SELECT id, department_name AS name, department_code AS code, unit_id 
                              FROM departments 
                              WHERE is_active = 1 AND (deleted_at IS NULL OR deleted_at = '')
                              ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Active Designations with Parent Department
        $designations = $pdo->query("SELECT d.id, d.department_id, dep.department_name, d.designation_name AS name, d.designation_code AS code 
                                     FROM designations d 
                                     LEFT JOIN departments dep ON d.department_id = dep.id 
                                     WHERE d.is_active = 1 
                                     ORDER BY dep.department_name ASC, d.designation_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Active Contractors
        $contractors = $pdo->query("SELECT id, contractor_name AS name, contact_person, phone, email 
                                    FROM contractors 
                                    WHERE is_active = 1 
                                    ORDER BY contractor_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Active Reporting Managers
        $managers = $pdo->query("SELECT rm.id, rm.manager_name AS name, rm.department_id, dep.department_name, rm.phone, rm.email 
                                 FROM reporting_managers rm 
                                 LEFT JOIN departments dep ON rm.department_id = dep.id 
                                 WHERE rm.is_active = 1 
                                 ORDER BY rm.manager_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Active Banks
        $banks = $pdo->query("SELECT id, bank_name AS name, ifsc_prefix 
                              FROM banks 
                              WHERE is_active = 1 
                              ORDER BY bank_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess("Registration masters retrieved", [
            'departments' => $depts,
            'designations' => $designations,
            'contractors' => $contractors,
            'reporting_managers' => $managers,
            'banks' => $banks,
        ]);
    }

    /**
     * Web Portal List & Search for any master type
     * GET /api/masters/list?type=...&q=...&dept_id=...
     */
    public static function getMasterList() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database connection unavailable", 500);

        $type = trim($_GET['type'] ?? 'departments');
        $q = trim($_GET['q'] ?? '');
        $deptId = isset($_GET['dept_id']) && $_GET['dept_id'] !== '' ? (int)$_GET['dept_id'] : null;

        switch ($type) {
            case 'departments':
                $sql = "SELECT d.*, 
                               (SELECT COUNT(*) FROM designations des WHERE des.department_id = d.id) AS designation_count
                        FROM departments d 
                        WHERE (d.deleted_at IS NULL OR d.deleted_at = '')";
                $params = [];
                if (!empty($q)) {
                    $sql .= " AND (d.department_name LIKE ? OR d.department_code LIKE ?)";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                }
                $sql .= " ORDER BY d.id ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'designations':
                $sql = "SELECT des.*, dep.department_name 
                        FROM designations des 
                        LEFT JOIN departments dep ON des.department_id = dep.id 
                        WHERE 1=1";
                $params = [];
                if (!empty($q)) {
                    $sql .= " AND (des.designation_name LIKE ? OR des.designation_code LIKE ? OR dep.department_name LIKE ?)";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                }
                if ($deptId !== null) {
                    $sql .= " AND des.department_id = ?";
                    $params[] = $deptId;
                }
                $sql .= " ORDER BY dep.department_name ASC, des.designation_name ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'contractors':
                $sql = "SELECT * FROM contractors WHERE 1=1";
                $params = [];
                if (!empty($q)) {
                    $sql .= " AND (contractor_name LIKE ? OR contact_person LIKE ? OR phone LIKE ?)";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                }
                $sql .= " ORDER BY contractor_name ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'reporting_managers':
                $sql = "SELECT rm.*, dep.department_name 
                        FROM reporting_managers rm 
                        LEFT JOIN departments dep ON rm.department_id = dep.id 
                        WHERE 1=1";
                $params = [];
                if (!empty($q)) {
                    $sql .= " AND (rm.manager_name LIKE ? OR rm.phone LIKE ? OR dep.department_name LIKE ?)";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                }
                if ($deptId !== null) {
                    $sql .= " AND rm.department_id = ?";
                    $params[] = $deptId;
                }
                $sql .= " ORDER BY rm.manager_name ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'banks':
                $sql = "SELECT * FROM banks WHERE 1=1";
                $params = [];
                if (!empty($q)) {
                    $sql .= " AND (bank_name LIKE ? OR ifsc_prefix LIKE ?)";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                }
                $sql .= " ORDER BY bank_name ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            default:
                jsonError("Invalid master type: $type", 400);
        }

        // Return all departments for filtering designations
        $departments = $pdo->query("SELECT id, department_name FROM departments WHERE (deleted_at IS NULL OR deleted_at = '') ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // Return all active factory units for unit scoping
        $units = $pdo->query("SELECT id, unit_name, unit_code, city, state FROM units WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess("Master list retrieved", [
            'type' => $type,
            'items' => $items,
            'departments' => $departments,
            'units' => $units
        ]);
    }

    /**
     * Create or Update a Master record
     * POST /api/masters/save
     */
    public static function saveMasterItem() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $type = trim($input['type'] ?? '');
        $id = !empty($input['id']) ? (int)$input['id'] : null;

        if (empty($type)) jsonError("Master type is required", 400);

        switch ($type) {
            case 'departments':
                $name = trim($input['department_name'] ?? '');
                $code = trim($input['department_code'] ?? '');
                $unit = trim($input['unit_id'] ?? '') ?: null;
                $active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

                if (empty($name)) jsonError("Department name is required", 400);

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE departments SET department_name = ?, department_code = ?, unit_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $code, $unit, $active, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO departments (department_name, department_code, unit_id, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$name, $code, $unit, $active]);
                    $id = $pdo->lastInsertId();
                }
                break;

            case 'designations':
                $name = trim($input['designation_name'] ?? '');
                $code = trim($input['designation_code'] ?? '');
                $deptId = !empty($input['department_id']) ? (int)$input['department_id'] : null;
                $active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

                if (empty($name)) jsonError("Designation name is required", 400);
                if (empty($deptId)) jsonError("Please select a Department for this designation", 400);

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE designations SET designation_name = ?, designation_code = ?, department_id = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $code, $deptId, $active, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO designations (designation_name, designation_code, department_id, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$name, $code, $deptId, $active]);
                    $id = $pdo->lastInsertId();
                }
                break;

            case 'contractors':
                $name = trim($input['contractor_name'] ?? '');
                $contact = trim($input['contact_person'] ?? '');
                $phone = trim($input['phone'] ?? '');
                $email = trim($input['email'] ?? '');
                $active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

                if (empty($name)) jsonError("Contractor agency name is required", 400);

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE contractors SET contractor_name = ?, contact_person = ?, phone = ?, email = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $contact, $phone, $email, $active, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO contractors (contractor_name, contact_person, phone, email, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$name, $contact, $phone, $email, $active]);
                    $id = $pdo->lastInsertId();
                }
                break;

            case 'reporting_managers':
                $name = trim($input['manager_name'] ?? '');
                $deptId = !empty($input['department_id']) ? (int)$input['department_id'] : null;
                $phone = trim($input['phone'] ?? '');
                $email = trim($input['email'] ?? '');
                $active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

                if (empty($name)) jsonError("Reporting manager name is required", 400);

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE reporting_managers SET manager_name = ?, department_id = ?, phone = ?, email = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $deptId, $phone, $email, $active, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO reporting_managers (manager_name, department_id, phone, email, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$name, $deptId, $phone, $email, $active]);
                    $id = $pdo->lastInsertId();
                }
                break;

            case 'banks':
                $name = trim($input['bank_name'] ?? '');
                $ifsc = trim($input['ifsc_prefix'] ?? '');
                $active = isset($input['is_active']) ? (int)$input['is_active'] : 1;

                if (empty($name)) jsonError("Bank name is required", 400);

                if ($id) {
                    $stmt = $pdo->prepare("UPDATE banks SET bank_name = ?, ifsc_prefix = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $ifsc, $active, $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO banks (bank_name, ifsc_prefix, is_active, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$name, $ifsc, $active]);
                    $id = $pdo->lastInsertId();
                }
                break;

            default:
                jsonError("Invalid master type: $type", 400);
        }

        jsonSuccess("Record saved successfully", ['id' => $id]);
    }

    /**
     * Delete or Toggle Active Status
     * POST /api/masters/delete
     */
    public static function deleteMasterItem() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $type = trim($input['type'] ?? '');
        $id = (int)($input['id'] ?? 0);

        if (empty($type) || empty($id)) jsonError("Type and ID are required", 400);

        $tableMap = [
            'departments' => 'departments',
            'designations' => 'designations',
            'contractors' => 'contractors',
            'reporting_managers' => 'reporting_managers',
            'banks' => 'banks',
        ];

        if (!isset($tableMap[$type])) jsonError("Invalid master type", 400);
        $tbl = $tableMap[$type];

        // Soft delete if column exists, otherwise hard delete
        try {
            $stmt = $pdo->prepare("DELETE FROM `$tbl` WHERE id = ?");
            $stmt->execute([$id]);
            jsonSuccess("Record deleted successfully");
        } catch (\PDOException $e) {
            // If foreign key constraint blocks delete, deactivate instead
            $stmt = $pdo->prepare("UPDATE `$tbl` SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            jsonSuccess("Record deactivated as it is referenced in employee history");
        }
    }

    /**
     * Export Master Data to CSV/Excel format
     * GET /api/masters/export?type=...
     */
    public static function exportCsv() {
        $pdo = getDbConnection();
        if (!$pdo) die("DB unavailable");

        $type = trim($_GET['type'] ?? 'departments');
        $filename = "TrooGood_" . ucfirst($type) . "_Master_" . date('Ymd_His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility

        switch ($type) {
            case 'departments':
                fputcsv($out, ['ID', 'Department Name', 'Department Code', 'Unit ID', 'Status']);
                $rows = $pdo->query("SELECT id, department_name, department_code, unit_id, is_active FROM departments WHERE (deleted_at IS NULL OR deleted_at = '') ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['id'], $r['department_name'], $r['department_code'], $r['unit_id'] ?: 'ALL', $r['is_active'] ? 'Active' : 'Inactive']);
                }
                break;

            case 'designations':
                fputcsv($out, ['ID', 'Department ID', 'Department Name', 'Designation Name', 'Designation Code', 'Status']);
                $rows = $pdo->query("SELECT des.id, des.department_id, dep.department_name, des.designation_name, des.designation_code, des.is_active 
                                     FROM designations des 
                                     LEFT JOIN departments dep ON des.department_id = dep.id 
                                     ORDER BY dep.department_name ASC, des.designation_name ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['id'], $r['department_id'], $r['department_name'], $r['designation_name'], $r['designation_code'], $r['is_active'] ? 'Active' : 'Inactive']);
                }
                break;

            case 'contractors':
                fputcsv($out, ['ID', 'Contractor Agency Name', 'Contact Person', 'Phone Number', 'Email Address', 'Status']);
                $rows = $pdo->query("SELECT id, contractor_name, contact_person, phone, email, is_active FROM contractors ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['id'], $r['contractor_name'], $r['contact_person'], $r['phone'], $r['email'], $r['is_active'] ? 'Active' : 'Inactive']);
                }
                break;

            case 'reporting_managers':
                fputcsv($out, ['ID', 'Manager Name', 'Department ID', 'Department Name', 'Phone Number', 'Email Address', 'Status']);
                $rows = $pdo->query("SELECT rm.id, rm.manager_name, rm.department_id, dep.department_name, rm.phone, rm.email, rm.is_active 
                                     FROM reporting_managers rm 
                                     LEFT JOIN departments dep ON rm.department_id = dep.id 
                                     ORDER BY rm.id ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['id'], $r['manager_name'], $r['department_id'], $r['department_name'], $r['phone'], $r['email'], $r['is_active'] ? 'Active' : 'Inactive']);
                }
                break;

            case 'banks':
                fputcsv($out, ['ID', 'Bank Name', 'IFSC Prefix', 'Status']);
                $rows = $pdo->query("SELECT id, bank_name, ifsc_prefix, is_active FROM banks ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    fputcsv($out, [$r['id'], $r['bank_name'], $r['ifsc_prefix'], $r['is_active'] ? 'Active' : 'Inactive']);
                }
                break;
        }

        fclose($out);
        exit;
    }

    /**
     * Import CSV File to bulk upload or update masters
     * POST /api/masters/import
     */
    public static function importCsv() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $type = trim($_POST['type'] ?? '');
        if (empty($type)) jsonError("Master type is required", 400);

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            jsonError("Please choose a valid CSV file to upload", 400);
        }

        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) jsonError("Could not open uploaded file", 400);

        // Discard BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (!$headers) jsonError("CSV file appears to be empty", 400);

        $inserted = 0;
        $updated = 0;

        $deptMap = $pdo->query("SELECT LOWER(department_name), id FROM departments")->fetchAll(PDO::FETCH_KEY_PAIR);

        while (($row = fgetcsv($handle)) !== false) {
            if (empty(array_filter($row))) continue;

            switch ($type) {
                case 'departments':
                    // Expected columns: ID, Department Name, Department Code, Unit ID, Status
                    $name = trim($row[1] ?? $row[0] ?? '');
                    $code = trim($row[2] ?? $row[1] ?? '');
                    $unit = trim($row[3] ?? '') ?: null;
                    if (empty($name)) continue 2;

                    $check = $pdo->prepare("SELECT id FROM departments WHERE department_name = ? LIMIT 1");
                    $check->execute([$name]);
                    $existing = $check->fetchColumn();

                    if ($existing) {
                        $pdo->prepare("UPDATE departments SET department_code = ?, unit_id = ?, is_active = 1, updated_at = NOW() WHERE id = ?")
                            ->execute([$code, $unit, $existing]);
                        $updated++;
                    } else {
                        $pdo->prepare("INSERT INTO departments (department_name, department_code, unit_id, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())")
                            ->execute([$name, $code, $unit]);
                        $inserted++;
                    }
                    break;

                case 'designations':
                    // Expected columns: ID, Dept ID or Dept Name, Designation Name, Code, Status
                    // Or Dept Name, Designation Name, Designation Code
                    $deptNameOrId = trim($row[2] ?? $row[1] ?? '');
                    $desName = trim($row[3] ?? $row[2] ?? $row[0] ?? '');
                    $desCode = trim($row[4] ?? $row[3] ?? $row[1] ?? '');

                    if (empty($desName)) continue 2;

                    // Resolve department_id
                    $deptId = null;
                    if (is_numeric($deptNameOrId)) {
                        $deptId = (int)$deptNameOrId;
                    } elseif (!empty($deptNameOrId)) {
                        $deptId = $deptMap[strtolower($deptNameOrId)] ?? null;
                    }

                    $check = $pdo->prepare("SELECT id FROM designations WHERE designation_name = ? AND (department_id = ? OR ? IS NULL) LIMIT 1");
                    $check->execute([$desName, $deptId, $deptId]);
                    $existing = $check->fetchColumn();

                    if ($existing) {
                        $pdo->prepare("UPDATE designations SET designation_code = ?, department_id = COALESCE(?, department_id), is_active = 1, updated_at = NOW() WHERE id = ?")
                            ->execute([$desCode, $deptId, $existing]);
                        $updated++;
                    } else {
                        $pdo->prepare("INSERT INTO designations (department_id, designation_name, designation_code, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, NOW(), NOW())")
                            ->execute([$deptId, $desName, $desCode]);
                        $inserted++;
                    }
                    break;

                case 'contractors':
                    // Expected: Name, Contact Person, Phone, Email
                    $name = trim($row[1] ?? $row[0] ?? '');
                    $contact = trim($row[2] ?? $row[1] ?? '');
                    $phone = trim($row[3] ?? $row[2] ?? '');
                    $email = trim($row[4] ?? $row[3] ?? '');
                    if (empty($name)) continue 2;

                    $check = $pdo->prepare("SELECT id FROM contractors WHERE contractor_name = ? LIMIT 1");
                    $check->execute([$name]);
                    $existing = $check->fetchColumn();

                    if ($existing) {
                        $pdo->prepare("UPDATE contractors SET contact_person = ?, phone = ?, email = ?, is_active = 1, updated_at = NOW() WHERE id = ?")
                            ->execute([$contact, $phone, $email, $existing]);
                        $updated++;
                    } else {
                        $pdo->prepare("INSERT INTO contractors (contractor_name, contact_person, phone, email, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())")
                            ->execute([$name, $contact, $phone, $email]);
                        $inserted++;
                    }
                    break;

                case 'reporting_managers':
                    $name = trim($row[1] ?? $row[0] ?? '');
                    $deptNameOrId = trim($row[3] ?? $row[2] ?? '');
                    $phone = trim($row[4] ?? $row[3] ?? '');
                    $email = trim($row[5] ?? $row[4] ?? '');
                    if (empty($name)) continue 2;

                    $deptId = null;
                    if (is_numeric($deptNameOrId)) {
                        $deptId = (int)$deptNameOrId;
                    } elseif (!empty($deptNameOrId)) {
                        $deptId = $deptMap[strtolower($deptNameOrId)] ?? null;
                    }

                    $check = $pdo->prepare("SELECT id FROM reporting_managers WHERE manager_name = ? LIMIT 1");
                    $check->execute([$name]);
                    $existing = $check->fetchColumn();

                    if ($existing) {
                        $pdo->prepare("UPDATE reporting_managers SET department_id = COALESCE(?, department_id), phone = ?, email = ?, is_active = 1, updated_at = NOW() WHERE id = ?")
                            ->execute([$deptId, $phone, $email, $existing]);
                        $updated++;
                    } else {
                        $pdo->prepare("INSERT INTO reporting_managers (manager_name, department_id, phone, email, is_active, created_at, updated_at) VALUES (?, ?, ?, ?, 1, NOW(), NOW())")
                            ->execute([$name, $deptId, $phone, $email]);
                        $inserted++;
                    }
                    break;

                case 'banks':
                    $name = trim($row[1] ?? $row[0] ?? '');
                    $ifsc = trim($row[2] ?? $row[1] ?? '');
                    if (empty($name)) continue 2;

                    $check = $pdo->prepare("SELECT id FROM banks WHERE bank_name = ? LIMIT 1");
                    $check->execute([$name]);
                    $existing = $check->fetchColumn();

                    if ($existing) {
                        $pdo->prepare("UPDATE banks SET ifsc_prefix = ?, is_active = 1, updated_at = NOW() WHERE id = ?")
                            ->execute([$ifsc, $existing]);
                        $updated++;
                    } else {
                        $pdo->prepare("INSERT INTO banks (bank_name, ifsc_prefix, is_active, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())")
                            ->execute([$name, $ifsc]);
                        $inserted++;
                    }
                    break;
            }
        }

        fclose($handle);

        jsonSuccess("CSV uploaded and processed successfully", [
            'inserted' => $inserted,
            'updated' => $updated,
            'total_processed' => $inserted + $updated
        ]);
    }
}
