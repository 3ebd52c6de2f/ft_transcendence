<?php

//desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/config.php';
require_once 'utils.php';
$idQuest = 1;
// $idQuest = checkAuthtentication($_SERVER('Authorization'));
$database = databaseConnection();
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$id = $_GET['id'] ?? null; // ERROR DE FORMATO CON EStO
$body = file_get_contents('php://input');
$bodyArray = json_decode($body, true);

/*
get a /friends retorna la lista de los amigos del usuario (en el body la id con el token)
delete a /friends elimina un amigo (con el id en el body del usuario a eliminar)
*/
if ($idQuest != 0 || checkDiff($id, $idQuest)) {
    switch ($requestMethod) {
        case  'GET':
            getFriendList($database, $id);
            break ;
        case 'DELETE':
            deleteFriend($database, $bodyArray);
            break ;
        default:
            http_response_code(405); // unauthorized
            echo json_encode(['error' => 'unauthorized method.']);
            break ;
    }
} else {
    http_response_code(403); // prohibisao
    echo json_encode(['error' => 'forbidden']);
}

function getFriendList($database, $id) {
    if (!$id || !is_numeric($id)) {
        http_response_code(403); // bad petition
        echo json_encode(['error' => 'bad petition']);
        return ;
    }
    $preparedQuery = $database->prepare("SELECT u.id, u.username, u.email FROM users u WHERE u.id IN ( SELECT friend_id FROM friends WHERE user_id = :id UNION SELECT user_id FROM friends WHERE friend_id = :id)");
    $preparedQuery->bindValue(':id', $id, SQLITE3_INTEGER);
    $res = $preparedQuery->execute();
    if (!$res) {
        http_response_code(500); // i server err
        echo json_encode(['error' => 'internal server error']);
        return ;
    } else {
        $friendList = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $friendList[] = $row;
        }
        echo json_encode(['friends' => $friendList]);
    }
}

function deleteFriend($database, $body) {
    if (!isset($body['user_id']) || !isset($body['friend_id']) || !is_numeric($body['user_id']) || !is_numeric($body['friend_id'])) {
        http_response_code(403); // bad petition
        echo json_encode(['error' => 'bad petition']);
        return ;
    }
    $userId = $body['user_id'];
    $friendId = $body['friend_id'];
    $friendsCheck = $database->prepare(" SELECT 1 FROM friends WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id AND friend_id = :user_id) LIMIT 1");
    $friendsCheck->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $friendsCheck->bindValue(':friend_id', $friendId, SQLITE3_INTEGER);
    $res = $friendsCheck->execute();
    if ($res == 1) {
        $preparedQuery = $database->prepare("DELETE FROM friends WHERE (user_id = :user_id AND friend_id = :friend_id) OR (user_id = :friend_id AND friend_id = :user_id");
        $preparedQuery->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $preparedQuery->bindValue(':friend_id', $friendId, SQLITE3_INTEGER);
        $preparedQuery->execute();
        if ($database->changes() > 0) {
            echo json_encode(['success' => 'friend deleted']);
            return ;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'unable to delete friend']);
            return ;
        }
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'users are not friends']);
        return ;
    }
}

/* FORMATO

{
    "user_id" : x,
    "friend_id" : y (usuario a eliminar)
}

*/
?>