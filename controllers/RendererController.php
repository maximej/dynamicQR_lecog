<?php
namespace App\Controllers;

class RendererController
{
    public function renderMainPage($request = null, $response = null, $args = [])
    {
        $successMessage = $_SESSION['success'] ?? '';
        $errorMessage = $_SESSION['error'] ?? '';
        $qrCodePath = $_SESSION['qr_code_path'] ?? '';
        unset($_SESSION['success'], $_SESSION['error'], $_SESSION['qr_code_path']);

        ob_start();
        $menu = new \App\Controllers\MainMenuController();
        $menu->showMenu();
        $menuHtml = ob_get_clean();

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Renderer</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/index.css">
</head>
<body class="bg-light d-flex flex-column align-items-center justify-content-center vh-100">';
        $html .= $menuHtml;
        $html .= '<div class="text-center">
                <p class="text-success fs-4">' . htmlspecialchars($successMessage) . '</p>
                <p class="text-danger fs-5">' . htmlspecialchars($errorMessage) . '</p>
                <img src="' . htmlspecialchars($qrCodePath) . '" alt="QR Code" class="img-fluid border rounded shadow-sm">
            </div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        </body>
        </html>';

        if ($response && method_exists($response, 'getBody')) {
            $response->getBody()->write($html);
            return $response;
        } else {
            echo $html;
            return null;
        }
    }
}
