<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Belt Journey & Syllabus Page
 * app/views/pages/public/belts.php
 *
 * Faithfully recreates the original Next.js Belts page:
 *  1. Hero Banner (progression badge, martial journey description)
 *  2. Belt Cards Grid (queries `belts` table, color badges, syllabus description, unknown fee handling)
 *  3. Grading Evaluation Pillars (Kihon, Kata, Kumite requirements)
 *  4. Call to Action Banner
 */

declare(strict_types=1);

$pageTitle = 'Belt Journey & Grading Syllabus';
$metaDescription = 'Explore the authentic Goju-Ryu Karate-Do belt grading syllabus from beginner 10th Kyu White Belt to certified 1st Dan Black Belt.';

$pdo = db();

try {
    $stmtBelts = $pdo->query("SELECT id, name, rank, color, exam_fee, description FROM belts ORDER BY rank ASC");
    $belts = $stmtBelts->fetchAll();
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to fetch belts on belts page: ' . $e->getMessage());
    $belts = [];
}

require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<!-- ─── 1. Hero Banner ───────────────────────────────────────────────────────── -->
<section class="section-py bg-black border-bottom border-secondary border-opacity-10 position-relative overflow-hidden text-center">
    <div class="hero-glow"></div>
    <div class="container-fluid px-lg-5 position-relative" style="z-index: 1;">
        <div class="mx-auto" style="max-width: 800px;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger text-xs fw-bold text-uppercase tracking-widest mb-4">
                <span>&#9670; GOJU-RYU KARATE-DO PROGRESSION &#9670;</span>
            </div>

            <h1 class="hero-title text-white mb-3" style="font-size: clamp(2.5rem, 6vw, 4.5rem);">
                THE BELT <span class="text-red">JOURNEY</span>
            </h1>

            <p class="text-muted-custom fs-5 mb-0" style="line-height: 1.8;">
                Karate is not a race of speed; it is an enduring journey of discipline, self-perfection, and inner strength. Discover the curriculum from beginner White Belt to certified Black Belt mastery.
            </p>
        </div>
    </div>
</section>

<!-- ─── 2. Belt Cards Grid ──────────────────────────────────────────────────── -->
<section class="section-py dark-section">
    <div class="container-fluid px-lg-5">
        <div class="row g-4">
            <?php foreach ($belts as $b): ?>
                <?php
                    $isWhiteOrYellow = in_array(strtoupper($b['color']), ['#FFFFFF', '#EAB308']);
                    $textColor = $isWhiteOrYellow ? '#0A0A0A' : '#FFFFFF';
                    $hasExamFee = ((float)$b['exam_fee'] > 0);
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card-dark h-100 p-4 d-flex flex-column justify-content-between rounded-4 shadow-sm">
                        <div>
                            <!-- Header: Rank circle + titles -->
                            <div class="d-flex align-items-center gap-3 pb-3 mb-3 border-bottom border-secondary border-opacity-10">
                                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold shadow flex-shrink-0"
                                     style="width: 44px; height: 44px; background-color: <?= escAttr($b['color']) ?>; color: <?= $textColor ?>; border: 1.5px solid rgba(255,255,255,0.25); font-size: 0.95rem;">
                                    <?= (int)$b['rank'] ?>
                                </div>
                                <div>
                                    <span class="text-muted-custom text-uppercase fw-bold d-block" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                                        Rank <?= (int)$b['rank'] ?>
                                    </span>
                                    <h5 class="text-white fw-bold mb-0" style="font-size: 1.05rem;">
                                        <?= esc($b['name']) ?>
                                    </h5>
                                </div>
                            </div>

                            <!-- Description / Syllabus -->
                            <p class="text-muted-custom small mb-0" style="line-height: 1.7;">
                                <?= esc($b['description']) ?>
                            </p>
                        </div>

                        <!-- Card Footer: Exam Fee / Inquiry Note -->
                        <div class="pt-3 mt-4 border-top border-secondary border-opacity-10 d-flex justify-content-between align-items-center text-xs">
                            <span class="text-muted-custom">Assessment Fee:</span>
                            <span class="text-red fw-bold font-monospace">
                                <?php if ($hasExamFee): ?>
                                    <?= formatCurrency((float)$b['exam_fee']) ?>
                                <?php else: ?>
                                    Inquire with Dojo
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <span class="text-muted-custom text-xs">
                Note: Belt examination schedules, prerequisites, and evaluation panels are notified prior to each testing session.
            </span>
        </div>
    </div>
</section>

<!-- ─── 3. Grading Evaluation Pillars ────────────────────────────────────────── -->
<section class="section-py bg-black border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="text-center mx-auto mb-5" style="max-width: 650px;">
            <span class="section-eyebrow">STANDARDS &amp; CRITERIA</span>
            <h2 class="heading-lg text-white mb-2">
                EXAMINATION <span class="text-red">PILLARS</span>
            </h2>
            <p class="text-muted-custom mb-0">
                Every belt promotion is earned through rigorous evaluation across three fundamental dimensions of karate mastery.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-dark p-4 p-lg-5 h-100 rounded-4">
                    <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger d-inline-flex mb-3">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h4 class="text-white fw-bold mb-2">1. KIHON (基本)</h4>
                    <p class="text-red small fw-semibold text-uppercase tracking-wider mb-3">Fundamental Technique</p>
                    <p class="text-muted-custom small mb-0" style="line-height: 1.8;">
                        Precision of stances (Zenkutsu-dachi, Shiko-dachi, Sanchin-dachi), hip drive, balance, eye focus, kiai, and crisp execution of basic punches, blocks, and kicks.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-dark p-4 p-lg-5 h-100 rounded-4">
                    <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger d-inline-flex mb-3">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <h4 class="text-white fw-bold mb-2">2. KATA (型)</h4>
                    <p class="text-red small fw-semibold text-uppercase tracking-wider mb-3">Traditional Forms &amp; Bunkai</p>
                    <p class="text-muted-custom small mb-0" style="line-height: 1.8;">
                        Authentic sequence preservation, rhythm, deep breathing (Ibuki/Tensho), power modulation, and demonstrated understanding of realistic combat bunkai against virtual attackers.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-dark p-4 p-lg-5 h-100 rounded-4">
                    <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger d-inline-flex mb-3">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/></svg>
                    </div>
                    <h4 class="text-white fw-bold mb-2">3. KUMITE (組手)</h4>
                    <p class="text-red small fw-semibold text-uppercase tracking-wider mb-3">Controlled Sparring</p>
                    <p class="text-muted-custom small mb-0" style="line-height: 1.8;">
                        Distance awareness (Maai), timing, tactical composure under pressure, defensive reflexes, and respectful etiquette before and after engagement.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── 4. Call to Action Banner ─────────────────────────────────────────────── -->
<section class="section-py cta-banner text-center text-white border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5 position-relative" style="z-index: 1;">
        <h2 class="heading-lg text-white mb-3">
            READY TO EARN YOUR FIRST <span class="text-white">BELT GRADING?</span>
        </h2>
        <p class="lead text-white-50 mx-auto mb-4" style="max-width: 680px; font-size: 1.1rem;">
            Admissions are open for Tigers, Cadets, and Adults. Enroll now to receive your official karate uniform, student ID, and begin the White Belt syllabus.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= BASE_PATH ?>/join" class="btn btn-light btn-lg fw-bold text-danger px-4 shadow">
                Enroll In Academy &rarr;
            </a>
            <a href="<?= BASE_PATH ?>/fees" class="btn btn-outline-light btn-lg px-4">
                View Fee Structure
            </a>
        </div>
    </div>
</section>

<?php
require_once APP_ROOT . '/app/views/layouts/public-footer.php';
