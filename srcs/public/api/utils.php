<?php

function segmentPath($data) {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $requestUri = trim($requestUri, '/');
    $segments = explode('/', $requestUri);

    return $segments[$data - 1] ?? null;
}

function checkDiff($id, $questId) {
    if (!$id)
        return 1;
    if ($questId === $id)
        return 1;
    return 0;
}

?>