<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: includes/flash.php
 * Description: One-time Session Flash Messaging System
 * ============================================================================
 * 
 * DESIGN RATIONALE:
 * - Stores alerts/notifications across HTTP redirects (e.g. after POST form saves).
 * - Automatically purges the message from $_SESSION once retrieved by getFlash(),
 *   preventing annoying duplicate notifications when a user reloads or refreshes.
 */

// Prevent direct HTTP access
if (!defined('VMS_APP')) {
    define('VMS_APP', true);
}

/**
 * Stores a flash notification message into the active session.
 *
 * @param string $type Message severity: 'success', 'danger', 'warning', 'info'
 * @param string $message The notification text to display
 * @return void
 */
function setFlash(string $type, string $message): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'] = [
        'type'    => in_array($type, ['success', 'danger', 'warning', 'info']) ? $type : 'info',
        'message' => $message,
    ];
}

/**
 * Checks whether a pending flash notification exists in the session.
 *
 * @return bool True if a flash message exists, false otherwise
 */
function hasFlash(): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return !empty($_SESSION['flash']);
}

/**
 * Retrieves the pending flash message and immediately clears it from the session.
 *
 * @return array|null Associative array ['type' => ..., 'message' => ...] or null
 */
function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Renders the flash message as a standard dismissible UI alert component.
 *
 * @return string HTML alert element or empty string
 */
function renderFlashAlert(): string
{
    $flash = getFlash();
    if (!$flash) {
        return '';
    }

    $typeClass = htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

    return sprintf(
        '<div class="alert alert-%s" role="alert">
            <span class="alert-text">%s</span>
            <button type="button" class="alert-close" aria-label="Close" onclick="this.parentElement.remove();">&times;</button>
        </div>',
        $typeClass,
        $message
    );
}
