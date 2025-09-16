<?php

require_once __DIR__ . '/header.php';

switch ($requestMethod)
{
    case 'GET':
        getFriendList($database, $id);
        break ;
    case 'DELETE':
        deleteFriend($database, $body);
        break ;
    default:
		errorSend(405, 'unauthorized method.');
}

function getFriendList($database, $id)
{
    if (!$id || !is_numeric($id))
		errorSend(403, 'bad petition');

    // Selecciona usuarios cuya id esté en la relación friends (en cualquiera de las dos direcciones).
    $preparedQuery = $database->prepare(
        "SELECT u.id, u.username, u.email
         FROM users u
         WHERE u.id IN (
            SELECT friend_id FROM friends WHERE user_id = :id
            UNION
            SELECT user_id FROM friends WHERE friend_id = :id
         )"
    );
    $preparedQuery->bindValue(':id', $id, SQLITE3_INTEGER); // Bindea el id buscado.
    $res = $preparedQuery->execute();                        // Ejecuta SELECT.
    if (!$res)
		errorSend(500, 'internal server error');
	else
	{
        $friendList = [];                                   // Acumula filas.
        while ($row = $res->fetchArray(SQLITE3_ASSOC))
            $friendList[] = $row;
        echo json_encode(['friends' => $friendList]);       // Devuelve JSON con amigos.
    }
}

function deleteFriend($database, $body)
{
    // Valida JSON de entrada.
    if (!isset($body['user_id']) || !isset($body['friend_id']) || !is_numeric($body['user_id']) || !is_numeric($body['friend_id']))
		errorSend(403, 'bad petition');
    $userId = $body['user_id'];
    $friendId = $body['friend_id'];

    // Comprueba existencia de relación en cualquier dirección.
    $friendsCheck = $database->prepare(
        "SELECT 1
         FROM friends
         WHERE (user_id = :user_id AND friend_id = :friend_id)
            OR (user_id = :friend_id AND friend_id = :user_id)
         LIMIT 1"
    );
    $friendsCheck->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $friendsCheck->bindValue(':friend_id', $friendId, SQLITE3_INTEGER);
    $res = $friendsCheck->execute();                         // Ejecuta SELECT.

    if ($res == 1)
	{                                        // Aquí compara mal: $res es SQLite3Result, no 1.
        // Elimina la relación en ambas direcciones con una única sentencia OR.
        $preparedQuery = $database->prepare(
            "DELETE FROM friends
             WHERE (user_id = :user_id AND friend_id = :friend_id)
                OR (user_id = :friend_id AND friend_id = :user_id" // Falta ) final en el fichero recibido.
        );
        $preparedQuery->bindValue(':user_id', $userId, SQLITE3_INTEGER);
        $preparedQuery->bindValue(':friend_id', $friendId, SQLITE3_INTEGER);
        $preparedQuery->execute();
        if ($database->changes() > 0)
		{
            echo json_encode(['success' => 'friend deleted']);
            return ;
        } 
		else
			errorSend(500, 'unable to delete friend');
    }
    else
		errorSend(403, 'users are not friends');
}

?>
