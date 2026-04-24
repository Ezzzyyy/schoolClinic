<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

header('Content-Type: application/json');

// Ensure only authorized staff (nurse/doctor) can update status
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['nurse', 'doctor'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    $studentModel = new Student($conn);

    $studentId = isset($_POST['studentId']) ? (int)$_POST['studentId'] : 0;
    $status    = $_POST['status'] ?? '';
    $remarks   = $_POST['remarks'] ?? null;

    if (!$studentId || !$status) {
        throw new Exception('Missing required information.');
    }

    // Valid statuses for the database ENUM
    $validStatuses = ['Active', 'Pending review', 'Inactive'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status value.');
    }

    if ($studentModel->updateStatus($studentId, $status, $remarks)) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
    } else {
        throw new Exception('Failed to update status in database.');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
