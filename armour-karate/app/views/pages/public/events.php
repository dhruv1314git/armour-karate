<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Events & Tournaments — Public Page
 * app/views/pages/public/events.php
 *
 * Faithfully recreates the original Next.js Events page:
 *  - Hero banner with badge
 *  - Upcoming events grid (cards with date badge, type tag, time, venue)
 *  - Past / Concluded events compact section
 *  - Professional empty state when no published events exist
 *
 * Data source: `events` table WHERE status = 'PUBLISHED'
 * No default/fake events are displayed — only real DB records.
 */

declare(strict_types=1);

$pageTitle       = 'Events, Tournaments & Camps | Armour Karate Academy';
$metaDescription = 'Official schedule of upcoming state and national karate championships, belt promotion examinations, and master seminars by Armour Martial Arts Gujju Karate Association.';

// ─── Database Query ───────────────────────────────────────────────────────────
$pdo = db();

$upcoming = [];
$past     = [];

try {
    $today = date('Y-m-d');

    // Upcoming: PUBLISHED and event_date >= today
    $stmtUp = $pdo->prepare(
        "SELECT id, event_name, type, event_date, start_time, venue, description, poster_url
           FROM events
          WHERE status = 'PUBLISHED'
            AND event_date >= :today
          ORDER BY event_date ASC"
    );
    $stmtUp->execute([':today' => $today]);
    $upcoming = $stmtUp->fetchAll();

    // Past: PUBLISHED and event_date < today
    $stmtPast = $pdo->prepare(
        "SELECT id, event_name, type, event_date, venue
           FROM events
          WHERE status = 'PUBLISHED'
            AND event_date < :today
          ORDER BY event_date DESC
          LIMIT 12"
    );
    $stmtPast->execute([':today' => $today]);
    $past = $stmtPast->fetchAll();

} catch (PDOException $e) {
    appLog('ERROR', 'Events page DB error: ' . $e->getMessage());
    $upcoming = [];
    $past     = [];
}

// ─── Helper: format event type for display ────────────────────────────────────
function fmtEventType(string $type): string
{
    return str_replace('_', ' ', $type);
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
                    <path d="M2.5 3a.5.5 0 0 0 0 1h11a.5.5 0 0 0 0-1h-11zm0 3a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1h-6zm0 3a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1h-6zm8-6a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5zm0 6a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5z"/>
                    <path d="M0 2.5A1.5 1.5 0 0 1 1.5 1h13A1.5 1.5 0 0 1 16 2.5v11a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-11zM1.5 2a.5.5 0 0 0-.5.5v11a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-11a.5.5 0 0 0-.5-.5h-13z"/>
                </svg>
                Competitive Arena &amp; Schedules
            </div>
            <h1 class="fw-black text-uppercase text-white mb-3"
                style="font-size: clamp(2.2rem, 5vw, 3.6rem); letter-spacing:-0.03em; line-height:1.05;">
                Events &amp; <span style="color:#DC2626;">Tournaments</span>
            </h1>
            <p style="color:#d4d4d4; font-size:0.925rem; line-height:1.7;">
                Test your skills on the national stage, attend international master camps, and prepare for official Kyu and Dan grading examinations.
            </p>
        </div>

        <!-- ── Upcoming Events Section ────────────────────────────────────── -->
        <div class="mb-5">
            <h2 class="fw-black text-uppercase text-white d-flex align-items-center gap-2 mb-4"
                style="font-size:1.35rem; letter-spacing:-0.01em;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="#DC2626" viewBox="0 0 16 16" aria-hidden="true">
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                </svg>
                Upcoming Schedules
            </h2>

            <?php if (empty($upcoming)): ?>
                <!-- Empty State -->
                <div class="rounded-4 p-5 text-center"
                     style="background:rgba(28,28,30,0.6); border:1px solid #2a2a2a;">
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#DC2626" viewBox="0 0 16 16" opacity="0.5" aria-hidden="true">
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5zM1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4H1z"/>
                        </svg>
                    </div>
                    <p class="fw-bold text-uppercase mb-1" style="color:#DC2626; font-size:0.7rem; letter-spacing:0.12em;">
                        Schedule Pending
                    </p>
                    <h3 class="fw-black text-white text-uppercase mb-2" style="font-size:1.1rem;">
                        Upcoming Schedules Currently Being Finalized
                    </h3>
                    <p style="color:#a3a3a3; font-size:0.82rem; max-width:480px; margin:0 auto 1.5rem;">
                        The Technical Committee is finalizing tournament dates, belt examination schedules, and seminar programs. Check back soon or contact the academy for current updates.
                    </p>
                    <a href="<?= BASE_PATH ?>/contact" class="btn btn-sm btn-danger px-4 fw-bold text-uppercase"
                       style="letter-spacing:0.08em; background:#DC2626; border:none;">
                        Contact for Updates
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($upcoming as $ev): ?>
                        <?php
                        try {
                            $dateObj = new DateTimeImmutable($ev['event_date']);
                            $month   = strtoupper($dateObj->format('M'));
                            $day     = $dateObj->format('j');
                            $fullDate = $dateObj->format('d M Y');
                        } catch (Exception) {
                            $month    = '—';
                            $day      = '—';
                            $fullDate = '—';
                        }
                        $typeLabel = fmtEventType($ev['type'] ?? 'EVENT');
                        ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="h-100 rounded-4 p-4 d-flex flex-column justify-content-between event-card"
                                 style="background:rgba(28,28,30,0.6); border:1px solid #2a2a2a; transition:all 0.3s ease;">
                                <div>
                                    <!-- Date Badge + Type Tag -->
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                        <!-- Date Badge -->
                                        <div class="d-flex flex-column align-items-center justify-content-center flex-shrink-0"
                                             style="width:56px; height:56px; background:rgba(127,29,29,0.35); border:1px solid rgba(185,28,28,0.3); border-radius:14px;">
                                            <span style="font-size:0.6rem; font-weight:800; color:#f87171; text-transform:uppercase; letter-spacing:0.1em; line-height:1;"><?= esc($month) ?></span>
                                            <span style="font-size:1.3rem; font-weight:900; color:#fff; line-height:1.1;"><?= esc($day) ?></span>
                                        </div>
                                        <!-- Type Tag -->
                                        <span class="px-2 py-1 rounded-2"
                                              style="font-size:0.6rem; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; background:#262626; color:#d4d4d4; border:1px solid #3a3a3a; white-space:nowrap;">
                                            <?= esc($typeLabel) ?>
                                        </span>
                                    </div>

                                    <!-- Event Poster (if available) -->
                                    <?php if (!empty($ev['poster_url'])): ?>
                                        <div class="mb-3 rounded-3 overflow-hidden position-relative" style="height:150px; background:#111; border:1px solid #333;">
                                            <img src="<?= escAttr($ev['poster_url']) ?>"
                                                 alt="<?= escAttr($ev['event_name']) ?> Poster"
                                                 class="w-100 h-100"
                                                 style="object-fit:cover;"
                                                 loading="lazy">
                                        </div>
                                    <?php endif; ?>

                                    <!-- Event Name -->
                                    <h3 class="fw-black text-uppercase text-white mb-2 event-card-title"
                                        style="font-size:1rem; line-height:1.3; transition:color 0.2s;">
                                        <?= esc($ev['event_name']) ?>
                                    </h3>

                                    <!-- Description -->
                                    <?php if (!empty($ev['description'])): ?>
                                        <p class="mb-0" style="font-size:0.78rem; color:#d4d4d4; line-height:1.6;
                                                                display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden;">
                                            <?= esc($ev['description']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Footer: Time + Venue -->
                                <div class="mt-3 pt-3" style="border-top:1px solid #2a2a2a;">
                                    <div class="d-flex align-items-center gap-2 mb-1" style="font-size:0.75rem; color:#a3a3a3;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#DC2626" viewBox="0 0 16 16" flex-shrink="0" aria-hidden="true">
                                            <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                        </svg>
                                        <span><?= esc($ev['start_time'] ?: '—') ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2" style="font-size:0.75rem; color:#a3a3a3;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="#DC2626" viewBox="0 0 16 16" flex-shrink="0" aria-hidden="true">
                                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                                        </svg>
                                        <span class="text-truncate"><?= esc($ev['venue']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Past / Concluded Events ────────────────────────────────────── -->
        <?php if (!empty($past)): ?>
        <div class="pt-5" style="border-top:1px solid #1f1f1f;">
            <h2 class="fw-bold text-uppercase mb-4" style="font-size:1.1rem; color:#737373; letter-spacing:-0.01em;">
                Concluded Championships &amp; Demonstrations
            </h2>
            <div class="row g-3" style="opacity:0.78;">
                <?php foreach ($past as $ev): ?>
                    <?php
                    try {
                        $dateObj  = new DateTimeImmutable($ev['event_date']);
                        $fullDate = $dateObj->format('d M Y');
                    } catch (Exception) {
                        $fullDate = '—';
                    }
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="p-4 rounded-4" style="background:rgba(28,28,30,0.4); border:1px solid #222;">
                            <span class="d-block mb-1" style="font-size:0.65rem; font-weight:800; text-transform:uppercase; letter-spacing:0.1em; color:#525252;">
                                <?= esc($fullDate) ?> &bull; <?= esc(fmtEventType($ev['type'] ?? '')) ?>
                            </span>
                            <h4 class="fw-bold text-white mb-1" style="font-size:0.9rem; line-height:1.3;">
                                <?= esc($ev['event_name']) ?>
                            </h4>
                            <p class="mb-0 text-truncate" style="font-size:0.75rem; color:#737373;">
                                <?= esc($ev['venue']) ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.container -->
</div>

<style>
.event-card:hover {
    border-color: rgba(220,38,38,0.45) !important;
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(127,29,29,0.2);
}
.event-card:hover .event-card-title {
    color: #f87171 !important;
}
</style>

<?php require_once APP_ROOT . '/app/views/layouts/public-footer.php'; ?>
