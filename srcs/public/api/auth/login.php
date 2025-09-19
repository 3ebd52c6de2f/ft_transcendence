<?php

require_once __DIR__ . '/header_auth.php';
require_once __DIR__ . '/gmail_api/mail_gmail.php';

$database = databaseConnection();	// Abre o crea el archivo de base de datos SQLite y devuelve un objeto conexión listo para usar. tipo del objeto: SQLite3
$requestMethod = $_SERVER['REQUEST_METHOD'];	// Lee el método HTTP dWe la petición actual (GET, POST, PATCH, DELETE).
$bodyJSON = file_get_contents('php://input');	// Lee el cuerpo crudo de la petición HTTP (bytes). Útil para JSON enviado por el cliente.
$body = json_decode($bodyJSON, true);	// El cuerpo del HTTP request debería ser JSON que represente un objeto. En PHP eso se traduce en un array asociativo. Si no lo es, el JSON es inválido o no tiene la estructura esperada.

// Nos cercioramos de los tipos de: método, content-type y formato del cuerpo. Además de la existencia de los credenciales
if ($requestMethod != 'POST') //comprobamos que el método sea el adecuado
	errorSend(405, 'unauthorized method');
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') === false) //comprobamos que el content_type sea el adecuado. ?? usara el primer valor que no sea null y no este indefinido.
	errorSend(415, 'unsupported media type'); // stripos() => STRing Insensitive POSition => Devuelve la posición de una subcadena en otra, sin distinguir mayúsculas/minúsculas. Si no encuentra la subcadena devuelve 'false'.
if (!is_array($body)) // El cuerpo del HTTP request debería ser JSON que represente un objeto. En PHP eso se traduce en un array asociativo. Si no lo es, el JSON es inválido o no tiene la estructura esperada.
	errorSend(400, 'invalid json');
if (!isset($body['username'], $body['password']) || // username y password existen en el array que es el cuerpo de la petición
($body['username'] === '' || $body['password'] === '')) // no están vacíos
	errorSend(400, 'Bad request. Missing fields');

$username = $body['username'];
$password = $body['password'];

// Preparamos una statement (stmt1) para realizar un query a la tabla 'users'. necesitamos id, el hash y el mail. 
$stmt1 = $database->prepare("SELECT id, password_hash, email FROM users WHERE username = :username");
$stmt1->bindValue(':username', $username);
$result1 = $stmt1->execute(); // Devuelve un objeto de tipo SQLite3Result, ese objeto $result1 es un cursor sobre las filas que devuelve la consulta. Al inicio, el cursor está antes de la primera fila. Cada vez que llamas a fetchArray(), el cursor avanza una fila. Cuando ya no hay filas → devuelve false.
if (!$result1)
	errorSend(500, "SQLite Error: " . $database->lastErrorMsg());
$row = $result1->fetchArray(SQLITE3_ASSOC); // Para obtener filas concretas necesitas llamar a fetchArray() sobre $result. $row = $result->fetchArray(SQLITE3_ASSOC); SQLITE3_ASSOC => Indica que queremos la fila como array asociativo.
if (!$row)
	errorSend(401, 'invalid credentials');

// Verificamos que la contraseña introducida y la guardad sean identicas.
if (!password_verify($password, $row['password_hash'])) // la variable 'password_hash' contiene el hash + los medios para desencriptarlo
	errorSend(401, 'invalid credentials');

// Generamos un código númerico aleatorio de 6 cifras (rellenamos con 0s empezando por la izq)
$two_fa_code = str_pad(random_int(0,999999), 6, '0', STR_PAD_LEFT);

// Eliminamos cualquier código 2FA anterior para este usuario.
$stmt_delete = $database->prepare('DELETE FROM twofa_codes WHERE user_id = :user_id');
$stmt_delete->bindValue(':user_id', $row['id'], SQLITE3_INTEGER);
$stmt_delete->execute();

// Lo insertamos en la tabla correspondiente
$stmt2 = $database->prepare('INSERT INTO twofa_codes (user_id, code) VALUES (:u, :t)');
$stmt2->bindValue(':u', $row['id'], SQLITE3_INTEGER);
$stmt2->bindValue(':t', $two_fa_code, SQLITE3_TEXT);
if ($stmt2->execute() === false)
	errorSend(500, 'couldn`t store two_fa_code in table');

$id = $database->lastInsertRowID();

if (!sendMailGmailAPI($row['email'], $row['id'], $two_fa_code))
	errorSend(500, 'couldn\'t send mail with Gmail API');

header('Content-Type: application/json');
echo json_encode(['pending_2fa' => true, 'id' => $id]);
exit;
// header() => Es la función de PHP para enviar una cabecera HTTP sin procesar. 
// Las cabeceras son metadatos que viajan junto con la respuesta del servidor y 
// le dan instrucciones al cliente sobre cómo interpretar el contenido.