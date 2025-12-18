<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Sportfest Manager'; ?></title>
    <link rel="stylesheet" href="<?php echo url_for('static/css/style.css'); ?>?v=<?php echo time(); ?>">
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
    <div class="container">
        <?php if (is_logged_in()): ?>
        <nav class="navbar">
            <div class="nav-brand">
                <img src="<?php echo url_for('static/logo_pirol.png'); ?>" alt="VvBG Logo" class="nav-logo">
                <span class="brand-text">Sportfest Manager</span>
            </div>
            <ul class="nav-links">
                <li><a href="<?php echo url_for('index.php'); ?>">Dashboard</a></li>
                <?php if (is_admin()): ?>
                <li><a href="<?php echo url_for('students.php'); ?>">Schüler</a></li>
                <li><a href="<?php echo url_for('disciplines.php'); ?>">Disziplinen</a></li>
                <?php endif; ?>
                <li><a href="<?php echo url_for('enter_results.php'); ?>">Erfassen</a></li>
                <li><a href="<?php echo url_for('view_results.php'); ?>">Ergebnisse</a></li>
                <li><a href="<?php echo url_for('settings.php'); ?>">Einstellungen</a></li>

                <li>
                    <button id="darkModeToggle" class="dark-mode-toggle" aria-label="Toggle Dark Mode">
                        <span id="darkModeIcon">🌙</span>
                    </button>
                </li>

                <li class="user-menu">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['username']); ?>&background=8DC63F&color=fff" alt="User" class="user-avatar">
                    <a href="<?php echo url_for('logout.php'); ?>" class="logout-link">Abmelden</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

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
