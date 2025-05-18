<?php
namespace App\Controllers;

class QrListController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function renderListPage($request = null, $response = null, $args = [])
    {
        // Handle search query
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * 10;

        if ($search !== '') {
            $query = "SELECT COUNT(*) as total FROM qrcodes WHERE id LIKE :search OR original_url LIKE :search OR redirect_url LIKE :search";
            $totalStmt = $this->pdo->prepare($query);
            $totalStmt->execute(['search' => "%$search%"]);
            $totalRows = $totalStmt->fetch()['total'];

            $query = "SELECT id, original_url, qr_code_path, redirect_url, created_at FROM qrcodes WHERE id LIKE :search OR original_url LIKE :search OR redirect_url LIKE :search ORDER BY created_at DESC LIMIT 10 OFFSET :offset";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':search', "%$search%", \PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $qrCodes = $stmt->fetchAll();
        } else {
            $query = "SELECT COUNT(*) as total FROM qrcodes";
            $totalStmt = $this->pdo->query($query);
            $totalRows = $totalStmt->fetch()['total'];

            $query = "SELECT id, original_url, qr_code_path, redirect_url, created_at FROM qrcodes ORDER BY created_at DESC LIMIT 10 OFFSET :offset";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            $qrCodes = $stmt->fetchAll();
        }

        // Determine if there are more results for pagination
        $hasMore = ($offset + count($qrCodes)) < $totalRows;

        ob_start();
        $menu = new \App\Controllers\MainMenuController();
        $menu->showMenu();
        $menuHtml = ob_get_clean();

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code List</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-light">';
        $html .= $menuHtml;
        $html .= '<div class="container mt-5">
            <h1 class="text-center mb-4">QR Code List</h1>';


        // Search form and pagination controls (centered)
        $html .= '<div class="d-flex flex-column align-items-center mb-3">';
        $html .= '<form class="row g-2 align-items-center justify-content-center mb-2" method="GET" action="/list.php">';
        $html .= '<div class="col-auto">';
        $html .= '<input type="text" name="search" class="form-control" style="min-width:220px;max-width:350px;" placeholder="Search by ID, original URL, or redirect URL" value="' . htmlspecialchars($search) . '" />';
        $html .= '</div>';
        $html .= '<div class="col-auto">';
        $html .= '<button type="submit" class="btn btn-primary btn-search-mt0">Search</button>';
        if ($search !== '') {
            $html .= '<a href="/list.php" class="btn btn-secondary ms-2">Clear</a>';
        }
        $html .= '</div>';
        $html .= '</form>';
        // Pagination controls
        $from = $offset + 1;
        $to = $offset + count($qrCodes);
        if ($totalRows === 0) {
            $from = 0;
            $to = 0;
        }
        $html .= '<div class="d-flex align-items-center justify-content-center mb-2" style="gap:0.5rem;">';
        // Previous button
        if ($page > 1) {
            $prevUrl = '/list.php?page=' . ($page - 1);
            if ($search !== '') $prevUrl .= '&search=' . urlencode($search);
            $html .= '<a href="' . $prevUrl . '" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Previous</a>';
        } else {
            $html .= '<button class="btn btn-secondary" disabled><i class="fas fa-arrow-left"></i> Previous</button>';
        }
        // Counter
        $html .= '<span class="text-muted px-2">' . $from . ' - ' . $to . ' / ' . $totalRows . '</span>';
        // Next button
        if ($hasMore) {
            $nextUrl = '/list.php?page=' . ($page + 1);
            if ($search !== '') $nextUrl .= '&search=' . urlencode($search);
            $html .= '<a href="' . $nextUrl . '" class="btn btn-secondary">Next <i class="fas fa-arrow-right"></i></a>';
        } else {
            $html .= '<button class="btn btn-secondary" disabled>Next <i class="fas fa-arrow-right"></i></button>';
        }
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<table class="table table-bordered table-hover text-center align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>QR Code</th>
                            <th>Original URL</th>
                            <th>Redirect URL</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>';

        // Get current user username
        $currentUser = null;
        if (isset($_SESSION['user_id'])) {
            $stmtUser = $this->pdo->prepare('SELECT username FROM users WHERE id = :id LIMIT 1');
            $stmtUser->execute(['id' => $_SESSION['user_id']]);
            $currentUser = $stmtUser->fetch();
        }

        foreach ($qrCodes as $qrCode) {
            $editBtn = '';
            if (!empty($qrCode['redirect_url'])) {
                $editBtn = '<button type="button" class="btn btn-sm btn-outline-primary me-1" title="Edit Redirect" data-bs-toggle="modal" data-bs-target="#editRedirectModal-' . htmlspecialchars($qrCode['id']) . '"><i class="fas fa-edit"></i></button>';
                // Modal for editing redirect URL
                $html .= '<div class="modal fade" id="editRedirectModal-' . htmlspecialchars($qrCode['id']) . '" tabindex="-1" aria-labelledby="editRedirectLabel-' . htmlspecialchars($qrCode['id']) . '" aria-hidden="true">'
                    . '<div class="modal-dialog">'
                    . '<div class="modal-content">'
                    . '<form method="POST" action="/edit_redirect.php?id=' . urlencode($qrCode['id']) . '">' 
                    . '<input type="hidden" name="search" value="' . htmlspecialchars($search) . '">' 
                    . '<input type="hidden" name="page" value="' . htmlspecialchars($page) . '">' 
                    . '<div class="modal-header">'
                    . '<h5 class="modal-title" id="editRedirectLabel-' . htmlspecialchars($qrCode['id']) . '">Edit Redirect URL</h5>'
                    . '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>'
                    . '</div>'
                    . '<div class="modal-body">'
                    . '<div class="mb-3">'
                    . '<label for="redirect_url_' . htmlspecialchars($qrCode['id']) . '" class="form-label">Redirect URL</label>'
                    . '<input type="url" class="form-control" id="redirect_url_' . htmlspecialchars($qrCode['id']) . '" name="redirect_url" value="' . htmlspecialchars($qrCode['redirect_url']) . '" required>'
                    . '</div>'
                    . '</div>'
                    . '<div class="modal-footer">'
                    . '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>'
                    . '<button type="submit" class="btn btn-primary">Save</button>'
                    . '</div>'
                    . '</form>'
                    . '</div>'
                    . '</div>'
                    . '</div>';
            }
            // Only show delete button/modal if user is root
            $deleteBtn = '';
            if ($currentUser && $currentUser['username'] === 'root') {
                $deleteBtn = '<form method="POST" action="/delete_qr.php" style="display:inline;">
                    <input type="hidden" name="id" value="' . htmlspecialchars($qrCode['id']) . '">
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm(\'Delete this QR code?\')"><i class="fas fa-trash"></i></button>
                </form>';
            }
            $html .= '<tr>
                <td><img src="' . htmlspecialchars($qrCode['qr_code_path'] ?? '') . '" alt="QR Code" class="qr-code-img"></td>';
            // Make original_url and redirect_url clickable and open in new tab
            $originalUrl = htmlspecialchars($qrCode['original_url'] ?? '');
            $redirectUrl = htmlspecialchars($qrCode['redirect_url'] ?? '');
            $originalUrlLink = $originalUrl ? '<a href="' . $originalUrl . '" target="_blank" rel="noopener">' . $originalUrl . '</a>' : '';
            $redirectUrlLink = $redirectUrl ? '<a href="' . $redirectUrl . '" target="_blank" rel="noopener">' . $redirectUrl . '</a>' : '';
            $html .= '<td>' . $originalUrlLink . '<div class="mt-2"></div></td>';
            $html .= '<td id="redirect-url-cell-' . htmlspecialchars($qrCode['id']) . '">' . $redirectUrlLink . '<div class="mt-2">' . $editBtn . '</div></td>';
            $html .= '<td>' . htmlspecialchars($qrCode['created_at'] ?? '') . '<div class="mt-2">' . $deleteBtn . '</div></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>
                </table>';

        $html .= '</div>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
            <script>
document.addEventListener(\'DOMContentLoaded\', function() {
    document.querySelectorAll(\'.edit-redirect-form\').forEach(function(form) {
        form.addEventListener(\'submit\', function(e) {
            e.preventDefault();
            const modal = form.closest(\'.modal\');
            const qrId = form.dataset.qrid;
            const redirectUrl = form.querySelector(\'input[name="redirect_url"]\').value;
            const formData = new FormData(form);
            fetch(form.action, {
                method: \'POST\',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the table cell
                    const cell = document.querySelector(`#redirect-url-cell-${qrId}`);
                    if (cell) cell.childNodes[0].nodeValue = redirectUrl + \' \';
                    // Hide modal
                    if (modal) {
                        const modalInstance = bootstrap.Modal.getInstance(modal);
                        modalInstance.hide();
                    }
                } else {
                    alert(\'Failed to update redirect URL.\');
                }
            })
            .catch(() => alert(\'Error updating redirect URL.\'));
        });
    });
});
</script>
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
