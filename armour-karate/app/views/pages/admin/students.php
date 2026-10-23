<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Admin Student Roster
 * app/views/pages/admin/students.php
 *
 * Route: /admin/students
 *
 * Features:
 *  - Search by name, email, or student ID (GET: q)
 *  - Filter by status (GET: status) and belt (GET: belt_id)
 *  - Pagination: 20 per page (GET: page)
 *  - Add Student modal with full server-side validation and transaction
 */

declare(strict_types=1);

requireAdmin();

// ─── Constants ────────────────────────────────────────────────────────────────

const STUDENTS_PER_PAGE     = 20;
const ALLOWED_STATUSES      = ['ALL', 'PENDING', 'ACTIVE', 'INACTIVE', 'REJECTED'];
const STATUS_BADGE_CLASSES  = [
    'PENDING'  => 'bg-warning text-dark',
    'ACTIVE'   => 'bg-success',
    'INACTIVE' => 'bg-secondary',
    'REJECTED' => 'bg-danger',
];

$pdo     = db();
$adminId = (int) $_SESSION['user_id'];

// ─── POST Handler: create_student ─────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_student') {
    csrfValidate();

    // --- Collect & sanitise inputs ---
    $fullName       = trim($_POST['full_name']       ?? '');
    $email          = trim(strtolower($_POST['email'] ?? ''));
    $password       = $_POST['password']             ?? '';
    $dateOfBirth    = trim($_POST['date_of_birth']   ?? '');
    $gender         = trim($_POST['gender']          ?? '');
    $mobileNumber   = trim($_POST['mobile_number']   ?? '');
    $address        = trim($_POST['address']         ?? '');
    $parentName     = trim($_POST['parent_name']     ?? '');
    $parentContact  = trim($_POST['parent_contact']  ?? '');
    $beltId         = (int) ($_POST['belt_id']       ?? 0);
    $locationId     = (int) ($_POST['location_id']   ?? 0);
    $joiningDate    = trim($_POST['joining_date']     ?? date('Y-m-d'));
    $accountStatus  = trim($_POST['account_status']  ?? 'ACTIVE');

    // --- Validation ---
    $errors = [];

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    } elseif (mb_strlen($fullName) > 150) {
        $errors[] = 'Full name must not exceed 150 characters.';
    }

    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    } elseif (mb_strlen($email) > 180) {
        $errors[] = 'Email address is too long.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one digit.';
    }

    if ($dateOfBirth !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) {
        $errors[] = 'Date of birth must be a valid date (YYYY-MM-DD).';
    }

    $allowedGenders = ['MALE', 'FEMALE', 'OTHER', ''];
    if (!in_array($gender, $allowedGenders, true)) {
        $errors[] = 'Invalid gender selection.';
    }

    if ($mobileNumber !== '' && !preg_match('/^\d{10}$/', $mobileNumber)) {
        $errors[] = 'Mobile number must be exactly 10 digits.';
    }

    $allowedAccountStatuses = ['ACTIVE', 'PENDING'];
    if (!in_array($accountStatus, $allowedAccountStatuses, true)) {
        $accountStatus = 'ACTIVE';
    }

    if ($joiningDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $joiningDate)) {
        $joiningDate = date('Y-m-d');
    }

    // Check email uniqueness (only if no prior errors on email)
    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chk->execute([$email]);
        if ($chk->fetchColumn() !== false) {
            $errors[] = 'An account with this email address already exists.';
        }
    }

    // Validate belt_id
    if ($beltId > 0) {
        $beltChk = $pdo->prepare('SELECT id FROM belts WHERE id = ? LIMIT 1');
        $beltChk->execute([$beltId]);
        if ($beltChk->fetchColumn() === false) {
            $beltId = 0;
        }
    }

    // Validate location_id
    if ($locationId > 0) {
        $locChk = $pdo->prepare('SELECT id FROM class_locations WHERE id = ? AND is_active = 1 LIMIT 1');
        $locChk->execute([$locationId]);
        if ($locChk->fetchColumn() === false) {
            $locationId = 0;
        }
    }

    if (!empty($errors)) {
        flashSet('error', implode(' ', $errors));
        safeRedirect(BASE_PATH . '/admin/students');
    }

    // --- Transaction ---
    try {
        $pdo->beginTransaction();

        // 1. Insert user record
        $stmtUser = $pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, email_verified, created_at, updated_at)
             VALUES (?, ?, ?, \'STUDENT\', 1, NOW(), NOW())'
        );
        $stmtUser->execute([$fullName, $email, hashPassword($password)]);
        $userId = (int) $pdo->lastInsertId();

        // 2. Generate student ID only for ACTIVE students
        $studentId = null;
        if ($accountStatus === 'ACTIVE') {
            $studentId = generateStudentId($pdo);
        }

        // 3. Insert student profile
        $stmtProfile = $pdo->prepare(
            'INSERT INTO student_profiles
                (user_id, student_id, full_name, email, date_of_birth, gender,
                 mobile_number, address, parent_name, parent_contact,
                 current_belt_id, preferred_location_id, joining_date, status,
                 created_at, updated_at)
             VALUES
                (?, ?, ?, ?, ?, ?,
                 ?, ?, ?, ?,
                 ?, ?, ?, ?,
                 NOW(), NOW())'
        );
        $stmtProfile->execute([
            $userId,
            $studentId,
            $fullName,
            $email,
            $dateOfBirth !== '' ? $dateOfBirth : null,
            $gender       !== '' ? $gender       : null,
            $mobileNumber !== '' ? $mobileNumber : null,
            $address      !== '' ? $address      : null,
            $parentName   !== '' ? $parentName   : null,
            $parentContact !== '' ? $parentContact : null,
            $beltId    > 0 ? $beltId    : null,
            $locationId > 0 ? $locationId : null,
            $joiningDate,
            $accountStatus,
        ]);
        $profileId = (int) $pdo->lastInsertId();

        // 4. Audit log (strictly part of transaction)
        $pdo->prepare(
            "INSERT INTO audit_logs (action, entity_type, entity_id, metadata, admin_id, created_at)
             VALUES ('STUDENT_CREATED_DIRECT', 'StudentProfile', ?, ?, ?, NOW())"
        )->execute([
            (string) $profileId,
            json_encode([
                'full_name'  => $fullName,
                'email'      => $email,
                'student_id' => $studentId,
                'status'     => $accountStatus,
            ], JSON_UNESCAPED_UNICODE),
            $adminId
        ]);

        $pdo->commit();

        flashSet('success', "Student \"{$fullName}\" has been created successfully." . ($studentId ? " Student ID: {$studentId}." : ''));
        safeRedirect(BASE_PATH . '/admin/students');

    } catch (RuntimeException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flashSet('error', $e->getMessage());
        safeRedirect(BASE_PATH . '/admin/students');
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        appLog('ERROR', 'Student create failed: ' . $e->getMessage());
        flashSet('error', 'A database error occurred while creating the student. Please try again.');
        safeRedirect(BASE_PATH . '/admin/students');
    }
}

// ─── GET: Filters & Pagination ────────────────────────────────────────────────

$search    = trim($_GET['q']       ?? '');
$rawStatus = strtoupper(trim($_GET['status'] ?? 'ALL'));
$filterStatus = in_array($rawStatus, ALLOWED_STATUSES, true) ? $rawStatus : 'ALL';
$filterBeltId = max(0, (int) ($_GET['belt_id'] ?? 0));
$currentPage  = max(1, (int) ($_GET['page']    ?? 1));

// ─── Build WHERE clause ───────────────────────────────────────────────────────

$whereClauses = [];
$bindParams   = [];

if ($search !== '') {
    $whereClauses[] = '(sp.full_name LIKE ? OR sp.email LIKE ? OR sp.student_id LIKE ?)';
    $likeTerm = '%' . $search . '%';
    $bindParams[] = $likeTerm;
    $bindParams[] = $likeTerm;
    $bindParams[] = $likeTerm;
}

if ($filterStatus !== 'ALL') {
    $whereClauses[] = 'sp.status = ?';
    $bindParams[] = $filterStatus;
}

if ($filterBeltId > 0) {
    $whereClauses[] = 'sp.current_belt_id = ?';
    $bindParams[] = $filterBeltId;
}

$whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// ─── Count total for pagination ───────────────────────────────────────────────

$countStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM student_profiles sp {$whereSQL}"
);
$countStmt->execute($bindParams);
$totalItems = (int) $countStmt->fetchColumn();

$pagination  = paginate($totalItems, STUDENTS_PER_PAGE, $currentPage);
$currentPage = $pagination['currentPage']; // clamped value

// ─── Fetch students ───────────────────────────────────────────────────────────

$listParams   = array_merge($bindParams, [$pagination['offset']]);
$studentStmt  = $pdo->prepare(
    "SELECT sp.id,
            sp.student_id,
            sp.full_name,
            sp.email,
            sp.status,
            sp.joining_date,
            b.name  AS belt_name,
            b.color AS belt_color,
            cl.location_name
     FROM   student_profiles sp
     LEFT JOIN belts          b  ON b.id  = sp.current_belt_id
     LEFT JOIN class_locations cl ON cl.id = sp.preferred_location_id
     {$whereSQL}
     ORDER BY sp.created_at DESC
     LIMIT " . STUDENTS_PER_PAGE . " OFFSET ?"
);
$studentStmt->execute($listParams);
$students = $studentStmt->fetchAll();

// ─── Fetch belts for filter dropdown & Add Student modal ──────────────────────

$beltsAll = $pdo->query(
    'SELECT id, name, color, rank FROM belts ORDER BY rank ASC'
)->fetchAll();

// Find the default belt (lowest rank)
$defaultBeltId = !empty($beltsAll) ? (int) $beltsAll[0]['id'] : 0;

// ─── Fetch active locations for Add Student modal ─────────────────────────────

$locationsAll = $pdo->query(
    'SELECT id, location_name FROM class_locations WHERE is_active = 1 ORDER BY location_name ASC'
)->fetchAll();

// ─── Build query string helper (for pagination links) ────────────────────────

function buildQueryString(array $overrides = []): string
{
    $params = [
        'q'       => $_GET['q']       ?? '',
        'status'  => $_GET['status']  ?? '',
        'belt_id' => $_GET['belt_id'] ?? '',
        'page'    => $_GET['page']    ?? '',
    ];
    $merged = array_merge($params, $overrides);
    // Remove empty values to keep URLs clean
    $filtered = array_filter($merged, static fn ($v) => $v !== '' && $v !== '0');
    return $filtered ? '?' . http_build_query($filtered) : '';
}

// ─── Page setup ───────────────────────────────────────────────────────────────

$pageTitle  = 'Student Management';
$portalType = 'admin';

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<!-- ── Page Header ──────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Student Roster</h1>
        <p class="text-muted small mb-0">
            <?= number_format($totalItems) ?> student<?= $totalItems === 1 ? '' : 's' ?> found
            <?php if ($search !== '' || $filterStatus !== 'ALL' || $filterBeltId > 0): ?>
                &mdash; <a href="<?= BASE_PATH ?>/admin/students" class="text-decoration-none small">Clear filters</a>
            <?php endif; ?>
        </p>
    </div>
    <button type="button"
            class="btn btn-danger fw-semibold px-4"
            data-bs-toggle="modal"
            data-bs-target="#addStudentModal">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add Student
    </button>
</div>

<!-- ── Filters ──────────────────────────────────────────────────────────────── -->
<form method="GET" action="<?= BASE_PATH ?>/admin/students"
      class="card border-0 shadow-sm rounded-3 mb-4"
      role="search"
      aria-label="Student search and filter">
    <div class="card-body py-3">
        <div class="row g-3 align-items-end">

            <!-- Search -->
            <div class="col-12 col-md-4">
                <label for="search-q" class="form-label small fw-semibold text-muted text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.05em;">
                    Search
                </label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/>
                        </svg>
                    </span>
                    <input type="search"
                           id="search-q"
                           name="q"
                           class="form-control border-start-0 ps-0"
                           placeholder="Name, email, or student ID…"
                           value="<?= escAttr($search) ?>"
                           maxlength="100"
                           autocomplete="off">
                </div>
            </div>

            <!-- Status filter -->
            <div class="col-6 col-md-3">
                <label for="filter-status" class="form-label small fw-semibold text-muted text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.05em;">
                    Status
                </label>
                <select id="filter-status" name="status" class="form-select">
                    <?php foreach (ALLOWED_STATUSES as $s): ?>
                        <option value="<?= escAttr($s) ?>"<?= $filterStatus === $s ? ' selected' : '' ?>>
                            <?= $s === 'ALL' ? 'All Statuses' : ucfirst(strtolower($s)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Belt filter -->
            <div class="col-6 col-md-3">
                <label for="filter-belt" class="form-label small fw-semibold text-muted text-uppercase mb-1" style="font-size:.7rem;letter-spacing:.05em;">
                    Belt
                </label>
                <select id="filter-belt" name="belt_id" class="form-select">
                    <option value="">All Belts</option>
                    <?php foreach ($beltsAll as $belt): ?>
                        <option value="<?= escAttr((string)$belt['id']) ?>"<?= $filterBeltId === (int)$belt['id'] ? ' selected' : '' ?>>
                            <?= esc($belt['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit -->
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    Apply
                </button>
            </div>

        </div>
    </div>
</form>

<!-- ── Student Table ─────────────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0">
        <?php if (empty($students)): ?>

            <!-- Empty State -->
            <div class="d-flex flex-column align-items-center justify-content-center py-5 text-center px-4">
                <div class="mb-3" style="width:56px;height:56px;background:#F1F5F9;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                    <svg width="24" height="24" fill="none" stroke="#94A3B8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2h5M12 12a5 5 0 100-10 5 5 0 000 10z"/>
                    </svg>
                </div>
                <?php if ($search !== '' || $filterStatus !== 'ALL' || $filterBeltId > 0): ?>
                    <h3 class="fw-semibold mb-1" style="font-size:1rem;">No students match your filters</h3>
                    <p class="text-muted small mb-3">Try adjusting your search term or clearing the filters.</p>
                    <a href="<?= BASE_PATH ?>/admin/students" class="btn btn-outline-secondary btn-sm">
                        Clear Filters
                    </a>
                <?php else: ?>
                    <h3 class="fw-semibold mb-1" style="font-size:1rem;">No students yet</h3>
                    <p class="text-muted small mb-3">Create your first student using the "Add Student" button above.</p>
                    <button type="button" class="btn btn-danger btn-sm fw-semibold"
                            data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        Add First Student
                    </button>
                <?php endif; ?>
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" aria-label="Student roster">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase"
                                style="font-size:.7rem;letter-spacing:.06em;white-space:nowrap;">
                                Student ID
                            </th>
                            <th class="py-3 fw-semibold text-muted text-uppercase"
                                style="font-size:.7rem;letter-spacing:.06em;">
                                Name
                            </th>
                            <th class="py-3 fw-semibold text-muted text-uppercase"
                                style="font-size:.7rem;letter-spacing:.06em;">
                                Email
                            </th>
                            <th class="py-3 fw-semibold text-muted text-uppercase"
                                style="font-size:.7rem;letter-spacing:.06em;">
                                Belt
                            </th>
                            <th class="py-3 fw-semibold text-muted text-uppercase"
                                style="font-size:.7rem;letter-spacing:.06em;">
                                Status
                            </th>
                            <th class="py-3 fw-semibold text-muted text-uppercase"
                                style="font-size:.7rem;letter-spacing:.06em;white-space:nowrap;">
                                Joining Date
                            </th>
                            <th class="py-3 fw-semibold text-muted text-uppercase"
                                style="font-size:.7rem;letter-spacing:.06em;">
                                Location
                            </th>
                            <th class="pe-4 py-3 fw-semibold text-muted text-uppercase text-end"
                                style="font-size:.7rem;letter-spacing:.06em;">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $row): ?>
                            <?php
                                $statusClass = STATUS_BADGE_CLASSES[$row['status']] ?? 'bg-secondary';
                                $beltDot     = $row['belt_color'] ? 'style="background:' . escAttr($row['belt_color']) . '"' : '';
                            ?>
                            <tr>
                                <!-- Student ID -->
                                <td class="ps-4 py-3">
                                    <?php if ($row['student_id']): ?>
                                        <code class="text-dark fw-semibold" style="font-size:.8rem;">
                                            <?= esc($row['student_id']) ?>
                                        </code>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:.8rem;">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Name -->
                                <td class="py-3 fw-semibold">
                                    <?= esc($row['full_name']) ?>
                                </td>

                                <!-- Email -->
                                <td class="py-3 text-muted" style="font-size:.875rem;">
                                    <?= esc($row['email']) ?>
                                </td>

                                <!-- Belt -->
                                <td class="py-3">
                                    <?php if ($row['belt_name']): ?>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle d-inline-block flex-shrink-0"
                                                  <?= $beltDot ?>
                                                  style="width:10px;height:10px;<?= $row['belt_color'] ? '' : 'background:#CBD5E1;' ?>">
                                            </span>
                                            <span style="font-size:.875rem;"><?= esc($row['belt_name']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:.875rem;">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status badge -->
                                <td class="py-3">
                                    <span class="badge <?= $statusClass ?> rounded-pill px-2 py-1"
                                          style="font-size:.7rem;font-weight:700;letter-spacing:.04em;">
                                        <?= esc($row['status']) ?>
                                    </span>
                                </td>

                                <!-- Joining Date -->
                                <td class="py-3 text-muted" style="font-size:.875rem;white-space:nowrap;">
                                    <?= formatDate($row['joining_date']) ?>
                                </td>

                                <!-- Location -->
                                <td class="py-3 text-muted" style="font-size:.875rem;">
                                    <?= $row['location_name'] ? esc($row['location_name']) : '<span>—</span>' ?>
                                </td>

                                <!-- Actions -->
                                <td class="pe-4 py-3 text-end">
                                    <a href="<?= BASE_PATH ?>/admin/student?id=<?= (int)$row['id'] ?>"
                                       class="btn btn-sm btn-outline-primary fw-semibold"
                                       style="font-size:.78rem;"
                                       title="View <?= escAttr($row['full_name']) ?>">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination footer -->
            <?php if ($pagination['totalPages'] > 1): ?>
                <div class="card-footer bg-white border-top d-flex align-items-center justify-content-between flex-wrap gap-3 py-3 px-4">
                    <p class="text-muted small mb-0">
                        Showing
                        <strong><?= number_format($pagination['offset'] + 1) ?></strong>–<strong><?= number_format(min($pagination['offset'] + STUDENTS_PER_PAGE, $totalItems)) ?></strong>
                        of <strong><?= number_format($totalItems) ?></strong> students
                    </p>
                    <nav aria-label="Student list pagination">
                        <ul class="pagination pagination-sm mb-0">

                            <!-- Previous -->
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                       href="<?= BASE_PATH ?>/admin/students<?= buildQueryString(['page' => $currentPage - 1]) ?>"
                                       aria-label="Previous page">
                                        &laquo;
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled" aria-disabled="true">
                                    <span class="page-link">&laquo;</span>
                                </li>
                            <?php endif; ?>

                            <!-- Page numbers (sliding window of 5) -->
                            <?php
                                $window     = 2;
                                $startPage  = max(1, $currentPage - $window);
                                $endPage    = min($pagination['totalPages'], $currentPage + $window);

                                // Show ellipsis + first page if gap exists
                                if ($startPage > 1):
                            ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= BASE_PATH ?>/admin/students<?= buildQueryString(['page' => 1]) ?>">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                                <li class="page-item <?= $p === $currentPage ? 'active' : '' ?>"
                                    <?= $p === $currentPage ? 'aria-current="page"' : '' ?>>
                                    <a class="page-link"
                                       href="<?= BASE_PATH ?>/admin/students<?= buildQueryString(['page' => $p]) ?>">
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Trailing ellipsis + last page -->
                            <?php if ($endPage < $pagination['totalPages']): ?>
                                <?php if ($endPage < $pagination['totalPages'] - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link"
                                       href="<?= BASE_PATH ?>/admin/students<?= buildQueryString(['page' => $pagination['totalPages']]) ?>">
                                        <?= $pagination['totalPages'] ?>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Next -->
                            <?php if ($currentPage < $pagination['totalPages']): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                       href="<?= BASE_PATH ?>/admin/students<?= buildQueryString(['page' => $currentPage + 1]) ?>"
                                       aria-label="Next page">
                                        &raquo;
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled" aria-disabled="true">
                                    <span class="page-link">&raquo;</span>
                                </li>
                            <?php endif; ?>

                        </ul>
                    </nav>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     Add Student Modal
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="modal fade"
     id="addStudentModal"
     tabindex="-1"
     aria-labelledby="addStudentModalLabel"
     aria-modal="true"
     role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-3 border-0 shadow">

            <!-- Modal Header -->
            <div class="modal-header border-bottom">
                <h2 class="modal-title fw-bold h5" id="addStudentModalLabel">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         class="me-2 text-danger" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-5-3a4 4 0 100-8 4 4 0 000 8zm-7 9a7 7 0 0114 0H3z"/>
                    </svg>
                    Add New Student
                </h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close modal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body px-4 py-4">
                <form id="addStudentForm"
                      method="POST"
                      action="<?= BASE_PATH ?>/admin/students"
                      novalidate
                      autocomplete="off">

                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="create_student">

                    <!-- ── Section: Account ───────────────────────────────── -->
                    <p class="text-uppercase fw-bold text-muted mb-3"
                       style="font-size:.68rem;letter-spacing:.1em;border-bottom:1px solid #E2E8F0;padding-bottom:.5rem;">
                        Account Information
                    </p>

                    <div class="row g-3 mb-4">
                        <!-- Full Name -->
                        <div class="col-12 col-md-6">
                            <label for="add-full-name" class="form-label fw-semibold small">
                                Full Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   id="add-full-name"
                                   name="full_name"
                                   class="form-control"
                                   placeholder="e.g. Arjun Patel"
                                   maxlength="150"
                                   required
                                   autocomplete="off">
                        </div>

                        <!-- Email -->
                        <div class="col-12 col-md-6">
                            <label for="add-email" class="form-label fw-semibold small">
                                Email Address <span class="text-danger">*</span>
                            </label>
                            <input type="email"
                                   id="add-email"
                                   name="email"
                                   class="form-control"
                                   placeholder="student@example.com"
                                   maxlength="180"
                                   required
                                   autocomplete="off">
                        </div>

                        <!-- Password -->
                        <div class="col-12 col-md-6">
                            <label for="add-password" class="form-label fw-semibold small">
                                Password <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="password"
                                       id="add-password"
                                       name="password"
                                       class="form-control"
                                       placeholder="Min. 8 chars, 1 uppercase, 1 digit"
                                       minlength="8"
                                       required
                                       autocomplete="new-password">
                                <button type="button"
                                        class="btn btn-outline-secondary btn-toggle-pwd"
                                        data-target="add-password"
                                        tabindex="-1"
                                        aria-label="Toggle password visibility">
                                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="form-text">
                                Must be ≥8 characters, contain an uppercase letter and a digit.
                            </div>
                        </div>

                        <!-- Account Status -->
                        <div class="col-12 col-md-6">
                            <label for="add-account-status" class="form-label fw-semibold small">
                                Account Status <span class="text-danger">*</span>
                            </label>
                            <select id="add-account-status" name="account_status" class="form-select" required>
                                <option value="ACTIVE" selected>Active (Student ID assigned immediately)</option>
                                <option value="PENDING">Pending (Awaiting review)</option>
                            </select>
                        </div>
                    </div>

                    <!-- ── Section: Personal Details ──────────────────────── -->
                    <p class="text-uppercase fw-bold text-muted mb-3"
                       style="font-size:.68rem;letter-spacing:.1em;border-bottom:1px solid #E2E8F0;padding-bottom:.5rem;">
                        Personal Details
                    </p>

                    <div class="row g-3 mb-4">
                        <!-- Date of Birth -->
                        <div class="col-12 col-md-4">
                            <label for="add-dob" class="form-label fw-semibold small">Date of Birth</label>
                            <input type="date"
                                   id="add-dob"
                                   name="date_of_birth"
                                   class="form-control"
                                   max="<?= date('Y-m-d') ?>">
                        </div>

                        <!-- Gender -->
                        <div class="col-12 col-md-4">
                            <label for="add-gender" class="form-label fw-semibold small">Gender</label>
                            <select id="add-gender" name="gender" class="form-select">
                                <option value="">— Select —</option>
                                <option value="MALE">Male</option>
                                <option value="FEMALE">Female</option>
                                <option value="OTHER">Other</option>
                            </select>
                        </div>

                        <!-- Mobile Number -->
                        <div class="col-12 col-md-4">
                            <label for="add-mobile" class="form-label fw-semibold small">Mobile Number</label>
                            <input type="tel"
                                   id="add-mobile"
                                   name="mobile_number"
                                   class="form-control"
                                   placeholder="10-digit mobile number"
                                   maxlength="10"
                                   pattern="\d{10}"
                                   autocomplete="off">
                        </div>

                        <!-- Address -->
                        <div class="col-12">
                            <label for="add-address" class="form-label fw-semibold small">Address</label>
                            <textarea id="add-address"
                                      name="address"
                                      class="form-control"
                                      rows="2"
                                      maxlength="500"
                                      placeholder="Full residential address…"></textarea>
                        </div>
                    </div>

                    <!-- ── Section: Parent / Guardian ─────────────────────── -->
                    <p class="text-uppercase fw-bold text-muted mb-3"
                       style="font-size:.68rem;letter-spacing:.1em;border-bottom:1px solid #E2E8F0;padding-bottom:.5rem;">
                        Parent / Guardian
                    </p>

                    <div class="row g-3 mb-4">
                        <!-- Parent Name -->
                        <div class="col-12 col-md-6">
                            <label for="add-parent-name" class="form-label fw-semibold small">Parent Name</label>
                            <input type="text"
                                   id="add-parent-name"
                                   name="parent_name"
                                   class="form-control"
                                   placeholder="Parent / guardian full name"
                                   maxlength="150"
                                   autocomplete="off">
                        </div>

                        <!-- Parent Contact -->
                        <div class="col-12 col-md-6">
                            <label for="add-parent-contact" class="form-label fw-semibold small">Parent Contact</label>
                            <input type="tel"
                                   id="add-parent-contact"
                                   name="parent_contact"
                                   class="form-control"
                                   placeholder="10-digit contact number"
                                   maxlength="10"
                                   pattern="\d{10}"
                                   autocomplete="off">
                        </div>
                    </div>

                    <!-- ── Section: Karate Details ─────────────────────────── -->
                    <p class="text-uppercase fw-bold text-muted mb-3"
                       style="font-size:.68rem;letter-spacing:.1em;border-bottom:1px solid #E2E8F0;padding-bottom:.5rem;">
                        Karate Details
                    </p>

                    <div class="row g-3">
                        <!-- Initial Belt -->
                        <div class="col-12 col-md-4">
                            <label for="add-belt" class="form-label fw-semibold small">Initial Belt</label>
                            <select id="add-belt" name="belt_id" class="form-select">
                                <option value="">— No belt assigned —</option>
                                <?php foreach ($beltsAll as $belt): ?>
                                    <option value="<?= escAttr((string)$belt['id']) ?>"
                                        <?= (int)$belt['id'] === $defaultBeltId ? ' selected' : '' ?>>
                                        <?= esc($belt['name']) ?>
                                        <?php if ($belt['color']): ?>
                                            (<?= esc($belt['color']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Preferred Location -->
                        <div class="col-12 col-md-4">
                            <label for="add-location" class="form-label fw-semibold small">Preferred Location</label>
                            <select id="add-location" name="location_id" class="form-select">
                                <option value="">— No preference —</option>
                                <?php foreach ($locationsAll as $loc): ?>
                                    <option value="<?= escAttr((string)$loc['id']) ?>">
                                        <?= esc($loc['location_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Joining Date -->
                        <div class="col-12 col-md-4">
                            <label for="add-joining-date" class="form-label fw-semibold small">Joining Date</label>
                            <input type="date"
                                   id="add-joining-date"
                                   name="joining_date"
                                   class="form-control"
                                   value="<?= escAttr(date('Y-m-d')) ?>">
                        </div>
                    </div>

                </form><!-- /#addStudentForm -->
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer border-top bg-light rounded-bottom-3 px-4 py-3">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="submit"
                        form="addStudentForm"
                        class="btn btn-danger fw-semibold px-4">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         class="me-1" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M5 13l4 4L19 7"/>
                    </svg>
                    Create Student
                </button>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /#addStudentModal -->

<!-- ─── Inline JS: password visibility toggle ─────────────────────────────── -->
<script>
(function () {
    'use strict';

    // Toggle password visibility
    document.querySelectorAll('.btn-toggle-pwd').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input    = document.getElementById(targetId);
            if (!input) return;
            input.type = (input.type === 'password') ? 'text' : 'password';
        });
    });

    // Re-open modal if flash error (i.e., form had validation issues from server)
    <?php
    // If there was an error flash queued during *this* request (before redirect), the modal
    // would normally close. We detect prior submission intent via a re-open flag.
    // Since we always redirect on POST, this is handled gracefully by the flash banner.
    // No client-side modal re-open needed — the flash alert at the top is sufficient.
    ?>

    // Mobile number: restrict to digits
    ['add-mobile', 'add-parent-contact'].forEach(function (id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });
    });

    // Client-side validation before submit (progressive enhancement)
    var form = document.getElementById('addStudentForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            var fullName = document.getElementById('add-full-name');
            var email    = document.getElementById('add-email');
            var password = document.getElementById('add-password');

            var pwd = password ? password.value : '';
            var valid = true;
            var msgs  = [];

            if (fullName && fullName.value.trim() === '') {
                msgs.push('Full name is required.');
                valid = false;
            }

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                msgs.push('A valid email address is required.');
                valid = false;
            }

            if (pwd.length < 8) {
                msgs.push('Password must be at least 8 characters.');
                valid = false;
            } else if (!/[A-Z]/.test(pwd)) {
                msgs.push('Password must contain at least one uppercase letter.');
                valid = false;
            } else if (!/[0-9]/.test(pwd)) {
                msgs.push('Password must contain at least one digit.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                alert(msgs.join('\n'));
            }
        });
    }
}());
</script>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
