<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    $studentId = (int)($_POST['student_id'] ?? 0);
    $visitDate = $_POST['visit_date'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $recommendations = $_POST['recommendations'] ?? '';
    $restrictions = $_POST['restrictions'] ?? '';
    $issuingDoctor = $_POST['issuing_doctor'] ?? '';
    $template = $_POST['template'] ?? 'A';
    $issuedBy = (int)($_SESSION['user_id'] ?? 0);

    if ($studentId <= 0) {
        throw new Exception('Invalid Student ID');
    }

    if (empty($visitDate)) {
        throw new Exception('Visit date is required');
    }

    if (empty($diagnosis)) {
        throw new Exception('Diagnosis is required');
    }

    if (empty($issuingDoctor)) {
        throw new Exception('Issuing doctor is required');
    }

    // Insert into medical_certificates table
    $sql = "INSERT INTO medical_certificates (
        student_id, 
        visit_id, 
        diagnosis, 
        recommendations, 
        restrictions, 
        issuing_doctor, 
        template, 
        issued_by, 
        date_issued, 
        status
    ) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, CURDATE(), 'pending')";

    $stmt = $conn->prepare($sql);
    $success = $stmt->execute([
        $studentId,
        $diagnosis,
        $recommendations,
        $restrictions,
        $issuingDoctor,
        $template,
        $issuedBy
    ]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Certificate created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create certificate']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
