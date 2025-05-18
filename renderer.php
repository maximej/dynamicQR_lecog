<?php
session_start();
require_once __DIR__ . '/controllers/RendererController.php';
require_once __DIR__ . '/controllers/MainMenuController.php';

use App\Controllers\RendererController;
use App\Controllers\MainMenuController;

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$menu = new MainMenuController();
$menu->showMenu();

$renderer = new RendererController();
$request = (object)[];
$response = new class {
    public function getBody() { return $this; }
    public function write($str) { echo $str; }
};
$renderer->renderMainPage($request, $response, []);
exit;
