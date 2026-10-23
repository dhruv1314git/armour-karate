<?php
declare(strict_types=1);
requireAdmin();

$pageTitle   = 'Fee Records';
$portalType  = 'admin';
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh;text-align:center;">
    <div class="mb-4" style="width:64px;height:64px;background:#FEF3C7;border-radius:50%;display:flex;align-items:center;justify-content:center;">
        <svg width="28" height="28" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <h2 class="fw-black text-uppercase mb-2" style="font-size:1.25rem;letter-spacing:0.04em;">Fee Records</h2>
    <p class="text-muted mb-4" style="max-width:480px;font-size:0.875rem;line-height:1.6;">
        This module is scheduled for Stage 7 development. It will allow you to manage student fee collection, payment history, outstanding dues, receipts, and automated reminders.
    </p>
    <span class="badge rounded-pill text-bg-warning px-3 py-2" style="font-size:0.75rem;font-weight:700;">Planned &mdash; Stage 7</span>
</div>
<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
