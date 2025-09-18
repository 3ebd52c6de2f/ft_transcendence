<?php

require_once __DIR__ . '/header_auth.php';

$database = databaseConnection();	// Abre o crea el archivo de base de datos SQLite y devuelve un objeto conexión listo para usar. tipo del objeto: SQLite3
$requestMethod = $_SERVER['REQUEST_METHOD'];	// Lee el método HTTP de la petición actual (GET, POST, PATCH, DELETE).
$bodyJSON = file_get_contents('php://input');	// Lee el cuerpo crudo de la petición HTTP (bytes). Útil para JSON enviado por el cliente.
$body = json_decode($bodyJSON, true);	// El cuerpo del HTTP request debería ser JSON que represente un objeto. En PHP eso se traduce en un array asociativo. Si no lo es, el JSON es inválido o no tiene la estructura esperada.

?>