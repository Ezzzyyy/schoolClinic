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

function ensureAuditLogsTable(PDO $conn): void
{
    $tableCheck = $conn->query("SHOW TABLES LIKE 'audit_logs'");
    if (!$tableCheck || !$tableCheck->fetch()) {
        $conn->exec("CREATE TABLE audit_logs (
            log_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50),
            entity_id INT,
            description TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )");
    }
}

ensureAuditLogsTable($conn);
$visitModel = new Visit($conn);

// Update visit status and remarks
$stmt = $conn->prepare("UPDATE clinic_visits SET visit_status = ?, notes = ? WHERE visit_id = ?");
$result = $stmt->execute([$statusId, $remarks, $visitId]);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Visit status updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update visit status.']);
}
