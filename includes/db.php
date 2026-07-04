<?php
/**
 * RISER Cap Store — Database Connection & Site Config
 *
 * Credentials live in includes/config.php, which is NOT committed to git
 * (see .gitignore). On a fresh checkout, copy config.sample.php to
 * config.php and fill in your real database details. If config.php
 * doesn't exist yet, the defines() below fall back to local defaults so
 * the site still runs out of the box on a local XAMPP/MAMP setup.
 */

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

defined('DB_HOST')         || define('DB_HOST',         'localhost');
defined('DB_NAME')         || define('DB_NAME',         'riser_store');
defined('DB_USER')         || define('DB_USER',         'root');
defined('DB_PASS')         || define('DB_PASS',         '');

defined('SITE_NAME')       || define('SITE_NAME',       'RISER');
defined('SHIPPING_FEE')    || define('SHIPPING_FEE',    200.00);
defined('CURRENCY_SYMBOL') || define('CURRENCY_SYMBOL', 'Rs. ');
defined('CUSTOM_EMBROIDERY_FEE') || define('CUSTOM_EMBROIDERY_FEE', 300.00); // PKR — Live Embroidery Customizer add-on

// Set to false before going live: hides raw DB error messages from visitors
// (a stack trace or connection string is useful info for an attacker).
defined('DEBUG_MODE') || define('DEBUG_MODE', true);

// Mirror DEBUG_MODE onto PHP's own error display so a stray notice/warning
// can't leak file paths or query fragments to a visitor in production.
// Errors are still logged either way — just not printed to the page.
ini_set('display_errors', DEBUG_MODE ? '1' : '0');
error_reporting(E_ALL);

// -----------------------------------------------------------------------
// BASE_URL — detects the URL prefix for the project folder automatically.
//
// Strategy: compare SCRIPT_FILENAME (filesystem path of the running script)
// against the project root path (dirname of this file's parent), then strip
// that many path segments from the end of SCRIPT_NAME (the URL path).
//
// Examples:
//   SCRIPT_FILENAME  = /var/www/html/riser/index.php
//   project root     = /var/www/html/riser           → script is 1 level deep → strip 1 segment from URL
//   SCRIPT_NAME      = /riser/index.php              → strip "index.php"      → BASE_URL = /riser
//
//   SCRIPT_FILENAME  = C:/xampp/htdocs/riser/admin/dashboard.php
//   project root     = C:/xampp/htdocs/riser          → script is 2 levels deep
//   SCRIPT_NAME      = /riser/admin/dashboard.php     → strip 2 segments      → BASE_URL = /riser
//
//   SCRIPT_FILENAME  = /var/www/html/index.php        (installed at web root)
//   project root     = /var/www/html                  → script is 1 level deep
//   SCRIPT_NAME      = /index.php                     → strip 1 segment        → BASE_URL = ""
// -----------------------------------------------------------------------
if (!defined('BASE_URL')) {
    // Normalise to forward slashes
    $scriptFile  = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? '');
    $scriptName  = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']     ?? '');
    $projectRoot = rtrim(str_replace('\\', '/', dirname(__DIR__)), '/'); // parent of includes/

    // How many path segments is the running script below the project root?
    // e.g. project=.../riser, file=.../riser/admin/dashboard.php → relative = admin/dashboard.php → depth = 2
    $projectRoot_lower = strtolower($projectRoot);
    $scriptFile_lower  = strtolower($scriptFile);

    if (strpos($scriptFile_lower, $projectRoot_lower) === 0) {
        $relPath = substr($scriptFile, strlen($projectRoot) + 1); // e.g. "admin/dashboard.php"
    } else {
        $relPath = basename($scriptFile); // fallback: just the filename
    }
    $depth = substr_count($relPath, '/') + 1; // number of segments to strip from URL end

    // Strip $depth segments from the right of SCRIPT_NAME
    $urlParts = explode('/', trim($scriptName, '/'));        // e.g. ['riser','admin','dashboard.php']
    $base     = array_slice($urlParts, 0, count($urlParts) - $depth); // e.g. ['riser']
    $baseUrl  = count($base) ? '/' . implode('/', $base) : '';

    define('BASE_URL', $baseUrl); // e.g. "/riser" or "" or "/mysite/riser"
}

// Baseline security headers on every request (defense in depth: clickjacking,
// MIME-sniffing, and referrer leakage protections).
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage())
            . '<br><br>Edit credentials in includes/db.php and make sure you imported database.sql');
    }
    error_log('RISER DB connection failed: ' . $e->getMessage());
    http_response_code(503);
    die('Sorry, the store is temporarily unavailable. Please try again shortly.');
}
