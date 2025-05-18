<?php
require_once 'config/database.php';

try {
    // Drop existing tables if they exist
    $pdo->exec('DROP TABLE IF EXISTS users');
    $pdo->exec('DROP TABLE IF EXISTS qrcodes');

    // Create qrcodes table
    $pdo->exec('CREATE TABLE IF NOT EXISTS qrcodes (
        id VARCHAR(50) PRIMARY KEY,
        original_url TEXT NOT NULL,
        qr_code_path TEXT NOT NULL,
        redirect_url VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');

    // Create users table
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');

    // Check if the users table is empty and insert default users if necessary
    $checkUserQuery = "SELECT COUNT(*) as user_count FROM users";
    $result = $pdo->query($checkUserQuery);

    if ($result) {
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if ($row['user_count'] == 0) {
            $usernames = ['root', 'user'];
            $defaultPassword = password_hash('admin', PASSWORD_BCRYPT);
            $insertUserQuery = "INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)";
            $stmt = $pdo->prepare($insertUserQuery);
            foreach ($usernames as $username) {
                $stmt->execute(['username' => $username, 'password_hash' => $defaultPassword]);
            }
            echo "Default users 'root' and 'user' created successfully.\n";
        } else {
            echo "Users table already has entries. No default user created.\n";
        }
    } else {
        echo "Error checking users table.\n";
    }

    echo "Database setup completed successfully.";
} catch (PDOException $e) {
    die('Error setting up database: ' . $e->getMessage());
}
