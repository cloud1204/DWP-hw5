<?php
header("Content-Type: application/json");
require 'db.php'; // Include the database connection

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true); // Decode JSON payload

    $username = trim($data['username'] ?? ''); // Use $data instead of $_POST
    $password = trim($data['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo json_encode(['error' => 'Username and password are required.']);
        exit;
    }

    try {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $userExists = $stmt->fetchColumn();

        if ($userExists) {
            echo json_encode(['error' => 'Username is already taken.']);
            exit;
        }

        // Hash the password for security
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Insert the new user into the database
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $stmt->execute([
            'username' => $username,
            'password' => $hashedPassword
        ]);

        echo json_encode(['message' => 'Registration successful.']);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'An error occurred while processing your request.']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
