<?php

require_once 'header.php';

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

