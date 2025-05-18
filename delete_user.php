<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    if ($userId <= 0) {
        $_SESSION['error'] = 'User ID is required.';
        header('Location: /userlist.php');
        exit;
    }
    // Prevent deleting the first user (id=1)
    if ($userId == 1) {
        $_SESSION['error'] = 'Cannot delete the default user.';
        header('Location: /userlist.php');
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute(['id' => $userId]);
    $_SESSION['success'] = 'User deleted successfully!';
    header('Location: /userlist.php');
    exit;
} else {
    header('Location: /userlist.php');
    exit;
}
