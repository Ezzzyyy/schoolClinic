<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['msg'] = 'Invalid request method.';
    $_SESSION['msg_type'] = 'error';
    header('Location: ../modules/settings.php?tab=users');
    exit;
}

$db = new Database();
$conn = $db->connect();

try {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $newRole = strtolower(trim((string)($_POST['role'] ?? '')));
    $newStatus = strtolower(trim((string)($_POST['status'] ?? '')));

    if ($userId <= 0) {
        throw new RuntimeException('Invalid user selected.');
    }

    $existsStmt = $conn->prepare('SELECT user_id FROM users WHERE user_id = :user_id LIMIT 1');
    $existsStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $existsStmt->execute();
    if (!$existsStmt->fetch(PDO::FETCH_ASSOC)) {
        throw new RuntimeException('User account not found.');
    }

    $updates = [];
    $params = [':user_id' => $userId];

    if ($newRole !== '') {
        $roleColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
        if (!$roleColumn) {
            throw new RuntimeException('Role column is missing in users table.');
        }

        $type = (string)($roleColumn['Type'] ?? '');
        $enumValues = [];
        if (preg_match('/^enum\((.*)\)$/i', $type, $matches)) {
            $enumValues = str_getcsv($matches[1], ',', "'");
            $enumValues = array_map('strtolower', array_map('trim', $enumValues));
        }

        if (!empty($enumValues) && !in_array($newRole, $enumValues, true)) {
            throw new RuntimeException('Invalid role selected for current database schema.');
        }

        $updates[] = 'role = :role';
        $params[':role'] = $newRole;
    }

    if ($newStatus !== '') {
        $statusColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'status'")->fetch(PDO::FETCH_ASSOC);
        if ($statusColumn) {
            $allowedStatus = ['active', 'inactive', 'pending review'];
            if (!in_array($newStatus, $allowedStatus, true)) {
                throw new RuntimeException('Invalid status selected.');
            }

            $updates[] = 'status = :status';
            $params[':status'] = $newStatus;
        }
    }

    if (empty($updates)) {
        throw new RuntimeException('No editable fields found to update.');
    }

    $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE user_id = :user_id';
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();

    $_SESSION['msg'] = 'User access updated successfully.';
    $_SESSION['msg_type'] = 'success';
} catch (Throwable $e) {
    $_SESSION['msg'] = $e->getMessage();
    $_SESSION['msg_type'] = 'error';
}

header('Location: ../modules/settings.php?tab=users');
exit;
