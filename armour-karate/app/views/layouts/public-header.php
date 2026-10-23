<?php
/**
 * Public Layout Header
 * app/views/layouts/public-header.php
 *
 * Included at the top of all public-facing pages.
 * Theme: Dark theme (background: #0A0A0A, text: #FFFFFF)
 *
 * Variables accepted:
 *   $pageTitle (string) - Custom title for <title> tag
 *   $metaDescription (string) - Custom meta description
 */

declare(strict_types=1);

$siteName = 'Armour Martial Arts';
$title = !empty($pageTitle) ? "{$pageTitle} | {$siteName}" : "{$siteName} – Gujju Karate Association";
$desc = $metaDescription ?? 'Official Academy of Gujju Karate Association. Dedicated to authentic martial arts training, discipline, and character building.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= esc($title) ?></title>
    <meta name="description" content="<?= escAttr($desc) ?>">
    <meta name="csrf-token" content="<?= escAttr(csrfToken()) ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= logoUrl() ?>">

    <!-- Google Fonts: Inter (Weights: 400, 500, 600, 700, 900) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Custom Design System CSS (Original Black/Red/White Theme) -->
    <link rel="stylesheet" href="<?= assetVersioned('css/custom.css') ?>">
</head>
<body class="theme-public">
<div class="public-page-wrapper">

    <!-- Flash Messages -->
    <?= flashRender() ?>

    <!-- Navigation Bar -->
    <?php require_once APP_ROOT . '/app/views/components/public/navbar.php'; ?>

    <!-- Main Content Slot -->
    <main class="public-main">
