<?php

header('Content-Type: application/json');

require_once 'config/config.php';

try {
    $database = databaseConnection();
    echo json_encode([
        'status' => 'ok',
        'message' => 'server up and database connected.'
    ]);
} catch (Exception $e) {
    http_response_code(500);// internal error
    echo json_encode([
        'status' => 'error',
        'message' => 'unable to connect database.',
        'error' => $e->getMessage()
    ]);
}

?>