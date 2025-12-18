<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

require_login();
require_admin();

// Handle different actions
$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? 0;

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = get_db();

    // Extract grade from class if not provided
    $class = $_POST['class'];
    $grade = $_POST['grade'] ?: null;

    if (!$grade && preg_match('/(\d+)/', $class, $matches)) {
        $grade = (int)$matches[1];
    }

    $stmt = $db->prepare('
        INSERT INTO students (firstname, lastname, class, gender, birth_year, grade)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $_POST['firstname'],
        $_POST['lastname'],
        $class,
        $_POST['gender'] ?? null,
        $_POST['birth_year'] ?: null,
        $grade
    ]);
    flash('Schüler erfolgreich hinzugefügt!', 'success');
    redirect('students.php');
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = get_db();

    // Extract grade from class if not provided
    $class = $_POST['class'];
    $grade = $_POST['grade'] ?: null;

    if (!$grade && preg_match('/(\d+)/', $class, $matches)) {
        $grade = (int)$matches[1];
    }

    $stmt = $db->prepare('
        UPDATE students
        SET firstname=?, lastname=?, class=?, gender=?, birth_year=?, grade=?
        WHERE id=?
    ');
    $stmt->execute([
        $_POST['firstname'],
        $_POST['lastname'],
        $class,
        $_POST['gender'] ?? null,
        $_POST['birth_year'] ?: null,
        $grade,
        $id
    ]);
    flash('Schüler erfolgreich aktualisiert!', 'success');
    redirect('students.php');
}

if ($action === 'delete') {
    $db = get_db();
    $stmt = $db->prepare('DELETE FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $stmt = $db->prepare('DELETE FROM results WHERE student_id = ?');
    $stmt->execute([$id]);
    flash('Schüler gelöscht!', 'success');
    redirect('students.php');
}

if ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        if (pathinfo($file['name'], PATHINFO_EXTENSION) === 'csv') {
            $handle = fopen($file['tmp_name'], 'r');
            $header = fgetcsv($handle); // Skip header

            $db = get_db();
            $count = 0;
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (count($data) >= 3) {
                    // Extract grade from class if not provided
                    $class = $data[2] ?? '';
                    $grade = $data[5] ?: null;

                    if (!$grade && preg_match('/(\d+)/', $class, $matches)) {
                        $grade = (int)$matches[1];
                    }

                    $stmt = $db->prepare('
                        INSERT INTO students (firstname, lastname, class, gender, birth_year, grade)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ');
                    $stmt->execute([
                        $data[0] ?? '',
                        $data[1] ?? '',
                        $class,
                        $data[3] ?? null,
                        $data[4] ?: null,
                        $grade
                    ]);
                    $count++;
                }
            }
            fclose($handle);
            flash("$count Schüler erfolgreich importiert!", 'success');
            redirect('students.php');
        }
    }
    flash('Keine gültige CSV-Datei ausgewählt', 'danger');
}

// Get data for dropdowns
$db = get_db();
$classes = $db->query('SELECT DISTINCT class FROM students WHERE class IS NOT NULL ORDER BY class')->fetchAll();
$grades = $db->query('SELECT DISTINCT grade FROM students WHERE grade IS NOT NULL ORDER BY grade')->fetchAll();
$disciplines = $db->query('SELECT * FROM disciplines ORDER BY name')->fetchAll();

// Show different pages based on action
if ($action === 'add') {
    $page_title = 'Schüler hinzufügen';
    include 'views/header.php';
    ?>
    <h1>Schüler hinzufügen</h1>
    <div class="card">
        <form method="POST" action="students.php?action=add">
            <div class="form-group">
                <label for="firstname">Vorname *</label>
                <input type="text" id="firstname" name="firstname" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="lastname">Nachname *</label>
                <input type="text" id="lastname" name="lastname" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="class">Klasse *</label>
                <input type="text" id="class" name="class" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="gender">Geschlecht</label>
                <select id="gender" name="gender" class="form-control">
                    <option value="">-</option>
                    <option value="m">Männlich</option>
                    <option value="w">Weiblich</option>
                    <option value="d">Divers</option>
                </select>
            </div>
            <div class="form-group">
                <label for="birth_year">Geburtsjahr</label>
                <input type="number" id="birth_year" name="birth_year" class="form-control">
            </div>
            <div class="form-group">
                <label for="grade">Jahrgang</label>
                <input type="number" id="grade" name="grade" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a href="students.php" class="btn btn-secondary">Abbrechen</a>
        </form>
    </div>
    <script>
    document.getElementById('class').addEventListener('input', function() {
        const classValue = this.value;
        const gradeInput = document.getElementById('grade');

        // Extract first continuous sequence of digits from class (e.g., "5a" -> 5, "10b" -> 10, "2024" -> 2024)
        const match = classValue.match(/(\d+)/);
        if (match) {
            gradeInput.value = match[1];
        } else {
            gradeInput.value = '';
        }
    });
    </script>
    <?php include 'views/footer.php'; exit;
}

if ($action === 'edit') {
    $stmt = $db->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$id]);
    $student = $stmt->fetch();

    $page_title = 'Schüler bearbeiten';
    include 'views/header.php';
    ?>
    <h1>Schüler bearbeiten</h1>
    <div class="card">
        <form method="POST" action="students.php?action=edit&id=<?php echo $id; ?>">
            <div class="form-group">
                <label for="firstname">Vorname *</label>
                <input type="text" id="firstname" name="firstname" class="form-control" value="<?php echo htmlspecialchars($student['firstname']); ?>" required>
            </div>
            <div class="form-group">
                <label for="lastname">Nachname *</label>
                <input type="text" id="lastname" name="lastname" class="form-control" value="<?php echo htmlspecialchars($student['lastname']); ?>" required>
            </div>
            <div class="form-group">
                <label for="class">Klasse *</label>
                <input type="text" id="class" name="class" class="form-control" value="<?php echo htmlspecialchars($student['class']); ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">Geschlecht</label>
                <select id="gender" name="gender" class="form-control">
                    <option value="">-</option>
                    <option value="m" <?php echo $student['gender'] === 'm' ? 'selected' : ''; ?>>Männlich</option>
                    <option value="w" <?php echo $student['gender'] === 'w' ? 'selected' : ''; ?>>Weiblich</option>
                    <option value="d" <?php echo $student['gender'] === 'd' ? 'selected' : ''; ?>>Divers</option>
                </select>
            </div>
            <div class="form-group">
                <label for="birth_year">Geburtsjahr</label>
                <input type="number" id="birth_year" name="birth_year" class="form-control" value="<?php echo $student['birth_year']; ?>">
            </div>
            <div class="form-group">
                <label for="grade">Jahrgang</label>
                <input type="number" id="grade" name="grade" class="form-control" value="<?php echo $student['grade']; ?>">
            </div>
            <button type="submit" class="btn btn-primary">Speichern</button>
            <a href="students.php" class="btn btn-secondary">Abbrechen</a>
        </form>
    </div>
    <script>
    const initialGrade = <?php echo $student['grade'] ? $student['grade'] : 'null'; ?>;
    document.getElementById('class').addEventListener('input', function() {
        const classValue = this.value;
        const gradeInput = document.getElementById('grade');

        // Extract first continuous sequence of digits from class (e.g., "5a" -> 5, "10b" -> 10, "2024" -> 2024)
        const match = classValue.match(/(\d+)/);
        if (match) {
            gradeInput.value = match[1];
        } else {
            gradeInput.value = '';
        }
    });
    </script>
    <?php include 'views/footer.php'; exit;
}

if ($action === 'import') {
    $page_title = 'Schüler importieren';
    include 'views/header.php';
    ?>
    <h1>Schüler importieren (CSV)</h1>
    <div class="card">
        <form method="POST" action="students.php?action=import" enctype="multipart/form-data" id="importForm">
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
                <strong>CSV-Format:</strong> Vorname, Nachname, Klasse, Geschlecht, Geburtsjahr, Jahrgang
            </p>
            <div class="flex flex-gap" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">Importieren</button>
                <a href="students.php" class="btn btn-secondary">Abbrechen</a>
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

// Default: List students
$page_title = 'Schülerverwaltung';
include 'views/header.php';
?>

<div class="flex justify-between align-center mb-3">
    <h1>Schülerverwaltung</h1>
    <div class="flex flex-gap">
        <button id="deleteSelectedStudentsBtn" onclick="deleteSelectedStudents()" class="btn btn-danger" style="display: none;">Ausgewählte löschen</button>
        <a href="students.php?action=add" class="btn btn-primary">Schüler hinzufügen</a>
        <a href="students.php?action=import" class="btn btn-secondary">CSV Import</a>
    </div>
</div>

<div class="card mb-3">
    <h2 style="font-size: 1.5rem; margin-bottom: 1rem;">Schnellexport pro Klasse</h2>
    <div class="flex flex-gap align-center" style="flex-wrap: wrap;">
        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px; max-width: 300px;">
            <label for="export_class">Klasse auswählen</label>
            <select id="export_class" class="form-control">
                <option value="">-- Klasse wählen --</option>
                <?php foreach ($classes as $class_item): ?>
                <option value="<?php echo htmlspecialchars($class_item['class']); ?>"><?php echo htmlspecialchars($class_item['class']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px; max-width: 300px;">
            <label for="export_disciplines_option">Disziplin-Auswahl</label>
            <select id="export_disciplines_option" class="form-control" onchange="toggleDisciplineSelect()">
                <option value="none">Nur Schülerdaten</option>
                <option value="all">Alle Disziplinen</option>
                <option value="specific">Einzelne Disziplinen</option>
            </select>
        </div>
        <div class="form-group" id="specific_disciplines_group" style="margin-bottom: 0; flex: 1; min-width: 200px; max-width: 300px; display: none;">
            <label for="export_specific_disciplines">Disziplinen wählen</label>
            <select id="export_specific_disciplines" class="form-control" multiple size="4">
                <?php foreach ($disciplines as $discipline): ?>
                <option value="<?php echo $discipline['id']; ?>"><?php echo htmlspecialchars($discipline['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button onclick="exportByClass()" class="btn btn-success" style="margin-top: 1.5rem;">Klasse exportieren</button>
    </div>
</div>

<div class="filter-panel">
    <h2>Filter</h2>
    <div class="filter-grid">
        <div class="form-group">
            <label for="filter_name">Name (Vor- oder Nachname)</label>
            <input type="text" id="filter_name" class="form-control" placeholder="Name eingeben...">
        </div>

        <div class="form-group">
            <label for="filter_class">Klasse</label>
            <select id="filter_class" class="form-control">
                <option value="">Alle Klassen</option>
                <?php foreach ($classes as $class_item): ?>
                <option value="<?php echo htmlspecialchars($class_item['class']); ?>"><?php echo htmlspecialchars($class_item['class']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="filter_grade">Jahrgang</label>
            <select id="filter_grade" class="form-control">
                <option value="">Alle Jahrgänge</option>
                <?php foreach ($grades as $grade): ?>
                <option value="<?php echo $grade['grade']; ?>"><?php echo $grade['grade']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="filter_gender">Geschlecht</label>
            <select id="filter_gender" class="form-control">
                <option value="">Alle</option>
                <option value="m">Männlich</option>
                <option value="w">Weiblich</option>
                <option value="d">Divers</option>
            </select>
        </div>
    </div>

    <div class="flex flex-gap mt-2">
        <button onclick="resetFilters()" class="btn btn-secondary">Filter zurücksetzen</button>
        <button onclick="exportStudents()" class="btn btn-success">Export (CSV)</button>
    </div>
</div>

<div id="students-container">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="selectAllStudents" onchange="toggleSelectAllStudents()">
                    </th>
                    <th>Vorname</th>
                    <th>Nachname</th>
                    <th>Klasse</th>
                    <th>Geschlecht</th>
                    <th>Geburtsjahr</th>
                    <th>Jahrgang</th>
                    <th>Werte</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody id="students-tbody">
                <!-- Students will be loaded dynamically -->
            </tbody>
        </table>
    </div>
</div>

<?php
$extra_js = <<<'JAVASCRIPT'
<style>
.discipline-results-container {
    background: var(--bg-light);
    padding: 0.75rem;
    border-radius: 4px;
    max-width: 400px;
    transition: background 0.3s ease;
}
table.discipline-results-table {
    background: transparent !important;
}
table.discipline-results-table thead {
    background: var(--primary) !important;
}
table.discipline-results-table tbody tr {
    background: transparent !important;
    border-bottom: none !important;
}
table.discipline-results-table tbody tr:hover {
    background: transparent !important;
    cursor: default !important;
    transform: none !important;
}
table.discipline-results-table tbody td {
    border: none !important;
}
.filter-grid .form-group {
    display: flex;
    flex-direction: column;
}
.filter-grid .form-group label {
    min-height: 1.5rem;
    margin-bottom: 0.5rem;
}
.filter-grid .form-control {
    height: 45px;
}
</style>
<script>
const BASE_URL = '/Sportfest';

function loadStudents() {
    const name = document.getElementById('filter_name').value;
    const classVal = document.getElementById('filter_class').value;
    const grade = document.getElementById('filter_grade').value;
    const gender = document.getElementById('filter_gender').value;

    const params = new URLSearchParams();
    if (name) params.append('name', name);
    if (classVal) params.append('class', classVal);
    if (grade) params.append('grade', grade);
    if (gender) params.append('gender', gender);

    fetch(BASE_URL + '/api.php?action=get-students&' + params.toString())
        .then(response => response.json())
        .then(data => {
            displayStudents(data);
        })
        .catch(error => {
            console.error('Error loading students:', error);
        });
}

function displayStudents(students) {
    const tbody = document.getElementById('students-tbody');

    if (students.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" class="text-center" style="padding: 2rem;">Keine Schüler gefunden. <a href="students.php?action=add">Ersten Schüler anlegen</a></td></tr>';
        return;
    }

    let html = '';
    students.forEach(student => {
        html += '<tr>';
        html += `<td><input type="checkbox" class="student-checkbox" value="${student.id}" onchange="updateDeleteStudentsButton()"></td>`;
        html += `<td>${student.firstname}</td>`;
        html += `<td>${student.lastname}</td>`;
        html += `<td><strong>${student.class}</strong></td>`;
        html += `<td>${student.gender || '-'}</td>`;
        html += `<td>${student.birth_year || '-'}</td>`;
        html += `<td>${student.grade || '-'}</td>`;
        html += `<td>`;
        html += `<button class="btn btn-sm btn-primary" onclick="toggleDisciplines(${student.id}, this)">Anzeigen</button>`;
        html += `<div id="disciplines-${student.id}" style="display: none; margin-top: 0.5rem;"></div>`;
        html += `</td>`;
        html += `<td>`;
        html += `<a href="students.php?action=edit&id=${student.id}" class="btn btn-sm btn-secondary">Bearbeiten</a> `;
        html += `<a href="students.php?action=delete&id=${student.id}" class="btn btn-sm btn-danger" onclick="return confirm('Schüler wirklich löschen?')">Löschen</a>`;
        html += `</td>`;
        html += '</tr>';
    });

    tbody.innerHTML = html;
}

function resetFilters() {
    document.getElementById('filter_name').value = '';
    document.getElementById('filter_class').value = '';
    document.getElementById('filter_grade').value = '';
    document.getElementById('filter_gender').value = '';
    loadStudents();
}

function exportStudents() {
    const name = document.getElementById('filter_name').value;
    const classVal = document.getElementById('filter_class').value;
    const grade = document.getElementById('filter_grade').value;
    const gender = document.getElementById('filter_gender').value;

    const params = new URLSearchParams();
    if (name) params.append('name', name);
    if (classVal) params.append('class', classVal);
    if (grade) params.append('grade', grade);
    if (gender) params.append('gender', gender);

    window.location.href = BASE_URL + '/api.php?action=export-students&' + params.toString();
}

function toggleDisciplineSelect() {
    const option = document.getElementById('export_disciplines_option').value;
    const specificGroup = document.getElementById('specific_disciplines_group');

    if (option === 'specific') {
        specificGroup.style.display = 'block';
    } else {
        specificGroup.style.display = 'none';
    }
}

function exportByClass() {
    const classVal = document.getElementById('export_class').value;
    const disciplinesOption = document.getElementById('export_disciplines_option').value;

    if (!classVal) {
        alert('Bitte wähle eine Klasse aus!');
        return;
    }

    const params = new URLSearchParams();
    params.append('class', classVal);
    params.append('include_disciplines', disciplinesOption);

    if (disciplinesOption === 'specific') {
        const specificSelect = document.getElementById('export_specific_disciplines');
        const selectedOptions = Array.from(specificSelect.selectedOptions).map(opt => opt.value);

        if (selectedOptions.length === 0) {
            alert('Bitte wähle mindestens eine Disziplin aus!');
            return;
        }

        params.append('discipline_ids', selectedOptions.join(','));
    }

    window.location.href = BASE_URL + '/api.php?action=export-students-with-results&' + params.toString();
}

function toggleSelectAllStudents() {
    const selectAll = document.getElementById('selectAllStudents');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    updateDeleteStudentsButton();
}

function updateDeleteStudentsButton() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    const deleteBtn = document.getElementById('deleteSelectedStudentsBtn');
    if (checkboxes.length > 0) {
        deleteBtn.style.display = 'block';
        deleteBtn.textContent = `${checkboxes.length} Ausgewählte löschen`;
    } else {
        deleteBtn.style.display = 'none';
    }
}

function deleteSelectedStudents() {
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);

    if (ids.length === 0) {
        alert('Bitte wähle mindestens einen Schüler aus.');
        return;
    }

    const count = ids.length;
    const message = count === 1
        ? 'Möchtest du diesen Schüler wirklich löschen?'
        : `Möchtest du diese ${count} Schüler wirklich löschen?`;

    if (confirm(message)) {
        fetch(BASE_URL + '/api.php?action=delete-students', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadStudents();
                document.getElementById('selectAllStudents').checked = false;
            } else {
                alert('Fehler beim Löschen: ' + data.message);
            }
        })
        .catch(error => {
            alert('Fehler beim Löschen der Schüler.');
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadStudents();

    document.getElementById('filter_class').addEventListener('change', loadStudents);
    document.getElementById('filter_grade').addEventListener('change', loadStudents);
    document.getElementById('filter_gender').addEventListener('change', loadStudents);
});

let nameFilterTimeout;
document.getElementById('filter_name').addEventListener('input', function(event) {
    clearTimeout(nameFilterTimeout);
    nameFilterTimeout = setTimeout(() => {
        loadStudents();
    }, 300);
});

function toggleDisciplines(studentId, button) {
    const disciplinesDiv = document.getElementById(`disciplines-${studentId}`);

    if (disciplinesDiv.style.display === 'none') {
        button.textContent = 'Laden...';
        button.disabled = true;

        fetch(BASE_URL + `/api.php?action=get-student-results&student_id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                displayDisciplines(studentId, data);
                disciplinesDiv.style.display = 'block';
                button.textContent = 'Ausblenden';
                button.disabled = false;
            })
            .catch(error => {
                console.error('Error loading disciplines:', error);
                button.textContent = 'Fehler';
                button.disabled = false;
            });
    } else {
        disciplinesDiv.style.display = 'none';
        button.textContent = 'Anzeigen';
    }
}

function displayDisciplines(studentId, results) {
    const disciplinesDiv = document.getElementById(`disciplines-${studentId}`);

    if (results.length === 0) {
        disciplinesDiv.innerHTML = '<p style="margin: 0; color: var(--gray); font-style: italic;">Keine Ergebnisse vorhanden</p>';
        return;
    }

    let html = '<div class="discipline-results-container">';
    html += '<table class="discipline-results-table" style="width: 100%; font-size: 0.9rem;">';
    html += '<thead><tr><th style="text-align: left; padding: 0.25rem; color: var(--white);">Disziplin</th><th style="text-align: right; padding: 0.25rem; color: var(--white);">Wert</th></tr></thead>';
    html += '<tbody>';

    results.forEach(result => {
        html += '<tr style="background: transparent !important;">';
        html += `<td style="padding: 0.25rem; color: var(--text-primary);">${result.discipline}</td>`;
        html += `<td style="text-align: right; padding: 0.25rem; color: var(--text-primary);"><strong>${result.value} ${result.unit}</strong></td>`;
        html += '</tr>';
    });

    html += '</tbody></table></div>';
    disciplinesDiv.innerHTML = html;
}
</script>
JAVASCRIPT;
?>

<?php include 'views/footer.php'; ?>
