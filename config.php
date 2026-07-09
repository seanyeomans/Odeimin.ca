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

$adminHash = getenv('ADMIN_PASSWORD_HASH');
$legacyPwd = getenv('UPLOAD_PASSWORD');

define('ADMIN_PASSWORD_HASH', $adminHash !== false ? $adminHash : '');
define('LEGACY_UPLOAD_PASSWORD', $legacyPwd !== false ? $legacyPwd : '');
define('MAX_SIZE_MB', (int)(getenv('MAX_SIZE_MB') ?: 20));
define('ADMIN_SESSION_TTL', (int)(getenv('ADMIN_SESSION_TTL') ?: 900));
define('ADMIN_LOGIN_WINDOW', (int)(getenv('ADMIN_LOGIN_WINDOW') ?: 900));
define('ADMIN_LOGIN_MAX_ATTEMPTS', (int)(getenv('ADMIN_LOGIN_MAX_ATTEMPTS') ?: 5));
define('ADMIN_LOGIN_LOCKOUT', (int)(getenv('ADMIN_LOGIN_LOCKOUT') ?: 900));
define('AUDIT_LOG_FILE', __DIR__ . '/logs/admin_audit.log');
define('IMAGES_DIR',  __DIR__ . '/images/');

// Photo categories: folder slug => display label.
// Each category is a subfolder inside images/available/ and images/unavailable/.
// Slugs must be filesystem/URL-safe (lowercase letters, digits, hyphens).
define('ODEIMIN_CATEGORIES', [
    'beadwork'    => 'Beadwork',
    'painting'    => 'Painting',
    'mixed-media' => 'Mixed media',
]);

function odeimin_categories(): array {
    return ODEIMIN_CATEGORIES;
}

function odeimin_is_valid_category(string $slug): bool {
    return array_key_exists($slug, ODEIMIN_CATEGORIES);
}

function odeimin_category_label(string $slug): string {
    return ODEIMIN_CATEGORIES[$slug] ?? 'Uncategorized';
}

function is_admin_auth_configured(): bool {
    return ADMIN_PASSWORD_HASH !== '' || LEGACY_UPLOAD_PASSWORD !== '';
}

function verify_admin_password(string $password): bool {
    if (ADMIN_PASSWORD_HASH !== '') {
        return password_verify($password, ADMIN_PASSWORD_HASH);
    }

    if (LEGACY_UPLOAD_PASSWORD !== '') {
        return hash_equals(LEGACY_UPLOAD_PASSWORD, $password);
    }

    return false;
}
