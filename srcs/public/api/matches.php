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
$usersNum = $bodyArray['number'] ?? null;
// variables de la peticion
// error_log(print_r($id, true));

if ($idQuest != 0 || checkDiff($id, $idQuest)) {
    switch ($requestMethod) {
        case GET:
            if ($usersNum)
                createTournament($id, $usersNum, $database);
            else
                searchPlayerByElo($id, $database);
            break ;
        case POST:
            updateElo($bodyArray, $database);
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

function updateElo($body, $database) {
    $winnerId = $body['winner_id'];
    $loserId = $body['loser_id'];
    if (!$winnerId || !$loserId || !is_numeric($winnerElo) || !is_numeric($loserId)) {
        http_response_code(400);
        echo json_encode(['error' => 'bad request']);
        return ;
    }
    $winnerElo = $database->querySingle("SELECT elo FROM users WHERE id = $winnerId");
    $loserElo = $database->querySingle("SELECT elo FROM users WHERE id = $loserId");
    if (!$winnerElo || !$loserElo) {
        http_response_code(500);
        echo json_encode(['error' => 'internal server error']);
        return ;
    }
    $expectedWinner = 1 / (1 + pow(10, ($loserElo - $winnerElo) / 400));
    $expectedLoser = 1 / (1 + pow(10, ($winnerElo - $loserElo) / 400));
    $newWinnerElo = $winnerElo + 32 * (1 - $expectedWinner);
    $newLoserElo = $loserElo + 32 * (0 - $expectedLoser);
    $preparedQueryW = $database->querySingle("UPDATE users SET elo = $newWinnerElo WHERE id = $winnerId");
    $preparedQueryL = $database->querySingle("UPDATE users SET elo = $newLoserElo WHERE id = $loserId");
    if (!$preparedQueryW || !$preparedQueryL) {
        http_response_code(500);
        echo json_encode(['error' => 'internal server error']);
        return ;
    }
    echo json_encode(['success', 'new_winner_elo' => $newWinnerElo, 'new_loser_elo' => $newLoserElo]);
}

function searchPlayerByElo($userId, $database) {
    if (!is_numeric($userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'bad petition']);
        return ;
    }
}

function createTournament($userId, $usersNum, $database) {
    if (!is_numeric($userId) || !is_numeric($usersNum)) {
        http_response_code(400);
        echo json_encode(['error' => 'bad petition']);
        return ;
    }
}

?>