<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $_SESSION['error'] = 'Username and password are required.';
        header('Location: /userlist.php');
        exit;
    }
    // Check if username exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Username already exists.';
        header('Location: /userlist.php');
        exit;
    }
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');
    $stmt->execute(['username' => $username, 'password_hash' => $passwordHash]);
    $_SESSION['success'] = 'User added successfully!';
    header('Location: /userlist.php');
    exit;
} else {
    header('Location: /userlist.php');
    exit;
}
