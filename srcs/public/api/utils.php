<?php

// deberiamos incluirlos en todos los archivos asi que lo pongo aqui y nos ahorramos otro archivo
ini_set('display_errors', 1);                      // Activa mostrar errores en pantalla para este proceso PHP (útil en desarrollo).
ini_set('display_startup_errors', 1);              // Muestra errores que ocurren al arrancar PHP o extensiones antes de ejecutar el script.
error_reporting(E_ALL);                            // Pide a PHP que notifique todos los tipos de errores y avisos.

function segmentPath($data) 
{
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requestUri = trim($requestUri, '/');
    $segments = explode('/', $requestUri);

    return $segments[$data - 1] ?? null;
}

function checkDiff($id, $questId)
{
    if (!$id)
        return 1;
    if ($questId === $id)
        return 1;
    return 0;
}

function errorSend(int $errorCode, string $errorMsg, ?string $detailsMsg = null): void
{
	http_response_code($errorCode);
    $response = ['error' => $errorMsg]; //inicia response y luego le asigna su primera pareja
	if ($detailsMsg)
		$response['details'] = $detailsMsg; //añade una nueva entrada al array response
	echo json_encode($response);
    return;
}

?>