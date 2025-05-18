<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/UserListController.php';
require_once __DIR__ . '/controllers/MainMenuController.php';

use App\Controllers\UserListController;
use App\Controllers\MainMenuController;

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$menu = new MainMenuController();
$menu->showMenu();

$userList = new UserListController($pdo);
$request = (object)[];
$response = new class {
    public function getBody() { return $this; }
    public function write($str) { echo $str; }
};
$userList->renderListPage($request, $response, []);
exit;
