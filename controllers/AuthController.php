<?php
namespace App\Controllers;

class AuthController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function showLoginForm($request = null, $response = null, $args = [])
    {
        $errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
        unset($_SESSION['error']); // Clear the error message after displaying it

        $formHtml = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Login</title>
            <link rel="stylesheet" href="public/index.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
            <link rel="apple-touch-icon" sizes="180x180" href="/public/fav/favicon/apple-touch-icon.png">
            <link rel="icon" type="image/png" sizes="32x32" href="/public/fav/favicon/favicon-32x32.png">
            <link rel="icon" type="image/png" sizes="16x16" href="/public/fav/favicon/favicon-16x16.png">
            <link rel="manifest" href="/public/fav/favicon/site.webmanifest">
            <link rel="mask-icon" href="/public/fav/favicon/safari-pinned-tab.svg" color="#5bbad5">
            <link rel="shortcut icon" href="/public/fav/favicon/favicon.ico">
            <meta name="msapplication-TileColor" content="#da532c">
            <meta name="msapplication-config" content="/public/fav/favicon/browserconfig.xml">
            <meta name="theme-color" content="#ffffff">
        </head>
        <body class="bg-light">
            <div class="container d-flex justify-content-center align-items-center vh-100">
                <div class="card shadow p-4">
                    <div class="container mt-5">
                        <img src="/public/COG_Logo_1000.jpg" alt="Logo" class="login-logo">
                        <h1 class="text-center">Login to QR.LeCOG.fr</h1>';

        if ($errorMessage) {
            $formHtml .= '<div class="alert alert-danger">' . htmlspecialchars($errorMessage) . '</div>';
        }

        $formHtml .= '<form method="POST" action="/login.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username:</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password:</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </body>
        </html>';

        if ($response && method_exists($response, 'getBody')) {
            $response->getBody()->write($formHtml);
            return $response;
        } else {
            echo $formHtml;
            return null;
        }
    }

    public function login($request = null, $response = null, $args = [])
    {
        $parsedBody = $_POST;
        $username = $parsedBody['username'] ?? '';
        $password = $parsedBody['password'] ?? '';

        if (!empty($username) && !empty($password)) {
            try {
                $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username');
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    header('Location: /index.php');
                    exit;
                } else {
                    $_SESSION['error'] = 'Invalid username or password.';
                    header('Location: /login.php');
                    exit;
                }
            } catch (\Exception $e) {
                $_SESSION['error'] = 'An error occurred. Please try again later.';
                header('Location: /login.php');
                exit;
            }
        } else {
            $_SESSION['error'] = 'Please fill in all fields.';
            header('Location: /login.php');
            exit;
        }
    }

    public function logout($request = null, $response = null, $args = [])
    {
        session_unset();
        session_destroy();
        header('Location: /login.php');
        exit;
    }
}
