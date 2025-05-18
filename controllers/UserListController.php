<?php
namespace App\Controllers;

class UserListController
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function renderListPage($request = null, $response = null, $args = [])
    {
        // Pagination
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * 10;

        // Get total users (excluding the first entry)
        $totalStmt = $this->pdo->query("SELECT COUNT(*) as total FROM users");
        $totalRows = $totalStmt->fetch()['total'] - 1;

        // Get users, skipping the first entry (ORDER BY id, LIMIT)
        $stmt = $this->pdo->prepare("SELECT id, username, created_at FROM users ORDER BY id ASC LIMIT 10 OFFSET :offset");
        $stmt->bindValue(':offset', $offset + 1, \PDO::PARAM_INT); // skip first
        $stmt->execute();
        $users = $stmt->fetchAll();

        // Pagination logic
        $hasMore = ($offset + count($users) + 1) < ($totalRows + 1);
        $from = $offset + 2; // since we skip the first
        $to = $offset + count($users) + 1;
        if ($totalRows <= 0) {
            $from = 0;
            $to = 0;
        }

        ob_start();
        $menu = new \App\Controllers\MainMenuController();
        $menu->showMenu();
        $menuHtml = ob_get_clean();

        $html = '<!DOCTYPE html><html lang="en"><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        $html .= '<title>User List</title>';
        $html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">';
        $html .= '<link rel="stylesheet" href="/public/index.css">';
        $html .= '</head><body class="bg-light">';
        $html .= $menuHtml;
        $html .= '<div class="container mt-5">';
        $html .= '<h1 class="text-center mb-4">User List</h1>';

        // Add alert area for password change result
        $html .= '<div id="rootPasswordStatusAlert" class="alert d-none mt-3" role="alert"></div>';
        $html .= '<script>
        // Show alert from sessionStorage if present
        document.addEventListener("DOMContentLoaded", function() {
          var alertDiv = document.getElementById("rootPasswordStatusAlert");
          var alertMsg = sessionStorage.getItem("userlist_alert");
          if (alertMsg) {
            var parts = alertMsg.split("|");
            alertDiv.innerText = parts[0];
            alertDiv.className = "alert mt-3";
            if (parts[1] === "success") {
              alertDiv.classList.add("alert-success");
            } else {
              alertDiv.classList.add("alert-danger");
            }
            alertDiv.classList.remove("d-none");
            setTimeout(function() {
              alertDiv.classList.add("d-none");
            }, 5000);
            sessionStorage.removeItem("userlist_alert");
          }
        });
        </script>';

        // Add User and Change Root User Password buttons side by side
        $html .= '<div class="d-flex justify-content-center mb-3 gap-2">';
        $html .= '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>';
        // Find root user in the whole users table
        $rootUser = null;
        $stmtRoot = $this->pdo->prepare("SELECT id, username FROM users WHERE username = 'root' LIMIT 1");
        $stmtRoot->execute();
        $rootUser = $stmtRoot->fetch();
        if ($rootUser) {
            $html .= '<button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editRootUserPasswordModal">Change Root Password</button>';
        }
        $html .= '</div>';

        // Pagination controls
        $html .= '<div class="d-flex align-items-center justify-content-center mb-3" style="gap:0.5rem;">';
        if ($page > 1) {
            $prevUrl = '/userlist.php?page=' . ($page - 1);
            $html .= '<a href="' . $prevUrl . '" class="btn btn-secondary">Previous</a>';
        } else {
            $html .= '<button class="btn btn-secondary" disabled>Previous</button>';
        }
        $html .= '<span class="text-muted px-2">' . $from . ' - ' . $to . ' / ' . $totalRows . '</span>';
        if ($hasMore) {
            $nextUrl = '/userlist.php?page=' . ($page + 1);
            $html .= '<a href="' . $nextUrl . '" class="btn btn-secondary">Next</a>';
        } else {
            $html .= '<button class="btn btn-secondary" disabled>Next</button>';
        }
        $html .= '</div>';

        // User table
        $html .= '<table class="table table-bordered table-hover text-center">';
        $html .= '<thead class="table-dark"><tr><th>ID</th><th>Username</th><th>Created At</th><th>Actions</th></tr></thead><tbody>';
        // Get current user username
        $currentUser = null;
        if (isset($_SESSION['user_id'])) {
            $stmtUser = $this->pdo->prepare('SELECT username FROM users WHERE id = :id LIMIT 1');
            $stmtUser->execute(['id' => $_SESSION['user_id']]);
            $currentUser = $stmtUser->fetch();
        }
        foreach ($users as $user) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($user['id']) . '</td>';
            $html .= '<td>' . htmlspecialchars($user['username']) . '</td>';
            $html .= '<td>' . htmlspecialchars($user['created_at']) . '</td>';
            $html .= '<td>';
            $html .= '<button type="button" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#editPasswordModal-' . htmlspecialchars($user['id']) . '">Edit Password</button>';
            // Only show delete button if user is root
            if ($currentUser && $currentUser['username'] === 'root') {
                $html .= '<form method="POST" action="/delete_user.php" style="display:inline;">';
                $html .= '<input type="hidden" name="user_id" value="' . htmlspecialchars($user['id']) . '">';
                $html .= '<button type="submit" class="btn btn-sm btn-outline-danger btn-search-mt0" onclick="return confirm(\'Delete this user?\')">Delete</button>';
                $html .= '</form>';
            }
            $html .= '</td>';
            $html .= '</tr>';
            // Edit Password Modal
            $html .= '<div class="modal fade" id="editPasswordModal-' . htmlspecialchars($user['id']) . '" tabindex="-1" aria-labelledby="editPasswordModalLabel-' . htmlspecialchars($user['id']) . '" aria-hidden="true">';
            $html .= '<div class="modal-dialog">';
            $html .= '<div class="modal-content">';
            $html .= '<form method="POST" action="/edit_user_password.php">';
            $html .= '<input type="hidden" name="user_id" value="' . htmlspecialchars($user['id']) . '">';
            $html .= '<div class="modal-header">';
            $html .= '<h5 class="modal-title" id="editPasswordModalLabel-' . htmlspecialchars($user['id']) . '">Edit Password for ' . htmlspecialchars($user['username']) . '</h5>';
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            $html .= '</div>';
            $html .= '<div class="modal-body">';
            $html .= '<div class="mb-3">';
            $html .= '<label for="new_password_' . htmlspecialchars($user['id']) . '" class="form-label">New Password</label>';
            $html .= '<input type="password" class="form-control" id="new_password_' . htmlspecialchars($user['id']) . '" name="new_password" required>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="modal-footer">';
            $html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';
            $html .= '<button type="submit" class="btn btn-primary">Save</button>';
            $html .= '</div>';
            $html .= '</form>';
            $html .= '</div></div></div>';
        }
        $html .= '</tbody></table>';

        // Add User Modal
        $html .= '<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">';
        $html .= '<div class="modal-dialog">';
        $html .= '<div class="modal-content">';
        $html .= '<form method="POST" action="/add_user.php">';
        $html .= '<div class="modal-header">';
        $html .= '<h5 class="modal-title" id="addUserModalLabel">Add User</h5>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        $html .= '</div>';
        $html .= '<div class="modal-body">';
        $html .= '<div class="mb-3">';
        $html .= '<label for="username" class="form-label">Username</label>';
        $html .= '<input type="text" class="form-control" id="username" name="username" required>';
        $html .= '</div>';
        $html .= '<div class="mb-3">';
        $html .= '<label for="password" class="form-label">Password</label>';
        $html .= '<input type="password" class="form-control" id="password" name="password" required>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="modal-footer">';
        $html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';
        $html .= '<button type="submit" class="btn btn-primary">Add User</button>';
        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div></div></div>';

        // Add modal for changing root user password
        if ($rootUser) {
            $html .= '<div class="modal fade" id="editRootUserPasswordModal" tabindex="-1" aria-labelledby="editRootUserPasswordModalLabel" aria-hidden="true">';
            $html .= '<div class="modal-dialog">';
            $html .= '<div class="modal-content">';
            $html .= '<form method="POST" action="/edit_user_password.php">';
            $html .= '<input type="hidden" name="user_id" value="' . htmlspecialchars($rootUser['id']) . '">';
            $html .= '<div class="modal-header">';
            $html .= '<h5 class="modal-title" id="editRootUserPasswordModalLabel">Change Password for root</h5>';
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
            $html .= '</div>';
            $html .= '<div class="modal-body">';
            $html .= '<div class="mb-3">';
            $html .= '<label for="old_password_root" class="form-label">Current Password</label>';
            $html .= '<input type="text" class="form-control" id="old_password_root" name="old_password" required autocomplete="off">';
            $html .= '</div>';
            $html .= '<div class="mb-3">';
            $html .= '<label for="new_password_root" class="form-label">New Password</label>';
            $html .= '<input type="text" class="form-control" id="new_password_root" name="new_password" required autocomplete="off">';
            $html .= '</div>';
            $html .= '<div class="mb-3">';
            $html .= '<label for="confirm_password_root" class="form-label">Confirm New Password</label>';
            $html .= '<input type="text" class="form-control" id="confirm_password_root" name="confirm_password" required autocomplete="off">';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="modal-footer">';
            $html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>';
            $html .= '<button type="submit" class="btn btn-primary">Save</button>';
            $html .= '</div>';
            $html .= '</form>';
            $html .= '</div></div></div>';
        }

        // JS to show status alert with a message at the top
        $html .= <<<JS
<script>
function showRootPasswordStatus(msg, type) {
  var alertDiv = document.getElementById('rootPasswordStatusAlert');
  alertDiv.innerText = msg;
  alertDiv.className = 'alert mt-3';
  if (type === 'success') {
    alertDiv.classList.add('alert-success');
  } else {
    alertDiv.classList.add('alert-danger');
  }
  alertDiv.classList.remove('d-none');
  setTimeout(function() {
    alertDiv.classList.add('d-none');
  }, 5000);
}
// Intercept all user password edit forms (including root)
document.querySelectorAll('form[action="/edit_user_password.php"]').forEach(function(form) {
  var saveBtn = form.querySelector('button[type="submit"]');
  form.addEventListener('submit', function(e) {
    var inModal = !!form.closest('.modal');
    console.log('[PasswordModal] Submit handler running. In modal:', inModal, 'Form:', form);
    // Always prevent default for modal forms to avoid redirect
    if (inModal) {
      e.preventDefault();
      if (saveBtn) saveBtn.disabled = true;
      var formData = new FormData(form);
      fetch('/edit_user_password.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(function(data) {
        // Instead of removing the modal, refresh the page and transmit the message via session
        if (data.status === 'wrong_password') {
          sessionStorage.setItem('userlist_alert', 'Wrong password|danger');
        } else if (data.status === 'mismatch') {
          sessionStorage.setItem('userlist_alert', 'New password and confirmation do not match|danger');
        } else if (data.status === 'success') {
          sessionStorage.setItem('userlist_alert', 'Password changed successfully|success');
        } else if (data.status === 'not_found') {
          sessionStorage.setItem('userlist_alert', 'User not found|danger');
        } else if (data.status === 'unauthorized') {
          sessionStorage.setItem('userlist_alert', 'Unauthorized|danger');
        } else {
          sessionStorage.setItem('userlist_alert', 'Unknown error|danger');
        }
        window.location.reload();
      })
      .catch(function() {
        var modal = bootstrap.Modal.getInstance(form.closest('.modal'));
        if (modal) modal.hide();
        showRootPasswordStatus('Server error', 'danger');
        if (saveBtn) saveBtn.disabled = false;
      });
    } else {
      // Prevent default for all forms to avoid redirect (even if not in modal)
      e.preventDefault();
      showRootPasswordStatus('Password change only allowed via modal.', 'danger');
    }
  });
  // Reset fields when modal is opened
  var modalDiv = form.closest('.modal');
  if (modalDiv) {
    modalDiv.addEventListener('show.bs.modal', function() {
      form.reset();
      if (saveBtn) saveBtn.disabled = false;
    });
  }
});
console.log('[PasswordModal] Handler attached to', document.querySelectorAll('form[action="/edit_user_password.php"]').length, 'forms');
</script>
JS;

        $html .= '</div></body></html>';

        echo $html;
    }
}
