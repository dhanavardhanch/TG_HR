<?php
/**
 * PeopleController - People Master (web panel)
 *  - Full employee directory merged from workers + universal_registrations
 *  - Profile view with documents, biometrics, login assignments & transfer history
 *  - Bulk login (attendance sync) assignment and unit transfer
 *  - ZIP export: Profile PDF + photo + Aadhaar + bank proof + PF exemption + embeddings JSON
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Helpers/SimplePdf.php';
require_once __DIR__ . '/../Helpers/MailHelper.php';

class PeopleController {

    private static $docTypes = [
        'photo'          => ['col' => 'photo_front_url',     'label' => 'Photograph',               'file' => 'Photo'],
        'aadhaar_front'  => ['col' => 'aadhaar_front_url',   'label' => 'Aadhaar (Front)',          'file' => 'Aadhaar_Front'],
        'aadhaar_back'   => ['col' => 'aadhaar_back_url',    'label' => 'Aadhaar (Back)',           'file' => 'Aadhaar_Back'],
        'bank_proof'     => ['col' => 'cheque_passbook_url', 'label' => 'Cancelled Cheque / Passbook', 'file' => 'Bank_Cheque_Passbook'],
        'pf_exemption'   => ['col' => 'pf_exemption_url',    'label' => 'PF Exemption Form',        'file' => 'PF_Exemption'],
    ];

    // ------------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------------
    private static function input() {
        $in = json_decode(file_get_contents('php://input'), true);
        return is_array($in) && !empty($in) ? $in : $_POST;
    }

    private static function intList($v) {
        if (is_string($v)) $v = explode(',', $v);
        return array_values(array_unique(array_filter(array_map('intval', (array)$v))));
    }

    /** Resolves a stored document URL to [localPath|null, publicUrl|null]. */
    private static function resolveDoc($url) {
        $url = trim((string)$url);
        if ($url === '') return [null, null];
        $public = realpath(__DIR__ . '/../../public');
        $rel = $url;
        if (preg_match('#^https?://#i', $url)) {
            $pos = stripos($url, '/uploads/');
            if ($pos === false) return [null, $url];
            $rel = substr($url, $pos + 1);
        }
        $rel = ltrim(str_replace('\\', '/', $rel), '/');
        if (strpos($rel, 'uploads/') !== 0) return [null, null]; // device-local path, never uploaded
        $path = $public . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        return is_file($path) ? [$path, '/' . $rel] : [null, null];
    }

    public static function parseDateForDb($val) {
        $val = trim((string)$val);
        if ($val === '' || $val === '0000-00-00' || $val === 'null') return null;
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $val, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/', $val, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
        $ts = strtotime($val);
        return ($ts && $ts > 0) ? date('Y-m-d', $ts) : null;
    }

    private static function baseSelect() {
        return "SELECT w.id, w.worker_code, w.full_name, w.unit_id, w.department, w.designation, w.monthly_salary,
                       w.category, w.contractor_agency, w.mobile_number, w.alternate_mobile, w.email_address,
                       w.date_of_birth, w.gender, w.blood_group, w.shift, w.approval_status, w.approved_by,
                       w.approved_at, w.rejection_reason, w.is_active, w.embedding_version,
                       w.photo_front_url, w.aadhaar_front_url, w.aadhaar_back_url, w.cheque_passbook_url,
                       w.pf_exemption_url, w.attendance_app_mode, w.app_email, w.created_at, w.updated_at,
                       r.date_of_joining, r.designation_title, r.monthly_salary AS reg_salary, r.reporting_to,
                       r.engagement_type, r.contractor_agency AS reg_contractor, r.father_or_husband_name,
                       r.marital_status, r.present_address, r.permanent_address, r.emergency_name,
                       r.emergency_relationship, r.emergency_mobile, r.aadhaar_number, r.pan_card_number,
                       r.bank_name_branch, r.bank_account_number, r.ifsc_code, r.has_worked_previously,
                       r.previous_employer, r.last_working_date, r.has_pf_account, r.uan_number,
                       r.eps_pension_status, r.esic_number, r.health_insurance_number, r.opt_out_pf_esi,
                       r.pf_exemption_url AS reg_pf_exemption_url, r.nominee_full_name, r.nominee_relationship,
                       r.nominee_dob, r.nominee_share_percentage, r.family_members_json, r.pdf_declaration_url,
                       r.signature_typed_name, r.signature_place_city, r.signature_timestamp,
                       (SELECT COUNT(*) FROM face_embeddings fe WHERE fe.worker_id = w.id AND fe.deleted_at IS NULL) AS embedding_count
                FROM workers w
                LEFT JOIN universal_registrations r ON r.worker_id = w.id AND r.deleted_at IS NULL";
    }

    /** Normalizes a joined row into the public People record. */
    private static function shape($w) {
        $designation = $w['designation'] ?: ($w['designation_title'] ?: $w['department']);
        $salary = $w['monthly_salary'] !== null ? $w['monthly_salary'] : $w['reg_salary'];
        $pfUrl = $w['pf_exemption_url'] ?: $w['reg_pf_exemption_url'];
        $category = $w['category'] ?: $w['engagement_type'];
        $contractor = $w['contractor_agency'] ?: $w['reg_contractor'];

        $dob = ($w['date_of_birth'] && $w['date_of_birth'] !== '0000-00-00') ? $w['date_of_birth'] : null;
        $doj = ($w['date_of_joining'] && $w['date_of_joining'] !== '0000-00-00') ? $w['date_of_joining'] : null;
        $lastWorkDate = ($w['last_working_date'] && $w['last_working_date'] !== '0000-00-00') ? $w['last_working_date'] : null;
        $nomineeDob = ($w['nominee_dob'] && $w['nominee_dob'] !== '0000-00-00') ? $w['nominee_dob'] : null;

        return [
            'id' => (int)$w['id'],
            'worker_code' => $w['worker_code'],
            'full_name' => $w['full_name'],
            'gender' => $w['gender'],
            'unit_id' => $w['unit_id'],
            'department' => $w['department'],
            'designation' => $designation,
            'monthly_salary' => $salary !== null ? (float)$salary : null,
            'mobile_number' => $w['mobile_number'],
            'alternate_mobile' => $w['alternate_mobile'],
            'email_address' => $w['email_address'],
            'attendance_app_mode' => $w['attendance_app_mode'] ?? 'kiosk',
            'app_email' => $w['app_email'] ?? $w['email_address'],
            'date_of_birth' => $dob,
            'blood_group' => $w['blood_group'],
            'date_of_joining' => $doj,
            'shift' => $w['shift'],
            'employment_type' => $category,
            'is_contract' => stripos((string)$category, 'contract') !== false,
            'contractor_name' => $contractor,
            'reporting_to' => $w['reporting_to'],
            'aadhaar_number' => $w['aadhaar_number'],
            'pan_number' => $w['pan_card_number'],
            'uan_number' => $w['uan_number'],
            'esi_number' => $w['esic_number'],
            'health_insurance_number' => $w['health_insurance_number'],
            'bank_name_branch' => $w['bank_name_branch'],
            'account_number' => $w['bank_account_number'],
            'ifsc_code' => $w['ifsc_code'],
            'has_pf_account' => (bool)$w['has_pf_account'],
            'eps_pension_status' => $w['eps_pension_status'],
            'opt_out_pf_esi' => (bool)$w['opt_out_pf_esi'],
            'father_or_husband_name' => $w['father_or_husband_name'],
            'marital_status' => $w['marital_status'],
            'present_address' => $w['present_address'],
            'permanent_address' => $w['permanent_address'],
            'emergency_name' => $w['emergency_name'],
            'emergency_relationship' => $w['emergency_relationship'],
            'emergency_mobile' => $w['emergency_mobile'],
            'has_worked_previously' => (bool)$w['has_worked_previously'],
            'previous_employer' => $w['previous_employer'],
            'last_working_date' => $lastWorkDate,
            'nominee_full_name' => $w['nominee_full_name'],
            'nominee_relationship' => $w['nominee_relationship'],
            'nominee_dob' => $nomineeDob,
            'nominee_share_percentage' => $w['nominee_share_percentage'],
            'family_members' => $w['family_members_json'] ? json_decode($w['family_members_json'], true) : [],
            'signature_typed_name' => $w['signature_typed_name'] ?? null,
            'signature_place_city' => $w['signature_place_city'] ?? null,
            'signature_timestamp' => $w['signature_timestamp'] ?? null,
            'has_form11_declaration' => (!empty($w['signature_typed_name']) || !empty($w['uan_number']) || !empty($w['aadhaar_number'])),
            'approval_status' => $w['approval_status'],
            'approved_by' => $w['approved_by'],
            'approved_at' => $w['approved_at'],
            'rejection_reason' => $w['rejection_reason'],
            'is_active' => (bool)$w['is_active'],
            'embedding_version' => (int)$w['embedding_version'],
            'embedding_count' => (int)$w['embedding_count'],
            'has_registration' => ($doj !== null || !empty($w['aadhaar_number'])),
            'created_at' => $w['created_at'],
            'updated_at' => $w['updated_at'],
            '_docs' => [
                'photo' => $w['photo_front_url'],
                'aadhaar_front' => $w['aadhaar_front_url'],
                'aadhaar_back' => $w['aadhaar_back_url'],
                'bank_proof' => $w['cheque_passbook_url'],
                'pf_exemption' => $pfUrl,
                'form11' => $w['pdf_declaration_url'],
            ],
        ];
    }

    private static function assignmentsFor($pdo, array $ids) {
        $map = [];
        if (empty($ids)) return $map;
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT a.worker_id, u.id AS user_id, u.full_name, u.email, u.unit_id, u.bound_device_id
                             FROM worker_login_assignments a JOIN users u ON u.id = a.user_id
                             WHERE a.deleted_at IS NULL AND a.worker_id IN ($in) ORDER BY u.full_name");
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $map[(int)$r['worker_id']][] = [
                'user_id' => (int)$r['user_id'], 'name' => $r['full_name'], 'email' => $r['email'],
                'unit_id' => $r['unit_id'], 'device_bound' => !empty($r['bound_device_id'])
            ];
        }
        return $map;
    }

    private static function loadPerson($pdo, $id) {
        $st = $pdo->prepare(self::baseSelect() . " WHERE w.id = ? AND w.deleted_at IS NULL LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ? self::shape($row) : null;
    }

    // ------------------------------------------------------------------------
    // GET /api/people
    // ------------------------------------------------------------------------
    public static function listPeople() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $unit = trim($_GET['unit'] ?? 'all');
        $status = trim($_GET['status'] ?? 'all');
        $type = trim($_GET['type'] ?? 'all');
        $q = trim($_GET['q'] ?? '');

        $sql = self::baseSelect() . " WHERE w.deleted_at IS NULL";
        $p = [];
        if ($unit !== '' && $unit !== 'all') { $sql .= " AND w.unit_id = ?"; $p[] = $unit; }
        if ($status !== '' && $status !== 'all') { $sql .= " AND w.approval_status = ?"; $p[] = $status; }
        if ($type === 'contract') { $sql .= " AND (w.category LIKE '%contract%' OR r.engagement_type LIKE '%contract%')"; }
        if ($type === 'rolls') { $sql .= " AND NOT (w.category LIKE '%contract%' OR IFNULL(r.engagement_type,'') LIKE '%contract%')"; }
        if ($q !== '') {
            $sql .= " AND (w.full_name LIKE ? OR w.worker_code LIKE ? OR w.mobile_number LIKE ? OR r.aadhaar_number LIKE ? OR w.contractor_agency LIKE ?)";
            $like = "%$q%";
            array_push($p, $like, $like, $like, $like, $like);
        }
        $sql .= " ORDER BY w.unit_id, w.full_name";
        $st = $pdo->prepare($sql);
        $st->execute($p);
        $rows = array_map([self::class, 'shape'], $st->fetchAll(PDO::FETCH_ASSOC));

        $assign = self::assignmentsFor($pdo, array_column($rows, 'id'));
        foreach ($rows as &$r) {
            $r['assignments'] = $assign[$r['id']] ?? [];
            [, $photoUrl] = self::resolveDoc($r['_docs']['photo']);
            $r['photo_url'] = $photoUrl;
            unset($r['_docs']);
        }
        unset($r);

        $logins = $pdo->query("SELECT id, full_name, email, role, unit_id, bound_device_id, status
                               FROM users WHERE deleted_at IS NULL AND status = 'active'
                               ORDER BY unit_id, full_name")->fetchAll(PDO::FETCH_ASSOC);
        $units = $pdo->query("SELECT id, unit_name FROM units WHERE is_active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

        $stats = ['total' => count($rows), 'approved' => 0, 'pending' => 0, 'contract' => 0, 'assigned' => 0];
        foreach ($rows as $r) {
            if ($r['approval_status'] === 'approved') $stats['approved']++;
            if ($r['approval_status'] === 'pending') $stats['pending']++;
            if ($r['is_contract']) $stats['contract']++;
            if (!empty($r['assignments'])) $stats['assigned']++;
        }

        jsonSuccess("People directory loaded", [
            'people' => $rows,
            'logins' => $logins,
            'units' => $units,
            'stats' => $stats,
        ]);
    }

    // ------------------------------------------------------------------------
    // GET /api/people/{id}
    // ------------------------------------------------------------------------
    public static function getPerson($id) {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);
        $person = self::loadPerson($pdo, (int)$id);
        if (!$person) jsonError("Employee not found", 404);

        $docs = [];
        foreach (self::$docTypes as $key => $meta) {
            $raw = $person['_docs'][$key];
            [$path, $url] = self::resolveDoc($raw);
            $docs[] = [
                'type' => $key,
                'label' => $meta['label'],
                'url' => $url,
                'is_image' => $url ? (bool)preg_match('/\.(jpe?g|png|webp|gif)$/i', $url) : false,
                'status' => $url ? 'available' : ($raw ? 'device_only' : 'missing'),
            ];
        }
        [, $form11] = self::resolveDoc($person['_docs']['form11']);
        unset($person['_docs']);

        $emb = $pdo->prepare("SELECT sample_angle, pipeline_version, embedding_data, updated_at FROM face_embeddings
                              WHERE worker_id = ? AND deleted_at IS NULL ORDER BY id");
        $emb->execute([(int)$id]);
        $samples = [];
        foreach ($emb->fetchAll(PDO::FETCH_ASSOC) as $e) {
            $vec = json_decode($e['embedding_data'], true);
            $samples[] = [
                'angle' => $e['sample_angle'], 'pipeline_version' => (int)$e['pipeline_version'],
                'dimensions' => is_array($vec) ? count($vec) : 0, 'updated_at' => $e['updated_at']
            ];
        }

        $tr = $pdo->prepare("SELECT from_unit, to_unit, reason, transferred_by, effective_date, created_at
                             FROM worker_transfers WHERE worker_id = ? AND deleted_at IS NULL ORDER BY id DESC");
        $tr->execute([(int)$id]);

        $person['assignments'] = self::assignmentsFor($pdo, [(int)$id])[(int)$id] ?? [];
        $person['documents'] = $docs;
        $person['form11_url'] = $form11;
        $person['photo_url'] = $docs[0]['url'];
        $person['biometrics'] = ['samples' => $samples, 'model' => 'MobileFaceNet ArcFace 192-d'];
        $person['transfers'] = $tr->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess("Employee profile loaded", $person);
    }

    // ------------------------------------------------------------------------
    // POST /api/people/update
    // ------------------------------------------------------------------------
    public static function updatePerson() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);
        $in = self::input();
        $id = (int)($in['id'] ?? 0);
        if (!$id) jsonError("Employee ID required", 422);

        $workerCols = [
            'worker_code', 'full_name', 'unit_id', 'department', 'designation', 'monthly_salary',
            'mobile_number', 'alternate_mobile', 'email_address', 'date_of_birth',
            'gender', 'blood_group', 'shift', 'category', 'contractor_agency', 'approval_status',
            'attendance_app_mode', 'app_email', 'app_password_hash'
        ];
        $regCols = [
            'employee_code', 'date_of_joining', 'designation_title', 'reporting_to', 'engagement_type', 'contractor_agency',
            'father_or_husband_name', 'marital_status', 'present_address', 'permanent_address',
            'emergency_name', 'emergency_relationship', 'emergency_mobile',
            'aadhaar_number', 'pan_card_number', 'uan_number', 'esic_number',
            'health_insurance_number', 'bank_account_number', 'ifsc_code', 'bank_name_branch',
            'has_pf_account', 'eps_pension_status', 'opt_out_pf_esi',
            'nominee_full_name', 'nominee_relationship', 'nominee_dob', 'nominee_share_percentage'
        ];

        // Map alternate incoming key names
        if (isset($in['employee_code']) && !isset($in['worker_code'])) $in['worker_code'] = $in['employee_code'];
        if (isset($in['worker_code']) && !isset($in['employee_code'])) $in['employee_code'] = $in['worker_code'];
        if (isset($in['category']) && !isset($in['engagement_type'])) $in['engagement_type'] = $in['category'];
        if (isset($in['designation']) && !isset($in['designation_title'])) $in['designation_title'] = $in['designation'];
        if (isset($in['bank_account_number']) && !isset($in['account_number'])) $in['account_number'] = $in['bank_account_number'];
        if (isset($in['pan_card_number']) && !isset($in['pan_number'])) $in['pan_number'] = $in['pan_card_number'];

        // Validate and sync worker_code / employee_code if provided (Optional / Not Mandatory)
        if (isset($in['worker_code']) || isset($in['employee_code'])) {
            $newCode = trim((string)($in['worker_code'] ?? $in['employee_code'] ?? ''));
            if ($newCode === '' || strcasecmp($newCode, 'Not Assigned') === 0 || strcasecmp($newCode, 'null') === 0) {
                $in['worker_code'] = null;
                $in['employee_code'] = null;
                $pdo->prepare("UPDATE universal_registrations SET employee_code = NULL WHERE worker_id = ?")->execute([$id]);
                $pdo->prepare("UPDATE approvals_inbox SET employee_code = 'Not Assigned' WHERE worker_id = ?")->execute([$id]);
            } else {
                $chk = $pdo->prepare("SELECT id, full_name FROM workers WHERE worker_code = ? AND id != ? AND deleted_at IS NULL LIMIT 1");
                $chk->execute([$newCode, $id]);
                $dup = $chk->fetch(PDO::FETCH_ASSOC);
                if ($dup) {
                    jsonError("Employee code '{$newCode}' is already assigned to {$dup['full_name']} (ID #{$dup['id']}).", 409);
                }
                $in['worker_code'] = $newCode;
                $in['employee_code'] = $newCode;

                // Keep universal_registrations and approvals_inbox synchronized
                $pdo->prepare("UPDATE universal_registrations SET employee_code = ? WHERE worker_id = ?")->execute([$newCode, $id]);
                $pdo->prepare("UPDATE approvals_inbox SET employee_code = ? WHERE worker_id = ?")->execute([$newCode, $id]);
            }
        }

        // Parse dates safely to MySQL YYYY-MM-DD format
        if (isset($in['date_of_birth'])) $in['date_of_birth'] = self::parseDateForDb($in['date_of_birth']);
        if (isset($in['date_of_joining'])) $in['date_of_joining'] = self::parseDateForDb($in['date_of_joining']);
        if (isset($in['nominee_dob'])) $in['nominee_dob'] = self::parseDateForDb($in['nominee_dob']);
        if (isset($in['last_working_date'])) $in['last_working_date'] = self::parseDateForDb($in['last_working_date']);

        $prevUnit = null;
        if (!empty($in['unit_id'])) {
            $prevSt = $pdo->prepare("SELECT unit_id FROM workers WHERE id = ?");
            $prevSt->execute([$id]);
            $prevUnit = $prevSt->fetchColumn();
        }

        $set = []; $vals = [];
        foreach ($workerCols as $c) {
            if (array_key_exists($c, $in)) {
                $set[] = "$c = ?";
                $vals[] = ($c === 'monthly_salary' && $in[$c] === '') ? null : $in[$c];
            }
        }
        if ($set) {
            $vals[] = $id;
            $pdo->prepare("UPDATE workers SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id = ?")->execute($vals);
        }

        $rset = []; $rvals = [];
        foreach ($regCols as $c) {
            if (array_key_exists($c, $in)) { $rset[] = "$c = ?"; $rvals[] = $in[$c] === '' ? null : $in[$c]; }
        }
        if (array_key_exists('monthly_salary', $in)) { $rset[] = "monthly_salary = ?"; $rvals[] = $in['monthly_salary'] === '' ? null : $in['monthly_salary']; }
        if ($rset) {
            $exists = $pdo->prepare("SELECT id FROM universal_registrations WHERE worker_id = ?");
            $exists->execute([$id]);
            if ($exists->fetchColumn()) {
                $rvals[] = $id;
                $pdo->prepare("UPDATE universal_registrations SET " . implode(', ', $rset) . ", updated_at = NOW() WHERE worker_id = ?")->execute($rvals);
            }
        }

        if (!empty($in['unit_id'])) {
            if ($prevUnit && $prevUnit !== $in['unit_id']) {
                $eff = trim($in['effective_date'] ?? '') ?: date('Y-m-d');
                $pdo->prepare("INSERT INTO worker_transfers (worker_id, from_unit, to_unit, reason, transferred_by, effective_date, created_at, updated_at)
                                VALUES (?, ?, ?, 'Profile Unit Update', 'WEB-PORTAL', ?, NOW(), NOW())")
                    ->execute([$id, $prevUnit, $in['unit_id'], $eff]);

                // Bump embedding version so kiosks pick up roster change on next sync
                $pdo->prepare("UPDATE workers SET embedding_version = embedding_version + 1, updated_at = NOW() WHERE id = ?")
                    ->execute([$id]);

                // If transfer is effective today or earlier, void today's punches in previous unit so they are marked Absent in old unit
                // When they give attendance in the new unit, attendance will be recorded in the new unit
                if ($eff <= date('Y-m-d')) {
                    $pdo->prepare("UPDATE attendance_punches SET deleted_at = NOW(), updated_at = NOW() 
                                   WHERE worker_id = ? AND DATE(punch_time) = CURDATE() AND deleted_at IS NULL AND (unit_id = ? OR unit_id IS NULL OR unit_id = '')")
                        ->execute([$id, $prevUnit]);
                }
            }
            require_once __DIR__ . '/EnrollmentController.php';
            EnrollmentController::autoMapWorkerToUnitLogins($pdo, $id, $in['unit_id'], 'PROFILE-UPDATE');
        }

        logAudit('WEB-PORTAL', 'admin', 'PEOPLE_UPDATE', 'workers', $id, "Updated employee master fields", null, $in);
        jsonSuccess("Employee profile updated successfully", ['id' => $id]);
    }

    // ------------------------------------------------------------------------
    // POST /api/people/{id}/upload-doc   (multipart: doc_type, file)
    // ------------------------------------------------------------------------
    public static function uploadDoc($id) {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);
        $type = $_POST['doc_type'] ?? '';
        if (!isset(self::$docTypes[$type])) jsonError("Invalid document type", 422);
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) jsonError("No file uploaded", 400);

        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'])) jsonError("Only JPG, PNG, WEBP or PDF allowed", 422);

        $dir = __DIR__ . '/../../public/uploads/people/' . (int)$id . '/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $name = $type . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $dir . $name)) jsonError("Failed to save file", 500);

        $rel = 'uploads/people/' . (int)$id . '/' . $name;
        $col = self::$docTypes[$type]['col'];
        $pdo->prepare("UPDATE workers SET $col = ?, updated_at = NOW() WHERE id = ?")->execute([$rel, (int)$id]);
        if ($type === 'pf_exemption') {
            $pdo->prepare("UPDATE universal_registrations SET pf_exemption_url = ? WHERE worker_id = ?")->execute([$rel, (int)$id]);
        }
        logAudit('WEB-PORTAL', 'admin', 'PEOPLE_DOC_UPLOAD', 'workers', (int)$id, "Uploaded $type");
        jsonSuccess("Document uploaded", ['url' => '/' . $rel, 'doc_type' => $type]);
    }

    // ------------------------------------------------------------------------
    // POST /api/people/assign  {worker_ids:[], user_ids:[], mode:'replace'|'add'|'clear'}
    // ------------------------------------------------------------------------
    public static function assignLogins() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);
        $in = self::input();
        $workerIds = self::intList($in['worker_ids'] ?? []);
        $userIds = self::intList($in['user_ids'] ?? []);
        $mode = $in['mode'] ?? 'replace';
        if (empty($workerIds)) jsonError("Select at least one employee", 422);
        if ($mode !== 'clear' && empty($userIds)) jsonError("Select at least one login", 422);

        $pdo->beginTransaction();
        try {
            $inW = implode(',', array_fill(0, count($workerIds), '?'));
            if ($mode === 'replace' || $mode === 'clear') {
                $pdo->prepare("UPDATE worker_login_assignments SET deleted_at = NOW() WHERE deleted_at IS NULL AND worker_id IN ($inW)")
                    ->execute($workerIds);
            }
            if ($mode !== 'clear') {
                $ins = $pdo->prepare("INSERT INTO worker_login_assignments (worker_id, user_id, assigned_by, created_at, updated_at)
                                      VALUES (?, ?, 'WEB-PORTAL', NOW(), NOW())
                                      ON DUPLICATE KEY UPDATE deleted_at = NULL, assigned_by = 'WEB-PORTAL', updated_at = NOW()");
                foreach ($workerIds as $w) foreach ($userIds as $u) $ins->execute([$w, $u]);
            }
            // Bump embedding version so kiosks pick up roster change on next sync
            $pdo->prepare("UPDATE workers SET embedding_version = embedding_version + 1, updated_at = NOW() WHERE id IN ($inW)")
                ->execute($workerIds);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonError("Assignment failed: " . $e->getMessage(), 500);
        }

        logAudit('WEB-PORTAL', 'admin', 'PEOPLE_LOGIN_ASSIGN', 'worker_login_assignments', count($workerIds),
            "Mode=$mode; workers=" . implode(',', $workerIds) . "; logins=" . implode(',', $userIds));
        jsonSuccess("Login assignment updated for " . count($workerIds) . " employee(s)", [
            'worker_ids' => $workerIds, 'user_ids' => $userIds, 'mode' => $mode
        ]);
    }

    // ------------------------------------------------------------------------
    // POST /api/people/transfer {worker_ids:[], to_unit, reason, effective_date, user_ids:[]}
    // ------------------------------------------------------------------------
    public static function transferUnit() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);
        $in = self::input();
        $workerIds = self::intList($in['worker_ids'] ?? []);
        $toUnit = trim($in['to_unit'] ?? '');
        $reason = trim($in['reason'] ?? '');
        $eff = trim($in['effective_date'] ?? '') ?: date('Y-m-d');
        $userIds = self::intList($in['user_ids'] ?? []);
        if (empty($workerIds)) jsonError("Select at least one employee", 422);
        if ($toUnit === '') jsonError("Target unit is required", 422);

        $chk = $pdo->prepare("SELECT COUNT(*) FROM units WHERE id = ?");
        $chk->execute([$toUnit]);
        if (!$chk->fetchColumn()) jsonError("Unknown unit '$toUnit'", 422);

        // If specific logins weren't selected, auto-map to all logins in target unit
        if (empty($userIds)) {
            $stU = $pdo->prepare("SELECT id FROM users WHERE unit_id = ? AND deleted_at IS NULL");
            $stU->execute([$toUnit]);
            $userIds = $stU->fetchAll(PDO::FETCH_COLUMN);
        }

        $moved = 0; $skipped = 0;
        $pdo->beginTransaction();
        try {
            $get = $pdo->prepare("SELECT unit_id FROM workers WHERE id = ? AND deleted_at IS NULL");
            $log = $pdo->prepare("INSERT INTO worker_transfers (worker_id, from_unit, to_unit, reason, transferred_by, effective_date, created_at, updated_at)
                                  VALUES (?, ?, ?, ?, 'WEB-PORTAL', ?, NOW(), NOW())");
            $upd = $pdo->prepare("UPDATE workers SET unit_id = ?, embedding_version = embedding_version + 1, updated_at = NOW() WHERE id = ?");
            // Drop assignments to logins that belong to other units
            $drop = $pdo->prepare("UPDATE worker_login_assignments a JOIN users u ON u.id = a.user_id
                                   SET a.deleted_at = NOW() WHERE a.worker_id = ? AND a.deleted_at IS NULL AND IFNULL(u.unit_id,'') <> ?");
            $ins = $pdo->prepare("INSERT INTO worker_login_assignments (worker_id, user_id, assigned_by, created_at, updated_at)
                                  VALUES (?, ?, 'WEB-PORTAL', NOW(), NOW())
                                  ON DUPLICATE KEY UPDATE deleted_at = NULL, updated_at = NOW()");
            foreach ($workerIds as $w) {
                $get->execute([$w]);
                $from = $get->fetchColumn();
                if ($from === false) { $skipped++; continue; }
                if ($from !== $toUnit) {
                    $log->execute([$w, $from, $toUnit, $reason ?: null, $eff]);
                    $upd->execute([$toUnit, $w]);

                    // If transferred today or earlier, void today's punches in previous unit so they are marked Absent in old unit
                    // When they give attendance in the new unit, attendance will be recorded in the new unit
                    if ($eff <= date('Y-m-d')) {
                        $pdo->prepare("UPDATE attendance_punches SET deleted_at = NOW(), updated_at = NOW() 
                                       WHERE worker_id = ? AND DATE(punch_time) = CURDATE() AND deleted_at IS NULL AND (unit_id = ? OR unit_id IS NULL OR unit_id = '')")
                            ->execute([$w, $from]);
                    }

                    $moved++;
                } else {
                    $skipped++;
                }
                $drop->execute([$w, $toUnit]);
                foreach ($userIds as $u) $ins->execute([$w, $u]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonError("Transfer failed: " . $e->getMessage(), 500);
        }

        logAudit('WEB-PORTAL', 'admin', 'PEOPLE_UNIT_TRANSFER', 'workers', $moved,
            "To=$toUnit; workers=" . implode(',', $workerIds) . "; reason=$reason");
        jsonSuccess("$moved employee(s) transferred to $toUnit" . ($skipped ? " ($skipped already in unit)" : ''), [
            'moved' => $moved, 'skipped' => $skipped, 'to_unit' => $toUnit
        ]);
    }

    // ------------------------------------------------------------------------
    // GET /api/people/download?ids=1,2,3   -> ZIP
    // ------------------------------------------------------------------------
    public static function downloadZip() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);
        $ids = self::intList($_GET['ids'] ?? '');
        if (empty($ids)) jsonError("No employees selected", 422);
        if (!class_exists('ZipArchive')) jsonError("PHP zip extension is not enabled", 500);

        $tmp = tempnam(sys_get_temp_dir(), 'tgppl');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);

        $single = count($ids) === 1;
        $zipName = 'TrooGood_People_' . date('Ymd_His') . '.zip';
        foreach ($ids as $id) {
            $p = self::loadPerson($pdo, $id);
            if (!$p) continue;
            $p['assignments'] = self::assignmentsFor($pdo, [$id])[$id] ?? [];
            $tr = $pdo->prepare("SELECT from_unit, to_unit, reason, effective_date FROM worker_transfers WHERE worker_id = ? AND deleted_at IS NULL ORDER BY id DESC");
            $tr->execute([$id]);
            $p['transfers'] = $tr->fetchAll(PDO::FETCH_ASSOC);

            $folder = preg_replace('/[^A-Za-z0-9_-]+/', '_', $p['worker_code'] . '_' . $p['full_name']);
            if ($single) $zipName = $folder . '_' . date('Ymd') . '.zip';
            $prefix = $folder . '/';

            // Documents
            $docPaths = [];
            foreach (self::$docTypes as $key => $meta) {
                [$path] = self::resolveDoc($p['_docs'][$key]);
                if ($path) {
                    $docPaths[$key] = $path;
                    $zip->addFile($path, $prefix . $meta['file'] . '.' . strtolower(pathinfo($path, PATHINFO_EXTENSION)));
                }
            }
            [$f11] = self::resolveDoc($p['_docs']['form11']);
            if ($f11) $zip->addFile($f11, $prefix . 'Form11_Declaration.' . pathinfo($f11, PATHINFO_EXTENSION));

            // Biometric embeddings
            $emb = $pdo->prepare("SELECT sample_angle, pipeline_version, embedding_data FROM face_embeddings WHERE worker_id = ? AND deleted_at IS NULL ORDER BY id");
            $emb->execute([$id]);
            $vectors = []; $pipeline = 2;
            foreach ($emb->fetchAll(PDO::FETCH_ASSOC) as $e) {
                $vectors[$e['sample_angle']] = json_decode($e['embedding_data'], true);
                $pipeline = (int)$e['pipeline_version'];
            }
            $zip->addFromString($prefix . 'Biometric_Embeddings.json', json_encode([
                'worker_id' => $p['id'], 'worker_code' => $p['worker_code'], 'full_name' => $p['full_name'],
                'unit_id' => $p['unit_id'], 'model' => 'MobileFaceNet ArcFace 192-d (L2 normalized)',
                'pipeline_version' => $pipeline, 'embedding_version' => $p['embedding_version'],
                'assigned_logins' => array_column($p['assignments'], 'email'),
                'exported_at' => date('c'), 'embeddings' => $vectors,
            ], JSON_PRETTY_PRINT));

            // Profile PDF
            $zip->addFromString($prefix . 'Employee_Profile_' . $p['worker_code'] . '.pdf', self::buildProfilePdf($p, $docPaths, count($vectors)));
        }
        $zip->close();

        logAudit('WEB-PORTAL', 'admin', 'PEOPLE_EXPORT_ZIP', 'workers', count($ids), "Exported ids=" . implode(',', $ids));

        while (ob_get_level()) ob_end_clean();
        header_remove('Content-Type');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipName . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        exit();
    }

    private static function buildProfilePdf($p, $docPaths, $vectorCount) {
        $pdf = new SimplePdf();
        $pdf->addPage();
        $W = SimplePdf::W;
        $m = 36;
        $dash = function ($v) { return ($v === null || $v === '') ? '-' : (string)$v; };

        // Header band
        $pdf->rect(0, 0, $W, 78, [23, 28, 38]);
        $pdf->text($m, 32, 'TrooGood HRMS', 18, true, [255, 255, 255]);
        $pdf->text($m, 52, 'Employee Master Profile', 11, false, [159, 169, 188]);
        $pdf->text($W - 210, 32, 'Generated: ' . date('d M Y, h:i A') . ' IST', 8.5, false, [159, 169, 188]);
        $pdf->text($W - 210, 46, 'Status: ' . strtoupper($p['approval_status']), 8.5, true,
            $p['approval_status'] === 'approved' ? [74, 222, 128] : [250, 204, 21]);

        // Identity block + photo
        $y = 100;
        $pdf->rect($W - $m - 100, $y, 100, 120, [244, 239, 233], [226, 220, 212]);
        if (!$pdf->image($docPaths['photo'] ?? null, $W - $m - 98, $y + 2, 96, 116)) {
            $pdf->text($W - $m - 78, $y + 64, 'No Photo', 10, false, [106, 116, 135]);
        }
        $pdf->text($m, $y + 18, $p['full_name'], 20, true);
        $pdf->text($m, $y + 38, $dash($p['designation']) . '  |  ' . $dash($p['unit_id']), 11, false, [106, 116, 135]);
        $pdf->text($m, $y + 58, 'Employee ID: ' . $p['worker_code'] . '   (System #' . $p['id'] . ')', 10, true, [41, 82, 227]);
        $pdf->text($m, $y + 76, 'Employment: ' . $dash($p['employment_type']) . ($p['contractor_name'] ? '  |  Contractor: ' . $p['contractor_name'] : ''), 9.5);
        $logins = array_map(function ($a) { return $a['name']; }, $p['assignments'] ?? []);
        $pdf->text($m, $y + 94, 'Attendance Sync: ' . ($logins ? implode(', ', $logins) : 'All ' . $p['unit_id'] . ' kiosk logins (default)'), 9.5);
        $pdf->text($m, $y + 112, 'Biometrics: ' . $vectorCount . ' face vector(s), version v' . $p['embedding_version'], 9.5);

        $y = 240;
        $section = function ($title, $rows) use ($pdf, $m, $W, &$y, $dash) {
            if ($y > 720) { $pdf->addPage(); $y = 50; }
            $pdf->rect($m, $y, $W - 2 * $m, 20, [41, 82, 227]);
            $pdf->text($m + 8, $y + 14, $title, 10, true, [255, 255, 255]);
            $y += 26;
            $colW = ($W - 2 * $m) / 2;
            $i = 0;
            foreach ($rows as $label => $val) {
                $col = $i % 2;
                $x = $m + $col * $colW + 6;
                $pdf->text($x, $y + 9, $label, 7.5, false, [106, 116, 135]);
                $pdf->wrapText($x, $y + 21, $colW - 14, $dash($val), 9.5, true);
                if ($col === 1 || $i === count($rows) - 1) {
                    $y += 32;
                    $pdf->line($m, $y - 3, $W - $m, $y - 3);
                    if ($y > 770) { $pdf->addPage(); $y = 50; }
                }
                $i++;
            }
            $y += 8;
        };

        $section('Personal Details', [
            'Gender' => $p['gender'], 'Date of Birth' => $p['date_of_birth'],
            'Mobile Number' => $p['mobile_number'], 'Alternate Mobile' => $p['alternate_mobile'],
            'Email' => $p['email_address'], 'Blood Group' => $p['blood_group'],
            'Father / Husband Name' => $p['father_or_husband_name'], 'Marital Status' => $p['marital_status'],
            'Present Address' => $p['present_address'], 'Permanent Address' => $p['permanent_address'],
        ]);
        $section('Employment Details', [
            'Unit' => $p['unit_id'], 'Designation' => $p['designation'],
            'Date of Joining' => $p['date_of_joining'], 'Shift' => $p['shift'],
            'Employment Type' => $p['employment_type'], 'Contractor Name' => $p['is_contract'] ? $p['contractor_name'] : 'N/A',
            'Monthly Salary' => $p['monthly_salary'] !== null ? 'Rs. ' . number_format($p['monthly_salary'], 2) : null,
            'Reporting To' => $p['reporting_to'],
        ]);
        $section('Statutory & Identity', [
            'Aadhaar Number' => $p['aadhaar_number'], 'PAN Number' => $p['pan_number'],
            'UAN Number' => $p['uan_number'], 'ESI Number' => $p['esi_number'],
            'Health Insurance Number' => $p['health_insurance_number'], 'PF Account' => $p['has_pf_account'] ? 'Yes' : 'No',
            'EPS Pension' => $p['eps_pension_status'], 'PF/ESI Opt-out' => $p['opt_out_pf_esi'] ? 'Yes (Exemption)' : 'No',
        ]);
        $section('Bank Details', [
            'Account Number' => $p['account_number'], 'IFSC Code' => $p['ifsc_code'],
            'Bank & Branch' => $p['bank_name_branch'],
        ]);
        $section('Emergency Contact & Nominee', [
            'Emergency Contact' => $p['emergency_name'], 'Relationship' => $p['emergency_relationship'],
            'Emergency Mobile' => $p['emergency_mobile'], 'Nominee' => $p['nominee_full_name'],
            'Nominee Relationship' => $p['nominee_relationship'], 'Nominee DOB / Share' => $dash($p['nominee_dob']) . ' / ' . $dash($p['nominee_share_percentage']),
        ]);
        if (!empty($p['transfers'])) {
            $rows = [];
            foreach ($p['transfers'] as $i => $t) {
                $rows['Transfer ' . ($i + 1) . ' (' . $dash($t['effective_date']) . ')'] = $dash($t['from_unit']) . ' -> ' . $t['to_unit'] . ($t['reason'] ? ' | ' . $t['reason'] : '');
            }
            $section('Unit Transfer History', $rows);
        }

        // Document pages
        foreach (['aadhaar_front' => 'Aadhaar Card (Front)', 'aadhaar_back' => 'Aadhaar Card (Back)',
                  'bank_proof' => 'Cancelled Cheque / Bank Passbook', 'pf_exemption' => 'PF Exemption Form'] as $k => $title) {
            if (empty($docPaths[$k]) || !preg_match('/\.(jpe?g|png|webp|gif)$/i', $docPaths[$k])) continue;
            $pdf->addPage();
            $pdf->rect(0, 0, $W, 50, [23, 28, 38]);
            $pdf->text($m, 31, $title . '  -  ' . $p['full_name'] . ' (' . $p['worker_code'] . ')', 12, true, [255, 255, 255]);
            $pdf->image($docPaths[$k], $m, 70, $W - 2 * $m, 720);
        }
        return $pdf->output();
    }

    // ------------------------------------------------------------------------
    // GET /api/people/{id}/form11-pdf   -> Download EPFO Form 11 PDF
    // ------------------------------------------------------------------------
    public static function downloadForm11Pdf($id) {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);
        $p = self::loadPerson($pdo, (int)$id);
        if (!$p) jsonError("Employee not found", 404);

        $docPaths = [];
        foreach (self::$docTypes as $key => $meta) {
            [$path] = self::resolveDoc($p['_docs'][$key]);
            if ($path) $docPaths[$key] = $path;
        }

        $pdfBytes = self::buildForm11Pdf($p, $docPaths);
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $p['worker_code'] . '_' . $p['full_name']);
        $fileName = 'TrooGood_EPFO_Form11_' . $safeName . '.pdf';

        while (ob_get_level()) ob_end_clean();
        header_remove('Content-Type');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($pdfBytes));
        echo $pdfBytes;
        exit();
    }

    private static function buildForm11Pdf($p, $docPaths) {
        $pdf = new SimplePdf();
        $pdf->addPage();
        $W = SimplePdf::W;
        $m = 32;
        $dash = function ($v) { return ($v === null || $v === '') ? '-' : (string)$v; };

        // PAGE 1: Header Band
        $pdf->rect(0, 0, $W, 70, [13, 71, 161]);
        $pdf->text($m, 24, "EMPLOYEES' PROVIDENT FUND ORGANISATION (EPFO)", 13, true, [255, 255, 255]);
        $pdf->text($m, 39, "DECLARATION FORM (NEW FORM NO. 11)", 11, true, [254, 240, 138]);
        $pdf->text($m, 52, "Under Paragraph 34 & 57 of EPF Scheme, 1952 & Paragraph 24 of EPS Scheme, 1995", 7.5, false, [219, 234, 254]);
        $pdf->text($W - 200, 24, "Establishment: TROOGOOD", 8.5, true, [255, 255, 255]);
        $pdf->text($W - 200, 37, "Mformillet Foods Pvt Ltd", 8, false, [219, 234, 254]);
        $pdf->text($W - 200, 49, "Factory Unit: " . $dash($p['unit_id']), 8, true, [254, 240, 138]);

        // Identity strip & Photo
        $y = 82;
        $photoW = 70;
        $photoH = 84;
        $pdf->rect($W - $m - $photoW, $y, $photoW, $photoH, [248, 250, 252], [203, 213, 225]);
        if (!$pdf->image($docPaths['photo'] ?? null, $W - $m - $photoW + 1, $y + 1, $photoW - 2, $photoH - 2)) {
            $pdf->text($W - $m - $photoW + 10, $y + 45, 'Employee Photo', 7, false, [100, 116, 139]);
        }

        $pdf->text($m, $y + 14, $p['full_name'], 15, true, [15, 23, 42]);
        $pdf->text($m, $y + 30, 'Designation: ' . $dash($p['designation']) . '   |   Worker Code: ' . $p['worker_code'], 9.5, false, [71, 85, 105]);
        $pdf->text($m, $y + 44, 'Date of Joining: ' . $dash($p['date_of_joining']) . '   |   Category: ' . $dash($p['employment_type']), 9, false, [71, 85, 105]);
        $pdf->text($m, $y + 58, 'Statutory Status: ' . ($p['opt_out_pf_esi'] ? 'VOLUNTARILY OPTED OUT' : 'STANDARD MANDATORY PF/ESI ENROLLED'), 8.5, true, $p['opt_out_pf_esi'] ? [180, 83, 9] : [22, 163, 74]);
        $pdf->text($m, $y + 72, 'Approval Status: ' . strtoupper($p['approval_status']) . '   |   Digital Biometrics: ' . ($p['embedding_count'] ?? 1) . ' Angle Vector(s)', 8.5, false, [100, 116, 139]);

        $y = 180;
        $renderSection = function ($title, $fields) use ($pdf, $m, $W, &$y, $dash) {
            $pdf->rect($m, $y, $W - 2 * $m, 17, [241, 245, 249], [203, 213, 225]);
            $pdf->text($m + 8, $y + 12, $title, 9, true, [30, 41, 59]);
            $y += 22;
            $colW = ($W - 2 * $m) / 2;
            $i = 0;
            foreach ($fields as $label => $val) {
                $col = $i % 2;
                $x = $m + $col * $colW + 6;
                $pdf->text($x, $y + 8, $label, 7.5, false, [100, 116, 139]);
                $pdf->wrapText($x, $y + 19, $colW - 12, $dash($val), 8.5, true, [15, 23, 42]);
                if ($col === 1 || $i === count($fields) - 1) {
                    $y += 28;
                    $pdf->line($m, $y - 2, $W - $m, $y - 2, [226, 232, 240]);
                }
                $i++;
            }
            $y += 6;
        };

        $renderSection("1. MEMBER PERSONAL PARTICULARS", [
            "Name of Member (as per Aadhaar)" => $p['full_name'],
            "Father's / Spouse's Name" => $p['father_or_husband_name'],
            "Date of Birth" => $p['date_of_birth'],
            "Gender / Marital Status" => $p['gender'] . ' / ' . $dash($p['marital_status']),
            "Mobile Number" => $p['mobile_number'],
            "Email Address" => $p['email_address'],
            "Present Residential Address" => $p['present_address'],
            "Permanent Address" => $p['permanent_address'],
        ]);

        $renderSection("2. KYC & BANK PARTICULARS (MANDATORY)", [
            "Aadhaar Number" => $p['aadhaar_number'],
            "PAN Card Number" => $p['pan_number'],
            "Bank Account Number" => $p['account_number'],
            "Bank IFSC Code" => $p['ifsc_code'],
            "Bank Name & Branch" => $p['bank_name_branch'],
            "Monthly Salary / Wages" => $p['monthly_salary'] ? ('Rs. ' . number_format($p['monthly_salary'], 2)) : 'As per wage register',
        ]);

        $renderSection("3. PREVIOUS EMPLOYMENT & PROVIDENT FUND PARTICULARS", [
            "Previous Employment Status" => $p['has_worked_previously'] ? ("Yes (" . ($p['previous_employer'] ?: 'Previous Firm') . ")") : "First Job (No prior service)",
            "Prior Universal Account No (UAN)" => $p['uan_number'] ?: "None (New Member)",
            "Earlier Member of EPF Scheme, 1952" => $p['has_pf_account'] ? "YES" : "NO",
            "Earlier Member of EPS Scheme, 1995" => $p['eps_pension_status'] ?: "NO",
            "Date of Exit from Previous Job" => $p['last_working_date'] ?: "N/A",
            "ESIC Insurance IP Number" => $p['esi_number'] ?: "New Enrollment",
            "PF/ESIC Statutory Coverage" => $p['opt_out_pf_esi'] ? "VOLUNTARILY OPTED OUT / EXEMPTED" : "Enrolled under Standard Statutory Scheme",
            "Contractor / Deputation Agency" => $p['is_contract'] ? ($p['contractor_name'] ?: 'Contractor') : "Direct TrooGood Employment",
        ]);

        $renderSection("4. NOMINEE PARTICULARS (ANNEXURE TO FORM 2 & FORM 11)", [
            "Primary Nominee Full Name" => $p['nominee_full_name'],
            "Nominee Relationship" => $p['nominee_relationship'],
            "Nominee Date of Birth" => $p['nominee_dob'],
            "Proportion of Share" => $p['nominee_share_percentage'] ?: "100%",
        ]);

        // PAGE 2: Declarations & Signatures
        $pdf->addPage();
        $pdf->rect(0, 0, $W, 55, [13, 71, 161]);
        $pdf->text($m, 24, "EPFO FORM 11 · STATUTORY DECLARATION & UNDERTAKING", 13, true, [255, 255, 255]);
        $pdf->text($m, 40, "Member: " . $p['full_name'] . "   |   Code: #" . $p['worker_code'] . "   |   Unit: " . $p['unit_id'], 8.5, false, [219, 234, 254]);

        $y = 75;
        $pdf->rect($m, $y, $W - 2 * $m, 17, [241, 245, 249], [203, 213, 225]);
        $pdf->text($m + 8, $y + 12, "5. STATUTORY UNDERTAKINGS & CONFIRMATIONS", 9, true, [30, 41, 59]);
        $y += 24;

        $declarations = [
            "1. Truthfulness & Statutory Accuracy" => "I certify that all the information given above is true, correct, and complete to the best of my knowledge. I understand that any false statement or suppression of material facts will render me liable for statutory penal action under Section 14 of the EPF & MP Act, 1952.",
            "2. Factory Safety & GMP Regulations" => "I agree to abide strictly by factory safety rules, hygiene protocols, personal protective equipment (PPE) guidelines, and Good Manufacturing Practices (GMP) of TrooGood / Mformillet Foods Pvt Ltd.",
            "3. Direct Bank Credit Authorization" => "I hereby authorize TrooGood to credit all monthly salary payments, wage arrears, statutory allowances, and bonuses directly into my verified bank account as specified in Section 2 above.",
            "4. Aadhaar Seeding & Verification Consent" => "I voluntarily provide my Aadhaar Number and give my explicit consent to TrooGood and EPFO to use my Aadhaar details for statutory identity authentication, UAN seeding, and regulatory compliance.",
            "5. Facial Biometric Consent (DPDP Act, 2023)" => "I hereby explicitly and voluntarily consent to the collection and mathematical extraction of 192-dimensional facial biometric feature vectors by TrooGood HRMS solely for attendance tracking, gate access, and workplace identity verification.",
        ];

        if ($p['opt_out_pf_esi']) {
            $declarations["6. Voluntary Opt-Out Declaration"] = "STATUTORY NON-COVERAGE DECLARATION: Having understood the provisions of the Employees' Provident Funds Scheme, 1952 and ESI Act, 1948, I hereby voluntarily elect exclusion from PF and ESI contributions on my own request.";
        }

        foreach ($declarations as $dTitle => $dText) {
            $pdf->text($m + 6, $y + 9, "[CONFIRMED]  " . $dTitle, 8.5, true, [22, 101, 52]);
            $y = $pdf->wrapText($m + 6, $y + 20, $W - 2 * $m - 12, $dText, 7.5, false, [71, 85, 105], 11);
            $y += 8;
        }

        // Section 6: Digital Signature Block
        $y += 10;
        $pdf->rect($m, $y, $W - 2 * $m, 110, [248, 250, 252], [13, 71, 161], 1.2);
        $pdf->text($m + 12, $y + 18, "6. ELECTRONIC SIGNATURE & LEGAL AUTHENTICATION", 9.5, true, [13, 71, 161]);
        
        $sigName = $p['signature_typed_name'] ?: $p['full_name'];
        $sigCity = $p['signature_place_city'] ?: 'Hyderabad';
        $sigTime = $p['signature_timestamp'] ?: ($p['created_at'] ?: date('d M Y, h:i A') . ' IST');

        $pdf->rect($m + 14, $y + 28, 220, 42, [255, 255, 255], [147, 197, 253]);
        $pdf->text($m + 22, $y + 48, $sigName, 13, true, [13, 71, 161]);
        $pdf->text($m + 22, $y + 62, "Digital Signature of Employee", 7, false, [100, 116, 139]);

        $pdf->text($m + 250, $y + 36, "Place / City: " . $sigCity, 8.5, true, [30, 41, 59]);
        $pdf->text($m + 250, $y + 50, "Timestamp: " . $sigTime, 8, false, [71, 85, 105]);
        $pdf->text($m + 250, $y + 64, "Status: Digitally Signed & Submitted via Kiosk / Web Portal", 8, true, [22, 101, 52]);

        $pdf->line($m + 14, $y + 78, $W - $m - 14, $y + 78, [226, 232, 240]);
        $pdf->text($m + 14, $y + 92, "LEGAL NOTICE: Valid electronic signature executed under Section 5 of the Information Technology Act, 2000.", 7.5, false, [100, 116, 139]);
        $pdf->text($m + 14, $y + 102, "Compliant with EPFO Form 11 electronic submission guidelines and Digital Personal Data Protection (DPDP) Act, 2023.", 7, false, [100, 116, 139]);

        // Section 7: Employer Undertaking (Part B)
        $y += 122;
        $pdf->rect($m, $y, $W - 2 * $m, 85, [255, 255, 255], [203, 213, 225]);
        $pdf->rect($m, $y, $W - 2 * $m, 16, [241, 245, 249], [203, 213, 225]);
        $pdf->text($m + 8, $y + 11, "7. UNDERTAKING BY THE EMPLOYER (PART B OF EPFO FORM 11)", 8.5, true, [30, 41, 59]);

        $pdf->text($m + 10, $y + 30, "Establishment Name: Mformillet Foods Pvt Ltd (TrooGood)   |   Factory Unit: " . $dash($p['unit_id']), 8, true, [30, 41, 59]);
        $pdf->text($m + 10, $y + 44, "Employee Code: #" . $p['worker_code'] . "   |   Date of Joining: " . $dash($p['date_of_joining']) . "   |   Designation: " . $dash($p['designation']), 8, false, [71, 85, 105]);
        $pdf->text($m + 10, $y + 58, "Certified that the member particulars and KYC credentials have been verified by the establishment.", 7.5, false, [71, 85, 105]);
        $pdf->text($m + 10, $y + 72, "Authorized Signatory: Head of Human Resources & Statutory Compliance · TrooGood", 8, true, [13, 71, 161]);

        return $pdf->output();
    }

    public static function updateAppMode() {
        $input = self::input();
        $id = (int)($input['worker_id'] ?? $input['id'] ?? 0);
        $mode = strtolower(trim($input['attendance_app_mode'] ?? 'kiosk'));
        $email = trim($input['app_email'] ?? '');
        $password = trim($input['app_password'] ?? '');
        $apkUrl = trim($input['apk_url'] ?? 'https://troogood.in/downloads/TrooGood_HR_App_v2.1.apk');

        if (!$id) jsonError("Worker ID is required", 422);
        if (!in_array($mode, ['kiosk', 'individual'])) $mode = 'kiosk';

        $pdo = getDbConnection();
        $wStmt = $pdo->prepare("SELECT worker_code, full_name, email_address FROM workers WHERE id = ?");
        $wStmt->execute([$id]);
        $worker = $wStmt->fetch(PDO::FETCH_ASSOC);
        if (!$worker) jsonError("Worker not found", 404);

        if ($mode === 'individual') {
            if (empty($email)) {
                $email = $worker['email_address'] ?: '';
            }
            if (empty($email)) {
                jsonError("Email ID is required to configure Individual App Access.", 422);
            }
            $pwdHash = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : null;

            $stmt = $pdo->prepare("UPDATE workers SET attendance_app_mode = 'individual', app_email = ?, app_password_hash = COALESCE(?, app_password_hash), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$email, $pwdHash, $id]);

            logAudit('WEB_PORTAL', 'HR Admin', 'UPDATE_ATTENDANCE_APP_MODE', 'workers', $id, [
                'mode' => 'individual', 'email' => $email, 'worker' => $worker['full_name']
            ]);

            // Dispatch App Credentials Email via TrooGood SMTP
            $mailSent = false;
            try {
                $tempPwd = !empty($password) ? $password : '(Retained previous password)';
                $mailResult = MailHelper::sendAppCredentials($email, $worker['full_name'], $worker['worker_code'], $tempPwd, $apkUrl);
                $mailSent = !empty($mailResult['success']);
            } catch (Exception $e) {
                error_log("Failed to dispatch app credentials email: " . $e->getMessage());
            }

            jsonSuccess("Individual Attendance App Mode activated. Credentials and APK link dispatched to {$email}.", [
                'worker_id' => $id,
                'mode' => 'individual',
                'email' => $email,
                'apk_url' => $apkUrl,
                'mail_sent' => $mailSent
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE workers SET attendance_app_mode = 'kiosk', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            logAudit('WEB_PORTAL', 'HR Admin', 'UPDATE_ATTENDANCE_APP_MODE', 'workers', $id, [
                'mode' => 'kiosk', 'worker' => $worker['full_name']
            ]);

            jsonSuccess("Attendance App Mode set to Kiosk for {$worker['full_name']}.", [
                'worker_id' => $id,
                'mode' => 'kiosk'
            ]);
        }
    }
}

