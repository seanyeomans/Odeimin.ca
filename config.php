<?php
// Load .env from the same directory if it exists.
// Variables already set in the server environment take precedence.
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\"'");
        // Don't overwrite values already set at the server level
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$val");
        }
    }
}

$pwd = getenv('UPLOAD_PASSWORD');
if ($pwd === false || $pwd === '') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Server configuration error: UPLOAD_PASSWORD not set.']);
    exit;
}

define('UPLOAD_PASSWORD', $pwd);
define('MAX_SIZE_MB', (int)(getenv('MAX_SIZE_MB') ?: 20));
define('IMAGES_DIR',  __DIR__ . '/images/');
