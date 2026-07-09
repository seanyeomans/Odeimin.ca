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

// Moves a photo between any (status, category) location. Toggling
// availability keeps the category; changing category keeps the status.
$filename     = basename($_POST['filename'] ?? '');
$fromStatus   = $_POST['from_status'] ?? '';
$toStatus     = $_POST['to_status'] ?? '';
$fromCategory = $_POST['from_category'] ?? '';
$toCategory   = $_POST['to_category'] ?? '';

$validStatuses = ['available', 'unavailable'];
if (!in_array($fromStatus, $validStatuses, true) || !in_array($toStatus, $validStatuses, true)) {
    respond(false, 'Invalid status.');
}

// '' = legacy file sitting directly in the status folder. Allowed as a
// source always; allowed as a destination only when the category is not
// being changed (i.e. a plain availability toggle on a legacy file).
if ($fromCategory !== '' && !odeimin_is_valid_category($fromCategory)) {
    respond(false, 'Invalid category.');
}
if ($toCategory !== '' && !odeimin_is_valid_category($toCategory)) {
    respond(false, 'Invalid category.');
}
if ($toCategory === '' && $fromCategory !== '') {
    respond(false, 'Invalid category.');
}

if ($fromStatus === $toStatus && $fromCategory === $toCategory) {
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

$srcPath = IMAGES_DIR . $fromStatus . '/' . ($fromCategory !== '' ? $fromCategory . '/' : '') . $filename;
$destDir = IMAGES_DIR . $toStatus . '/' . ($toCategory !== '' ? $toCategory . '/' : '');

if (!file_exists($srcPath)) {
    respond(false, 'File not found.');
}

if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

if (file_exists($destDir . $filename)) {
    respond(false, 'A file with this name already exists at the destination.');
}

if (!rename($srcPath, $destDir . $filename)) {
    respond(false, 'Could not move file. Check server permissions.');
}

odeimin_append_audit_log('photo_moved', [
    'filename' => $filename,
    'from_status' => $fromStatus,
    'to_status' => $toStatus,
    'from_category' => $fromCategory,
    'to_category' => $toCategory,
]);

respond(true, 'Updated.');
