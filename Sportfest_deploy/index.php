<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

// Redirect to login if not authenticated
if (!is_logged_in()) {
    redirect('login.php');
}

$role = $_SESSION['role'] ?? '';
$page_title = 'Dashboard - Sportfest Manager';
$extra_css = '<style>
.main-content {
    min-height: calc(100vh - 200px);
}
</style>';
include 'views/header.php';
?>

<h1>Willkommen im Sportfest Manager</h1>

<div class="card-grid">
    <?php if ($role === 'admin'): ?>
    <div class="card dashboard-card">
        <h2 style="margin-bottom: 1rem; word-wrap: break-word; overflow-wrap: break-word;">Schüler</h2>
        <a href="<?php echo url_for('students.php'); ?>" class="btn btn-primary">Zur Verwaltung</a>
    </div>

    <div class="card dashboard-card">
        <h2 style="margin-bottom: 1rem;">Disziplinen</h2>
        <a href="<?php echo url_for('disciplines.php'); ?>" class="btn btn-primary">Zur Verwaltung</a>
    </div>
    <?php endif; ?>

    <div class="card dashboard-card">
        <h2 style="margin-bottom: 1rem;">Werterfassung</h2>
        <a href="<?php echo url_for('enter_results.php'); ?>" class="btn btn-primary">Zur Erfassung</a>
    </div>

    <div class="card dashboard-card">
        <h2 style="margin-bottom: 1rem;">Auswertung</h2>
        <a href="<?php echo url_for('view_results.php'); ?>" class="btn btn-primary">Zur Auswertung</a>
    </div>
</div>

<?php include 'views/footer.php'; ?>
