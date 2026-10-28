<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/navbar.php
 * Description: Dynamic Role-Aware Navigation Bar Component
 * ============================================================================
 */

if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

$isAuth  = isLoggedIn();
$role    = currentRole();
$uName   = currentUserName();
$homeUrl = $isAuth ? url($role . '/dashboard.php') : url('index.php');
?>
<nav class="navbar" role="navigation" aria-label="Main Navigation">
    <div class="container nav-container">
        <!-- Brand / Logo (Clickable, links to user role dashboard or guest home) -->
        <a href="<?= $homeUrl; ?>" class="brand" aria-label="Vaccination Management System Home">
            <span class="brand-icon" aria-hidden="true">&#10010;</span>
            <span>VMS<span style="color: var(--teal); font-weight: 500;">.health</span></span>
        </a>

        <!-- Accessible Mobile Hamburger Toggle -->
        <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="navMenu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Navigation Links Container -->
        <div class="nav-menu" id="navMenu">
            <?php if (!$isAuth): ?>
                <!-- PUBLIC GUEST NAVIGATION -->
                <a href="<?= url('index.php'); ?>" class="nav-link">Home</a>
                <a href="<?= url('about.php'); ?>" class="nav-link">About</a>
                <a href="<?= url('vaccines.php'); ?>" class="nav-link">Vaccines</a>
                <a href="<?= url('hospitals.php'); ?>" class="nav-link">Hospitals</a>
                <a href="<?= url('contact.php'); ?>" class="nav-link">Contact</a>
                <a href="<?= url('login.php'); ?>" class="btn btn-outline btn-sm" style="margin-left: 0.5rem;">Sign In</a>
                <a href="<?= url('register.php'); ?>" class="btn btn-primary btn-sm">Register</a>

            <?php elseif ($role === 'parent'): ?>
                <!-- PARENT PORTAL NAVIGATION -->
                <a href="<?= url('parent/dashboard.php'); ?>" class="nav-link">Dashboard</a>
                <a href="<?= url('parent/children.php'); ?>" class="nav-link">My Children</a>
                <a href="<?= url('parent/book.php'); ?>" class="nav-link">Book Vaccine</a>
                <a href="<?= url('parent/bookings.php'); ?>" class="nav-link">Appointments</a>
                <a href="<?= url('parent/reports.php'); ?>" class="nav-link">Reports</a>
                <a href="<?= url('parent/profile.php'); ?>" class="nav-link">Profile</a>
                <span class="nav-role-badge badge-role-parent" title="Logged in as Parent"><?= e($uName); ?> (Parent)</span>
                <a href="<?= url('logout.php'); ?>" class="btn btn-outline btn-sm">Logout</a>

            <?php elseif ($role === 'hospital'): ?>
                <!-- HOSPITAL PORTAL NAVIGATION -->
                <a href="<?= url('hospital/dashboard.php'); ?>" class="nav-link">Dashboard</a>
                <a href="<?= url('hospital/appointments.php'); ?>" class="nav-link">Appointments</a>
                <a href="<?= url('hospital/vaccine_stock.php'); ?>" class="nav-link">Vaccine Stock</a>
                <span class="nav-role-badge badge-role-hospital" title="Logged in as Hospital"><?= e($uName); ?></span>
                <a href="<?= url('logout.php'); ?>" class="btn btn-outline btn-sm">Logout</a>

            <?php elseif ($role === 'admin'): ?>
                <!-- ADMIN PORTAL NAVIGATION -->
                <a href="<?= url('admin/dashboard.php'); ?>" class="nav-link">Dashboard</a>
                <a href="<?= url('admin/requests.php'); ?>" class="nav-link">Requests</a>
                <a href="<?= url('admin/children.php'); ?>" class="nav-link">Children</a>
                <a href="<?= url('admin/hospitals.php'); ?>" class="nav-link">Hospitals</a>
                <a href="<?= url('admin/vaccines.php'); ?>" class="nav-link">Vaccines</a>
                <a href="<?= url('admin/bookings.php'); ?>" class="nav-link">Bookings</a>
                <a href="<?= url('admin/reports.php'); ?>" class="nav-link">Reports</a>
                <span class="nav-role-badge badge-role-admin" title="System Administrator">Admin</span>
                <a href="<?= url('logout.php'); ?>" class="btn btn-outline btn-sm">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
