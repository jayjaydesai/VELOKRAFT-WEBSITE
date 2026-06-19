<?php
/**
 * Velokraft enquiry form handler.
 * Sends the contact form as an email via the Brevo API (free tier).
 * Set these in Railway → Variables:
 *   BREVO_API_KEY  – your Brevo (Sendinblue) API key
 *   MAIL_TO        – where enquiries should arrive (e.g. sales@velokraft.eu)
 *   MAIL_FROM      – a Brevo-verified sender address (e.g. admin@velokraft.eu)
 */
header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'error';
    exit;
}

$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = trim($_POST['phone']   ?? '');
$type    = trim($_POST['type']    ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
    http_response_code(422);
    echo 'error';
    exit;
}

$apiKey = getenv('BREVO_API_KEY');
$to     = getenv('MAIL_TO')   ?: 'sales@velokraft.eu';
$from   = getenv('MAIL_FROM') ?: 'noreply@velokraft.eu';

if (!$apiKey) {
    http_response_code(500);
    echo 'error';
    exit;
}

$payload = [
    'sender'      => ['name' => 'Velokraft Website', 'email' => $from],
    'to'          => [['email' => $to]],
    'replyTo'     => ['email' => $email, 'name' => $name],
    'subject'     => "New enquiry from {$name} — Velokraft",
    'textContent' => "Name: {$name}\n"
                   . "Email: {$email}\n"
                   . "Phone: {$phone}\n"
                   . "Type: {$type}\n\n"
                   . "Message:\n{$message}",
];

$ctx = stream_context_create([
    'http' => [
        'method'        => 'POST',
        'header'        => "accept: application/json\r\n"
                         . "content-type: application/json\r\n"
                         . "api-key: {$apiKey}",
        'content'       => json_encode($payload),
        'ignore_errors' => true,
        'timeout'       => 20,
    ],
]);

$resp = @file_get_contents('https://api.brevo.com/v3/smtp/email', false, $ctx);

$code = 0;
if (!empty($http_response_header[0]) && preg_match('{\s(\d{3})\s}', $http_response_header[0], $mm)) {
    $code = (int) $mm[1];
}

if ($resp !== false && $code >= 200 && $code < 300) {
    echo 'success';
} else {
    http_response_code(502);
    echo 'error';
}
