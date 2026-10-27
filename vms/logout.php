<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: logout.php
 * Description: Secure Session Termination & Logout Handler
 * ============================================================================
 */

define('VMS_APP', true);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/flash.php';

// Terminate user session and remove session cookies
logoutUser();

// Start a fresh, clean session exclusively to carry the logout flash notification
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
setFlash('info', 'You have been safely signed out. See you next time!');

// Redirect to login portal
header('Location: ' . url('login.php'));
exit;
