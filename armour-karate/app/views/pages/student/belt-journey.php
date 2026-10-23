<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Belt Journey & Promotion Roadmap
 * app/views/pages/student/belt-journey.php
 *
 * Faithfully recreates the original Next.js belt journey page:
 *  - Current attained belt hero card with Kyu rank
 *  - Complete 8-belt syllabus roadmap with attained/current indicators
 *  - Chronological promotion history timeline (old belt -> new belt)
 *  - Remarks and examination verification links
 *  - Authentic empty state for students beginning their journey
 *  - Strict IDOR isolation: Only authenticated student's history is queried
 */

declare(strict_types=1);

$sessionStudent = requireStudent();
$pdo = db();

$pageTitle  = 'My Belt Journey';
$portalType = 'student';

// Fetch current student profile & current belt
$stmtStudent = $pdo->prepare(
    "SELECT sp.id, sp.full_name, sp.current_belt_id,
            b.name AS belt_name, b.color AS belt_color, b.rank AS belt_rank
     FROM student_profiles sp
     LEFT JOIN belts b ON b.id = sp.current_belt_id
     WHERE sp.id = ? AND sp.user_id = ?
     LIMIT 1"
);
$stmtStudent->execute([$sessionStudent['profile_id'], $sessionStudent['user_id']]);
$student = $stmtStudent->fetch();

if (!$student) {
    destroySession();
    safeRedirect(BASE_PATH . '/login');
}

$currentRank = (int)($student['belt_rank'] ?? 1);
$currentBeltName = $student['belt_name'] ?? 'White Belt';
$currentBeltColor = $student['belt_color'] ?? '#FFFFFF';

// Fetch all 8 configured academy belts ordered by rank
$stmtBelts = $pdo->query("SELECT * FROM belts ORDER BY rank ASC");
$allBelts = $stmtBelts->fetchAll();

// Fetch promotion history for this student (IDOR-scoped)
$stmtHistory = $pdo->prepare(
    "SELECT sbh.*, 
            ob.name AS old_belt_name, ob.color AS old_belt_color,
            nb.name AS new_belt_name, nb.color AS new_belt_color,
            e.exam_name
     FROM student_belt_history sbh
     JOIN belts nb ON nb.id = sbh.new_belt_id
     LEFT JOIN belts ob ON ob.id = sbh.old_belt_id
     LEFT JOIN exam_registrations er ON er.id = sbh.exam_registration_id
     LEFT JOIN exams e ON e.id = er.exam_id
     WHERE sbh.student_profile_id = ?
     ORDER BY sbh.promotion_date DESC, sbh.id DESC"
);
$stmtHistory->execute([$student['id']]);
$beltHistory = $stmtHistory->fetchAll();

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Page Title & Header -->
    <div class="border-bottom pb-3">
        <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
            My Belt Journey
        </h1>
        <p class="text-secondary small mb-0" style="font-size: 0.8rem;">
            Official progression timeline in traditional Goju-Ryu Karate-Do
        </p>
    </div>

    <!-- Current Belt Hero Highlight -->
    <div class="rounded-4 p-4 p-sm-5 text-white position-relative overflow-hidden shadow-sm"
         style="background: linear-gradient(135deg, #0f0f11 0%, #1a0808 100%); border: 1px solid #27272a;">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-4">
            <div class="d-flex align-items-center gap-4">
                <div class="rounded-3 d-flex align-items-center justify-content-center fw-black shadow-lg flex-shrink-0"
                     style="width: 72px; height: 72px; background-color: <?= escAttr($currentBeltColor) ?>; color: <?= ($currentBeltColor === '#FFFFFF' || $currentBeltColor === '#ffffff' || $currentBeltColor === '#FDE047') ? '#0A0A0A' : '#FFFFFF' ?>; border: 2px solid rgba(255, 255, 255, 0.2);">
                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/></svg>
                </div>
                <div>
                    <span class="text-danger fw-bold text-uppercase d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.12em;">
                        Current Attained Rank
                    </span>
                    <h2 class="fs-3 fw-black text-uppercase text-white mb-1" style="letter-spacing: -0.01em;">
                        <?= esc($currentBeltName) ?>
                    </h2>
                    <p class="text-secondary small mb-0" style="font-size: 0.8rem;">
                        Kyu Rank <?= esc((string)$currentRank) ?> &bull; International Goju-Ryu Syllabus
                    </p>
                </div>
            </div>

            <div class="p-3 rounded-3 text-sm-end" style="background: rgba(255, 255, 255, 0.05); border: 1px solid #27272a;">
                <span class="text-secondary text-uppercase fw-bold d-block" style="font-size: 0.65rem; letter-spacing: 0.08em;">
                    Promotions Recorded
                </span>
                <span class="fs-4 fw-black text-danger">
                    <?= count($beltHistory) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Complete Academy Belt Roadmap -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
        <h2 class="fs-6 fw-bold text-dark mb-1">Goju-Ryu Belt Syllabus Roadmap</h2>
        <p class="text-secondary small mb-4" style="font-size: 0.75rem;">
            Standard grading ranks from beginner 10th Kyu up to master 1st Dan Black Belt
        </p>

        <div class="row row-cols-2 row-cols-sm-4 row-cols-lg-8 g-3 pt-1">
            <?php foreach ($allBelts as $b): 
                $rankVal = (int) $b['rank'];
                $isAttained = $rankVal <= $currentRank;
                $isCurrent  = $rankVal === $currentRank;
            ?>
                <div class="col">
                    <div class="p-3 rounded-3 text-center d-flex flex-column align-items-center h-100 transition-all border <?= $isCurrent ? 'border-danger bg-danger bg-opacity-10 shadow-sm' : ($isAttained ? 'border-success bg-success bg-opacity-10' : 'border-light-subtle bg-light opacity-50') ?>">
                        <!-- Swatch Circle -->
                        <div class="rounded-circle border shadow-sm d-flex align-items-center justify-content-center mb-2"
                             style="width: 34px; height: 34px; background-color: <?= escAttr($b['color']) ?>; border-color: rgba(0, 0, 0, 0.15) !important;">
                            <?php if ($isAttained): ?>
                                <svg width="16" height="16" fill="<?= ($b['color'] === '#FFFFFF' || $b['color'] === '#ffffff' || $b['color'] === '#FDE047') ? '#000' : '#fff' ?>" viewBox="0 0 16 16"><path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425z"/></svg>
                            <?php endif; ?>
                        </div>
                        <div class="text-truncate w-100">
                            <span class="fs-6 fw-bold text-dark d-block text-truncate" style="font-size: 0.78rem;">
                                <?= esc($b['name']) ?>
                            </span>
                            <span class="text-secondary small d-block" style="font-size: 0.7rem;">
                                Rank <?= esc((string)$b['rank']) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Promotion History Timeline -->
    <div class="card border-0 shadow-sm rounded-4 p-4 p-sm-5 bg-white">
        <h2 class="fs-6 fw-bold text-dark mb-1">Promotion History Records</h2>
        <p class="text-secondary small mb-4" style="font-size: 0.75rem;">
            Official chronological log of past belt promotions and grading evaluations
        </p>

        <?php if (!empty($beltHistory)): ?>
            <div class="position-relative ps-4 border-start border-2 border-danger-subtle ms-3 d-flex flex-column gap-4">
                <?php foreach ($beltHistory as $h): ?>
                    <div class="position-relative">
                        <!-- Timeline Dot -->
                        <span class="position-absolute rounded-circle border border-2 border-white shadow-sm"
                              style="width: 16px; height: 16px; background-color: <?= escAttr($h['new_belt_color']) ?>; left: -25px; top: 4px;"></span>

                        <div class="p-3 rounded-3 bg-light border">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-secondary small" style="font-size: 0.78rem;">
                                        <?= esc($h['old_belt_name'] ?? 'White Belt (Joining)') ?>
                                    </span>
                                    <svg width="12" height="12" fill="currentColor" class="text-secondary" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
                                    <span class="fs-6 fw-bold text-dark">
                                        <?= esc($h['new_belt_name']) ?>
                                    </span>
                                </div>
                                <div class="text-secondary small d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                    <svg width="12" height="12" fill="currentColor" class="text-danger" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                    <span><?= formatDate($h['promotion_date']) ?></span>
                                </div>
                            </div>

                            <?php if (!empty($h['remarks'])): ?>
                                <p class="text-secondary small fst-italic mb-2" style="font-size: 0.78rem;">
                                    &ldquo;<?= esc($h['remarks']) ?>&rdquo;
                                </p>
                            <?php endif; ?>

                            <?php if (!empty($h['exam_name'])): ?>
                                <div class="text-secondary small font-monospace" style="font-size: 0.7rem;">
                                    Verified from exam: <span class="fw-bold text-dark"><?= esc($h['exam_name']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="py-5 text-center bg-light rounded-4 border p-4">
                <div class="p-3 rounded-circle bg-white shadow-sm d-inline-flex mb-3 text-secondary">
                    <svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/></svg>
                </div>
                <h3 class="fs-6 fw-bold text-dark mb-1">Your journey begins at White Belt!</h3>
                <p class="text-secondary small mb-0" style="font-size: 0.8rem; max-width: 420px; margin: 0 auto;">
                    Attend regular dojo sessions and prepare for the upcoming Kyu grading examination to receive your first promotion.
                </p>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
