<?php
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo 'Method Not Allowed';
  exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $subject === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  echo 'Please fill out the form correctly.';
  exit;
}

$to      = 'takhmina@yahoo.com'; // change if needed
$mailSub = "Contact: $subject";
$body    = "Name: $name\nEmail: $email\n\nMessage:\n$message\n";
$headers = "From: no-reply@yourdomain.tld\r\nReply-To: $email\r\n";

if (@mail($to, $mailSub, $body, $headers)) {
  echo 'OK'; // what validate.js expects on success
} else {
  http_response_code(500);
  echo 'Could not send email. Please try again later.';
}
