<?php

function segmentPath($data) {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requestUri = trim($requestUri, '/');
    $segments = explode('/', $requestUri);

    return $segments[$data - 1] ?? null;
}

?>