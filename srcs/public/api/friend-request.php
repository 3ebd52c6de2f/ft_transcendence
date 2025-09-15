<?php

//desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/config.php';
require_once 'utils.php';
$idQuest = 1;
// $idQuest = checkAuthentification($_SERVER['Authentication']);
$database = databaseConnection();
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$id = $_GET['id'] ?? null;

$body = file_get_contents('php://input');
$bodyArray = json_decode($body, true);

/* 
post /friend-request = envia solicitud de amistad
get /friend-request?id=x = lista solicitudes pendientes
post /friend-request/respond = aceptar/declinar una solicitud de amistad
*/

if ($idQuest != 0 || checkDiff($id, $idQuest)) {
    switch ($requestMethod) {
        case 'POST':
            sendFriendRequest($database, $bodyArray);
            break ;
        case  'GET':
            requestListById($database, $id);
            break ;
        case 'PATCH':
            acceptDeclineFriendRequest($database, $bodyArray);
            break ;
        default:
            http_response_code(405); // unauthorized
            echo json_encode(['error' => 'unauthorized method.']);
            break ;
    } 
} else {
    http_response_code(403);//forbidden
    echo json_encode(['error' => 'forbidden']);
    return ;
}


function requestListById($database, $id) {
    if (!is_numeric($id)) {
        http_response_code(400); // bad request
        echo json_encode(['error' => 'bad request']);
        return;
    }
    $preparedQuery = $database->prepare("SELECT sender_id FROM friend_request WHERE receiver_id = :id AND status = 'pending'");
    $preparedQuery->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $preparedQuery->execute();
    if (!$result) {
        http_response_code(500); // internal server error
        echo json_encode(['error' => 'internal server error', 'details' => $error]);
        return;
    }
    $response = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $response[] = $row;
    }
    echo json_encode(['friend-request list' => $response]);
    return ;
}

function acceptDeclineFriendRequest($database, $body) {
    $senderId = $body['sender_id'] ?? null;
    $receiverId  = $body['receiver_id'] ?? null;
    $action = $body['action'] ?? null;
    if (!isset($senderId, $receiverId, $action) || !in_array($action, ['accept', 'decline']) ||
    !is_numeric($senderId) || !is_numeric($receiverId)) {
        http_response_code(400);
        json_encode(['error' => 'bad request']);
        return ;
    }
    $preparedQuery = $database->prepare("SELECT * FROM friend_request WHERE sender_id = :sender_id AND receiver_id = :receiver_id");
    $preparedQuery->bindValue(':sender_id', $senderId, SQLITE3_INTEGER);
    $preparedQuery->bindValue(':receiver_id', $receiverId, SQLITE3_INTEGER);
    $res = $preparedQuery->execute();
    if (!$res) {
        http_response_code(500);
        json_encode(['error' => 'internal server error']);
        return ;
    }
    $request = $res->fetchArray(SQLITE3_ASSOC);
    if (!$request) {
        http_response_code(404);
        echo json_encode(['error' => 'friend request not found']);
        return;
    }
    if ($action === 'accept') {
        $stmt1 = $database->prepare("INSERT INTO friends (user_id, friend_id) VALUES (:receiver_id, :sender_id)");
        $stmt1->bindValue(':receiver_id', $receiverId, SQLITE3_INTEGER);
        $stmt1->bindValue(':sender_id', $senderId, SQLITE3_INTEGER);
        $res1 = $stmt1->execute();

        $stmt2 = $database->prepare("INSERT INTO friends (user_id, friend_id) VALUES (:sender_id, :receiver_id)");
        $stmt2->bindValue(':sender_id', $senderId, SQLITE3_INTEGER);
        $stmt2->bindValue(':receiver_id', $receiverId, SQLITE3_INTEGER);
        $res2 = $stmt2->execute();

        if (!$res1 || !$res2) {
            http_response_code(500);
            echo json_encode(['error' => 'failed to add friends']);
            return;
        }
    }
    $del = $database->prepare("DELETE FROM friend_request WHERE sender_id = :sender_id AND receiver_id = :receiver_id");
    $del->bindValue(':sender_id', $senderId, SQLITE3_INTEGER);
    $del->bindValue(':receiver_id', $receiverId, SQLITE3_INTEGER);
    $delRes = $del->execute();
    if ($action === 'accept') {
        echo json_encode(['message' => 'friend request accepted']);
    } else {
        echo json_encode(['message' => 'friend request declined']);
    }
    return ;
}

function sendFriendRequest($database, $bodyArray) {
    $senderId = $bodyArray['sender_id'] ?? null;
    $receiverId = $bodyArray['receiver_id'] ?? null;
    if (!isset($senderId, $receiverId) || $senderId == $receiverId || !is_numeric($senderId) || !is_numeric($receiverId)) {
        http_response_code(400);
        echo json_encode(['error' => 'bad petition.']);
        return;
    }
    $userExists = function($userId) use ($database) {
        $preparedQuery = $database->prepare("SELECT 1 FROM users WHERE id = :id");
        $preparedQuery->bindValue(':id', $userId, SQLITE3_INTEGER);
        $res = $preparedQuery->execute();
        return ($res && $res->fetchArray()) ? true : false;
    };
    if (!$userExists($senderId) || !$userExists($receiverId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Sender or receiver user does not exist']);
        return;
    }
    $preparedQuery = $database->prepare("SELECT 1 FROM friends WHERE user_id = :user AND friend_id = :friend");
    $preparedQuery->bindValue(':user', $senderId, SQLITE3_INTEGER);
    $preparedQuery->bindValue(':friend', $receiverId, SQLITE3_INTEGER);
    $res = $preparedQuery->execute();
    if ($res && $res->fetchArray()) {
        http_response_code(409);
        echo json_encode(['error' => 'Users are already friends']);
        return;
    }
    $preparedQuery = $database->prepare("SELECT 1 FROM friend_request WHERE sender_id = :sender AND receiver_id = :receiver AND status = 'pending'");
    $preparedQuery->bindValue(':sender', $senderId, SQLITE3_INTEGER);
    $preparedQuery->bindValue(':receiver', $receiverId, SQLITE3_INTEGER);
    $res = $preparedQuery->execute();
    if ($res && $res->fetchArray()) {
        http_response_code(409);
        echo json_encode(['error' => 'Friend request already sent']);
        return;
    }
    $preparedQuery = $database->prepare("INSERT INTO friend_request (sender_id, receiver_id, status) VALUES (:sender, :receiver, 'pending')");
    $preparedQuery->bindValue(':sender', $senderId, SQLITE3_INTEGER);
    $preparedQuery->bindValue(':receiver', $receiverId, SQLITE3_INTEGER);
    $result = $preparedQuery->execute();
    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to execute statement', 'details' => $error]);
        return;
    }
    http_response_code(201);
    echo json_encode(['message' => 'Friend request sent']);
    return ;
}

?>