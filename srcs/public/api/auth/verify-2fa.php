<?php

require_once __DIR__ . '/header_auth.php';
require_once __DIR__ . '/../../vendor/autoload.php';

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
$user_id = $body['id'];

//conseguimos el código de la línea correspondiente de la base de datos
$stmt1 = $database->prepare('SELECT user_id, code, created_at, time_to_expire_mins, attempts_left FROM twofa_codes WHERE user_id = :user_id');
$stmt1->bindValue(':user_id', $user_id);
$result1 = $stmt1->execute();
if (!$result1)
	errorSend(500, "SQLite Error: " . $database->lastErrorMsg());
$row1 = $result1->fetchArray(SQLITE3_ASSOC);
if (!$row1)
	errorSend(401, 'invalid credentials1');

//validamos el número de intentos, si lo hemos sobrepasado eliminamos la fila
if ($row1['attempts_left'] <= 0)
{
	delete_row($database, $user_id);
	errorSend(401, 'too many invalid attempts');
}

//validamos los tiempos
$currentTime = new DateTime();
$createdAt = new DateTime($row1['created_at']); //el constructor de DateTime() es capaz de interpretar el formato de texto estándar de SQLite (YYYY-MM-DD HH:MM:SS)
$diff_secs =  $currentTime->getTimestamp() - $createdAt->getTimestamp();
if ($diff_secs > $row1['time_to_expire_mins'] * 60)
{
	delete_row($database, $user_id);
	errorSend(401, 'code is too old =>' . $diff_secs . ' .max time: ' . $row1['time_to_expire_mins'] * 60);
} //getTimestamp() => devuelve el timestamp Unix: número de segundos transcurridos desde el 1 de enero de 1970 00:00:00

//validamos el código del usuario
if (!hash_equals($row1['code'], $code)) //con === se puede timear el tiempo que pasa comparando ambos strings, para saber hasta que carcater son identicos, con hash_equals() no
{
	decrease_attempts_left($database, $user_id, $row1['attempts_left']);
	errorSend(401, 'invalid credentials2');
}

//creamos el JasonWebToken (JWT)
$userId = $row1['user_id'];
$issuer = 'http://localhost:8081';
$audience = 'http://localhost:8081';
$issuedAt = time(); // timestamp Unix
$expire = $issuedAt + 3600; // el token expira en 1 hora (3600 segundos)
$payload = ['iss' => $issuer, 'aud' => $audience, 'iat' => $issuedAt, 'exp' => $expire, 'data' => ['userId' => $userId]]; // 'data' => para añadir datos personalizados
$secretKey = getenv('JWTsecretKey'); //necesitamos getenv para leer la variable de entorno
$jwt = Firebase\JWT\JWT::encode($payload, $secretKey, 'HS256'); // Codificamos el payload para generar el string del JWT, usando el algoritmo HS256

//enviamos el token
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Login successful.', 'token' => $jwt]);
exit;

function delete_row(SQLite3 $database, int $user_id): void
{
	$deleteStmt = $database->prepare('DELETE FROM twofa_codes WHERE id = :id');
	$deleteStmt->bindValue(':id', $user_id);
	if (!$deleteStmt->execute())
		errorSend(500, "SQLite Error: " . $database->lastErrorMsg());
}

function decrease_attempts_left(SQLite3 $database, int $user_id, int $attempts_left): void
{
	$updateStmt = $database->prepare('UPDATE twofa_codes SET attempts_left = :attempts WHERE id = :id');
	$updateStmt->bindValue(':id', $user_id);
	$updateStmt->bindValue(':attempts', $attempts_left - 1);
	if (!$updateStmt->execute())
		errorSend(500, "SQLite Error: " . $database->lastErrorMsg());
}

?>
