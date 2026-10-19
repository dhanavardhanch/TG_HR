<?php
/**
 * CandidateController - Standalone Candidate Onboarding, Invites, and Verification Flow
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../Helpers/MailHelper.php';

class CandidateController {

    public static function inviteCandidate() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $name = trim($input['candidate_name'] ?? $input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = trim($input['app_password'] ?? $input['password'] ?? 'TrooGood@2026');
        $unit = trim($input['unit_id'] ?? $input['unit_code'] ?? 'TGK-1');
        $dept = trim($input['department'] ?? 'Production');
        $designation = trim($input['designation'] ?? 'Operator');
        $mobile = trim($input['mobile'] ?? $input['phone'] ?? '');
        $source = trim($input['source'] ?? 'onboarding');

        if (empty($name) || empty($email)) {
            jsonError("Candidate name and email are required", 422);
        }

        $token = bin2hex(random_bytes(24));
        $pdo = getDbConnection();

        // Check if candidates table has column stage or status
        $cols = $pdo->query("SHOW COLUMNS FROM candidates")->fetchAll(PDO::FETCH_COLUMN);
        $stageCol = in_array('stage', $cols) ? 'stage' : 'status';

        // Check if a candidate record with this email already exists to prevent duplicate cards
        $existStmt = $pdo->prepare("SELECT id, $stageCol FROM candidates WHERE email = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1");
        $existStmt->execute([$email]);
        $existing = $existStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $candId = (int)$existing['id'];
            $upStmt = $pdo->prepare("UPDATE candidates SET 
                candidate_name = ?, 
                mobile_number = COALESCE(NULLIF(?, ''), mobile_number),
                invite_token = ?, 
                app_password = ?, 
                unit_id = ?, 
                department = ?, 
                position = ?, 
                $stageCol = 'invited', 
                updated_at = NOW() 
                WHERE id = ?");
            $upStmt->execute([$name, $mobile, $token, $password, $unit, $dept, $designation, $candId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO candidates 
                (candidate_name, email, mobile_number, invite_token, app_password, unit_id, department, position, $stageCol, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'invited', NOW(), NOW())");
            $stmt->execute([$name, $email, $mobile, $token, $password, $unit, $dept, $designation]);
            $candId = (int)$pdo->lastInsertId();
        }

        $inviteUrl = "/candidate-onboarding?token=" . $token;

        logAudit('WEB_PORTAL', 'HR Admin', 'INVITE_CANDIDATE', 'candidates', $candId, [
            'name' => $name, 'email' => $email, 'unit' => $unit, 'source' => $source
        ]);

        // Trigger automated outbound email invitation via TrooGood SMTP
        $mailSent = false;
        try {
            $mailResult = MailHelper::sendCandidateInvite($email, $name, $designation ?: $dept, $unit, $inviteUrl, $password, $source);
            $mailSent = !empty($mailResult['success']);
        } catch (Exception $e) {
            error_log("Failed to dispatch candidate invite email: " . $e->getMessage());
        }

        $isPeople = ($source === 'people');
        $succMsg = ($isPeople ? "Candidate registered & App credentials generated" : "Candidate invited successfully") . ($mailSent ? " & invitation email dispatched" : "");

        jsonSuccess($succMsg, [
            'candidate_id' => $candId,
            'invite_token' => $token,
            'invite_url' => $inviteUrl,
            'candidate_name' => $name,
            'email' => $email,
            'source' => $source,
            'mail_sent' => $mailSent
        ]);
    }

    public static function getCandidateByToken() {
        $token = trim($_GET['token'] ?? '');
        if (empty($token)) {
            jsonError("Invite token is required", 422);
        }

        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE invite_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $cand = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cand) {
            jsonError("Invalid or expired invitation token. Please contact HR.", 404);
        }

        // Fetch master dropdowns
        $depts = $pdo->query("SELECT id, department_name AS name FROM departments WHERE is_active = 1 ORDER BY department_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $desigs = $pdo->query("SELECT id, department_id, designation_name AS name FROM designations WHERE is_active = 1 ORDER BY designation_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $contractors = $pdo->query("SELECT id, contractor_name AS agency_name, contractor_name AS name FROM contractors WHERE is_active = 1 ORDER BY contractor_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $units = $pdo->query("SELECT id, unit_name, unit_code, city FROM units WHERE is_active = 1 AND deleted_at IS NULL ORDER BY unit_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $banks = $pdo->query("SELECT id, bank_name FROM banks WHERE is_active = 1 ORDER BY bank_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess("Candidate details verified", [
            'candidate' => $cand,
            'masters' => [
                'departments' => $depts,
                'designations' => $desigs,
                'contractors' => $contractors,
                'units' => $units,
                'banks' => $banks
            ]
        ]);
    }

    public static function submitCandidateForm() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $token = trim($input['token'] ?? $input['invite_token'] ?? '');

        if (empty($token)) {
            jsonError("Invitation token is required", 422);
        }

        $pdo = getDbConnection();
        $stmt = $pdo->prepare("SELECT id, candidate_name, email, unit_id, department, position FROM candidates WHERE invite_token = ? LIMIT 1");
        $stmt->execute([$token]);
        $cand = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cand) {
            jsonError("Candidate not found for this invitation token", 404);
        }

        $candId = (int)$cand['id'];
        $cols = $pdo->query("SHOW COLUMNS FROM candidates")->fetchAll(PDO::FETCH_COLUMN);
        $stageCol = in_array('stage', $cols) ? 'stage' : 'status';

        $submissionJson = json_encode($input, JSON_UNESCAPED_UNICODE);
        $updatedName = trim($input['candidate_name'] ?? $input['full_name'] ?? $input['cand_name'] ?? $input['name'] ?? '');
        $updatedEmail = trim($input['email'] ?? $input['cand_email'] ?? '');
        $updatedUnit = trim($input['unit_id'] ?? '');
        $updatedDept = trim($input['department'] ?? '');
        $updatedPos = trim($input['role_designation'] ?? $input['position'] ?? $input['designation'] ?? '');
        $mobile = trim($input['mobile_number'] ?? $input['mobile'] ?? $input['phone'] ?? '');

        $upStmt = $pdo->prepare("UPDATE candidates 
                                 SET submission_data = ?, 
                                     candidate_name = COALESCE(NULLIF(?, ''), candidate_name),
                                     email = COALESCE(NULLIF(?, ''), email),
                                     mobile_number = COALESCE(NULLIF(?, ''), mobile_number), 
                                     unit_id = COALESCE(NULLIF(?, ''), unit_id),
                                     department = COALESCE(NULLIF(?, ''), department),
                                     position = COALESCE(NULLIF(?, ''), position),
                                     $stageCol = 'details_submitted', 
                                     updated_at = NOW() 
                                 WHERE id = ?");
        $upStmt->execute([$submissionJson, $updatedName, $updatedEmail, $mobile, $updatedUnit, $updatedDept, $updatedPos, $candId]);

        // Also add or update candidate_documents if attachments provided
        if (!empty($input['documents']) && is_array($input['documents'])) {
            $docStmt = $pdo->prepare("INSERT INTO candidate_documents (candidate_id, doc_type, doc_name, file_url, verification_status, uploaded_at)
                                      VALUES (?, ?, ?, ?, 'pending', NOW())");
            foreach ($input['documents'] as $doc) {
                $docStmt->execute([$candId, $doc['type'] ?? 'ID_PROOF', $doc['name'] ?? 'Document', $doc['url'] ?? '']);
            }
        }

        $finalName = !empty($updatedName) ? $updatedName : $cand['candidate_name'];
        $finalEmail = !empty($updatedEmail) ? $updatedEmail : $cand['email'];
        $finalPos = !empty($updatedPos) ? $updatedPos : ($cand['position'] ?? 'New Joiner');
        $finalUnit = !empty($updatedUnit) ? $updatedUnit : ($cand['unit_id'] ?? 'TrooGood Facility');

        logAudit('CANDIDATE_PORTAL', $finalName, 'SUBMIT_ONBOARDING_FORM', 'candidates', $candId, [
            'name' => $finalName,
            'email' => $finalEmail,
            'stage' => 'details_submitted'
        ]);

        // Trigger confirmation email to candidate
        try {
            MailHelper::sendCandidateSubmissionConfirmation(
                $finalEmail, 
                $finalName, 
                $finalPos, 
                $finalUnit
            );
        } catch (Exception $e) {
            error_log("Failed to dispatch onboarding confirmation email: " . $e->getMessage());
        }

        jsonSuccess("Onboarding details submitted successfully. HR will review your documents and release your offer.", [
            'candidate_id' => $candId,
            'status' => 'details_submitted'
        ]);
    }

    public static function releaseOffer() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $candId = (int)($input['candidate_id'] ?? 0);
        $salary = trim($input['offer_salary'] ?? '');
        $letterUrl = trim($input['offer_letter_url'] ?? '');
        $notes = trim($input['hr_notes'] ?? '');

        if (!$candId) jsonError("Candidate ID is required", 422);

        $pdo = getDbConnection();
        $cols = $pdo->query("SHOW COLUMNS FROM candidates")->fetchAll(PDO::FETCH_COLUMN);
        $stageCol = in_array('stage', $cols) ? 'stage' : 'status';

        $cStmt = $pdo->prepare("SELECT candidate_name, email, unit_id, department, position FROM candidates WHERE id = ?");
        $cStmt->execute([$candId]);
        $cand = $cStmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("UPDATE candidates 
                               SET offer_salary = ?, offer_letter_url = ?, hr_notes = ?, $stageCol = 'offer_released', updated_at = NOW() 
                               WHERE id = ?");
        $stmt->execute([$salary, $letterUrl, $notes, $candId]);

        logAudit('WEB_PORTAL', 'HR Admin', 'RELEASE_OFFER', 'candidates', $candId, ['salary' => $salary]);

        // Trigger offer release notification email
        if ($cand && !empty($cand['email'])) {
            try {
                MailHelper::sendOfferLetterRelease(
                    $cand['email'],
                    $cand['candidate_name'],
                    $cand['position'] ?? 'Employee',
                    $cand['unit_id'] ?? 'TrooGood Facility',
                    $salary,
                    $letterUrl,
                    $notes
                );
            } catch (Exception $e) {
                error_log("Failed to dispatch offer release email: " . $e->getMessage());
            }
        }

        jsonSuccess("Offer released to candidate successfully", [
            'candidate_id' => $candId,
            'stage' => 'offer_released'
        ]);
    }

    public static function approveToWorker() {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $candId = (int)($input['candidate_id'] ?? 0);
        $workerCode = trim($input['worker_code'] ?? '');

        if (!$candId) jsonError("Candidate ID is required", 422);
        
        $pdo = getDbConnection();
        $cStmt = $pdo->prepare("SELECT * FROM candidates WHERE id = ? LIMIT 1");
        $cStmt->execute([$candId]);
        $cand = $cStmt->fetch(PDO::FETCH_ASSOC);

        if (!$cand) jsonError("Candidate not found", 404);

        if (!empty($workerCode)) {
            // Check if worker_code already exists
            $wCheck = $pdo->prepare("SELECT COUNT(*) FROM workers WHERE worker_code = ? AND deleted_at IS NULL");
            $wCheck->execute([$workerCode]);
            if ((int)$wCheck->fetchColumn() > 0) {
                jsonError("Worker code #$workerCode is already assigned to an existing employee", 422);
            }
        } else {
            $workerCode = null;
        }

        $sub = !empty($cand['submission_data']) ? json_decode($cand['submission_data'], true) : [];

        $fullName = $sub['full_name'] ?? $cand['candidate_name'];
        $unitId = $sub['unit_id'] ?? $cand['unit_id'] ?? 'TGK-1';
        $department = $sub['department'] ?? $cand['department'] ?? 'Production';
        $designation = $sub['role_designation'] ?? $cand['designation'] ?? 'Operator';
        $mobile = $sub['mobile_number'] ?? $cand['mobile'] ?? '';
        $aadhaar = $sub['aadhaar_number'] ?? '';
        $pan = $sub['pan_card_number'] ?? '';
        $bankName = $sub['bank_name'] ?? '';
        $bankAcc = $sub['bank_account_number'] ?? '';
        $ifsc = $sub['ifsc_code'] ?? '';
        $salary = !empty($cand['offer_salary']) ? (float)str_replace(['₹', ',', ' '], '', $cand['offer_salary']) : null;
        $engagement = $sub['engagement_type'] ?? 'On TrooGood Rolls';
        $agency = $sub['contractor_agency'] ?? '';

        $email = $cand['email'] ?? ($sub['email'] ?? null);

        $wStmt = $pdo->prepare("INSERT INTO workers 
            (worker_code, full_name, unit_id, department, designation, mobile_number, email_address, monthly_salary, contractor_agency, approval_status, attendance_app_mode, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', 'kiosk', NOW(), NOW())");
        $wStmt->execute([
            $workerCode, $fullName, $unitId, $department, $designation, $mobile, $email, $salary, $agency
        ]);
        $workerId = (int)$pdo->lastInsertId();

        // Update candidate stage to joined
        $cols = $pdo->query("SHOW COLUMNS FROM candidates")->fetchAll(PDO::FETCH_COLUMN);
        $stageCol = in_array('stage', $cols) ? 'stage' : 'status';
        $pdo->prepare("UPDATE candidates SET $stageCol = 'joined', updated_at = NOW() WHERE id = ?")->execute([$candId]);

        logAudit('WEB_PORTAL', 'HR Admin', 'APPROVE_CANDIDATE_TO_WORKER', 'workers', $workerId, [
            'candidate_id' => $candId,
            'worker_code' => $workerCode,
            'full_name' => $fullName
        ]);

        jsonSuccess("Candidate approved and added to People Master as #$workerCode", [
            'worker_id' => $workerId,
            'worker_code' => $workerCode,
            'full_name' => $fullName
        ]);
    }
}
