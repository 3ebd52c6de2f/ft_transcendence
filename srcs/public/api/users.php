<?php

require_once 'utils.php';
require_once 'header.php';

switch ($requestMethod)
{
    case 'POST':
        createUser($database, $bodyArray);     
        break ;
    case 'GET':
        if (!$id)                         
            getUserList($database);
		else                            
            getUserDataById($id, $database);
        break ;
    case 'PATCH':
        if ($id)                            
            editUserData($database, $id, $bodyArray);
        break ;
    case 'DELETE':
        if ($id)                            
            deleteUser($database, $id);
        break ;
    default:
        http_response_code(405);               
        echo json_encode(['error' => 'unauthorized method.']); 
} 

/* creamos un nuevo user */

function createUser($database, $body): void 
{
    if (!isset($body['username'], $body['email'], $body['password']))
		errorSend(400, 'Bad request. Missing fields.');

    $username = $body['username'];
    $email = $body['email'];      
    $password = password_hash($body['password'], PASSWORD_DEFAULT);
    $checkQuery = $database->prepare("SELECT id FROM users WHERE username = :username OR email = :email"); 
    $checkQuery->bindValue(':username', $username);
    $checkQuery->bindValue(':email', $email);
    $result = $checkQuery->execute();
    if ($result->fetchArray(SQLITE3_ASSOC))
		errorSend(409, 'username/email used in other account');

    try 
	{
        $secureQuest = $database->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
        if (!$secureQuest)
			errorSend(500, 'Prepare failed', $database->lastErrorMsg());

        $secureQuest->bindValue(':username', $username);
        $secureQuest->bindValue(':email', $email);
        $secureQuest->bindValue(':password_hash', $password);
        $secureQuest->execute();
        echo json_encode(['success' => true, 'message' => 'User created.']);
    } 
	catch (Exception $e)
	{
        errorSend(500, 'Prepare failed', $database->lastErrorMsg());
    }
}

/* esta funciona haciendo una peticion POST, en la que el body
ha de tener el formato: "username:(nombre de usuario), email:(email xD),
password:(contrasenha) :D */

function getUserList($database)
{
    $dbQuery = "SELECT id, username FROM users ORDER BY id ASC"; 
    $data = $database->query($dbQuery);             
    $users = [];                                    
    while ($rows = $data->fetchArray(SQLITE3_ASSOC))
        $users[] = $rows;
    echo json_encode($users);                       
}
/* this function retorna la lista de todos los usuarios
en la base de datos pero solo dara por ahora el id, el nombre
y la fecha en la que se ha creado, podria dar mas cosas
pero creo que sobran atope y seguridat y tal 

se accede por peticion GET a /users sin ?id=(id) */

function getUserDataById($playerId, $database)
{
    if (!is_numeric($playerId))
		errorSend(404, 'invalid Id');

    $secureQuery = $database->prepare("SELECT id, username FROM users WHERE id = :id");
    $secureQuery->bindValue(":id", $playerId, SQLITE3_INTEGER);
    $data = $secureQuery->execute();	// data es un objeto tipo SQLite3Result, es un objeto cursor, no contiene aún las filas en un formato usable.
    $arrayData = $data->fetchArray(SQLITE3_ASSOC);	// Necesitas $data en valores PHP. SQLITE3_ASSOC dice: dame la fila como array asociativo (claves = nombres de columnas).
    if ($arrayData)
        echo json_encode($arrayData);
	else
		errorSend(404, 'user not found');
    return;
}
/* si la funcion de datos de un solo jugador con acceso por id
quieres usar una peticion de tipo GET a /users?id=(id) tendras que realizar */

function editUserData($database, $playerId, $body)
{
    if (!is_numeric($playerId))
		errorSend(400, 'invalid user ID');

    $updatedData = [];
    $parameters = []; 
    if (isset($body['username']))
	{                  
        $updatedData[] = "username = :username";
        $parameters[':username'] = $body['username'];
    }
    if (isset($body['email']))
	{                     
        $updatedData[] = "email = :email";
        $parameters[':email'] = $body['email'];
    }

    if (empty($updatedData))
		errorSend(400, 'no fields to be updated');

    $query = "UPDATE users SET " . implode(', ', $updatedData) . " WHERE id = :id"; 
    $preparedQuery = $database->prepare($query);     
    foreach ($parameters as $key => $value)
        $preparedQuery->bindValue($key, $value);
    $preparedQuery->bindValue(':id', $playerId, SQLITE3_INTEGER); 
    $preparedQuery->execute();                       
    if ($database->changes() > 0)                 
        echo json_encode(['success' => 1, 'message' => 'user updated']);
	else
		errorSend(404, 'user not found or no changes made'); 
    return ;
}

/* funciona con peticion PATCH en users/id donde id
es el id del usuario a cambiar, ademas, en el body de la peticion
han de estar escritos los datos a modificar con el formato
" username:'nuevo nombre de usuario', email: 'nuevo email' "

el tema de la contra ira en security.php
*/

function deleteUser($database, $playerId)
{          
    if (!is_numeric($playerId))
		errorSend(400, 'invalid user ID'); 

    $preparedQuery = $database->prepare("DELETE FROM users WHERE id = :id"); 
    $preparedQuery->bindValue(':id', $playerId, SQLITE3_INTEGER);            
    $res = $preparedQuery->execute();                 
    if ($database->changes() > 0)
        echo json_encode(['success' => 1, 'message' => 'user deleted']);
	else
		errorSend(404, 'user not found or already deleted'); 
    return ;
}
/* funciona haciendo una peticion a /users de tipo DELETE con el id*/

?>
