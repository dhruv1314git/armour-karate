<?php
/**
 * Planned Route / Feature In Development
 * app/views/pages/errors/coming-soon.php
 *
 * Rendered for recognized routes whose full implementation is scheduled
 * for subsequent migration stages (Stages 2–8).
 */

declare(strict_types=1);

$route = defined('CURRENT_ROUTE') ? CURRENT_ROUTE : '';
$pageTitle = 'Module In Development';

// Determine which migration stage this route belongs to
$stageMap = [
    // Public (Stage 2 & 3)
    'about'                 => ['Stage 2', 'Public Website — About & Academy Info'],
    'belts'                 => ['Stage 2', 'Public Website — Belts & Examination Fees'],
    'fees'                  => ['Stage 2', 'Public Website — Fee Structure'],
    'join'                  => ['Stage 2', 'Public Website — Enrollment Guide'],
    'events'                => ['Stage 3', 'Dynamic Modules — Tournaments & Events'],
    'black-belts'           => ['Stage 3', 'Dynamic Modules — Black Belt Hall of Fame'],
    'gallery'               => ['Stage 3', 'Dynamic Modules — Photo Gallery & Lightbox'],
    'locations'             => ['Stage 3', 'Dynamic Modules — Class Locations & Dojos'],
    'contact'               => ['Stage 3', 'Dynamic Modules — Contact & Enquiries'],

    // Auth (Stage 4)
    'login'                 => ['Stage 4', 'Authentication — Student & Parent Login'],
    'register'              => ['Stage 4', 'Authentication — 4-Step Registration Wizard'],
    'forgot-password'       => ['Stage 4', 'Authentication — Password Reset Request'],
    'reset-password'        => ['Stage 4', 'Authentication — Password Reset Token Handler'],
    'logout'                => ['Stage 4', 'Authentication — Session Termination'],
    'registration-pending'  => ['Stage 4', 'Status Gate — Pending Approval Notice'],
    'registration-rejected' => ['Stage 4', 'Status Gate — Rejected Application Notice'],
    'account-inactive'      => ['Stage 4', 'Status Gate — Inactive Account Notice'],
    'admin/login'           => ['Stage 4', 'Authentication — Administrator Login'],
    'admin/setup'           => ['Stage 4', 'Authentication — Admin Setup (CLI Script: scripts/init-admin.php)'],

    // Student Portal (Stage 5)
    'student/dashboard'     => ['Stage 5', 'Student Portal — Dashboard Overview'],
    'student/belt-journey'  => ['Stage 5', 'Student Portal — Belt Journey & Roadmap'],
    'student/fees'          => ['Stage 5', 'Student Portal — Fee Payment History'],
    'student/exams'         => ['Stage 5', 'Student Portal — Exam Registrations & Results'],
    'student/events'        => ['Stage 5', 'Student Portal — Events & Seminars'],
    'student/class-info'    => ['Stage 5', 'Student Portal — Active Dojo & Class Schedule'],
    'student/profile'       => ['Stage 5', 'Student Portal — Profile Management'],
    'student/account'       => ['Stage 5', 'Student Portal — Account & Password Security'],

    // Admin Panel (Stage 6–8)
    'admin/dashboard'       => ['Stage 6', 'Admin Panel — Management Dashboard & Metrics'],
    'admin/students'        => ['Stage 6', 'Admin Panel — Student Roster & Approvals'],
    'admin/student'         => ['Stage 6', 'Admin Panel — Student Detail Dossier'],
    'admin/exams'           => ['Stage 7', 'Admin Panel — Belt Examination Management'],
    'admin/events'          => ['Stage 7', 'Admin Panel — Event & Tournament Publishing'],
    'admin/gallery'         => ['Stage 7', 'Admin Panel — Gallery Media Management'],
    'admin/black-belt'      => ['Stage 7', 'Admin Panel — Black Belt Profiles'],
    'admin/locations'       => ['Stage 7', 'Admin Panel — Class Locations & Schedules'],
    'admin/fees'            => ['Stage 8', 'Admin Panel — Fee Configuration & Payment Recording'],
    'admin/belts'           => ['Stage 8', 'Admin Panel — Belt Rank Structure & Requirements'],
    'admin/contact'         => ['Stage 8', 'Admin Panel — Contact Enquiry Processing'],
    'admin/audit-logs'      => ['Stage 8', 'Admin Panel — Security Audit Trail'],
    'admin/settings'        => ['Stage 8', 'Admin Panel — Academy Master Settings'],
];

[$stageNum, $stageDesc] = $stageMap[$route] ?? ['Upcoming Stage', 'Feature implementation scheduled'];

require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>
<section class="hero-section text-center py-5">
    <div class="hero-glow"></div>
    <div class="hero-dot-grid"></div>
    <div class="hero-content">
        <span class="hero-eyebrow text-red">ROUTE REGISTERED &bull; <?= esc($stageNum) ?></span>
        <h1 class="hero-title">MODULE IN <span class="text-red">DEVELOPMENT</span></h1>
        <p class="hero-description">
            The route <code class="text-warning">/<?= esc($route) ?></code> is mapped to the Core PHP architecture and scheduled for implementation in <strong><?= esc($stageNum) ?></strong> (<em><?= esc($stageDesc) ?></em>).
        </p>
        <div class="p-3 mb-4 rounded border border-secondary text-start mx-auto" style="max-width: 540px; background: rgba(28,28,30,0.8);">
            <div class="small text-muted mb-1">Architecture status:</div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge badge-custom badge-active">Route Defined</span>
                <span class="badge badge-custom badge-promoted">Clean URL Ready</span>
                <span class="badge badge-custom badge-pending">UI Awaiting Stage</span>
            </div>
            <div class="small text-white-50">
                Front controller has successfully resolved this path. Full feature database tables and view templates will be activated in the designated stage upon approval.
            </div>
        </div>
        <div class="hero-cta-group">
            <a href="<?= BASE_PATH ?>/" class="btn-primary-custom">
                &larr; Return to Homepage
            </a>
            <a href="<?= BASE_PATH ?>/contact" class="btn-outline-custom">
                Contact Academy
            </a>
        </div>
    </div>
</section>
<?php
require_once APP_ROOT . '/app/views/layouts/public-footer.php';
