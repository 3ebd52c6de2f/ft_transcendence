<?php

// Carga el autoloader de Composer => para usar las clases de Google
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../header.php';

function gmailClient(): Google\Client
{
    $client = new Google\Client();
    $client->setApplicationName('Transcendence');
    $client->setScopes([Google\Service\Gmail::GMAIL_SEND]);
    $client->setAuthConfig(__DIR__ . '/../../config/google_oauth_client.json');
    $client->setAccessType('offline');

    $tokenPath = __DIR__ . '/../../config/google_token.json';
    if (!file_exists($tokenPath))
		errorSend(500, 'Falta google_token.json. Ejecuta el script de setup para generarlo.');
    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired())
    {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($tokenPath, json_encode($client->getAccessToken(), JSON_PRETTY_PRINT));
    }
    
    return $client;
}

function sendMailGmailAPI(string $to, string $subject, string $textBody): bool
{
    try
    {
        $client = gmailClient();
        $gmail = new Google\Service\Gmail($client);

        $message = "From: 'me'\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $textBody;

        $rawMessage = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');

        $gmailMessage = new Google\Service\Gmail\Message();
        $gmailMessage->setRaw($rawMessage);

        $gmail->users_messages->send('me', $gmailMessage);
        
        return true;
    }
    catch (Exception $e)
    {
        return false;
    }
}