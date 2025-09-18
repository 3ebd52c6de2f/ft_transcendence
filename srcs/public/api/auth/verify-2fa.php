<?php

require_once __DIR__ . '/header_auth.php';

$database = databaseConnection();	// Abre o crea el archivo de base de datos SQLite y devuelve un objeto conexión listo para usar. tipo del objeto: SQLite3
$requestMethod = $_SERVER['REQUEST_METHOD'];	// Lee el método HTTP de la petición actual (GET, POST, PATCH, DELETE).
$bodyJSON = file_get_contents('php://input');	// Lee el cuerpo crudo de la petición HTTP (bytes). Útil para JSON enviado por el cliente.
$body = json_decode($bodyJSON, true);	// El cuerpo del HTTP request debería ser JSON que represente un objeto. En PHP eso se traduce en un array asociativo. Si no lo es, el JSON es inválido o no tiene la estructura esperada.

//validamos la petición
if ($requestMethod != 'POST')
	errorSend(405, 'unauthorized method');
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false)
	errorSend(415, 'unsupported media type');
if (!is_array($body))
	errorSend(400, 'invalid json');
if (!isset($body['code'], $body['id']) || $body['code'] === '' || $body['id'] === '')
	errorSend(400, 'Bad request. Missing fields');	

$code = $body['code'];
$id = $body['id'];

//conseguimos el código de la línea correspondiente de la base de datos
$stmt1 = $database->prepare('SELECT user_id, code, created_at, time_to_expire_mins, attempts_left FROM twofa_codes WHERE id = :id');
$stmt1->bindValue(':id', $id);
$result1 = $stmt1->execute();
if (!$result1)
	errorSend(500, "SQLite Error: " . $database->lastErrorMsg());
$row1 = $result1->fetchArray(SQLITE3_ASSOC);
if (!$row1)
	errorSend(401, 'invalid credentials');

//validamos el número de intentos, si lo hemos sobrepasado eliminamos la fila
if ($row1['attempts_left'] > 0)
{
	delete_row($database, $id);	
	errorSend(401, 'too many invalid attempts');
}

//validamos el código del usuario
if (!($row1['code'] === $code))
{
	increase_attempts($database, $id);
	errorSend(401, 'invalid credentials');
}

//validamos los tiempos
$currentTime = new DateTime();
$createdAt = new DateTime($row1['created_at']);
$diff_secs =  $currentTime->getTimestamp() - $createdAt->getTimestamp();
if ($diff_secs < $row1['time_to_expire_mins'] * 60)
{
	increase_attempts($database, $id);
	errorSend(401, 'code is too old =>' . $diff_secs . ' .max time: ' . $row1['time_to_expire_mins'] * 60);
}
//getTimestamp() => devuelve el timestamp Unix: número de segundos transcurridos desde el 1 de enero de 1970 00:00:00

//creamos el JasonWebToken (JWT) 








function delete_row(SQLite3 $database, int $id): void
{
	$stmt_del = $database->prepare('DELETE FROM twofa_codes WHERE id = ' . intval($id)); //intval =>  sea siempre un número entero.
	if (!$stmt_del->execute())
		errorSend(500, "SQLite Error: " . $database->lastErrorMsg());
}

function increase_attempts(SQLite3 $database, int $id): void
{
	$stmt = $database->prepare('UPDATE attempts_left FROM twofa_codes WHERE id = :id');
	$stmt->bindValue(':id', $id);
	if ($stmt->execute())
		errorSend(500, 'couldn`t increase_attempts on table twofa_codes');
}








header('Content-Type: application/json');          // Indica al navegador/cliente que la respuesta será texto en formato JSON.
exit;
?>
