<?php

require_once __DIR__ . '/../header.php';

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

$checkQuery = $database->prepare("SELECT id FROM users WHERE username = :username");
$checkQuery->bindValue(':username', $username);
$result = $checkQuery->execute(); // Devuelve un objeto de tipo SQLite3Result, ese objeto $result es un cursor sobre las filas que devuelve la consulta. Al inicio, el cursor está antes de la primera fila. Cada vez que llamas a fetchArray(), el cursor avanza una fila. Cuando ya no hay filas → devuelve false.
$row = $result->fetchArray(SQLITE3_ASSOC); // Para obtener filas concretas necesitas llamar a fetchArray() sobre $result. $row = $result->fetchArray(SQLITE3_ASSOC); SQLITE3_ASSOC => Indica que queremos la fila como array asociativo.
if (!$row)
	errorSend(401, 'invalid credentials');

if (!password_verify($loggedPassword, $row['password_hash']))
	errorSend(401, 'invalid credentials');

$two_fa_code = make6DigitCode();
if (!sendMail($row['email'] , $two_fa_code))
	errorSend(500, "Unable to send authentication mail, to: $to");

function make6DigitCode(): string
{
	$randInt = random_int(0,999999);
	return (str_pad($randInt, 6, '0', STR_PAD_LEFT)); //str_pad(string $stringInicial, int $desiredStrLength, string $padString = " ", int $padType = STR_PAD_RIGHT): string => lo que va despues de = son los valores por defecto.
}

function sendMail(string $to, string $code): bool
{
	$headers = "From: no-reply@tudominio.com\r\n" . "Content-Type: text/plain; charset=UTF-8\r\n";
	if (!mail($to, "trascendence código de autentificación", $code, $headers)) // mail(string $to, string $subject, string $message, array|string $headers = [], string $parameters = ""): bool
		return (false);
	return (true);
}