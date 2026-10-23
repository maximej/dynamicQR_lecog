<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/QrDataStore.php';

use App\Controllers\QrDataStore;

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$id = trim((string)($_POST['id'] ?? ''));
$enabled = ($_POST['enabled'] ?? '') === '1';
$page = max(1, (int)($_POST['page'] ?? 1));
$search = trim((string)($_POST['search'] ?? ''));

$stmt = $pdo->prepare('SELECT redirect_url FROM qrcodes WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$qrCode = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$qrCode || empty($qrCode['redirect_url'])) {
    http_response_code(404);
    exit('Dynamic QR code not found.');
}

try {
    (new QrDataStore())->setTrackingEnabled($id, $enabled);
    $_SESSION['success'] = $enabled ? 'Scan tracking enabled.' : 'Scan tracking disabled.';
} catch (Throwable $e) {
    error_log('QR tracking update failed: ' . $e->getMessage());
    $_SESSION['error'] = 'Unable to update scan tracking.';
}

$location = '/list.php?page=' . $page;
if ($search !== '') {
    $location .= '&search=' . urlencode($search);
}
header('Location: ' . $location);
exit;
