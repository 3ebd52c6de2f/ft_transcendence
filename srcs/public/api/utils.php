<?php

function segmentPath($data) 
{
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requestUri = trim($requestUri, '/');
    $segments = explode('/', $requestUri);

    return $segments[$data - 1] ?? null;
}

function checkDiff($id, $questId)
{
    if (!$id)
        return 1;
    if ($questId === $id)
        return 1;
    return 0;
}

function errorSend(int $errorCode, string $errorMsg, ?string $detailsMsg = null): void
{
	http_response_code($errorCode);
    $response = ['error' => $errorStr]; //inicia response y luego le asigna su primera pareja
	if ($detailsMsg)
		$response['details'] = $detailsMsg; //añade una nueva entrada al array response
	echo json_encode($response);
    return;
}

?>