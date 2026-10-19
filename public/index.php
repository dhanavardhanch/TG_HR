<?php
// If running via PHP CLI built-in web server, serve existing static files directly
if (php_sapi_name() === 'cli-server') {
    $staticFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_file($staticFile)) {
        return false;
    }
}

/**
 * Troogood HRMS Master Router & REST API Gateway
 * Supports Flutter Mobile Kiosk Client & Web Management Panel
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/SyncController.php';
require_once __DIR__ . '/../app/Controllers/PunchController.php';
require_once __DIR__ . '/../app/Controllers/EnrollmentController.php';
require_once __DIR__ . '/../app/Controllers/WebPortalController.php';
require_once __DIR__ . '/../app/Controllers/CoreAccessController.php';
require_once __DIR__ . '/../app/Controllers/IssueController.php';
require_once __DIR__ . '/../app/Controllers/PeopleController.php';
require_once __DIR__ . '/../app/Controllers/MasterController.php';
require_once __DIR__ . '/../app/Controllers/HolidayController.php';
require_once __DIR__ . '/../app/Controllers/LogController.php';
require_once __DIR__ . '/../app/Controllers/NotificationController.php';
require_once __DIR__ . '/../app/Controllers/LeaveController.php';
require_once __DIR__ . '/../app/Controllers/CandidateController.php';
require_once __DIR__ . '/../app/Controllers/SmtpController.php';


// Initialize Global CORS and Request Headers
initApiHeaders();

// Parse Request URI
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($rawUri, PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Normalize URI (remove duplicate slashes or trailing slashes)
$uri = rtrim($uri, '/');
if (empty($uri)) $uri = '/';

// API Root / Gateway Health Check
if ($uri === '/api' || $uri === '/api/health') {
    jsonSuccess("TrooGood HRMS API Gateway is online and active", [
        'version' => '2.1.0',
        'status' => 'operational',
        'database' => 'connected',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// -----------------------------------------------------------------------------
// STANDALONE CANDIDATE ONBOARDING (Clean standalone form, decoupled from HRMS)
// -----------------------------------------------------------------------------
if (in_array($uri, ['/candidate-onboarding', '/candidate_onboarding', '/candidate_onboarding.php', '/candidate-onboarding.php'])) {
    include __DIR__ . '/views/candidate_onboarding.php';
    exit();
}

// -----------------------------------------------------------------------------
// WEB UI ROUTES (Wireframes W01 through W07 & Phase 1 Core Modules)
// -----------------------------------------------------------------------------
$webViews = ['/', '/dashboard', '/people', '/people-master', '/person-detail', '/attendance', '/onboarding', '/approvals', '/payroll', '/roles', '/units-sync', '/audit-log', '/portal-users', '/kiosk-management', '/shifts', '/masters', '/holidays', '/leave-calculator', '/error-logs', '/notifications'];
if (in_array($uri, $webViews)) {
    include __DIR__ . '/views/portal.php';
    exit();
}

// -----------------------------------------------------------------------------
// SYSTEM HEALTH CHECK
// -----------------------------------------------------------------------------
if ($uri === '/api/health') {
    $pdo = getDbConnection();
    $env = loadEnv();
    jsonResponse([
        "status" => "online",
        "app" => "TrooGood HRMS API Gateway",
        "version" => "2.1.0",
        "environment" => $env['APP_ENV'] ?? 'local',
        "database" => $env['DB_DATABASE'] ?? 'Troogood_HR_APP',
        "db_status" => $pdo ? "connected" : "disconnected",
        "mobile_biometrics" => "MobileFaceNet ArcFace 192-d",
        "timestamp" => date('c')
    ]);
}

// -----------------------------------------------------------------------------
// ERP FACTORIES ROSTER API & SYNC
// -----------------------------------------------------------------------------
if ($uri === '/api/factories/troogood' && $method === 'GET') {
    $pdo = getDbConnection();
    $units = $pdo->query("SELECT id, unit_name, unit_code, city, state, is_active FROM units WHERE is_active = 1 AND deleted_at IS NULL ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    jsonSuccess("TrooGood Factory Units Retrieved", [
        'factories' => $units,
        'count' => count($units)
    ]);
}

if (($uri === '/api/units/sync' || $uri === '/api/erp/sync-units') && $method === 'POST') {
    CoreAccessController::syncUnits();
}

if (($uri === '/api/units/sync-logs' || $uri === '/api/units/logs') && $method === 'GET') {
    CoreAccessController::getUnitSyncLogs();
}

// -----------------------------------------------------------------------------
// 1. AUTHENTICATION & HARDWARE DEVICE LOCK
// -----------------------------------------------------------------------------
// POST /auth/login.php OR /api/auth/login.php OR /api/v1/auth/login
if (($uri === '/auth/login.php' || $uri === '/api/auth/login.php' || $uri === '/api/v1/auth/login') && $method === 'POST') {
    AuthController::login();
}

// POST /auth/verify_device.php OR /api/auth/verify_device.php OR /api/v1/kiosk/verify-device
if (($uri === '/auth/verify_device.php' || $uri === '/api/auth/verify_device.php' || $uri === '/api/v1/kiosk/verify-device') && $method === 'POST') {
    AuthController::verifyDevice();
}

// POST /auth/release_device.php OR /api/auth/release_device.php OR /api/v1/kiosk/release-device
if (($uri === '/auth/release_device.php' || $uri === '/api/auth/release_device.php' || $uri === '/api/v1/kiosk/release-device') && $method === 'POST') {
    AuthController::releaseDevice();
}

// POST /auth/logout.php OR /api/auth/logout.php OR /api/v1/auth/logout
if (($uri === '/auth/logout.php' || $uri === '/api/auth/logout.php' || $uri === '/api/v1/auth/logout') && $method === 'POST') {
    AuthController::logout();
}

// -----------------------------------------------------------------------------
// 2. WORKER ROSTER & 192-D BIOMETRIC EMBEDDINGS SYNC
// -----------------------------------------------------------------------------
// GET /kiosk/sync.php OR /api/kiosk/sync.php OR /api/v1/workers/sync_roster.php OR /api/v1/sync/employees
if (($uri === '/kiosk/sync.php' || $uri === '/api/kiosk/sync.php' || $uri === '/api/v1/workers/sync_roster.php' || $uri === '/api/v1/sync/employees') && $method === 'GET') {
    SyncController::syncWorkers();
}

// GET /embeddings/delta.php OR /api/embeddings/delta.php
if (($uri === '/embeddings/delta.php' || $uri === '/api/embeddings/delta.php') && $method === 'GET') {
    SyncController::deltaEmbeddings();
}

// -----------------------------------------------------------------------------
// 3. OFFLINE ATTENDANCE PUNCHES QUEUE & BULK SYNC
// -----------------------------------------------------------------------------
// POST /punches/bulk.php OR /api/punches/bulk.php OR /api/v1/attendance/punch_sync.php OR /api/v1/attendance/batch-sync
if (($uri === '/punches/bulk.php' || $uri === '/api/punches/bulk.php' || $uri === '/api/v1/attendance/punch_sync.php' || $uri === '/api/v1/attendance/batch-sync') && $method === 'POST') {
    PunchController::bulkPunches();
}

// POST /punches/record.php OR /api/punches/record.php OR /api/v1/attendance/punch
if (($uri === '/punches/record.php' || $uri === '/api/punches/record.php' || $uri === '/api/v1/attendance/punch') && $method === 'POST') {
    PunchController::singlePunch();
}

// -----------------------------------------------------------------------------
// 4. WORKER BIOMETRIC ENROLLMENT & 7-STEP STATUTORY ONBOARDING
// -----------------------------------------------------------------------------
// POST /employees/enroll.php OR /api/employees/enroll.php OR /api/v1/kiosk/enroll-worker
if (($uri === '/employees/enroll.php' || $uri === '/api/employees/enroll.php' || $uri === '/api/v1/kiosk/enroll-worker' || $uri === '/api/enrollment/enroll') && $method === 'POST') {
    EnrollmentController::enrollWorker();
}

// POST /api/v1/enrollment/universal_register.php (7-step EPFO Form 11)
if (($uri === '/api/v1/enrollment/universal_register.php' || $uri === '/enrollment/universal_register.php' || $uri === '/api/v1/enrollment/register') && $method === 'POST') {
    EnrollmentController::universalRegister();
}

// POST /api/v1/enrollment/upload_doc.php
if (($uri === '/api/v1/enrollment/upload_doc.php' || $uri === '/enrollment/upload_doc.php') && $method === 'POST') {
    EnrollmentController::uploadDoc();
}

// GET /employees/pending.php OR /api/employees/pending.php OR /api/v1/supervisor/pending_approvals.php
if (($uri === '/employees/pending.php' || $uri === '/api/employees/pending.php' || $uri === '/api/v1/supervisor/pending_approvals.php') && $method === 'GET') {
    EnrollmentController::getPendingWorkers();
}

// POST /employees/approve.php OR /api/employees/approve.php OR /api/v1/supervisor/approve.php
if (($uri === '/employees/approve.php' || $uri === '/api/employees/approve.php' || $uri === '/api/v1/supervisor/approve.php') && $method === 'POST') {
    EnrollmentController::approveWorker();
}

// GET/POST /api/workers/check_mobile.php OR /api/v1/workers/check_mobile.php
if (($uri === '/api/workers/check_mobile.php' || $uri === '/api/v1/workers/check_mobile.php' || $uri === '/workers/check_mobile.php' || $uri === '/api/workers/check-mobile') && ($method === 'GET' || $method === 'POST')) {
    EnrollmentController::checkMobile();
}

// GET /api/contractors OR /api/v1/contractors
if (($uri === '/api/contractors' || $uri === '/api/v1/contractors' || $uri === '/contractors') && $method === 'GET') {
    EnrollmentController::getContractors();
}

// POST /employees/reject.php OR /api/employees/reject.php OR /api/v1/supervisor/reject.php
if (($uri === '/employees/reject.php' || $uri === '/api/employees/reject.php' || $uri === '/api/v1/supervisor/reject.php') && $method === 'POST') {
    EnrollmentController::rejectWorker();
}

// -----------------------------------------------------------------------------
// 5. AUDIT LOGGING
// -----------------------------------------------------------------------------
if (($uri === '/audit/log.php' || $uri === '/api/audit/log.php') && $method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    logAudit(
        $input['device_id'] ?? null,
        $input['user_identifier'] ?? null,
        $input['action'] ?? 'CLIENT_AUDIT',
        $input['entity_type'] ?? 'client',
        $input['entity_id'] ?? '0',
        $input['details'] ?? null
    );
    jsonSuccess("Audit log recorded");
}

// -----------------------------------------------------------------------------
// 5B. HELPDESK & SUPPORT ISSUES
// -----------------------------------------------------------------------------
if (($uri === '/issues/create.php' || $uri === '/api/issues/create.php' || $uri === '/api/v1/issues/create') && $method === 'POST') {
    IssueController::createIssue();
}

if (($uri === '/issues/list.php' || $uri === '/api/issues/list.php' || $uri === '/api/v1/issues/list') && $method === 'GET') {
    IssueController::listIssues();
}

if (($uri === '/issues/update.php' || $uri === '/api/issues/update.php') && $method === 'POST') {
    IssueController::updateIssue();
}

// -----------------------------------------------------------------------------
// 6. PORTAL REST APIs (FOR WIREFRAME VIEWS W01 - W07)
// -----------------------------------------------------------------------------
if ($uri === '/api/portal/dashboard' && $method === 'GET') {
    WebPortalController::getDashboardData();
}

if ($uri === '/api/portal/attendance-matrix' && $method === 'GET') {
    WebPortalController::getAttendanceMatrix();
}

if ($uri === '/api/portal/attendance-override' && $method === 'POST') {
    WebPortalController::overrideAttendanceCell();
}

if ($uri === '/api/portal/attendance-import' && $method === 'POST') {
    WebPortalController::importAttendanceCsv();
}

if ($uri === '/api/portal/onboarding-pipeline' && $method === 'GET') {
    WebPortalController::getOnboardingPipeline();
}

if (preg_match('#^/api/portal/candidate/(\d+)$#', $uri, $matches) && $method === 'GET') {
    WebPortalController::getCandidateVerification((int)$matches[1]);
}

if ($uri === '/api/portal/candidate-doc-verify' && $method === 'POST') {
    WebPortalController::updateDocVerification();
}

if ($uri === '/api/portal/approvals' && $method === 'GET') {
    WebPortalController::getApprovalsInbox();
}

if ($uri === '/api/portal/approval-action' && $method === 'POST') {
    WebPortalController::actionApprovalItem();
}

if ($uri === '/api/portal/payroll' && $method === 'GET') {
    WebPortalController::getPayrollRun();
}

if (($uri === '/api/portal/roles' || $uri === '/api/acl/matrix') && $method === 'GET') {
    CoreAccessController::getAclMatrix();
}
if ($uri === '/api/acl/matrix/save' && $method === 'POST') {
    CoreAccessController::saveAclMatrix();
}
if ($uri === '/api/acl/role/create' && $method === 'POST') {
    CoreAccessController::createRole();
}
if ($uri === '/api/acl/role/delete' && $method === 'POST') {
    CoreAccessController::deleteRole();
}
if ($uri === '/api/acl/role/clone' && $method === 'POST') {
    CoreAccessController::cloneRole();
}

// Immutable Audit Log
if ($uri === '/api/audit/timeline' && $method === 'GET') {
    CoreAccessController::getAuditTimeline();
}

// Portal Users Management
if ($uri === '/api/portal/users' && $method === 'GET') {
    CoreAccessController::getPortalUsers();
}
if ($uri === '/api/portal/user/status' && $method === 'POST') {
    CoreAccessController::toggleUserStatus();
}
if ($uri === '/api/portal/user/delete' && $method === 'POST') {
    CoreAccessController::deleteUser();
}
if ($uri === '/api/portal/user/unit-access' && $method === 'POST') {
    CoreAccessController::saveUserUnitAccess();
}
if ($uri === '/api/portal/user/reset-password' && $method === 'POST') {
    CoreAccessController::resetUserPassword();
}
if ($uri === '/api/portal/user/create' && $method === 'POST') {
    CoreAccessController::createUser();
}
if ($uri === '/api/portal/user/update' && $method === 'POST') {
    CoreAccessController::updateUser();
}
if ($uri === '/api/portal/user/release-device' && $method === 'POST') {
    CoreAccessController::releaseDeviceLock();
}
if ($uri === '/api/portal/devices' && $method === 'GET') {
    CoreAccessController::getKioskDevices();
}
if ($uri === '/api/portal/devices/toggle' && $method === 'POST') {
    CoreAccessController::toggleKioskDevice();
}
if ($uri === '/api/portal/devices/delete' && $method === 'POST') {
    CoreAccessController::deleteKioskDevice();
}
if ($uri === '/api/portal/auth/login' && $method === 'POST') {
    CoreAccessController::portalLogin();
}

// -----------------------------------------------------------------------------
// SHIFT MASTER (IST Shift Timings & Dynamic Attendance Allocation)
// -----------------------------------------------------------------------------
if (($uri === '/api/shifts' || $uri === '/api/v1/shifts') && $method === 'GET') {
    WebPortalController::getShifts();
}
if (($uri === '/api/shifts/save' || $uri === '/api/v1/shifts/save') && $method === 'POST') {
    WebPortalController::saveShift();
}
if (($uri === '/api/shifts/toggle' || $uri === '/api/v1/shifts/toggle') && $method === 'POST') {
    WebPortalController::toggleShiftStatus();
}

// -----------------------------------------------------------------------------
// PEOPLE MASTER (Workforce Directory, Full Profile, Documents, Assign, Transfer, Zip Export)
// -----------------------------------------------------------------------------
if ($uri === '/api/people' && $method === 'GET') {
    PeopleController::listPeople();
}
if ($uri === '/api/people/update' && $method === 'POST') {
    PeopleController::updatePerson();
}
if ($uri === '/api/people/assign' && $method === 'POST') {
    PeopleController::assignLogins();
}
if ($uri === '/api/people/transfer' && $method === 'POST') {
    PeopleController::transferUnit();
}
if ($uri === '/api/people/download' && $method === 'GET') {
    PeopleController::downloadZip();
}
if (preg_match('#^/api/people/(\d+)/upload-doc$#', $uri, $matches) && $method === 'POST') {
    PeopleController::uploadDoc((int)$matches[1]);
}
if (preg_match('#^/api/people/(\d+)/form11-pdf$#', $uri, $matches) && $method === 'GET') {
    PeopleController::downloadForm11Pdf((int)$matches[1]);
}
if (preg_match('#^/api/people/(\d+)$#', $uri, $matches) && $method === 'GET') {
    PeopleController::getPerson((int)$matches[1]);
}

// -----------------------------------------------------------------------------
// MASTERS MANAGEMENT & MOBILE SYNC (Departments, Designations, Contractors, Managers, Banks)
// -----------------------------------------------------------------------------
if ($uri === '/api/masters/registration-masters' && $method === 'GET') {
    MasterController::getRegistrationMasters();
}
if ($uri === '/api/masters/list' && $method === 'GET') {
    MasterController::getMasterList();
}
if ($uri === '/api/masters/save' && $method === 'POST') {
    MasterController::saveMasterItem();
}
if ($uri === '/api/masters/delete' && $method === 'POST') {
    MasterController::deleteMasterItem();
}
if ($uri === '/api/masters/export' && $method === 'GET') {
    MasterController::exportCsv();
}
if ($uri === '/api/masters/import' && $method === 'POST') {
    MasterController::importCsv();
}

// -----------------------------------------------------------------------------
// HOLIDAY CALENDAR (Full Year Master, CSV Template Download & Upload)
// -----------------------------------------------------------------------------
if (($uri === '/api/holidays' || $uri === '/api/holidays/list' || $uri === '/api/v1/holidays') && $method === 'GET') {
    HolidayController::listHolidays();
}
if (($uri === '/api/holidays/save' || $uri === '/api/v1/holidays/save') && $method === 'POST') {
    HolidayController::saveHoliday();
}
if (($uri === '/api/holidays/delete' || $uri === '/api/v1/holidays/delete') && $method === 'POST') {
    HolidayController::deleteHoliday();
}
if (($uri === '/api/holidays/template' || $uri === '/api/v1/holidays/template') && $method === 'GET') {
    HolidayController::downloadTemplate();
}
if (($uri === '/api/holidays/upload' || $uri === '/api/v1/holidays/upload') && $method === 'POST') {
    HolidayController::uploadHolidays();
}

// -----------------------------------------------------------------------------
// SYSTEM ERROR LOGS (Mobile App, Web Portal, API Gateway & Sync Error Tracking)
// -----------------------------------------------------------------------------
if (($uri === '/api/logs/error' || $uri === '/api/v1/logs/error') && $method === 'POST') {
    LogController::logError();
}
if (($uri === '/api/logs/list' || $uri === '/api/v1/logs/list') && $method === 'GET') {
    LogController::listLogs();
}
if (($uri === '/api/logs/clear' || $uri === '/api/v1/logs/clear') && $method === 'POST') {
    LogController::clearLogs();
}

// -----------------------------------------------------------------------------
// NOTIFICATIONS SYSTEM (In-App Kiosk Bell & Web Management Broadcast)
// -----------------------------------------------------------------------------
if (($uri === '/api/notifications/active' || $uri === '/api/v1/notifications/active' || $uri === '/api/api/notifications/active') && $method === 'GET') {
    NotificationController::getActiveNotifications();
}
if (($uri === '/api/notifications/mark-read' || $uri === '/api/v1/notifications/mark-read' || $uri === '/api/api/notifications/mark-read') && $method === 'POST') {
    NotificationController::markAsRead();
}
if (($uri === '/api/notifications/broadcast' || $uri === '/api/v1/notifications/broadcast' || $uri === '/api/api/notifications/broadcast') && $method === 'POST') {
    NotificationController::broadcastNotification();
}

// -----------------------------------------------------------------------------
// LEAVE CALCULATOR (Sunday/Holiday Duty Comp-Off + 1.5 Accrual - Leaves Taken)
// -----------------------------------------------------------------------------
if (($uri === '/api/portal/leave-calculator' || $uri === '/api/leave/calculator') && $method === 'GET') {
    LeaveController::getLeaveCalculator();
}
if (($uri === '/api/portal/leave-export' || $uri === '/api/leave/export') && $method === 'GET') {
    LeaveController::exportCsv();
}

// -----------------------------------------------------------------------------
// CANDIDATE ONBOARDING & VERIFICATION PIPELINE
// -----------------------------------------------------------------------------
if ($uri === '/api/candidates/invite' && $method === 'POST') {
    CandidateController::inviteCandidate();
}
if ($uri === '/api/candidates/verify-token' && $method === 'GET') {
    CandidateController::getCandidateByToken();
}
if ($uri === '/api/candidates/submit-form' && $method === 'POST') {
    CandidateController::submitCandidateForm();
}
if ($uri === '/api/candidates/verify-and-offer' && $method === 'POST') {
    CandidateController::releaseOffer();
}
if ($uri === '/api/candidates/approve-to-worker' && $method === 'POST') {
    CandidateController::approveToWorker();
}

// -----------------------------------------------------------------------------
// PEOPLE MASTER ATTENDANCE APP MODE (Kiosk vs Individual Access Credentials)
// -----------------------------------------------------------------------------
if ($uri === '/api/people/update-app-mode' && $method === 'POST') {
    PeopleController::updateAppMode();
}

// -----------------------------------------------------------------------------
// OUTBOUND MAIL SERVER (SMTP) SETTINGS & REPORT TRIGGERS
// -----------------------------------------------------------------------------
if ($uri === '/api/portal/smtp-settings' && $method === 'GET') {
    SmtpController::getSettings();
}
if ($uri === '/api/portal/smtp-settings' && $method === 'POST') {
    SmtpController::saveSettings();
}
if (($uri === '/api/portal/smtp-settings/test' || $uri === '/api/smtp/test') && $method === 'POST') {
    SmtpController::testConnection();
}
if (($uri === '/api/reports/daily-operations-email' || $uri === '/api/portal/daily-operations-email') && $method === 'POST') {
    SmtpController::triggerDailyReport();
}

// -----------------------------------------------------------------------------
// 404 NOT FOUND FALLBACK
// -----------------------------------------------------------------------------
jsonError("Endpoint not found: $method $uri", 404);

