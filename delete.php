<?php
require_once __DIR__ . '/config.php';

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
$subdir   = $_POST['subdir'] ?? '';

if (!in_array($subdir, ['available', 'unavailable'], true)) {
    respond(false, 'Invalid directory.');
}

if ($filename === '' || $filename === '.' || $filename === '..') {
    respond(false, 'Invalid filename.');
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext     = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    respond(false, 'Not a deletable file type.');
}

$dir  = IMAGES_DIR . $subdir . '/';
$path = $dir . $filename;

// Confirm the resolved path is still inside the expected subdirectory
$realDir  = realpath($dir) ?: '';
$realPath = realpath($path) ?: '';
if ($realDir === '' || $realPath === '' || strpos($realPath, $realDir) !== 0) {
    respond(false, 'Invalid path.');
}

if (!file_exists($path)) {
    respond(false, 'File not found.');
}

if (!unlink($path)) {
    respond(false, 'Could not delete file. Check server permissions.');
}

respond(true, 'Deleted.');
