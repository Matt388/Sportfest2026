<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

require_login();

$db = get_db();
$disciplines = $db->query('SELECT * FROM disciplines ORDER BY name')->fetchAll();
$classes = $db->query('SELECT DISTINCT class FROM students ORDER BY class')->fetchAll();

$students = [];
$selected_discipline = null;
$selected_class = '';
$existing_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discipline_id = $_POST['discipline_id'] ?? 0;
    $class_name = $_POST['class'] ?? '';

    if ($discipline_id && $class_name) {
        $stmt = $db->prepare('SELECT * FROM students WHERE class = ? ORDER BY lastname');
        $stmt->execute([$class_name]);
        $students = $stmt->fetchAll();

        $stmt = $db->prepare('SELECT * FROM disciplines WHERE id = ?');
        $stmt->execute([$discipline_id]);
        $selected_discipline = $stmt->fetch();

        // Load all existing attempts for this discipline and class
        $stmt = $db->prepare('
            SELECT student_id, attempt_number, value FROM results
            WHERE discipline_id = ? AND student_id IN (
                SELECT id FROM students WHERE class = ?
            )
            ORDER BY student_id, attempt_number
        ');
        $stmt->execute([$discipline_id, $class_name]);
        $results = $stmt->fetchAll();

        foreach ($results as $r) {
            if (!isset($existing_results[$r['student_id']])) {
                $existing_results[$r['student_id']] = [];
            }
            $existing_results[$r['student_id']][$r['attempt_number']] = $r['value'];
        }

        $selected_class = $class_name;
    }
}

$page_title = 'Ergebnisse erfassen';
include 'views/header.php';
?>

<h1>Ergebnisse erfassen</h1>

<?php if (empty($disciplines)): ?>
<div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 2rem;">
    <h2 style="color: #856404; margin-bottom: 1rem;">⚠️ Keine Disziplinen vorhanden</h2>
    <p style="color: #856404;">Es wurden noch keine Disziplinen angelegt. Bitte erstellen Sie zuerst Disziplinen unter <a href="<?php echo url_for('disciplines.php'); ?>">Disziplinenverwaltung</a>.</p>
</div>
<?php endif; ?>

<?php if (empty($classes)): ?>
<div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 2rem;">
    <h2 style="color: #856404; margin-bottom: 1rem;">⚠️ Keine Schüler vorhanden</h2>
    <p style="color: #856404;">Es wurden noch keine Schüler angelegt. Bitte erstellen Sie zuerst Schüler unter <a href="<?php echo url_for('students.php'); ?>">Schülerverwaltung</a>.</p>
</div>
<?php endif; ?>

<div class="card">
    <form method="POST">
        <div class="filter-grid">
            <div class="form-group">
                <label for="discipline_id">Disziplin *</label>
                <select id="discipline_id" name="discipline_id" class="form-control" required>
                    <option value="">Bitte wählen</option>
                    <?php foreach ($disciplines as $discipline): ?>
                    <option value="<?php echo $discipline['id']; ?>" <?php echo ($selected_discipline && $selected_discipline['id'] == $discipline['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($discipline['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="class">Klasse *</label>
                <select id="class" name="class" class="form-control" required>
                    <option value="">Bitte wählen</option>
                    <?php foreach ($classes as $class_item): ?>
                    <option value="<?php echo htmlspecialchars($class_item['class']); ?>" <?php echo ($selected_class === $class_item['class']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class_item['class']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" id="loadStudentsBtn">Schülerliste laden</button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const disciplineSelect = document.getElementById('discipline_id');
    const classSelect = document.getElementById('class');
    const form = disciplineSelect.closest('form');

    // Auto-submit form when both filters are selected
    function autoSubmitIfReady() {
        if (disciplineSelect.value && classSelect.value) {
            form.submit();
        }
    }

    disciplineSelect.addEventListener('change', autoSubmitIfReady);
    classSelect.addEventListener('change', autoSubmitIfReady);
});
</script>

<?php if (!empty($students) && $selected_discipline): ?>
<div class="card">
    <h2><?php echo htmlspecialchars($selected_discipline['name']); ?> - Klasse <?php echo htmlspecialchars($selected_class); ?></h2>
    <p><strong>Einheit:</strong> <?php echo htmlspecialchars($selected_discipline['unit']); ?> |
       <strong>Typ:</strong>
       <?php
       if ($selected_discipline['type'] === 'time') echo 'Zeit (niedriger = besser)';
       elseif ($selected_discipline['type'] === 'distance') echo 'Weite/Höhe (höher = besser)';
       else echo 'Punkte';
       ?> |
       <strong>Versuche:</strong> <?php echo $selected_discipline['attempts']; ?>
    </p>

    <div class="results-grid mt-3">
        <?php foreach ($students as $student): ?>
        <div class="result-row" style="grid-template-columns: 2fr repeat(<?php echo $selected_discipline['attempts']; ?>, 1fr) 1fr 1fr;">
            <div><strong><?php echo htmlspecialchars($student['lastname']); ?>, <?php echo htmlspecialchars($student['firstname']); ?></strong></div>
            <?php for ($attempt = 1; $attempt <= $selected_discipline['attempts']; $attempt++):
                $saved_value = isset($existing_results[$student['id']][$attempt]) ? $existing_results[$student['id']][$attempt] : '';
            ?>
            <div>
                <label style="font-size: 0.8rem; color: #666;">Versuch <?php echo $attempt; ?></label>
                <input type="text"
                       class="form-control result-input"
                       data-student-id="<?php echo $student['id']; ?>"
                       data-discipline-id="<?php echo $selected_discipline['id']; ?>"
                       data-attempt="<?php echo $attempt; ?>"
                       placeholder="Versuch <?php echo $attempt; ?>"
                       pattern="[0-9]+([.,][0-9]+)?"
                       value="<?php echo htmlspecialchars($saved_value); ?>">
            </div>
            <?php endfor; ?>
            <div>
                <label style="font-size: 0.8rem; color: #666;">Bestes</label>
                <span class="best-result" id="best-<?php echo $student['id']; ?>">
                    <?php
                    if (isset($existing_results[$student['id']]) && !empty($existing_results[$student['id']])) {
                        $values = array_values($existing_results[$student['id']]);
                        if ($selected_discipline['type'] === 'time' || $selected_discipline['type'] === 'points') {
                            echo min($values);
                        } else {
                            echo max($values);
                        }
                    } else {
                        echo '-';
                    }
                    ?>
                </span>
            </div>
            <div>
                <span class="save-status" id="status-<?php echo $student['id']; ?>">-</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$discipline_type = $selected_discipline['type'] ?? '';
$discipline_id = $selected_discipline['id'] ?? '';
$extra_js = <<<JAVASCRIPT
<script>
const BASE_URL = '/Sportfest';
const disciplineType = '{$discipline_type}';
const disciplineId = '{$discipline_id}';
const selectedClass = '{$selected_class}';

document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.result-input');
    const studentAttempts = {};

    // Initialize attempts from existing values
    inputs.forEach(input => {
        const studentId = input.dataset.studentId;
        const attempt = input.dataset.attempt;
        const value = input.value;

        if (!studentAttempts[studentId]) {
            studentAttempts[studentId] = {};
        }
        if (value) {
            studentAttempts[studentId][attempt] = value.replace(',', '.');
        }
    });

    inputs.forEach(input => {
        let timeout = null;

        input.addEventListener('input', function() {
            const studentId = this.dataset.studentId;
            const attemptNumber = this.dataset.attempt;
            const value = this.value.replace(',', '.');

            if (!studentAttempts[studentId]) {
                studentAttempts[studentId] = {};
            }
            studentAttempts[studentId][attemptNumber] = value;

            clearTimeout(timeout);
            timeout = setTimeout(() => {
                if (value && !isNaN(parseFloat(value))) {
                    // Save this specific attempt
                    saveAttempt(studentId, attemptNumber, parseFloat(value));
                }
                // Update best result display
                updateBestResult(studentId, studentAttempts[studentId]);
            }, 500);
        });
    });
});

function updateBestResult(studentId, attempts) {
    const values = Object.values(attempts).filter(v => v && !isNaN(parseFloat(v))).map(v => parseFloat(v));

    if (values.length === 0) {
        document.getElementById('best-' + studentId).textContent = '-';
        return;
    }

    let bestValue;
    if (disciplineType === 'time' || disciplineType === 'points') {
        bestValue = Math.min(...values);
    } else {
        bestValue = Math.max(...values);
    }

    const bestSpan = document.getElementById('best-' + studentId);
    bestSpan.textContent = bestValue.toFixed(2);
}

function saveAttempt(studentId, attemptNumber, value) {
    const statusSpan = document.getElementById('status-' + studentId);
    statusSpan.textContent = '💾 Speichern...';

    fetch(BASE_URL + '/api.php?action=save-result', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            student_id: studentId,
            discipline_id: disciplineId,
            attempt_number: attemptNumber,
            value: value
        })
    })
    .then(response => response.json())
    .then(data => {
        statusSpan.textContent = '✓ Gespeichert';
        setTimeout(() => {
            statusSpan.textContent = '-';
        }, 2000);
    })
    .catch(error => {
        statusSpan.textContent = '✕ Fehler';
        console.error('Error saving result:', error);
    });
}
</script>
JAVASCRIPT;
?>

<?php include 'views/footer.php'; ?>
