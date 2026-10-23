<?php
declare(strict_types=1);
requireAdmin();

$pageTitle  = 'Exam Management';
$portalType = 'admin';
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh;text-align:center;">
    <div class="mb-4" style="width:64px;height:64px;background:#FEF3C7;border-radius:50%;display:flex;align-items:center;justify-content:center;">
        <svg width="28" height="28" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
    </div>
    <h2 class="fw-black text-uppercase mb-2" style="font-size:1.25rem;letter-spacing:0.04em;">Exam Management</h2>
    <p class="text-muted mb-4" style="max-width:480px;font-size:0.875rem;line-height:1.6;">
        This module is scheduled for Stage 7 development. It will allow you to create grading exams, manage registrations, record results, and trigger belt promotions.
    </p>
    <span class="badge rounded-pill text-bg-warning px-3 py-2" style="font-size:0.75rem;font-weight:700;">Planned &mdash; Stage 7</span>
</div>
<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
