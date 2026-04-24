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

    $certId = (int)($_POST['certificate_id'] ?? 0);
    $remarks = $_POST['certNotes'] ?? '';

    if ($certId <= 0) {
        throw new Exception('Invalid Certificate ID');
    }

    $filePath = null;
    if (isset($_FILES['certFile']) && $_FILES['certFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['certFile']['tmp_name'];
        $fileName = $_FILES['certFile']['name'];
        $fileSize = $_FILES['certFile']['size'];
        $fileType = $_FILES['certFile']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file extension. Allowed: pdf, jpg, jpeg, png');
        }

        if ($fileSize > 5 * 1024 * 1024) {
            throw new Exception('File too large. Max: 5MB');
        }

        $newFileName = 'CERT_' . $certId . '_' . time() . '.' . $fileExtension;
        $uploadFileDir = __DIR__ . '/../uploads/certificates/';
        $destPath = $uploadFileDir . $newFileName;

        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            throw new Exception('Failed to move uploaded file.');
        }
        $filePath = 'uploads/certificates/' . $newFileName;
    } else if (isset($_FILES['certFile']) && $_FILES['certFile']['error'] !== UPLOAD_ERR_NO_FILE) {
        throw new Exception('File upload error: ' . $_FILES['certFile']['error']);
    }

    if (!$filePath) {
        throw new Exception('No file provided or upload failed');
    }

    $sql = "UPDATE medical_certificates SET file_path = ?, remarks = ? WHERE certificate_id = ?";
    $stmt = $conn->prepare($sql);
    $success = $stmt->execute([$filePath, $remarks, $certId]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Certificate attached successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
