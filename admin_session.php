<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function respond(bool $ok, string $message, array $extra = []): void {
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
    exit;
}

odeimin_start_session();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    respond(true, 'Session status.', [
        'authenticated' => odeimin_is_admin_authenticated(),
    ]);
}

if ($method !== 'POST') {
    respond(false, 'Method not allowed.');
}

$action = $_POST['action'] ?? '';

if ($action === 'logout') {
    odeimin_append_audit_log('admin_logout');
    odeimin_clear_admin_session();
    respond(true, 'Signed out.', ['authenticated' => false]);
}

if ($action !== 'login') {
    respond(false, 'Invalid action.');
}

if (!is_admin_auth_configured()) {
    http_response_code(500);
    respond(false, 'Admin authentication is not configured.');
}

$password = trim($_POST['password'] ?? '');
if ($password === '') {
    respond(false, 'Password is required.');
}

$ip = odeimin_client_ip();
$rate = odeimin_login_rate_status($ip);
if ($rate['blocked']) {
    http_response_code(429);
    respond(false, 'Too many failed attempts. Try again later.', [
        'retryAfter' => $rate['retry_after'],
    ]);
}

if (!verify_admin_password($password)) {
    $updated = odeimin_record_failed_login($ip);
    odeimin_append_audit_log('admin_login_failed', [
        'attemptsRemaining' => $updated['attempts_remaining'],
        'retryAfter' => $updated['retry_after'],
    ]);

    if ($updated['blocked']) {
        http_response_code(429);
        respond(false, 'Too many failed attempts. Try again later.', [
            'retryAfter' => $updated['retry_after'],
        ]);
    }

    http_response_code(401);
    respond(false, 'Incorrect password.');
}

odeimin_clear_login_rate($ip);
odeimin_mark_admin_authenticated();
odeimin_append_audit_log('admin_login_success');
respond(true, 'Signed in.', ['authenticated' => true]);
