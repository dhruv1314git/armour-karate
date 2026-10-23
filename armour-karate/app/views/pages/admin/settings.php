<?php
declare(strict_types=1);
requireAdmin();

$pageTitle  = 'System Settings';
$portalType = 'admin';
require_once APP_ROOT . '/app/views/layouts/portal-header.php';
?>
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh;text-align:center;">
    <div class="mb-4" style="width:64px;height:64px;background:#FEF3C7;border-radius:50%;display:flex;align-items:center;justify-content:center;">
        <svg width="28" height="28" fill="none" stroke="#D97706" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </div>
    <h2 class="fw-black text-uppercase mb-2" style="font-size:1.25rem;letter-spacing:0.04em;">System Settings</h2>
    <p class="text-muted mb-4" style="max-width:480px;font-size:0.875rem;line-height:1.6;">
        This module is scheduled for Stage 7 development. It will allow you to configure site-wide settings including branding, email templates, notification preferences, and admin role management.
    </p>
    <span class="badge rounded-pill text-bg-warning px-3 py-2" style="font-size:0.75rem;font-weight:700;">Planned &mdash; Stage 7</span>
</div>
<?php require_once APP_ROOT . '/app/views/layouts/portal-footer.php'; ?>
