<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Settings.php';

protectPage(1);

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();
$settingsModel = new Settings($conn);

$backupDir = __DIR__ . '/../uploads/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$backupFile = $backupDir . 'backup_' . date('Y-m-d_His') . '.sql';

// Increase execution time and memory limit
set_time_limit(300);
ini_set('memory_limit', '256M');

try {
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    if (!$result) {
        throw new Exception('Failed to get tables');
    }
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sql = '';
    foreach ($tables as $table) {
        // Get CREATE TABLE statement
        $result = $conn->query("SHOW CREATE TABLE {$table}");
        if (!$result) {
            throw new Exception("Failed to get CREATE TABLE for {$table}");
        }
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $sql .= "\n\n" . $row['Create Table'] . ";\n\n";

        // Get table data
        $result = $conn->query("SELECT * FROM {$table}");
        if (!$result) {
            throw new Exception("Failed to select from {$table}");
        }

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $sql .= "INSERT INTO {$table} VALUES (";
            $values = [];
            foreach ($row as $value) {
                $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
            }
            $sql .= implode(', ', $values) . ");\n";
        }
    }

    $written = file_put_contents($backupFile, $sql);
    if ($written === false) {
        throw new Exception('Failed to write backup file');
    }

    // Update last backup date
    $settingsModel->saveMultiple(['last_backup_date' => date('Y-m-d H:i:s')]);
    
    echo json_encode(['success' => true, 'message' => 'Backup created successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()]);
}
