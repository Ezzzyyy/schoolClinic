<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Visit.php';
require_once __DIR__ . '/../classes/Medicine.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get Database Connection
$db = new Database();
$conn = $db->connect();

// Instantiate Models
$visitModel = new Visit($conn);
$medModel   = new Medicine($conn);

// Map Status Name to ID
$statusMap = [
    'Pending' => 1,
    'Completed' => 2,
    'Referred' => 3
];

// 1. Resolve student_id from student_number
$studentNumber = $_POST['student_id'] ?? '';
$stmt = $conn->prepare("SELECT student_id FROM students WHERE student_number = ?");
$stmt->execute([$studentNumber]);
$studentId = $stmt->fetchColumn();

if (!$studentId) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// 2. Prepare Data
$data = [
    'student_id'   => $studentId,
    'visit_date'   => $_POST['visit_date'] ?? date('Y-m-d H:i:s'),
    'complaint'    => $_POST['complaint'] ?? '',
    'symptoms'     => $_POST['symptoms'] ?? '',
    'diagnosis'    => $_POST['diagnosis'] ?? '',
    'treatment'    => $_POST['treatment'] ?? '',
    'visit_status' => $statusMap[$_POST['visit_status']] ?? 1,
    'handled_by'   => $_SESSION['user_id'],
    'notes'        => $_POST['notes'] ?? ''
];

// 3. Save Visit
$visitId = $visitModel->create($data);
if ($visitId) {
    // 4. Handle Dispensed Medicines
    $medicineIds = $_POST['medicine_id'] ?? [];
    $quantities  = $_POST['quantity'] ?? [];
    
    foreach ($medicineIds as $index => $medId) {
        $qty = (int)($quantities[$index] ?? 0);
        if ($medId > 0 && $qty > 0) {
            $medModel->dispense((int)$medId, $qty, (int)$_SESSION['user_id'], (int)$visitId);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Visit and medicine usage logged successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save visit']);
}
