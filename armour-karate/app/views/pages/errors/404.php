<?php
/**
 * 404 Not Found Page
 * app/views/pages/errors/404.php
 */

declare(strict_types=1);

$pageTitle = 'Page Not Found (404)';
require_once APP_ROOT . '/app/views/layouts/public-header.php';
?>
<section class="hero-section text-center py-5">
    <div class="hero-glow"></div>
    <div class="hero-dot-grid"></div>
    <div class="hero-content">
        <span class="hero-eyebrow text-red">ERROR 404</span>
        <h1 class="hero-title">PAGE NOT <span class="text-red">FOUND</span></h1>
        <p class="hero-description">
            The page or karate dojo resource you are looking for does not exist or has been relocated.
        </p>
        <div class="hero-cta-group">
            <a href="<?= BASE_PATH ?>/" class="btn-primary-custom">
                &larr; Return to Home
            </a>
            <a href="<?= BASE_PATH ?>/contact" class="btn-outline-custom">
                Contact Academy
            </a>
        </div>
    </div>
</section>
<?php
require_once APP_ROOT . '/app/views/layouts/public-footer.php';
