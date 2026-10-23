<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Public Homepage
 * app/views/pages/public/home.php
 *
 * Faithfully recreates the 9 sections of the original Next.js homepage:
 *  1. Hero Section (red radial glow, dot grid, CTA group)
 *  2. Martial Arts Pillars (Shin, Gi, Tai, Do - authentic & factual)
 *  3. About & Philosophy (Okinawan Goju-Ryu lineage & 3 benefit cards)
 *  4. Belt Journey Preview (dynamic from `belts` table)
 *  5. Upcoming Events Preview (dynamic with clean empty state)
 *  6. Black Belt Hall of Fame (dynamic with clean accreditation state)
 *  7. Inside The Academy Gallery Preview (dynamic/stylized category cards)
 *  8. Training Locations Preview (dynamic with clean inquiry state)
 *  9. Join Academy Call to Action Banner
 */

declare(strict_types=1);

$pageTitle = 'Authentic Karate Academy & Martial Arts Training';
$metaDescription = 'Armour Martial Arts – Gujju Karate Association. Official traditional Goju-Ryu Karate-Do training in Gujarat. Discipline, strength, and character for all ages.';

// Database queries with safe fallback handling
$pdo = db();

// 1. Belts (8 ranks)
try {
    $stmtBelts = $pdo->query("SELECT id, name, rank, color, exam_fee, description FROM belts ORDER BY rank ASC");
    $belts = $stmtBelts->fetchAll();
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to fetch belts on homepage: ' . $e->getMessage());
    $belts = [];
}

// 2. Published Events (max 3)
try {
    $stmtEvents = $pdo->query("SELECT id, event_name, type, event_date, start_time, venue, description FROM events WHERE status = 'PUBLISHED' ORDER BY event_date ASC LIMIT 3");
    $events = $stmtEvents->fetchAll();
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to fetch events on homepage: ' . $e->getMessage());
    $events = [];
}

// 3. Visible Black Belt Profiles (max 3)
try {
    $stmtBlackBelts = $pdo->query("SELECT id, name, black_belt_rank, year_achieved, achievements, description FROM black_belt_profiles WHERE is_visible = 1 ORDER BY display_order ASC LIMIT 3");
    $blackBelts = $stmtBlackBelts->fetchAll();
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to fetch black belt profiles on homepage: ' . $e->getMessage());
    $blackBelts = [];
}

// 4. Visible Gallery Images (max 6)
try {
    $stmtGallery = $pdo->query("SELECT id, image_url, title, description, category FROM gallery_images WHERE is_visible = 1 ORDER BY created_at DESC LIMIT 6");
    $galleryImages = $stmtGallery->fetchAll();
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to fetch gallery images on homepage: ' . $e->getMessage());
    $galleryImages = [];
}

// 5. Active Class Locations (max 3)
try {
    $stmtLocations = $pdo->query("SELECT id, location_name, address, instructor, training_days, training_time, contact FROM class_locations WHERE is_active = 1 ORDER BY location_name ASC LIMIT 3");
    $locations = $stmtLocations->fetchAll();
} catch (Throwable $e) {
    logger('DATABASE', 'Failed to fetch locations on homepage: ' . $e->getMessage());
    $locations = [];
}

// 6. Academy Settings
try {
    $stmtSettings = $pdo->query("SELECT academy_name, tagline, description, address FROM academy_settings WHERE id = 1");
    $academySettings = $stmtSettings->fetch() ?: [];
} catch (Throwable $e) {
    $academySettings = [];
}

// Fallback stylized gallery cards if no uploaded images
$fallbackGallery = [
    ['title' => 'Intense Sanchin Breathing & Stance Training', 'tag' => 'Dojo Discipline', 'cat' => 'TRAINING', 'grad' => 'linear-gradient(135deg, #1c0808 0%, #0d0d0d 100%)'],
    ['title' => 'State Kumite Sparring & Defense', 'tag' => 'Sparring Action', 'cat' => 'TOURNAMENTS', 'grad' => 'linear-gradient(135deg, #0d0d0d 0%, #1c0808 100%)'],
    ['title' => 'Black Belt Dan Promotion Assessment', 'tag' => 'Mastery & Honor', 'cat' => 'BLACK_BELT', 'grad' => 'linear-gradient(135deg, #000000 0%, #170505 100%)'],
    ['title' => 'Junior Cadets Kata Demonstration', 'tag' => 'Next Generation', 'cat' => 'BELT_EXAMS', 'grad' => 'linear-gradient(135deg, #220808 0%, #0a0a0a 100%)'],
    ['title' => 'Traditional Bunkai Application Workshop', 'tag' => 'Endurance & Spirit', 'cat' => 'CAMPS', 'grad' => 'linear-gradient(135deg, #0f0f0f 0%, #1f0707 100%)'],
    ['title' => 'Self-Defense & Anti-Bullying Seminar', 'tag' => 'Community Impact', 'cat' => 'ACADEMY_EVENTS', 'grad' => 'linear-gradient(135deg, #1a0606 0%, #000000 100%)'],
];

require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<!-- ─── 1. Hero Section ──────────────────────────────────────────────────────── -->
<section class="hero-section">
    <div class="hero-glow"></div>
    <div class="hero-dot-grid"></div>
    <div class="hero-grid-lines"></div>

    <div class="hero-content">
        <span class="hero-eyebrow">
            OFFICIAL ACADEMY &bull; GUJJU KARATE ASSOCIATION
        </span>

        <h1 class="hero-title">
            ARMOUR <span class="text-red">MARTIAL ARTS</span>
        </h1>

        <div class="hero-subtitle">
            GUJJU KARATE ASSOCIATION
        </div>

        <p class="hero-description">
            Experience the authentic discipline of traditional Goju-Ryu Karate-Do. Developing physical strength, mental clarity, and moral integrity for students of all ages.
        </p>

        <div class="hero-cta-group">
            <a href="<?= BASE_PATH ?>/join" class="btn-primary-custom btn-lg btn-pulse">
                <span>Join Academy</span>
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
            <a href="<?= BASE_PATH ?>/about" class="btn-outline-custom btn-lg">
                Explore Academy
            </a>
        </div>
    </div>
</section>

<!-- ─── 2. Martial Arts Pillars (Authentic Foundation) ──────────────────────── -->
<section class="stats-section py-4">
    <div class="container-fluid px-lg-5">
        <div class="row g-3 g-md-0 text-center">
            <div class="col-6 col-md-3 border-end border-secondary border-opacity-10">
                <div class="p-3">
                    <div class="text-red fw-bold text-uppercase small tracking-widest mb-1">心 &bull; SHIN</div>
                    <div class="text-white fw-black fs-4 mb-1">MIND & SPIRIT</div>
                    <div class="text-muted-custom small">Focus, calm resilience & respect</div>
                </div>
            </div>
            <div class="col-6 col-md-3 border-end border-secondary border-opacity-10">
                <div class="p-3">
                    <div class="text-red fw-bold text-uppercase small tracking-widest mb-1">技 &bull; GI</div>
                    <div class="text-white fw-black fs-4 mb-1">TECHNIQUE</div>
                    <div class="text-muted-custom small">Goju-Ryu Katas, stances & precision</div>
                </div>
            </div>
            <div class="col-6 col-md-3 border-end border-secondary border-opacity-10">
                <div class="p-3">
                    <div class="text-red fw-bold text-uppercase small tracking-widest mb-1">体 &bull; TAI</div>
                    <div class="text-white fw-black fs-4 mb-1">BODY CONDITION</div>
                    <div class="text-muted-custom small">Sanchin power, agility & endurance</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3">
                    <div class="text-red fw-bold text-uppercase small tracking-widest mb-1">道 &bull; DO</div>
                    <div class="text-white fw-black fs-4 mb-1">THE WAY OF LIFE</div>
                    <div class="text-muted-custom small">Honor, integrity & lifelong discipline</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── 3. About Section (Philosophy & Lineage) ──────────────────────────────── -->
<section class="section-py dark-section">
    <div class="container-fluid px-lg-5">
        <div class="row align-items-center g-5">
            <!-- Left: Philosophy & Heritage -->
            <div class="col-lg-6">
                <span class="section-eyebrow">THE OKINAWAN LINEAGE</span>
                <h2 class="heading-lg text-white mb-4">
                    AUTHENTIC <span class="text-red">GOJU-RYU</span> KARATE-DO
                </h2>
                <p class="text-muted-custom mb-3" style="line-height: 1.8;">
                    Founded on the ancient Okinawan principle of <strong class="text-white">“Go”</strong> (hardness and unyielding strength) and <strong class="text-white">“Ju”</strong> (softness, breath, and yielding circularity), Armour Martial Arts Gujju Karate Association imparts traditional karate as a complete way of life.
                </p>
                <p class="text-muted-custom mb-4" style="line-height: 1.8;">
                    Our dojo equips every practitioner with unwavering focus, physical resilience, and moral character to face every challenge on and off the tatami. We combine explosive athletic conditioning with classical breath control and ethical conduct.
                </p>
                <div>
                    <a href="<?= BASE_PATH ?>/about" class="text-red text-decoration-none fw-bold d-inline-flex align-items-center gap-2">
                        <span>Explore Our History, Lineage &amp; Chief Instructor</span>
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </div>

            <!-- Right: Three Pillars Cards -->
            <div class="col-lg-6">
                <div class="d-flex flex-column gap-3">
                    <div class="card-dark p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger flex-shrink-0">
                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                            </div>
                            <div>
                                <h5 class="text-white fw-bold mb-1">Authentic Belt Progression</h5>
                                <p class="text-muted-custom small mb-0">Structured syllabus from White Belt through Dan grades with rigorous standard examinations and certified ranks.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-dark p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger flex-shrink-0">
                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <h5 class="text-white fw-bold mb-1">Practical Self-Defense &amp; Sparring</h5>
                                <p class="text-muted-custom small mb-0">Beyond competition, students master realistic defense tactics, situational awareness, and anti-bullying confidence.</p>
                            </div>
                        </div>
                    </div>

                    <div class="card-dark p-4">
                        <div class="d-flex align-items-start gap-3">
                            <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger flex-shrink-0">
                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                            <div>
                                <h5 class="text-white fw-bold mb-1">Holistic Training for All Ages</h5>
                                <p class="text-muted-custom small mb-0">Tailored divisions for Little Tigers (5–9 yrs), Cadets (10–16 yrs), and Adults focusing on agility, discipline, and health.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── 4. Belt Journey Section ──────────────────────────────────────────────── -->
<section class="section-py bg-black border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5 gap-3">
            <div>
                <span class="section-eyebrow">GOJU-RYU PROGRESSION</span>
                <h2 class="heading-lg text-white mb-2">
                    THE BELT <span class="text-red">JOURNEY</span>
                </h2>
                <p class="text-muted-custom mb-0" style="max-width: 650px;">
                    From foundational stances to complete technical mastery, each rank represents dedication, perseverance, and spiritual growth.
                </p>
            </div>
            <div>
                <a href="<?= BASE_PATH ?>/belts" class="btn btn-outline-custom btn-sm">
                    View Full Belt Syllabus &rarr;
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($belts as $b): ?>
                <?php
                    $isWhiteOrYellow = in_array(strtoupper($b['color']), ['#FFFFFF', '#EAB308']);
                    $textColor = $isWhiteOrYellow ? '#0A0A0A' : '#FFFFFF';
                ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card-dark h-100 p-4 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom border-secondary border-opacity-10">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-xs shadow"
                                         style="width: 38px; height: 38px; background-color: <?= escAttr($b['color']) ?>; color: <?= $textColor ?>; border: 1.5px solid rgba(255,255,255,0.2);">
                                        <?= (int)$b['rank'] ?>
                                    </div>
                                    <div>
                                        <div class="text-muted-custom text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                                            Rank <?= (int)$b['rank'] ?>
                                        </div>
                                        <h6 class="text-white fw-bold mb-0">
                                            <?= esc($b['name']) ?>
                                        </h6>
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted-custom small mb-0" style="line-height: 1.6;">
                                <?= esc($b['description']) ?>
                            </p>
                        </div>

                        <div class="pt-3 mt-3 border-top border-secondary border-opacity-10 d-flex justify-content-between align-items-center text-xs">
                            <span class="text-muted-custom">Assessment:</span>
                            <span class="text-red fw-bold">
                                <?php if ((float)$b['exam_fee'] > 0): ?>
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
    </div>
</section>

<!-- ─── 5. Upcoming Events Preview Section ───────────────────────────────────── -->
<section class="section-py dark-section border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5 gap-3">
            <div>
                <span class="section-eyebrow">UPCOMING SCHEDULES</span>
                <h2 class="heading-lg text-white mb-2">
                    TOURNAMENTS &amp; <span class="text-red">BELT EXAMS</span>
                </h2>
                <p class="text-muted-custom mb-0">
                    Participate in state competitions, intensive training seminars, and scheduled belt grading assessments.
                </p>
            </div>
            <div>
                <a href="<?= BASE_PATH ?>/events" class="btn btn-outline-custom btn-sm">
                    View All Events &rarr;
                </a>
            </div>
        </div>

        <?php if (!empty($events)): ?>
            <div class="row g-4">
                <?php foreach ($events as $ev): ?>
                    <?php
                        $ts = strtotime($ev['event_date']);
                        $month = strtoupper(date('M', $ts));
                        $day = date('d', $ts);
                    ?>
                    <div class="col-md-4">
                        <div class="card-dark h-100 p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="text-center p-2 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25" style="min-width: 54px;">
                                        <div class="text-danger fw-bold small"><?= $month ?></div>
                                        <div class="text-white fw-black fs-4 lh-1"><?= $day ?></div>
                                    </div>
                                    <span class="badge bg-secondary bg-opacity-25 text-light text-uppercase">
                                        <?= esc(str_replace('_', ' ', $ev['type'])) ?>
                                    </span>
                                </div>
                                <h5 class="text-white fw-bold mb-2"><?= esc($ev['event_name']) ?></h5>
                                <p class="text-muted-custom small mb-3"><?= esc($ev['description']) ?></p>
                            </div>
                            <div class="pt-3 border-top border-secondary border-opacity-10 small text-muted-custom">
                                <div class="mb-1">&#128337; <?= esc($ev['start_time']) ?></div>
                                <div>&#128205; <?= esc($ev['venue']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Neutral Dignified Empty State -->
            <div class="card-dark p-5 text-center my-3 mx-auto" style="max-width: 640px;">
                <div class="text-red mb-3">
                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h5 class="text-white fw-bold mb-2">Upcoming Schedules &amp; Belt Examinations</h5>
                <p class="text-muted-custom small mb-4">
                    New championship dates and belt examination schedules are currently being finalized by the Technical Committee. Please check back soon or inquire with the dojo administration for seasonal batch updates.
                </p>
                <div>
                    <a href="<?= BASE_PATH ?>/contact" class="btn btn-outline-custom btn-sm">
                        Inquire with Academy Office
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ─── 6. Black Belt Hall of Fame Preview ───────────────────────────────────── -->
<section class="section-py bg-black border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5 gap-3">
            <div>
                <span class="section-eyebrow">DAN RANKINGS &amp; MASTERY</span>
                <h2 class="heading-lg text-white mb-2">
                    BLACK BELT <span class="text-red">HALL OF FAME</span>
                </h2>
                <p class="text-muted-custom mb-0">
                    Honoring dedicated practitioners who have demonstrated relentless perseverance and achieved international Dan rankings.
                </p>
            </div>
            <div>
                <a href="<?= BASE_PATH ?>/black-belts" class="btn btn-outline-custom btn-sm">
                    Full Hall of Fame &rarr;
                </a>
            </div>
        </div>

        <?php if (!empty($blackBelts)): ?>
            <div class="row g-4">
                <?php foreach ($blackBelts as $bb): ?>
                    <div class="col-md-4">
                        <div class="card-dark h-100 p-4 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom border-secondary border-opacity-10">
                                    <div>
                                        <span class="text-red fw-bold small text-uppercase tracking-wider d-block"><?= esc($bb['black_belt_rank']) ?></span>
                                        <span class="text-muted-custom text-xs">Awarded: <?= (int)$bb['year_achieved'] ?></span>
                                    </div>
                                    <div class="badge bg-danger text-white">DAN</div>
                                </div>
                                <h5 class="text-white fw-bold mb-2"><?= esc($bb['name']) ?></h5>
                                <p class="text-muted-custom small mb-0"><?= esc($bb['achievements']) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Neutral Accreditation State -->
            <div class="card-dark p-5 text-center my-3 mx-auto" style="max-width: 640px;">
                <div class="text-red mb-3">
                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
                <h5 class="text-white fw-bold mb-2">Black Belt Registry &amp; Dan Accreditation</h5>
                <p class="text-muted-custom small mb-4">
                    The official Black Belt registry is undergoing annual credential verification with the World Goju-Ryu technical standards board. Dan recipient profiles will be published upon completion of the accreditation update.
                </p>
                <div>
                    <a href="<?= BASE_PATH ?>/belts" class="btn btn-outline-custom btn-sm">
                        Explore Dan Requirements
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ─── 7. Inside The Academy (Gallery Preview) ──────────────────────────────── -->
<section class="section-py dark-section border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5 gap-3">
            <div>
                <span class="section-eyebrow">DOJO LIFE &amp; ACTION</span>
                <h2 class="heading-lg text-white mb-2">
                    INSIDE THE <span class="text-red">ACADEMY</span>
                </h2>
                <p class="text-muted-custom mb-0">
                    A glimpse into daily martial practice, championship triumphs, and the unbreakable spirit of our practitioners.
                </p>
            </div>
            <div>
                <a href="<?= BASE_PATH ?>/gallery" class="btn btn-outline-custom btn-sm">
                    View Full Gallery &rarr;
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php if (!empty($galleryImages)): ?>
                <?php foreach ($galleryImages as $img): ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card-dark overflow-hidden p-0 h-100 position-relative" style="min-height: 240px;">
                            <img src="<?= escAttr($img['image_url']) ?>" alt="<?= escAttr($img['title']) ?>" class="w-100 h-100 object-fit-cover">
                            <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-gradient-dark">
                                <span class="badge bg-danger text-white text-xs mb-1"><?= esc($img['category']) ?></span>
                                <h6 class="text-white fw-bold mb-0 small"><?= esc($img['title']) ?></h6>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Stylized Martial Category Showcase (Matches Next.js design) -->
                <?php foreach ($fallbackGallery as $item): ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card-dark p-4 h-100 d-flex flex-column justify-content-between rounded-4"
                             style="min-height: 220px; background: <?= $item['grad'] ?>; border-color: rgba(220, 38, 38, 0.2);">
                            <div>
                                <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25 text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.08em;">
                                    <?= esc($item['tag']) ?>
                                </span>
                            </div>
                            <div>
                                <div class="text-muted-custom text-uppercase small mb-1" style="font-size: 0.7rem; letter-spacing: 0.08em;">
                                    <?= esc($item['cat']) ?>
                                </div>
                                <h5 class="text-white fw-bold mb-0" style="font-size: 1.05rem;">
                                    <?= esc($item['title']) ?>
                                </h5>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ─── 8. Training Locations Preview ────────────────────────────────────────── -->
<section class="section-py bg-black border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5 gap-3">
            <div>
                <span class="section-eyebrow">FIND A DOJO</span>
                <h2 class="heading-lg text-white mb-2">
                    TRAINING <span class="text-red">CENTERS</span>
                </h2>
                <p class="text-muted-custom mb-0">
                    Equipped with safety tatami mats, punching bags, traditional makiwara, and certified instructors.
                </p>
            </div>
            <div>
                <a href="<?= BASE_PATH ?>/locations" class="btn btn-outline-custom btn-sm">
                    All Dojo Locations &rarr;
                </a>
            </div>
        </div>

        <?php if (!empty($locations)): ?>
            <div class="row g-4">
                <?php foreach ($locations as $loc): ?>
                    <div class="col-md-4">
                        <div class="card-dark h-100 p-4 d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 text-uppercase mb-3">
                                    Active Dojo
                                </span>
                                <h5 class="text-white fw-bold mb-2"><?= esc($loc['location_name']) ?></h5>
                                <p class="text-muted-custom small mb-3"><?= esc($loc['address']) ?></p>
                                <?php if (!empty($loc['instructor'])): ?>
                                    <div class="small text-muted-custom mb-2">
                                        <span class="text-secondary">Instructor:</span> <strong class="text-white"><?= esc($loc['instructor']) ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="pt-3 border-top border-secondary border-opacity-10 small text-muted-custom">
                                <div class="mb-1">&#128197; <?= esc($loc['training_days']) ?></div>
                                <div>&#128337; <?= esc($loc['training_time']) ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Neutral Dojo Inquiry State -->
            <div class="card-dark p-5 text-center my-3 mx-auto" style="max-width: 640px;">
                <div class="text-red mb-3">
                    <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <h5 class="text-white fw-bold mb-2">Authorized Dojo Branches &amp; Schedules</h5>
                <p class="text-muted-custom small mb-4">
                    Authorized training dojos and class timings are configured by batch seasons across Gujarat. To locate the closest training batch, schedule a trial session, or inquire about open enrollment, please reach out to our administrative team.
                </p>
                <div>
                    <a href="<?= BASE_PATH ?>/contact" class="btn btn-primary-custom btn-sm">
                        Contact Academy for Locations
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ─── 9. Contact / Join Academy CTA Banner ─────────────────────────────────── -->
<section class="section-py cta-banner text-center text-white border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5 position-relative" style="z-index: 1;">
        <span class="badge bg-white text-danger fw-bold mb-3 px-3 py-2 text-uppercase tracking-wide" style="font-size: 0.75rem;">
            ADMISSIONS OPEN FOR ALL AGE GROUPS
        </span>
        <h2 class="heading-lg text-white mb-3">
            BEGIN YOUR MARTIAL JOURNEY AT <span class="text-white">ARMOUR KARATE</span>
        </h2>
        <p class="lead text-white-50 mx-auto mb-4" style="max-width: 700px; font-size: 1.1rem;">
            Whether your goal is black belt certification, tournament competition, or self-defense conditioning, our instructors will guide you every step of the way.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= BASE_PATH ?>/join" class="btn btn-light btn-lg fw-bold text-danger px-4 shadow">
                Enroll In Academy Now &rarr;
            </a>
            <a href="<?= BASE_PATH ?>/contact" class="btn btn-outline-light btn-lg px-4">
                Contact Dojo Team
            </a>
        </div>
        <div class="pt-4 mt-2 text-white-50 small">
            <span>Official Academy &bull; Gujarat, India &bull; Gujju Karate Association</span>
        </div>
    </div>
</section>

<?php
require_once APP_ROOT . '/app/views/layouts/public-footer.php';
