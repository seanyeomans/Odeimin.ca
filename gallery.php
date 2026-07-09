<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$files   = [];

function odeimin_gallery_add(array &$files, string $dir, string $status, string $category, array $allowed): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..') continue;
        if (!is_file($dir . $file)) continue;
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) continue;

        $urlPath = 'images/' . $status . '/'
                 . ($category !== '' ? rawurlencode($category) . '/' : '')
                 . rawurlencode($file);

        $files[] = [
            'src'           => $urlPath,
            'name'          => pathinfo($file, PATHINFO_FILENAME),
            'modified'      => filemtime($dir . $file),
            'available'     => $status === 'available',
            'filename'      => $file,
            'subdir'        => $status,
            'category'      => $category,
            'categoryLabel' => odeimin_category_label($category),
        ];
    }
}

foreach (['available', 'unavailable'] as $status) {
    $statusDir = IMAGES_DIR . $status . '/';

    // Legacy files sitting directly in the status folder (no category yet)
    odeimin_gallery_add($files, $statusDir, $status, '', $allowed);

    foreach (array_keys(odeimin_categories()) as $slug) {
        odeimin_gallery_add($files, $statusDir . $slug . '/', $status, $slug, $allowed);
    }
}

usort($files, fn($a, $b) => $b['modified'] - $a['modified']);
echo json_encode(array_values($files));
