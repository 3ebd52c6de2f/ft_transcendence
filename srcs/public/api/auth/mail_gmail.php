<?php
// Ubicación: srcs/public/api/auth/mail_gmail.php

use Google\Client;
use Google\Service\Gmail;

// La ruta ahora sube dos niveles para llegar a la raíz de 'public'
require_once __DIR__ . '/../../vendor/autoload.php';

function gmailClient(): Client
{
    $client = new Client();
    $client->setApplicationName('Transcendence');
    $client->setScopes([Gmail::GMAIL_SEND]);
    // La ruta ahora sube dos niveles
    $client->setAuthConfig(__DIR__ . '/../../config/google_oauth_client.json');
    $client->setAccessType('offline');

    // La ruta ahora sube dos niveles
    $tokenPath = __DIR__ . '/../../config/google_token.json';
    if (!file_exists($tokenPath))
    {
        throw new RuntimeException('Falta google_token.json. Ejecuta el script de setup para generarlo.');
    }

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
        $gmail = new Gmail($client);

        $message = "From: 'me'\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $textBody;

        $rawMessage = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');

        $gmailMessage = new Gmail\Message();
        $gmailMessage->setRaw($rawMessage);

        $gmail->users_messages->send('me', $gmailMessage);
        
        return true;
    }
    catch (Exception $e)
    {
        return false;
    }
}