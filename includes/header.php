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
  <script>
  // Apply the saved (or system) theme before CSS loads to avoid a flash
  (function () {
    var t = null;
    try { t = localStorage.getItem('odeimin-theme'); } catch (e) {}
    if (t !== 'dark' && t !== 'light') {
      t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', t);
  })();
  </script>
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= htmlspecialchars(odeimin_asset('/css/style.css'), ENT_QUOTES, 'UTF-8') ?>">
  <?php foreach ($extraCss as $href): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars(odeimin_asset($href), ENT_QUOTES, 'UTF-8') ?>">
  <?php endforeach; ?>
</head>
<body>

<header>
  <h1><a href="/"> Odeimin</a></h1>
  <div class="header-nav">
    <nav>
      <?php foreach ($navItems as $route => $label): ?>
        <?php $href = $route === '' ? '/' : '/' . $route; ?>
        <a href="<?= $href ?>" class="<?= $pageKey === $route ? 'active' : '' ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
      <?php endforeach; ?>
    </nav>
    <button type="button" id="theme-toggle" class="theme-toggle" aria-label="Switch to dark mode">
      <!-- Owl: shown in light mode (tap for night) -->
      <svg class="icon-owl" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
        <path fill="currentColor" fill-rule="evenodd" d="M7 3 L9.3 5.1 C10.1 4.6 11 4.35 12 4.35 C13 4.35 13.9 4.6 14.7 5.1 L17 3 C18.4 5 19 7.2 19 9.8 C19 15.3 16.2 19.6 12 21 C7.8 19.6 5 15.3 5 9.8 C5 7.2 5.6 5 7 3 Z M9.3 7.2 A1.7 1.7 0 1 0 9.3 10.6 A1.7 1.7 0 1 0 9.3 7.2 Z M14.7 7.2 A1.7 1.7 0 1 0 14.7 10.6 A1.7 1.7 0 1 0 14.7 7.2 Z M12 11.2 L13.2 13.2 L12 15 L10.8 13.2 Z"/>
      </svg>
      <!-- Jay: shown in dark mode (tap for day) -->
      <svg class="icon-jay" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
        <path fill="currentColor" fill-rule="evenodd" d="M2.5 10.2 L6.8 8.6 L8.2 4.2 C9.6 5.4 10.3 6.4 10.6 7.3 C13.6 6.6 16.2 8.3 16.9 11 L21.5 16.2 L17.3 16.4 C16.6 14.8 15 15.2 13.2 15.1 C10.2 15 8 13.4 7.3 11.2 Z M7.9 8.4 A0.8 0.8 0 1 0 7.9 10 A0.8 0.8 0 1 0 7.9 8.4 Z"/>
      </svg>
    </button>
  </div>
</header>

<main>
