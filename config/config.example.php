<?php
declare(strict_types=1);

// Copy this file to config.php and set the real values.
// Keep config.php OUT of public_html.

define('DB_HOST', 'sql306.infinityfree.com');
define('DB_NAME', 'if0_37863141_flatfinder');
define('DB_USER', 'if0_37863141');
define('DB_PASS', 'YOUR_DB_PASSWORD_HERE');

// Used for session-related hashing (e.g., IP hashing). Change this.
define('APP_SECRET', 'CHANGE_ME_TO_A_LONG_RANDOM_STRING');

define('APP_NAME', 'FlatFinder');

// Optional: used for bootstrapping the first admin login if the admin_users table is empty.
define('ADMIN_USERNAME', 'admin');
// Generate a hash locally with:
// php -r "echo password_hash('your-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
// Then paste it here.
define('ADMIN_PASSWORD_HASH', 'PASTE_PASSWORD_HASH_HERE');
