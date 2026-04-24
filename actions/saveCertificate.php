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
