<?php

require_once __DIR__ . '/../header.php';

function onTime(DateTime $created_at, int $time_to_expire_mins): bool
{
	$currentTime = new DateTime();
	$diff_secs =  $currentTime->getTimestamp() - $created_at->getTimestamp(); //getTimestamp() => devuelve el timestamp Unix: número de segundos transcurridos desde el 1 de enero de 1970 00:00:00
	return ($time_to_expire_mins * 60 > $diff_secs);
}


















header('Content-Type: application/json');          // Indica al navegador/cliente que la respuesta será texto en formato JSON.

require_once __DIR__ . '/../config/config.php';               // Carga el archivo que tiene la función de conexión a la base de datos y la creación de tablas.

$database = databaseConnection();                  // Abre o crea el archivo de base de datos SQLite y devuelve un objeto conexión listo para usar.
$requestMethod = $_SERVER['REQUEST_METHOD'];       // Lee el método HTTP de la petición actual (GET, POST, PATCH, DELETE).
$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/')); // Toma la ruta completa pedida y la divide por “/” en segmentos (array); hoy no se usa luego.
$id = $_GET['id'] ?? null;                         // Intenta leer el parámetro ?id= de la URL; si no está presente, queda en null.
$bodyJSON = file_get_contents('php://input');          // Lee el cuerpo crudo de la petición HTTP (bytes). Útil para JSON enviado por el cliente.
$body = json_decode($bodyJSON, true);             // Intenta convertir el cuerpo (JSON) en un array asociativo de PHP. Si no es JSON válido, el resultado será null.


?>



