<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Settings.php';

protectPage(1);

// Only set JSON header for specific form types that return JSON
if (in_array($_POST['form_type'] ?? '', ['test_email', 'manual_backup', 'clear_old_logs'])) {
    header('Content-Type: application/json');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

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
$settingsModel = new Settings($conn);

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
} elseif ($formType === 'system_setup' || $formType === 'system_settings') {
    $dataToSave = [
        'email_enabled'       => isset($_POST['email_enabled']) ? '1' : '0',
        'email_username'      => $_POST['email_username'] ?? '',
        'email_from_address'  => $_POST['email_from_address'] ?? '',
        'email_from_name'     => $_POST['email_from_name'] ?? '',
        'session_timeout'     => $_POST['session_timeout'] ?? '60',
        'max_login_attempts'  => $_POST['max_login_attempts'] ?? '5',
        'two_factor_auth'     => isset($_POST['two_factor_auth']) ? 'enabled' : 'disabled',
        'data_retention_days' => $_POST['data_retention_days'] ?? '365',
        'backup_enabled'      => isset($_POST['backup_enabled']) ? '1' : '0'
    ];

    if (!empty($_POST['email_password'])) {
        $dataToSave['email_password'] = $_POST['email_password'];
    }
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
    // Create manual backup using PHP
    ob_clean();
    ob_start();
    
    $backupDir = __DIR__ . '/../uploads/backups/';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0777, true);
    }

    $backupFile = $backupDir . 'backup_' . date('Y-m-d_His') . '.sql';

    // Increase execution time and memory limit
    set_time_limit(300);
    ini_set('memory_limit', '256M');

    try {
        // Get all tables
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        if (!$result) {
            throw new Exception('Failed to get tables');
        }
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = '';
        foreach ($tables as $table) {
            // Get CREATE TABLE statement
            $result = $conn->query("SHOW CREATE TABLE {$table}");
            if (!$result) {
                throw new Exception("Failed to get CREATE TABLE for {$table}");
            }
            $row = $result->fetch(PDO::FETCH_ASSOC);
            $sql .= "\n\n" . $row['Create Table'] . ";\n\n";

            // Get table data
            $result = $conn->query("SELECT * FROM {$table}");
            if (!$result) {
                throw new Exception("Failed to select from {$table}");
            }

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $sql .= "INSERT INTO {$table} VALUES (";
                $values = [];
                foreach ($row as $value) {
                    $values[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                }
                $sql .= implode(', ', $values) . ");\n";
            }
        }

        $written = file_put_contents($backupFile, $sql);
        if ($written === false) {
            throw new Exception('Failed to write backup file');
        }

        // Update last backup date
        $settingsModel->saveMultiple(['last_backup_date' => date('Y-m-d H:i:s')]);
        
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Backup created successfully!']);
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()]);
    }
    exit;
} elseif ($formType === 'clear_old_logs') {
    // Clear audit logs older than 90 days
    $query = "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";

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
        writeAuditLog($conn, (int)($_SESSION['user_id'] ?? 0), 'updated', 'settings', null, "Updated settings: $formType");
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
