<?php
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_COOKIE['gameData'])) {
        // Parse the cookie JSON
        $gameData = json_decode($_COOKIE['gameData'], true);
        echo json_encode([
            'message' => 'Game data loaded from cookie.',
            'data' => $gameData
        ]);
    } else {
        echo json_encode(['error' => 'No game data found in cookie.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
