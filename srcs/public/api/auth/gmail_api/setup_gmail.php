<?php

// Carga el autoloader de Composer => para que encuentre la clase Google\Client
require_once __DIR__ . '/../../../vendor/autoload.php';

// Ruta al archivo de credenciales descargado de Google Cloud
$credentialsPath = __DIR__ . '/../../../secrets/google_oauth_client.json';
// Ruta donde se guardará el token de acceso/refresco
$tokenPath = __DIR__ . '/../../../config/google_token.json';

if (!file_exists($credentialsPath))
	throw new Exception('No se encuentra el archivo de credenciales. Descárgalo de Google Cloud y guárdalo en: ' . $credentialsPath);

$client = new Google\Client();
$client->setApplicationName('Transcendence 2FA Setup');
$client->setScopes(['https://www.googleapis.com/auth/gmail.send']);
$client->setAuthConfig($credentialsPath);
$client->setAccessType('offline'); // Solicita un refresh_token
$client->setPrompt('select_account consent');

// 1. Generar la URL de autorización
$authUrl = $client->createAuthUrl();
echo "Abre esta URL en tu navegador para autorizar la aplicación:\n\n";
echo $authUrl . "\n\n";

// 2. Pedir el código de verificación al usuario
echo "Pega el código de autorización (en el URL entre code= y &scope) aquí y presiona Enter: ";
$authCode = trim(fgets(STDIN));

// 3. Intercambiar el código de verificación por un token de acceso
$accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

// 4. Comprobar si hubo un error
if (array_key_exists('error', $accessToken))
	throw new Exception("Error al obtener el token: " . join(', ', $accessToken));

// 5. Guardar el token (incluyendo el refresh_token) en un archivo
file_put_contents($tokenPath, json_encode($accessToken, JSON_PRETTY_PRINT));

printf("¡Éxito! El token ha sido guardado en: %s\n", $tokenPath);
printf("Ahora tu aplicación puede enviar correos.\n");

