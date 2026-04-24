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

$id       = (int)($_POST['medId'] ?? 0);
$name     = trim($_POST['medName'] ?? '');
$category = trim($_POST['medCategory'] ?? '');
$unit     = trim($_POST['medUnit'] ?? 'tablets');
$qty      = (int)($_POST['medQty'] ?? 0);
$reorder  = (int)($_POST['reorderLevel'] ?? 10);
$expiry   = trim($_POST['expiryDate'] ?? '');
$location = trim($_POST['location'] ?? '');
$notes    = trim($_POST['medNotes'] ?? '');

if ($id <= 0 || $name === '' || $category === '') {
    echo json_encode(['success' => false, 'message' => 'Medicine ID, name, and category are required.']);
    exit;
}

// Convert month input (YYYY-MM) to full date
if (strlen($expiry) === 7) {
    $expiry = date('Y-m-t', strtotime($expiry . '-01'));
}

$db   = new Database();
$conn = $db->connect();
$med  = new Medicine($conn);

// Verify the medicine exists
if (!$med->getById($id)) {
    echo json_encode(['success' => false, 'message' => 'Medicine not found.']);
    exit;
}

$ok = $med->update($id, [
    'name'            => $name,
    'category'        => $category,
    'quantity'        => $qty,
    'unit'            => $unit,
    'reorder_level'   => $reorder,
    'expiration_date' => $expiry,
    'location'        => $location,
    'notes'           => $notes
]);

if ($ok) {
    echo json_encode(['success' => true, 'message' => "$name updated successfully."]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update medicine. Please try again.']);
}
