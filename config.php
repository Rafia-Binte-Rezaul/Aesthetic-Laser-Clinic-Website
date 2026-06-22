<?php
// config.php — database connection helper & environment loader

// Helper function to load environment variables from a .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignore comments and empty lines
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }

        // Split by the first '='
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip surrounding quotes
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                $value = $matches[1];
            }

            // Only set if not already defined in environment
            if (getenv($key) === false) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Load environment configuration
loadEnv(__DIR__ . '/.env');

// Helper to get environment variable with fallback
if (!function_exists('config_env')) {
    function config_env(string $key, string $default = ''): string {
        $v = getenv($key);
        return ($v === false || $v === null) ? $default : $v;
    }
}

// Database configuration
$host     = config_env('DB_HOST', 'localhost');
$user     = config_env('DB_USER', 'root');
$password = config_env('DB_PASS', '');
$dbname   = config_env('DB_NAME', 'appointment_data');

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// --- EMAIL CONFIGURATION (Constants defined for backwards compatibility) ---
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', config_env('SMTP_HOST', 'sandbox.smtp.mailtrap.io'));
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', (int)config_env('SMTP_PORT', '2525'));
}
if (!defined('SMTP_USER')) {
    define('SMTP_USER', config_env('SMTP_USER', ''));
}
if (!defined('SMTP_PASS')) {
    define('SMTP_PASS', config_env('SMTP_PASS', ''));
}
if (!defined('SMTP_FROM')) {
    define('SMTP_FROM', config_env('SMTP_FROM', 'booking@skinith.com'));
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', config_env('SMTP_FROM_NAME', 'Skinith Clinic Test'));
}
?>
