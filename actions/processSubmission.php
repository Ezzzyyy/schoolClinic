<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/HealthAssessment.php';
require_once __DIR__ . '/../classes/Settings.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    $studentModel = new Student($conn);
    $healthModel  = new HealthAssessment($conn);
    $settingsModel = new Settings($conn);
    $settings = $settingsModel->getAll();

    $requirements = [
        ['input' => 'xray', 'setting' => 'req_xray', 'label' => 'Chest X-ray', 'field' => 'x_ray', 'folder' => 'xray'],
        ['input' => 'urinalysis', 'setting' => 'req_urinalysis', 'label' => 'Urinalysis', 'field' => 'urinalysis', 'folder' => 'urinalysis'],
        ['input' => 'hematology', 'setting' => 'req_cbc', 'label' => 'Hematology / CBC', 'field' => 'hematology', 'folder' => 'hematology'],
        ['input' => 'drugTest', 'setting' => 'req_drug_test', 'label' => 'Drug Test', 'field' => 'drug_test', 'folder' => 'drugtest'],
        ['input' => 'medCert', 'setting' => 'req_med_cert', 'label' => 'Medical Certificate', 'field' => 'med_certificate', 'folder' => 'medical_certificates'],
        ['input' => 'vaccination', 'setting' => 'req_vaccination', 'label' => 'Vaccination Card', 'field' => 'vaccination_card', 'folder' => 'vaccination_cards'],
    ];

    // 1. Process Student Information
    $studentData = [
        'student_number'    => $_POST['studentNo'] ?? '',
        'first_name'        => $_POST['firstName'] ?? '',
        'middle_name'       => $_POST['middleName'] ?? null,
        'last_name'         => $_POST['lastName'] ?? '',
        'gender'            => $_POST['gender'] ?? '',
        'birth_date'        => $_POST['birthdate'] ?? null,
        'contact_number'    => $_POST['contactNo'] ?? '',
        'address'           => $_POST['address'] ?? '',
        'emergency_contact' => $_POST['emergency'] ?? '',
        'email'             => $_POST['email'] ?? '',
        'health_notes'      => $_POST['healthNotes'] ?? null,
        'course_id'         => $_POST['courseId'] ?? null,
        'year_id'           => $_POST['yearId'] ?? null
    ];

    if (empty($studentData['student_number']) || empty($studentData['first_name'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required personal information.']);
        exit;
    }

    $studentId = $studentModel->upsert($studentData);

    // 2. Process File Uploads
    $uploadDir = __DIR__ . '/../uploads/';
    $filePaths = [];

    foreach ($requirements as $requirement) {
        $isEnabled = ($settings[$requirement['setting'] . '_enabled'] ?? '1') === '1';
        $isRequired = ($settings[$requirement['setting']] ?? 'optional') === 'required';
        $hasUpload = isset($_FILES[$requirement['input']]) && $_FILES[$requirement['input']]['error'] === UPLOAD_ERR_OK;

        if (!$isEnabled) {
            continue;
        }

        if ($isRequired && !$hasUpload) {
            echo json_encode(['success' => false, 'message' => 'Missing required document: ' . $requirement['label'] . '.']);
            exit;
        }

        if (!$hasUpload) {
            continue;
        }

        $folderPath = $uploadDir . $requirement['folder'] . '/';
        if (!is_dir($folderPath)) mkdir($folderPath, 0777, true);

        $ext = pathinfo($_FILES[$requirement['input']]['name'], PATHINFO_EXTENSION);
        $filename = $studentData['student_number'] . '_' . time() . '.' . $ext;
        $destination = $folderPath . $filename;

        if (move_uploaded_file($_FILES[$requirement['input']]['tmp_name'], $destination)) {
            $filePaths[$requirement['input']] = 'uploads/' . $requirement['folder'] . '/' . $filename;
        }
    }

    // 3. Save Health Assessment
    $assessmentData = [
        'student_id'      => $studentId,
        'x_ray'           => $filePaths['xray'] ?? null,
        'urinalysis'      => $filePaths['urinalysis'] ?? null,
        'hematology'      => $filePaths['hematology'] ?? null,
        'drug_test'       => $filePaths['drugTest'] ?? null,
        'med_certificate' => $filePaths['medCert'] ?? null,
        'vaccination_card'=> $filePaths['vaccination'] ?? null,
        'assessment_date' => date('Y-m-d')
    ];

    if ($healthModel->save($assessmentData)) {
        echo json_encode(['success' => true, 'message' => 'Submission successful! Your records are now under review.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Data saved, but health assessment linking failed.']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
