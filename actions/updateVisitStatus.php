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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$visitId = isset($_POST['visit_id']) ? (int)$_POST['visit_id'] : 0;
$statusId = isset($_POST['status_id']) ? (int)$_POST['status_id'] : 0;
$remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';

if (!$visitId || !$statusId) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$db = new Database();
$conn = $db->connect();
$visitModel = new Visit($conn);

// Update visit status and remarks
$stmt = $conn->prepare("UPDATE clinic_visits SET visit_status = ?, notes = ? WHERE visit_id = ?");
$result = $stmt->execute([$statusId, $remarks, $visitId]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Visit status updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update visit status.']);
}
