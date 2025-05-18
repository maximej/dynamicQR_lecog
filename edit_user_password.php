<?php
session_start();
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $oldPassword = $_POST['old_password'] ?? '';

    // Fetch user
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    if (!$user) {
        echo json_encode(['status' => 'not_found']);
        exit;
    }

    // If root, check old password and confirmation
    if ($user['username'] === 'root') {
        if (!password_verify($oldPassword, $user['password_hash'])) {
            echo json_encode(['status' => 'wrong_password']);
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['status' => 'mismatch']);
            exit;
        }
    }
    // For all users, update password
    if ($user['username'] !== 'root' && $confirmPassword !== '' && $newPassword !== $confirmPassword) {
        echo json_encode(['status' => 'mismatch']);
        exit;
    }
    if ($newPassword === '') {
        echo json_encode(['status' => 'mismatch']);
        exit;
    }
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
    $stmt->execute(['password_hash' => $passwordHash, 'id' => $userId]);
    echo json_encode(['status' => 'success']);
    exit;
} else {
    echo json_encode(['status' => 'invalid']);
    exit;
}
