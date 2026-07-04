<?php
/**
 * RISER Cap Store — Local Config Template
 *
 * SETUP:
 *   1. Copy this file to includes/config.php  (same folder)
 *   2. Fill in your real database credentials below
 *   3. includes/config.php is already in .gitignore, so it will never be
 *      committed or pushed to GitHub — your real password stays private.
 *
 * On shared hosting (Hostinger, Namecheap, etc.) these values come from
 * your hosting control panel's "MySQL Databases" section. On Railway /
 * Render, they come from the database service's connection details.
 */

define('DB_HOST', 'localhost');        // usually 'localhost' on shared hosting
define('DB_NAME', 'riser_store');      // the database name you created
define('DB_USER', 'your_db_username'); // e.g. u123456789_riser
define('DB_PASS', 'your_db_password');

// Set to false once the site is live — hides raw error/database details
// from visitors and only writes them to the server's error log instead.
define('DEBUG_MODE', false);
