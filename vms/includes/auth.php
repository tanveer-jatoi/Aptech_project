<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/auth.php
 * Description: Session-Based Authentication & Role-Based Access Control (RBAC)
 * ============================================================================
 * 
 * DESIGN RATIONALE:
 * - Configures HTTP-only cookies to prevent JavaScript token theft (XSS mitigation).
 * - Enforces session_regenerate_id(true) on successful login to prevent Session Fixation.
 * - Centralizes role authorization (admin / parent / hospital) with guard functions.
 */

// Prevent direct HTTP access
if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/flash.php';

// Initialize session with secure cookie flags if not already running
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,          // Expire when browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,       // Inaccessible to JavaScript
        'samesite' => 'Lax'       // Protection against CSRF attacks
    ]);
    session_start();
}

/**
 * Checks if a user is currently authenticated in the active session.
 *
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Returns the currently authenticated user's ID.
 *
 * @return int|null User ID or null if unauthenticated
 */
function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Returns the active user's system role ('admin', 'parent', 'hospital').
 *
 * @return string|null Role string or null
 */
function currentRole(): ?string
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Returns the active user's display name.
 *
 * @return string Display name or fallback
 */
function currentUserName(): string
{
    return $_SESSION['user_name'] ?? 'User';
}

/**
 * Returns the parent_id or hospital_id linked to this user account.
 *
 * @return int|null Linked profile ID or null
 */
function currentProfileId(): ?int
{
    return isset($_SESSION['profile_id']) ? (int)$_SESSION['profile_id'] : null;
}

/**
 * Initializes a secure session for a verified user and guards against session fixation.
 *
 * @param array $user Associative array of user record from users table
 * @param int|null $profileId Linked parent_id or hospital_id
 * @return void
 */
function loginUser(array $user, ?int $profileId = null): void
{
    // Regenerate session ID to mitigate session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['profile_id'] = $profileId !== null ? (int)$profileId : null;

    // Refresh CSRF token for the new authenticated session
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Terminates user session, clears session variables, and expires session cookie.
 *
 * @return void
 */
function logoutUser(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Access Guard: Demands that the visitor is logged in.
 * If not authenticated, redirects them to the login screen with a friendly message.
 *
 * @param string|null $customLoginUrl Optional custom redirect destination
 * @return void
 */
function requireAuth(?string $customLoginUrl = null): void
{
    if (!isLoggedIn()) {
        setFlash('warning', 'Please sign in to access this page.');
        $redirect = $customLoginUrl ?? url('login.php');
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Role Guard: Demands that the authenticated user matches one of the allowed roles.
 *
 * @param array|string $allowedRoles Single role or array of authorized roles
 * @return void
 */
function requireRole($allowedRoles): void
{
    requireAuth();

    $roles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    $userRole = currentRole();

    if (!in_array($userRole, $roles, true)) {
        setFlash('danger', 'Unauthorized access. You do not have permission for that area.');
        redirectToRoleDashboard($userRole);
        exit;
    }
}

/**
 * Redirects the user to their designated portal dashboard based on their role.
 *
 * @param string|null $role Role string ('admin', 'parent', 'hospital')
 * @return void
 */
function redirectToRoleDashboard(?string $role = null): void
{
    $targetRole = $role ?? currentRole();

    switch ($targetRole) {
        case 'admin':
            header('Location: ' . url('admin/dashboard.php'));
            break;
        case 'parent':
            header('Location: ' . url('parent/dashboard.php'));
            break;
        case 'hospital':
            header('Location: ' . url('hospital/dashboard.php'));
            break;
        default:
            header('Location: ' . url('index.php'));
            break;
    }
    exit;
}
