<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');

$dir = __DIR__ . '/images/';
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!is_dir($dir)) {
    echo json_encode([]);
    exit;
}

$files = [];
foreach (scandir($dir) as $file) {
    if ($file === '.' || $file === '..') continue;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) continue;
    $files[] = [
        'src'      => 'images/' . rawurlencode($file),
        'name'     => pathinfo($file, PATHINFO_FILENAME),
        'modified' => filemtime($dir . $file),
    ];
}

// Newest first
usort($files, fn($a, $b) => $b['modified'] - $a['modified']);

echo json_encode(array_values($files));
