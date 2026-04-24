<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Settings.php';

protectPage(1);

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();
$settingsModel = new Settings($conn);

$users = $settingsModel->getUsers();

$userData = [];
foreach ($users as $u) {
    $roleRaw = strtolower((string)($u['role'] ?? ''));
    if ($roleRaw === '1' || $roleRaw === 'admin') {
        $roleLabel = 'Admin';
    } elseif ($roleRaw === '2' || $roleRaw === 'nurse') {
        $roleLabel = 'Nurse';
    } elseif ($roleRaw === 'doctor') {
        $roleLabel = 'Doctor';
    } else {
        $roleLabel = ucfirst((string)($u['role'] ?? 'Assistant'));
    }
    
    $lastLogin = $u['last_login'] ? date('M d, H:i A', strtotime($u['last_login'])) : 'Never';
    
    $userData[] = [
        'user_id' => (int)($u['user_id'] ?? 0),
        'name' => trim((string)($u['first_name'] ?? '') . ' ' . (string)($u['last_name'] ?? '')),
        'email' => $u['email'] ?? '',
        'role' => $roleLabel,
        'role_raw' => $u['role'] ?? '',
        'last_login' => $lastLogin,
        'status' => $u['status'] ?? 'active'
    ];
}

echo json_encode(['success' => true, 'users' => $userData]);
