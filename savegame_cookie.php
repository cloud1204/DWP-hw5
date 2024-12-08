<?php
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $currentChips = $data['currentChips'] ?? null;
    $history = $data['history'] ?? null;
    $round = $data['round'] ?? null;

    if (is_null($currentChips) || is_null($history) || is_null($round)) {
        echo json_encode(['error' => 'Incomplete game data.']);
        exit;
    }

    // Serialize the game data
    $gameData = json_encode([
        'currentChips' => $currentChips,
        'history' => $history,
        'round' => $round
    ]);

    // Set the cookie
    setcookie('gameData', $gameData, time() + 3600, '/'); // Expires in 1 hour
    echo json_encode(['message' => 'Game data saved to cookie.']);
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
