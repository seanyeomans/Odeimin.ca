<?php
$navItems = [
    '' => 'Gallery',
    'about' => 'About',
    'contact' => 'Contact',
    'upload' => 'Manage',
];

// Appends the file's mtime as a version so browsers re-fetch CSS/JS
// whenever the file changes, instead of serving a stale cached copy.
function odeimin_asset(string $path): string {
    $file = dirname(__DIR__) . $path;
    $version = @filemtime($file) ?: 1;
    return $path . '?v=' . $version;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(odeimin_asset('/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
  <?php foreach ($extraCss as $href): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars(odeimin_asset($href), ENT_QUOTES, 'UTF-8') ?>">
  <?php endforeach; ?>
</head>
<body>

<header>
  <h1><a href="/"> Odeimin</a></h1>
  <nav>
    <?php foreach ($navItems as $route => $label): ?>
      <?php $href = $route === '' ? '/' : '/' . $route; ?>
      <a href="<?= $href ?>" class="<?= $pageKey === $route ? 'active' : '' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
    <?php endforeach; ?>
  </nav>
</header>

<main>
