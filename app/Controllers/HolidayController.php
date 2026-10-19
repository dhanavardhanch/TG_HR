<?php
/**
 * HolidayController - Holiday Calendar Management & Bulk Year Upload
 */

require_once __DIR__ . '/../../config/db.php';

class HolidayController {

    public static function listHolidays() {
        $pdo = getDbConnection();
        $year = (int)($_GET['year'] ?? date('Y'));
        $unit = $_GET['unit'] ?? 'ALL';

        $sql = "SELECT id, holiday_date, holiday_name, holiday_type, year, unit_id, description, is_active,
                       DATE_FORMAT(holiday_date, '%W') as day_of_week
                FROM holiday_calendar 
                WHERE year = ? AND is_active = 1";
        $params = [$year];

        if ($unit !== 'ALL' && !empty($unit)) {
            $sql .= " AND (unit_id = 'ALL' OR unit_id = ?)";
            $params[] = $unit;
        }
        $sql .= " ORDER BY holiday_date ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate statistics
        $national = 0; $festival = 0; $company = 0;
        foreach ($holidays as $h) {
            if ($h['holiday_type'] === 'National') $national++;
            elseif ($h['holiday_type'] === 'Company') $company++;
            else $festival++;
        }

        jsonSuccess("Holidays loaded for year $year", [
            'year' => $year,
            'count' => count($holidays),
            'stats' => [
                'total' => count($holidays),
                'national' => $national,
                'festival' => $festival,
                'company' => $company
            ],
            'holidays' => $holidays
        ]);
    }

    public static function saveHoliday() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);
        $date = trim($input['holiday_date'] ?? '');
        $name = trim($input['holiday_name'] ?? '');
        $type = trim($input['holiday_type'] ?? 'Festival');
        $unit = trim($input['unit_id'] ?? 'ALL');
        $desc = trim($input['description'] ?? '');

        if (empty($date) || empty($name)) {
            jsonError("Holiday date and name are required", 422);
        }

        $year = (int)date('Y', strtotime($date));
        $pdo = getDbConnection();

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE holiday_calendar 
                                   SET holiday_date = ?, holiday_name = ?, holiday_type = ?, year = ?, unit_id = ?, description = ?, updated_at = NOW() 
                                   WHERE id = ?");
            $stmt->execute([$date, $name, $type, $year, $unit, $desc, $id]);
            logAudit('WEB_PORTAL', 'HR Admin', 'UPDATE_HOLIDAY', 'holiday_calendar', $id, ['name' => $name, 'date' => $date]);
            jsonSuccess("Holiday updated successfully", ['id' => $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO holiday_calendar (holiday_date, holiday_name, holiday_type, year, unit_id, description) 
                                   VALUES (?, ?, ?, ?, ?, ?)
                                   ON DUPLICATE KEY UPDATE holiday_name = VALUES(holiday_name), holiday_type = VALUES(holiday_type), description = VALUES(description)");
            $stmt->execute([$date, $name, $type, $year, $unit, $desc]);
            $newId = (int)$pdo->lastInsertId();
            logAudit('WEB_PORTAL', 'HR Admin', 'ADD_HOLIDAY', 'holiday_calendar', $newId, ['name' => $name, 'date' => $date]);
            jsonSuccess("Holiday added successfully", ['id' => $newId]);
        }
    }

    public static function deleteHoliday() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonError("Holiday ID is required", 422);

        $pdo = getDbConnection();
        $stmt = $pdo->prepare("DELETE FROM holiday_calendar WHERE id = ?");
        $stmt->execute([$id]);

        logAudit('WEB_PORTAL', 'HR Admin', 'DELETE_HOLIDAY', 'holiday_calendar', $id, []);
        jsonSuccess("Holiday deleted successfully");
    }

    public static function downloadTemplate() {
        $year = (int)($_GET['year'] ?? date('Y'));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="troogood_holiday_template_' . $year . '.csv"');

        $out = fopen('php://output', 'w');
        // CSV Header
        fputcsv($out, ['holiday_date', 'holiday_name', 'holiday_type', 'unit_id', 'description']);

        // Sample rows
        fputcsv($out, ["$year-01-01", "New Year's Day", "Company", "ALL", "Annual company holiday"]);
        fputcsv($out, ["$year-01-26", "Republic Day", "National", "ALL", "National Flag Hoisting"]);
        fputcsv($out, ["$year-05-01", "May Day / Workers Day", "National", "ALL", "International Labour Day"]);
        fputcsv($out, ["$year-08-15", "Independence Day", "National", "ALL", "National Independence Day"]);
        fputcsv($out, ["$year-10-02", "Gandhi Jayanti", "National", "ALL", "Birth of Mahatma Gandhi"]);
        fputcsv($out, ["$year-12-25", "Christmas Day", "Festival", "ALL", "Christmas Celebration"]);

        fclose($out);
        exit();
    }

    public static function uploadHolidays() {
        $pdo = getDbConnection();
        $year = (int)($_POST['year'] ?? date('Y'));
        $replaceExisting = !empty($_POST['replace_existing']);

        $csvContent = '';
        if (!empty($_FILES['file']['tmp_name'])) {
            $csvContent = file_get_contents($_FILES['file']['tmp_name']);
        } elseif (!empty($_POST['csv_content'])) {
            $csvContent = $_POST['csv_content'];
        }

        if (empty($csvContent)) {
            jsonError("No CSV file uploaded or content is empty", 422);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($csvContent));
        if (count($lines) < 2) {
            jsonError("CSV must contain a header row and at least one holiday row", 422);
        }

        $header = str_getcsv(array_shift($lines));
        $headerMap = [];
        foreach ($header as $idx => $col) {
            $colClean = strtolower(trim(str_replace([' ', '_', '-'], '', $col)));
            if (strpos($colClean, 'date') !== false) $headerMap['date'] = $idx;
            elseif (strpos($colClean, 'name') !== false) $headerMap['name'] = $idx;
            elseif (strpos($colClean, 'type') !== false) $headerMap['type'] = $idx;
            elseif (strpos($colClean, 'unit') !== false) $headerMap['unit'] = $idx;
            elseif (strpos($colClean, 'desc') !== false) $headerMap['desc'] = $idx;
        }

        if (!isset($headerMap['date']) || !isset($headerMap['name'])) {
            jsonError("CSV missing required columns: holiday_date and holiday_name", 422);
        }

        if ($replaceExisting) {
            $pdo->prepare("DELETE FROM holiday_calendar WHERE year = ?")->execute([$year]);
        }

        $stmt = $pdo->prepare("INSERT INTO holiday_calendar (holiday_date, holiday_name, holiday_type, year, unit_id, description) 
                               VALUES (?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE holiday_name = VALUES(holiday_name), holiday_type = VALUES(holiday_type), description = VALUES(description)");

        $inserted = 0;
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            $row = str_getcsv($line);
            if (empty($row) || !isset($row[$headerMap['date']])) continue;

            $rawDate = trim($row[$headerMap['date']]);
            $dateParsed = date('Y-m-d', strtotime($rawDate));
            if (!$dateParsed || $dateParsed === '1970-01-01') continue;

            $hName = trim($row[$headerMap['name']]);
            if (empty($hName)) continue;

            $hType = isset($headerMap['type']) && !empty($row[$headerMap['type']]) ? trim($row[$headerMap['type']]) : 'Festival';
            $hUnit = isset($headerMap['unit']) && !empty($row[$headerMap['unit']]) ? trim($row[$headerMap['unit']]) : 'ALL';
            $hDesc = isset($headerMap['desc']) && !empty($row[$headerMap['desc']]) ? trim($row[$headerMap['desc']]) : '';
            $rowYear = (int)date('Y', strtotime($dateParsed));

            $stmt->execute([$dateParsed, $hName, $hType, $rowYear, $hUnit, $hDesc]);
            $inserted++;
        }

        logAudit('WEB_PORTAL', 'HR Admin', 'BULK_UPLOAD_HOLIDAYS', 'holiday_calendar', $inserted, ['year' => $year, 'count' => $inserted]);

        jsonSuccess("Successfully uploaded $inserted holiday(s) for year $year", [
            'inserted_count' => $inserted,
            'year' => $year
        ]);
    }
}
