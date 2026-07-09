<?php
require_once __DIR__ . '/auth.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$route = trim($path, '/');

if ($route === 'index.php') {
    $route = '';
}

$routes = [
    '' => [
        'title' => 'Odeimin - Art by Noelle',
        'page' => 'home',
        'css' => ['/css/index.css'],
        'js' => ['/js/index.js'],
    ],
    'about' => [
        'title' => 'About - Odeimin',
        'page' => 'about',
        'css' => ['/css/about.css'],
        'js' => ['/js/about.js'],
    ],
    'contact' => [
        'title' => 'Contact - Odeimin',
        'page' => 'contact',
        'css' => ['/css/contact.css'],
        'js' => ['/js/contact.js'],
    ],
    'upload' => [
        'title' => 'Manage - Odeimin',
        'page' => 'upload',
        'css' => ['/css/upload.css'],
        'js' => ['/js/upload.js'],
    ],
];

if (!array_key_exists($route, $routes)) {
    http_response_code(404);
    $pageKey = '';
    $pageTitle = 'Not Found - Odeimin';
    $extraCss = ['/css/about.css'];
    $extraJs = [];
    $contentFile = __DIR__ . '/pages/not-found.php';
} else {
    $config = $routes[$route];
    $pageKey = $route;
    $pageTitle = $config['title'];
    $extraCss = $config['css'];
    $extraJs = $config['js'];
    $contentFile = __DIR__ . '/pages/' . $config['page'] . '.php';
}

// Must run before any output — session_start() cannot set cookies/headers
// once the page below has started echoing HTML.
$isAdminAuthed = ($route === 'upload') ? odeimin_is_admin_authenticated() : false;

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/content.php';
require __DIR__ . '/includes/footer.php';
