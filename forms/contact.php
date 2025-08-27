<?php
/**
 * contact.php — secure contact form handler
 * - Returns plain text. On success, echoes exactly "OK" (for BootstrapMade validate.js).
 * - Validates and sanitizes input, prevents header injection.
 * - Sends via PHP mail(); optionally switch to SMTP/PHPMailer if needed.
 */

declare(strict_types=1);

// ---------------------------
// CONFIG — CHANGE THESE
// ---------------------------
$TO_EMAIL            = 'takhmina@yahoo.com'; // Your inbox
$SITE_FROM_ADDRESS   = 'no-reply@yourdomain.tld'; // A real domain email from your site
$SITE_FROM_NAME      = 'Portfolio Contact';
$SUBJECT_PREFIX      = 'Contact'; // prepended to user subject
$MAX_NAME_LEN        = 100;
$MAX_SUBJECT_LEN     = 150;
$MAX_MESSAGE_LEN     = 5000;
$ALLOW_ORIGINS       = []; // e.g., ['https://tahminamuksinova.github.io'] if you need CORS for AJAX

// ---------------------------
// OPTIONAL CORS (only if you submit via fetch from another origin)
// ---------------------------
if (!empty($ALLOW_ORIGINS)) {
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  if ($origin && in_array($origin, $ALLOW_ORIGINS, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Vary: Origin');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
  }
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

// ---------------------------
// BASIC SETUP
// ---------------------------
header('Content-Type: text/plain; charset=utf-8');

// Method guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Allow: POST', true, 405);
  echo 'Method Not Allowed';
  exit;
}

// Helper: strip control chars & trim
$sanitize_text = function (?string $v) {
  $v = $v ?? '';
  // Remove control characters except CR/LF/TAB
  $v = preg_replace('/[^\P{C}\t\r\n]/u', '', $v);
  return trim($v);
};

// Helper: prevent header injection in any user-provided header fields
$reject_header_injection = function (string $v): bool {
  return (bool)preg_match('/[\r\n](to:|from:|cc:|bcc:|subject:)/i', $v);
};

// ---------------------------
// READ INPUT
// ---------------------------
$name    = $sanitize_text($_POST['name']    ?? '');
$email   = $sanitize_text($_POST['email']   ?? '');
$subject = $sanitize_text($_POST['subject'] ?? '');
$message = $sanitize_text($_POST['message'] ?? '');

// ---------------------------
// VALIDATION
// ---------------------------
$errors = [];

// Required
if ($name === '')    { $errors[] = 'Name is required.'; }
if ($email === '')   { $errors[] = 'Email is required.'; }
if ($subject === '') { $errors[] = 'Subject is required.'; }
if ($message === '') { $errors[] = 'Message is required.'; }

// Lengths
if (mb_strlen($name)    > $MAX_NAME_LEN)    { $errors[] = 'Name is too long.'; }
if (mb_strlen($subject) > $MAX_SUBJECT_LEN) { $errors[] = 'Subject is too long.'; }
if (mb_strlen($message) > $MAX_MESSAGE_LEN) { $errors[] = 'Message is too long.'; }

// Email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $errors[] = 'Invalid email address.';
}

// Header injection checks
if ($reject_header_injection($name) || $reject_header_injection($email) || $reject_header_injection($subject)) {
  $errors[] = 'Invalid header content.';
}

if ($errors) {
  http_response_code(400);
  echo implode(' ', $errors);
  exit;
}

// ---------------------------
// BUILD EMAIL
// ---------------------------
$finalSubject = trim($SUBJECT_PREFIX . ': ' . $subject);
$bodyLines = [
  "Name: $name",
  "Email: $email",
  "",
  "Message:",
  $message,
];
$body = implode("\n", $bodyLines);

// Headers
$headers   = [];
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';
$headers[] = 'Content-Transfer-Encoding: 8bit';
$headers[] = 'From: ' . sprintf('"%s" <%s>', addslashes($SITE_FROM_NAME), $SITE_FROM_ADDRESS);
// Let you reply directly to the sender
$headers[] = 'Reply-To: ' . $email;
$headers[] = 'X-Mailer: PHP/' . phpversion();

$headersStr = implode("\r\n", $headers);

// ---------------------------
// SEND (mail())
// ---------------------------
// NOTE: On many hosts, mail() may be disabled or require correct SPF/DKIM on $SITE_FROM_ADDRESS.
// If delivery fails, switch to SMTP via PHPMailer/SMTP provider.
$sent = @mail($TO_EMAIL, $finalSubject, $body, $headersStr);

// ---------------------------
// RESPONSE
// ---------------------------
if ($sent) {
  // EXACTLY "OK" for BootstrapMade validate.js compatibility
  echo 'OK';
} else {
  http_response_code(500);
  echo 'Could not send email. Please try again later.';
}
