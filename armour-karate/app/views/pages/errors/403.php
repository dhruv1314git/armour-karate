<?php
/**
 * 403 Access Denied Page
 * app/views/pages/errors/403.php
 */

declare(strict_types=1);

$pageTitle = 'Access Denied (403)';
require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>
<section class="hero-section text-center py-5">
    <div class="hero-glow"></div>
    <div class="hero-dot-grid"></div>
    <div class="hero-content">
        <span class="hero-eyebrow text-red">SECURITY GUARD</span>
        <h1 class="hero-title">ACCESS <span class="text-red">DENIED</span></h1>
        <p class="hero-description">
            You do not have permission to access this administrative or protected student resource.
        </p>
        <div class="hero-cta-group">
            <a href="<?= BASE_PATH ?>/" class="btn-primary-custom">
                &larr; Return to Home
            </a>
            <a href="<?= BASE_PATH ?>/login" class="btn-outline-custom">
                Student Login
            </a>
        </div>
    </div>
</section>
<?php
require_once APP_ROOT . '/app/views/layouts/public-footer.php';
