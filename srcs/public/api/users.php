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
// variables de la peticion
// error_log(print_r($id, true));

if ($requestMethod == 'POST') {
    createUser($database, $bodyArray);
    return ;
}

if ($idQuest != 0 || checkDiff($id, $idQuest)) {
    switch ($requestMethod) {
        case  'GET':
            if (!$id) {
                getUserList($database);
            }
            else {
                getUserDataById($id, $database);
            }
            break ;
        case 'PATCH':
            if ($id) {
                $password = $bodyArray['password'] ?? null;
                if ($password) {
                    editPassword($database, $id, $password);
                } else {
                    editUserData($database, $id, $bodyArray);
                }
                break ;
            }
            break ;
        case 'DELETE':
            if ($id) {
                deleteUser($database, $id);
            }
            break ;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'unauthorized method.']);
            break ;
    } 
} else {
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    return ;
}

function createUser($database, $body): void {
    if (!isset($body['username'], $body['email'], $body['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Bad request. Missing fields.']);
        return;
    }
    
    $username = $body['username'];
    $email = $body['email'];
    $password = password_hash($body['password'], PASSWORD_DEFAULT);
    
    $checkQuery = $database->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $checkQuery->bindValue(':username', $username);
    $checkQuery->bindValue(':email', $email);
    $result = $checkQuery->execute();

    if ($result->fetchArray(SQLITE3_ASSOC)) {
        http_response_code(409); // Conflictu
        echo json_encode(['error' => 'username/email used in other account']);
        return;
    }

    try {
        $secureQuest = $database->prepare("INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)");
        if (!$secureQuest) {
            http_response_code(500);
            echo json_encode(['error' => 'Prepare failed', 'details' => $database->lastErrorMsg()]);
            return;
        }
        $secureQuest->bindValue(':username', $username);
        $secureQuest->bindValue(':email', $email);
        $secureQuest->bindValue(':password_hash', $password);
        $secureQuest->execute();
        echo json_encode(['success' => true, 'message' => 'User created.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'error' => "User can't be created...",
            'details' => $e->getMessage()
        ]);
    }
}

function getUserList($database) {
    $dbQuery = "SELECT id, username, elo FROM users ORDER BY id ASC";
    $data = $database->query($dbQuery);
    $users = [];
    while ($rows = $data->fetchArray(SQLITE3_ASSOC)) {
        $users[] = $rows;
    }
    echo json_encode($users);
}

function getUserDataById($playerId, $database) {
    if (!is_numeric($playerId)) {
        http_response_code(404);
        echo json_encode(['error' => 'invalid Id']);
        return ;
    }
    $secureQuest = $database->prepare("SELECT id, username, elo FROM users WHERE id = :id");
    $secureQuest->bindValue(":id", $playerId, SQLITE3_INTEGER);
    $data = $secureQuest->execute();
    $arrayData = $data->fetchArray(SQLITE3_ASSOC);
    if ($arrayData) {
        echo json_encode($arrayData);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'user not found']);
    }
    return ;
}

function editUserData($database, $playerId, $body) {
    if (!is_numeric($playerId)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid user ID']);
        return ;
    }
    $updatedData = [];
    $parameters = [];
    if (isset($body['username'])) {
        $updatedData[] = "username = :username";
        $parameters[':username'] = $body['username'];
    }
    if (isset($body['email'])) {
        $updatedData[] = "email = :email";
        $parameters[':email'] = $body['email'];
    }
    if (empty($updatedData)) {
        http_response_code(400);
        echo json_encode(['error' => 'no fields to be updated']);
        return ;
    }
    $query = "UPDATE users SET " . implode(', ', $updatedData) . " WHERE id = :id";
    $preparedQuery = $database->prepare($query);
    foreach ($parameters as $key => $value) {
        $preparedQuery->bindValue($key, $value);
    }
    $preparedQuery->bindValue(':id', $playerId, SQLITE3_INTEGER);
    $preparedQuery->execute();
    if ($database->changes() > 0) {
        echo json_encode(['success' => 1, 'message' => 'user updated']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'user not found or no changes made']);
    }
    return ;
}

function deleteUser($database, $playerId) {
    if (!is_numeric($playerId)) {
        http_response_code(400);
        echo json_encode(['error' => 'invalid user ID']);
        return ;
    }
    $preparedQuery = $database->prepare("DELETE FROM users WHERE id = :id");
    $preparedQuery->bindValue(':id', $playerId, SQLITE3_INTEGER);
    $res = $preparedQuery->execute();
    if ($database->changes() > 0) {
        echo json_encode(['success' => 1, 'message' => 'user deleted']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'user not found or already deleted']);
    }
    return ;
}

function editPassword($database, $playerId, $password) {
    $preparedQuery = $database->prepare("INSERT INTO users (id, password_hash) VALUES (:playerId, :pass)");
    $preparedQuery->bindValue(":playerId", $playerId);
    $hashedPass = password_hash($password, PASSWORD_DEFAULT);
    $preparedQuery->bindValue(":pass", $hashedPass);
    $res = $preparedQuery->execute();
    if (!$res) {
        http_response_code(500); // server internal error
        echo json_encode(['error' => 'internal server error']);
        return ;
    }
    echo json_encode(['success' => 'password updated']);
    return ;
}

?>