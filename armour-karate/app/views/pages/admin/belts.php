<?php
declare(strict_types=1);
requireAdmin();

$pageTitle  = 'Belt Management';
$portalType = 'admin';
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh;text-align:center;">
    <div class="mb-4" style="width:64px;height:64px;background:#FEF3C7;border-radius:50%;display:flex;align-items:center;justify-content:center;">
        <svg width="28" height="28" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
        </svg>
    </div>
    <h2 class="fw-black text-uppercase mb-2" style="font-size:1.25rem;letter-spacing:0.04em;">Belt Management</h2>
    <p class="text-muted mb-4" style="max-width:480px;font-size:0.875rem;line-height:1.6;">
        This module is scheduled for Stage 7 development. It will allow you to manage belt grades, requirements, colour assignments, and promotion criteria for all ranks.
    </p>
    <span class="badge rounded-pill text-bg-warning px-3 py-2" style="font-size:0.75rem;font-weight:700;">Planned &mdash; Stage 7</span>
</div>
<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
