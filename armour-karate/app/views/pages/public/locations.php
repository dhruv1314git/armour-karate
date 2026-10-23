<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Class Locations — Public Page
 * app/views/pages/public/locations.php
 *
 * Faithfully recreates the original Next.js Locations page:
 *  - Hero banner
 *  - Location cards grid (name, address, instructor, days, time, contact, maps)
 *  - Head Office / Administrative Headquarters card (from DB, verified fields only)
 *  - Professional empty state when no active locations exist
 *
 * Data source:
 *  - `class_locations` WHERE is_active = 1  (all currently set to 0 — safe empty state)
 *  - `head_office` singleton row
 *
 * SAFETY:
 *  - Does not reactivate hidden demo locations.
 *  - Does not display unverified Ahmedabad / Surat / Vadodara branches.
 *  - Only shows contact / map if column is non-NULL and non-empty.
 *  - Google Maps links validated as safe https:// URLs only.
 */

declare(strict_types=1);

$pageTitle       = 'Dojo Locations & Training Centers | Armour Karate Academy';
$metaDescription = 'Find your nearest Armour Martial Arts Gujju Karate Association training center. View batch timings, instructors, and directions.';

// ─── Database Queries ─────────────────────────────────────────────────────────
$pdo       = db();
$locations = [];
$headOffice = null;

try {
    $stmt = $pdo->query(
        "SELECT id, location_name, address, instructor, training_days, training_time, contact, map_url
           FROM class_locations
          WHERE is_active = 1
          ORDER BY location_name ASC"
    );
    $locations = $stmt->fetchAll();
} catch (PDOException $e) {
    appLog('ERROR', 'Locations page DB error (locations): ' . $e->getMessage());
    $locations = [];
}

try {
    $stmt2      = $pdo->prepare("SELECT id, address, phone, email, office_timing, map_url FROM head_office WHERE id = 1");
    $stmt2->execute();
    $headOffice = $stmt2->fetch() ?: null;
} catch (PDOException $e) {
    appLog('ERROR', 'Locations page DB error (head_office): ' . $e->getMessage());
    $headOffice = null;
}

/**
 * Validate that a map URL is a safe external HTTPS URL.
 * Prevents JavaScript injection and open redirects.
 */
function safeMapUrl(?string $url): string
{
    if (empty($url)) return '';
    $url = trim($url);
    // Must be https:// Google Maps or similar trusted mapping service
    if (
        preg_match('#^https://(maps\.google\.com|www\.google\.com/maps|goo\.gl/maps|maps\.app\.goo\.gl)#i', $url)
    ) {
        return $url;
    }
    return '';
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
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                </svg>
                Find Your Dojo
            </div>
            <h1 class="fw-black text-uppercase text-white mb-3"
                style="font-size: clamp(2.2rem, 5vw, 3.6rem); letter-spacing:-0.03em; line-height:1.05;">
                Training <span style="color:#DC2626;">Centers</span>
            </h1>
            <p style="color:#d4d4d4; font-size:0.925rem; line-height:1.7;">
                Armour Karate branches offer certified Goju-Ryu training with premium facilities and qualified instructors.
            </p>
        </div>

        <!-- ── Locations Grid or Empty State ─────────────────────────────── -->
        <?php if (empty($locations)): ?>
            <div class="rounded-4 p-5 text-center mb-5"
                 style="background:rgba(28,28,30,0.6); border:1px solid #2a2a2a;">
                <div class="mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="52" height="52" fill="#DC2626" viewBox="0 0 16 16" opacity="0.4" aria-hidden="true">
                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                    </svg>
                </div>
                <p class="fw-bold text-uppercase mb-1" style="color:#DC2626; font-size:0.7rem; letter-spacing:0.12em;">
                    Location Details Pending
                </p>
                <h3 class="fw-black text-white text-uppercase mb-2" style="font-size:1.1rem;">
                    Dojo Branch Information Coming Soon
                </h3>
                <p style="color:#a3a3a3; font-size:0.82rem; max-width:480px; margin:0 auto 1.5rem;">
                    Verified training center locations and schedules are being confirmed by the Technical Committee. Contact the academy directly for the nearest training center near you.
                </p>
                <a href="<?= BASE_PATH ?>/contact" class="btn btn-sm btn-danger px-4 fw-bold text-uppercase"
                   style="letter-spacing:0.08em; background:#DC2626; border:none;">
                    Contact Academy for Locations
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-5">
                <?php foreach ($locations as $loc): ?>
                    <?php
                    $safeMap = safeMapUrl($loc['map_url'] ?? '');
                    $contact = !empty($loc['contact']) ? trim($loc['contact']) : '';
                    // Only show contact if it looks like a real phone number (not empty)
                    $showContact = ($contact !== '' && strlen($contact) >= 7);
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="location-card h-100 rounded-4 p-4 p-sm-5 d-flex flex-column justify-content-between"
                             style="background:rgba(28,28,30,0.6); border:1px solid #2a2a2a; transition:all 0.3s ease;">
                            <div>
                                <!-- Header -->
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                    <span class="px-2 py-1 rounded-pill"
                                          style="font-size:0.6rem; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; background:rgba(127,29,29,0.35); border:1px solid rgba(185,28,28,0.3); color:#f87171;">
                                        Active Branch
                                    </span>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#525252" class="location-pin-icon" viewBox="0 0 16 16" aria-hidden="true" style="transition:color 0.2s; flex-shrink:0;">
                                        <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                    </svg>
                                </div>

                                <!-- Name + Address -->
                                <div class="mb-3">
                                    <h3 class="fw-black text-uppercase text-white location-card-title mb-2"
                                        style="font-size:1rem; line-height:1.3; transition:color 0.2s;">
                                        <?= esc($loc['location_name']) ?>
                                    </h3>
                                    <p style="font-size:0.78rem; color:#737373; line-height:1.6;">
                                        <?= esc($loc['address']) ?>
                                    </p>
                                </div>

                                <!-- Details -->
                                <div class="pt-3" style="border-top:1px solid #222;">
                                    <?php if (!empty($loc['instructor'])): ?>
                                    <div class="d-flex align-items-center gap-2 mb-2" style="font-size:0.75rem; color:#d4d4d4;">
                                        <span style="color:#737373; font-weight:600;">Sensei:</span>
                                        <span class="fw-bold text-white"><?= esc($loc['instructor']) ?></span>
                                    </div>
                                    <?php endif; ?>

                                    <div class="d-flex align-items-center gap-2 mb-2" style="font-size:0.75rem; color:#a3a3a3;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#DC2626" viewBox="0 0 16 16" flex-shrink="0" aria-hidden="true">
                                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                                        </svg>
                                        <span><?= esc($loc['training_days']) ?></span>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 mb-2" style="font-size:0.75rem; color:#a3a3a3;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#DC2626" viewBox="0 0 16 16" flex-shrink="0" aria-hidden="true">
                                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                        </svg>
                                        <span><?= esc($loc['training_time']) ?></span>
                                    </div>

                                    <?php if ($showContact): ?>
                                    <div class="d-flex align-items-center gap-2 pt-1" style="font-size:0.78rem; color:#fff; font-family:monospace;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#DC2626" viewBox="0 0 16 16" flex-shrink="0" aria-hidden="true">
                                            <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.568 17.568 0 0 0 4.168 6.608 17.569 17.569 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.678.678 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.678.678 0 0 0-.122-.58L3.654 1.328zM1.884.511a1.745 1.745 0 0 1 2.612.163L6.29 2.98c.329.423.445.974.315 1.494l-.547 2.19a.678.678 0 0 0 .178.643l2.457 2.457a.678.678 0 0 0 .644.178l2.189-.547a1.745 1.745 0 0 1 1.494.315l2.306 1.794c.829.645.905 1.87.163 2.611l-1.034 1.034c-.74.74-1.846 1.065-2.877.702a18.634 18.634 0 0 1-7.01-4.42 18.634 18.634 0 0 1-4.42-7.009c-.362-1.03-.037-2.137.703-2.877L1.885.511z"/>
                                        </svg>
                                        <a href="tel:<?= escAttr(preg_replace('/[^0-9+]/', '', $contact)) ?>"
                                           style="color:inherit; text-decoration:none;"
                                           onmouseover="this.style.textDecoration='underline'"
                                           onmouseout="this.style.textDecoration='none'">
                                            <?= esc($contact) ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="d-flex align-items-center justify-content-between pt-4 mt-3" style="border-top:1px solid #222;">
                                <?php if (!empty($safeMap)): ?>
                                    <a href="<?= escAttr($safeMap) ?>"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       style="font-size:0.72rem; font-weight:700; color:#DC2626; text-decoration:none; display:inline-flex; align-items:center; gap:4px; transition:color 0.2s;"
                                       onmouseover="this.style.color='#f87171'"
                                       onmouseout="this.style.color='#DC2626'">
                                        <span>Google Maps Directions</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                            <path d="M8.636 3.5a.5.5 0 0 0-.5-.5H1.5A1.5 1.5 0 0 0 0 4.5v10A1.5 1.5 0 0 0 1.5 16h10a1.5 1.5 0 0 0 1.5-1.5V7.864a.5.5 0 0 0-1 0V14.5a.5.5 0 0 1-.5.5h-10a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5h6.636a.5.5 0 0 0 .5-.5z"/>
                                            <path d="M16 .5a.5.5 0 0 0-.5-.5h-5a.5.5 0 0 0 0 1h3.793L6.146 9.146a.5.5 0 1 0 .708.708L15 1.707V5.5a.5.5 0 0 0 1 0v-5z"/>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <span style="font-size:0.72rem; color:#525252;">Contact for directions</span>
                                <?php endif; ?>

                                <a href="<?= BASE_PATH ?>/join"
                                   class="px-3 py-1 rounded-2 fw-bold text-uppercase text-white text-decoration-none"
                                   style="font-size:0.65rem; letter-spacing:0.08em; background:#262626; border:1px solid #333; transition:background 0.2s;"
                                   onmouseover="this.style.background='#333'"
                                   onmouseout="this.style.background='#262626'">
                                    Join Branch
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ── Head Office / Administrative Headquarters ───────────────────── -->
        <div class="rounded-4 p-5 p-lg-5"
             style="background:linear-gradient(135deg, #111 0%, #1a0707 100%); border:1px solid #2a2a2a; max-width:900px; margin:0 auto;">
            <div class="mb-4">
                <span style="font-size:0.65rem; font-weight:800; text-transform:uppercase; letter-spacing:0.12em; color:#DC2626; display:block; margin-bottom:6px;">
                    Administrative Headquarters
                </span>
                <h2 class="fw-black text-uppercase text-white mb-2" style="font-size:1.4rem; letter-spacing:-0.01em;">
                    Armour State Head Office &amp; Technical Board
                </h2>
                <p style="font-size:0.82rem; color:#d4d4d4; line-height:1.7; max-width:640px;">
                    For state affiliations, referee licensing, international Dan registration, and institutional training programs, visit or contact our central headquarters.
                </p>
            </div>

            <div class="row g-3">
                <!-- Address -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="p-3 rounded-3" style="background:rgba(10,10,10,0.7); border:1px solid #222;">
                        <span style="font-size:0.65rem; color:#525252; font-weight:600; display:block; margin-bottom:4px;">Registered Region</span>
                        <span class="fw-bold text-white" style="font-size:0.8rem;">
                            <?= esc(($headOffice && !empty($headOffice['address'])) ? $headOffice['address'] : 'Gujarat, India') ?>
                        </span>
                    </div>
                </div>

                <!-- Phone: only if verified -->
                <?php if ($headOffice && !empty($headOffice['phone'])): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="p-3 rounded-3" style="background:rgba(10,10,10,0.7); border:1px solid #222;">
                        <span style="font-size:0.65rem; color:#525252; font-weight:600; display:block; margin-bottom:4px;">State Helpline</span>
                        <a href="tel:<?= escAttr(preg_replace('/[^0-9+]/', '', $headOffice['phone'])) ?>"
                           class="fw-bold text-white text-decoration-none" style="font-size:0.8rem; font-family:monospace;">
                            <?= esc($headOffice['phone']) ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Email: only if verified -->
                <?php if ($headOffice && !empty($headOffice['email'])): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="p-3 rounded-3" style="background:rgba(10,10,10,0.7); border:1px solid #222;">
                        <span style="font-size:0.65rem; color:#525252; font-weight:600; display:block; margin-bottom:4px;">Official Email</span>
                        <a href="mailto:<?= escAttr($headOffice['email']) ?>"
                           class="fw-bold text-white text-decoration-none" style="font-size:0.8rem; word-break:break-all;">
                            <?= esc($headOffice['email']) ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Office Timing: only if verified -->
                <?php if ($headOffice && !empty($headOffice['office_timing'])): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="p-3 rounded-3" style="background:rgba(10,10,10,0.7); border:1px solid #222;">
                        <span style="font-size:0.65rem; color:#525252; font-weight:600; display:block; margin-bottom:4px;">Office Hours</span>
                        <span class="fw-bold text-white" style="font-size:0.8rem;">
                            <?= esc($headOffice['office_timing']) ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Fallback: Contact CTA when details not yet verified -->
                <?php if (!$headOffice || (empty($headOffice['phone']) && empty($headOffice['email']))): ?>
                <div class="col-12">
                    <div class="p-3 rounded-3 d-flex align-items-center gap-3" style="background:rgba(10,10,10,0.7); border:1px solid #222;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#DC2626" viewBox="0 0 16 16" flex-shrink="0" aria-hidden="true">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                        <span style="font-size:0.78rem; color:#a3a3a3;">
                            Contact information is being verified. Use the
                            <a href="<?= BASE_PATH ?>/contact" style="color:#DC2626; text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">contact form</a>
                            to reach the academy.
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.container -->
</div>

<style>
.location-card:hover {
    border-color: rgba(220,38,38,0.45) !important;
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(127,29,29,0.2);
}
.location-card:hover .location-card-title {
    color: #f87171 !important;
}
.location-card:hover .location-pin-icon {
    fill: #DC2626 !important;
}
</style>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
