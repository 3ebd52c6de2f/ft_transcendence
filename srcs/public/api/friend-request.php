<?php

require_once 'header.php';
require_once 'utils.php';

switch ($requestMethod) 
{
    case 'POST':
        sendFriendRequest($database, $bodyArray);
        break ;
    case 'GET':
        requestListById($database, $id);
        break ;
    case 'PATCH':
        acceptDeclineFriendRequest($database, $bodyArray);
        break ;
    default:
        http_response_code(405); 
        echo json_encode(['error' => 'unauthorized method.']);
} 

function requestListById($database, $id)
{
    if (!is_numeric($id))
		errorSend(400, 'bad request');

    // Selecciona remitentes de solicitudes pendientes donde receiver_id = :id.
    $preparedQuery = $database->prepare(
        "SELECT sender_id FROM friend_request WHERE receiver_id = :id AND status = 'pending'");
    $preparedQuery->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $preparedQuery->execute();
    if (!$result)
		errorSend(500, 'internal server error', 'couldn`t find id');

    $response = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC))
        $response[] = $row;

    echo json_encode(['friend-request list' => $response]); // Devuelve lista de {sender_id}.
}

function acceptDeclineFriendRequest($database, $body)
{
    $senderId = $body['sender_id'] ?? null;      // Id de quien envió.
    $receiverId  = $body['receiver_id'] ?? null; // Id de quien recibe.
    $action = $body['action'] ?? null;           // 'accept' | 'decline'.

    if (!isset($senderId, $receiverId, $action) || !in_array($action, ['accept', 'decline']) ||
        !is_numeric($senderId) || !is_numeric($receiverId))
		errorSend(400, 'bad request');

    // Busca la solicitud concreta.
    $preparedQuery = $database->prepare(
        "SELECT * FROM friend_request WHERE sender_id = :sender_id AND receiver_id = :receiver_id"
    );
    $preparedQuery->bindValue(':sender_id', $senderId, SQLITE3_INTEGER);
    $preparedQuery->bindValue(':receiver_id', $receiverId, SQLITE3_INTEGER);
    $res = $preparedQuery->execute();
    if (!$res)
		errorSend(500, 'internal server error');

    $request = $res->fetchArray(SQLITE3_ASSOC);
    if (!$request)
		errorSend(404, 'friend request not found');

    if ($action === 'accept')
	{                   // Si se acepta, inserta amistad en ambos sentidos.
        $stmt1 = $database->prepare("INSERT INTO friends (user_id, friend_id) VALUES (:receiver_id, :sender_id)");
        $stmt1->bindValue(':receiver_id', $receiverId, SQLITE3_INTEGER);
        $stmt1->bindValue(':sender_id', $senderId, SQLITE3_INTEGER);
        $res1 = $stmt1->execute();

        $stmt2 = $database->prepare("INSERT INTO friends (user_id, friend_id) VALUES (:sender_id, :receiver_id)");
        $stmt2->bindValue(':sender_id', $senderId, SQLITE3_INTEGER);
        $stmt2->bindValue(':receiver_id', $receiverId, SQLITE3_INTEGER);
        $res2 = $stmt2->execute();

        if (!$res1 || !$res2)
			errorSend(500, 'failed to add friends');
    }
    // Elimina la solicitud.
    $del = $database->prepare(
        "DELETE FROM friend_request WHERE sender_id = :sender_id AND receiver_id = :receiver_id"
    );
    $del->bindValue(':sender_id', $senderId, SQLITE3_INTEGER);
    $del->bindValue(':receiver_id', $receiverId, SQLITE3_INTEGER);
    $delRes = $del->execute();

    if ($action === 'accept')
	{
        echo json_encode(['message' => 'friend request accepted']);
    } else {
        echo json_encode(['message' => 'friend request declined']);
    }
}

function sendFriendRequest($database, $bodyArray)
{
    $senderId = $bodyArray['sender_id'] ?? null;
    $receiverId = $bodyArray['receiver_id'] ?? null;

    // Valida ids y evita autoreferencia.
    if (!isset($senderId, $receiverId) || $senderId == $receiverId || !is_numeric($senderId) || !is_numeric($receiverId))
		errorSend(400, 'bad petition.');

    // Cierre sobre BD: comprueba existencia de usuario.
    $userExists = function($userId) use ($database)
	{
        $preparedQuery = $database->prepare("SELECT 1 FROM users WHERE id = :id");
        $preparedQuery->bindValue(':id', $userId, SQLITE3_INTEGER);
        $res = $preparedQuery->execute();
        return ($res && $res->fetchArray()) ? true : false;
    };
    if (!$userExists($senderId) || !$userExists($receiverId))
		errorSend(400, 'Sender or receiver user does not exist');

    // Bloquea si ya son amigos (en una dirección).
    $preparedQuery = $database->prepare("SELECT 1 FROM friends WHERE user_id = :user AND friend_id = :friend");
    $preparedQuery->bindValue(':user', $senderId, SQLITE3_INTEGER);
    $preparedQuery->bindValue(':friend', $receiverId, SQLITE3_INTEGER);
    $res = $preparedQuery->execute();
    if ($res && $res->fetchArray())
		errorSend(409, 'Users are already friends');

    // Bloquea si ya hay solicitud pendiente.
    $preparedQuery = $database->prepare("SELECT 1 FROM friend_re... = :sender AND receiver_id = :receiver AND status = 'pending'"); // ← línea truncada en fichero recibido.
    $preparedQuery->bindValue(':sender', $senderId, SQLITE3_INTEGER);
    $preparedQuery->bindValue(':receiver', $receiverId, SQLITE3_INTEGER);
    $res = $preparedQuery->execute();
    if ($res && $res->fetchArray())
		errorSend(409, 'Friend request already sent');


    // Inserta solicitud como 'pending'.
    $preparedQuery = $database->prepare("INSERT INTO friend_requ..., receiver_id, status) VALUES (:sender, :receiver, 'pending')"); // ← línea truncada en fichero recibido.
    $preparedQuery->bindValue(':sender', $senderId, SQLITE3_INTEGER);
    $preparedQuery->bindValue(':receiver', $receiverId, SQLITE3_INTEGER);
    $result = $preparedQuery->execute();
    if (!$result)
		errorSend(500, 'Failed to execute statement', 'Cannot find sender//receiver');

    http_response_code(201);
    echo json_encode(['message' => 'Friend request sent']);
}

?>
