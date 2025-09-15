<?php

require_once 'utils.php';

ini_set('display_errors', 1);                      // Activa mostrar errores en pantalla para este proceso PHP (útil en desarrollo).
ini_set('display_startup_errors', 1);              // Muestra errores que ocurren al arrancar PHP o extensiones antes de ejecutar el script.
error_reporting(E_ALL);                            // Pide a PHP que notifique todos los tipos de errores y avisos.

header('Content-Type: application/json');          // Indica al navegador/cliente que la respuesta será texto en formato JSON.

require_once '../config/config.php';               // Carga el archivo que tiene la función de conexión a la base de datos y la creación de tablas.

$database = databaseConnection();                  // Abre o crea el archivo de base de datos SQLite y devuelve un objeto conexión listo para usar.
$requestMethod = $_SERVER['REQUEST_METHOD'];       // Lee el método HTTP de la petición actual (GET, POST, PATCH, DELETE).
$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/')); // Toma la ruta completa pedida y la divide por “/” en segmentos (array); hoy no se usa luego.
$id = $_GET['id'] ?? null;                         // Intenta leer el parámetro ?id= de la URL; si no está presente, queda en null.
$body = file_get_contents('php://input');          // Lee el cuerpo crudo de la petición HTTP (bytes). Útil para JSON enviado por el cliente.
$bodyArray = json_decode($body, true);             // Intenta convertir el cuerpo (JSON) en un array asociativo de PHP. Si no es JSON válido, el resultado será null.

?>
