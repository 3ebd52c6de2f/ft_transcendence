<?php

header('Content-Type: application/json'); 	// Indica al navegador/cliente que la respuesta será texto en formato JSON.

ini_set('display_errors', 1);				// Activa mostrar errores en pantalla para este proceso PHP (útil en desarrollo).
ini_set('display_startup_errors', 1);		// Muestra errores que ocurren al arrancar PHP o extensiones antes de ejecutar el script.
error_reporting(E_ALL);						// Pide a PHP que notifique todos los tipos de errores y avisos.

require_once __DIR__ . '/../../config/config.php';	// Carga el archivo que tiene la función de conexión a la base de datos y la creación de tablas.
// __DIR__ => es una constante mágica, directorio del archivo actual, el interpréte de PHP la rellena en tiempo de compilación => 
// PHP no interpreta rutas relativas “desde donde está el archivo” sino “desde el directorio de trabajo actual del proceso que ejecuta PHP”. Ese working directory no es fijo ni garantizado.

function errorSend(int $errorCode, string $errorMsg, ?string $detailsMsg = null): void
{
	http_response_code($errorCode);
	$response = ['error' => $errorMsg]; //inicia response y luego le asigna su primera pareja
	if ($detailsMsg)
		$response['details'] = $detailsMsg; //añade una nueva entrada al array response
	echo json_encode($response);
	exit;
}

?>
