<?php
declare(strict_types=1);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

function ensureAuditLogsTable(PDO $conn): void
{
    $conn->exec(
        "CREATE TABLE IF NOT EXISTS audit_logs (
            log_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            action VARCHAR(100) NOT NULL,
            entity_type VARCHAR(50) DEFAULT NULL,
            entity_id INT(11) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (log_id),
            KEY fk_al_user (user_id),
            KEY timestamp (timestamp),
            KEY entity_type (entity_type),
            CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES users (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

try {
    $db = new Database();
    $conn = $db->connect();
    ensureAuditLogsTable($conn);
    $userId = (int) ($_SESSION['user_id'] ?? 0);

    $stmt = $conn->prepare(
        'SELECT log_id, action, entity_type, entity_id, description, ip_address, timestamp
         FROM audit_logs
         WHERE user_id = :user_id
         ORDER BY timestamp DESC
         LIMIT 25'
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $logs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $logs[] = [
            'id' => (int) $row['log_id'],
            'action' => $row['action'],
            'entity_type' => $row['entity_type'],
            'entity_id' => $row['entity_id'],
            'description' => $row['description'],
            'ip_address' => $row['ip_address'],
            'timestamp' => $row['timestamp'],
        ];
    }

    echo json_encode(['success' => true, 'logs' => $logs]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load activity log.']);
}
