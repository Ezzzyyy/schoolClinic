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
ensureAuditLogsTable($conn);
$med  = new Medicine($conn);

function writeAuditLog(PDO $conn, int $userId, string $action, ?string $entityType, ?int $entityId, ?string $description): void
{
    $stmt = $conn->prepare(
        'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, description, ip_address) VALUES (:user_id, :action, :entity_type, :entity_id, :description, :ip_address)'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':action', $action, PDO::PARAM_STR);
    $stmt->bindValue(':entity_type', $entityType, $entityType === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':entity_id', $entityId, $entityId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':description', $description, $description === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null, !empty($_SERVER['REMOTE_ADDR']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->execute();
}

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
    writeAuditLog($conn, $handledBy, 'add', 'medicine', $newId, "Added medicine: $name");
    echo json_encode(['success' => true, 'message' => "$name added to inventory.", 'id' => $newId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add medicine. Please try again.']);
}
