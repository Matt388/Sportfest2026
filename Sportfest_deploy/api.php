<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'db.php';

// Get the requested action
$action = $_GET['action'] ?? '';

// Set JSON header for API responses
header('Content-Type: application/json');

try {
    switch ($action) {
        case 'get-students':
            require_login();
            require_admin();
            echo json_encode(get_students_filtered());
            break;

        case 'delete-students':
            require_login();
            require_admin();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(delete_students_bulk($data['ids'] ?? []));
            break;

        case 'export-students':
            require_login();
            require_admin();
            export_students();
            break;

        case 'export-students-with-results':
            require_login();
            require_admin();
            export_students_with_results();
            break;

        case 'get-student-results':
            require_login();
            $student_id = $_GET['student_id'] ?? 0;
            echo json_encode(get_student_results($student_id));
            break;

        case 'delete-disciplines':
            require_login();
            require_admin();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(delete_disciplines_bulk($data['ids'] ?? []));
            break;

        case 'save-result':
            require_login();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(save_result($data));
            break;

        case 'get-results':
            require_login();
            echo json_encode(get_results_filtered());
            break;

        case 'export-results':
            require_login();
            export_results();
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// API Functions

function get_students_filtered() {
    $db = get_db();
    $name = $_GET['name'] ?? '';
    $class = $_GET['class'] ?? '';
    $grade = $_GET['grade'] ?? '';
    $gender = $_GET['gender'] ?? '';

    $query = 'SELECT * FROM students WHERE 1=1';
    $params = [];

    if ($name) {
        $query .= ' AND (LOWER(firstname) LIKE ? OR LOWER(lastname) LIKE ?)';
        $search_pattern = '%' . strtolower($name) . '%';
        $params[] = $search_pattern;
        $params[] = $search_pattern;
    }
    if ($class) {
        $query .= ' AND class = ?';
        $params[] = $class;
    }
    if ($grade) {
        $query .= ' AND grade = ?';
        $params[] = (int)$grade;
    }
    if ($gender) {
        $query .= ' AND gender = ?';
        $params[] = $gender;
    }

    $query .= ' ORDER BY class, lastname';

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function delete_students_bulk($ids) {
    if (empty($ids)) {
        return ['success' => false, 'message' => 'Keine IDs angegeben'];
    }

    try {
        $db = get_db();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $db->prepare("DELETE FROM students WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        $stmt = $db->prepare("DELETE FROM results WHERE student_id IN ($placeholders)");
        $stmt->execute($ids);

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function export_students() {
    $db = get_db();
    $name = $_GET['name'] ?? '';
    $class = $_GET['class'] ?? '';
    $grade = $_GET['grade'] ?? '';
    $gender = $_GET['gender'] ?? '';

    $query = 'SELECT * FROM students WHERE 1=1';
    $params = [];

    if ($name) {
        $query .= ' AND (LOWER(firstname) LIKE ? OR LOWER(lastname) LIKE ?)';
        $search_pattern = '%' . strtolower($name) . '%';
        $params[] = $search_pattern;
        $params[] = $search_pattern;
    }
    if ($class) {
        $query .= ' AND class = ?';
        $params[] = $class;
    }
    if ($grade) {
        $query .= ' AND grade = ?';
        $params[] = (int)$grade;
    }
    if ($gender) {
        $query .= ' AND gender = ?';
        $params[] = $gender;
    }

    $query .= ' ORDER BY class, lastname';

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    // Generate filename
    $filename = 'schueler';
    if ($class) $filename .= '_klasse_' . $class;
    if ($grade) $filename .= '_jahrgang_' . $grade;
    $filename .= '_' . date('Ymd') . '.csv';

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Vorname', 'Nachname', 'Klasse', 'Geschlecht', 'Geburtsjahr', 'Jahrgang']);

    foreach ($students as $student) {
        fputcsv($output, [
            $student['firstname'],
            $student['lastname'],
            $student['class'],
            $student['gender'] ?? '',
            $student['birth_year'] ?? '',
            $student['grade'] ?? ''
        ]);
    }

    fclose($output);
    exit;
}

function export_students_with_results() {
    $db = get_db();
    $class = $_GET['class'] ?? '';
    $include_disciplines = $_GET['include_disciplines'] ?? 'none';

    if (!$class) {
        http_response_code(400);
        echo "Klasse erforderlich";
        exit;
    }

    $stmt = $db->prepare('SELECT * FROM students WHERE class = ? ORDER BY lastname, firstname');
    $stmt->execute([$class]);
    $students = $stmt->fetchAll();

    $disciplines = [];
    if ($include_disciplines === 'all') {
        $disciplines = $db->query('SELECT * FROM disciplines ORDER BY name')->fetchAll();
    } elseif ($include_disciplines === 'specific') {
        $discipline_ids = explode(',', $_GET['discipline_ids'] ?? '');
        if (!empty($discipline_ids) && $discipline_ids[0]) {
            $placeholders = implode(',', array_fill(0, count($discipline_ids), '?'));
            $stmt = $db->prepare("SELECT * FROM disciplines WHERE id IN ($placeholders) ORDER BY name");
            $stmt->execute($discipline_ids);
            $disciplines = $stmt->fetchAll();
        }
    }

    $filename = 'schueler_klasse_' . $class;
    if (in_array($include_disciplines, ['all', 'specific'])) {
        $filename .= '_mit_ergebnissen';
    }
    $filename .= '_' . date('Ymd') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Create header
    $header = ['Vorname', 'Nachname', 'Klasse', 'Geschlecht', 'Geburtsjahr', 'Jahrgang'];
    if (in_array($include_disciplines, ['all', 'specific']) && !empty($disciplines)) {
        foreach ($disciplines as $disc) {
            $header[] = $disc['name'] . ' (' . $disc['unit'] . ')';
        }
    }
    fputcsv($output, $header);

    // Write student data
    foreach ($students as $student) {
        $row = [
            $student['firstname'],
            $student['lastname'],
            $student['class'],
            $student['gender'] ?? '',
            $student['birth_year'] ?? '',
            $student['grade'] ?? ''
        ];

        if (in_array($include_disciplines, ['all', 'specific']) && !empty($disciplines)) {
            foreach ($disciplines as $disc) {
                $stmt = $db->prepare('SELECT value FROM results WHERE student_id = ? AND discipline_id = ?');
                $stmt->execute([$student['id'], $disc['id']]);
                $result = $stmt->fetch();
                $row[] = $result ? $result['value'] : '';
            }
        }

        fputcsv($output, $row);
    }

    fclose($output);
    exit;
}

function get_student_results($student_id) {
    $db = get_db();
    $stmt = $db->prepare('
        SELECT d.name as discipline, d.unit, d.type,
               CASE
                   WHEN d.type IN ("time", "points") THEN MIN(r.value)
                   ELSE MAX(r.value)
               END as value
        FROM results r
        JOIN disciplines d ON r.discipline_id = d.id
        WHERE r.student_id = ?
        GROUP BY d.id, d.name, d.unit, d.type
        ORDER BY d.name
    ');
    $stmt->execute([$student_id]);
    return $stmt->fetchAll();
}

function delete_disciplines_bulk($ids) {
    if (empty($ids)) {
        return ['success' => false, 'message' => 'Keine IDs angegeben'];
    }

    try {
        $db = get_db();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $db->prepare("DELETE FROM disciplines WHERE id IN ($placeholders)");
        $stmt->execute($ids);

        $stmt = $db->prepare("DELETE FROM results WHERE discipline_id IN ($placeholders)");
        $stmt->execute($ids);

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function save_result($data) {
    $db = get_db();
    $student_id = $data['student_id'] ?? 0;
    $discipline_id = $data['discipline_id'] ?? 0;
    $value = $data['value'] ?? 0;
    $attempt_number = $data['attempt_number'] ?? 1;

    // Check if this specific attempt already exists
    $stmt = $db->prepare('SELECT id FROM results WHERE student_id = ? AND discipline_id = ? AND attempt_number = ?');
    $stmt->execute([$student_id, $discipline_id, $attempt_number]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Update existing attempt
        $stmt = $db->prepare('UPDATE results SET value = ?, timestamp = ?, entered_by = ? WHERE id = ?');
        $stmt->execute([$value, date('Y-m-d H:i:s'), $_SESSION['username'], $existing['id']]);
    } else {
        // Insert new attempt
        $stmt = $db->prepare('INSERT INTO results (student_id, discipline_id, value, attempt_number, timestamp, entered_by) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$student_id, $discipline_id, $value, $attempt_number, date('Y-m-d H:i:s'), $_SESSION['username']]);
    }

    return ['status' => 'success'];
}

function get_results_filtered() {
    $db = get_db();
    $discipline_id = $_GET['discipline_id'] ?? '';
    $class = $_GET['class'] ?? '';
    $grade = $_GET['grade'] ?? '';
    $gender = $_GET['gender'] ?? '';

    // Build query to get best result for each student
    $query = '
        SELECT s.firstname, s.lastname, s.class, s.gender, s.grade,
               d.name as discipline, d.type, d.unit,
               CASE
                   WHEN d.type IN ("time", "points") THEN MIN(r.value)
                   ELSE MAX(r.value)
               END as value
        FROM results r
        JOIN students s ON r.student_id = s.id
        JOIN disciplines d ON r.discipline_id = d.id
        WHERE 1=1
    ';
    $params = [];

    if ($discipline_id) {
        $query .= ' AND d.id = ?';
        $params[] = $discipline_id;
    }
    if ($class) {
        $query .= ' AND s.class = ?';
        $params[] = $class;
    }
    if ($grade) {
        $query .= ' AND s.grade = ?';
        $params[] = (int)$grade;
    }
    if ($gender) {
        $query .= ' AND s.gender = ?';
        $params[] = $gender;
    }

    // Group by student and discipline to get best result
    $query .= ' GROUP BY s.id, d.id';

    if ($discipline_id) {
        $stmt = $db->prepare('SELECT type FROM disciplines WHERE id = ?');
        $stmt->execute([$discipline_id]);
        $disc = $stmt->fetch();
        if ($disc) {
            $discipline_type = $disc['type'];
            if (in_array($discipline_type, ['time', 'points'])) {
                $query .= ' ORDER BY value ASC';
            } else {
                $query .= ' ORDER BY value DESC';
            }
        }
    } else {
        $query .= ' ORDER BY d.name, value ASC';
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function export_results() {
    $db = get_db();
    $discipline_id = $_GET['discipline_id'] ?? '';
    $class = $_GET['class'] ?? '';
    $grade = $_GET['grade'] ?? '';
    $gender = $_GET['gender'] ?? '';

    // Build query to get best result for each student
    $query = '
        SELECT s.firstname, s.lastname, s.class, s.gender, s.grade,
               d.name as discipline, d.type, d.unit,
               CASE
                   WHEN d.type IN ("time", "points") THEN MIN(r.value)
                   ELSE MAX(r.value)
               END as value
        FROM results r
        JOIN students s ON r.student_id = s.id
        JOIN disciplines d ON r.discipline_id = d.id
        WHERE 1=1
    ';
    $params = [];

    if ($discipline_id) {
        $query .= ' AND d.id = ?';
        $params[] = $discipline_id;
    }
    if ($class) {
        $query .= ' AND s.class = ?';
        $params[] = $class;
    }
    if ($grade) {
        $query .= ' AND s.grade = ?';
        $params[] = (int)$grade;
    }
    if ($gender) {
        $query .= ' AND s.gender = ?';
        $params[] = $gender;
    }

    // Group by student and discipline to get best result
    $query .= ' GROUP BY s.id, d.id';

    if ($discipline_id) {
        $stmt = $db->prepare('SELECT type FROM disciplines WHERE id = ?');
        $stmt->execute([$discipline_id]);
        $disc = $stmt->fetch();
        if ($disc) {
            $discipline_type = $disc['type'];
            if (in_array($discipline_type, ['time', 'points'])) {
                $query .= ' ORDER BY value ASC';
            } else {
                $query .= ' ORDER BY value DESC';
            }
        }
    } else {
        $query .= ' ORDER BY d.name, value ASC';
    }

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    $filename = 'sportfest_ergebnisse_' . date('Ymd') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Platz', 'Vorname', 'Nachname', 'Klasse', 'Geschlecht', 'Jahrgang', 'Disziplin', 'Ergebnis', 'Einheit']);

    foreach ($results as $idx => $row) {
        fputcsv($output, [
            $idx + 1,
            $row['firstname'],
            $row['lastname'],
            $row['class'],
            $row['gender'] ?? '',
            $row['grade'] ?? '',
            $row['discipline'],
            $row['value'],
            $row['unit']
        ]);
    }

    fclose($output);
    exit;
}
?>
