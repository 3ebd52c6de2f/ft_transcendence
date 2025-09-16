<?php

require_once __DIR__ . '/../header.php';

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

// Preparamos una statement (stmt) para realizar un query a la tabla 'users'. necesitamos id, el hash y el mail. 
$stmt = $database->prepare("SELECT id, password_hash, email FROM users WHERE username = :username");
$stmt->bindValue(':username', $username);
$result = $stmt->execute(); // Devuelve un objeto de tipo SQLite3Result, ese objeto $result es un cursor sobre las filas que devuelve la consulta. Al inicio, el cursor está antes de la primera fila. Cada vez que llamas a fetchArray(), el cursor avanza una fila. Cuando ya no hay filas → devuelve false.
if (!$result)
	errorSend(500, "SQLite Error: " . $database->lastErrorMsg());
$row = $result->fetchArray(SQLITE3_ASSOC); // Para obtener filas concretas necesitas llamar a fetchArray() sobre $result. $row = $result->fetchArray(SQLITE3_ASSOC); SQLITE3_ASSOC => Indica que queremos la fila como array asociativo.
if (!$row)
	errorSend(401, 'invalid credentials');

// Verificamos que la contraseña introducida y la guardad sean identicas.
if (!password_verify($password, $row['password_hash'])) // la variable 'password_hash' contiene el hash + los medios para desencriptarlo
	errorSend(401, 'invalid credentials');

// Generamos un código númerico aleatorio de 6 cifras (rellenamos con 0s empezando por la izq)
$two_fa_code = str_pad(random_int(0,999999), 6, '0', STR_PAD_LEFT);

if (!sendMail($row['email'] , $two_fa_code))
	errorSend(500, "Unable to send authentication mail, to:" . $row['email']);

function sendMail(string $to, string $code): bool
{
	$headers = "From: no-reply@tudominio.com\r\n" . "Content-Type: text/plain; charset=UTF-8\r\n";
	if (!mail($to, "trascendence código de autentificación", $code, $headers)) // mail(string $to, string $subject, string $message, array|string $headers = [], string $parameters = ""): bool
		return (false);
	return (true);
}
