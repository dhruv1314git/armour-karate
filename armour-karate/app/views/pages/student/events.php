<?php
/**
 * Armour Martial Arts – Gujju Karate Association
 * Student Academy Events & Tournaments
 * app/views/pages/student/events.php
 *
 * Faithfully recreates the original Next.js student events page:
 *  - Published academy tournaments, seminars, and training camps
 *  - Categorized into Upcoming Events and Concluded Events
 *  - Responsive cards with event type badge, date, time, venue & description
 *  - Privacy guard: Only status = 'PUBLISHED' events are displayed
 */

declare(strict_types=1);

requireStudent();
$pdo = db();

$pageTitle  = 'Academy Events & Tournaments';
$portalType = 'student';

// Fetch all published events
$stmtEvents = $pdo->query(
    "SELECT * FROM events 
     WHERE status = 'PUBLISHED' 
     ORDER BY event_date ASC, id ASC"
);
$allEvents = $stmtEvents->fetchAll();

$today = date('Y-m-d');
$upcoming = [];
$past     = [];

foreach ($allEvents as $ev) {
    if ($ev['event_date'] >= $today) {
        $upcoming[] = $ev;
    } else {
        $past[] = $ev;
    }
}

// Order past events descending (most recent past first)
usort($past, fn($a, $b) => strcmp($b['event_date'], $a['event_date']));

require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- Page Title & Header -->
    <div class="border-bottom pb-3">
        <h1 class="fs-4 fw-black text-uppercase text-dark mb-1" style="letter-spacing: -0.01em;">
            Academy Events &amp; Tournaments
        </h1>
        <p class="text-secondary small mb-0" style="font-size: 0.8rem;">
            Championship schedules, conditioning camps, and technical workshops
        </p>
    </div>

    <!-- Upcoming Events Section -->
    <div class="d-flex flex-column gap-3">
        <div class="d-flex align-items-center justify-content-between">
            <h2 class="fs-6 fw-bold text-dark mb-0">Upcoming Events</h2>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.7rem;">
                <?= count($upcoming) ?> <?= count($upcoming) === 1 ? 'Event' : 'Events' ?> Scheduled
            </span>
        </div>

        <?php if (!empty($upcoming)): ?>
            <div class="row g-4">
                <?php foreach ($upcoming as $ev): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100 bg-white d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.08em;">
                                        <?= esc(str_replace('_', ' ', $ev['type'])) ?>
                                    </span>
                                    <span class="text-danger fw-bold font-monospace small" style="font-size: 0.78rem;">
                                        <?= formatDate($ev['event_date']) ?>
                                    </span>
                                </div>

                                <h3 class="fs-6 fw-bold text-dark mb-2 leading-snug">
                                    <?= esc($ev['event_name']) ?>
                                </h3>

                                <p class="text-secondary small mb-3" style="font-size: 0.78rem; line-height: 1.6;">
                                    <?= esc($ev['description'] ?: 'Official academy event organized by Armour Karate.') ?>
                                </p>
                            </div>

                            <div class="pt-3 border-top d-flex flex-column gap-2 text-secondary small" style="font-size: 0.75rem;">
                                <div class="d-flex align-items-center gap-2">
                                    <svg width="13" height="13" fill="currentColor" class="text-danger flex-shrink-0" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
                                    <span><?= esc($ev['start_time'] ?: '9:00 AM') ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <svg width="13" height="13" fill="currentColor" class="text-danger flex-shrink-0" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                                    <span class="text-truncate"><?= esc($ev['venue']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="py-5 text-center bg-white rounded-4 border p-4 text-secondary small">
                No upcoming events are currently scheduled. Check back soon for championship schedules!
            </div>
        <?php endif; ?>
    </div>

    <!-- Concluded Events Section -->
    <?php if (!empty($past)): ?>
        <div class="pt-3 border-top d-flex flex-column gap-3">
            <h2 class="fs-6 fw-bold text-secondary mb-0">Concluded Events</h2>

            <div class="row g-3">
                <?php foreach (array_slice($past, 0, 6) as $ev): ?>
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 bg-white border shadow-sm">
                            <div class="d-flex align-items-center justify-content-between mb-1" style="font-size: 0.7rem;">
                                <span class="text-secondary"><?= formatDate($ev['event_date']) ?></span>
                                <span class="text-uppercase fw-bold text-secondary"><?= esc(str_replace('_', ' ', $ev['type'])) ?></span>
                            </div>
                            <h3 class="fs-6 fw-bold text-dark text-truncate mb-1" style="font-size: 0.85rem;"><?= esc($ev['event_name']) ?></h3>
                            <div class="text-secondary small text-truncate" style="font-size: 0.72rem;"><?= esc($ev['venue']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
