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

// Validate required fields
$name     = trim($_POST['medName'] ?? '');
$category = trim($_POST['medCategory'] ?? '');
$unit     = trim($_POST['medUnit'] ?? 'tablets');
$qty      = (int)($_POST['medQty'] ?? 0);
$reorder  = (int)($_POST['reorderLevel'] ?? 10);
$expiry   = trim($_POST['expiryDate'] ?? '');
$location = trim($_POST['location'] ?? '');
$notes    = trim($_POST['medNotes'] ?? '');

if ($name === '' || $category === '' || $qty < 0) {
    echo json_encode(['success' => false, 'message' => 'Medicine name, category, and a valid quantity are required.']);
    exit;
}

if ($expiry === '') {
    echo json_encode(['success' => false, 'message' => 'Expiry date is required.']);
    exit;
}

// Convert month input (YYYY-MM) to a full date (last day of month)
if (strlen($expiry) === 7) {
    $expiry = $expiry . '-01';
    $expiry = date('Y-m-t', strtotime($expiry)); // last day of month
}

$db   = new Database();
$conn = $db->connect();
$med  = new Medicine($conn);

$handledBy = (int)($_SESSION['user_id'] ?? 0);

$newId = $med->add([
    'name'            => $name,
    'category'        => $category,
    'quantity'        => $qty,
    'unit'            => $unit,
    'reorder_level'   => $reorder,
    'expiration_date' => $expiry,
    'location'        => $location,
    'notes'           => $notes
], $handledBy);

if ($newId > 0) {
    echo json_encode(['success' => true, 'message' => "$name added to inventory.", 'id' => $newId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add medicine. Please try again.']);
}
