<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/functions.php
 * Description: Core Utility, Localization & Security Helper Functions
 * ============================================================================
 */

// Prevent direct HTTP access
if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

// Ensure default Pakistan timezone is active
if (date_default_timezone_get() !== 'Asia/Karachi') {
    date_default_timezone_set('Asia/Karachi');
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
 * Renders an invisible honeypot input field for anti-bot / anti-spam protection.
 *
 * @param string $fieldName Field name for honeypot
 * @return string HTML honeypot field
 */
function honeypotField(string $fieldName = 'website_hp'): string
{
    return '<div style="position: absolute; left: -9999px; top: -9999px; opacity: 0; pointer-events: none;" aria-hidden="true">'
         . '<label for="' . e($fieldName) . '">Leave this field blank</label>'
         . '<input type="text" id="' . e($fieldName) . '" name="' . e($fieldName) . '" tabindex="-1" autocomplete="off" value="">'
         . '</div>';
}

/**
 * Verifies that the honeypot field was left blank by human visitors.
 *
 * @param string $fieldName Field name for honeypot
 * @return bool True if safe (blank), false if filled by spam bot
 */
function verifyHoneypot(string $fieldName = 'website_hp'): bool
{
    return empty($_POST[$fieldName]);
}

/**
 * Checks session-based rate limits for sensitive actions (e.g. login, contact submissions).
 *
 * @param string $key Identifier e.g. 'login_attempt_' . $ip
 * @param int $maxAttempts Maximum allowed attempts in window
 * @param int $decaySeconds Window duration in seconds
 * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
 */
function checkRateLimit(string $key, int $maxAttempts = 5, int $decaySeconds = 300): array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $now = time();
    $bucket = $_SESSION['rate_limits'][$key] ?? ['attempts' => 0, 'first_attempt' => $now];

    // Reset window if decay elapsed
    if ($now - $bucket['first_attempt'] > $decaySeconds) {
        $bucket = ['attempts' => 0, 'first_attempt' => $now];
    }

    $allowed = $bucket['attempts'] < $maxAttempts;
    $remaining = max(0, $maxAttempts - $bucket['attempts']);
    $retryAfter = max(1, ($bucket['first_attempt'] + $decaySeconds) - $now);

    return [
        'allowed'     => $allowed,
        'remaining'   => $remaining,
        'retry_after' => $retryAfter,
    ];
}

/**
 * Records an attempt for a rate-limited key.
 *
 * @param string $key Identifier
 */
function recordRateLimitAttempt(string $key): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $now = time();
    if (!isset($_SESSION['rate_limits'][$key]) || ($now - $_SESSION['rate_limits'][$key]['first_attempt'] > 300)) {
        $_SESSION['rate_limits'][$key] = ['attempts' => 1, 'first_attempt' => $now];
    } else {
        $_SESSION['rate_limits'][$key]['attempts']++;
    }
}

/**
 * Clears rate limiting attempts on successful action.
 *
 * @param string $key Identifier
 */
function clearRateLimit(string $key): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['rate_limits'][$key]);
}

/**
 * Safely formats a database date or datetime string for display (Defaults to DD-MM-YYYY for Pakistan).
 *
 * @param string|null $dateString Date or datetime string
 * @param string $format Target PHP date format (default: 'd-m-Y')
 * @return string Formatted date or fallback text
 */
function formatDate(?string $dateString, string $format = 'd-m-Y'): string
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
 * Formats a currency amount in Pakistani Rupees (PKR).
 *
 * @param float|int|string|null $amount Numeric amount
 * @param bool $useRsPrefix Whether to prefix with Rs. instead of PKR
 * @return string Formatted currency string (e.g. 'PKR 2,500' or 'Rs. 2,500')
 */
function formatPKR($amount, bool $useRsPrefix = true): string
{
    $num = (float)($amount ?? 0);
    $prefix = $useRsPrefix ? 'Rs. ' : 'PKR ';
    return $prefix . number_format($num, 0);
}

/**
 * Validates whether a given phone number follows realistic Pakistani phone formats (+92 or 03xx).
 *
 * @param string $phone
 * @return bool True if valid, false otherwise
 */
function isValidPakistaniPhone(string $phone): bool
{
    // Clean spaces, hyphens, and parentheses
    $clean = preg_replace('/[\s\-\(\)]/', '', $phone);
    // Patterns supported:
    // +923001234567 / 00923001234567 / 03001234567 (Mobile)
    // +922134567890 / 02134567890 (Landline)
    return (bool)preg_match('/^((\+92|0092|0)?(3[0-9]{2}|[2456789][0-9]))[0-9]{7,8}$/', $clean);
}

/**
 * Returns a canonical list of major Pakistani cities for dropdowns and filters.
 *
 * @return array List of cities
 */
function pakistaniCities(): array
{
    return [
        'Karachi',
        'Lahore',
        'Islamabad',
        'Rawalpindi',
        'Faisalabad',
        'Multan',
        'Peshawar',
        'Quetta',
        'Hyderabad',
        'Gujranwala',
        'Sialkot',
        'Bahawalpur',
        'Sargodha',
        'Sukkur',
        'Larkana',
        'Abbottabad',
    ];
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
