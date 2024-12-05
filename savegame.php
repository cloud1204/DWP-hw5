<?php
session_start(); // Start the session
header("Content-Type: application/json");
require 'db.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $userId = $_SESSION['user_id'];
    $currentChips = $data['currentChips'];
    $history = json_encode($data['history']); // Convert history array to JSON
    $round = $data['round'];

    try {
        // Insert or update game data for the user
        $stmt = $pdo->prepare("
            INSERT INTO game_data (user_id, current_chips, history, round)
            VALUES (:user_id, :current_chips, :history, :round)
            ON DUPLICATE KEY UPDATE
            current_chips = :current_chips, history = :history, round = :round
        ");
        $stmt->execute([
            'user_id' => $userId,
            'current_chips' => $currentChips,
            'history' => $history,
            'round' => $round
        ]);

        echo json_encode(['message' => 'Game data saved successfully.']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'An error occurred while saving game data.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
