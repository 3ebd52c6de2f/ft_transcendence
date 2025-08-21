<?php

header('Content-Type: application/json');
require_once '../config/config.php';

$database = databaseConnection();
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
$id = $requestUri[2] ?? null;
$body = file_get_contents('php://input');
$bodyArray = json_decode($body, true);
// variables de la peticion

switch ($requestMethod) {
    case 'POST':
        createUser($database, $bodyArray);
        break ;
    case  'GET':
        if ($id === null) {
            getUserList($database);
        }
        else {
            getUserDataById($id, $database);
        }
        break ;
    case 'PATCH':
        if ($id) {
            editUserData($database, $id, $bodyArray);
        }
        break ;
    case 'DELETE':
        if ($id) {
            deleteUser($database, $bodyArray);
        }
        break ;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'unauthorized method.']);
        break ;
} 
// router

function createUser($database, $body) : void {
    if (!isset($body['username'], $body['email'], $body['password'])) {
        http_response_code(400); // bad petition
        echo json_encode(["error" => "bad request"]);
        return ;
    }
    // compruebo que esten los campos y si no devuelvo peticion mal formada.

    $username = $body['username'];
    $email = $body['email'];
    $password = password_hash($body['password'], PASSWORD_DEFAULT);
    // password_hash() utiliza bcrypt para hashear la contra

    $secureQuest = $database->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
    $secureQuest->bindValue(":username", $username);
    $secureQuest->bindValue(":email", $email);
    $secureQuest->bindValue(":password", $password);
    // peticion segura para evitar sqliny, con clave valor

    try {
        $secureQuest->execute();
        echo json_encode(["success" => true, "message" => "user created"]);
    } catch (Exception $e) {
        http_response_code(500); // internal server error
        echo json_encode(["error" => "user can't be created...", "details" => $e->getMessage()]);
    }
    return ;
}
/* esta funciona haciendo una peticion POST, en la que el body
ha de tener el formato: "username:(nombre de usuario), email:(email xD),
password:(contrasenha) :D */

function getUserList($database) {
    $dbQuery = "SELECT id, username FROM users ORDER BY id ASC";
    $data = $database->query($dbQuery);
    $users = [];
    while ($rows = $data->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $rows;
    }
    echo json_encode($users);
}
/* this function retorna la lista de todos los usuarios
en la base de datos pero solo dara por ahora el id, el nombre
y la fecha en la que se ha creado, podria dar mas cosas
pero creo que sobran atope y seguridat y tal 

se accede por peticion GET a /users sin /(id) */

function getUserDataById($playerId, $database) {
    if (!is_numeric($playerId)) {
        http_response_code(404);
        echo json_encode(["error" => "invalid Id"]);
        return ;
    }
    $secureQuest = $database->prepare("SELECT id, username, created_at FROM users WHERE id = :id");
    $secureQuest->bindValue(":id", $playerId, SQLITE3_INTEGER);
    $data = $secureQuest->execute();
    $arrayData = $data->fetchArray(SQLITE3_ASSOC);
    if ($arrayData) {
        echo json_encode($arrayData);
    } else {
        http_response_code(404);
        echo json_encode(["error" => "user not found"]);
    }
    return ;
}
/* si la funcion de datos de un solo jugador con acceso por id
quieres usar una peticion de tipo GET a /users/(id) tendras que realizar */

function editUserData($database, $playerId, $body) {
    // editar la info de un user
}

/* funciona con peticion PATCH en users/id donde id
es el id del usuario a cambiar, ademas, en el body de la peticion
han de estar escritos los datos a modificar con el formato
" username:'nuevo nombre de usuario', email: 'nuevo email' "
*/

function deleteUser($database, $body) {
    echo($body);
    if (!isset($body['username'], $body['password'])) {
        http_response_code(400); // bad petition
        echo json_encode(["error" => "username/password not found"]);
        return ;
    }
    $username = $body['username'];
    $password = $body['password'];
    $secureQuest = $database->prepare("SELECT id, password FROM users WHERE username = :username");
    $secureQuest->bindValue(":username", $username);
    $userData = $secureQuest->execute();
    $userDataArray = $userData->fetchArray(SQLITE3_ASSOC);
    if (!$userDataArray) {
        http_response_code(404);
        echo json_encode(["error" => "user not found"]);
        return ;
    }
    if (!password_verify($password, $userData['password'])) {
        http_response_code(401); // no autorizado
        echo json_encode(["error" => "invalid password"]);
        return ;
    }
    $database->exec("DELETE FROM users WHERE id = {$userData['id']}");
    echo json_encode(["success" => "", "message" => "user deleted"]);
    return ;
}
/* funciona haciendo una peticion a /users de tipo DELETE, 
en el cuerpo de la misma tienen que estar escritos
correctamente el nombre de usuario y la contrasenha
en formato: "username:(nombre de usuario), password:(contrasenha)"*/

?>