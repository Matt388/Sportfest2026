<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

// Handle login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? AND password = ?');
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        flash('Erfolgreich angemeldet!', 'success');
        redirect('index.php');
    } else {
        flash('Ungültiger Benutzername oder Passwort', 'danger');
    }
}

$page_title = 'Login - Sportfest Manager';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo url_for('static/css/style.css'); ?>">
</head>
<body>
    <div class="container">
        <main class="main-content">
            <?php
            $flash_messages = get_flashed_messages();
            if (!empty($flash_messages)):
            ?>
                <div class="alerts">
                    <?php foreach ($flash_messages as $msg): ?>
                        <div class="alert alert-<?php echo htmlspecialchars($msg['category']); ?>">
                            <span class="alert-icon">
                                <?php
                                if ($msg['category'] === 'success') echo '✓';
                                elseif ($msg['category'] === 'danger') echo '✕';
                                else echo 'i';
                                ?>
                            </span>
                            <?php echo htmlspecialchars($msg['message']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="login-container">
                <button id="darkModeToggle" class="dark-mode-toggle login-dark-toggle" aria-label="Toggle Dark Mode">
                    <span id="darkModeIcon">🌙</span>
                </button>

                <div class="login-card">
                    <img src="<?php echo url_for('static/logo_pirol.png'); ?>" alt="VvBG Logo" class="login-logo">
                    <h1>Sportfest Manager</h1>

                    <form method="POST" action="<?php echo url_for('login.php'); ?>">
                        <div class="form-group">
                            <label for="username">Benutzername</label>
                            <input type="text" id="username" name="username" class="form-control" required autofocus>
                        </div>

                        <div class="form-group">
                            <label for="password">Passwort</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">Anmelden</button>
                    </form>

                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid var(--gray-light); font-size: 0.9rem; color: var(--gray);">
                        <p><strong>Demo-Zugänge:</strong></p>
                        <p>Admin: admin / admin123</p>
                        <p>Helfer: user / user123</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <p>Sportfest Manager © <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script src="<?php echo url_for('static/js/main.js'); ?>"></script>
</body>
</html>
