<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/QrCodeController.php';
require_once __DIR__ . '/controllers/MainMenuController.php';
require_once __DIR__ . '/vendor/autoload.php';

use App\Controllers\QrCodeController;
use App\Controllers\MainMenuController;

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$menu = new MainMenuController();
$menu->showMenu();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qr = new QrCodeController();
    $request = (object)['getParsedBody' => function() { return $_POST; }];
    $response = new class {
        public function withHeader($header, $value) { header("$header: $value"); return $this; }
        public function withStatus($code) { http_response_code($code); return $this; }
    };
    $qr->generate($request, $response, []);
    exit;
} else {
    header('Location: /create.php');
    exit;
}
