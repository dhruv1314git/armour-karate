<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Admin Student Detail Page
 * app/views/pages/admin/student-detail.php
 *
 * Route: /admin/student?id=X
 *
 * Five-tab dossier:
 *  1. Profile   — view & edit student info
 *  2. Belt History — promotion log + promote form
 *  3. Fee Payments — payment ledger + record payment form
 *  4. Examinations — registrations + results
 *  5. Admin Actions — approve/reject/activate/deactivate
 */

declare(strict_types=1);

requireAdmin();

$pdo     = db();
$adminId = (int) ($_SESSION['user_id'] ?? 0);

// ─── Resolve student profile ──────────────────────────────────────────────────

$profileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$profileId || $profileId < 1) {
    flashSet('error', 'Invalid student ID.');
    safeRedirect(BASE_PATH . '/admin/students');
}

function fetchStudentFull(PDO $pdo, int $profileId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT sp.*, u.email AS user_email, u.role, u.created_at AS account_created,
                b.name AS belt_name, b.color AS belt_color, b.rank AS belt_rank,
                cl.location_name AS preferred_location_name
         FROM student_profiles sp
         JOIN users u ON u.id = sp.user_id
         LEFT JOIN belts b ON b.id = sp.current_belt_id
         LEFT JOIN class_locations cl ON cl.id = sp.preferred_location_id
         WHERE sp.id = ?"
    );
    $stmt->execute([$profileId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$student = fetchStudentFull($pdo, $profileId);
if (!$student) {
    flashSet('error', 'Student profile not found.');
    safeRedirect(BASE_PATH . '/admin/students');
}

// ─── POST Action Dispatcher ───────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfValidate(false); // don't rotate yet — we'll redirect

    $action = trim($_POST['action'] ?? '');

    // ── APPROVE ──────────────────────────────────────────────────────────────
    if ($action === 'approve') {
        if ($student['status'] !== 'PENDING') {
            flashSet('error', 'Student is not in PENDING status.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
        }
        try {
            $pdo->beginTransaction();
            // Lock row
            $pdo->prepare("SELECT id FROM student_profiles WHERE id = ? FOR UPDATE")->execute([$profileId]);
            // Re-check status inside transaction
            $recheck = $pdo->prepare("SELECT status FROM student_profiles WHERE id = ?");
            $recheck->execute([$profileId]);
            $currentStatus = $recheck->fetchColumn();
            if ($currentStatus !== 'PENDING') {
                $pdo->rollBack();
                flashSet('error', 'Student was already processed by another action.');
                safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
            }
            $newStudentId = generateStudentId($pdo);
            $pdo->prepare(
                "UPDATE student_profiles SET student_id = ?, status = 'ACTIVE', updated_at = NOW() WHERE id = ?"
            )->execute([$newStudentId, $profileId]);
            // Audit log inside transaction
            $pdo->prepare(
                "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
                 VALUES ('STUDENT_APPROVED', 'StudentProfile', ?, ?, ?, NOW())"
            )->execute([
                (string) $profileId,
                json_encode(['student_id' => $newStudentId, 'full_name' => $student['full_name']]),
                $adminId,
            ]);
            $pdo->commit();
            flashSet('success', "Student approved. Assigned ID: {$newStudentId}");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            appLog('ERROR', 'Approval failed: ' . $e->getMessage());
            flashSet('error', 'Approval failed. Please try again.');
        }
        safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
    }

    // ── REJECT ───────────────────────────────────────────────────────────────
    if ($action === 'reject') {
        if (!in_array($student['status'], ['PENDING', 'ACTIVE'], true)) {
            flashSet('error', 'Cannot reject a student in current status.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
        }
        $reason = trim($_POST['reason'] ?? '');
        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "UPDATE student_profiles SET status = 'REJECTED', updated_at = NOW() WHERE id = ?"
            )->execute([$profileId]);
            $pdo->prepare(
                "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
                 VALUES ('STUDENT_REJECTED', 'StudentProfile', ?, ?, ?, NOW())"
            )->execute([
                (string) $profileId,
                json_encode(['full_name' => $student['full_name'], 'reason' => $reason ?: 'Not specified']),
                $adminId,
            ]);
            $pdo->commit();
            flashSet('success', 'Student registration rejected.');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            appLog('ERROR', 'Reject failed: ' . $e->getMessage());
            flashSet('error', 'Rejection failed. Please try again.');
        }
        safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
    }

    // ── ACTIVATE ─────────────────────────────────────────────────────────────
    if ($action === 'activate') {
        if ($student['status'] === 'ACTIVE') {
            flashSet('error', 'Student is already active.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
        }
        // Must have a student_id to activate
        if (empty($student['student_id'])) {
            flashSet('error', 'Cannot activate: student ID not assigned. Approve the registration first.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
        }
        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "UPDATE student_profiles SET status = 'ACTIVE', updated_at = NOW() WHERE id = ?"
            )->execute([$profileId]);
            $pdo->prepare(
                "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
                 VALUES ('STUDENT_ACTIVATED', 'StudentProfile', ?, ?, ?, NOW())"
            )->execute([(string) $profileId, json_encode(['full_name' => $student['full_name']]), $adminId]);
            $pdo->commit();
            flashSet('success', 'Student account activated.');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flashSet('error', 'Activation failed.');
        }
        safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
    }

    // ── DEACTIVATE ───────────────────────────────────────────────────────────
    if ($action === 'deactivate') {
        if ($student['status'] !== 'ACTIVE') {
            flashSet('error', 'Student is not currently active.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
        }
        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "UPDATE student_profiles SET status = 'INACTIVE', updated_at = NOW() WHERE id = ?"
            )->execute([$profileId]);
            $pdo->prepare(
                "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
                 VALUES ('STUDENT_DEACTIVATED', 'StudentProfile', ?, ?, ?, NOW())"
            )->execute([(string) $profileId, json_encode(['full_name' => $student['full_name']]), $adminId]);
            $pdo->commit();
            flashSet('success', 'Student account deactivated.');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flashSet('error', 'Deactivation failed.');
        }
        safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
    }

    // ── EDIT PROFILE ─────────────────────────────────────────────────────────
    if ($action === 'edit_profile') {
        // Whitelist of editable fields only — no mass assignment
        $fullName       = trim($_POST['full_name']       ?? '');
        $gender         = $_POST['gender']               ?? '';
        $dob            = trim($_POST['date_of_birth']   ?? '');
        $mobile         = trim($_POST['mobile_number']   ?? '');
        $address        = trim($_POST['address']         ?? '');
        $parentName     = trim($_POST['parent_name']     ?? '');
        $parentContact  = trim($_POST['parent_contact']  ?? '');
        $locationId     = filter_var($_POST['preferred_location_id'] ?? '', FILTER_VALIDATE_INT) ?: null;
        $joiningDate    = trim($_POST['joining_date']    ?? '');

        $errors = [];
        if ($fullName === '') $errors[] = 'Full name is required.';
        if (!in_array($gender, ['MALE', 'FEMALE', 'OTHER', ''], true)) $errors[] = 'Invalid gender.';
        if ($mobile !== '' && !preg_match('/^[6-9]\d{9}$/', $mobile)) $errors[] = 'Invalid mobile number.';
        if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) $errors[] = 'Invalid date of birth.';
        if ($joiningDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $joiningDate)) $errors[] = 'Invalid joining date.';

        if (!empty($errors)) {
            flashSet('error', implode(' ', $errors));
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=profile');
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "UPDATE student_profiles
                 SET full_name = ?, gender = ?, date_of_birth = ?, mobile_number = ?,
                     address = ?, parent_name = ?, parent_contact = ?,
                     preferred_location_id = ?, joining_date = ?, updated_at = NOW()
                 WHERE id = ?"
            )->execute([
                $fullName,
                $gender ?: null,
                $dob ?: null,
                $mobile ?: null,
                $address ?: null,
                $parentName ?: null,
                $parentContact ?: null,
                $locationId,
                $joiningDate ?: null,
                $profileId,
            ]);
            // Mirror name to users table
            $pdo->prepare("UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?")->execute([$fullName, $student['user_id']]);
            $pdo->prepare(
                "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
                 VALUES ('STUDENT_PROFILE_EDITED', 'StudentProfile', ?, ?, ?, NOW())"
            )->execute([(string) $profileId, json_encode(['full_name' => $fullName, 'edited_by_admin' => $adminId]), $adminId]);
            $pdo->commit();
            flashSet('success', 'Student profile updated successfully.');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            appLog('ERROR', 'Profile edit failed: ' . $e->getMessage());
            flashSet('error', 'Profile update failed.');
        }
        safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=profile');
    }

    // ── PROMOTE BELT ─────────────────────────────────────────────────────────
    if ($action === 'promote_belt') {
        $newBeltId      = filter_var($_POST['new_belt_id'] ?? '', FILTER_VALIDATE_INT);
        $promotionDate  = trim($_POST['promotion_date'] ?? date('Y-m-d'));
        $remarks        = trim($_POST['remarks'] ?? '');
        $examRegId      = filter_var($_POST['exam_registration_id'] ?? '', FILTER_VALIDATE_INT) ?: null;

        if (!$newBeltId) {
            flashSet('error', 'Please select a target belt.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=belt');
        }

        // Validate belt exists
        $beltCheck = $pdo->prepare("SELECT id, name FROM belts WHERE id = ?");
        $beltCheck->execute([$newBeltId]);
        $newBelt = $beltCheck->fetch(PDO::FETCH_ASSOC);
        if (!$newBelt) {
            flashSet('error', 'Target belt not found.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=belt');
        }

        // Validate exam registration belongs to this student if provided
        if ($examRegId) {
            $erCheck = $pdo->prepare("SELECT id FROM exam_registrations WHERE id = ? AND student_profile_id = ?");
            $erCheck->execute([$examRegId, $profileId]);
            if (!$erCheck->fetch()) {
                flashSet('error', 'Selected exam registration does not belong to this student.');
                safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=belt');
            }
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $promotionDate)) {
            $promotionDate = date('Y-m-d');
        }

        try {
            $pdo->beginTransaction();
            $oldBeltId = $student['current_belt_id'];
            // Update current belt
            $pdo->prepare(
                "UPDATE student_profiles SET current_belt_id = ?, updated_at = NOW() WHERE id = ?"
            )->execute([$newBeltId, $profileId]);
            // Insert belt history
            $pdo->prepare(
                "INSERT INTO student_belt_history
                 (student_profile_id, old_belt_id, new_belt_id, exam_registration_id, promoted_by_admin_id, promotion_date, remarks, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            )->execute([$profileId, $oldBeltId, $newBeltId, $examRegId, $adminId, $promotionDate, $remarks ?: null]);
            // Update exam registration promotion status if linked
            if ($examRegId) {
                $pdo->prepare(
                    "UPDATE exam_registrations SET promotion_status = 'PROMOTED', passed = 1, result = 'PASS', updated_at = NOW() WHERE id = ?"
                )->execute([$examRegId]);
            }
            // Audit log
            $pdo->prepare(
                "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
                 VALUES ('BELT_PROMOTED', 'StudentProfile', ?, ?, ?, NOW())"
            )->execute([
                (string) $profileId,
                json_encode(['old_belt_id' => $oldBeltId, 'new_belt_id' => $newBeltId, 'new_belt_name' => $newBelt['name'], 'full_name' => $student['full_name']]),
                $adminId,
            ]);
            $pdo->commit();
            flashSet('success', "Belt promoted to {$newBelt['name']} successfully.");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            appLog('ERROR', 'Belt promotion failed: ' . $e->getMessage());
            flashSet('error', 'Belt promotion failed. Please try again.');
        }
        safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=belt');
    }

    // ── RECORD PAYMENT ───────────────────────────────────────────────────────
    if ($action === 'record_payment') {
        $amount         = trim($_POST['amount'] ?? '');
        $paymentDate    = trim($_POST['payment_date'] ?? '');
        $paymentPeriod  = trim($_POST['payment_period'] ?? '');
        $paymentMethod  = trim($_POST['payment_method'] ?? '');
        $feesPaidUpTo   = trim($_POST['fees_paid_up_to'] ?? '');
        $remarks        = trim($_POST['remarks'] ?? '');

        $errors = [];
        $amountFloat = filter_var($amount, FILTER_VALIDATE_FLOAT);
        if ($amountFloat === false || $amountFloat <= 0) $errors[] = 'Amount must be a positive number.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $paymentDate)) $errors[] = 'Invalid payment date.';
        if ($paymentPeriod === '') $errors[] = 'Payment period is required.';
        $allowedMethods = ['CASH', 'UPI', 'BANK_TRANSFER', 'CHEQUE', 'CARD', 'OTHER'];
        if (!in_array($paymentMethod, $allowedMethods, true)) $errors[] = 'Invalid payment method.';
        if ($feesPaidUpTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $feesPaidUpTo)) $errors[] = 'Invalid fees paid up to date.';

        if (!empty($errors)) {
            flashSet('error', implode(' ', $errors));
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=fees');
        }

        // Generate unique reference
        $paymentRef = generatePaymentRef();
        // Ensure uniqueness
        $refCheck = $pdo->prepare("SELECT id FROM payments WHERE payment_ref = ?");
        $refCheck->execute([$paymentRef]);
        if ($refCheck->fetch()) {
            $paymentRef .= '-' . random_int(100, 999);
        }

        // Store amount as string for precision (DECIMAL column)
        $amountDecimal = number_format((float) $amountFloat, 2, '.', '');

        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "INSERT INTO payments (payment_ref, student_profile_id, created_by_admin_id, amount,
                 payment_date, payment_period, payment_method, fees_paid_up_to, remarks, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
            )->execute([
                $paymentRef,
                $profileId,
                $adminId,
                $amountDecimal,
                $paymentDate,
                $paymentPeriod,
                $paymentMethod,
                $feesPaidUpTo ?: null,
                $remarks ?: null,
            ]);
            $paymentId = $pdo->lastInsertId();
            $pdo->prepare(
                "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
                 VALUES ('PAYMENT_RECORDED', 'Payment', ?, ?, ?, NOW())"
            )->execute([
                (string) $paymentId,
                json_encode(['payment_ref' => $paymentRef, 'amount' => $amountDecimal, 'student_profile_id' => $profileId, 'full_name' => $student['full_name']]),
                $adminId,
            ]);
            $pdo->commit();
            flashSet('success', "Payment of ₹{$amountDecimal} recorded. Reference: {$paymentRef}");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            appLog('ERROR', 'Payment record failed: ' . $e->getMessage());
            flashSet('error', 'Payment recording failed. Please try again.');
        }
        safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=fees');
    }

    // ── REGISTER FOR EXAM ─────────────────────────────────────────────────────
    if ($action === 'register_exam') {
        $examId = filter_var($_POST['exam_id'] ?? '', FILTER_VALIDATE_INT);
        if (!$examId) {
            flashSet('error', 'Please select an exam.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=exams');
        }
        // Check exam exists
        $examRow = $pdo->prepare("SELECT id, exam_name, status FROM exams WHERE id = ?");
        $examRow->execute([$examId]);
        $exam = $examRow->fetch(PDO::FETCH_ASSOC);
        if (!$exam) {
            flashSet('error', 'Selected exam not found.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=exams');
        }
        if ($exam['status'] === 'CANCELLED') {
            flashSet('error', 'Cannot register for a cancelled exam.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=exams');
        }
        // Check duplicate
        $dupCheck = $pdo->prepare("SELECT id FROM exam_registrations WHERE student_profile_id = ? AND exam_id = ?");
        $dupCheck->execute([$profileId, $examId]);
        if ($dupCheck->fetch()) {
            flashSet('error', 'Student is already registered for this exam.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=exams');
        }

        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "INSERT INTO exam_registrations (student_profile_id, exam_id, registration_date, result, created_at, updated_at)
                 VALUES (?, ?, CURDATE(), 'PENDING', NOW(), NOW())"
            )->execute([$profileId, $examId]);
            $regId = $pdo->lastInsertId();
            $pdo->prepare(
                "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
                 VALUES ('EXAM_REGISTERED', 'ExamRegistration', ?, ?, ?, NOW())"
            )->execute([
                (string) $regId,
                json_encode(['exam_name' => $exam['exam_name'], 'student_profile_id' => $profileId, 'full_name' => $student['full_name']]),
                $adminId,
            ]);
            $pdo->commit();
            flashSet('success', "Student registered for: {$exam['exam_name']}");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            appLog('ERROR', 'Exam registration failed: ' . $e->getMessage());
            flashSet('error', 'Exam registration failed.');
        }
        safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=exams');
    }

    // ── RECORD EXAM RESULT ────────────────────────────────────────────────────
    if ($action === 'record_result') {
        $regId  = filter_var($_POST['registration_id'] ?? '', FILTER_VALIDATE_INT);
        $result = $_POST['result'] ?? '';
        $marks  = trim($_POST['marks'] ?? '');
        $remark = trim($_POST['remark'] ?? '');

        if (!$regId || !in_array($result, ['PENDING', 'PASS', 'FAIL'], true)) {
            flashSet('error', 'Invalid result data.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=exams');
        }
        // Verify registration belongs to this student
        $regCheck = $pdo->prepare("SELECT er.id, e.exam_name FROM exam_registrations er JOIN exams e ON e.id = er.exam_id WHERE er.id = ? AND er.student_profile_id = ?");
        $regCheck->execute([$regId, $profileId]);
        $reg = $regCheck->fetch(PDO::FETCH_ASSOC);
        if (!$reg) {
            flashSet('error', 'Exam registration not found for this student.');
            safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=exams');
        }

        $marksValue = $marks !== '' ? (int) $marks : null;
        $passed     = ($result === 'PASS') ? 1 : 0;

        try {
            $pdo->beginTransaction();
            $pdo->prepare(
                "UPDATE exam_registrations SET result = ?, marks = ?, passed = ?, remarks = ?, updated_at = NOW() WHERE id = ?"
            )->execute([$result, $marksValue, $passed, $remark ?: null, $regId]);
            $pdo->prepare(
                "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
                 VALUES ('EXAM_RESULT_RECORDED', 'ExamRegistration', ?, ?, ?, NOW())"
            )->execute([
                (string) $regId,
                json_encode(['exam_name' => $reg['exam_name'], 'result' => $result, 'marks' => $marksValue, 'full_name' => $student['full_name']]),
                $adminId,
            ]);
            $pdo->commit();
            flashSet('success', "Exam result recorded: {$result}" . ($marksValue !== null ? " ({$marksValue} marks)" : ''));
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            appLog('ERROR', 'Result record failed: ' . $e->getMessage());
            flashSet('error', 'Result recording failed.');
        }
        safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId . '&tab=exams');
    }

    // Unknown action
    safeRedirect(BASE_PATH . '/admin/student?id=' . $profileId);
}

// ─── Data Fetch for Rendering ─────────────────────────────────────────────────

$activeTab = in_array($_GET['tab'] ?? '', ['profile', 'belt', 'fees', 'exams', 'actions'], true)
    ? ($_GET['tab'])
    : 'profile';

// Refresh student after possible POST redirect
$student = fetchStudentFull($pdo, $profileId);

// Belt list for dropdowns
$allBelts = $pdo->query("SELECT id, name, color, rank FROM belts ORDER BY rank ASC")->fetchAll(PDO::FETCH_ASSOC);

// Locations for dropdown
$allLocations = $pdo->query("SELECT id, location_name FROM class_locations WHERE is_active = 1 ORDER BY location_name")->fetchAll(PDO::FETCH_ASSOC);

// Belt history
$beltHistory = $pdo->prepare(
    "SELECT sbh.*, ob.name AS old_belt_name, ob.color AS old_belt_color,
            nb.name AS new_belt_name, nb.color AS new_belt_color,
            e.exam_name
     FROM student_belt_history sbh
     LEFT JOIN belts ob ON ob.id = sbh.old_belt_id
     LEFT JOIN belts nb ON nb.id = sbh.new_belt_id
     LEFT JOIN exam_registrations er ON er.id = sbh.exam_registration_id
     LEFT JOIN exams e ON e.id = er.exam_id
     WHERE sbh.student_profile_id = ?
     ORDER BY sbh.promotion_date DESC"
);
$beltHistory->execute([$profileId]);
$beltHistoryRows = $beltHistory->fetchAll(PDO::FETCH_ASSOC);

// Payments
$paymentsStmt = $pdo->prepare(
    "SELECT id, payment_ref, amount, payment_date, payment_period, payment_method, fees_paid_up_to, remarks
     FROM payments WHERE student_profile_id = ?
     ORDER BY payment_date DESC"
);
$paymentsStmt->execute([$profileId]);
$payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);

$totalPaid = array_sum(array_column($payments, 'amount'));

// Exam registrations
$examRegsStmt = $pdo->prepare(
    "SELECT er.id, er.exam_id, er.registration_date, er.result, er.marks, er.passed, er.promotion_status, er.remarks,
            e.exam_name, e.exam_date, e.venue, e.status AS exam_status, e.exam_fee
     FROM exam_registrations er
     JOIN exams e ON e.id = er.exam_id
     WHERE er.student_profile_id = ?
     ORDER BY e.exam_date DESC"
);
$examRegsStmt->execute([$profileId]);
$examRegistrations = $examRegsStmt->fetchAll(PDO::FETCH_ASSOC);

// Available exams (for registration dropdown — SCHEDULED, future or current)
$availableExams = $pdo->prepare(
    "SELECT e.id, e.exam_name, e.exam_date, e.venue, e.exam_fee
     FROM exams e
     WHERE e.status = 'SCHEDULED' AND e.exam_date >= CURDATE()
     AND e.id NOT IN (SELECT exam_id FROM exam_registrations WHERE student_profile_id = ?)
     ORDER BY e.exam_date ASC"
);
$availableExams->execute([$profileId]);
$availableExamsList = $availableExams->fetchAll(PDO::FETCH_ASSOC);

// Exam registrations eligible for belt linkage (PASS result, not yet promoted)
$eligibleRegsForPromotion = $pdo->prepare(
    "SELECT er.id, e.exam_name, e.exam_date
     FROM exam_registrations er
     JOIN exams e ON e.id = er.exam_id
     WHERE er.student_profile_id = ? AND er.passed = 1 AND (er.promotion_status IS NULL OR er.promotion_status != 'PROMOTED')
     ORDER BY e.exam_date DESC"
);
$eligibleRegsForPromotion->execute([$profileId]);
$eligibleRegs = $eligibleRegsForPromotion->fetchAll(PDO::FETCH_ASSOC);

// ─── Status Badge Helper ──────────────────────────────────────────────────────

function statusBadge(string $status): string
{
    return match ($status) {
        'ACTIVE'   => '<span class="badge" style="background:#16a34a;color:#fff;">ACTIVE</span>',
        'PENDING'  => '<span class="badge" style="background:#d97706;color:#fff;">PENDING</span>',
        'INACTIVE' => '<span class="badge" style="background:#6b7280;color:#fff;">INACTIVE</span>',
        'REJECTED' => '<span class="badge" style="background:#dc2626;color:#fff;">REJECTED</span>',
        default    => '<span class="badge bg-secondary">' . esc($status) . '</span>',
    };
}

function resultBadge(string $result): string
{
    return match ($result) {
        'PASS'    => '<span class="badge" style="background:#16a34a;color:#fff;">PASS</span>',
        'FAIL'    => '<span class="badge" style="background:#dc2626;color:#fff;">FAIL</span>',
        'PENDING' => '<span class="badge" style="background:#d97706;color:#fff;">PENDING</span>',
        default   => '<span class="badge bg-secondary">' . esc($result) . '</span>',
    };
}

$pageTitle  = 'Student Dossier';
$portalType = 'admin';

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<style>
.tab-nav { display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem; }
.tab-nav a {
    padding: 0.6rem 1.1rem;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6b7280;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: color .15s, border-color .15s;
}
.tab-nav a:hover { color: #111; }
.tab-nav a.active { color: #dc2626; border-bottom-color: #dc2626; }
.student-avatar {
    width: 72px; height: 72px; border-radius: 50%;
    background: linear-gradient(135deg, #dc2626, #991b1b);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.75rem; font-weight: 900; color: #fff;
    flex-shrink: 0;
}
.belt-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.15); }
.info-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.07em; color: #9ca3af; font-weight: 700; }
.info-value { font-size: 0.92rem; color: #111827; font-weight: 600; }
.section-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1.25rem; margin-bottom: 1rem; }
</style>

<!-- Student Header Card -->
<div class="section-card d-flex align-items-center gap-3 mb-3">
    <?php if (!empty($student['profile_photo_url'])): ?>
        <img src="<?= escAttr(uploadUrl($student['profile_photo_url'])) ?>" alt="<?= escAttr($student['full_name']) ?>"
             style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;">
    <?php else: ?>
        <div class="student-avatar"><?= esc(strtoupper(substr($student['full_name'], 0, 1))) ?></div>
    <?php endif; ?>

    <div class="flex-grow-1 min-width-0">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <h1 class="fw-black mb-0" style="font-size:1.3rem;"><?= esc($student['full_name']) ?></h1>
            <?= statusBadge($student['status']) ?>
        </div>
        <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:0.82rem;color:#6b7280;">
            <?php if (!empty($student['student_id'])): ?>
                <span class="font-monospace fw-bold text-dark"><?= esc($student['student_id']) ?></span>
            <?php else: ?>
                <span class="text-warning fw-bold">No ID — Pending Approval</span>
            <?php endif; ?>
            <span><?= esc($student['user_email']) ?></span>
            <?php if (!empty($student['belt_name'])): ?>
                <span>
                    <span class="belt-dot me-1" style="background:<?= escAttr($student['belt_color']) ?>;"></span>
                    <?= esc($student['belt_name']) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <a href="<?= BASE_PATH ?>/admin/students" class="btn btn-sm btn-outline-secondary ms-auto">
        &larr; All Students
    </a>
</div>

<!-- Tab Navigation -->
<div class="tab-nav">
    <a href="?id=<?= $profileId ?>&tab=profile"  class="<?= $activeTab === 'profile'  ? 'active' : '' ?>">Profile</a>
    <a href="?id=<?= $profileId ?>&tab=belt"     class="<?= $activeTab === 'belt'     ? 'active' : '' ?>">Belt History</a>
    <a href="?id=<?= $profileId ?>&tab=fees"     class="<?= $activeTab === 'fees'     ? 'active' : '' ?>">Fee Payments</a>
    <a href="?id=<?= $profileId ?>&tab=exams"    class="<?= $activeTab === 'exams'    ? 'active' : '' ?>">Examinations</a>
    <a href="?id=<?= $profileId ?>&tab=actions"  class="<?= $activeTab === 'actions'  ? 'active' : '' ?>">Admin Actions</a>
</div>

<!-- ════════════════════════════════════ TAB 1: PROFILE ════════════════════════════ -->
<?php if ($activeTab === 'profile'): ?>
<div class="row g-3">
    <!-- View Panel -->
    <div class="col-lg-5">
        <div class="section-card h-100">
            <h2 class="fw-black text-uppercase mb-3" style="font-size:0.9rem;letter-spacing:0.06em;">Student Details</h2>
            <div class="row g-2">
                <?php
                $fields = [
                    'Student ID'        => $student['student_id'] ?: '(Pending Approval)',
                    'Full Name'         => $student['full_name'],
                    'Email'             => $student['email'],
                    'Date of Birth'     => $student['date_of_birth'] ? formatDate($student['date_of_birth']) : '—',
                    'Gender'            => $student['gender'] ?: '—',
                    'Mobile Number'     => $student['mobile_number'] ?: '—',
                    'Current Belt'      => ($student['belt_name'] ?? '—'),
                    'Joining Date'      => $student['joining_date'] ? formatDate($student['joining_date']) : '—',
                    'Preferred Location'=> $student['preferred_location_name'] ?: '—',
                    'Address'           => $student['address'] ?: '—',
                    'Parent Name'       => $student['parent_name'] ?: '—',
                    'Parent Contact'    => $student['parent_contact'] ?: '—',
                    'Account Status'    => $student['status'],
                    'Registered'        => formatDate($student['created_at']),
                ];
                foreach ($fields as $label => $value): ?>
                    <div class="col-12 col-sm-6">
                        <div class="info-label"><?= esc($label) ?></div>
                        <div class="info-value"><?= esc($value) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="col-lg-7">
        <div class="section-card">
            <h2 class="fw-black text-uppercase mb-3" style="font-size:0.9rem;letter-spacing:0.06em;">Edit Profile</h2>
            <form method="POST" action="<?= BASE_PATH ?>/admin/student?id=<?= $profileId ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="edit_profile">
                <div class="row g-2">
                    <div class="col-sm-8">
                        <label class="form-label info-label">Full Name *</label>
                        <input type="text" name="full_name" class="form-control form-control-sm" required
                               value="<?= escAttr($student['full_name']) ?>">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label info-label">Gender</label>
                        <select name="gender" class="form-select form-select-sm">
                            <option value="">— Select —</option>
                            <?php foreach (['MALE','FEMALE','OTHER'] as $g): ?>
                                <option value="<?= $g ?>" <?= ($student['gender'] === $g) ? 'selected' : '' ?>><?= $g ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label info-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control form-control-sm"
                               value="<?= escAttr($student['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label info-label">Mobile Number</label>
                        <input type="text" name="mobile_number" class="form-control form-control-sm"
                               value="<?= escAttr($student['mobile_number'] ?? '') ?>" pattern="[6-9]\d{9}" placeholder="10-digit">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label info-label">Joining Date</label>
                        <input type="date" name="joining_date" class="form-control form-control-sm"
                               value="<?= escAttr($student['joining_date'] ?? '') ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label info-label">Preferred Location</label>
                        <select name="preferred_location_id" class="form-select form-select-sm">
                            <option value="">— None —</option>
                            <?php foreach ($allLocations as $loc): ?>
                                <option value="<?= $loc['id'] ?>" <?= ((int)$student['preferred_location_id'] === (int)$loc['id']) ? 'selected' : '' ?>>
                                    <?= esc($loc['location_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label info-label">Address</label>
                        <input type="text" name="address" class="form-control form-control-sm"
                               value="<?= escAttr($student['address'] ?? '') ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label info-label">Parent / Guardian Name</label>
                        <input type="text" name="parent_name" class="form-control form-control-sm"
                               value="<?= escAttr($student['parent_name'] ?? '') ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label info-label">Parent Contact</label>
                        <input type="text" name="parent_contact" class="form-control form-control-sm"
                               value="<?= escAttr($student['parent_contact'] ?? '') ?>">
                    </div>
                    <div class="col-12 mt-2">
                        <button type="submit" class="btn btn-sm btn-danger fw-bold px-4" style="background:#dc2626;border-color:#dc2626;">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════ TAB 2: BELT HISTORY ══════════════════════ -->
<?php if ($activeTab === 'belt'): ?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="section-card">
            <h2 class="fw-black text-uppercase mb-3" style="font-size:0.9rem;letter-spacing:0.06em;">Belt Promotion History</h2>
            <?php if (empty($beltHistoryRows)): ?>
                <div class="text-center py-4 text-muted">
                    <p class="mb-0">No belt promotions recorded.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="font-size:0.72rem;">Date</th>
                                <th style="font-size:0.72rem;">From</th>
                                <th style="font-size:0.72rem;">To</th>
                                <th style="font-size:0.72rem;">Exam</th>
                                <th style="font-size:0.72rem;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($beltHistoryRows as $bh): ?>
                                <tr>
                                    <td style="font-size:0.82rem;"><?= esc(formatDate($bh['promotion_date'])) ?></td>
                                    <td style="font-size:0.82rem;">
                                        <?php if ($bh['old_belt_name']): ?>
                                            <span class="belt-dot me-1" style="background:<?= escAttr($bh['old_belt_color']) ?>;"></span><?= esc($bh['old_belt_name']) ?>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td style="font-size:0.82rem;">
                                        <span class="belt-dot me-1" style="background:<?= escAttr($bh['new_belt_color']) ?>;"></span><?= esc($bh['new_belt_name']) ?>
                                    </td>
                                    <td style="font-size:0.82rem;"><?= esc($bh['exam_name'] ?? '—') ?></td>
                                    <td style="font-size:0.82rem;"><?= esc($bh['remarks'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="section-card">
            <h2 class="fw-black text-uppercase mb-3" style="font-size:0.9rem;letter-spacing:0.06em;">Promote Belt</h2>
            <div class="mb-2">
                <span class="info-label">Current Belt</span><br>
                <?php if ($student['belt_name']): ?>
                    <span class="belt-dot me-1" style="background:<?= escAttr($student['belt_color']) ?>;"></span>
                    <strong><?= esc($student['belt_name']) ?></strong>
                <?php else: ?>
                    <span class="text-muted">Not assigned</span>
                <?php endif; ?>
            </div>
            <form method="POST" action="<?= BASE_PATH ?>/admin/student?id=<?= $profileId ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="promote_belt">
                <div class="mb-2">
                    <label class="form-label info-label">Target Belt *</label>
                    <select name="new_belt_id" class="form-select form-select-sm" required>
                        <option value="">— Select Belt —</option>
                        <?php foreach ($allBelts as $belt): ?>
                            <?php if ((int)$belt['id'] !== (int)$student['current_belt_id']): ?>
                                <option value="<?= $belt['id'] ?>">
                                    <?= esc('Rank ' . $belt['rank'] . ' — ' . $belt['name']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label info-label">Promotion Date *</label>
                    <input type="date" name="promotion_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <?php if (!empty($eligibleRegs)): ?>
                <div class="mb-2">
                    <label class="form-label info-label">Link to Exam (optional)</label>
                    <select name="exam_registration_id" class="form-select form-select-sm">
                        <option value="">— No exam linkage —</option>
                        <?php foreach ($eligibleRegs as $er): ?>
                            <option value="<?= $er['id'] ?>"><?= esc($er['exam_name'] . ' (' . formatDate($er['exam_date']) . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label class="form-label info-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Optional notes">
                </div>
                <button type="submit" class="btn btn-sm btn-danger fw-bold px-4" style="background:#dc2626;border-color:#dc2626;"
                        onclick="return confirm('Promote this student\'s belt? This cannot be undone automatically.')">
                    Confirm Promotion
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════ TAB 3: FEE PAYMENTS ══════════════════════ -->
<?php if ($activeTab === 'fees'): ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="section-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-black text-uppercase mb-0" style="font-size:0.9rem;letter-spacing:0.06em;">Payment History</h2>
                <span class="badge rounded-pill" style="background:#16a34a;color:#fff;font-size:0.75rem;">
                    Total: <?= esc(formatCurrency($totalPaid)) ?>
                </span>
            </div>
            <?php if (empty($payments)): ?>
                <div class="text-center py-4 text-muted"><p class="mb-0">No payments recorded yet.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="font-size:0.72rem;">Ref</th>
                                <th style="font-size:0.72rem;">Amount</th>
                                <th style="font-size:0.72rem;">Date</th>
                                <th style="font-size:0.72rem;">Period</th>
                                <th style="font-size:0.72rem;">Method</th>
                                <th style="font-size:0.72rem;">Paid Up To</th>
                                <th style="font-size:0.72rem;">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td><code style="font-size:0.78rem;"><?= esc($pay['payment_ref']) ?></code></td>
                                    <td class="text-success fw-bold" style="font-size:0.82rem;"><?= esc(formatCurrency($pay['amount'])) ?></td>
                                    <td style="font-size:0.82rem;"><?= esc(formatDate($pay['payment_date'])) ?></td>
                                    <td style="font-size:0.82rem;"><?= esc($pay['payment_period']) ?></td>
                                    <td style="font-size:0.82rem;"><?= esc($pay['payment_method']) ?></td>
                                    <td style="font-size:0.82rem;"><?= $pay['fees_paid_up_to'] ? esc(formatDate($pay['fees_paid_up_to'])) : '—' ?></td>
                                    <td style="font-size:0.82rem;"><?= esc($pay['remarks'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="section-card">
            <h2 class="fw-black text-uppercase mb-3" style="font-size:0.9rem;letter-spacing:0.06em;">Record Payment</h2>
            <form method="POST" action="<?= BASE_PATH ?>/admin/student?id=<?= $profileId ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="record_payment">
                <div class="mb-2">
                    <label class="form-label info-label">Amount (₹) *</label>
                    <input type="number" name="amount" class="form-control form-control-sm" step="0.01" min="0.01" placeholder="e.g. 1200.00" required>
                </div>
                <div class="mb-2">
                    <label class="form-label info-label">Payment Date *</label>
                    <input type="date" name="payment_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="mb-2">
                    <label class="form-label info-label">Payment Period *</label>
                    <input type="text" name="payment_period" class="form-control form-control-sm" placeholder="e.g. October 2026" required>
                </div>
                <div class="mb-2">
                    <label class="form-label info-label">Payment Method *</label>
                    <select name="payment_method" class="form-select form-select-sm" required>
                        <option value="">— Select —</option>
                        <?php foreach (['CASH','UPI','BANK_TRANSFER','CHEQUE','CARD','OTHER'] as $m): ?>
                            <option value="<?= $m ?>"><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label info-label">Fees Paid Up To</label>
                    <input type="date" name="fees_paid_up_to" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label info-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control form-control-sm" placeholder="Optional">
                </div>
                <button type="submit" class="btn btn-sm btn-success fw-bold px-4 w-100">Record Payment</button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════ TAB 4: EXAMINATIONS ══════════════════════ -->
<?php if ($activeTab === 'exams'): ?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="section-card">
            <h2 class="fw-black text-uppercase mb-3" style="font-size:0.9rem;letter-spacing:0.06em;">Exam Registrations</h2>
            <?php if (empty($examRegistrations)): ?>
                <div class="text-center py-4 text-muted"><p class="mb-0">No exam registrations yet.</p></div>
            <?php else: ?>
                <?php foreach ($examRegistrations as $reg): ?>
                    <div class="border rounded-2 p-3 mb-2" style="background:#fafafa;">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="fw-bold" style="font-size:0.9rem;"><?= esc($reg['exam_name']) ?></div>
                                <div class="text-muted" style="font-size:0.8rem;">
                                    <?= esc(formatDate($reg['exam_date'])) ?> &bull; <?= esc($reg['venue'] ?? '—') ?>
                                </div>
                                <?php if ($reg['marks'] !== null): ?>
                                    <div style="font-size:0.8rem;">Marks: <strong><?= esc($reg['marks']) ?></strong></div>
                                <?php endif; ?>
                                <?php if ($reg['remarks']): ?>
                                    <div class="text-muted" style="font-size:0.78rem;">Note: <?= esc($reg['remarks']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex flex-column gap-1 align-items-end">
                                <?= resultBadge($reg['result']) ?>
                                <?php if ($reg['promotion_status'] === 'PROMOTED'): ?>
                                    <span class="badge" style="background:#7c3aed;color:#fff;font-size:0.7rem;">Promoted</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- Inline Result Recording Form -->
                        <details class="mt-2">
                            <summary style="font-size:0.78rem;cursor:pointer;color:#dc2626;font-weight:700;">Record / Update Result</summary>
                            <form method="POST" action="<?= BASE_PATH ?>/admin/student?id=<?= $profileId ?>" class="mt-2">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="record_result">
                                <input type="hidden" name="registration_id" value="<?= $reg['id'] ?>">
                                <div class="row g-1">
                                    <div class="col-auto">
                                        <select name="result" class="form-select form-select-sm">
                                            <?php foreach (['PENDING','PASS','FAIL'] as $r): ?>
                                                <option value="<?= $r ?>" <?= $reg['result'] === $r ? 'selected' : '' ?>><?= $r ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <input type="number" name="marks" class="form-control form-control-sm" style="width:90px;" placeholder="Marks" value="<?= esc($reg['marks'] ?? '') ?>">
                                    </div>
                                    <div class="col">
                                        <input type="text" name="remark" class="form-control form-control-sm" placeholder="Remarks" value="<?= escAttr($reg['remarks'] ?? '') ?>">
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-sm btn-danger fw-bold" style="background:#dc2626;border-color:#dc2626;">Save</button>
                                    </div>
                                </div>
                            </form>
                        </details>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="section-card">
            <h2 class="fw-black text-uppercase mb-3" style="font-size:0.9rem;letter-spacing:0.06em;">Register for Exam</h2>
            <?php if (empty($availableExamsList)): ?>
                <p class="text-muted" style="font-size:0.85rem;">No upcoming scheduled exams available for registration.</p>
            <?php else: ?>
                <form method="POST" action="<?= BASE_PATH ?>/admin/student?id=<?= $profileId ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="register_exam">
                    <div class="mb-3">
                        <label class="form-label info-label">Select Exam *</label>
                        <select name="exam_id" class="form-select form-select-sm" required>
                            <option value="">— Choose exam —</option>
                            <?php foreach ($availableExamsList as $ex): ?>
                                <option value="<?= $ex['id'] ?>">
                                    <?= esc($ex['exam_name'] . ' — ' . formatDate($ex['exam_date'])) ?>
                                    <?php if ($ex['exam_fee'] > 0): ?>(₹<?= esc(number_format($ex['exam_fee'], 2)) ?>)<?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-danger fw-bold px-4 w-100" style="background:#dc2626;border-color:#dc2626;">
                        Register for Exam
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════ TAB 5: ADMIN ACTIONS ═════════════════════ -->
<?php if ($activeTab === 'actions'): ?>
<div class="row g-3">
    <!-- Status Control Card -->
    <div class="col-lg-6">
        <div class="section-card">
            <h2 class="fw-black text-uppercase mb-1" style="font-size:0.9rem;letter-spacing:0.06em;">Account Status Control</h2>
            <p class="text-muted mb-3" style="font-size:0.82rem;">Current status: <?= statusBadge($student['status']) ?></p>

            <?php if ($student['status'] === 'PENDING'): ?>
                <!-- Approve -->
                <form method="POST" action="<?= BASE_PATH ?>/admin/student?id=<?= $profileId ?>" class="mb-3">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="approve">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-2" style="background:#f0fdf4;border:1px solid #86efac;">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-success" style="font-size:0.85rem;">Approve Registration</div>
                            <div class="text-muted" style="font-size:0.78rem;">Assigns a unique KAR-YYYY-NNNN Student ID and sets status to ACTIVE.</div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success fw-bold"
                                onclick="return confirm('Approve this student and assign a Student ID?')">
                            Approve
                        </button>
                    </div>
                </form>
                <!-- Reject -->
                <form method="POST" action="<?= BASE_PATH ?>/admin/student?id=<?= $profileId ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="reject">
                    <div class="p-3 rounded-2" style="background:#fef2f2;border:1px solid #fca5a5;">
                        <div class="fw-bold text-danger mb-1" style="font-size:0.85rem;">Reject Registration</div>
                        <div class="text-muted mb-2" style="font-size:0.78rem;">Sets status to REJECTED. The application record is preserved.</div>
                        <div class="input-group input-group-sm">
                            <input type="text" name="reason" class="form-control" placeholder="Reason (optional)">
                            <button type="submit" class="btn btn-sm btn-danger fw-bold"
                                    onclick="return confirm('Reject this student registration?')">
                                Reject
                            </button>
                        </div>
                    </div>
                </form>

            <?php elseif ($student['status'] === 'ACTIVE'): ?>
                <form method="POST" action="<?= BASE_PATH ?>/admin/student?id=<?= $profileId ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="deactivate">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-2" style="background:#fef3c7;border:1px solid #fcd34d;">
                        <div class="flex-grow-1">
                            <div class="fw-bold" style="font-size:0.85rem;color:#92400e;">Deactivate Account</div>
                            <div class="text-muted" style="font-size:0.78rem;">Removes portal access. Data is preserved. Account can be reactivated.</div>
                        </div>
                        <button type="submit" class="btn btn-sm fw-bold" style="background:#d97706;color:#fff;border-color:#d97706;"
                                onclick="return confirm('Deactivate this student account?')">
                            Deactivate
                        </button>
                    </div>
                </form>

            <?php elseif ($student['status'] === 'INACTIVE'): ?>
                <form method="POST" action="<?= BASE_PATH ?>/admin/student?id=<?= $profileId ?>">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="activate">
                    <div class="d-flex align-items-center gap-2 p-3 rounded-2" style="background:#f0fdf4;border:1px solid #86efac;">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-success" style="font-size:0.85rem;">Reactivate Account</div>
                            <div class="text-muted" style="font-size:0.78rem;">Restores portal access. Requires existing Student ID.</div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success fw-bold"
                                onclick="return confirm('Reactivate this student account?')">
                            Activate
                        </button>
                    </div>
                </form>

            <?php elseif ($student['status'] === 'REJECTED'): ?>
                <div class="text-muted p-3 rounded-2" style="background:#f9fafb;border:1px solid #e5e7eb;font-size:0.85rem;">
                    This registration was rejected. To reinstate, contact the academy administrator.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Account Info -->
    <div class="col-lg-6">
        <div class="section-card">
            <h2 class="fw-black text-uppercase mb-3" style="font-size:0.9rem;letter-spacing:0.06em;">Account Information</h2>
            <div class="row g-2">
                <div class="col-12 col-sm-6">
                    <div class="info-label">User ID</div>
                    <div class="info-value"><?= esc($student['user_id']) ?></div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="info-label">Role</div>
                    <div class="info-value"><?= esc($student['role']) ?></div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="info-label">Account Created</div>
                    <div class="info-value"><?= esc(formatDate($student['account_created'], 'd M Y H:i')) ?></div>
                </div>
                <div class="col-12 col-sm-6">
                    <div class="info-label">Profile Updated</div>
                    <div class="info-value"><?= esc(formatDate($student['updated_at'], 'd M Y H:i')) ?></div>
                </div>
                <div class="col-12">
                    <div class="info-label">Total Payments Recorded</div>
                    <div class="info-value text-success fw-bold"><?= esc(formatCurrency($totalPaid)) ?></div>
                </div>
                <div class="col-12">
                    <div class="info-label">Belt Promotions</div>
                    <div class="info-value"><?= count($beltHistoryRows) ?></div>
                </div>
                <div class="col-12">
                    <div class="info-label">Exam Registrations</div>
                    <div class="info-value"><?= count($examRegistrations) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
