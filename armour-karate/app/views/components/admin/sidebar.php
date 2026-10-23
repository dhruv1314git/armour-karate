<?php
/**
 * Admin Sidebar Component
 * app/views/components/admin/sidebar.php
 *
 * Dark sidebar (width: 260px, background: #0F0F10)
 * All 12 admin navigation links + unread badge + admin profile snippet
 */

declare(strict_types=1);

$adminName = $_SESSION['admin_name'] ?? 'Academy Administrator';
$adminEmail = $_SESSION['admin_email'] ?? 'admin@armourkarate.com';
?>
<!-- Mobile overlay -->
<div class="sidebar-overlay"></div>

<aside class="app-sidebar">
    <!-- Brand -->
    <a href="<?= BASE_PATH ?>/admin/dashboard" class="sidebar-brand">
        <img src="<?= logoUrl() ?>" alt="Armour Karate Logo" height="36">
        <div>
            <div class="sidebar-brand-name">Armour Karate</div>
            <div class="sidebar-brand-sub">Admin Portal</div>
        </div>
    </a>

    <!-- Navigation Section -->
    <div class="sidebar-section">
        <div class="sidebar-section-label">Management</div>

        <a href="<?= BASE_PATH ?>/admin/dashboard" class="sidebar-link <?= activeClass('admin/dashboard') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_PATH ?>/admin/students" class="sidebar-link <?= activeClass('admin/students') || activeClass('admin/student') || activeClass('admin/students/view') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span>Students Roster</span>
        </a>

        <a href="<?= BASE_PATH ?>/admin/fees" class="sidebar-link <?= activeClass('admin/fees') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Fee Records</span>
        </a>

        <a href="<?= BASE_PATH ?>/admin/belts" class="sidebar-link <?= activeClass('admin/belts') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            <span>Belts & Exams</span>
        </a>

        <a href="<?= BASE_PATH ?>/admin/exams" class="sidebar-link <?= activeClass('admin/exams') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Examinations</span>
        </a>

        <div class="sidebar-section-label mt-3">Content & Academy</div>

        <a href="<?= BASE_PATH ?>/admin/events" class="sidebar-link <?= activeClass('admin/events') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Tournaments & Events</span>
        </a>

        <a href="<?= BASE_PATH ?>/admin/gallery" class="sidebar-link <?= activeClass('admin/gallery') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span>Gallery</span>
        </a>

        <a href="<?= BASE_PATH ?>/admin/black-belt" class="sidebar-link <?= activeClass('admin/black-belt') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            <span>Black Belt Corner</span>
        </a>

        <a href="<?= BASE_PATH ?>/admin/locations" class="sidebar-link <?= activeClass('admin/locations') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Locations & Dojos</span>
        </a>

        <a href="<?= BASE_PATH ?>/admin/contact" class="sidebar-link <?= activeClass('admin/contact') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span>Contact Enquiries</span>
        </a>

        <div class="sidebar-section-label mt-3">System</div>

        <a href="<?= BASE_PATH ?>/admin/audit-logs" class="sidebar-link <?= activeClass('admin/audit-logs') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Audit Logs</span>
        </a>

        <a href="<?= BASE_PATH ?>/admin/settings" class="sidebar-link <?= activeClass('admin/settings') ?>">
            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Settings</span>
        </a>
    </div>

    <!-- Sidebar Footer / Admin Identity -->
    <div class="sidebar-footer">
        <div class="sidebar-user mb-2">
            <div class="sidebar-user-avatar">
                <?= strtoupper(substr($adminName, 0, 1)) ?>
            </div>
            <div style="min-width: 0;">
                <div class="sidebar-user-name"><?= esc($adminName) ?></div>
                <div class="sidebar-user-id"><?= esc($adminEmail) ?></div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <a href="<?= BASE_PATH ?>/" class="btn btn-sm btn-ghost-dark py-1 px-2" style="font-size: 0.75rem;" target="_blank">
                View Site &nearr;
            </a>
            <a href="<?= BASE_PATH ?>/logout" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 0.75rem;">
                Logout
            </a>
        </div>
    </div>
</aside>
