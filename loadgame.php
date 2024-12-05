<?php
session_start(); // Start the session
header("Content-Type: application/json");
require 'db.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized. Please log in.']);
    exit;
}

// Get the user ID from the session
$userId = $_SESSION['user_id'];

try {
    // Fetch the latest game data for the user from the database
    $stmt = $pdo->prepare("SELECT current_chips, history, round FROM game_data WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(['user_id' => $userId]);

    $gameData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($gameData) {
        echo json_encode(['message' => 'Game data loaded successfully.', 'data' => $gameData]);
    } else {
        echo json_encode(['error' => 'No game data found for this user.']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'An error occurred while fetching game data.']);
}
?>
