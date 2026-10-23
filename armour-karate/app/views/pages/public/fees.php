<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Fee Structure & Belt Exam Fees Page
 * app/views/pages/public/fees.php
 *
 * Faithfully recreates the original Next.js Fees page:
 *  1. Hero Banner (transparent coaching badge, description)
 *  2. Core Tuition Cards Grid (Admission, Monthly Coaching, Annual Affiliation)
 *     - When admission/training fees are 0.00, displays neutral inquiry state:
 *       "Please contact the academy for the latest fee details."
 *  3. Belt-Wise Grading Assessment Table (dynamically loaded from `belts`)
 *     - When exam fees are 0.00, displays "Inquire at Dojo"
 *  4. Questions & Admissions Call to Action
 */

declare(strict_types=1);

$pageTitle = 'Fee Structure & Examination Fees';
$metaDescription = 'Transparent fee structure for admissions, monthly dojo coaching, and belt-wise grading examinations at Armour Martial Arts.';

$pdo = db();

// Load Fee Structure
try {
    $stmtFee = $pdo->query("SELECT * FROM fee_structures WHERE id = 1");
    $feeStructure = $stmtFee->fetch() ?: [];
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to fetch fee structure: ' . $e->getMessage());
    $feeStructure = [];
}

$admissionFee = isset($feeStructure['admission_fee']) ? (float)$feeStructure['admission_fee'] : 0.00;
$trainingFee  = isset($feeStructure['training_fee'])  ? (float)$feeStructure['training_fee']  : 0.00;
$feeNotes     = !empty($feeStructure['notes']) ? $feeStructure['notes'] : 'Please contact the academy for the latest fee details and schedule.';

// Load Belts
try {
    $stmtBelts = $pdo->query("SELECT id, name, rank, color, exam_fee FROM belts ORDER BY rank ASC");
    $belts = $stmtBelts->fetchAll();
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to fetch belts on fees page: ' . $e->getMessage());
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
                <span>&#9670; TRANSPARENT &bull; STRUCTURED &bull; ACCREDITED &#9670;</span>
            </div>

            <h1 class="hero-title text-white mb-3" style="font-size: clamp(2.5rem, 6vw, 4.5rem);">
                ACADEMY <span class="text-red">FEE STRUCTURE</span>
            </h1>

            <p class="text-muted-custom fs-5 mb-0" style="line-height: 1.8;">
                Our mission is to make traditional Goju-Ryu Karate accessible to youth and adults alike with structured dojo training and standard grading certifications.
            </p>
        </div>
    </div>
</section>

<!-- ─── 2. Core Tuition Cards Grid ──────────────────────────────────────────── -->
<section class="section-py dark-section">
    <div class="container-fluid px-lg-5">
        <div class="row g-4 justify-content-center" style="max-width: 1100px; margin-left: auto; margin-right: auto;">
            <!-- Card 1: One-Time Admission -->
            <div class="col-md-4">
                <div class="card-dark p-4 p-lg-5 h-100 d-flex flex-column justify-content-between rounded-4 shadow-sm">
                    <div>
                        <span class="text-muted-custom text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                            One-Time Registration
                        </span>
                        <h4 class="text-white fw-bold mb-3">Admission Fee</h4>

                        <div class="py-2 mb-3">
                            <?php if ($admissionFee > 0): ?>
                                <div class="text-white fw-black fs-2 font-monospace"><?= formatCurrency($admissionFee) ?></div>
                                <span class="text-muted-custom small">Once upon initial enrollment</span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-25 text-light py-2 px-3 text-xs fw-semibold">
                                    Inquire with Academy
                                </span>
                                <span class="text-muted-custom small d-block mt-2">Covered during initial intake</span>
                            <?php endif; ?>
                        </div>

                        <ul class="list-unstyled d-flex flex-column gap-2 text-muted-custom small border-top border-secondary border-opacity-10 pt-3 mb-0">
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Official Karate Uniform (Dogi)</span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>White Belt &amp; Academy Patch</span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Permanent Student ID Card</span>
                            </li>
                        </ul>
                    </div>

                    <div class="pt-4 mt-3 border-top border-secondary border-opacity-10">
                        <a href="<?= BASE_PATH ?>/join" class="btn btn-outline-custom w-100 btn-sm">
                            Enroll Now &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 2: Monthly Coaching (Featured) -->
            <div class="col-md-4">
                <div class="card-dark p-4 p-lg-5 h-100 d-flex flex-column justify-content-between rounded-4 position-relative border-danger shadow-lg"
                     style="background: linear-gradient(180deg, #180808 0%, #0d0d0d 100%);">
                    <span class="position-absolute top-0 start-50 translate-middle badge bg-danger text-white text-uppercase px-3 py-1 shadow" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                        Most Popular
                    </span>

                    <div>
                        <span class="text-red text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                            Regular Dojo Training
                        </span>
                        <h4 class="text-white fw-bold mb-3">Monthly Coaching</h4>

                        <div class="py-2 mb-3">
                            <?php if ($trainingFee > 0): ?>
                                <div class="text-white fw-black fs-2 font-monospace"><?= formatCurrency($trainingFee) ?></div>
                                <span class="text-muted-custom small">Per month / recurring schedule</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 py-2 px-3 text-xs fw-semibold">
                                    Inquire with Academy
                                </span>
                                <span class="text-muted-custom small d-block mt-2">Batch-specific monthly coaching</span>
                            <?php endif; ?>
                        </div>

                        <ul class="list-unstyled d-flex flex-column gap-2 text-muted-custom small border-top border-secondary border-opacity-10 pt-3 mb-0">
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>3–4 Training Sessions Per Week</span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Kata, Kumite &amp; Sanchin Conditioning</span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Access to Dojo Tatami Mats</span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Student Portal Access</span>
                            </li>
                        </ul>
                    </div>

                    <div class="pt-4 mt-3 border-top border-secondary border-opacity-10">
                        <a href="<?= BASE_PATH ?>/join" class="btn btn-primary-custom w-100 btn-sm">
                            Start Training &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 3: Annual Registration / Affiliation -->
            <div class="col-md-4">
                <div class="card-dark p-4 p-lg-5 h-100 d-flex flex-column justify-content-between rounded-4 shadow-sm">
                    <div>
                        <span class="text-muted-custom text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                            Annual Association
                        </span>
                        <h4 class="text-white fw-bold mb-3">Annual Registration</h4>

                        <div class="py-2 mb-3">
                            <span class="badge bg-secondary bg-opacity-25 text-light py-2 px-3 text-xs fw-semibold">
                                Annual Affiliation
                            </span>
                            <span class="text-muted-custom small d-block mt-2">Annual academy renewal</span>
                        </div>

                        <ul class="list-unstyled d-flex flex-column gap-2 text-muted-custom small border-top border-secondary border-opacity-10 pt-3 mb-0">
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Official Association Registry</span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>State Tournament Eligibility</span>
                            </li>
                            <li class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Belt Examination Verification</span>
                            </li>
                        </ul>
                    </div>

                    <div class="pt-4 mt-3 border-top border-secondary border-opacity-10">
                        <a href="<?= BASE_PATH ?>/contact" class="btn btn-outline-custom w-100 btn-sm">
                            Inquire Details &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Neutral Info Banner -->
        <div class="card-dark p-3 p-md-4 mt-5 mx-auto text-center rounded-3 border-secondary border-opacity-25" style="max-width: 900px;">
            <p class="text-muted-custom small mb-0">
                <strong class="text-white">Fee Policy Notice:</strong> <?= esc($feeNotes) ?>
            </p>
        </div>
    </div>
</section>

<!-- ─── 3. Belt-Wise Grading Assessment Fees Table ───────────────────────────── -->
<section class="section-py bg-black border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="card-dark p-4 p-lg-5 mx-auto rounded-4 shadow-lg" style="max-width: 900px;">
            <div class="mb-4">
                <span class="section-eyebrow">BELT EXAMINATIONS</span>
                <h3 class="text-white fw-bold mb-1">Grading Assessment Schedule</h3>
                <p class="text-muted-custom small mb-0">
                    Grading fees cover technical panel evaluation, certificate printing, and official belt awarding upon passing.
                </p>
            </div>

            <div class="table-responsive">
                <table class="table-custom table-dark-custom">
                    <thead>
                        <tr>
                            <th>Grading Assessment</th>
                            <th>Rank</th>
                            <th class="text-end">Examination Fee</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($belts as $b): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="rounded-circle d-inline-block border border-secondary"
                                              style="width: 14px; height: 14px; background-color: <?= escAttr($b['color']) ?>;"></span>
                                        <span class="fw-semibold text-white"><?= esc($b['name']) ?></span>
                                    </div>
                                </td>
                                <td class="text-muted-custom font-monospace">
                                    Rank <?= (int)$b['rank'] ?>
                                </td>
                                <td class="text-end text-red fw-bold font-monospace">
                                    <?php if ((float)$b['exam_fee'] > 0): ?>
                                        <?= formatCurrency((float)$b['exam_fee']) ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-25 text-light text-xs">Inquire at Dojo</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="pt-3 mt-3 border-top border-secondary border-opacity-10">
                <p class="text-muted-custom text-xs mb-0">
                    * Please contact the academy for the latest fee details and examination registration timelines. Testing dates are published approximately 4 weeks prior to each assessment session.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- ─── 4. Call to Action Banner ─────────────────────────────────────────────── -->
<section class="section-py cta-banner text-center text-white border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5 position-relative" style="z-index: 1;">
        <h2 class="heading-lg text-white mb-3">
            HAVE QUESTIONS ABOUT <span class="text-white">FEES OR BATCH TIMINGS?</span>
        </h2>
        <p class="lead text-white-50 mx-auto mb-4" style="max-width: 680px; font-size: 1.1rem;">
            Our administrative team is happy to assist parents and students with batch schedules, age group recommendations, and new enrollments.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= BASE_PATH ?>/join" class="btn btn-light btn-lg fw-bold text-danger px-4 shadow">
                Submit Enrollment Application &rarr;
            </a>
            <a href="<?= BASE_PATH ?>/contact" class="btn btn-outline-light btn-lg px-4">
                Contact Dojo Office
            </a>
        </div>
    </div>
</section>

<?php
require_once APP_ROOT . '/app/views/layouts/public-footer.php';
