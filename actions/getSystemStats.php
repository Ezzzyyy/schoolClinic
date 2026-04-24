<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

header('Content-Type: application/json');

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Get database size
    $dbName = $conn->query('SELECT DATABASE()')->fetchColumn();
    $sizeStmt = $conn->prepare("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size_mb
        FROM information_schema.tables 
        WHERE table_schema = ?
    ");
    $sizeStmt->execute([$dbName]);
    $dbSize = $sizeStmt->fetch(PDO::FETCH_ASSOC)['size_mb'] ?? 0;
    
    // Get counts
    $userCount = $conn->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $studentCount = $conn->query('SELECT COUNT(*) FROM students')->fetchColumn();
    $visitCount = $conn->query('SELECT COUNT(*) FROM clinic_visits')->fetchColumn();
    $medicineCount = $conn->query('SELECT COUNT(*) FROM medicines')->fetchColumn();
    $reportCount = $conn->query('SELECT COUNT(*) FROM report_logs')->fetchColumn();
    $auditCount = $conn->query('SELECT COUNT(*) FROM audit_logs')->fetchColumn();
    
    // Get last backup date from settings
    $backupStmt = $conn->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $backupStmt->execute(['last_backup_date']);
    $lastBackup = $backupStmt->fetchColumn() ?? 'Never';
    
    echo json_encode([
        'success' => true,
        'db_size_mb' => (float) $dbSize,
        'users' => (int) $userCount,
        'students' => (int) $studentCount,
        'visits' => (int) $visitCount,
        'medicines' => (int) $medicineCount,
        'reports' => (int) $reportCount,
        'audit_logs' => (int) $auditCount,
        'last_backup' => $lastBackup,
        'app_version' => 'ClinIQ v1.0.0'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load system statistics'
    ]);
}
