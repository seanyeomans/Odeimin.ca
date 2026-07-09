<?php
// ── Configuration ────────────────────────────────────────────────
define('TO_EMAIL', 'noelle@odeimin.ca');
define('SUBJECT_PREFIX', '[odeimin.ca] Message from ');
// ─────────────────────────────────────────────────────────────────

header('Content-Type: application/json');
header('Cache-Control: no-store');

function respond(bool $ok, string $message): void {
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Method not allowed.');
}

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    respond(false, 'Please fill in all fields.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}

// Sanitize to prevent header injection
$name    = str_replace(["\r", "\n"], '', $name);
$email   = str_replace(["\r", "\n"], '', $email);

// Optional gallery-piece reference from the "Ask about this piece" link.
// Only pass through paths that look like our own gallery images.
$piece = trim($_POST['piece'] ?? '');
$piece = str_replace(["\r", "\n"], '', $piece);
if (!preg_match('#^images/(available|unavailable)/[^?\#\\\\]{1,180}\.(jpe?g|png|gif|webp)$#i', $piece)) {
    $piece = '';
}

$subject = SUBJECT_PREFIX . $name;

$body  = "Name: $name\n";
$body .= "Email: $email\n\n";
if ($piece !== '') {
    $body .= "Regarding piece: https://odeimin.ca/$piece\n\n";
}
$body .= "Message:\n$message\n";

$headers  = "From: noreply@odeimin.ca\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

if (mail(TO_EMAIL, $subject, $body, $headers)) {
    respond(true, 'Message sent! Noelle will be in touch soon.');
} else {
    respond(false, 'Sorry, the message could not be sent. Please try again later.');
}
