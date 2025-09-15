<?php

require_once '../utils.php';
require_once '../header.php';

function make6DigitCode(): string
{
	$randInt = random_int(0,999999);
	return (str_pad($randInt, 6, '0', STR_PAD_LEFT)); //str_pad(string $stringInicial, int $desiredStrLength, string $padString = " ", int $padType = STR_PAD_RIGHT): string => lo que va despues de = son los valores por defecto.
}

function sendMail(string $to, string $code): void
{
	$headers = "From: no-reply@tudominio.com\r\n" . "Content-Type: text/plain; charset=UTF-8\r\n";
	if (!mail($to, "trascendence código de autentificación", $code, $headers)) // mail(string $to, string $subject, string $message, array|string $headers = [], string $parameters = ""): bool
		errorSend(500, "Unable to send authentication mail, to: $to");
}

function onTime(DateTime $created_at, int $time_to_expire_mins): bool
{
	$currentTime = new DateTime(); // Formato: "2025-09-15 18:45:00"
	$diff_secs =  $currentTime->getTimestamp() - $created_at->getTimestamp(); //getTimestamp() => devuelve el timestamp Unix: número de segundos transcurridos desde el 1 de enero de 1970 00:00:00
	return ($time_to_expire_mins * 60 > $diff_secs);
}

?>