<?php
require_once __DIR__ . '/config.php';

function odeimin_client_ip(): string {
    $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($forwarded !== '') {
        $parts = explode(',', $forwarded);
        $first = trim($parts[0]);
        if ($first !== '') {
            return $first;
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function odeimin_logs_dir(): string {
    $dir = dirname(AUDIT_LOG_FILE);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function odeimin_append_audit_log(string $event, array $context = []): void {
    $entry = array_merge([
        'ts' => gmdate('c'),
        'event' => $event,
        'ip' => odeimin_client_ip(),
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ], $context);

    odeimin_logs_dir();
    @file_put_contents(AUDIT_LOG_FILE, json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function odeimin_login_rate_dir(): string {
    $dir = __DIR__ . '/logs/ratelimit';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function odeimin_login_rate_file(string $ip): string {
    return odeimin_login_rate_dir() . '/' . hash('sha256', $ip) . '.json';
}

function odeimin_login_rate_state(string $ip): array {
    $file = odeimin_login_rate_file($ip);
    if (!file_exists($file)) {
        return ['first' => 0, 'attempts' => 0, 'blocked_until' => 0];
    }

    $json = @file_get_contents($file);
    if ($json === false || $json === '') {
        return ['first' => 0, 'attempts' => 0, 'blocked_until' => 0];
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return ['first' => 0, 'attempts' => 0, 'blocked_until' => 0];
    }

    return [
        'first' => (int)($data['first'] ?? 0),
        'attempts' => (int)($data['attempts'] ?? 0),
        'blocked_until' => (int)($data['blocked_until'] ?? 0),
    ];
}

function odeimin_write_login_rate_state(string $ip, array $state): void {
    $file = odeimin_login_rate_file($ip);
    @file_put_contents($file, json_encode($state), LOCK_EX);
}

function odeimin_login_rate_status(string $ip): array {
    $state = odeimin_login_rate_state($ip);
    $now = time();

    if ($state['blocked_until'] > $now) {
        return [
            'blocked' => true,
            'retry_after' => $state['blocked_until'] - $now,
            'attempts_remaining' => 0,
        ];
    }

    if ($state['first'] <= 0 || ($now - $state['first']) > ADMIN_LOGIN_WINDOW) {
        $state = ['first' => 0, 'attempts' => 0, 'blocked_until' => 0];
        odeimin_write_login_rate_state($ip, $state);
    }

    return [
        'blocked' => false,
        'retry_after' => 0,
        'attempts_remaining' => max(ADMIN_LOGIN_MAX_ATTEMPTS - $state['attempts'], 0),
    ];
}

function odeimin_record_failed_login(string $ip): array {
    $state = odeimin_login_rate_state($ip);
    $now = time();

    if ($state['first'] <= 0 || ($now - $state['first']) > ADMIN_LOGIN_WINDOW) {
        $state['first'] = $now;
        $state['attempts'] = 0;
        $state['blocked_until'] = 0;
    }

    $state['attempts']++;

    if ($state['attempts'] >= ADMIN_LOGIN_MAX_ATTEMPTS) {
        $state['blocked_until'] = $now + ADMIN_LOGIN_LOCKOUT;
    }

    odeimin_write_login_rate_state($ip, $state);

    $retryAfter = max($state['blocked_until'] - $now, 0);
    return [
        'blocked' => $state['blocked_until'] > $now,
        'retry_after' => $retryAfter,
        'attempts_remaining' => max(ADMIN_LOGIN_MAX_ATTEMPTS - $state['attempts'], 0),
    ];
}

function odeimin_clear_login_rate(string $ip): void {
    $file = odeimin_login_rate_file($ip);
    if (file_exists($file)) {
        @unlink($file);
    }
}

function odeimin_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return (($_SERVER['SERVER_PORT'] ?? '') === '443');
}

function odeimin_start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = odeimin_is_https();

    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    session_name('odeimin_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}

function odeimin_mark_admin_authenticated(): void {
    odeimin_start_session();
    session_regenerate_id(true);
    $_SESSION['odeimin_admin_ok'] = true;
    $_SESSION['odeimin_last_seen'] = time();
}

function odeimin_clear_admin_session(): void {
    odeimin_start_session();
    $_SESSION = [];
    session_destroy();
}

function odeimin_is_admin_authenticated(): bool {
    odeimin_start_session();

    $isAuthed = !empty($_SESSION['odeimin_admin_ok']);
    if (!$isAuthed) {
        return false;
    }

    $lastSeen = (int)($_SESSION['odeimin_last_seen'] ?? 0);
    $now = time();

    if ($lastSeen <= 0 || ($now - $lastSeen) > ADMIN_SESSION_TTL) {
        odeimin_clear_admin_session();
        return false;
    }

    $_SESSION['odeimin_last_seen'] = $now;
    return true;
}

function odeimin_require_admin(): void {
    if (!is_admin_auth_configured()) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'message' => 'Admin authentication is not configured.']);
        exit;
    }

    if (!odeimin_is_admin_authenticated()) {
        odeimin_append_audit_log('admin_auth_required_failed');
        http_response_code(401);
        echo json_encode(['ok' => false, 'message' => 'Not authenticated. Please sign in.']);
        exit;
    }
}
