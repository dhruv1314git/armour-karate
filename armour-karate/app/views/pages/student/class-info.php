<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Class Information & Training Centers
 * app/views/pages/student/class-info.php
 *
 * Faithfully recreates the original Next.js class-info page:
 *  - Displays verified, active training centers and dojos
 *  - Highlights the student's primary preferred dojo with a red shield badge
 *  - Training days, batch timings, instructor information & safe Google Maps links
 *  - Truthful state when unverified/demo locations are deactivated
 *  - Safe fallback messaging per Stage 1/2 database corrections
 */

declare(strict_types=1);

$sessionStudent = requireStudent();
$pdo = db();

$pageTitle  = 'Training Centers & Class Schedules';
$portalType = 'student';

// Fetch student's preferred location ID
$stmtStudent = $pdo->prepare(
    "SELECT preferred_location_id FROM student_profiles WHERE id = ? AND user_id = ? LIMIT 1"
);
$stmtStudent->execute([$sessionStudent['profile_id'], $sessionStudent['user_id']]);
$student = $stmtStudent->fetch();

$preferredLocationId = (int)($student['preferred_location_id'] ?? 0);

// Fetch verified, active class locations
$stmtLocations = $pdo->query(
    "SELECT * FROM class_locations WHERE is_active = 1 ORDER BY location_name ASC"
);
$locations = $stmtLocations->fetchAll();

// Check if preferred location is assigned and active
$hasActivePreferred = false;
if ($preferredLocationId > 0) {
    foreach ($locations as $loc) {
        if ((int)$loc['id'] === $preferredLocationId) {
            $hasActivePreferred = true;
            break;
        }
    }
}

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Page Title & Header -->
    <div class="border-bottom pb-3">
        <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
            Training Centers &amp; Class Schedules
        </h1>
        <p class="text-secondary small mb-0" style="font-size: 0.8rem;">
            Official dojo locations, batch timings, and assigned head instructors
        </p>
    </div>

    <?php if (!empty($locations)): ?>
        <div class="row g-4">
            <?php foreach ($locations as $loc): 
                $isPreferred = ((int)$loc['id'] === $preferredLocationId);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card rounded-4 p-4 h-100 shadow-sm bg-white d-flex flex-column justify-content-between transition-all <?= $isPreferred ? 'border-2 border-danger shadow ring-2 ring-danger-subtle' : 'border' ?>">
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <?php if ($isPreferred): ?>
                                    <span class="badge bg-danger text-uppercase fw-bold d-inline-flex align-items-center gap-1 px-3 py-1.5" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                                        <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/></svg>
                                        <span>Your Primary Dojo</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border text-uppercase fw-bold" style="font-size: 0.68rem;">
                                        Active Dojo
                                    </span>
                                <?php endif; ?>

                                <svg width="20" height="20" fill="currentColor" class="<?= $isPreferred ? 'text-danger' : 'text-secondary' ?>" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                            </div>

                            <h2 class="fs-6 fw-bold text-dark mb-1"><?= esc($loc['location_name']) ?></h2>
                            <p class="text-secondary small mb-3" style="font-size: 0.78rem; line-height: 1.5;"><?= esc($loc['address']) ?></p>

                            <div class="pt-3 border-top d-flex flex-column gap-2 text-secondary small" style="font-size: 0.75rem;">
                                <?php if (!empty($loc['instructor'])): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-dark">Sensei:</span>
                                        <span><?= esc($loc['instructor']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex align-items-center gap-2">
                                    <svg width="13" height="13" fill="currentColor" class="text-danger flex-shrink-0" viewBox="0 0 16 16"><path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/></svg>
                                    <span><?= esc($loc['training_days']) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <svg width="13" height="13" fill="currentColor" class="text-danger flex-shrink-0" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
                                    <span><?= esc($loc['training_time']) ?></span>
                                </div>
                                <?php if (!empty($loc['contact'])): ?>
                                    <div class="d-flex align-items-center gap-2">
                                        <svg width="13" height="13" fill="currentColor" class="text-danger flex-shrink-0" viewBox="0 0 16 16"><path d="M11 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h6zM5 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H5z"/></svg>
                                        <span><?= esc($loc['contact']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!empty($loc['map_url'])): ?>
                            <div class="pt-3 border-top">
                                <a href="<?= escAttr($loc['map_url']) ?>" target="_blank" rel="noopener noreferrer" class="text-danger fw-bold text-decoration-none d-inline-flex align-items-center gap-1 small" style="font-size: 0.78rem;">
                                    <span>Open in Google Maps</span>
                                    <svg width="12" height="12" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/><path fill-rule="evenodd" d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/></svg>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="py-5 text-center bg-white rounded-4 border p-4 shadow-sm">
            <div class="p-3 rounded-circle bg-light d-inline-flex mb-3 text-secondary">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
            </div>
            <h2 class="fs-6 fw-bold text-dark mb-1">Dojo Information &amp; Schedule Notice</h2>
            <p class="text-secondary small mb-3" style="font-size: 0.8rem; max-width: 520px; margin: 0 auto; line-height: 1.6;">
                Dojo batch assignments and seasonal branch schedules are managed directly by the Chief Instructor. Please consult the dojo administration or check your registration verification card for your assigned training batch.
            </p>
            <a href="<?= BASE_PATH ?>/contact" class="btn btn-outline-danger btn-sm text-uppercase fw-bold px-3 py-2" style="font-size: 0.75rem; letter-spacing: 0.05em;">
                Contact Head Office &rarr;
            </a>
        </div>
    <?php endif; ?>

</div>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
