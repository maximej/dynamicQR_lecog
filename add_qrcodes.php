<?php
require_once 'config/database.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    for ($i = 0; $i < 50; $i++) {
        $id = uniqid();
        $url = 'https://example.com/' . $id;
        $path = 'qrcodes/' . $id . '.png';
        $redirect = rand(0, 1) ? $url : null;
        $createdAt = date('Y-m-d H:i:s', time() - rand(0, 100000));

        $stmt = $pdo->prepare("INSERT INTO qrcodes (id, original_url, qr_code_path, redirect_url, created_at) VALUES (:id, :url, :path, :redirect, :createdAt)");
        $stmt->execute([
            ':id' => $id,
            ':url' => $url,
            ':path' => $path,
            ':redirect' => $redirect,
            ':createdAt' => $createdAt
        ]);
    }

    echo "50 QR codes added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
