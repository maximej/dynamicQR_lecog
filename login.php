<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

use App\Controllers\AuthController;

$auth = new AuthController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle login POST
    $request = (object)['getParsedBody' => function() { return $_POST; }];
    $response = new class {
        public function withHeader($header, $value) { header("$header: $value"); return $this; }
        public function withStatus($code) { http_response_code($code); return $this; }
    };
    $auth->login($request, $response, []);
    exit;
} else {
    // Show login form
    $request = (object)[];
    $response = new class {
        public function getBody() { return $this; }
        public function write($str) { echo $str; }
    };
    $auth->showLoginForm($request, $response, []);
    exit;
}
