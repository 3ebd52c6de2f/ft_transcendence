<?php

require_once 'header.php'; // Variables comunes ya listas.

 /*
 get a /friends retorna la lista de los amigos del usuario (en el body la id con el token)
 delete a /friends elimina un amigo (con el id en el body del usuario a eliminar)
 */

switch ($requestMethod) {
    case 'GET':
        getFriendList($database, $id);          // Lista amigos de ?id.
        break ;
    case 'DELETE':
        deleteFriend($database, $bodyArray);    // Elimina relación con JSON {user_id, friend_id}.
        break ;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'unauthorized method.']);
        break ;
}

function getFriendList($database, $id) {
    if (!$id || !is_numeric($id)) {             // Valida id.
        http_response_code(403);                // Nota: semánticamente sería 400, aquí retorna 403.
        echo json_encode(['error' => 'bad petition']);
        return ;
    }
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
    if (!$res) {                                            // Error al ejecutar.
        http_response_code(500);
        echo json_encode(['error' => 'internal server error']);
        return ;
    } else {
        $friendList = [];                                   // Acumula filas.
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $friendList[] = $row;
        }
        echo json_encode(['friends' => $friendList]);       // Devuelve JSON con amigos.
    }
}

function deleteFriend($database, $body) {
    // Valida JSON de entrada.
    if (!isset($body['user_id']) || !isset($body['friend_id']) || !is_numeric($body['user_id']) || !is_numeric($body['friend_id'])) {
        http_response_code(403);                            // Nota: semánticamente sería 400.
        echo json_encode(['error' => 'bad petition']);
        return ;
    }
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

    if ($res == 1) {                                        // Aquí compara mal: $res es SQLite3Result, no 1.
        // Elimina la relación en ambas direcciones con una única sentencia OR.
        $preparedQuery = $database->prepare(
            "DELETE FROM friends
             WHERE (user_id = :user_id AND friend_id = :friend_id)
                OR (user_id = :friend_id AND friend_id = :user_id" // Falta ) final en el fichero recibido.
        );
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
        http_response_code(403);                            // Si no son amigos.
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
