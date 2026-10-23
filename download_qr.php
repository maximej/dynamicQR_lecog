<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$id = trim((string)($_GET['id'] ?? ''));
$format = strtolower(trim((string)($_GET['format'] ?? 'jpeg')));
if ($id === '' || !in_array($format, ['jpg', 'jpeg'], true)) {
    http_response_code(400);
    exit('Invalid download request.');
}

$stmt = $pdo->prepare('SELECT qr_code_path FROM qrcodes WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$qrCode = $stmt->fetch(PDO::FETCH_ASSOC);

$qrDirectory = realpath(__DIR__ . '/qrcodes');
$sourcePath = $qrCode ? realpath(__DIR__ . '/' . ltrim((string)$qrCode['qr_code_path'], '/\\')) : false;
if ($qrDirectory === false || $sourcePath === false || !str_starts_with($sourcePath, $qrDirectory . DIRECTORY_SEPARATOR) || !is_file($sourcePath)) {
    http_response_code(404);
    exit('QR code image not found.');
}

$source = @imagecreatefrompng($sourcePath);
if ($source === false) {
    http_response_code(500);
    exit('Unable to read QR code image.');
}

$width = imagesx($source);
$height = imagesy($source);
$jpeg = imagecreatetruecolor($width, $height);
$white = imagecolorallocate($jpeg, 255, 255, 255);
imagefill($jpeg, 0, 0, $white);
imagecopy($jpeg, $source, 0, 0, 0, 0, $width, $height);

header('Content-Type: image/jpeg');
header('Content-Disposition: attachment; filename="qr-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $id) . '.jpg"');
header('Cache-Control: private, no-store');
imagejpeg($jpeg, null, 95);

imagedestroy($jpeg);
imagedestroy($source);
exit;
