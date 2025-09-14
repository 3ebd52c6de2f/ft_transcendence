<?php

//desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/config.php';
require_once 'utils.php';

$database = databaseConnection();
$idQuest = 1;
// $idQuest = checkAuthentication($_SERVER['Authorization'/'HTTP_AUTHORIZATION']);
$requestMethod = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$body = file_get_contents('php://input');
$bodyArray = json_decode($body, true);
// error_log(print_r($id, true));

if ($idQuest != 0 || checkDiff($id, $idQuest)) {
    switch ($requestMethod) {
        case 'GET':
            if ($id) {
                friendsLadderList($id, $database);
                break ;
            }
            else {
                globalLadderList($database);
                break ;
            }
        default:
            http_response_code(405);
            echo json_encode(['error' => 'unauthorized method.']);
            break ;
        }
    }
    
    
function friendsLadderList($id, $database) {
    $preparedQuery = $database->prepare("SELECT u.id, u.username, u.elo FROM users u INNER JOIN friends f 
        ON (u.id = f.friend_id OR u.id = f.user_id) WHERE $id IN (f.user_id, f.friend_id)
        AND u.id != $id ORDER BY u.elo DESC");
    $res = $preparedQuery->execute();
    if (!$res) {
        http_response_code(500); // server internal error
        echo json_encode(['error' => 'internal server error']);
    }
    $data = [];
    while ($array = $res->fetchArray(SQLITE3_ASSOc)) {
        $data[] = $array;
    }
    echo json_encode($data, JSON_PRETTY_PRINT);
    return ;
}

function globalLadderList($database) {
    $preparedQuery = $database->prepare("SELECT id, username, elo FROM users ORDER BY elo DESC");
    $res = $preparedQuery->execute();
    if (!$res) {
        http_response_code(500); // server internal error
        echo json_encode(['error' => 'internal server error']);
        return ;
    }
    $data = [];
    while ($array = $res->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $array;
    }
    echo json_encode($data, JSON_PRETTY_PRINT);
    return ;
}

?>