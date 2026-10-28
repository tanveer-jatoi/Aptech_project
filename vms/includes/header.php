<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/header.php
 * Description: Master HTML5 Header, Metadata & Asset Injector
 * ============================================================================
 */

// Enforce authorized context
if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/flash.php';

// Format clean title without duplicate brand tags
$rawTitle = $pageTitle ?? 'Child Vaccination Management System';
if (stripos($rawTitle, 'VMS') === false) {
    $browserTitle = $rawTitle . ' - VMS Healthcare';
} else {
    $browserTitle = $rawTitle;
}
$metaDesc = $pageDescription ?? 'Child immunization tracking, hospital appointment booking, and national vaccination schedule management in Pakistan.';
$ogImage = url('assets/img/og-preview.svg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($browserTitle); ?></title>
    <meta name="description" content="<?= e($metaDesc); ?>">

    <!-- Proper Favicon & Site Icons -->
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/img/favicon.svg'); ?>">
    <link rel="alternate icon" href="<?= url('assets/img/favicon.svg'); ?>">

    <!-- Social Sharing Open Graph Metadata (Preview Functionality) -->
    <meta property="og:title" content="<?= e($browserTitle); ?>">
    <meta property="og:description" content="<?= e($metaDesc); ?>">
    <meta property="og:image" content="<?= e($ogImage); ?>">
    <meta property="og:type" content="website">

    <!-- Master CSS Design System & Animation Engine (Linked Once Globally) -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?= url('assets/css/animations.css'); ?>">
</head>
<body>
    <!-- Accessibility Skip Link -->
    <a href="#mainContent" class="skip-link">Skip to main content</a>

    <!-- Global Role-Aware Navigation Bar -->
    <?php require_once __DIR__ . '/navbar.php'; ?>

    <!-- Cookie Consent Banner -->
    <?php require_once __DIR__ . '/components/cookie_banner.php'; ?>

    <!-- Global Toast Notification Layer (Auto-hides flash messages) -->
    <?php require_once __DIR__ . '/components/toast.php'; ?>

    <!-- Main Application Viewport Wrapper -->
    <main class="main-content" id="mainContent">
