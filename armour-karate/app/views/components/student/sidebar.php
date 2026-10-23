<?php
/**
 * Student Sidebar Component
 * app/views/components/student/sidebar.php
 *
 * Dark sidebar for student portal
 * Navigation links:
 * 1. Dashboard
 * 2. Belt Journey
 * 3. Fees
 * 4. Exams
 * 5. Events
 * 6. Class Info
 * 7. Profile
 * 8. Account
 * 9. Logout
 */

declare(strict_types=1);

$studentName = $_SESSION['student_name'] ?? 'Karate Student';
$studentId   = $_SESSION['student_id']   ?? 'KAR-PENDING';
$beltName    = $_SESSION['current_belt_name'] ?? 'White Belt';
$beltColor   = $_SESSION['current_belt_color'] ?? '#FFFFFF';
$photoUrl    = $_SESSION['student_photo_url'] ?? null;
?>
<!-- Mobile overlay -->
<div class="sidebar-overlay"></div>

<aside class="app-sidebar">
    <!-- Brand -->
    <a href="<?= BASE_PATH ?>/student/dashboard" class="sidebar-brand">
        <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" height="36">
        <div>
            <div class="sidebar-brand-name">Armour Karate</div>
            <div class="sidebar-brand-sub">Student Portal</div>
        </div>
    </a>

    <!-- Navigation Section -->
    <div class="sidebar-section">
        <div class="sidebar-section-label">My Training</div>

        <a href="<?= BASE_PATH ?>/student/dashboard" class="sidebar-link <?= activeClass('student/dashboard') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_PATH ?>/student/belt-journey" class="sidebar-link <?= activeClass('student/belt-journey') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            <span>Belt Journey</span>
        </a>

        <a href="<?= BASE_PATH ?>/student/fees" class="sidebar-link <?= activeClass('student/fees') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Fees</span>
        </a>

        <a href="<?= BASE_PATH ?>/student/exams" class="sidebar-link <?= activeClass('student/exams') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Exams</span>
        </a>

        <div class="sidebar-section-label mt-3">Academy & Schedule</div>

        <a href="<?= BASE_PATH ?>/student/events" class="sidebar-link <?= activeClass('student/events') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Events</span>
        </a>

        <a href="<?= BASE_PATH ?>/student/class-info" class="sidebar-link <?= activeClass('student/class-info') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Class Info</span>
        </a>

        <div class="sidebar-section-label mt-3">Account</div>

        <a href="<?= BASE_PATH ?>/student/profile" class="sidebar-link <?= activeClass('student/profile') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Profile</span>
        </a>

        <a href="<?= BASE_PATH ?>/student/account" class="sidebar-link <?= activeClass('student/account') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            <span>Account</span>
        </a>
    </div>

    <!-- Sidebar Footer / Student Identity -->
    <div class="sidebar-footer">
        <div class="sidebar-user mb-2">
            <div class="sidebar-user-avatar overflow-hidden">
                <?php if (!empty($photoUrl)): ?>
                    <img src="<?= escAttr(uploadUrl($photoUrl)) ?>" alt="<?= escAttr($studentName) ?>" class="w-100 h-100 object-fit-cover rounded-circle">
                <?php else: ?>
                    <?= strtoupper(substr($studentName, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div style="min-width: 0;">
                <div class="sidebar-user-name text-truncate"><?= esc($studentName) ?></div>
                <div class="sidebar-user-id d-flex align-items-center gap-1">
                    <span class="belt-dot" style="background: <?= escAttr($beltColor) ?>; width: 8px; height: 8px;"></span>
                    <span class="text-truncate"><?= esc($studentId) ?></span>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= BASE_PATH ?>/" class="btn btn-sm btn-ghost-dark py-1 px-2" style="font-size: 0.75rem;" target="_blank">
                Website &nearr;
            </a>
            <form action="<?= BASE_PATH ?>/logout" method="POST" class="d-inline m-0">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 0.75rem;">
                    Logout
                </button>
            </form>
        </div>
    </div>
</aside>
