<?php
// redirect.php for legacy or direct QR code redirection
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo 'Missing QR code ID.';
    exit;
}

$id = $_GET['id'];

// Load database config and connect
$config = require __DIR__ . '/config/app.php';
$environment = 'production';
$dbConfig = $config['environments'][$environment]['database'];
$dsn = $dbConfig['dsn'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->prepare('SELECT redirect_url FROM qrcodes WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['redirect_url']) {
        header('Location: ' . $result['redirect_url'], true, 302);
        exit;
    } else {
        // Redirect to main server 404 page
        header("HTTP/1.1 404 Not Found");
        include __DIR__ . '/public/404.html'; // fallback: if you have a custom 404 page
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database error: ' . htmlspecialchars($e->getMessage());
    exit;
}
