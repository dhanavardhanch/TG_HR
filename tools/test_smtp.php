<?php
$host = 'troogood.in';
$port = 465;
$username = 'dailymails@troogood.in';
$password = 'TgMail@1234$#@!';

echo "Testing SSL SMTP connection to {$host}:{$port}...\n";

function readSmtpResponse($fp) {
    $resp = '';
    while ($line = fgets($fp, 512)) {
        $resp .= $line;
        // Multi-line SMTP responses have a hyphen after the code (e.g. 250-), final line has space (e.g. 250 )
        if (strlen($line) >= 4 && substr($line, 3, 1) === ' ') {
            break;
        }
    }
    return $resp;
}

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

$fp = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
if (!$fp) {
    echo "Connection FAILED: $errstr ($errno)\n";
    exit(1);
}

echo "CONNECTED successfully!\n";
$banner = readSmtpResponse($fp);
echo "Banner:\n$banner";

fputs($fp, "EHLO troogood.in\r\n");
$ehlo = readSmtpResponse($fp);
echo "EHLO:\n$ehlo";

fputs($fp, "AUTH LOGIN\r\n");
$auth = readSmtpResponse($fp);
echo "AUTH:\n$auth";

fputs($fp, base64_encode($username) . "\r\n");
$user = readSmtpResponse($fp);
echo "Username sent -> Response:\n$user";

fputs($fp, base64_encode($password) . "\r\n");
$pass = readSmtpResponse($fp);
echo "Password sent -> Response:\n$pass";

fputs($fp, "QUIT\r\n");
$quit = readSmtpResponse($fp);
fclose($fp);

if (strpos($pass, '235') !== false) {
    echo "\n>>> SUCCESS: SMTP AUTHENTICATION 100% SUCCEEDED! <<<\n";
} else {
    echo "\n>>> FAILED: Auth response did not contain 235: $pass <<<\n";
}
