<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

use App\Controllers\AuthController;

$auth = new AuthController($pdo);
$auth->logout(); // This will handle session destroy and redirect to login.php
exit;
