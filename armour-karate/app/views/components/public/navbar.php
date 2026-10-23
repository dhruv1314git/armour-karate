<?php
/**
 * Public Navbar Component
 * app/views/components/public/navbar.php
 *
 * Original navigation order:
 * Home · About · Belts · Fees · Events · Black Belts · Gallery · Locations · Contact
 * CTAs: Student Login (ghost) · Join Academy (primary red)
 */

declare(strict_types=1);
?>
<nav class="navbar navbar-expand-lg public-navbar fixed-top">
    <div class="container-fluid px-lg-4">
        <!-- Brand Logo & Name -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_PATH ?>/">
            <img src="<?= logoUrl() ?>" alt="Armour Martial Arts Logo" height="42" class="d-inline-block">
            <span class="d-none d-sm-inline-block fw-bold text-white tracking-tight" style="font-size: 0.95rem; letter-spacing: -0.02em;">
                ARMOUR <span class="text-red">MARTIAL ARTS</span>
            </span>
        </a>

        <!-- Mobile Toggler -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPublicNav" aria-controls="navbarPublicNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse" id="navbarPublicNav">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link <?= activeClass('') ?>" href="<?= BASE_PATH ?>/">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= activeClass('about') ?>" href="<?= BASE_PATH ?>/about">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= activeClass('belts') ?>" href="<?= BASE_PATH ?>/belts">Belts</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= activeClass('fees') ?>" href="<?= BASE_PATH ?>/fees">Fees</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= activeClass('events') ?>" href="<?= BASE_PATH ?>/events">Events</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= activeClass('black-belts') ?>" href="<?= BASE_PATH ?>/black-belts">Black Belts</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= activeClass('gallery') ?>" href="<?= BASE_PATH ?>/gallery">Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= activeClass('locations') ?>" href="<?= BASE_PATH ?>/locations">Locations</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= activeClass('contact') ?>" href="<?= BASE_PATH ?>/contact">Contact</a>
                </li>
            </ul>

            <!-- Right CTAs -->
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="<?= BASE_PATH ?>/admin/dashboard" class="btn btn-sm btn-nav-login">
                            Admin Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_PATH ?>/student/dashboard" class="btn btn-sm btn-nav-login">
                            Student Dashboard
                        </a>
                    <?php endif; ?>
                    <a href="<?= BASE_PATH ?>/logout" class="btn btn-sm btn-outline-danger">
                        Logout
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_PATH ?>/login" class="btn btn-nav-login">
                        Student Login
                    </a>
                    <a href="<?= BASE_PATH ?>/join" class="btn btn-nav-join">
                        Join Academy
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
