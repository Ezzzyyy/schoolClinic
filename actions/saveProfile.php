<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

function ensureProfilePhotoColumn(PDO $conn): void
{
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
        $conn->exec("ALTER TABLE users ADD profile_photo VARCHAR(255) DEFAULT NULL AFTER email");
    }
}

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../modules/profile.php');
    exit;
}

$db = new Database();
$conn = $db->connect();
ensureProfilePhotoColumn($conn);
ensureAuditLogsTable($conn);
$userId = (int) ($_SESSION['user_id'] ?? 0);
$formType = $_POST['form_type'] ?? '';

try {
    if ($formType === 'profile_info') {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $username === '') {
            throw new RuntimeException('Please complete all required profile fields.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Please provide a valid email address.');
        }

        $dupeStmt = $conn->prepare(
            'SELECT user_id FROM users WHERE (email = :email OR username = :username) AND user_id <> :user_id LIMIT 1'
        );
        $dupeStmt->bindValue(':email', $email, PDO::PARAM_STR);
        $dupeStmt->bindValue(':username', $username, PDO::PARAM_STR);
        $dupeStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $dupeStmt->execute();
        if ($dupeStmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Email or username is already used by another account.');
        }

        $stmt = $conn->prepare(
            'UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, username = :username WHERE user_id = :user_id'
        );
        $stmt->bindValue(':first_name', $firstName, PDO::PARAM_STR);
        $stmt->bindValue(':last_name', $lastName, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if (!empty($_FILES['profile_photo']['name']) && is_uploaded_file($_FILES['profile_photo']['tmp_name'])) {
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo ? finfo_file($fileInfo, $_FILES['profile_photo']['tmp_name']) : '';
            if ($fileInfo) {
                finfo_close($fileInfo);
            }

            if (!isset($allowedTypes[$mimeType])) {
                throw new RuntimeException('Profile photo must be a JPG, PNG, or WEBP image.');
            }

            $uploadDir = __DIR__ . '/../uploads/profile_photos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = 'profile_' . $userId . '_' . time() . '.' . $allowedTypes[$mimeType];
            $relativePath = 'uploads/profile_photos/' . $fileName;
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
                throw new RuntimeException('Unable to save profile photo.');
            }

            $photoStmt = $conn->prepare('UPDATE users SET profile_photo = :profile_photo WHERE user_id = :user_id');
            $photoStmt->bindValue(':profile_photo', $relativePath, PDO::PARAM_STR);
            $photoStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $photoStmt->execute();
            $_SESSION['profile_photo'] = $relativePath;
        }

        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $firstName . ' ' . $lastName;
        $_SESSION['profile_msg'] = 'Profile information updated successfully.';
        $_SESSION['profile_msg_type'] = 'success';
        writeAuditLog($conn, $userId, 'updated', 'user', $userId, 'Updated profile information.');
    } elseif ($formType === 'password_change') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            throw new RuntimeException('Please complete all password fields.');
        }

        if (strlen($newPassword) < 8) {
            throw new RuntimeException('New password must be at least 8 characters long.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new RuntimeException('New password and confirmation do not match.');
        }

        $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = :user_id LIMIT 1');
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, (string) $user['password'])) {
            throw new RuntimeException('Current password is incorrect.');
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $updateStmt = $conn->prepare('UPDATE users SET password = :password WHERE user_id = :user_id');
        $updateStmt->bindValue(':password', $newHash, PDO::PARAM_STR);
        $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $updateStmt->execute();

        $_SESSION['profile_msg'] = 'Password changed successfully.';
        $_SESSION['profile_msg_type'] = 'success';
        writeAuditLog($conn, $userId, 'updated', 'user', $userId, 'Updated account password.');
    } else {
        throw new RuntimeException('Invalid profile request.');
    }
} catch (Throwable $e) {
    $_SESSION['profile_msg'] = $e->getMessage();
    $_SESSION['profile_msg_type'] = 'error';
}

header('Location: ../modules/profile.php');
exit;
