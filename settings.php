<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

require_login();

$db = get_db();

// Helper function to check if user can be edited
function can_edit_user($target_user_id, $db) {
    $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch();

    if (!$target_user) return false;

    // Can always edit yourself
    if ($target_user_id == $_SESSION['user_id']) return true;

    // Admin can edit all users
    if (is_admin()) return true;

    return false;
}

// Handle user management actions (Admin only)
if (is_admin() && isset($_GET['user_action'])) {
    $action = $_GET['user_action'];

    if ($action === 'delete' && isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];

        // Don't allow deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            flash('Sie können sich nicht selbst löschen!', 'danger');
            redirect('settings.php');
        }

        // Check if user can be deleted
        if (can_edit_user($user_id, $db)) {
            $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$user_id]);
            flash('Benutzer gelöscht!', 'success');
        } else {
            flash('Sie haben keine Berechtigung, diesen Benutzer zu löschen!', 'danger');
        }
        redirect('settings.php');
    }

    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'user';

        if ($username && $password) {
            try {
                $stmt = $db->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
                $stmt->execute([$username, $password, $role]);
                flash('Benutzer erfolgreich erstellt!', 'success');
                redirect('settings.php');
            } catch (PDOException $e) {
                flash('Benutzername existiert bereits!', 'danger');
            }
        }
    }

    if ($action === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_POST['user_id'] ?? 0;
        $new_password = $_POST['new_password'] ?? '';

        if ($new_password && can_edit_user($user_id, $db)) {
            $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$new_password, $user_id]);
            flash('Passwort erfolgreich geändert!', 'success');
            redirect('settings.php');
        } else {
            flash('Sie haben keine Berechtigung, dieses Passwort zu ändern!', 'danger');
            redirect('settings.php');
        }
    }

    if ($action === 'change_username' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_POST['user_id'] ?? 0;
        $new_username = $_POST['new_username'] ?? '';

        if ($new_username && can_edit_user($user_id, $db)) {
            try {
                $stmt = $db->prepare('UPDATE users SET username = ? WHERE id = ?');
                $stmt->execute([$new_username, $user_id]);

                // Update session if changing own username
                if ($user_id == $_SESSION['user_id']) {
                    $_SESSION['username'] = $new_username;
                }

                flash('Benutzername erfolgreich geändert!', 'success');
                redirect('settings.php');
            } catch (PDOException $e) {
                flash('Benutzername existiert bereits!', 'danger');
                redirect('settings.php');
            }
        } else {
            flash('Sie haben keine Berechtigung, diesen Benutzernamen zu ändern!', 'danger');
            redirect('settings.php');
        }
    }
}

// Get all users for admin
$users = [];
if (is_admin()) {
    $users = $db->query('SELECT * FROM users ORDER BY username')->fetchAll();
}

$page_title = 'Einstellungen';
include 'views/header.php';
?>

<h1>Einstellungen</h1>

<?php if (is_admin()): ?>
<div class="card">
    <div class="flex justify-between align-center mb-3">
        <h2>Benutzerverwaltung</h2>
        <button onclick="showAddUserModal()" class="btn btn-primary">Benutzer hinzufügen</button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Benutzername</th>
                    <th>Rolle</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user):
                    $can_edit = can_edit_user($user['id'], $db);
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                    <td>
                        <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'primary' : 'secondary'; ?>">
                            <?php echo $user['role'] === 'admin' ? 'Administrator' : 'Helfer'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($can_edit): ?>
                            <button onclick="showChangeUsernameModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-sm btn-primary">Benutzername ändern</button>
                            <button onclick="showChangePasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="btn btn-sm btn-secondary">Passwort ändern</button>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                            <a href="settings.php?user_action=delete&user_id=<?php echo $user['id']; ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Benutzer <?php echo htmlspecialchars($user['username']); ?> wirklich löschen?')">Löschen</a>
                            <?php else: ?>
                            <span class="badge badge-info">Sie</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge badge-warning">Keine Berechtigung</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <h2>Über die Anwendung</h2>
    <p><strong>Version:</strong> 1.0.0</p>
    <p><strong>Sportfest Manager</strong> - Verwaltung von Sportfesten fürs Vicco</p>
</div>

<!-- Add User Modal -->
<div id="addUserModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg); padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
        <h2>Neuen Benutzer hinzufügen</h2>
        <form method="POST" action="settings.php?user_action=add">
            <div class="form-group">
                <label for="new_username">Benutzername *</label>
                <input type="text" id="new_username" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="new_password">Passwort *</label>
                <input type="text" id="new_password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="new_role">Rolle *</label>
                <select id="new_role" name="role" class="form-control" required>
                    <option value="user">Helfer</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            <div class="flex flex-gap">
                <button type="submit" class="btn btn-primary">Erstellen</button>
                <button type="button" onclick="hideAddUserModal()" class="btn btn-secondary">Abbrechen</button>
            </div>
        </form>
    </div>
</div>

<!-- Change Username Modal -->
<div id="changeUsernameModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg); padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
        <h2>Benutzername ändern</h2>
        <p id="changeUsernameCurrentName" style="margin-bottom: 1rem;"></p>
        <form method="POST" action="settings.php?user_action=change_username">
            <input type="hidden" id="change_username_user_id" name="user_id">
            <div class="form-group">
                <label for="change_new_username">Neuer Benutzername *</label>
                <input type="text" id="change_new_username" name="new_username" class="form-control" required>
            </div>
            <div class="flex flex-gap">
                <button type="submit" class="btn btn-primary">Benutzername ändern</button>
                <button type="button" onclick="hideChangeUsernameModal()" class="btn btn-secondary">Abbrechen</button>
            </div>
        </form>
    </div>
</div>

<!-- Change Password Modal -->
<div id="changePasswordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: var(--bg); padding: 2rem; border-radius: 8px; max-width: 500px; width: 90%;">
        <h2>Passwort ändern</h2>
        <p id="changePasswordUsername" style="margin-bottom: 1rem;"></p>
        <form method="POST" action="settings.php?user_action=change_password">
            <input type="hidden" id="change_user_id" name="user_id">
            <div class="form-group">
                <label for="change_new_password">Neues Passwort *</label>
                <input type="text" id="change_new_password" name="new_password" class="form-control" required>
            </div>
            <div class="flex flex-gap">
                <button type="submit" class="btn btn-primary">Passwort ändern</button>
                <button type="button" onclick="hideChangePasswordModal()" class="btn btn-secondary">Abbrechen</button>
            </div>
        </form>
    </div>
</div>

<?php
$extra_js = '<script>
function showAddUserModal() {
    document.getElementById("addUserModal").style.display = "flex";
}

function hideAddUserModal() {
    document.getElementById("addUserModal").style.display = "none";
}

function showChangeUsernameModal(userId, username) {
    document.getElementById("change_username_user_id").value = userId;
    document.getElementById("changeUsernameCurrentName").textContent = "Aktueller Benutzername: " + username;
    document.getElementById("change_new_username").value = username;
    document.getElementById("changeUsernameModal").style.display = "flex";
}

function hideChangeUsernameModal() {
    document.getElementById("changeUsernameModal").style.display = "none";
}

function showChangePasswordModal(userId, username) {
    document.getElementById("change_user_id").value = userId;
    document.getElementById("changePasswordUsername").textContent = "Passwort für Benutzer: " + username;
    document.getElementById("changePasswordModal").style.display = "flex";
}

function hideChangePasswordModal() {
    document.getElementById("changePasswordModal").style.display = "none";
}

// Close modal when clicking outside
document.getElementById("addUserModal")?.addEventListener("click", function(e) {
    if (e.target === this) hideAddUserModal();
});

document.getElementById("changeUsernameModal")?.addEventListener("click", function(e) {
    if (e.target === this) hideChangeUsernameModal();
});

document.getElementById("changePasswordModal")?.addEventListener("click", function(e) {
    if (e.target === this) hideChangePasswordModal();
});
</script>';
?>

<?php include 'views/footer.php'; ?>
