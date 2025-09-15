<?php

require_once 'header.php';
require_once 'utils.php';

$usersNum = $bodyArray['number'] ?? null;
$idQuest = 1;

if ($idQuest != 0 || checkDiff($id, $idQuest))
{
    switch ($requestMethod)
	{
        case GET:
            if ($usersNum)
                createTournament($id, $usersNum, $database);
            else
                searchPlayerByElo($id, $database);
            break;
        case POST:
            updateElo($bodyArray, $database);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'unauthorized method.']);
            break;
    }
}
else 
{
    http_response_code(403);
    echo json_encode(['error' => 'forbidden']);
    return ;
}

function updateElo($body, $database)
{
    $winnerId = $body['winner_id'];
    $loserId = $body['loser_id'];
    if (!$winnerId || !$loserId || !is_numeric($winnerElo) || !is_numeric($loserId))
		errorSend(400, 'bad request');

    $winnerElo = $database->querySingle("SELECT elo FROM users WHERE id = $winnerId");
    $loserElo = $database->querySingle("SELECT elo FROM users WHERE id = $loserId");
    if (!$winnerElo || !$loserElo)
		errorSend(500, 'internal server error');

    $expectedWinner = 1 / (1 + pow(10, ($loserElo - $winnerElo) / 400));
    $expectedLoser = 1 / (1 + pow(10, ($winnerElo - $loserElo) / 400));
    $newWinnerElo = $winnerElo + 32 * (1 - $expectedWinner);
    $newLoserElo = $loserElo + 32 * (0 - $expectedLoser);
    $preparedQueryW = $database->querySingle("UPDATE users SET elo = $newWinnerElo WHERE id = $winnerId");
    $preparedQueryL = $database->querySingle("UPDATE users SET elo = $newLoserElo WHERE id = $loserId");
    if (!$preparedQueryW || !$preparedQueryL)
		errorSend(500, 'internal server error');

    echo json_encode(['success', 'new_winner_elo' => $newWinnerElo, 'new_loser_elo' => $newLoserElo]);
}

function searchPlayerByElo($userId, $database)
{
    if (!is_numeric($userId))
		errorSend(400, 'bad petition');
}

function createTournament($userId, $usersNum, $database)
{
    if (!is_numeric($userId) || !is_numeric($usersNum))
		errorSend(400, 'bad petition');
}

?>