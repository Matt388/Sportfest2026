<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

require_login();

$db = get_db();
$disciplines = $db->query('SELECT * FROM disciplines ORDER BY name')->fetchAll();
$classes = $db->query('SELECT DISTINCT class FROM students ORDER BY class')->fetchAll();
$grades = $db->query('SELECT DISTINCT grade FROM students WHERE grade IS NOT NULL ORDER BY grade')->fetchAll();

$page_title = 'Ergebnisse';
include 'views/header.php';
?>

<h1>Ergebnisse & Ranglisten</h1>

<?php if (empty($disciplines)): ?>
<div class="card" style="background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 2rem;">
    <h2 style="color: #856404; margin-bottom: 1rem;">⚠️ Keine Disziplinen vorhanden</h2>
    <p style="color: #856404;">Es wurden noch keine Disziplinen angelegt. Bitte erstellen Sie zuerst Disziplinen unter <a href="<?php echo url_for('disciplines.php'); ?>">Disziplinenverwaltung</a>.</p>
</div>
<?php endif; ?>

<?php
$total_results = $db->query('SELECT COUNT(*) as count FROM results')->fetch();
if ($total_results['count'] == 0):
?>
<div class="card" style="background: #d1ecf1; border-left: 4px solid #17a2b8; margin-bottom: 2rem;">
    <h2 style="color: #0c5460; margin-bottom: 1rem;">ℹ️ Keine Ergebnisse vorhanden</h2>
    <p style="color: #0c5460;">Es wurden noch keine Ergebnisse erfasst. Bitte erfassen Sie zuerst Ergebnisse unter <a href="<?php echo url_for('enter_results.php'); ?>">Erfassen</a>.</p>
</div>
<?php endif; ?>

<div class="filter-panel">
    <h2>Filter</h2>
    <div class="filter-grid">
        <div class="form-group">
            <label for="filter_discipline">Disziplin</label>
            <select id="filter_discipline" class="form-control">
                <option value="">Alle Disziplinen</option>
                <?php foreach ($disciplines as $discipline): ?>
                <option value="<?php echo $discipline['id']; ?>"><?php echo htmlspecialchars($discipline['name']); ?></option>
                <?php endforeach; ?>
            </select>
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
        <button onclick="exportResults()" class="btn btn-success">Export (CSV)</button>
    </div>
</div>

<div id="results-container">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Platz</th>
                    <th>Vorname</th>
                    <th>Nachname</th>
                    <th>Klasse</th>
                    <th>Geschlecht</th>
                    <th>Jahrgang</th>
                    <th>Disziplin</th>
                    <th>Ergebnis</th>
                </tr>
            </thead>
            <tbody id="results-tbody">
                <tr><td colspan="8" class="text-center" style="padding: 2rem;">Bitte wähle einen Filter aus, um Ergebnisse anzuzeigen.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php
$extra_js = <<<'JAVASCRIPT'
<script>
const BASE_URL = '/Sportfest';

function loadResults() {
    const discipline = document.getElementById('filter_discipline').value;
    const classVal = document.getElementById('filter_class').value;
    const grade = document.getElementById('filter_grade').value;
    const gender = document.getElementById('filter_gender').value;

    const params = new URLSearchParams();
    if (discipline) params.append('discipline_id', discipline);
    if (classVal) params.append('class', classVal);
    if (grade) params.append('grade', grade);
    if (gender) params.append('gender', gender);

    fetch(BASE_URL + '/api.php?action=get-results&' + params.toString())
        .then(response => response.json())
        .then(data => {
            displayResults(data);
        })
        .catch(error => {
            console.error('Error loading results:', error);
        });
}

function displayResults(results) {
    const tbody = document.getElementById('results-tbody');
    const blurEnabled = localStorage.getItem('blur_results') === 'true';

    if (results.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="padding: 2rem;">Keine Ergebnisse gefunden.</td></tr>';
        return;
    }

    let html = '';
    results.forEach((result, index) => {
        const blurClass = blurEnabled ? 'blur-text' : '';
        const place = index + 1;
        let placeDisplay = '';

        // Medal icons for top 3
        if (place === 1) {
            placeDisplay = '<span class="medal gold" title="1. Platz" style="font-size: 1.8rem;">🥇</span>';
        } else if (place === 2) {
            placeDisplay = '<span class="medal silver" title="2. Platz" style="font-size: 1.8rem;">🥈</span>';
        } else if (place === 3) {
            placeDisplay = '<span class="medal bronze" title="3. Platz" style="font-size: 1.8rem;">🥉</span>';
        } else {
            placeDisplay = `<strong>${place}</strong>`;
        }

        html += '<tr>';
        html += `<td>${placeDisplay}</td>`;
        html += `<td class="${blurClass}">${result.firstname}</td>`;
        html += `<td class="${blurClass}">${result.lastname}</td>`;
        html += `<td>${result.class}</td>`;
        html += `<td>${result.gender || '-'}</td>`;
        html += `<td>${result.grade || '-'}</td>`;
        html += `<td>${result.discipline}</td>`;
        html += `<td><strong>${result.value} ${result.unit}</strong></td>`;
        html += '</tr>';
    });

    tbody.innerHTML = html;
}

function resetFilters() {
    document.getElementById('filter_discipline').value = '';
    document.getElementById('filter_class').value = '';
    document.getElementById('filter_grade').value = '';
    document.getElementById('filter_gender').value = '';
    loadResults();
}

function exportResults() {
    const discipline = document.getElementById('filter_discipline').value;
    const classVal = document.getElementById('filter_class').value;
    const grade = document.getElementById('filter_grade').value;
    const gender = document.getElementById('filter_gender').value;

    const params = new URLSearchParams();
    if (discipline) params.append('discipline_id', discipline);
    if (classVal) params.append('class', classVal);
    if (grade) params.append('grade', grade);
    if (gender) params.append('gender', gender);

    window.location.href = BASE_URL + '/api.php?action=export-results&' + params.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('filter_discipline').addEventListener('change', loadResults);
    document.getElementById('filter_class').addEventListener('change', loadResults);
    document.getElementById('filter_grade').addEventListener('change', loadResults);
    document.getElementById('filter_gender').addEventListener('change', loadResults);

    loadResults();
});
</script>
JAVASCRIPT;
?>

<?php include 'views/footer.php'; ?>
