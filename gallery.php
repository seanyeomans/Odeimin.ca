<?php
header('Content-Type: application/json');
header('Cache-Control: no-store');

$base    = __DIR__ . '/images/';
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$files   = [];

foreach (['available', 'unavailable'] as $status) {
    $dir = $base . $status . '/';
    if (!is_dir($dir)) continue;
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;
        $files[] = [
            'src'       => 'images/' . $status . '/' . rawurlencode($file),
            'name'      => pathinfo($file, PATHINFO_FILENAME),
            'modified'  => filemtime($dir . $file),
            'available' => $status === 'available',
            'filename'  => $file,
            'subdir'    => $status,
        ];
    }
}

usort($files, fn($a, $b) => $b['modified'] - $a['modified']);
echo json_encode(array_values($files));
