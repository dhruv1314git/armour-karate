<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Black Belt Hall of Fame — Public Page
 * app/views/pages/public/black-belts.php
 *
 * Faithfully recreates the original Next.js Black Belts Hall of Fame:
 *  - Hero banner with flame icon badge
 *  - Dan Ranks & Martial Honor cards grid
 *  - Dan badge, rank, year achieved, achievements, tournament honors, quote
 *  - Motivational quote banner at bottom
 *  - Professional empty state when no verified profiles are visible
 *
 * Data source: `black_belt_profiles` WHERE is_visible = 1 ORDER BY display_order ASC
 * Note: Demonstration profiles have is_visible = 0 and are strictly hidden.
 */

declare(strict_types=1);

$pageTitle       = 'Black Belt Hall of Fame | Armour Karate Academy';
$metaDescription = 'Honoring the distinguished master practitioners, national champions, and Dan-ranked instructors of Armour Martial Arts Gujju Karate Association.';

// ─── Database Query ───────────────────────────────────────────────────────────
$pdo        = db();
$blackBelts = [];

try {
    $stmt = $pdo->query(
        "SELECT id, name, photo, black_belt_rank, year_achieved, achievements,
                tournament_achievements, description, display_order
           FROM black_belt_profiles
          WHERE is_visible = 1
          ORDER BY display_order ASC"
    );
    $blackBelts = $stmt->fetchAll();
} catch (PDOException $e) {
    appLog('ERROR', 'Black Belts page DB error: ' . $e->getMessage());
    $blackBelts = [];
}

// ─── Layout ───────────────────────────────────────────────────────────────────
require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<div class="page-section" style="background:#0A0A0A; color:#fff; padding: 5rem 0 4rem;">
    <div class="container" style="max-width:1280px;">

        <!-- ── Hero Banner ────────────────────────────────────────────────── -->
        <div class="text-center mb-5 pb-2" style="max-width:760px; margin-left:auto; margin-right:auto;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3"
                 style="background:rgba(127,29,29,0.35); border:1px solid rgba(185,28,28,0.3); color:#f87171; font-size:0.7rem; font-weight:800; letter-spacing:0.12em; text-transform:uppercase;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#DC2626" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8 16c3.314 0 6-2 6-5.5 0-1.5-.5-4-2.5-6 .25 1.5-1.25 2-1.25 2C11 4 9 .5 6 0c.357 2 .5 4-2 6-1.25 1-2 2.729-2 4.5C2 14 4.686 16 8 16Zm0-1c-1.657 0-3-1-3-2.75 0-.75.25-2 1.25-3C6.125 10 7 10.5 7 10.5c-.375-1.25.5-3.25 2-4.5-.5 1.5.5 2.5 1 3 .5.5 1 1.25 1 2.25C11 13 9.657 15 8 15Z"/>
                </svg>
                Dan Ranks &amp; Martial Honor
            </div>
            <h1 class="fw-black text-uppercase text-white mb-3"
                style="font-size: clamp(2.2rem, 5vw, 3.6rem); letter-spacing:-0.03em; line-height:1.05;">
                Black Belt <span style="color:#DC2626;">Hall of Fame</span>
            </h1>
            <p style="color:#d4d4d4; font-size:0.925rem; line-height:1.7;">
                A black belt is not the end of the journey; it is the beginning of true mastery. We honor the practitioners who endured thousands of hours of sweat, discipline, and perseverance to earn their international Dan rank.
            </p>
        </div>

        <!-- ── Profiles Grid or Empty State ───────────────────────────────── -->
        <?php if (empty($blackBelts)): ?>
            <!-- Professional Empty State -->
            <div class="rounded-4 p-5 text-center mb-5"
                 style="background:rgba(28,28,30,0.6); border:1px solid #2a2a2a; max-width:800px; margin-left:auto; margin-right:auto;">
                <div class="mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" fill="#DC2626" viewBox="0 0 16 16" opacity="0.4" aria-hidden="true">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                    </svg>
                </div>
                <p class="fw-bold text-uppercase mb-1" style="color:#DC2626; font-size:0.7rem; letter-spacing:0.12em;">
                    Accreditation &amp; Verification Underway
                </p>
                <h3 class="fw-black text-white text-uppercase mb-2" style="font-size:1.15rem;">
                    Certified Black Belt Registry Pending Technical Review
                </h3>
                <p style="color:#a3a3a3; font-size:0.82rem; max-width:540px; margin:0 auto 1.5rem; line-height:1.7;">
                    The Academy's Technical Examination Board is currently auditing all Dan-grade rank credentials against official federation records. Verified Black Belt profiles will be published following the conclusion of the annual accreditation review.
                </p>
                <a href="<?= BASE_PATH ?>/contact" class="btn btn-sm btn-danger px-4 fw-bold text-uppercase"
                   style="letter-spacing:0.08em; background:#DC2626; border:none;">
                    Enquire With Technical Board
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-5">
                <?php foreach ($blackBelts as $bb): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="black-belt-card h-100 rounded-4 p-4 p-sm-5 d-flex flex-column justify-content-between position-relative overflow-hidden"
                             style="background:rgba(28,28,30,0.6); border:1px solid #2a2a2a; transition:all 0.3s ease;">

                            <!-- Gold / Red Accent Line -->
                            <div class="position-absolute top-0 start-0 end-0"
                                 style="height:4px; background:linear-gradient(90deg, #DC2626 0%, #F59E0B 50%, #DC2626 100%);"></div>

                            <div>
                                <!-- Header Badge -->
                                <div class="d-flex align-items-center justify-content-between pb-3 mb-4"
                                     style="border-bottom:1px solid #262626;">
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Dan Emblem -->
                                        <div class="d-flex align-items-center justify-content-center fw-black text-center"
                                             style="width:48px; height:48px; background:#0a0a0a; border:2px solid #DC2626; border-radius:14px; color:#DC2626; font-size:0.85rem; box-shadow:0 4px 12px rgba(220,38,38,0.25);">
                                            DAN
                                        </div>
                                        <div>
                                            <span class="d-block fw-bold text-uppercase"
                                                  style="font-size:0.75rem; color:#f87171; letter-spacing:0.08em;">
                                                <?= esc($bb['black_belt_rank']) ?>
                                            </span>
                                            <span style="font-size:0.72rem; color:#737373;">
                                                Achieved in Year <?= esc($bb['year_achieved']) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Trophy / Award Icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="#F59E0B" viewBox="0 0 16 16" aria-hidden="true" class="bb-award-icon" style="transition:transform 0.2s;">
                                        <path d="m8 0 1.669.864 1.858.282.842 1.68 1.337 1.32L13.4 6l.306 1.854-1.337 1.32-.842 1.68-1.858.282L8 12l-1.669-.864-1.858-.282-.842-1.68-1.337-1.32L2.6 6l-.306-1.854 1.337-1.32.842-1.68 1.858-.282L8 0z"/>
                                        <path d="M4 11.794V16l4-1 4 1v-4.206l-2.018.306L8 13.126 6.018 12.1 4 11.794z"/>
                                    </svg>
                                </div>

                                <!-- Photo if available -->
                                <?php if (!empty($bb['photo'])): ?>
                                    <div class="mb-3 rounded-3 overflow-hidden position-relative" style="height:180px; background:#111; border:1px solid #333;">
                                        <img src="<?= escAttr($bb['photo']) ?>"
                                             alt="<?= escAttr($bb['name']) ?>"
                                             class="w-100 h-100"
                                             style="object-fit:cover;"
                                             loading="lazy">
                                    </div>
                                <?php endif; ?>

                                <!-- Name & Primary Achievements -->
                                <div class="mb-3">
                                    <h3 class="fw-black text-uppercase text-white bb-card-title mb-2"
                                        style="font-size:1.15rem; line-height:1.25; letter-spacing:0.02em; transition:color 0.2s;">
                                        <?= esc($bb['name']) ?>
                                    </h3>
                                    <?php if (!empty($bb['achievements'])): ?>
                                        <p class="fw-semibold text-neutral-200 mb-0" style="font-size:0.8rem; color:#e5e5e5; line-height:1.6;">
                                            <?= esc($bb['achievements']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Tournament Honors Box -->
                                <?php if (!empty($bb['tournament_achievements'])): ?>
                                    <div class="p-3 rounded-3 mb-3"
                                         style="background:rgba(10,10,10,0.85); border:1px solid #2a2a2a;">
                                        <span class="d-flex align-items-center gap-1 fw-bold text-uppercase mb-1"
                                              style="font-size:0.65rem; color:#F59E0B; letter-spacing:0.08em;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="#F59E0B" viewBox="0 0 16 16" aria-hidden="true">
                                                <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                                            </svg>
                                            Tournament Honors
                                        </span>
                                        <p class="mb-0" style="font-size:0.75rem; color:#a3a3a3; line-height:1.5;">
                                            <?= esc($bb['tournament_achievements']) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <!-- Description / Quote -->
                                <?php if (!empty($bb['description'])): ?>
                                    <p class="fst-italic mb-0" style="font-size:0.75rem; color:#737373; line-height:1.6;">
                                        &ldquo;<?= esc($bb['description']) ?>&rdquo;
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Footer Ribbon -->
                            <div class="d-flex align-items-center justify-content-between pt-3 mt-4"
                                 style="border-top:1px solid #222;">
                                <span class="fw-semibold text-uppercase"
                                      style="font-size:0.65rem; color:#737373; letter-spacing:0.1em;">
                                    Goju-Ryu Registered
                                </span>
                                <span style="display:inline-block; width:36px; height:4px; background:#DC2626; border-radius:4px;"></span>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── Motivational Quote Banner ──────────────────────────────────── -->
        <div class="rounded-4 p-5 text-center position-relative overflow-hidden"
             style="background:#0a0a0a; border:1px solid #2a2a2a; max-width:800px; margin:0 auto;">
            <div class="mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="#DC2626" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.77 62.77 0 0 1 5.072.56z"/>
                </svg>
            </div>
            <p class="fst-italic fw-medium text-white mb-2"
               style="font-size:clamp(1rem, 2.5vw, 1.25rem); line-height:1.6; color:#e5e5e5;">
                &ldquo;A black belt is a white belt who never, ever gave up.&rdquo;
            </p>
            <p class="fw-bold text-uppercase mb-0"
               style="font-size:0.7rem; letter-spacing:0.14em; color:#737373;">
                Dojo Philosophy &bull; Armour Karate Academy
            </p>
        </div>

    </div><!-- /.container -->
</div>

<style>
.black-belt-card:hover {
    border-color: rgba(220,38,38,0.55) !important;
    transform: translateY(-4px);
    box-shadow: 0 24px 48px rgba(127,29,29,0.25);
}
.black-belt-card:hover .bb-card-title {
    color: #f87171 !important;
}
.black-belt-card:hover .bb-award-icon {
    transform: scale(1.15);
}
</style>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
