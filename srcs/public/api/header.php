<?php

header('Content-Type: application/json');			// Indica al navegador/cliente que la respuesta será texto en formato JSON.

ini_set('display_errors', 1);						// Activa mostrar errores en pantalla para este proceso PHP (útil en desarrollo).
ini_set('display_startup_errors', 1);				// Muestra errores que ocurren al arrancar PHP o extensiones antes de ejecutar el script.
error_reporting(E_ALL);								// Pide a PHP que notifique todos los tipos de errores y avisos.

require_once __DIR__ . '/../config/config.php';		// Carga el archivo que tiene la función de conexión a la base de datos y la creación de tablas.
// __DIR__ => es una constante mágica, directorio del archivo actual, el interpréte de PHP la rellena en tiempo de compilación => 
// PHP no interpreta rutas relativas “desde donde está el archivo” sino “desde el directorio de trabajo actual del proceso que ejecuta PHP”. Ese working directory no es fijo ni garantizado.

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
	exit;
}

$database = databaseConnection();                  				// Abre o crea el archivo de base de datos SQLite y devuelve un objeto conexión listo para usar. tipo del objeto: SQLite3
$requestMethod = $_SERVER['REQUEST_METHOD'];       				// Lee el método HTTP de la petición actual (GET, POST, PATCH, DELETE).
$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/')); // Toma la ruta completa pedida y la divide por “/” en segmentos (array); hoy no se usa luego.
$id = $_GET['id'] ?? null;                         				// Intenta leer el parámetro ?id= de la URL; si no está presente, queda en null.
$bodyJSON = file_get_contents('php://input');          			// Lee el cuerpo crudo de la petición HTTP (bytes). Útil para JSON enviado por el cliente.
$body = json_decode($bodyJSON, true);             				// El cuerpo del HTTP request debería ser JSON que represente un objeto. En PHP eso se traduce en un array asociativo. Si no lo es, el JSON es inválido o no tiene la estructura esperada.

?>
