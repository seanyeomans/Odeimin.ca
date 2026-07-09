<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function respond(bool $ok, string $message): void {
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed.');
}

odeimin_require_admin();

$filename = basename($_POST['filename'] ?? '');
$subdir   = $_POST['subdir'] ?? '';
$category = $_POST['category'] ?? '';

if (!in_array($subdir, ['available', 'unavailable'], true)) {
    respond(false, 'Invalid directory.');
}

// '' = legacy file sitting directly in the status folder
if ($category !== '' && !odeimin_is_valid_category($category)) {
    respond(false, 'Invalid category.');
}

if ($filename === '' || $filename === '.' || $filename === '..') {
    respond(false, 'Invalid filename.');
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext     = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed)) {
    respond(false, 'Not a deletable file type.');
}

$dir  = IMAGES_DIR . $subdir . '/' . ($category !== '' ? $category . '/' : '');
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

odeimin_append_audit_log('photo_deleted', [
    'filename' => $filename,
    'subdir' => $subdir,
    'category' => $category,
]);

respond(true, 'Deleted.');
