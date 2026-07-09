<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function respond(bool $ok, string $message, array $extra = []): void {
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed.');
}

odeimin_require_admin();

// When the request body exceeds post_max_size, PHP silently discards both
// $_POST and $_FILES — detect that and report it as a size problem.
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 0 && empty($_FILES) && empty($_POST)) {
    respond(false, 'File too large (max ' . MAX_SIZE_MB . ' MB).');
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

// Category — must be one of the configured slugs
$category = $_POST['category'] ?? '';
if (!odeimin_is_valid_category($category)) {
    respond(false, 'Please choose a valid category.');
}

// Build a clean, unique filename
$original  = pathinfo($file['name'], PATHINFO_FILENAME);
$safe      = preg_replace('/[^a-zA-Z0-9_-]/', '_', $original);
$safe      = substr($safe, 0, 60);
$timestamp = date('Ymd_His');
$filename  = $timestamp . '_' . $safe . '.' . $ext;

$uploadDir = IMAGES_DIR . $status . '/' . $category . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Avoid silently overwriting when two uploads land in the same second
// with the same name (possible during a multi-file batch)
$dest = $uploadDir . $filename;
$n = 1;
while (file_exists($dest)) {
    $filename = $timestamp . '_' . $safe . '-' . $n . '.' . $ext;
    $dest = $uploadDir . $filename;
    $n++;
}

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    respond(false, 'Could not save file. Check server permissions.');
}

odeimin_append_audit_log('photo_uploaded', [
    'filename' => $filename,
    'subdir' => $status,
    'category' => $category,
    'mime' => $mime,
    'size' => (int)$file['size'],
]);

respond(true, 'Uploaded successfully.', [
    'file'     => 'images/' . $status . '/' . $category . '/' . $filename,
    'subdir'   => $status,
    'category' => $category,
]);
