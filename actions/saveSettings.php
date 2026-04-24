<?php
declare(strict_types=1);
error_reporting(0);
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Settings.php';

protectPage(1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$db = new Database();
$conn = $db->connect();
$settingsModel = new Settings($conn);

$formType = $_POST['form_type'] ?? '';
$dataToSave = [];

if ($formType === 'clinic_setup') {
    $dataToSave = [
        'clinic_name'       => $_POST['clinic_name'] ?? '',
        'school_year'       => $_POST['school_year'] ?? '',
        'clinic_contact'    => $_POST['clinic_contact'] ?? '',
        'clinic_address'    => $_POST['clinic_address'] ?? '',
        'document_footer'   => $_POST['document_footer'] ?? '',
        'primary_physician' => $_POST['primary_physician'] ?? '',
        'physician_license' => $_POST['physician_license'] ?? '',
        'head_nurse'        => $_POST['head_nurse'] ?? '',
        'nurse_license'     => $_POST['nurse_license'] ?? ''
    ];
} elseif ($formType === 'assessment_reqs') {
    $keys = ['req_xray', 'req_urinalysis', 'req_cbc', 'req_drug_test', 'req_med_cert', 'req_vaccination'];
    foreach ($keys as $k) {
        $dataToSave[$k] = $_POST[$k] ?? 'optional';
        $dataToSave[$k . '_enabled'] = isset($_POST[$k . '_enabled']) ? '1' : '0';
    }
} elseif ($formType === 'system_setup') {
    $dataToSave = [
        'session_timeout'     => $_POST['session_timeout'] ?? '60',
        'max_login_attempts'  => $_POST['max_login_attempts'] ?? '5',
        'two_factor_auth'     => isset($_POST['two_factor_auth']) ? 'enabled' : 'disabled'
    ];
} elseif ($formType === 'email_config') {
    $dataToSave = [
        'email_enabled'       => isset($_POST['email_enabled']) ? '1' : '0',
        'email_username'      => $_POST['email_username'] ?? '',
        'email_from_address'  => $_POST['email_from_address'] ?? '',
        'email_from_name'     => $_POST['email_from_name'] ?? ''
    ];
    
    // Only update password if provided
    if (!empty($_POST['email_password'])) {
        $dataToSave['email_password'] = $_POST['email_password'];
    }
} elseif ($formType === 'test_email') {
    // Test email configuration with Gmail defaults
    header('Content-Type: application/json');
    
    $host = 'smtp.gmail.com';
    $port = 587;
    $username = $_POST['email_username'] ?? '';
    $password = $_POST['email_password'] ?? '';
    
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email credentials are incomplete.']);
        exit;
    }
    
    // Test SMTP connection
    $connection = @fsockopen($host, $port, $errno, $errstr, 5);
    
    if ($connection) {
        fclose($connection);
        echo json_encode(['success' => true, 'message' => 'Gmail SMTP connection successful!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cannot connect to Gmail SMTP: ' . $errstr]);
    }
    exit;
} elseif ($formType === 'backup_config') {
    $dataToSave = [
        'data_retention_days' => $_POST['data_retention_days'] ?? '365',
        'backup_enabled'      => isset($_POST['backup_enabled']) ? '1' : '0'
    ];
} elseif ($formType === 'manual_backup') {
    // Create manual backup
    header('Content-Type: application/json');
    
    $backupDir = __DIR__ . '/../uploads/backups/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }
    
    $backupFile = $backupDir . 'backup_' . date('Y-m-d_His') . '.sql';
    $dbName = 'school_clinic_db';
    $dbUser = 'root';
    $dbPass = '';
    $dbHost = 'localhost';
    
    $command = "mysqldump -h {$dbHost} -u {$dbUser}" . (!empty($dbPass) ? " -p{$dbPass}" : "") . " {$dbName} > {$backupFile}";
    
    $output = null;
    $returnVar = null;
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0 && file_exists($backupFile)) {
        // Update last backup date
        $settingsModel->save('last_backup_date', date('Y-m-d H:i:s'));
        echo json_encode(['success' => true, 'message' => 'Backup created successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create backup. Make sure mysqldump is installed.']);
    }
    exit;
} elseif ($formType === 'clear_old_logs') {
    // Clear audit logs older than 90 days
    header('Content-Type: application/json');
    
    $query = "DELETE FROM audit_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)";
    
    if ($conn->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Old logs cleared successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to clear logs.']);
    }
    exit;
}

if (!empty($dataToSave)) {
    $ok = $settingsModel->saveMultiple($dataToSave);
    if ($ok) {
        $_SESSION['msg'] = 'Settings updated successfully.';
        $_SESSION['msg_type'] = 'success';
    } else {
        $_SESSION['msg'] = 'Failed to update settings.';
        $_SESSION['msg_type'] = 'error';
    }
} else {
    $_SESSION['msg'] = 'No changes were detected.';
    $_SESSION['msg_type'] = 'info';
}

header("Location: ../modules/settings.php");
exit;
