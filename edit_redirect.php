<?php
// edit_redirect.php: Edit the redirect URL for a QR code
session_start();
require_once __DIR__ . '/config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo 'Missing QR code ID.';
    exit;
}

$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['id'], $_POST['redirect_url'])) {
    $id = $_GET['id'];
    $redirectUrl = $_POST['redirect_url'];
    $search = isset($_POST['search']) ? $_POST['search'] : '';
    $page = isset($_POST['page']) ? $_POST['page'] : 1;
    // Update the QR code redirect URL in the database
    require_once __DIR__ . '/config/database.php';
    $pdo = new \PDO($dsn, $username, $password, $options);
    $stmt = $pdo->prepare('UPDATE qrcodes SET redirect_url = :redirect_url WHERE id = :id');
    $stmt->execute(['redirect_url' => $redirectUrl, 'id' => $id]);
    // Redirect back to the list with search and page params
    $params = [];
    if ($search !== '') $params[] = 'search=' . urlencode($search);
    if ($page > 1) $params[] = 'page=' . urlencode($page);
    $query = $params ? ('?' . implode('&', $params)) : '';
    header('Location: /list.php' . $query);
    exit;
}

// Fetch current redirect_url
$stmt = $pdo->prepare('SELECT redirect_url FROM qrcodes WHERE id = :id');
$stmt->execute(['id' => $id]);
$qr = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$qr) {
    http_response_code(404);
    echo 'QR code not found.';
    exit;
}
$currentUrl = htmlspecialchars($qr['redirect_url'] ?? '', ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Redirect URL</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/index.css">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Redirect URL</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="redirect_url" class="form-label">Redirect URL</label>
            <input type="url" class="form-control" id="redirect_url" name="redirect_url" value="<?php echo $currentUrl; ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="/list.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
