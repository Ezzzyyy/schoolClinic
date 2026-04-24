<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Visit.php';

protectPage(1);

header('Content-Type: application/json');

$studentId = (int) ($_GET['student_id'] ?? 0);

if ($studentId === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    $visitModel = new Visit($conn);
    
    $visits = $visitModel->getByStudentId($studentId);
    
    $visitData = [];
    foreach ($visits as $v) {
        $visitData[] = [
            'visit_id' => (int) $v['visit_id'],
            'visit_date' => date('M d, Y h:i A', strtotime((string) $v['visit_date'])),
            'complaint' => $v['complaint'] ?? '',
            'diagnosis' => $v['diagnosis'] ?? 'Not diagnosed',
            'symptoms' => $v['symptoms'] ?? '',
            'treatment' => $v['treatment'] ?? '',
            'notes' => $v['notes'] ?? '',
            'status' => $v['status_name'] ?? '',
            'handler' => trim(($v['handler_first'] ?? '') . ' ' . ($v['handler_last'] ?? ''))
        ];
    }
    
    echo json_encode(['success' => true, 'visits' => $visitData]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching visits: ' . $e->getMessage()]);
}
