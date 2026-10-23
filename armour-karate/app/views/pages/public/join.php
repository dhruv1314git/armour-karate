<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Join Academy — New Student Enrollment Page
 * app/views/pages/public/join.php
 *
 * Faithfully recreates the original Next.js Join page:
 *  1. Hero Banner (enrollment year badge, warrior journey title, CTAs)
 *  2. 4-Step Admission Pathway (UserCheck, ShieldCheck, Award, Flame cards)
 *  3. Training Divisions / Programs by Age (Little Tigers, Cadets & Juniors, Adults)
 *  4. Frequently Asked Questions (FAQ cards with HelpCircle icon)
 *  5. Bottom CTA Banner ("The Dojo Awaits Your First Bow")
 */

declare(strict_types=1);

$pageTitle = 'Join The Academy — Student Enrollment';
$metaDescription = 'Enroll in Armour Martial Arts Gujju Karate Association. Traditional Goju-Ryu karate training for kids, teenagers, and adults in Gujarat.';

$steps = [
    [
        'step' => '01',
        'title' => 'Complete Online Registration',
        'desc'  => 'Fill out our secure admission form with student details, emergency guardian contacts, and your preferred dojo training location.',
        'tag'   => 'Takes 3 minutes',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'
    ],
    [
        'step' => '02',
        'title' => 'Sensei Review & Verification',
        'desc'  => 'Chief Sensei and the administrative board review your application, verify age category eligibility, and confirm training batch availability.',
        'tag'   => 'Within 24–48 hours',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'
    ],
    [
        'step' => '03',
        'title' => 'Official Student ID Issued',
        'desc'  => 'Upon approval, a unique Student ID (KAR-YYYY-NNNN) is generated. You receive your confirmation and student portal access.',
        'tag'   => 'Unique Lifetime ID',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138z"/></svg>'
    ],
    [
        'step' => '04',
        'title' => 'Step Onto the Tatami',
        'desc'  => 'Receive your academy uniform (Karate Gi) and White Belt. Attend your inaugural training session, bow to the Sensei, and begin your martial journey.',
        'tag'   => 'White Belt Ceremony',
        'icon'  => '<svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/></svg>'
    ],
];

$programs = [
    [
        'title'    => 'Little Tigers (Ages 5 – 9)',
        'subtitle' => 'Foundations & Motor Coordination',
        'desc'     => 'Focus on balance, listening skills, discipline, and anti-bullying awareness in a fun, positive, and high-energy dojo setting.',
        'schedule' => '3 sessions/week · 45 mins',
        'popular'  => false
    ],
    [
        'title'    => 'Cadets & Juniors (Ages 10 – 16)',
        'subtitle' => 'Athletic Conditioning & Kata Mastery',
        'desc'     => 'Classical Goju-Ryu kata, controlled sparring (Kumite), speed agility, situational self-defense, and state tournament preparation.',
        'schedule' => '3–4 sessions/week · 75 mins',
        'popular'  => true
    ],
    [
        'title'    => 'Adults & Seniors (Ages 17+)',
        'subtitle' => 'Traditional Martial Art & Fitness',
        'desc'     => 'Sanchin breathing power (Ibuki), combat joint locks, joint health, endurance conditioning, and lifelong karate mastery.',
        'schedule' => 'Evening & weekend batches',
        'popular'  => false
    ],
];

$faqs = [
    [
        'q' => 'Do I need prior martial arts experience?',
        'a' => 'None at all. Our beginner syllabus is structured from the ground up starting at 10th Kyu (White Belt). Sensei instructs step-by-step with patient attention.'
    ],
    [
        'q' => 'How does the registration approval work?',
        'a' => 'To maintain dojo safety and verified records, applications are reviewed by the administration. Upon approval, you will receive your official Student ID (KAR-YYYY-NNNN) and portal access.'
    ],
    [
        'q' => 'What should I bring to the first class?',
        'a' => 'Comfortable workout clothing and a water bottle. Your official Goju-Ryu Karate Gi (uniform) and white belt will be provided at the dojo upon admission completion.'
    ],
    [
        'q' => 'Can I transfer from another karate style?',
        'a' => 'Yes. Students from Shotokan, Shito-Ryu, or Wado-Ryu are evaluated by the technical board during an orientation session to determine appropriate Kyu rank placement.'
    ],
];

require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>

<!-- ─── 1. Hero Section ──────────────────────────────────────────────────────── -->
<section class="section-py bg-black border-bottom border-secondary border-opacity-10 position-relative overflow-hidden text-center">
    <div class="hero-glow"></div>
    <div class="container-fluid px-lg-5 position-relative" style="z-index: 1;">
        <div class="mx-auto" style="max-width: 850px;">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-danger bg-opacity-10 border border-danger border-opacity-25 text-danger text-xs fw-bold text-uppercase tracking-widest mb-4">
                <span>&#9670; NEW STUDENT ENROLLMENT — YEAR <?= date('Y') ?> &#9670;</span>
            </div>

            <h1 class="hero-title text-white mb-3" style="font-size: clamp(2.5rem, 6vw, 4.5rem);">
                BEGIN YOUR <span class="text-red">WARRIOR</span> JOURNEY
            </h1>

            <p class="text-muted-custom fs-5 mb-4" style="line-height: 1.8;">
                Join Gujarat’s premier Goju-Ryu Karate-Do Academy. Authentic martial discipline, official belt certifications, state championship glory, and lifetime confidence.
            </p>

            <div class="d-flex justify-content-center gap-3 flex-wrap pt-2">
                <a href="<?= BASE_PATH ?>/register" class="btn btn-primary-custom btn-lg btn-pulse">
                    <span>Proceed to Registration Form</span>
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <a href="<?= BASE_PATH ?>/fees" class="btn btn-outline-custom btn-lg">
                    View Fee Structure
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ─── 2. 4-Step Process Section ────────────────────────────────────────────── -->
<section class="section-py dark-section">
    <div class="container-fluid px-lg-5">
        <div class="text-center mx-auto mb-5" style="max-width: 650px;">
            <span class="section-eyebrow">ADMISSION PATHWAY</span>
            <h2 class="heading-lg text-white mb-2">
                HOW ENROLLMENT <span class="text-red">WORKS</span>
            </h2>
            <p class="text-muted-custom mb-0">
                Our 4-step admission pipeline ensures verified student records, official academy affiliation, and a smooth dojo orientation.
            </p>
        </div>

        <div class="row g-4">
            <?php foreach ($steps as $s): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card-dark p-4 h-100 d-flex flex-column justify-content-between rounded-4 shadow-sm">
                        <div>
                            <!-- Header with Step number and Tag -->
                            <div class="d-flex justify-content-between align-items-center pb-3 mb-3 border-bottom border-secondary border-opacity-10">
                                <span class="text-red fw-black fs-2 font-monospace lh-1">
                                    <?= esc($s['step']) ?>
                                </span>
                                <span class="badge bg-secondary bg-opacity-25 text-light text-xs font-monospace">
                                    <?= esc($s['tag']) ?>
                                </span>
                            </div>

                            <!-- Icon -->
                            <div class="p-3 rounded-3 bg-danger bg-opacity-10 text-danger d-inline-flex mb-3">
                                <?= $s['icon'] ?>
                            </div>

                            <h5 class="text-white fw-bold mb-2">
                                <?= esc($s['title']) ?>
                            </h5>

                            <p class="text-muted-custom small mb-0" style="line-height: 1.7;">
                                <?= esc($s['desc']) ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── 3. Age & Program Categories ─────────────────────────────────────────── -->
<section class="section-py bg-black border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="text-center mx-auto mb-5" style="max-width: 650px;">
            <span class="section-eyebrow">TRAINING DIVISIONS</span>
            <h2 class="heading-lg text-white mb-2">
                PROGRAMS FOR EVERY <span class="text-red">AGE</span>
            </h2>
            <p class="text-muted-custom mb-0">
                Whether you are enrolling a child to develop discipline or an adult seeking elite martial training, we provide age-specific curriculum.
            </p>
        </div>

        <div class="row g-4 justify-content-center" style="max-width: 1100px; margin-left: auto; margin-right: auto;">
            <?php foreach ($programs as $p): ?>
                <div class="col-md-4">
                    <div class="card-dark p-4 p-lg-5 h-100 d-flex flex-column justify-content-between rounded-4 position-relative <?= $p['popular'] ? 'border-danger shadow-lg' : '' ?>"
                         style="<?= $p['popular'] ? 'background: linear-gradient(180deg, #180808 0%, #0d0d0d 100%);' : '' ?>">
                        <?php if ($p['popular']): ?>
                            <span class="position-absolute top-0 start-50 translate-middle badge bg-danger text-white text-uppercase px-3 py-1 shadow" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                                Most Popular
                            </span>
                        <?php endif; ?>

                        <div>
                            <span class="text-red text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.1em;">
                                <?= esc($p['subtitle']) ?>
                            </span>
                            <h4 class="text-white fw-bold mb-3"><?= esc($p['title']) ?></h4>

                            <p class="text-muted-custom small mb-4" style="line-height: 1.8;">
                                <?= esc($p['desc']) ?>
                            </p>
                        </div>

                        <div class="pt-3 border-top border-secondary border-opacity-10">
                            <div class="d-flex align-items-center gap-2 text-muted-custom small mb-3">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span><?= esc($p['schedule']) ?></span>
                            </div>

                            <a href="<?= BASE_PATH ?>/register" class="btn <?= $p['popular'] ? 'btn-primary-custom' : 'btn-outline-custom' ?> w-100 btn-sm">
                                Register for this Division &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ─── 4. Frequently Asked Questions ────────────────────────────────────────── -->
<section class="section-py dark-section border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5">
        <div class="text-center mx-auto mb-5" style="max-width: 650px;">
            <span class="section-eyebrow">CLEAR ANSWERS</span>
            <h2 class="heading-lg text-white mb-2">
                FREQUENTLY ASKED <span class="text-red">QUESTIONS</span>
            </h2>
            <p class="text-muted-custom mb-0">
                Key questions about joining, attendance, uniforms, and rank progression.
            </p>
        </div>

        <div class="mx-auto" style="max-width: 800px;">
            <div class="d-flex flex-column gap-3">
                <?php foreach ($faqs as $f): ?>
                    <div class="card-dark p-4 rounded-4 shadow-sm">
                        <div class="d-flex align-items-start gap-3">
                            <div class="text-red mt-1 flex-shrink-0">
                                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h6 class="text-white fw-bold mb-2"><?= esc($f['q']) ?></h6>
                                <p class="text-muted-custom small mb-0" style="line-height: 1.7;">
                                    <?= esc($f['a']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ─── 5. Bottom Call to Action Banner ──────────────────────────────────────── -->
<section class="section-py cta-banner text-center text-white border-top border-secondary border-opacity-10">
    <div class="container-fluid px-lg-5 position-relative" style="z-index: 1;">
        <h2 class="heading-lg text-white mb-3">
            THE DOJO AWAITS YOUR <span class="text-white">FIRST BOW</span>
        </h2>
        <p class="lead text-white-50 mx-auto mb-4" style="max-width: 680px; font-size: 1.1rem;">
            Take the definitive first step toward lifelong fitness, indomitable spirit, and authentic Goju-Ryu mastery.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= BASE_PATH ?>/register" class="btn btn-light btn-lg fw-bold text-danger px-4 shadow">
                Submit Admission Form &rarr;
            </a>
            <a href="<?= BASE_PATH ?>/contact" class="btn btn-outline-light btn-lg px-4">
                Inquire Dojo Batches
            </a>
        </div>
    </div>
</section>

<?php
require_once APP_ROOT . '/app/views/layouts/public-footer.php';
