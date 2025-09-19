<?php

// Carga el autoloader de Composer => para usar las clases de Google
require_once __DIR__ . '/../../../vendor/autoload.php';

function gmailClient(): Google\Client
{
    $client = new Google\Client();
    $client->setApplicationName('Transcendence');
    $client->setScopes([Google\Service\Gmail::GMAIL_SEND]); //GMAIL_SEND es una constante de la librería de Google usada para referirse a la URL del scope
    $client->setAuthConfig(__DIR__ . '/../../../secrets/google_oauth_client.json');
    $client->setAccessType('offline'); 
	//Al solicitar acceso offline, le estás pidiendo a Google que, además del access_token de corta duración,
	//te entregue un refresh_token. Este es un token especial de larga duración. Su única función es permitir que tu servidor, 
	//de forma automática y sin intervención del usuario, lo intercambie por un nuevo access_token cada vez que el antiguo caduque.
	//La alternativa, el acceso online, se tiene que renovar cada hora.

    $tokenPath = __DIR__ . '/../../../config/google_token.json';
    if (!file_exists($tokenPath))
		throw new Exception('Falta google_token.json. Ejecuta el script de setup para generarlo.'); 

    $accessToken = json_decode(file_get_contents($tokenPath), true);
    $client->setAccessToken($accessToken);

    if ($client->isAccessTokenExpired())
    {
		$client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
		file_put_contents($tokenPath, json_encode($client->getAccessToken(), JSON_PRETTY_PRINT));
    }

    return $client;
}

function sendMailGmailAPI(string $mail, string $id, string $code): bool
{
	try
	{
		$client = gmailClient();
		$gmail = new Google\Service\Gmail($client);

		$message = "From: 'me'\r\n";
		$message .= "To: {$mail}\r\n";
		$message .= "Subject: code for user => {$id}\r\n";
		$message .= "Content-Type: text/plain; charset=UTF-8\r\n";
		$message .= "\r\n";
		$message .= "CODE: " . $code;
		$message .= "\r\n";

		$rawMessage = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');

		$gmailMessage = new Google\Service\Gmail\Message();
		$gmailMessage->setRaw($rawMessage);

		$gmail->users_messages->send('me', $gmailMessage);
	}
	catch(Exception $e)
	{
		error_log('Gmail API Error: ' . $e->getMessage());
		return (false);
	}
	return (true);
}
