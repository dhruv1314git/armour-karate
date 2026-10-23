<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Dashboard
 * app/views/pages/student/dashboard.php
 *
 * Faithfully recreates the original Next.js student dashboard:
 *  - Welcome profile header with student ID & status badge
 *  - 4 status metric cards: Current Belt, Fee Clearance, Next Exam, Preferred Dojo
 *  - Recent fee payment receipts table
 *  - Belt examinations and grading evaluation overview
 *  - Upcoming academy tournaments & conditioning camps
 *  - Dojo Kun philosophical principles
 *  - Strict IDOR scope: Only authenticated student's data is accessed
 */

declare(strict_types=1);

$sessionStudent = requireStudent();
$pdo = db();

$pageTitle  = 'Student Dashboard';
$portalType = 'student';

// Fetch fresh student profile & belt info
$stmtProfile = $pdo->prepare(
    "SELECT sp.*, u.email, u.image AS user_image, 
            b.name AS belt_name, b.color AS belt_color, b.rank AS belt_rank,
            cl.location_name, cl.training_days
     FROM student_profiles sp
     JOIN users u ON u.id = sp.user_id
     LEFT JOIN belts b ON b.id = sp.current_belt_id
     LEFT JOIN class_locations cl ON cl.id = sp.preferred_location_id
     WHERE sp.id = ? AND sp.user_id = ?
     LIMIT 1"
);
$stmtProfile->execute([$sessionStudent['profile_id'], $sessionStudent['user_id']]);
$student = $stmtProfile->fetch();

if (!$student) {
    // Session state out of sync with database
    destroySession();
    safeRedirect(BASE_PATH . '/login');
}

// Fetch recent payments (IDOR-scoped)
$stmtPayments = $pdo->prepare(
    "SELECT * FROM payments 
     WHERE student_profile_id = ? 
     ORDER BY payment_date DESC 
     LIMIT 3"
);
$stmtPayments->execute([$student['id']]);
$recentPayments = $stmtPayments->fetchAll();

// Fetch upcoming exams student is registered for (IDOR-scoped)
$stmtExams = $pdo->prepare(
    "SELECT er.*, e.exam_name, e.exam_date, e.time, e.venue, e.exam_fee,
            b.name AS target_belt_name, b.color AS target_belt_color
     FROM exam_registrations er
     JOIN exams e ON e.id = er.exam_id
     LEFT JOIN belts b ON b.id = e.target_belt_id
     WHERE er.student_profile_id = ? AND e.exam_date >= CURDATE()
     ORDER BY e.exam_date ASC 
     LIMIT 2"
);
$stmtExams->execute([$student['id']]);
$upcomingExams = $stmtExams->fetchAll();

// Fetch published academy events
$stmtEvents = $pdo->query(
    "SELECT * FROM events 
     WHERE status = 'PUBLISHED' AND event_date >= CURDATE() 
     ORDER BY event_date ASC 
     LIMIT 3"
);
$upcomingEvents = $stmtEvents->fetchAll();

// Calculate Fee Clearance Status
$latestPayment = $recentPayments[0] ?? null;
$feesPaidUpTo  = $latestPayment['fees_paid_up_to'] ?? null;
$isFeeDue      = true;
if ($feesPaidUpTo) {
    $isFeeDue = strtotime($feesPaidUpTo) < strtotime('today');
}

$studentIdDisplay = !empty($student['student_id']) ? $student['student_id'] : 'PENDING';
$beltColor        = $student['belt_color'] ?? '#FFFFFF';
$beltName         = $student['belt_name']  ?? 'White Belt';
$beltRank         = $student['belt_rank']  ?? 1;

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- 1. Profile Welcome Banner -->
    <div class="rounded-4 p-4 p-sm-5 text-white position-relative overflow-hidden shadow-sm"
         style="background: linear-gradient(135deg, #141416 0%, #1f0808 100%); border: 1px solid #27272a;">
        <div class="position-absolute top-0 end-0 rounded-circle pointer-events-none"
             style="width: 320px; height: 320px; background: rgba(220, 38, 38, 0.12); filter: blur(120px);"></div>

        <div class="position-relative z-1 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
            <div class="d-flex align-items-center gap-4">
                <div class="rounded-3 d-flex align-items-center justify-content-center text-white fw-black shadow-lg flex-shrink-0"
                     style="width: 72px; height: 72px; background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%); font-size: 1.75rem; border: 2px solid rgba(220, 38, 38, 0.4);">
                    <?php if (!empty($student['profile_photo_url'])): ?>
                        <img src="<?= escAttr(uploadUrl($student['profile_photo_url'])) ?>" alt="<?= escAttr($student['full_name']) ?>" class="w-100 h-100 object-fit-cover rounded-3">
                    <?php else: ?>
                        <?= strtoupper(substr($student['full_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h1 class="fs-4 fw-black text-uppercase text-white mb-0" style="letter-spacing: -0.01em;">
                            <?= esc($student['full_name']) ?>
                        </h1>
                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.08em;">
                            <?= esc($student['status']) ?>
                        </span>
                    </div>
                    <div class="text-secondary small font-monospace" style="font-size: 0.8rem;">
                        Student ID: <span class="text-danger fw-bold"><?= esc($studentIdDisplay) ?></span>
                        <span class="mx-1">&bull;</span>
                        Member since <?= formatDate($student['joining_date']) ?>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="<?= BASE_PATH ?>/student/profile" class="btn btn-outline-light btn-sm fw-bold text-uppercase px-3 py-2" style="font-size: 0.75rem; letter-spacing: 0.05em; background: rgba(255, 255, 255, 0.05); border-color: #3f3f46;">
                    View Full Profile
                </a>
            </div>
        </div>
    </div>

    <!-- 2. Key Status Metric Cards -->
    <div class="row g-3">
        <!-- Current Belt Card -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.06em;">Current Belt</span>
                    <div class="p-2 rounded-3" style="background: rgba(220, 38, 38, 0.1); color: #dc2626;">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/></svg>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="rounded-circle border shadow-sm" style="width: 16px; height: 16px; background-color: <?= escAttr($beltColor) ?>;"></span>
                        <span class="fs-5 fw-black text-dark text-truncate"><?= esc($beltName) ?></span>
                    </div>
                    <div class="text-secondary small" style="font-size: 0.78rem;">
                        Rank <?= esc((string)$beltRank) ?> &bull; Goju-Ryu Syllabus
                    </div>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <a href="<?= BASE_PATH ?>/student/belt-journey" class="text-danger fw-bold text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                        <span>View Belt Journey</span>
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Fee Clearance Card -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.06em;">Fee Clearance</span>
                    <div class="p-2 rounded-3" style="background: <?= $isFeeDue ? 'rgba(220, 38, 38, 0.1)' : 'rgba(16, 185, 129, 0.1)' ?>; color: <?= $isFeeDue ? '#dc2626' : '#10b981' ?>;">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1H2Zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V7Z"/></svg>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="fs-5 fw-black <?= $isFeeDue ? 'text-danger' : 'text-success' ?> mb-1">
                        <?= $feesPaidUpTo ? formatDate($feesPaidUpTo) : 'No Record' ?>
                    </div>
                    <div class="text-secondary small" style="font-size: 0.78rem;">
                        Status: <span class="fw-bold <?= $isFeeDue ? 'text-danger' : 'text-success' ?>"><?= $isFeeDue ? 'Payment Due' : 'Paid & Up to Date' ?></span>
                    </div>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <a href="<?= BASE_PATH ?>/student/fees" class="text-danger fw-bold text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                        <span>Payment History</span>
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Next Exam Card -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.06em;">Next Belt Exam</span>
                    <div class="p-2 rounded-3" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.917l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.917l-7.5-3.5Z"/></svg>
                    </div>
                </div>
                <div class="mb-3">
                    <?php if (!empty($upcomingExams)): ?>
                        <div class="fs-6 fw-bold text-dark text-truncate mb-1">
                            <?= esc($upcomingExams[0]['exam_name']) ?>
                        </div>
                        <div class="text-secondary small" style="font-size: 0.78rem;">
                            <?= formatDate($upcomingExams[0]['exam_date']) ?> &bull; <?= esc($upcomingExams[0]['result'] ?? 'REGISTERED') ?>
                        </div>
                    <?php else: ?>
                        <div class="fs-6 fw-bold text-secondary mb-1">
                            No Active Registration
                        </div>
                        <div class="text-secondary small" style="font-size: 0.78rem;">
                            Exams scheduled bi-annually
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <a href="<?= BASE_PATH ?>/student/exams" class="text-danger fw-bold text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                        <span>Exam Records</span>
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Training Dojo Card -->
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-secondary text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.06em;">Training Dojo</span>
                    <div class="p-2 rounded-3" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="fs-6 fw-bold text-dark text-truncate mb-1">
                        <?= esc($student['location_name'] ?? 'Central Dojo Headquarters') ?>
                    </div>
                    <div class="text-secondary small text-truncate" style="font-size: 0.78rem;">
                        <?= esc($student['training_days'] ?? 'Mon, Wed, Fri & Sat') ?>
                    </div>
                </div>
                <div class="mt-auto pt-2 border-top">
                    <a href="<?= BASE_PATH ?>/student/class-info" class="text-danger fw-bold text-decoration-none d-inline-flex align-items-center gap-1" style="font-size: 0.78rem;">
                        <span>Dojo Schedules</span>
                        <svg width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Main Dashboard Grid -->
    <div class="row g-4">
        <!-- Left Column: Recent Payments & Belt Exams -->
        <div class="col-lg-8 d-flex flex-column gap-4">

            <!-- Recent Payments Table -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fs-6 fw-bold text-dark mb-1">Recent Fee Payments</h2>
                        <p class="text-secondary small mb-0" style="font-size: 0.75rem;">Your official recorded dues and receipt summaries</p>
                    </div>
                    <a href="<?= BASE_PATH ?>/student/fees" class="text-danger fw-bold text-decoration-none small" style="font-size: 0.75rem;">
                        View All
                    </a>
                </div>

                <?php if (!empty($recentPayments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                            <thead class="table-light text-uppercase text-secondary" style="font-size: 0.7rem;">
                                <tr>
                                    <th class="py-2.5">Receipt Ref</th>
                                    <th class="py-2.5">Period</th>
                                    <th class="py-2.5">Date</th>
                                    <th class="py-2.5">Amount</th>
                                    <th class="py-2.5">Paid Up To</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPayments as $p): ?>
                                    <tr>
                                        <td class="font-monospace fw-bold text-dark py-3"><?= esc($p['payment_ref']) ?></td>
                                        <td class="text-secondary py-3"><?= esc($p['payment_period']) ?></td>
                                        <td class="text-secondary py-3"><?= formatDate($p['payment_date']) ?></td>
                                        <td class="fw-bold text-dark py-3"><?= formatCurrency($p['amount']) ?></td>
                                        <td class="text-success fw-semibold py-3"><?= $p['fees_paid_up_to'] ? formatDate($p['fees_paid_up_to']) : '—' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="py-5 text-center text-secondary small">
                        No payment transactions recorded yet.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Belt Examinations Box -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="fs-6 fw-bold text-dark mb-1">Belt Examinations &amp; Gradings</h2>
                        <p class="text-secondary small mb-0" style="font-size: 0.75rem;">Assessments for Kyu and Dan rank advancements</p>
                    </div>
                    <a href="<?= BASE_PATH ?>/student/exams" class="text-danger fw-bold text-decoration-none small" style="font-size: 0.75rem;">
                        All Exams
                    </a>
                </div>

                <?php if (!empty($upcomingExams)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($upcomingExams as $reg): ?>
                            <div class="p-3 rounded-3 border bg-light d-flex align-items-center justify-content-between gap-3">
                                <div>
                                    <span class="fs-6 fw-bold text-dark d-block"><?= esc($reg['exam_name']) ?></span>
                                    <span class="text-secondary small" style="font-size: 0.75rem;">
                                        <?= formatDate($reg['exam_date']) ?> &bull; Venue: <?= esc($reg['venue']) ?>
                                    </span>
                                </div>
                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 text-uppercase fw-bold" style="font-size: 0.7rem;">
                                    <?= esc($reg['result'] ?? 'REGISTERED') ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-5 text-center text-secondary small">
                        You are not currently registered for any upcoming belt examination.
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Right Column: Upcoming Events & Dojo Kun -->
        <div class="col-lg-4 d-flex flex-column gap-4">

            <!-- Upcoming Events Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="fs-6 fw-bold text-dark mb-0">Upcoming Events</h2>
                    <a href="<?= BASE_PATH ?>/student/events" class="text-danger fw-bold text-decoration-none small" style="font-size: 0.75rem;">
                        Calendar
                    </a>
                </div>

                <?php if (!empty($upcomingEvents)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($upcomingEvents as $ev): ?>
                            <div class="p-3 rounded-3 border bg-light">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">
                                    <?= esc(str_replace('_', ' ', $ev['type'])) ?>
                                </span>
                                <h3 class="fs-6 fw-bold text-dark mb-1" style="font-size: 0.85rem;"><?= esc($ev['event_name']) ?></h3>
                                <div class="text-secondary small d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                    <svg width="12" height="12" fill="currentColor" class="text-danger" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                    <span><?= formatDate($ev['event_date']) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-4 text-center text-secondary small">
                        No public events scheduled currently.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Dojo Kun Card -->
            <div class="rounded-4 p-4 text-white shadow-sm"
                 style="background: linear-gradient(135deg, #141416 0%, #000000 100%); border: 1px solid #27272a;">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <svg width="16" height="16" fill="currentColor" class="text-danger" viewBox="0 0 16 16"><path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/></svg>
                    <h3 class="fs-6 fw-bold text-uppercase text-danger mb-0" style="font-size: 0.75rem; letter-spacing: 0.08em;">
                        Dojo Kun (Rules of The Dojo)
                    </h3>
                </div>
                <p class="text-light small fst-italic mb-3" style="font-size: 0.78rem; line-height: 1.6; color: #d4d4d8 !important;">
                    &ldquo;Strive for perfection of character. Be faithful and defend the paths of truth. Foster the spirit of effort. Honor the principles of etiquette. Guard against impetuous courage.&rdquo;
                </p>
                <div class="text-end text-secondary text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.1em;">
                    &mdash; Master Gichin Funakoshi &amp; Goju-Ryu Masters
                </div>
            </div>

        </div>
    </div>

</div>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
