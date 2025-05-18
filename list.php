<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/QrListController.php';
require_once __DIR__ . '/controllers/MainMenuController.php';

use App\Controllers\QrListController;
use App\Controllers\MainMenuController;

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$menu = new MainMenuController();
$menu->showMenu();

$qrList = new QrListController($pdo);
$request = (object)[];
$response = new class {
    public function getBody() { return $this; }
    public function write($str) { echo $str; }
};
$qrList->renderListPage($request, $response, []);
exit;
