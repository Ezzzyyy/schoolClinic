<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

$db = new Database();
$conn = $db->connect();

$query = "SELECT al.*, u.first_name, u.last_name FROM audit_logs al
          LEFT JOIN users u ON al.user_id = u.user_id
          ORDER BY al.timestamp DESC LIMIT 1000";

$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d_His') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date & Time', 'User', 'Action', 'Entity Type', 'Entity ID', 'IP Address', 'Description']);

if ($result && $result->rowCount() > 0) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            date('M d, Y H:i A', strtotime($row['timestamp'])),
            ($row['first_name'] ?? 'System') . ' ' . ($row['last_name'] ?? ''),
            ucfirst($row['action']),
            $row['entity_type'] ?? '—',
            $row['entity_id'] ?? '—',
            $row['ip_address'] ?? '—',
            $row['description'] ?? ''
        ]);
    }
}

fclose($output);
exit;
?>
