<?php
// Public redirect endpoint. Stores only an aggregate count and UTC timestamp.
require_once __DIR__ . '/controllers/QrDataStore.php';

use App\Controllers\QrDataStore;

$store = new QrDataStore();
$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
$slug = isset($_GET['slug']) ? QrDataStore::normalizeSlug((string)$_GET['slug']) : '';

if ($id === '' && $slug !== '') {
    try {
        QrDataStore::validateSlug($slug);
        $id = $store->resolveSlug($slug) ?? '';
    } catch (Throwable $e) {
        $id = '';
    }
}

if ($id === '') {
    http_response_code(404);
    include __DIR__ . '/public/404.html';
    exit;
}

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
        try {
            if ($store->isTrackingEnabled($id)) {
                $store->recordView($id);
            }
        } catch (Throwable $e) {
            // A statistics write must never prevent the redirect itself.
            error_log('QR statistics write failed: ' . $e->getMessage());
        }
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
    echo 'The redirect service is temporarily unavailable.';
    error_log('QR redirect database error: ' . $e->getMessage());
    exit;
}
