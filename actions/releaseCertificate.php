<?php
declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Visit.php';

protectPage(1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$certId = (int)($_POST['certId'] ?? 0);

if ($certId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid certificate ID.']);
    exit;
}

$db   = new Database();
$conn = $db->connect();
$visitModel = new Visit($conn);

$ok = $visitModel->releaseCertificate($certId);

if ($ok) {
    $stats = $visitModel->getCertificateStats();
    echo json_encode(['success' => true, 'message' => 'Certificate released successfully.', 'stats' => $stats]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to release certificate. Please try again.']);
}
