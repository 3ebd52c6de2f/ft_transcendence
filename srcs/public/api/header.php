<?php

ini_set('display_errors', 1);               // Activa mostrar errores en salida (desarrollo).
ini_set('display_startup_errors', 1);       // Muestra errores al iniciar PHP/extensiones.
error_reporting(E_ALL);                     // Nivel máximo de reporte.

header('Content-Type: application/json');   // Todas las respuestas serán JSON.
require_once '../config/config.php';        // Carga conexión y creación de tablas.
require_once 'utils.php';

$idQuest = 1;
$database = databaseConnection();           // Abre/crea SQLite y aplica PRAGMAs/esquema.
$requestMethod = $_SERVER['REQUEST_METHOD']; // Método HTTP actual: GET/POST/PATCH/DELETE.
$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/')); // Segmenta la ruta (no usado aquí).
$id = $_GET['id'] ?? null;                  // Lee ?id=... si viene en la query string.
$body = file_get_contents('php://input');   // Lee el cuerpo bruto de la petición.
$bodyArray = json_decode($body, true);      // Decodifica JSON a array asociativo (o null si no es JSON).

?>
