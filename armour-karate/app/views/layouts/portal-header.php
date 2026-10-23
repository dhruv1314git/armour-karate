<?php
/**
 * Portal Layout Header (Admin & Student Dashboards)
 * app/views/layouts/portal-header.php
 *
 * Theme: Light theme with fixed dark sidebar.
 * Variables:
 *   $pageTitle (string) - Page title
 *   $portalType (string) - 'admin' | 'student'
 */

declare(strict_types=1);

$title = !empty($pageTitle) ? "{$pageTitle} | Armour Karate Portal" : "Armour Karate Portal";
$portalType = $portalType ?? (isAdmin() ? 'admin' : 'student');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= esc($title) ?></title>
    <meta name="csrf-token" content="<?= escAttr(csrfToken()) ?>">

    <link rel="icon" type="image/png" href="<?= logoUrl() ?>">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Custom Design System CSS -->
    <link rel="stylesheet" href="<?= assetVersioned('css/custom.css') ?>">
</head>
<body class="theme-portal">
<div class="portal-layout">

    <!-- Sidebar Component (Admin or Student) -->
    <?php
    if ($portalType === 'admin') {
        require_once APP_ROOT . '/app/views/components/admin/sidebar.php';
    } else {
        require_once APP_ROOT . '/app/views/components/student/sidebar.php';
    }
    ?>

    <!-- Main Portal Content Wrapper -->
    <div class="portal-main">
        <!-- Top Sticky Header -->
        <header class="portal-header">
            <button class="btn-sidebar-toggle" aria-label="Toggle Navigation Sidebar">
                <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <div class="portal-header-title">
                <?= esc($pageTitle ?? 'Dashboard') ?>
            </div>

            <div class="portal-header-right">
                <span class="badge badge-custom <?= isAdmin() ? 'badge-active' : 'badge-promoted' ?>">
                    <?= isAdmin() ? 'ADMIN' : 'STUDENT' ?>
                </span>
                <a href="<?= BASE_PATH ?>/logout" class="btn btn-sm btn-outline-danger" title="Logout">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                </a>
            </div>
        </header>

        <!-- Flash messages -->
        <div class="px-4 pt-3">
            <?= flashRender() ?>
        </div>

        <!-- Main Body Area -->
        <div class="portal-content">
