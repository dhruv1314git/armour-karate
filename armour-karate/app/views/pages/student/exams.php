<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Belt Examinations & Results
 * app/views/pages/student/exams.php
 *
 * Faithfully recreates the original Next.js student exams page:
 *  - Upcoming registered exams with target belt badges, date, time, venue & exam fee
 *  - Past grading evaluations & results table (marks, PASS/FAIL badge, promotion status, remarks)
 *  - Dignified empty states
 *  - Strict IDOR scope: Only exams linked to the authenticated student's profile are shown
 */

declare(strict_types=1);

$sessionStudent = requireStudent();
$pdo = db();

$pageTitle  = 'Belt Examinations & Results';
$portalType = 'student';

// Fetch student profile ID
$stmtStudent = $pdo->prepare(
    "SELECT id FROM student_profiles WHERE id = ? AND user_id = ? LIMIT 1"
);
$stmtStudent->execute([$sessionStudent['profile_id'], $sessionStudent['user_id']]);
$student = $stmtStudent->fetch();

if (!$student) {
    destroySession();
    safeRedirect(BASE_PATH . '/login');
}

// Fetch all exam registrations for this student (IDOR-scoped)
$stmtRegistrations = $pdo->prepare(
    "SELECT er.*, 
            e.exam_name, e.exam_date, e.time, e.venue, e.exam_fee, e.status AS exam_status,
            b.name AS target_belt_name, b.color AS target_belt_color
     FROM exam_registrations er
     JOIN exams e ON e.id = er.exam_id
     LEFT JOIN belts b ON b.id = e.target_belt_id
     WHERE er.student_profile_id = ?
     ORDER BY e.exam_date DESC, e.id DESC"
);
$stmtRegistrations->execute([$student['id']]);
$registrations = $stmtRegistrations->fetchAll();

$today = date('Y-m-d');
$upcoming = [];
$past = [];

foreach ($registrations as $r) {
    if ($r['exam_date'] >= $today && $r['exam_status'] !== 'COMPLETED' && $r['exam_status'] !== 'CANCELLED') {
        $upcoming[] = $r;
    } else {
        $past[] = $r;
    }
}

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Page Title & Header -->
    <div class="border-bottom pb-3">
        <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
            Belt Examinations &amp; Results
        </h1>
        <p class="text-secondary small mb-0" style="font-size: 0.8rem;">
            Official grading assessments, registration tickets, and evaluation scores
        </p>
    </div>

    <!-- Upcoming Registered Examinations -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
        <div class="mb-4">
            <h2 class="fs-6 fw-bold text-dark mb-1">Upcoming Registered Examinations</h2>
            <p class="text-secondary small mb-0" style="font-size: 0.75rem;">
                Scheduled tests for your next belt rank promotion
            </p>
        </div>

        <?php if (!empty($upcoming)): ?>
            <div class="row g-3">
                <?php foreach ($upcoming as $reg): ?>
                    <div class="col-md-6">
                        <div class="p-4 rounded-3 border bg-light h-100 d-flex flex-column justify-content-between">
                            <div class="mb-3">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                    <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50 text-uppercase fw-bold" style="font-size: 0.7rem;">
                                        Target Belt: <?= esc($reg['target_belt_name'] ?? 'Next Kyu') ?>
                                    </span>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 text-uppercase fw-bold" style="font-size: 0.68rem;">
                                        <?= esc($reg['result'] ?? 'REGISTERED') ?>
                                    </span>
                                </div>
                                <h3 class="fs-6 fw-bold text-dark mb-3"><?= esc($reg['exam_name']) ?></h3>

                                <div class="d-flex flex-column gap-2 text-secondary small" style="font-size: 0.78rem;">
                                    <div class="d-flex align-items-center gap-2">
                                        <svg width="14" height="14" fill="currentColor" class="text-danger flex-shrink-0" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                        <span><?= formatDate($reg['exam_date']) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <svg width="14" height="14" fill="currentColor" class="text-danger flex-shrink-0" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
                                        <span><?= esc($reg['time'] ?: '9:00 AM') ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <svg width="14" height="14" fill="currentColor" class="text-danger flex-shrink-0" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                                        <span class="text-truncate"><?= esc($reg['venue']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 border-top d-flex justify-content-between align-items-center small" style="font-size: 0.78rem;">
                                <span class="text-secondary">Examination Fee:</span>
                                <span class="fw-bold text-dark"><?= formatCurrency($reg['exam_fee']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="py-5 text-center bg-light rounded-4 border p-4 text-secondary small">
                You do not have any upcoming scheduled belt examinations.
            </div>
        <?php endif; ?>
    </div>

    <!-- Past Exam History & Results Table -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
        <div class="mb-4">
            <h2 class="fs-6 fw-bold text-dark mb-1">Grading History &amp; Results</h2>
            <p class="text-secondary small mb-0" style="font-size: 0.75rem;">
                Past assessment evaluations and committee remarks
            </p>
        </div>

        <?php if (!empty($past)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                    <thead class="table-light text-uppercase text-secondary" style="font-size: 0.7rem;">
                        <tr>
                            <th class="py-3 font-bold">Exam Name</th>
                            <th class="py-3 font-bold">Target Belt</th>
                            <th class="py-3 font-bold">Exam Date</th>
                            <th class="py-3 font-bold">Marks</th>
                            <th class="py-3 font-bold">Result</th>
                            <th class="py-3 font-bold">Promotion Status</th>
                            <th class="py-3 font-bold">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past as $r): ?>
                            <tr>
                                <td class="fw-bold text-dark py-3"><?= esc($r['exam_name']) ?></td>
                                <td class="text-secondary py-3"><?= esc($r['target_belt_name'] ?? 'Kyu Grading') ?></td>
                                <td class="text-secondary py-3"><?= formatDate($r['exam_date']) ?></td>
                                <td class="fw-bold text-dark py-3">
                                    <?= $r['marks'] !== null ? esc((string)$r['marks']) . '/100' : '—' ?>
                                </td>
                                <td class="py-3">
                                    <?php if ($r['result'] === 'PASS'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1 text-uppercase fw-bold" style="font-size: 0.68rem;">
                                            PASS
                                        </span>
                                    <?php elseif ($r['result'] === 'FAIL'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1 text-uppercase fw-bold" style="font-size: 0.68rem;">
                                            FAIL
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 px-2 py-1 text-uppercase fw-bold" style="font-size: 0.68rem;">
                                            PENDING
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3">
                                    <span class="fw-bold text-uppercase" style="font-size: 0.72rem; color: <?= $r['promotion_status'] === 'PROMOTED' ? '#10b981' : '#71717a' ?>;">
                                        <?= esc(str_replace('_', ' ', $r['promotion_status'])) ?>
                                    </span>
                                </td>
                                <td class="text-secondary py-3 text-truncate" style="max-width: 220px;">
                                    <?= !empty($r['remarks']) ? esc($r['remarks']) : '—' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-5 text-center text-secondary small">
                No past examination results found on your record.
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
