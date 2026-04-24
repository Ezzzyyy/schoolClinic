<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Medicine.php';

protectPage(1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$id = (int)($_POST['medId'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid medicine ID.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();
$med  = new Medicine($conn);

$existing = $med->getById($id);
if (!$existing) {
    echo json_encode(['success' => false, 'message' => 'Medicine not found.']);
    exit;
}

$ok = $med->delete($id);

if ($ok) {
    echo json_encode(['success' => true, 'message' => "{$existing['name']} has been removed from inventory."]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "Cannot delete {$existing['name']} — it has dispensing records linked to clinic visits."
    ]);
}
