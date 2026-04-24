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

$existing = $med->getById($id);
if (!$existing) {
    echo json_encode(['success' => false, 'message' => 'Medicine not found.']);
    exit;
}

$ok = $med->delete($id);

if ($ok) {
    writeAuditLog($conn, (int)($_SESSION['user_id'] ?? 0), 'delete', 'medicine', $id, "Deleted medicine: {$existing['name']}");
    echo json_encode(['success' => true, 'message' => "{$existing['name']} has been removed from inventory."]);
} else {
    echo json_encode([
        'success' => false,
        'message' => "Cannot delete {$existing['name']} — it has dispensing records linked to clinic visits."
    ]);
}
