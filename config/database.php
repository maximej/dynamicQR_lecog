<?php
require_once 'config/app.php';

// Load configuration
$config = require 'config/app.php';

// Detect environment based on server name
if (isset($_SERVER['SERVER_NAME']) && (strpos($_SERVER['SERVER_NAME'], 'localhost') !== false || $_SERVER['SERVER_NAME'] === '127.0.0.1')) {
    $environment = 'development';
} else {
    $environment = 'production';
}

// Load the appropriate database configuration
$dbConfig = $config['environments'][$environment]['database'];

$dsn = $dbConfig['dsn'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
