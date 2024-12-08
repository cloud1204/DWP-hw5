<?php
session_start(); // Start the session
header("Content-Type: application/json");
require 'db.php'; // Include the database connection

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo json_encode(['error' => 'Username and password are required.']);
        exit;
    }

    try {
        // Check if the user exists
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            setcookie('gameData', '', time() - 3600, '/');
            echo json_encode(['message' => 'Login successful.', 'user_id' => $user['id']]);
        } else {
            echo json_encode(['error' => 'Invalid username or password.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'An error occurred while processing your request.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
