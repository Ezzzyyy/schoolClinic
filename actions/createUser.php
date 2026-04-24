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
    $firstName = trim((string)($_POST['first_name'] ?? ''));
    $lastName = trim((string)($_POST['last_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = strtolower(trim((string)($_POST['role'] ?? '')));

    if ($firstName === '' || $lastName === '' || $email === '' || $username === '' || $password === '' || $role === '') {
        throw new RuntimeException('Please fill in all required fields.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please provide a valid email address.');
    }

    if (strlen($password) < 6) {
        throw new RuntimeException('Password must be at least 6 characters long.');
    }

    // Check for duplicates
    $dupeStmt = $conn->prepare('SELECT user_id FROM users WHERE email = :email OR username = :username LIMIT 1');
    $dupeStmt->bindValue(':email', $email, PDO::PARAM_STR);
    $dupeStmt->bindValue(':username', $username, PDO::PARAM_STR);
    $dupeStmt->execute();
    if ($dupeStmt->fetch(PDO::FETCH_ASSOC)) {
        throw new RuntimeException('Email or username is already in use.');
    }

    // Validate role against schema
    $roleColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
    if ($roleColumn) {
        $type = (string)($roleColumn['Type'] ?? '');
        $enumValues = [];
        if (preg_match('/^enum\((.*)\)$/i', $type, $matches)) {
            $enumVals = str_getcsv($matches[1], ',', "'");
            $enumValues = array_map('strtolower', array_map('trim', $enumVals));
        }

        if (!empty($enumValues) && !in_array($role, $enumValues, true)) {
            throw new RuntimeException('Invalid role selected for current database schema.');
        }
    }

    // Hash password and create user
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $createStmt = $conn->prepare(
        'INSERT INTO users (first_name, last_name, email, username, password, role, status) VALUES (:first_name, :last_name, :email, :username, :password, :role, :status)'
    );
    $createStmt->bindValue(':first_name', $firstName, PDO::PARAM_STR);
    $createStmt->bindValue(':last_name', $lastName, PDO::PARAM_STR);
    $createStmt->bindValue(':email', $email, PDO::PARAM_STR);
    $createStmt->bindValue(':username', $username, PDO::PARAM_STR);
    $createStmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
    $createStmt->bindValue(':role', $role, PDO::PARAM_STR);
    $createStmt->bindValue(':status', 'active', PDO::PARAM_STR);
    $createStmt->execute();

    $_SESSION['msg'] = 'User account created successfully.';
    $_SESSION['msg_type'] = 'success';
} catch (Throwable $e) {
    $_SESSION['msg'] = $e->getMessage();
    $_SESSION['msg_type'] = 'error';
}

header('Location: ../modules/settings.php?tab=users');
exit;
