<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * About Academy Page
 * app/views/pages/public/about.php
 *
 * Faithfully recreates the original Next.js About page:
 *  1. Hero Banner (ambient red halo, tradition & lineage eyebrow)
 *  2. Academy Story & Heritage (Chojun Miyagi Goju-Ryu lineage, Go & Ju philosophy, emblem card)
 *  3. Mission & Vision (side-by-side pillar cards)
 *  4. Chief Sensei / Instructor Profile (dynamically loaded from `instructor_profiles`, graceful null handling)
 *  5. Why Train With Us (6 feature cards matching original icons & text)
 *  6. Admissions / Trial Class Call to Action
 */

declare(strict_types=1);

$pageTitle = 'About Our Academy & Tradition';
$metaDescription = 'Discover the traditional Goju-Ryu lineage of Armour Martial Arts Gujju Karate Association. Learn about Goju-Ryu philosophy, our Chief Instructor, and our mission.';

$pdo = db();

// Load Academy Settings
try {
    $stmtSettings = $pdo->query("SELECT * FROM academy_settings WHERE id = 1");
    $academySettings = $stmtSettings->fetch() ?: [];
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to load academy settings: ' . $e->getMessage());
    $academySettings = [];
}

// Load Chief Instructor Profile
try {
    $stmtInstructor = $pdo->query("SELECT * FROM instructor_profiles WHERE id = 1");
    $instructor = $stmtInstructor->fetch() ?: [];
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to load instructor profile: ' . $e->getMessage());
    $instructor = [];
}

$instructorName = !empty($instructor['name']) ? $instructor['name'] : 'Chief Instructor';
$instructorRole = !empty($instructor['academy_role']) ? $instructor['academy_role'] : 'Chief Technical Director';
$instructorRank = !empty($instructor['karate_rank']) ? $instructor['karate_rank'] : null;
$instructorBio  = !empty($instructor['biography']) ? $instructor['biography'] : 'Dedicated to the lifelong study, preservation, and dissemination of traditional Goju-Ryu Karate-Do, prioritizing spiritual discipline, humility, and uncompromising technical precision.';

$whyTrainWithUs = [
    [
        'title' => 'Authentic Okinawan Lineage',
        'desc'  => 'We practice pure Goju-Ryu preserving the authentic curriculum — including classical Katas (Sanchin, Tensho, Saifa, Seiyunchin) and traditional Bunkai combat applications.',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'
    ],
    [
        'title' => 'Structured Belt Certifications',
        'desc'  => 'All Kyu gradings and Dan assessments follow rigorous technical standards with official certificate registry from White Belt to advanced Black Belt.',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z"/></svg>'
    ],
    [
        'title' => 'Real-World Practical Self-Defense',
        'desc'  => 'We train beyond the rules of sport karate. Students develop instinctive close-quarter defense tactics, joint manipulation, anti-bullying reflexes, and spatial danger awareness.',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>'
    ],
    [
        'title' => 'Age-Tailored Structured Curriculum',
        'desc'  => 'Age-appropriate programs designed specifically for Little Tigers (5–9 yrs), Cadets (10–16 yrs), and Adult Practitioners, balancing motor skills, discipline, and stamina.',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>'
    ],
    [
        'title' => 'Championship Competition Pathways',
        'desc'  => 'Comprehensive athletic conditioning for students aspiring to compete at District, State, and National tournaments in traditional Kata and Kumite divisions.',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>'
    ],
    [
        'title' => 'Character, Humility & Respect',
        'desc'  => 'Karate begins and ends with courtesy (Rei). We mold humble, focused, and respectful citizens who carry dojo discipline into academic, professional, and personal life.',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    ],
];

require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<!-- ─── 1. Hero Banner ───────────────────────────────────────────────────────── -->
<section class="section-py bg-black border-bottom border-secondary border-opacity-10 position-relative overflow-hidden text-center">
    <div class="hero-glow"></div>
    <div class="container-fluid px-lg-5 position-relative" style="z-index: 1;">
        <div class="mx-auto" style="max-width: 800px;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger text-xs fw-bold text-uppercase tracking-widest mb-4">
                <span>&#9670; TRADITION &bull; LINEAGE &bull; DISCIPLINE &#9670;</span>
            </div>

            <h1 class="hero-title text-white mb-3" style="font-size: clamp(2.5rem, 6vw, 4.5rem);">
                ABOUT OUR <span class="text-red">ACADEMY</span>
            </h1>

            <p class="text-muted-custom fs-5 mb-0" style="line-height: 1.8;">
                Preserving the sacred art of Goju-Ryu Karate-Do. Dedicated to forging unbreakable warrior discipline, physical strength, and moral character.
            </p>
        </div>
    </div>
</section>

<!-- ─── 2. Academy Story & Heritage ─────────────────────────────────────────── -->
<section class="section-py dark-section">
    <div class="container-fluid px-lg-5">
        <div class="row align-items-center g-5">
            <!-- Left: Story & Origins -->
            <div class="col-lg-7">
                <span class="section-eyebrow">OUR STORY &amp; ORIGINS</span>
                <h2 class="heading-lg text-white mb-4">
                    AUTHENTIC TRADITION &amp; <span class="text-red">OKINAWAN HERITAGE</span>
                </h2>

                <p class="text-muted-custom mb-3" style="line-height: 1.8; font-size: 1.05rem;">
                    Armour Martial Arts Gujju Karate Association was established with a singular mission: to impart authentic Goju-Ryu Karate-Do without dilution, commercial shortcuts, or loss of traditional integrity.
                </p>

                <p class="text-muted-custom mb-4" style="line-height: 1.8;">
                    Goju-Ryu (剛柔流) was founded in Okinawa by Grandmaster Chojun Miyagi, deriving its name from the classical Chinese martial poem Kenpo Hakku: <em class="text-white">“Ho wa Goju o danto su”</em> (The law of the universe is hardness and softness). Under this timeless philosophy, our practitioners develop both rooted stability (Go) and flexible, circular adaptability (Ju).
                </p>

                <!-- Hardness & Softness Cards -->
                <div class="row g-3 pt-2">
                    <div class="col-sm-6">
                        <div class="card-dark p-3 h-100">
                            <h6 class="text-red fw-bold text-uppercase small mb-1 tracking-wider">GO (剛) — Hardness</h6>
                            <p class="text-muted-custom small mb-0">Linear strikes, rooted stances, iron-body Sanchin conditioning, and uncompromising willpower.</p>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card-dark p-3 h-100">
                            <h6 class="text-red fw-bold text-uppercase small mb-1 tracking-wider">JU (柔) — Softness</h6>
                            <p class="text-muted-custom small mb-0">Circular parrying, deep breathing (Tensho), fluid evasion, and calm adaptability under pressure.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Academy Emblem Showcase Card -->
            <div class="col-lg-5">
                <div class="card-dark p-5 text-center position-relative shadow-lg border border-secondary border-opacity-25" style="border-radius: 24px;">
                    <div class="mb-4">
                        <img src="<?= logoUrl() ?>" alt="Armour Karate Association" height="96" class="rounded-circle p-2 bg-black border border-danger border-opacity-50 shadow">
                    </div>

                    <h4 class="text-white fw-black text-uppercase tracking-tight mb-1">
                        ARMOUR MARTIAL ARTS
                    </h4>
                    <p class="text-red fw-bold text-uppercase small tracking-widest mb-4">
                        GUJJU KARATE ASSOCIATION
                    </p>

                    <hr class="border-secondary border-opacity-25 my-4">

                    <div class="row g-3 text-start small">
                        <div class="col-6">
                            <span class="text-muted-custom d-block" style="font-size: 0.75rem;">Style</span>
                            <strong class="text-white">Traditional Goju-Ryu</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted-custom d-block" style="font-size: 0.75rem;">Headquarters</span>
                            <strong class="text-white">Gujarat, India</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted-custom d-block" style="font-size: 0.75rem;">Curriculum</span>
                            <strong class="text-white">Kata, Kumite &amp; Sanchin</strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted-custom d-block" style="font-size: 0.75rem;">Progression</span>
                            <strong class="text-white">10th Kyu to Dan Grades</strong>
                        </div>
                    </div>

                    <div class="pt-4 mt-2">
                        <span class="badge bg-secondary bg-opacity-25 text-white-50 border border-secondary border-opacity-25 py-2 px-3 rounded-pill text-xs">
                            &#10003; Official Registered Martial Arts Academy
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── 3. Mission & Vision ──────────────────────────────────────────────────── -->
<section class="section-py bg-black border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="row g-4">
            <!-- Mission Card -->
            <div class="col-md-6">
                <div class="card-dark p-4 p-lg-5 h-100 d-flex flex-column justify-content-between rounded-4">
                    <div>
                        <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger d-inline-flex mb-4">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>

                        <h3 class="text-white fw-black text-uppercase mb-3">
                            OUR <span class="text-red">MISSION</span>
                        </h3>

                        <p class="text-muted-custom mb-4" style="line-height: 1.8;">
                            To foster physical resilience, mental fortitude, and moral uprightness in every practitioner through rigorous traditional Goju-Ryu training. We empower students to defend themselves and protect others, instilling humility, courage, and lifelong vitality.
                        </p>

                        <div class="d-flex flex-column gap-2 text-muted-custom small">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Preserving authentic Okinawan syllabus and classical Katas</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Cultivating zero tolerance for violence, bullying, and arrogance</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Nurturing disciplined athletic contenders for state championships</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vision Card -->
            <div class="col-md-6">
                <div class="card-dark p-4 p-lg-5 h-100 d-flex flex-column justify-content-between rounded-4">
                    <div>
                        <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger d-inline-flex mb-4">
                            <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>

                        <h3 class="text-white fw-black text-uppercase mb-3">
                            OUR <span class="text-red">VISION</span>
                        </h3>

                        <p class="text-muted-custom mb-4" style="line-height: 1.8;">
                            To stand as a hallmark of martial arts excellence across Gujarat and India — developing students whose character, humility, leadership, and athletic caliber uplift their communities and set an unbreakable standard.
                        </p>

                        <div class="d-flex flex-column gap-2 text-muted-custom small">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Expanding safe, equipped training dojos across all districts</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Conducting regular masterclinics and technical evaluations</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-danger">&#10003;</span>
                                <span>Making self-defense training accessible to women, youth, and children</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── 4. Chief Sensei / Leadership Profile ─────────────────────────────────── -->
<section class="section-py dark-section border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="text-center mx-auto mb-5" style="max-width: 650px;">
            <span class="section-eyebrow">LEADERSHIP &amp; MASTER INSTRUCTION</span>
            <h2 class="heading-lg text-white mb-2">
                MEET OUR <span class="text-red">CHIEF INSTRUCTOR</span>
            </h2>
            <p class="text-muted-custom mb-0">
                Instruction rooted in classical transmission, relentless discipline, and comprehensive technical mastery.
            </p>
        </div>

        <div class="card-dark p-4 p-lg-5 mx-auto rounded-4 shadow-lg" style="max-width: 950px;">
            <div class="row align-items-center g-4 g-lg-5">
                <!-- Avatar / Shihan Seal -->
                <div class="col-md-5 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-2 bg-black border border-danger border-opacity-50 shadow-lg mb-3"
                         style="width: 180px; height: 180px;">
                        <div class="w-100 h-100 rounded-circle bg-danger bg-opacity-10 d-flex flex-column align-items-center justify-content-center">
                            <span class="text-white-50 fs-1 fw-bold" style="font-family: serif;">師範</span>
                            <span class="text-danger text-uppercase fw-bold text-xs tracking-widest mt-1">SHIHAN</span>
                        </div>
                    </div>

                    <h4 class="text-white fw-black text-uppercase mb-1"><?= esc($instructorName) ?></h4>
                    <span class="badge bg-danger text-white text-uppercase tracking-wider mb-2">
                        <?= esc($instructorRole) ?>
                    </span>
                    <?php if ($instructorRank): ?>
                        <div class="text-muted-custom small"><?= esc($instructorRank) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Bio & Martial Direction -->
                <div class="col-md-7">
                    <div class="mb-4">
                        <span class="text-red fw-bold text-uppercase small tracking-widest d-block mb-2">
                            Martial Philosophy &amp; Directorship
                        </span>
                        <p class="text-muted-custom" style="line-height: 1.8;">
                            <?= nl2br(esc($instructorBio)) ?>
                        </p>
                    </div>

                    <?php if (!empty($instructor['qualification'])): ?>
                        <div class="mb-3">
                            <span class="text-white fw-bold small text-uppercase tracking-wider d-block mb-1">
                                Accreditation &amp; Qualifications
                            </span>
                            <p class="text-muted-custom small mb-0"><?= esc($instructor['qualification']) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($instructor['achievements'])): ?>
                        <div class="mb-3">
                            <span class="text-white fw-bold small text-uppercase tracking-wider d-block mb-1">
                                Achievements
                            </span>
                            <p class="text-muted-custom small mb-0"><?= nl2br(esc($instructor['achievements'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="pt-3 border-top border-secondary border-opacity-10 d-flex align-items-center gap-3 text-muted-custom small">
                        <span>&#10003; Chief Technical Director</span>
                        <span>&bull;</span>
                        <span>Traditional Goju-Ryu Lineage</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── 5. Why Train With Us (6 Feature Cards) ───────────────────────────────── -->
<section class="section-py bg-black border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="text-center mx-auto mb-5" style="max-width: 650px;">
            <span class="section-eyebrow">THE ARMOUR STANDARD</span>
            <h2 class="heading-lg text-white mb-2">
                WHY TRAIN <span class="text-red">WITH US</span>
            </h2>
            <p class="text-muted-custom mb-0">
                We provide a disciplined, respectful, and world-class training ground designed to unlock your ultimate potential.
            </p>
        </div>

        <div class="row g-4">
            <?php foreach ($whyTrainWithUs as $item): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card-dark p-4 h-100 d-flex flex-column justify-content-between rounded-4">
                        <div>
                            <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger d-inline-flex mb-3">
                                <?= $item['icon'] ?>
                            </div>
                            <h5 class="text-white fw-bold mb-2"><?= esc($item['title']) ?></h5>
                            <p class="text-muted-custom small mb-0" style="line-height: 1.7;">
                                <?= esc($item['desc']) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── 6. Admissions / Trial Class Call to Action ───────────────────────────── -->
<section class="section-py cta-banner text-center text-white border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5 position-relative" style="z-index: 1;">
        <h2 class="heading-lg text-white mb-3">
            TAKE YOUR FIRST STEP ON THE <span class="text-white">TATAMI</span>
        </h2>
        <p class="lead text-white-50 mx-auto mb-4" style="max-width: 680px; font-size: 1.1rem;">
            Experience our traditional karate training firsthand. Inquire today for batch timings, active dojo locations, and introductory sessions for children, youth, and adults.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= BASE_PATH ?>/join" class="btn btn-light btn-lg fw-bold text-danger px-4 shadow">
                Enroll In Academy &rarr;
            </a>
            <a href="<?= BASE_PATH ?>/contact" class="btn btn-outline-light btn-lg px-4">
                Inquire Dojo Timings
            </a>
        </div>
    </div>
</section>

<?php
require_once APP_ROOT . '/app/views/layouts/public-footer.php';
