<?php
/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: config/db.php
 * Description: Secure Database Connection Handler using PHP Data Objects (PDO)
 * ============================================================================
 * 
 * DESIGN RATIONALE:
 * - Implements a Singleton-style PDO connection to prevent multiple redundant
 *   connections during a single HTTP request lifecycle.
 * - Enforces PDO::ATTR_EMULATE_PREPARES => false to guarantee true server-side
 *   prepared statements at the MySQL driver level, neutralizing SQL Injection.
 * - Forces PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION for reliable error catching.
 * - Never prints raw database error messages or credentials to the output buffer.
 *   Catches connection failures gracefully and logs them to the server error log.
 */

// Prevent direct script execution if requested outside the application context
if (!defined('VMS_APP')) {
    // If not included through an authorized entry point, define it for standalone DB scripts
    define('VMS_APP', true);
}

// Database Connection Configuration Constants
// Default XAMPP settings: Host 127.0.0.1 / Port 3306 / User 'root' / Blank Password
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'vms_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retrieves the shared PDO database connection instance.
 *
 * @return PDO The active PDO connection instance.
 * @throws RuntimeException If connection cannot be established.
 */
function getDBConnection(): PDO
{
    static $pdoInstance = null;

    if ($pdoInstance === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            // Throw exceptions on errors for structured try/catch handling
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Fetch rows as associative arrays by default (e.g. $row['column_name'])
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Disable emulated prepares so MySQL driver handles true prepared statements
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Ensure connection uses UTF-8 encoding
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
        ];

        try {
            $pdoInstance = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Write detailed failure reason to the Apache/PHP system log for debugging
            error_log('[VMS DB ERROR] Connection failed: ' . $e->getMessage());

            // Terminate execution with a safe, user-friendly message without leaking credentials
            http_response_code(500);
            echo '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Database Connection Error - VMS</title>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem; display: flex; justify-content: center; align-items: center; min-height: 80vh; margin: 0; }
                    .error-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08); padding: 2rem; max-width: 520px; width: 100%; text-align: center; }
                    .error-badge { display: inline-block; background: #fee2e2; color: #dc2626; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.85rem; font-weight: 600; margin-bottom: 1rem; }
                    h1 { font-size: 1.5rem; margin-bottom: 0.5rem; color: #0f172a; }
                    p { color: #64748b; line-height: 1.6; font-size: 0.95rem; margin-bottom: 1.25rem; }
                    .instruction { background: #f1f5f9; padding: 0.75rem 1rem; border-radius: 8px; font-family: monospace; font-size: 0.85rem; color: #334155; text-align: left; }
                </style>
            </head>
            <body>
                <div class="error-card">
                    <span class="error-badge">Database Service Unavailable</span>
                    <h1>Unable to Connect to Database</h1>
                    <p>The application could not connect to MySQL. Please verify that the MySQL service is started in your XAMPP Control Panel and that <code>vms_db</code> is imported.</p>
                    <div class="instruction">
                        1. Open XAMPP Control Panel<br>
                        2. Click "Start" on MySQL<br>
                        3. Import <code>vms/database/vms.sql</code> in phpMyAdmin
                    </div>
                </div>
            </body>
            </html>';
            exit;
        }
    }

    return $pdoInstance;
}

// Create a globally accessible $pdo instance for procedural convenience in views and scripts
$pdo = getDBConnection();
