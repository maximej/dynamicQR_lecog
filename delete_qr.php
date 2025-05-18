<?php
// delete_qr.php: Delete a QR code and its image
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    http_response_code(400);
    echo 'Invalid request.';
    exit;
}

$id = $_POST['id'];

// Get QR code image path
$stmt = $pdo->prepare('SELECT qr_code_path FROM qrcodes WHERE id = :id');
$stmt->execute(['id' => $id]);
$qr = $stmt->fetch(PDO::FETCH_ASSOC);

if ($qr && !empty($qr['qr_code_path']) && file_exists($qr['qr_code_path'])) {
    unlink($qr['qr_code_path']);
}

// Delete from database
$stmt = $pdo->prepare('DELETE FROM qrcodes WHERE id = :id');
$stmt->execute(['id' => $id]);

$_SESSION['success'] = 'QR code deleted.';
header('Location: /list.php');
exit;
