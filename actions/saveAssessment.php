<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/HealthAssessment.php';
require_once __DIR__ . '/../classes/Student.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();
    $haModel = new HealthAssessment($conn);
    $studentModel = new Student($conn);

    $studentId = (int)($_POST['student_id'] ?? 0);
    if ($studentId <= 0) {
        throw new Exception('Invalid Student ID');
    }

    // Prepare data for HealthAssessment::save
    $data = [
        'student_id'       => $studentId,
        'height'           => $_POST['height'] ?? null,
        'weight'           => $_POST['weight'] ?? null,
        'blood_pressure'   => $_POST['blood_pressure'] ?? null,
        'pulse_rate'       => $_POST['pulse_rate'] ?? null,
        'lab_remarks'      => $_POST['lab_remarks'] ?? null,
        'clearance_status' => $_POST['clearance_status'] ?? 'pending',
        'assessment_date'  => $_POST['assessment_date'] ?? date('Y-m-d'),
        'handled_by'       => $_SESSION['user_id'] ?? null
    ];

    $success = $haModel->save($data);

    if ($success) {
        // Also update student's general status if they are cleared or conditional
        $newStatus = 'Pending review';
        if ($data['clearance_status'] === 'cleared' || $data['clearance_status'] === 'conditional') {
            $newStatus = 'Active';

            // --- Auto-Queue Certificate ---
            // 1. Get the assessment_id (might be new or existing)
            $ha = $haModel->getByStudentId($studentId);
            if ($ha) {
                $assessmentId = (int)$ha['assessment_id'];
                
                // 2. Check if a certificate already exists for this assessment
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM medical_certificates WHERE assessment_id = ?");
                $checkStmt->execute([$assessmentId]);
                $exists = (int)$checkStmt->fetchColumn();

                if ($exists === 0) {
                    // 3. Create certificate record
                    $certStmt = $conn->prepare("INSERT INTO medical_certificates (assessment_id, issued_by, date_issued) VALUES (?, ?, ?)");
                    $certStmt->execute([
                        $assessmentId,
                        $_SESSION['user_id'] ?? 1, // Default to admin/first user if session missing
                        date('Y-m-d')
                    ]);
                }
            }
        }
        
        $studentModel->updateStatus($studentId, $newStatus, "Health Assessment: " . $data['clearance_status'] . ". " . ($data['lab_remarks'] ?? ''));

        echo json_encode(['success' => true, 'message' => 'Assessment saved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save assessment']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
