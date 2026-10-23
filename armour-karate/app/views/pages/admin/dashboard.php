<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Admin Dashboard – Academy Overview
 * app/views/pages/admin/dashboard.php
 *
 * Read-only summary page for authenticated administrators.
 * Displays KPIs, pending registrations, and recent payments.
 * No audit log writes on this page (read-only).
 */

declare(strict_types=1);

requireAdmin();

$pdo       = db();
$adminId   = (int) $_SESSION['user_id'];
$pageTitle = 'Academy Dashboard';
$portalType = 'admin';

// ─── 1. Primary KPI Aggregates ────────────────────────────────────────────────

// Active students
$stmtActive = $pdo->query(
    "SELECT COUNT(*) FROM student_profiles WHERE status = 'ACTIVE'"
);
$activeStudentCount = (int) $stmtActive->fetchColumn();

// Pending registrations
$stmtPending = $pdo->query(
    "SELECT COUNT(*) FROM student_profiles WHERE status = 'PENDING'"
);
$pendingCount = (int) $stmtPending->fetchColumn();

// Total enrolled (ACTIVE + INACTIVE)
$stmtTotal = $pdo->query(
    "SELECT COUNT(*) FROM student_profiles WHERE status IN ('ACTIVE', 'INACTIVE')"
);
$totalEnrolled = (int) $stmtTotal->fetchColumn();

// Rejected + Inactive
$stmtRejInactive = $pdo->query(
    "SELECT COUNT(*) FROM student_profiles WHERE status IN ('REJECTED', 'INACTIVE')"
);
$rejectedInactiveCount = (int) $stmtRejInactive->fetchColumn();

// ─── 2. Fee-Due Count ─────────────────────────────────────────────────────────
// Active students whose latest payment's fees_paid_up_to is in the past OR have no payment.
$stmtFeeDue = $pdo->query(
    "SELECT COUNT(DISTINCT sp.id)
     FROM student_profiles sp
     LEFT JOIN payments p
         ON  p.student_profile_id = sp.id
         AND p.payment_date = (
             SELECT MAX(p2.payment_date)
             FROM payments p2
             WHERE p2.student_profile_id = sp.id
         )
     WHERE sp.status = 'ACTIVE'
       AND (p.id IS NULL OR p.fees_paid_up_to < CURDATE())"
);
$feeDueCount = (int) $stmtFeeDue->fetchColumn();

// ─── 3. 7-Day Payment Collection ─────────────────────────────────────────────
$stmtPayWeek = $pdo->query(
    "SELECT COALESCE(SUM(amount), 0) AS total, COUNT(*) AS cnt
     FROM payments
     WHERE payment_date >= CURDATE() - INTERVAL 7 DAY"
);
$payWeekRow   = $stmtPayWeek->fetch(PDO::FETCH_ASSOC);
$payWeekTotal = (float)  ($payWeekRow['total'] ?? 0);
$payWeekCount = (int)    ($payWeekRow['cnt']   ?? 0);

// ─── 4. Recent 7-Day Registrations ───────────────────────────────────────────
$stmtRecentReg = $pdo->query(
    "SELECT COUNT(*) FROM student_profiles
     WHERE created_at >= NOW() - INTERVAL 7 DAY"
);
$recentRegCount = (int) $stmtRecentReg->fetchColumn();

// ─── 5. Upcoming Exams (next 30 days) ────────────────────────────────────────
$stmtExams = $pdo->query(
    "SELECT COUNT(*) FROM exams
     WHERE status != 'CANCELLED'
       AND exam_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY"
);
$upcomingExamCount = (int) $stmtExams->fetchColumn();

// ─── 6. Upcoming Events (next 30 days) ───────────────────────────────────────
$stmtEvents = $pdo->query(
    "SELECT COUNT(*) FROM events
     WHERE event_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 30 DAY"
);
$upcomingEventCount = (int) $stmtEvents->fetchColumn();

// ─── 7. Admin / Black Belt Count (users with ADMIN role as fallback) ──────────
$stmtAdmins = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role = 'ADMIN'"
);
$adminCount = (int) $stmtAdmins->fetchColumn();

// ─── 8. Last 5 Pending Registrations ─────────────────────────────────────────
$stmtPendingList = $pdo->query(
    "SELECT sp.id, sp.student_id, sp.full_name, sp.joining_date, sp.created_at,
            cl.location_name
     FROM student_profiles sp
     LEFT JOIN class_locations cl ON cl.id = sp.preferred_location_id
     WHERE sp.status = 'PENDING'
     ORDER BY sp.created_at DESC
     LIMIT 5"
);
$pendingList = $stmtPendingList->fetchAll(PDO::FETCH_ASSOC);

// ─── 9. Last 5 Payments ───────────────────────────────────────────────────────
$stmtPayList = $pdo->query(
    "SELECT p.id, p.payment_ref, p.amount, p.payment_date, p.payment_method,
            p.payment_period, sp.full_name, sp.student_id, sp.id AS student_profile_id
     FROM payments p
     JOIN student_profiles sp ON sp.id = p.student_profile_id
     ORDER BY p.created_at DESC
     LIMIT 5"
);
$paymentList = $stmtPayList->fetchAll(PDO::FETCH_ASSOC);

// ─── Render ───────────────────────────────────────────────────────────────────
require APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<!-- ═══ DASHBOARD PAGE ═══════════════════════════════════════════════════════ -->

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1" style="color:#1a1a1a;">Academy Overview</h2>
        <p class="text-muted mb-0 small">Welcome back — here's your academy at a glance.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= BASE_PATH ?>/admin/students"
           class="btn btn-sm btn-danger fw-semibold">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Student
        </a>
        <a href="<?= BASE_PATH ?>/admin/fees"
           class="btn btn-sm btn-outline-secondary fw-semibold">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2M5 9h14l1 12H4L5 9z"/>
            </svg>
            Record Payment
        </a>
        <a href="<?= BASE_PATH ?>/admin/exams"
           class="btn btn-sm btn-outline-secondary fw-semibold">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Schedule Exam
        </a>
        <a href="<?= BASE_PATH ?>/admin/events"
           class="btn btn-sm btn-outline-secondary fw-semibold">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Create Event
        </a>
    </div>
</div>

<!-- ─── Primary Stats Grid (4 cols) ─────────────────────────────────────────── -->
<div class="row g-3 mb-3">

    <!-- Active Students -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 rounded-3 shadow-sm h-100" style="background:#fff;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center"
                         style="background:#dcfce7; width:44px; height:44px;">
                        <svg width="22" height="22" fill="none" stroke="#16a34a" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="badge rounded-pill fw-semibold" style="background:#dcfce7;color:#15803d;font-size:.72rem;">ACTIVE</span>
                </div>
                <div class="fw-black mb-1" style="font-size:2rem;color:#111827;line-height:1;">
                    <?= esc($activeStudentCount) ?>
                </div>
                <div class="text-muted small fw-medium">Active Students</div>
            </div>
            <div class="card-footer bg-transparent border-top-0 px-4 pb-3">
                <a href="<?= BASE_PATH ?>/admin/students?status=ACTIVE"
                   class="small fw-semibold text-decoration-none" style="color:#16a34a;">
                    View all &rsaquo;
                </a>
            </div>
        </div>
    </div>

    <!-- Pending Registrations -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 rounded-3 shadow-sm h-100"
             style="background:#fff; <?= $pendingCount > 0 ? 'border-left: 4px solid #DC2626 !important;' : '' ?>">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center"
                         style="background:<?= $pendingCount > 0 ? '#fee2e2' : '#f3f4f6' ?>;width:44px;height:44px;">
                        <svg width="22" height="22" fill="none" stroke="<?= $pendingCount > 0 ? '#DC2626' : '#6b7280' ?>"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <?php if ($pendingCount > 0): ?>
                        <span class="badge rounded-pill fw-semibold" style="background:#fee2e2;color:#b91c1c;font-size:.72rem;">NEEDS ACTION</span>
                    <?php else: ?>
                        <span class="badge rounded-pill fw-semibold" style="background:#f3f4f6;color:#6b7280;font-size:.72rem;">CLEAR</span>
                    <?php endif; ?>
                </div>
                <div class="fw-black mb-1" style="font-size:2rem;color:<?= $pendingCount > 0 ? '#DC2626' : '#111827' ?>;line-height:1;">
                    <?= esc($pendingCount) ?>
                </div>
                <div class="text-muted small fw-medium">Pending Registrations</div>
            </div>
            <div class="card-footer bg-transparent border-top-0 px-4 pb-3">
                <a href="<?= BASE_PATH ?>/admin/students?status=PENDING"
                   class="small fw-semibold text-decoration-none" style="color:#DC2626;">
                    Review pending &rsaquo;
                </a>
            </div>
        </div>
    </div>

    <!-- Fee Payments Due -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 rounded-3 shadow-sm h-100" style="background:#fff;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center"
                         style="background:<?= $feeDueCount > 0 ? '#fef3c7' : '#f3f4f6' ?>;width:44px;height:44px;">
                        <svg width="22" height="22" fill="none" stroke="<?= $feeDueCount > 0 ? '#d97706' : '#6b7280' ?>"
                             viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <?php if ($feeDueCount > 0): ?>
                        <span class="badge rounded-pill fw-semibold" style="background:#fef3c7;color:#92400e;font-size:.72rem;">OVERDUE</span>
                    <?php else: ?>
                        <span class="badge rounded-pill fw-semibold" style="background:#f3f4f6;color:#6b7280;font-size:.72rem;">ALL CLEAR</span>
                    <?php endif; ?>
                </div>
                <div class="fw-black mb-1" style="font-size:2rem;color:<?= $feeDueCount > 0 ? '#d97706' : '#111827' ?>;line-height:1;">
                    <?= esc($feeDueCount) ?>
                </div>
                <div class="text-muted small fw-medium">Fee Payments Due</div>
            </div>
            <div class="card-footer bg-transparent border-top-0 px-4 pb-3">
                <a href="<?= BASE_PATH ?>/admin/students?fee_status=overdue"
                   class="small fw-semibold text-decoration-none" style="color:#d97706;">
                    View overdue &rsaquo;
                </a>
            </div>
        </div>
    </div>

    <!-- 7-Day Collections -->
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 rounded-3 shadow-sm h-100" style="background:#fff;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="rounded-3 p-2 d-flex align-items-center justify-content-center"
                         style="background:#d1fae5;width:44px;height:44px;">
                        <svg width="22" height="22" fill="none" stroke="#059669" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="badge rounded-pill fw-semibold" style="background:#d1fae5;color:#065f46;font-size:.72rem;"><?= esc($payWeekCount) ?> RECORDS</span>
                </div>
                <div class="fw-black mb-1" style="font-size:1.7rem;color:#059669;line-height:1;">
                    <?= esc(formatCurrency($payWeekTotal)) ?>
                </div>
                <div class="text-muted small fw-medium">7-Day Collections</div>
            </div>
            <div class="card-footer bg-transparent border-top-0 px-4 pb-3">
                <a href="<?= BASE_PATH ?>/admin/payments"
                   class="small fw-semibold text-decoration-none" style="color:#059669;">
                    Payment records &rsaquo;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ─── Secondary Stats Row (4 cols) ─────────────────────────────────────────── -->
<div class="row g-3 mb-4">

    <!-- Upcoming Exams -->
    <div class="col-6 col-md-3">
        <div class="card border-0 rounded-3 shadow-sm h-100" style="background:#fff;">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 flex-shrink-0 d-flex align-items-center justify-content-center"
                     style="background:#ede9fe;width:42px;height:42px;">
                    <svg width="20" height="20" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <div class="fw-black" style="font-size:1.5rem;color:#111827;line-height:1.1;"><?= esc($upcomingExamCount) ?></div>
                    <div class="text-muted" style="font-size:.78rem;">Upcoming Exams</div>
                    <div style="font-size:.7rem;color:#8b5cf6;">Next 30 days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Events -->
    <div class="col-6 col-md-3">
        <div class="card border-0 rounded-3 shadow-sm h-100" style="background:#fff;">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 flex-shrink-0 d-flex align-items-center justify-content-center"
                     style="background:#fce7f3;width:42px;height:42px;">
                    <svg width="20" height="20" fill="none" stroke="#db2777" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <div class="fw-black" style="font-size:1.5rem;color:#111827;line-height:1.1;"><?= esc($upcomingEventCount) ?></div>
                    <div class="text-muted" style="font-size:.78rem;">Upcoming Events</div>
                    <div style="font-size:.7rem;color:#db2777;">Next 30 days</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Enrolled -->
    <div class="col-6 col-md-3">
        <div class="card border-0 rounded-3 shadow-sm h-100" style="background:#fff;">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 flex-shrink-0 d-flex align-items-center justify-content-center"
                     style="background:#dbeafe;width:42px;height:42px;">
                    <svg width="20" height="20" fill="none" stroke="#2563eb" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <div class="fw-black" style="font-size:1.5rem;color:#111827;line-height:1.1;"><?= esc($totalEnrolled) ?></div>
                    <div class="text-muted" style="font-size:.78rem;">Total Enrolled</div>
                    <div style="font-size:.7rem;color:#2563eb;">Active + Inactive</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent 7-Day Registrations -->
    <div class="col-6 col-md-3">
        <div class="card border-0 rounded-3 shadow-sm h-100" style="background:#fff;">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 p-2 flex-shrink-0 d-flex align-items-center justify-content-center"
                     style="background:#ffedd5;width:42px;height:42px;">
                    <svg width="20" height="20" fill="none" stroke="#ea580c" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div>
                    <div class="fw-black" style="font-size:1.5rem;color:#111827;line-height:1.1;"><?= esc($recentRegCount) ?></div>
                    <div class="text-muted" style="font-size:.78rem;">New Registrations</div>
                    <div style="font-size:.7rem;color:#ea580c;">Last 7 days</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ─── Two-Column Bottom Section ──────────────────────────────────────────── -->
<div class="row g-3">

    <!-- LEFT: Pending Registrations List -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-3 shadow-sm h-100" style="background:#fff;">
            <div class="card-header bg-transparent border-bottom px-4 py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0 fw-bold" style="font-size:.97rem;color:#111827;">Pending Registrations</h5>
                    <span class="text-muted" style="font-size:.75rem;">Last 5 awaiting review</span>
                </div>
                <?php if ($pendingCount > 0): ?>
                    <span class="badge rounded-pill fw-bold" style="background:#fee2e2;color:#b91c1c;font-size:.72rem;">
                        <?= esc($pendingCount) ?> pending
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pendingList)): ?>
                    <div class="px-4 py-5 text-center">
                        <div class="mb-3">
                            <svg width="44" height="44" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-muted mb-0 small fw-medium">No pending registrations</p>
                        <p class="text-muted mb-0" style="font-size:.75rem;">All caught up!</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pendingList as $student): ?>
                            <li class="list-group-item px-4 py-3 border-bottom">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <a href="<?= BASE_PATH ?>/admin/student?id=<?= escAttr((string)$student['id']) ?>"
                                               class="fw-semibold text-decoration-none text-dark"
                                               style="font-size:.9rem;">
                                                <?= esc($student['full_name']) ?>
                                            </a>
                                            <?php if (!empty($student['student_id'])): ?>
                                                <span class="text-muted" style="font-size:.72rem;font-family:monospace;">
                                                    <?= esc($student['student_id']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <span class="text-muted" style="font-size:.75rem;">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                Applied <?= esc(formatDate($student['created_at'])) ?>
                                            </span>
                                            <?php if (!empty($student['location_name'])): ?>
                                                <span class="text-muted" style="font-size:.75rem;">
                                                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                    <?= esc($student['location_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <!-- Approve -->
                                        <form method="POST"
                                              action="<?= BASE_PATH ?>/admin/student?id=<?= escAttr((string)$student['id']) ?>"
                                              class="d-inline"
                                              onsubmit="return confirm('Approve <?= escJs($student['full_name']) ?>?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit"
                                                    class="btn btn-sm fw-semibold"
                                                    style="background:#dcfce7;color:#15803d;border:none;font-size:.72rem;padding:.25rem .6rem;"
                                                    title="Approve registration">
                                                Approve
                                            </button>
                                        </form>
                                        <!-- Reject -->
                                        <form method="POST"
                                              action="<?= BASE_PATH ?>/admin/student?id=<?= escAttr((string)$student['id']) ?>"
                                              class="d-inline"
                                              onsubmit="return confirm('Reject <?= escJs($student['full_name']) ?>?');">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit"
                                                    class="btn btn-sm fw-semibold"
                                                    style="background:#fee2e2;color:#b91c1c;border:none;font-size:.72rem;padding:.25rem .6rem;"
                                                    title="Reject registration">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent border-top px-4 py-3">
                <a href="<?= BASE_PATH ?>/admin/students?status=PENDING"
                   class="small fw-semibold text-decoration-none" style="color:#DC2626;">
                    View all pending registrations &rsaquo;
                </a>
            </div>
        </div>
    </div>

    <!-- RIGHT: Recent Payment Records -->
    <div class="col-lg-6">
        <div class="card border-0 rounded-3 shadow-sm h-100" style="background:#fff;">
            <div class="card-header bg-transparent border-bottom px-4 py-3 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0 fw-bold" style="font-size:.97rem;color:#111827;">Recent Payments</h5>
                    <span class="text-muted" style="font-size:.75rem;">Last 5 recorded transactions</span>
                </div>
                <span class="badge rounded-pill fw-bold" style="background:#d1fae5;color:#065f46;font-size:.72rem;">
                    <?= esc(formatCurrency($payWeekTotal)) ?> / 7d
                </span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($paymentList)): ?>
                    <div class="px-4 py-5 text-center">
                        <div class="mb-3">
                            <svg width="44" height="44" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-muted mb-0 small fw-medium">No payments recorded yet</p>
                        <p class="text-muted mb-0" style="font-size:.75rem;">
                            <a href="<?= BASE_PATH ?>/admin/fees" class="text-decoration-none" style="color:#DC2626;">Record the first payment</a>
                        </p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($paymentList as $pay): ?>
                            <li class="list-group-item px-4 py-3 border-bottom">
                                <div class="d-flex align-items-start justify-content-between gap-2">
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                            <a href="<?= BASE_PATH ?>/admin/student?id=<?= escAttr((string)$pay['student_profile_id']) ?>"
                                               class="fw-semibold text-decoration-none text-dark"
                                               style="font-size:.9rem;">
                                                <?= esc($pay['full_name']) ?>
                                            </a>
                                            <span class="text-muted" style="font-size:.72rem;font-family:monospace;">
                                                <?= esc($pay['student_id']) ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 flex-wrap">
                                            <span class="text-muted" style="font-size:.75rem;">
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" class="me-1" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <?= esc(formatDate($pay['payment_date'])) ?>
                                            </span>
                                            <span class="text-muted" style="font-size:.75rem;">
                                                <?= esc($pay['payment_method'] ?? '—') ?>
                                            </span>
                                            <?php if (!empty($pay['payment_period'])): ?>
                                                <span class="text-muted" style="font-size:.75rem;">
                                                    <?= esc($pay['payment_period']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="text-muted" style="font-size:.7rem;font-family:monospace;">
                                                <?= esc($pay['payment_ref']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 text-end">
                                        <div class="fw-bold" style="color:#059669;font-size:.95rem;">
                                            <?= esc(formatCurrency($pay['amount'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent border-top px-4 py-3">
                <a href="<?= BASE_PATH ?>/admin/fees"
                   class="small fw-semibold text-decoration-none" style="color:#059669;">
                    View all payments &rsaquo;
                </a>
            </div>
        </div>
    </div>
</div>

<?php require APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
