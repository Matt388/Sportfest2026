<?php
require_once 'config.php';

// Get database connection
function get_db() {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $db;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Initialize database
function init_db() {
    $db = get_db();

    // Create users table
    $db->exec('
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT NOT NULL
        )
    ');

    // Create students table
    $db->exec('
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            firstname TEXT NOT NULL,
            lastname TEXT NOT NULL,
            class TEXT NOT NULL,
            gender TEXT,
            birth_year INTEGER,
            grade INTEGER
        )
    ');

    // Create disciplines table
    $db->exec('
        CREATE TABLE IF NOT EXISTS disciplines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            type TEXT NOT NULL,
            unit TEXT NOT NULL,
            attempts INTEGER DEFAULT 1
        )
    ');

    // Create results table
    $db->exec('
        CREATE TABLE IF NOT EXISTS results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            discipline_id INTEGER NOT NULL,
            value REAL NOT NULL,
            attempt_number INTEGER DEFAULT 1,
            timestamp TEXT NOT NULL,
            entered_by TEXT NOT NULL,
            FOREIGN KEY (student_id) REFERENCES students (id),
            FOREIGN KEY (discipline_id) REFERENCES disciplines (id)
        )
    ');

    // Add attempt_number column if it doesn't exist (for existing databases)
    try {
        $db->exec('ALTER TABLE results ADD COLUMN attempt_number INTEGER DEFAULT 1');
    } catch (PDOException $e) {
        // Column already exists, ignore
    }

    // Create default users if they don't exist
    try {
        $stmt = $db->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
        $stmt->execute(['admin', 'admin123', 'admin']);
        $stmt->execute(['user', 'user123', 'user']);
    } catch (PDOException $e) {
        // Users already exist, ignore error
    }
}

// Initialize database on first load
init_db();
?>
