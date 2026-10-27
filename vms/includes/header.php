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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($pageTitle ?? 'Child Vaccination Management System'); ?> | VMS</title>
    <meta name="description" content="<?= e($pageDescription ?? 'Child immunization tracking, hospital appointment booking, and vaccination schedule management.'); ?>">

    <!-- Master CSS Design System & Animation Engine (Linked Once Globally) -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css'); ?>">
    <link rel="stylesheet" href="<?= url('assets/css/animations.css'); ?>">
</head>
<body>
    <!-- Global Role-Aware Navigation Bar -->
    <?php require_once __DIR__ . '/navbar.php'; ?>

    <!-- Global Toast Notification Layer (Auto-hides flash messages) -->
    <?php require_once __DIR__ . '/components/toast.php'; ?>

    <!-- Main Application Viewport Wrapper -->
    <main class="main-content" id="mainContent">
