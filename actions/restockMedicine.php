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

$id  = (int)($_POST['medId'] ?? 0);
$qty = (int)($_POST['restockQty'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid medicine ID.']);
    exit;
}

if ($qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'Restock quantity must be greater than zero.']);
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

$handledBy = (int)($_SESSION['user_id'] ?? 0);

$ok = $med->restock($id, $qty, $handledBy);

if ($ok) {
    $newQty = (int)$existing['quantity'] + $qty;
    echo json_encode([
        'success' => true,
        'message' => "Restocked {$existing['name']} with $qty {$existing['unit']}. New total: $newQty."
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to restock. Please try again.']);
}
