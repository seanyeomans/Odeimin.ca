<?php
// ── Configuration ────────────────────────────────────────────────
define('UPLOAD_PASSWORD', 'CHANGE_ME');   // must match upload.php
define('IMAGES_DIR',      __DIR__ . '/images/');
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

$filename = basename($_POST['filename'] ?? '');
$from     = $_POST['from'] ?? '';
$to       = $_POST['to'] ?? '';

$valid = ['available', 'unavailable'];
if (!in_array($from, $valid, true) || !in_array($to, $valid, true)) {
    respond(false, 'Invalid status.');
}
if ($from === $to) {
    respond(false, 'Source and destination are the same.');
}

if ($filename === '' || $filename === '.' || $filename === '..') {
    respond(false, 'Invalid filename.');
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext     = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    respond(false, 'Not a valid file type.');
}

$srcPath = IMAGES_DIR . $from . '/' . $filename;
$destDir = IMAGES_DIR . $to . '/';

if (!file_exists($srcPath)) {
    respond(false, 'File not found.');
}

if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

if (!rename($srcPath, $destDir . $filename)) {
    respond(false, 'Could not move file. Check server permissions.');
}

respond(true, 'Status updated.');
