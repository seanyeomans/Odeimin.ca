<?php
// ── Configuration ────────────────────────────────────────────────
define('UPLOAD_PASSWORD', 'CHANGE_ME');   // must match upload.php
define('UPLOAD_DIR',      __DIR__ . '/images/');
// ─────────────────────────────────────────────────────────────────

header('Content-Type: application/json');
header('Cache-Control: no-store');

function respond(bool $ok, string $message): void {
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed.');
}

$password = trim($_POST['password'] ?? '');
if (!hash_equals(UPLOAD_PASSWORD, $password)) {
    respond(false, 'Incorrect password.');
}

$filename = $_POST['filename'] ?? '';

// Strip any path components — only allow a bare filename
$filename = basename($filename);

if ($filename === '' || $filename === '.' || $filename === '..') {
    respond(false, 'Invalid filename.');
}

$path = UPLOAD_DIR . $filename;

// Confirm the resolved path is still inside UPLOAD_DIR
if (strpos(realpath($path) ?: '', realpath(UPLOAD_DIR)) !== 0) {
    respond(false, 'Invalid path.');
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext     = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    respond(false, 'Not a deletable file type.');
}

if (!file_exists($path)) {
    respond(false, 'File not found.');
}

if (!unlink($path)) {
    respond(false, 'Could not delete file. Check server permissions.');
}

respond(true, 'Deleted.');
