<?php

require_once 'header.php'; // Importa variables comunes: $database, $requestMethod, $id, $bodyArray, etc.

switch ($requestMethod) {                      // Router por método HTTP.
    case 'POST':
        createUser($database, $bodyArray);     // Crear usuario con datos del body JSON.
        break ;
    case 'GET':
        if (!$id) {                            // GET sin ?id → lista
            getUserList($database);
        } else {                               // GET con ?id → detalle
            getUserDataById($id, $database);
        }
        break ;
    case 'PATCH':
        if ($id) {                             // PATCH requiere ?id
            editUserData($database, $id, $bodyArray);
        }
        break ;
    case 'DELETE':
        if ($id) {                             // DELETE requiere ?id
            deleteUser($database, $id);
        }
        break ;
    default:
        http_response_code(405);               // Método no permitido.
        echo json_encode(['error' => 'unauthorized method.']); // Respuesta JSON de error.
        break ;
} 
// router

function createUser($database, $body): void {  // Crea un usuario nuevo.
    if (!isset($body['username'], $body['email'], $body['password'])) { // Valida campos requeridos.
        http_response_code(400);
        echo json_encode(['error' => 'Bad request. Missing fields.']);
        return;
    }
    
    $username = $body['username'];             // Extrae username.
    $email = $body['email'];                   // Extrae email.
    $password = password_hash($body['password'], PASSWORD_DEFAULT); // Hash seguro con salt.

    // Statement preparado para comprobar unicidad de username/email.
    $checkQuery = $database->prepare("SELECT id FROM users WHERE username = :username OR email = :email"); // Prepara SQL con placeholders nombrados.
    $checkQuery->bindValue(':username', $username); // Sustituye :username por el valor.
    $checkQuery->bindValue(':email', $email);       // Sustituye :email por el valor.
    $result = $checkQuery->execute();               // Ejecuta y devuelve SQLite3Result.

    if ($result->fetchArray(SQLITE3_ASSOC)) {       // Si hay fila, ya existe username o email.
        http_response_code(409);                    // 409 Conflict.
        echo json_encode(['error' => 'username/email used in other account']);
        return;
    }

    try {
        // Inserta el nuevo usuario.
        $secureQuest = $database->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
        if (!$secureQuest) {                        // Si falla la preparación.
            http_response_code(500);
            echo json_encode(['error' => 'Prepare failed', 'details' => $database->lastErrorMsg()]);
            return;
        }
        $secureQuest->bindValue(':username', $username);
        $secureQuest->bindValue(':email', $email);
        $secureQuest->bindValue(':password_hash', $password);
        $secureQuest->execute();                    // Ejecuta INSERT.
        echo json_encode(['success' => true, 'message' => 'User created.']);
    } catch (Exception $e) {                        // Captura excepciones de ejecución.
        http_response_code(500);
        echo json_encode([
            'error' => "User can't be created...",
            'details' => $e->getMessage()
        ]);
    }
}

/* esta funciona haciendo una peticion POST, en la que el body
ha de tener el formato: "username:(nombre de usuario), email:(email xD),
password:(contrasenha) :D */

function getUserList($database) {                   // Lista todos los usuarios (id, username).
    $dbQuery = "SELECT id, username FROM users ORDER BY id ASC"; // SQL literal sin parámetros.
    $data = $database->query($dbQuery);             // Ejecuta query directa (no preparada).
    $users = [];                                    // Acumulador de filas.
    while ($rows = $data->fetchArray(SQLITE3_ASSOC)) { // Itera resultado en arrays asociativos.
        $users[] = $rows;
    }
    echo json_encode($users);                       // Devuelve JSON con la lista.
}
/* this function retorna la lista de todos los usuarios
en la base de datos pero solo dara por ahora el id, el nombre
y la fecha en la que se ha creado, podria dar mas cosas
pero creo que sobran atope y seguridat y tal 

se accede por peticion GET a /users sin ?id=(id) */

function getUserDataById($playerId, $database) {    // Detalle de usuario por id.
    if (!is_numeric($playerId)) {                   // Valida que id sea numérico.
        http_response_code(404);
        echo json_encode(['error' => 'invalid Id']);
        return ;
    }
    $secureQuest = $database->prepare("SELECT id, username FROM users WHERE id = :id"); // Prepara SELECT por id.
    $secureQuest->bindValue(":id", $playerId, SQLITE3_INTEGER); // Bindea id con tipo entero.
    $data = $secureQuest->execute();                // Ejecuta SELECT.
    $arrayData = $data->fetchArray(SQLITE3_ASSOC);  // Lee una fila.
    if ($arrayData) {
        echo json_encode($arrayData);               // Devuelve el usuario.
    } else {
        http_response_code(404);                    // No encontrado.
        echo json_encode(['error' => 'user not found']);
    }
    return ;
}
/* si la funcion de datos de un solo jugador con acceso por id
quieres usar una peticion de tipo GET a /users?id=(id) tendras que realizar */

function editUserData($database, $playerId, $body) { // Actualiza username/email de un usuario.
    if (!is_numeric($playerId)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid user ID']);
        return ;
    }
    $updatedData = [];                               // Fragmentos "columna = :param".
    $parameters = [];                                // Mapa parámetro→valor.
    if (isset($body['username'])) {                  // Si viene username en JSON.
        $updatedData[] = "username = :username";
        $parameters[':username'] = $body['username'];
    }
    if (isset($body['email'])) {                     // Si viene email en JSON.
        $updatedData[] = "email = :email";
        $parameters[':email'] = $body['email'];
    }
    if (empty($updatedData)) {                       // Nada que actualizar.
        http_response_code(400);
        echo json_encode(['error' => 'no fields to be updated']);
        return ;
    }
    $query = "UPDATE users SET " . implode(', ', $updatedData) . " WHERE id = :id"; // Construye SET dinámico.
    $preparedQuery = $database->prepare($query);     // Prepara UPDATE final.
    foreach ($parameters as $key => $value) {        // Bindea cada parámetro dinámico.
        $preparedQuery->bindValue($key, $value);
    }
    $preparedQuery->bindValue(':id', $playerId, SQLITE3_INTEGER); // Bindea id objetivo.
    $preparedQuery->execute();                       // Ejecuta UPDATE.
    if ($database->changes() > 0) {                  // Comprueba filas afectadas.
        echo json_encode(['success' => 1, 'message' => 'user updated']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'user not found or no changes made']);
    }
    return ;
}

/* funciona con peticion PATCH en users/id donde id
es el id del usuario a cambiar, ademas, en el body de la peticion
han de estar escritos los datos a modificar con el formato
" username:'nuevo nombre de usuario', email: 'nuevo email' "

el tema de la contra ira en security.php
*/

function deleteUser($database, $playerId) {          // Borra un usuario por id.
    if (!is_numeric($playerId)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid user ID']);
        return ;
    }
    $preparedQuery = $database->prepare("DELETE FROM users WHERE id = :id"); // Prepara DELETE por id.
    $preparedQuery->bindValue(':id', $playerId, SQLITE3_INTEGER);            // Bindea id.
    $res = $preparedQuery->execute();                 // Ejecuta DELETE.
    if ($database->changes() > 0) {                   // Si hubo filas borradas.
        echo json_encode(['success' => 1, 'message' => 'user deleted']);
    } else {
        http_response_code(404);                      // Ya no existe o nunca existió.
        echo json_encode(['error' => 'user not found or already deleted']);
    }
    return ;
}
/* funciona haciendo una peticion a /users de tipo DELETE con el id*/

?>
