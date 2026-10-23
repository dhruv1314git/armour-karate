<?php
/**
 * Public Footer Component
 * app/views/components/public/footer.php
 *
 * 4-column footer matching the original Next.js website design.
 */

declare(strict_types=1);
?>
<footer class="public-footer">
    <div class="container-fluid px-lg-4">
        <div class="row g-4 justify-content-between">
            <!-- Col 1: About & Logo -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand mb-3">
                    <img src="<?= logoUrl() ?>" alt="Armour Martial Arts" height="52">
                </div>
                <h6 class="text-white fw-bold mb-2 tracking-tight">ARMOUR MARTIAL ARTS</h6>
                <p class="section-eyebrow text-red mb-3">GUJJU KARATE ASSOCIATION</p>
                <p class="footer-text mb-4">
                    Committed to authentic martial arts training, fostering discipline, mental fortitude, and physical excellence for students of all ages.
                </p>
                <div class="footer-social-links">
                    <a href="https://facebook.com" target="_blank" rel="noopener" class="footer-social-link" aria-label="Facebook">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M9 8H6v4h3v12h5V12h3.642L18 8h-4V6.333C14 5.374 14.5 5 15.667 5H18V0h-3.889C10.667 0 9 1.667 9 4.667V8z"/></svg>
                    </a>
                    <a href="https://instagram.com" target="_blank" rel="noopener" class="footer-social-link" aria-label="Instagram">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                    </a>
                    <a href="https://youtube.com" target="_blank" rel="noopener" class="footer-social-link" aria-label="YouTube">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                    </a>
                </div>
            </div>

            <!-- Col 2: Academy Navigation -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-heading">Academy</h6>
                <a href="<?= BASE_PATH ?>/about" class="footer-link">About Academy</a>
                <a href="<?= BASE_PATH ?>/belts" class="footer-link">Belt System</a>
                <a href="<?= BASE_PATH ?>/fees" class="footer-link">Fee Structure</a>
                <a href="<?= BASE_PATH ?>/events" class="footer-link">Tournaments & Events</a>
                <a href="<?= BASE_PATH ?>/black-belts" class="footer-link">Black Belt Corner</a>
                <a href="<?= BASE_PATH ?>/gallery" class="footer-link">Photo Gallery</a>
            </div>

            <!-- Col 3: Programs & Portals -->
            <div class="col-lg-3 col-md-6 col-6">
                <h6 class="footer-heading">Programs & Access</h6>
                <a href="<?= BASE_PATH ?>/locations" class="footer-link">Dojos & Locations</a>
                <a href="<?= BASE_PATH ?>/join" class="footer-link">Join Academy (Enroll)</a>
                <a href="<?= BASE_PATH ?>/login" class="footer-link">Student Login</a>
                <a href="<?= BASE_PATH ?>/register" class="footer-link">New Student Registration</a>
                <a href="<?= BASE_PATH ?>/admin/login" class="footer-link text-muted">Admin Access</a>
            </div>

            <!-- Col 4: Contact & Inquiries -->
            <div class="col-lg-3 col-md-6">
                <h6 class="footer-heading">Get in Touch</h6>
                <p class="footer-text mb-2">Have questions about admissions, classes, or belts?</p>
                <a href="<?= BASE_PATH ?>/contact" class="btn btn-outline-custom btn-sm mb-3">
                    Contact Us
                </a>
                <div class="footer-text small">
                    <div>Head Office: Gujarat, India</div>
                    <div>Admissions & Training Inquiries Open</div>
                </div>
            </div>
        </div>

        <hr class="footer-divider">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 footer-bottom">
            <div>
                &copy; <?= date('Y') ?> Armour Martial Arts – Gujju Karate Association. All rights reserved.
            </div>
            <div>
                Traditional Karate-Do &bull; Character &bull; Sincerity &bull; Effort &bull; Etiquette &bull; Self-Control
            </div>
        </div>
    </div>
</footer>
