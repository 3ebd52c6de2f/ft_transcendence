
<?php

require_once 'utils.php';
require_once 'header.php';

$idQuest = 1;

if ($idQuest != 0 || checkDiff($id, $idQuest))
{
    switch ($requestMethod)
	{
        case 'GET':
 		if ($id)
		{
		     friendsLadderList($id, $database);
		     break ;
		}
		else 
		{
		     globalLadderList($database);
		     break ;
		}
        default:
			errorSend(405, 'unauthorized method.');
    }
}
    
    
function friendsLadderList($id, $database)
{
    $preparedQuery = $database->prepare("SELECT u.id, u.username, u.elo FROM users u INNER JOIN friends f 
        ON (u.id = f.friend_id OR u.id = f.user_id) WHERE $id IN (f.user_id, f.friend_id)
        AND u.id != $id ORDER BY u.elo DESC");
    $res = $preparedQuery->execute();
    if (!$res)
		errorSend(500, 'internal server error');

    $data = [];
    while ($array = $res->fetchArray(SQLITE3_ASSOC))
        $data[] = $array;

    echo json_encode($data, JSON_PRETTY_PRINT);
    return ;
}

function globalLadderList($database)
{
    $preparedQuery = $database->prepare("SELECT id, username, elo FROM users ORDER BY elo DESC");
    $res = $preparedQuery->execute();
    if (!$res)
		errorSend(500, 'internal server error');

    $data = [];
    while ($array = $res->fetchArray(SQLITE3_ASSOC))
        $data[] = $array;

    echo json_encode($data, JSON_PRETTY_PRINT);
    return ;
}

?>

