<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

require_login();
require_admin();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;
$db = get_db();

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('INSERT INTO disciplines (name, type, unit, attempts) VALUES (?, ?, ?, ?)');
    $stmt->execute([$_POST['name'], $_POST['type'], $_POST['unit'], $_POST['attempts'] ?? 1]);
    flash('Disziplin erfolgreich hinzugefügt!', 'success');
    redirect('disciplines.php');
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare('UPDATE disciplines SET name=?, type=?, unit=?, attempts=? WHERE id=?');
    $stmt->execute([$_POST['name'], $_POST['type'], $_POST['unit'], $_POST['attempts'] ?? 1, $id]);
    flash('Disziplin erfolgreich aktualisiert!', 'success');
    redirect('disciplines.php');
}

if ($action === 'delete') {
    $stmt = $db->prepare('DELETE FROM disciplines WHERE id = ?');
    $stmt->execute([$id]);
    $stmt = $db->prepare('DELETE FROM results WHERE discipline_id = ?');
    $stmt->execute([$id]);
    flash('Disziplin gelöscht!', 'success');
    redirect('disciplines.php');
}

if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            $header = fgetcsv($handle);

            $count = 0;
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 3) {
                    $stmt = $db->prepare('INSERT INTO disciplines (name, type, unit, attempts) VALUES (?, ?, ?, ?)');
                    $stmt->execute([
                        $data[0] ?? '',
                        $data[1] ?? 'distance',
                        $data[2] ?? '',
                        isset($data[3]) ? (int)$data[3] : 1
                    ]);
                    $count++;
                }
            }
            fclose($handle);
            flash("$count Disziplinen erfolgreich importiert!", 'success');
            redirect('disciplines.php');
        }
    }
    flash('Keine gültige CSV-Datei ausgewählt', 'danger');
}

if ($action === 'add') {
    $page_title = 'Disziplin hinzufügen';
    include 'views/header.php';
    ?>
    <h1>Disziplin hinzufügen</h1>
    <div class="card">
        <form method="POST" action="disciplines.php?action=add">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="type">Typ *</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="distance">Strecke (höher = besser)</option>
                    <option value="time">Zeit (niedriger = besser)</option>
                    <option value="points">Punkte (niedriger = besser)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="unit">Einheit *</label>
                <input type="text" id="unit" name="unit" class="form-control" placeholder="z.B. m, s, Pkt" required>
            </div>
            <div class="form-group">
                <label for="attempts">Anzahl Versuche</label>
                <input type="number" id="attempts" name="attempts" class="form-control" value="1" min="1">
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a href="disciplines.php" class="btn btn-secondary">Abbrechen</a>
        </form>
    </div>
    <?php include 'views/footer.php'; exit;
}

if ($action === 'edit') {
    $stmt = $db->prepare('SELECT * FROM disciplines WHERE id = ?');
    $stmt->execute([$id]);
    $discipline = $stmt->fetch();

    $page_title = 'Disziplin bearbeiten';
    include 'views/header.php';
    ?>
    <h1>Disziplin bearbeiten</h1>
    <div class="card">
        <form method="POST" action="disciplines.php?action=edit&id=<?php echo $id; ?>">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($discipline['name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="type">Typ *</label>
                <select id="type" name="type" class="form-control" required>
                    <option value="distance" <?php echo $discipline['type'] === 'distance' ? 'selected' : ''; ?>>Strecke (höher = besser)</option>
                    <option value="time" <?php echo $discipline['type'] === 'time' ? 'selected' : ''; ?>>Zeit (niedriger = besser)</option>
                    <option value="points" <?php echo $discipline['type'] === 'points' ? 'selected' : ''; ?>>Punkte (niedriger = besser)</option>
                </select>
            </div>
            <div class="form-group">
                <label for="unit">Einheit *</label>
                <input type="text" id="unit" name="unit" class="form-control" value="<?php echo htmlspecialchars($discipline['unit']); ?>" required>
            </div>
            <div class="form-group">
                <label for="attempts">Anzahl Versuche</label>
                <input type="number" id="attempts" name="attempts" class="form-control" value="<?php echo $discipline['attempts']; ?>" min="1">
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a href="disciplines.php" class="btn btn-secondary">Abbrechen</a>
        </form>
    </div>
    <?php include 'views/footer.php'; exit;
}

if ($action === 'import') {
    $page_title = 'Disziplinen importieren';
    include 'views/header.php';
    ?>
    <h1>Disziplinen importieren (CSV)</h1>
    <div class="card">
        <form method="POST" action="disciplines.php?action=import" enctype="multipart/form-data" id="importForm">
            <div class="dropbox" id="dropbox">
                <div class="dropbox-icon">📄</div>
                <div class="dropbox-text">Drag & Drop</div>
                <div class="dropbox-subtext">oder <span class="browse-link">durchsuchen</span></div>
                <div class="dropbox-format">Unterstützt: .CSV</div>
                <input type="file" id="file" name="file" accept=".csv" required>
            </div>
            <div class="file-selected" id="fileSelected">
                <span>✓</span>
                <span id="fileName"></span>
            </div>
            <p style="margin-top: 1.5rem; color: var(--gray); font-size: 0.9rem;">
                <strong>CSV-Format:</strong> Name, Typ, Einheit, Anzahl Versuche
            </p>
            <div class="flex flex-gap" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Importieren</button>
                <a href="disciplines.php" class="btn btn-secondary">Abbrechen</a>
            </div>
        </form>
    </div>
    <script>
    const dropbox = document.getElementById('dropbox');
    const fileInput = document.getElementById('file');
    const fileSelected = document.getElementById('fileSelected');
    const fileName = document.getElementById('fileName');

    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropbox.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Highlight drop area when item is dragged over it
    ['dragenter', 'dragover'].forEach(eventName => {
        dropbox.addEventListener(eventName, () => {
            dropbox.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropbox.addEventListener(eventName, () => {
            dropbox.classList.remove('dragover');
        }, false);
    });

    // Handle dropped files
    dropbox.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFiles(files);
    }, false);

    // Handle file input change
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            fileName.textContent = file.name;
            fileSelected.classList.add('show');
        }
    }
    </script>
    <?php include 'views/footer.php'; exit;
}

// List view
$disciplines = $db->query('SELECT * FROM disciplines ORDER BY name')->fetchAll();

$page_title = 'Disziplinen';
include 'views/header.php';
?>

<div class="flex justify-between align-center mb-3">
    <h1>Disziplinen</h1>
    <div class="flex flex-gap">
        <button id="deleteSelectedDisciplinesBtn" onclick="deleteSelectedDisciplines()" class="btn btn-danger" style="display: none;">Ausgewählte löschen</button>
        <a href="disciplines.php?action=add" class="btn btn-primary">Disziplin hinzufügen</a>
        <a href="disciplines.php?action=import" class="btn btn-secondary">CSV Import</a>
    </div>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th style="width: 40px;">
                    <input type="checkbox" id="selectAllDisciplines" onchange="toggleSelectAllDisciplines()">
                </th>
                <th>Name</th>
                <th>Typ</th>
                <th>Einheit</th>
                <th>Versuche</th>
                <th>Aktionen</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($disciplines)): ?>
            <tr>
                <td colspan="6" class="text-center" style="padding: 2rem;">
                    Keine Disziplinen vorhanden. <a href="disciplines.php?action=add">Erste Disziplin anlegen</a>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($disciplines as $disc): ?>
                <tr>
                    <td><input type="checkbox" class="discipline-checkbox" value="<?php echo $disc['id']; ?>" onchange="updateDeleteDisciplinesButton()"></td>
                    <td><strong><?php echo htmlspecialchars($disc['name']); ?></strong></td>
                    <td>
                        <?php
                        if ($disc['type'] === 'distance') echo 'Strecke';
                        elseif ($disc['type'] === 'time') echo 'Zeit';
                        else echo 'Punkte';
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($disc['unit']); ?></td>
                    <td><?php echo $disc['attempts']; ?></td>
                    <td>
                        <a href="disciplines.php?action=edit&id=<?php echo $disc['id']; ?>" class="btn btn-sm btn-secondary">Bearbeiten</a>
                        <a href="disciplines.php?action=delete&id=<?php echo $disc['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Disziplin wirklich löschen?')">Löschen</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$extra_js = <<<'JAVASCRIPT'
<script>
const BASE_URL = '/Sportfest';

function toggleSelectAllDisciplines() {
    const selectAll = document.getElementById('selectAllDisciplines');
    const checkboxes = document.querySelectorAll('.discipline-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    updateDeleteDisciplinesButton();
}

function updateDeleteDisciplinesButton() {
    const checkboxes = document.querySelectorAll('.discipline-checkbox:checked');
    const deleteBtn = document.getElementById('deleteSelectedDisciplinesBtn');
    if (checkboxes.length > 0) {
        deleteBtn.style.display = 'block';
        deleteBtn.textContent = `${checkboxes.length} Ausgewählte löschen`;
    } else {
        deleteBtn.style.display = 'none';
    }
}

function deleteSelectedDisciplines() {
    const checkboxes = document.querySelectorAll('.discipline-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length === 0) {
        alert('Bitte wähle mindestens eine Disziplin aus.');
        return;
    }

    const count = ids.length;
    const message = count === 1
        ? 'Möchtest du diese Disziplin wirklich löschen?'
        : `Möchtest du diese ${count} Disziplinen wirklich löschen?`;

    if (confirm(message)) {
        fetch(BASE_URL + '/api.php?action=delete-disciplines', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Fehler beim Löschen: ' + data.message);
            }
        })
        .catch(error => {
            alert('Fehler beim Löschen der Disziplinen.');
        });
    }
}
</script>
JAVASCRIPT;
?>

<?php include 'views/footer.php'; ?>
