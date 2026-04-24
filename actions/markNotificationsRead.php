<?php
declare(strict_types=1);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $markAll = isset($_POST['all']) && $_POST['all'] === '1';
    $notificationId = (int) ($_POST['notification_id'] ?? 0);

    if ($markAll) {
        $sql = "UPDATE notifications
                SET is_read = 1, updated_at = NOW()
                WHERE is_read = 0
                  AND (user_id IS NULL OR user_id = :user_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
        exit;
    }

    if ($notificationId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid notification id.']);
        exit;
    }

    $sql = "UPDATE notifications
            SET is_read = 1, updated_at = NOW()
            WHERE notification_id = :notification_id
              AND (user_id IS NULL OR user_id = :user_id)";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':notification_id', $notificationId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update notifications.'
    ]);
}
