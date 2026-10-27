<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/functions.php
 * Description: Core Utility & Security Helper Functions
 * ============================================================================
 */

// Prevent direct HTTP access
if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

// ----------------------------------------------------------------------------
// Base URL Detection Helper
// Ensures assets and navigation links work seamlessly across root and subfolders
// (e.g. /vms/, /vms/admin/, /vms/parent/, /vms/hospital/)
// ----------------------------------------------------------------------------
if (!defined('BASE_URL')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pos = strpos($scriptName, '/vms');
    if ($pos !== false) {
        $detectedBase = substr($scriptName, 0, $pos + 4);
    } else {
        $detectedBase = dirname($scriptName);
    }
    define('BASE_URL', rtrim($detectedBase, '/') . '/');
}

/**
 * Generates an absolute application URL relative to the project root.
 *
 * @param string $path Target relative path (e.g. 'assets/css/style.css', 'admin/dashboard.php')
 * @return string Full web accessible URL
 */
function url(string $path = ''): string
{
    return BASE_URL . ltrim($path, '/');
}

/**
 * Escapes strings for secure HTML output to prevent Cross-Site Scripting (XSS).
 *
 * @param mixed $value The string or value to escape
 * @return string Escaped HTML string
 */
function e($value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitizes generic user text input by trimming whitespace and stripping null bytes.
 *
 * @param mixed $data Raw input
 * @return string Sanitized input
 */
function sanitizeInput($data): string
{
    if (!is_string($data)) {
        return '';
    }
    // Remove null bytes and trim outer whitespace
    return trim(str_replace(chr(0), '', $data));
}

/**
 * Generates or retrieves the active CSRF token for the user's session.
 *
 * @return string 64-character hexadecimal CSRF token
 */
function getCSRFToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Outputs a hidden input field containing the active CSRF token for forms.
 *
 * @return string HTML hidden input element
 */
function csrfField(): string
{
    $token = getCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

/**
 * Verifies a submitted CSRF token using timing-attack-safe comparison.
 *
 * @param string|null $token The token sent with the request (POST)
 * @return bool True if valid, false otherwise
 */
function verifyCSRFToken(?string $token): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Safely formats a database date or datetime string for display.
 *
 * @param string|null $dateString Date or datetime string
 * @param string $format Target PHP date format (default: 'M d, Y')
 * @return string Formatted date or fallback text
 */
function formatDate(?string $dateString, string $format = 'M d, Y'): string
{
    if (empty($dateString) || $dateString === '0000-00-00' || $dateString === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    $timestamp = strtotime($dateString);
    if ($timestamp === false) {
        return 'Invalid Date';
    }
    return date($format, $timestamp);
}

/**
 * Calculates a child's age in days from their Date of Birth.
 *
 * @param string $dob Date of Birth (YYYY-MM-DD)
 * @return int Age in days
 */
function calculateAgeInDays(string $dob): int
{
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        $interval = $birthDate->diff($today);
        return $interval->invert === 1 ? 0 : (int)$interval->days;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Formats a child's age into a human-friendly string (e.g. '6 weeks', '4 months', '1 year 2 months').
 *
 * @param string $dob Date of Birth (YYYY-MM-DD)
 * @return string Human-friendly age
 */
function formatAge(string $dob): string
{
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        if ($birthDate > $today) {
            return 'Not born yet';
        }
        $diff = $birthDate->diff($today);

        if ($diff->y > 0) {
            return $diff->y . ' yr' . ($diff->y > 1 ? 's' : '') . ($diff->m > 0 ? ' ' . $diff->m . ' mo' : '');
        }
        if ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '');
        }
        $weeks = (int)floor($diff->d / 7);
        if ($weeks > 0) {
            return $weeks . ' week' . ($weeks > 1 ? 's' : '');
        }
        return $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
    } catch (Exception $e) {
        return 'Unknown';
    }
}

/**
 * Calculates the exact recommended due date for a vaccine based on child DOB and age in days.
 *
 * @param string $dob Child Date of Birth (YYYY-MM-DD)
 * @param int $recommendedAgeDays Recommended days from birth
 * @return string Due date formatted as YYYY-MM-DD
 */
function calculateVaccineDueDate(string $dob, int $recommendedAgeDays): string
{
    try {
        $date = new DateTime($dob);
        $date->modify('+' . (int)$recommendedAgeDays . ' days');
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return date('Y-m-d');
    }
}

/**
 * Determines whether a scheduled vaccine date has already passed.
 *
 * @param string $dueDate Calculated due date (YYYY-MM-DD)
 * @return bool True if overdue, false otherwise
 */
function isDateOverdue(string $dueDate): bool
{
    return strtotime($dueDate) < strtotime('today');
}
