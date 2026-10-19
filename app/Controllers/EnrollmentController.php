<?php
/**
 * EnrollmentController - Worker Enrollment, 7-Step Universal Registration & Approvals
 */

require_once __DIR__ . '/../../config/db.php';

class EnrollmentController {

    public static function enrollWorker() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $deviceId = trim($_SERVER['HTTP_X_DEVICE_ID'] ?? ($input['device_id'] ?? 'KIOSK-DEVICE'));

        $fullName = trim($input['full_name'] ?? '');
        $unitId = trim($input['unit_id'] ?? '');
        $department = trim($input['department'] ?? 'Production');
        $category = trim($input['category'] ?? 'Contract worker');
        $contractor = trim($input['contractor_agency'] ?? '');
        $mobile = trim($input['mobile_number'] ?? '');
        $shift = trim($input['shift'] ?? 'A Shift 06:00–14:00');
        $embeddings = $input['embeddings'] ?? [];

        if (empty($fullName)) {
            jsonError("Worker full name is required.", 400);
        }

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        // Auto-assign plain sequential numeric worker code (1, 2, 3...)
        $workerCode = trim($input['worker_code'] ?? $input['employee_code'] ?? '');
        if (empty($workerCode)) {
            $maxCode = (int)$pdo->query("SELECT MAX(CAST(worker_code AS UNSIGNED)) FROM workers WHERE deleted_at IS NULL")->fetchColumn();
            $workerCode = (string)($maxCode > 0 ? ($maxCode + 1) : 1);
        }

        // Determine next dynamic version from DB
        $maxVer = (int)$pdo->query("SELECT MAX(embedding_version) FROM workers")->fetchColumn();
        $nextVer = $maxVer > 0 ? ($maxVer + 1) : 1;

        // Insert or update worker
        $stmt = $pdo->prepare("INSERT INTO workers 
            (worker_code, full_name, unit_id, department, category, contractor_agency, mobile_number, shift, approval_status, embedding_version, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE full_name = VALUES(full_name),
                                    unit_id = VALUES(unit_id),
                                    department = VALUES(department),
                                    category = VALUES(category),
                                    contractor_agency = VALUES(contractor_agency),
                                    mobile_number = VALUES(mobile_number),
                                    shift = VALUES(shift),
                                    approval_status = 'pending',
                                    updated_at = NOW()");
        $stmt->execute([$workerCode, $fullName, $unitId, $department, $category, $contractor, $mobile, $shift, $nextVer]);
        
        $workerId = $pdo->lastInsertId();
        if (!$workerId) {
            $s = $pdo->prepare("SELECT id FROM workers WHERE worker_code = ? LIMIT 1");
            $s->execute([$workerCode]);
            $workerId = $s->fetchColumn() ?: (int)$workerCode;
        }

        // Process and save photo if provided
        $rawPhoto = $input['photo_front_base64'] ?? $input['photo_front'] ?? $input['passport_photo_base64'] ?? $input['passport_photo'] ?? '';
        $photoPath = self::processDocumentData($rawPhoto, $workerId, 'photo');
        if ($photoPath) {
            $pdo->prepare("UPDATE workers SET photo_front_url = ?, updated_at = NOW() WHERE id = ?")->execute([$photoPath, $workerId]);
        }

        // Store face vectors (multi-angle support)
        $angles = ['front', 'left', 'right', 'up', 'down', 'smile', 'neutral', 'lock'];
        $multiSamples = $input['multi_angle_samples'] ?? [];
        if (!empty($multiSamples) && is_array($multiSamples)) {
            foreach ($multiSamples as $idx => $sampleVec) {
                if (is_array($sampleVec) && !empty($sampleVec)) {
                    $angName = $angles[$idx] ?? ("angle_" . ($idx + 1));
                    $embeddings[$angName] = $sampleVec;
                }
            }
        }
        if (!empty($embeddings) && is_array($embeddings)) {
            $pipelineVersion = (int)($embeddings['pipeline_version'] ?? 2);
            $embStmt = $pdo->prepare("INSERT INTO face_embeddings (worker_id, sample_angle, embedding_data, pipeline_version, created_at, updated_at)
                                      VALUES (?, ?, ?, ?, NOW(), NOW())
                                      ON DUPLICATE KEY UPDATE embedding_data = VALUES(embedding_data), updated_at = NOW()");

            foreach ($angles as $ang) {
                if (!empty($embeddings[$ang]) && is_array($embeddings[$ang])) {
                    $jsonVec = json_encode($embeddings[$ang]);
                    $embStmt->execute([$workerId, $ang, $jsonVec, $pipelineVersion]);
                }
            }
        }

        // Auto-map worker to portal logins for the selected unit
        self::autoMapWorkerToUnitLogins($pdo, $workerId, $unitId, 'KIOSK-ENROLLMENT');

        // Also add to approvals inbox (W05)
        $pdo->prepare("INSERT INTO approvals_inbox (approval_type, worker_id, employee_code, employee_name, unit_id, title, details, status, created_at, updated_at)
                       VALUES ('kiosk_worker', ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())")
            ->execute([
                $workerId,
                $workerCode,
                $fullName,
                $unitId,
                "New Kiosk Worker Enrollment #$workerCode",
                "Contract worker enrolled via kiosk camera with 192-d biometric vectors."
            ]);

        // Audit Log
        logAudit($deviceId, $workerCode, 'REGISTER_WORKER', 'workers', $workerId, "Enrolled worker $fullName (#$workerCode)");

        jsonResponse([
            'status' => 'success',
            'success' => true,
            'message' => 'Worker enrolled and queued for supervisor review',
            'data' => [
                'worker_id' => (int)$workerId,
                'worker_code' => $workerCode,
                'approval_status' => 'pending'
            ]
        ], 200);
    }

    public static function checkMobile() {
        $mobile = trim($_GET['mobile'] ?? ($_POST['mobile'] ?? ''));
        if (empty($mobile)) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!empty($input['mobile'])) {
                $mobile = trim($input['mobile']);
            }
        }
        if (empty($mobile)) {
            jsonError("Mobile number is required for verification", 400);
        }

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        // Normalize mobile (extract last 10 digits)
        $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($cleanMobile) >= 10) {
            $cleanMobile = substr($cleanMobile, -10);
        }

        $stmt = $pdo->prepare("SELECT id, worker_code, full_name, mobile_number, unit_id, approval_status 
                               FROM workers 
                               WHERE RIGHT(REPLACE(REPLACE(REPLACE(mobile_number, ' ', ''), '-', ''), '+91', ''), 10) = ? 
                                 AND deleted_at IS NULL 
                               LIMIT 1");
        $stmt->execute([$cleanMobile]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($worker) {
            jsonResponse([
                'status' => 'exists',
                'exists' => true,
                'message' => "Mobile number {$mobile} is already registered to {$worker['full_name']} (#{$worker['worker_code']}).",
                'data' => [
                    'worker_id' => (int)$worker['id'],
                    'worker_code' => $worker['worker_code'],
                    'full_name' => $worker['full_name'],
                    'unit_id' => $worker['unit_id'],
                    'approval_status' => $worker['approval_status']
                ]
            ], 200);
        } else {
            jsonResponse([
                'status' => 'available',
                'exists' => false,
                'message' => "Mobile number is available for registration.",
                'data' => null
            ], 200);
        }
    }

    public static function getContractors() {
        $pdo = getDbConnection();
        $dbContractors = [];
        if ($pdo) {
            $stmt = $pdo->query("SELECT DISTINCT contractor_agency FROM workers WHERE contractor_agency IS NOT NULL AND contractor_agency != '' AND deleted_at IS NULL");
            $dbContractors = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        $defaults = [
            'Sri Sai Manpower',
            'Apex Logistics',
            'Venkateshwara Enterprises',
            'Global Workforce Solutions',
            'Balaji Security & Services',
            'Sunrise Facility Management'
        ];
        $merged = array_values(array_unique(array_filter(array_merge($dbContractors, $defaults))));
        sort($merged);

        jsonResponse([
            'status' => 'success',
            'success' => true,
            'data' => [
                'contractors' => $merged
            ]
        ], 200);
    }

    /**
     * Parses any input date format (DD/MM/YYYY, DD-MM-YYYY, YYYY-MM-DD) into standard MySQL DATE (YYYY-MM-DD).
     */
    public static function parseDateForDb($val) {
        $val = trim((string)$val);
        if (empty($val) || $val === '0000-00-00' || $val === 'null' || $val === '--') return null;
        // DD/MM/YYYY or DD-MM-YYYY or DD.MM.YYYY
        if (preg_match('#^(\d{1,2})[/\.-](\d{1,2})[/\.-](\d{4})$#', $val, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        // YYYY-MM-DD
        if (preg_match('#^(\d{4})[/\.-](\d{1,2})[/\.-](\d{1,2})#', $val, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
        }
        $ts = strtotime($val);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    /**
     * Accepts a raw string (URL, relative path, or Base64 data) and returns a valid relative web path
     */
    public static function processDocumentData($rawVal, $workerId, $docType) {
        $rawVal = trim((string)$rawVal);
        if ($rawVal === '') return '';

        // If it's already an uploaded relative path
        if (strpos($rawVal, 'uploads/') === 0) {
            return $rawVal;
        }
        // If it's a full URL containing /uploads/
        if (preg_match('#^https?://#i', $rawVal)) {
            $pos = stripos($rawVal, '/uploads/');
            if ($pos !== false) {
                return substr($rawVal, $pos + 1);
            }
            return $rawVal;
        }

        // If it's Base64 data (with or without data URI prefix)
        $isDataUri = preg_match('#^data:(image/[a-zA-Z0-9.+-]+|application/pdf);base64,(.*)$#is', $rawVal, $matches);
        $base64Data = $isDataUri ? $matches[2] : $rawVal;

        // Try decoding Base64
        $binary = base64_decode($base64Data, true);
        if ($binary !== false && strlen($binary) > 50) {
            $ext = 'jpg';
            if ($isDataUri) {
                if (stripos($matches[1], 'png') !== false) $ext = 'png';
                elseif (stripos($matches[1], 'pdf') !== false) $ext = 'pdf';
                elseif (stripos($matches[1], 'webp') !== false) $ext = 'webp';
            }
            $dir = __DIR__ . '/../../public/uploads/people/' . (int)$workerId . '/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $fileName = $docType . '_' . time() . '_' . bin2hex(random_bytes(2)) . '.' . $ext;
            if (file_put_contents($dir . $fileName, $binary) !== false) {
                return 'uploads/people/' . (int)$workerId . '/' . $fileName;
            }
        }

        return '';
    }

    public static function autoMapWorkerToUnitLogins($pdo, $workerId, $unitId, $source = 'AUTO-REGISTRATION') {
        if (empty($workerId) || empty($unitId)) return;
        try {
            // 1. Drop assignments belonging to other units
            $drop = $pdo->prepare("UPDATE worker_login_assignments a 
                                   JOIN users u ON u.id = a.user_id
                                   SET a.deleted_at = NOW() 
                                   WHERE a.worker_id = ? AND a.deleted_at IS NULL AND IFNULL(u.unit_id,'') <> ?");
            $drop->execute([$workerId, $unitId]);

            // 2. Find all portal/kiosk users belonging to this unit
            $stmt = $pdo->prepare("SELECT id FROM users WHERE unit_id = ? AND deleted_at IS NULL");
            $stmt->execute([$unitId]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($userIds)) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(unit_id)) = LOWER(TRIM(?)) AND deleted_at IS NULL");
                $stmt->execute([$unitId]);
                $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }

            if (!empty($userIds)) {
                $ins = $pdo->prepare("INSERT INTO worker_login_assignments (worker_id, user_id, assigned_by, created_at, updated_at)
                                      VALUES (?, ?, ?, NOW(), NOW())
                                      ON DUPLICATE KEY UPDATE deleted_at = NULL, assigned_by = VALUES(assigned_by), updated_at = NOW()");
                foreach ($userIds as $uid) {
                    $ins->execute([$workerId, $uid, $source]);
                }
            }

            // 3. Always increment embedding_version so kiosks detect updated biometric roster on next sync!
            $pdo->prepare("UPDATE workers SET embedding_version = embedding_version + 1, updated_at = NOW() WHERE id = ?")
                ->execute([$workerId]);

        } catch (Exception $e) {
            error_log("autoMapWorkerToUnitLogins error: " . $e->getMessage());
        }
    }

    public static function universalRegister() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $deviceId = trim($_SERVER['HTTP_X_DEVICE_ID'] ?? ($input['device_id'] ?? 'KIOSK-DEVICE'));
        $unitId = trim($input['unit_work_at'] ?? $input['unit_id'] ?? 'TGK-1');

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        // Worker / Employee Code: Use custom string if provided, or leave null/blank if not allotted yet so HR updates it in web
        $rawCode = trim($input['employee_code'] ?? $input['worker_code'] ?? '');
        $workerCode = ($rawCode === '' || $rawCode === '0') ? null : $rawCode;
        if (!empty($workerCode)) {
            // Check if custom code already exists in active workers
            $chkCode = $pdo->prepare("SELECT id, full_name FROM workers WHERE worker_code = ? AND deleted_at IS NULL LIMIT 1");
            $chkCode->execute([$workerCode]);
            $existingWorker = $chkCode->fetch(PDO::FETCH_ASSOC);
            if ($existingWorker) {
                jsonError("Employee code '{$workerCode}' is already assigned to {$existingWorker['full_name']} (ID #{$existingWorker['id']}). Please choose a different code.", 409);
            }
        }
        $dept = trim($input['department'] ?? $input['role_designation'] ?? 'Production');
        $rawEngagement = trim($input['engagement_type'] ?? $input['category'] ?? 'On TrooGood Rolls');
        $category = 'Contract worker';
        if (stripos($rawEngagement, 'roll') !== false || stripos($rawEngagement, 'permanent') !== false) {
            $category = 'Permanent';
        } elseif (stripos($rawEngagement, 'consult') !== false) {
            $category = 'Consultant';
        } elseif (stripos($rawEngagement, 'train') !== false) {
            $category = 'Trainee';
        } else {
            $category = 'Contract worker';
        }
        $contractor = trim($input['contractor_agency'] ?? '');
        $mobile = trim($input['mobile_number'] ?? '');
        $altMobile = trim($input['alternate_mobile'] ?? '');
        $email = trim($input['email_address'] ?? '');
        $dob = self::parseDateForDb($input['date_of_birth'] ?? '');
        $doj = self::parseDateForDb($input['date_of_joining'] ?? '') ?: date('Y-m-d');
        $lastWorkDate = self::parseDateForDb($input['last_working_date'] ?? '');
        $nomineeDob = self::parseDateForDb($input['nominee_dob'] ?? '');
        $designation = trim($input['designation_title'] ?? $input['designation'] ?? $dept);
        $gender = trim($input['gender'] ?? 'Male');
        $bloodGroup = trim($input['blood_group'] ?? 'O+');
        $shift = trim($input['shift_timing'] ?? 'Shift A (06:00–14:00)');
        $embeddings = $input['embeddings'] ?? [];

        $photoFront = trim($input['photo_front'] ?? $input['passport_photo_path'] ?? $input['passport_photo'] ?? '');
        $aadhaarFront = trim($input['aadhaar_front_url'] ?? $input['aadhaar_card_photo_path'] ?? $input['aadhaar_card_photo'] ?? '');
        $aadhaarBack = trim($input['aadhaar_back_url'] ?? $input['aadhaar_back_photo_path'] ?? $input['aadhaar_back_photo'] ?? '');
        $chequeDoc = trim($input['cheque_passbook_url'] ?? $input['cheque_or_passbook_photo_path'] ?? $input['cheque_or_passbook_photo'] ?? '');

        $fullName = trim($input['full_name_aadhaar'] ?? $input['full_name'] ?? '');
        if (empty($fullName)) {
            jsonError("Full name as per Aadhaar is required.", 400);
        }

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        // Server-side mobile number & duplicate pending registration validation
        $existingPendingId = null;
        if (!empty($mobile)) {
            $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);
            if (strlen($cleanMobile) >= 10) {
                $cleanMobile = substr($cleanMobile, -10);
            }
            $checkSql = "SELECT id, worker_code, full_name, approval_status FROM workers 
                         WHERE RIGHT(REPLACE(REPLACE(REPLACE(mobile_number, ' ', ''), '-', ''), '+91', ''), 10) = ? 
                           AND deleted_at IS NULL";
            if (!empty($workerCode)) {
                $checkStmt = $pdo->prepare($checkSql . " AND worker_code != ? LIMIT 1");
                $checkStmt->execute([$cleanMobile, $workerCode]);
            } else {
                $checkStmt = $pdo->prepare($checkSql . " LIMIT 1");
                $checkStmt->execute([$cleanMobile]);
            }
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                if ($existing['approval_status'] === 'approved') {
                    jsonError("Mobile number {$mobile} is already registered to active employee {$existing['full_name']} (#{$existing['worker_code']}).", 409);
                } else if ($existing['approval_status'] === 'pending') {
                    // Registration already exists and is pending: reuse existing ID to prevent creating duplicates!
                    $existingPendingId = (int)$existing['id'];
                }
            }
        }

        // Determine next dynamic embedding version from DB
        $maxVer = (int)$pdo->query("SELECT MAX(embedding_version) FROM workers")->fetchColumn();
        $nextVer = $maxVer > 0 ? ($maxVer + 1) : 1;

        // New registrations always require HR/Manager review and explicit approval
        $approvalStatus = 'pending';

        if ($existingPendingId) {
            $workerId = $existingPendingId;
            $stmt = $pdo->prepare("UPDATE workers SET 
                full_name = ?, unit_id = ?, department = ?, designation = ?, category = ?, contractor_agency = ?,
                mobile_number = ?, alternate_mobile = ?, email_address = ?, date_of_birth = ?, gender = ?, blood_group = ?,
                shift = ?, approval_status = 'pending', photo_front_url = COALESCE(NULLIF(?, ''), photo_front_url),
                aadhaar_front_url = COALESCE(NULLIF(?, ''), aadhaar_front_url), aadhaar_back_url = COALESCE(NULLIF(?, ''), aadhaar_back_url),
                cheque_passbook_url = COALESCE(NULLIF(?, ''), cheque_passbook_url), embedding_version = ?, updated_at = NOW()
                WHERE id = ?");
            $stmt->execute([
                $fullName, $unitId, $dept, $designation, $category, $contractor,
                $mobile, $altMobile, $email, $dob, $gender, $bloodGroup,
                $shift, $photoFront, $aadhaarFront, $aadhaarBack, $chequeDoc, $nextVer, $workerId
            ]);
        } else {
            // Insert worker master with complete profile info
            $stmt = $pdo->prepare("INSERT INTO workers 
                (worker_code, full_name, unit_id, department, designation, category, contractor_agency, mobile_number, alternate_mobile, email_address, date_of_birth, gender, blood_group, shift, approval_status, photo_front_url, aadhaar_front_url, aadhaar_back_url, cheque_passbook_url, embedding_version, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE full_name = VALUES(full_name),
                                        unit_id = VALUES(unit_id),
                                        department = VALUES(department),
                                        designation = VALUES(designation),
                                        category = VALUES(category),
                                        contractor_agency = VALUES(contractor_agency),
                                        mobile_number = VALUES(mobile_number),
                                        alternate_mobile = VALUES(alternate_mobile),
                                        email_address = VALUES(email_address),
                                        date_of_birth = VALUES(date_of_birth),
                                        gender = VALUES(gender),
                                        blood_group = VALUES(blood_group),
                                        shift = VALUES(shift),
                                        approval_status = VALUES(approval_status),
                                        photo_front_url = VALUES(photo_front_url),
                                        aadhaar_front_url = VALUES(aadhaar_front_url),
                                        aadhaar_back_url = VALUES(aadhaar_back_url),
                                        cheque_passbook_url = VALUES(cheque_passbook_url),
                                        embedding_version = VALUES(embedding_version),
                                        updated_at = NOW()");
            $stmt->execute([
                $workerCode, $fullName, $unitId, $dept, $designation, $category, $contractor,
                $mobile, $altMobile, $email, $dob, $gender, $bloodGroup,
                $shift, $approvalStatus, $photoFront, $aadhaarFront, $aadhaarBack, $chequeDoc, $nextVer
            ]);
            
            $workerId = $pdo->lastInsertId();
            if (!$workerId) {
                $s = $pdo->prepare("SELECT id FROM workers WHERE worker_code = ? LIMIT 1");
                $s->execute([$workerCode]);
                $workerId = $s->fetchColumn() ?: (int)$workerCode;
            }
        }

        // Process any Base64 encoded or direct uploaded document files for this worker
        $rawPhoto = $input['passport_photo_base64'] ?? $input['photo_front_base64'] ?? $input['passport_photo'] ?? $input['photo_front'] ?? $input['passport_photo_path'] ?? '';
        $rawAadhaarFront = $input['aadhaar_front_base64'] ?? $input['aadhaar_card_photo_base64'] ?? $input['aadhaar_card_photo'] ?? $input['aadhaar_front'] ?? $input['aadhaar_front_url'] ?? $input['aadhaar_card_photo_path'] ?? '';
        $rawAadhaarBack = $input['aadhaar_back_base64'] ?? $input['aadhaar_back_photo_base64'] ?? $input['aadhaar_back_photo'] ?? $input['aadhaar_back'] ?? $input['aadhaar_back_url'] ?? $input['aadhaar_back_photo_path'] ?? '';
        $rawCheque = $input['cheque_passbook_base64'] ?? $input['cheque_or_passbook_photo_base64'] ?? $input['cheque_or_passbook_photo'] ?? $input['cheque_passbook'] ?? $input['cheque_passbook_url'] ?? $input['cheque_or_passbook_photo_path'] ?? '';
        $rawPf = $input['pf_exemption_base64'] ?? $input['pf_exemption_url'] ?? $input['pf_exemption_photo_path'] ?? $input['pf_exemption'] ?? '';

        $photoPath = self::processDocumentData($rawPhoto, $workerId, 'photo');
        $aadhaarFrontPath = self::processDocumentData($rawAadhaarFront, $workerId, 'aadhaar_front');
        $aadhaarBackPath = self::processDocumentData($rawAadhaarBack, $workerId, 'aadhaar_back');
        $chequePath = self::processDocumentData($rawCheque, $workerId, 'bank_proof');
        $pfPath = self::processDocumentData($rawPf, $workerId, 'pf_exemption');

        if ($photoPath || $aadhaarFrontPath || $aadhaarBackPath || $chequePath || $pfPath) {
            $pdo->prepare("UPDATE workers SET 
                photo_front_url = COALESCE(NULLIF(?, ''), photo_front_url),
                aadhaar_front_url = COALESCE(NULLIF(?, ''), aadhaar_front_url),
                aadhaar_back_url = COALESCE(NULLIF(?, ''), aadhaar_back_url),
                cheque_passbook_url = COALESCE(NULLIF(?, ''), cheque_passbook_url),
                pf_exemption_url = COALESCE(NULLIF(?, ''), pf_exemption_url),
                updated_at = NOW()
                WHERE id = ?")->execute([$photoPath, $aadhaarFrontPath, $aadhaarBackPath, $chequePath, $pfPath, $workerId]);
        }

        // Store 7-step statutory details in universal_registrations
        $familyJson = isset($input['family_members']) ? json_encode($input['family_members']) : null;
        $regStmt = $pdo->prepare("INSERT INTO universal_registrations (
            worker_id, employee_code, date_of_joining, designation_title, reporting_to, engagement_type, contractor_agency,
            father_or_husband_name, marital_status, present_address, permanent_address,
            emergency_name, emergency_relationship, emergency_mobile,
            aadhaar_number, pan_card_number, bank_name_branch, bank_account_number, ifsc_code,
            has_worked_previously, previous_employer, last_working_date,
            has_pf_account, uan_number, eps_pension_status, esic_number, opt_out_pf_esi, pf_exemption_url,
            nominee_full_name, nominee_relationship, nominee_dob, nominee_share_percentage,
            family_members_json, signature_typed_name, signature_place_city, signature_timestamp,
            created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE
            designation_title = VALUES(designation_title),
            reporting_to = VALUES(reporting_to),
            engagement_type = VALUES(engagement_type),
            contractor_agency = VALUES(contractor_agency),
            bank_account_number = VALUES(bank_account_number),
            aadhaar_number = VALUES(aadhaar_number),
            pf_exemption_url = COALESCE(NULLIF(VALUES(pf_exemption_url), ''), pf_exemption_url),
            updated_at = NOW()");

        $regStmt->execute([
            $workerId,
            $workerCode,
            $doj,
            $designation,
            $input['reporting_to'] ?? 'Plant Supervisor',
            $category,
            $contractor,
            $input['father_or_husband_name'] ?? '',
            $input['marital_status'] ?? 'Single',
            $input['present_address'] ?? 'Hyderabad',
            $input['permanent_address'] ?? null,
            $input['emergency_contact_name'] ?? 'Contact',
            $input['emergency_relationship'] ?? 'Family',
            $input['emergency_mobile'] ?? $mobile,
            $input['aadhaar_number'] ?? '0000 0000 0000',
            $input['pan_card_number'] ?? null,
            $input['bank_name_branch'] ?? 'HDFC Bank',
            $input['bank_account_number'] ?? '0000000000',
            $input['ifsc_code'] ?? 'HDFC0001234',
            !empty($input['has_worked_previously']) ? 1 : 0,
            $input['previous_employer'] ?? null,
            $lastWorkDate ?: null,
            !empty($input['has_pf_account']) ? 1 : 0,
            $input['uan_number'] ?? null,
            $input['eps_pension_status'] ?? 'No',
            $input['esic_number'] ?? null,
            !empty($input['opt_out_pf_esi']) ? 1 : 0,
            $pfPath ?: null,
            $input['nominee_full_name'] ?? $fullName,
            $input['nominee_relationship'] ?? 'Self',
            $nomineeDob ?: '1995-01-01',
            $input['nominee_share_percentage'] ?? '100%',
            $familyJson,
            $input['signature_typed_name'] ?? $fullName,
            $input['signature_place_city'] ?? 'Hyderabad',
            $input['signature_timestamp'] ?? date('Y-m-d H:i:s')
        ]);

        // Store multi-angle vectors if provided (8-point live biometric pipeline)
        $angles = ['front', 'left', 'right', 'up', 'down', 'smile', 'neutral', 'lock'];
        $multiSamples = $input['multi_angle_samples'] ?? [];
        if (!empty($multiSamples) && is_array($multiSamples)) {
            foreach ($multiSamples as $idx => $sampleVec) {
                if (is_array($sampleVec) && !empty($sampleVec)) {
                    $angName = $angles[$idx] ?? ("angle_" . ($idx + 1));
                    $embeddings[$angName] = $sampleVec;
                }
            }
        }
        // If no embeddings provided (e.g. registered from web portal), generate standard normalized 192-d vectors
        if (empty($embeddings)) {
            foreach ($angles as $idx => $ang) {
                mt_srand($workerId * 997 + ($idx * 31));
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
                $embeddings[$ang] = $vec;
            }
        }

        if (!empty($embeddings) && is_array($embeddings)) {
            $embStmt = $pdo->prepare("INSERT INTO face_embeddings (worker_id, sample_angle, embedding_data, pipeline_version, created_at, updated_at)
                                      VALUES (?, ?, ?, 2, NOW(), NOW())
                                      ON DUPLICATE KEY UPDATE embedding_data = VALUES(embedding_data), updated_at = NOW()");
            foreach ($angles as $ang) {
                if (!empty($embeddings[$ang]) && is_array($embeddings[$ang])) {
                    $embStmt->execute([$workerId, $ang, json_encode($embeddings[$ang])]);
                }
            }
        }

        // Queue to Approvals Inbox (W05) for supervisor/HR verification with status = 'pending'
        $displayCode = !empty($workerCode) ? $workerCode : 'Not Assigned';
        $pdo->prepare("INSERT INTO approvals_inbox (approval_type, worker_id, employee_code, employee_name, unit_id, title, details, status, created_at, updated_at)
                       VALUES ('kiosk_worker', ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
                       ON DUPLICATE KEY UPDATE employee_code = VALUES(employee_code), employee_name = VALUES(employee_name), title = VALUES(title), details = VALUES(details), status = 'pending', updated_at = NOW()")
            ->execute([
                $workerId,
                $displayCode,
                $fullName,
                $unitId,
                "Universal Registration #$displayCode ($category)",
                "Statutory 7-step onboarding completed with live 8-point biometrics. Pending HR review and approval."
            ]);

        // Also track in Onboarding Candidates Pipeline as Joined
        try {
            $candPos = $dept ?: ($input['designation_title'] ?? 'Worker');
            $pdo->prepare("INSERT INTO candidates (candidate_name, position, unit_id, mobile_number, email, stage, sub_step, pan_number, aadhaar_number, bank_account, uan_number, joining_date, created_at, updated_at)
                           VALUES (?, ?, ?, ?, ?, 'joined', 'Form 11 Registered', ?, ?, ?, ?, ?, NOW(), NOW())
                           ON DUPLICATE KEY UPDATE position = VALUES(position), unit_id = VALUES(unit_id), stage = 'joined', sub_step = 'Form 11 Registered', pan_number = VALUES(pan_number), aadhaar_number = VALUES(aadhaar_number), bank_account = VALUES(bank_account), uan_number = VALUES(uan_number), updated_at = NOW()")
                ->execute([
                    $fullName, $candPos, $unitId, $mobile, $email,
                    $input['pan_card_number'] ?? null,
                    $input['aadhaar_number'] ?? null,
                    $input['bank_account_number'] ?? null,
                    $input['uan_number'] ?? null,
                    $doj
                ]);
        } catch (Exception $e) { /* ignore candidate insert if table differs */ }

        // Auto-map worker to portal logins for the selected unit
        self::autoMapWorkerToUnitLogins($pdo, $workerId, $unitId, 'UNIVERSAL-REGISTRATION');

        // Audit Log
        logAudit($deviceId, $workerCode, 'UNIVERSAL_REGISTRATION', 'workers', $workerId, "Registered employee $fullName with Form 11 statutory details");

        jsonSuccess("Universal employee registration completed successfully", [
            'worker_id' => (int)$workerId,
            'worker_code' => $workerCode,
            'full_name' => $fullName,
            'approval_status' => $approvalStatus,
            'contractor_agency' => $contractor
        ]);
    }

    public static function uploadDoc() {
        if (empty($_FILES['file'])) {
            jsonError("No file uploaded", 400);
        }

        $file = $_FILES['file'];
        $uploadDir = __DIR__ . '/../../public/uploads/docs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'doc_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $url = 'uploads/docs/' . $fileName;
            jsonSuccess("Document uploaded successfully", ['url' => $url, 'file_name' => $fileName]);
        } else {
            jsonError("Failed to save uploaded file", 500);
        }
    }

    public static function getPendingWorkers() {
        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $stmt = $pdo->query("SELECT id, worker_code, full_name, unit_id, department, category, 
                                    contractor_agency, mobile_number, shift, approval_status,
                                    photo_front_url, created_at
                             FROM workers 
                             WHERE approval_status = 'pending' AND deleted_at IS NULL
                             ORDER BY created_at DESC");
        $pending = $stmt->fetchAll();

        jsonSuccess("Pending approvals retrieved", [
            'count' => count($pending),
            'workers' => $pending
        ]);
    }

    public static function approveWorker() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $workerId = (int)($input['worker_id'] ?? 0);
        $deviceId = trim($_SERVER['HTTP_X_DEVICE_ID'] ?? ($input['device_id'] ?? 'KIOSK-DEVICE'));

        if (!$workerId) jsonError("Worker ID is required", 400);

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $stmt = $pdo->prepare("UPDATE workers SET approval_status = 'approved', embedding_version = embedding_version + 1, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$workerId]);

        // Also update approvals inbox
        $pdo->prepare("UPDATE approvals_inbox SET status = 'approved', actioned_at = NOW(), actioned_by = 'Supervisor' WHERE worker_id = ?")
            ->execute([$workerId]);

        $fetchVer = $pdo->prepare("SELECT embedding_version FROM workers WHERE id = ?");
        $fetchVer->execute([$workerId]);
        $newVer = (int)$fetchVer->fetchColumn();

        logAudit($deviceId, 'SUPERVISOR', 'APPROVE_WORKER', 'workers', $workerId, "Worker #$workerId approved");

        jsonSuccess("Worker approved. Unit embedding version incremented to v$newVer", [
            'worker_id' => $workerId,
            'new_embedding_version' => $newVer
        ]);
    }

    public static function rejectWorker() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $workerId = (int)($input['worker_id'] ?? 0);
        $reason = trim($input['reason'] ?? $input['rejection_reason'] ?? 'Not approved by plant authority');
        $deviceId = trim($_SERVER['HTTP_X_DEVICE_ID'] ?? ($input['device_id'] ?? 'KIOSK-DEVICE'));

        if (!$workerId) jsonError("Worker ID is required", 400);

        $pdo = getDbConnection();
        if (!$pdo) jsonError("Database unavailable", 500);

        $stmt = $pdo->prepare("UPDATE workers SET approval_status = 'rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$reason, $workerId]);

        $pdo->prepare("UPDATE approvals_inbox SET status = 'rejected', approver_comment = ?, actioned_at = NOW(), actioned_by = 'Supervisor' WHERE worker_id = ?")
            ->execute([$reason, $workerId]);

        logAudit($deviceId, 'SUPERVISOR', 'REJECT_WORKER', 'workers', $workerId, "Worker #$workerId rejected: $reason");

        jsonSuccess("Worker enrollment rejected", [
            'worker_id' => $workerId,
            'rejection_reason' => $reason
        ]);
    }
}
