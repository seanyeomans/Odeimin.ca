<?php
// ── Configuration ────────────────────────────────────────────────
define('UPLOAD_PASSWORD', 'CHANGE_ME');   // <-- set your password here
define('MAX_SIZE_MB',     20);
define('IMAGES_DIR',      __DIR__ . '/images/');
// ─────────────────────────────────────────────────────────────────

header('Content-Type: application/json');
header('Cache-Control: no-store');

function respond(bool $ok, string $message, array $extra = []): void {
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed.');
}

// Auth
$password = trim($_POST['password'] ?? '');
if (!hash_equals(UPLOAD_PASSWORD, $password)) {
    respond(false, 'Incorrect password.');
}

// File present?
if (empty($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
    respond(false, 'No file received.');
}

$file  = $_FILES['photo'];
$error = $file['error'];

if ($error !== UPLOAD_ERR_OK) {
    $msgs = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form size limit.',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by extension.',
    ];
    respond(false, $msgs[$error] ?? 'Upload error.');
}

// Size
$maxBytes = MAX_SIZE_MB * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    respond(false, 'File too large (max ' . MAX_SIZE_MB . ' MB).');
}

// Type — verify via GD, not just extension
$info = @getimagesize($file['tmp_name']);
if (!$info) {
    respond(false, 'File does not appear to be an image.');
}

$mimeToExt = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

$mime = $info['mime'];
if (!isset($mimeToExt[$mime])) {
    respond(false, 'Unsupported image type. Use JPEG, PNG, GIF, or WebP.');
}

$ext = $mimeToExt[$mime];

// Availability status
$status = $_POST['status'] ?? 'available';
if (!in_array($status, ['available', 'unavailable'], true)) {
    $status = 'available';
}

// Build a clean, unique filename
$original  = pathinfo($file['name'], PATHINFO_FILENAME);
$safe      = preg_replace('/[^a-zA-Z0-9_-]/', '_', $original);
$safe      = substr($safe, 0, 60);
$timestamp = date('Ymd_His');
$filename  = $timestamp . '_' . $safe . '.' . $ext;

$uploadDir = IMAGES_DIR . $status . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$dest = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    respond(false, 'Could not save file. Check server permissions.');
}

respond(true, 'Uploaded successfully.', [
    'file'   => 'images/' . $status . '/' . $filename,
    'subdir' => $status,
]);
