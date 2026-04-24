<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$query = "SELECT al.*, u.first_name, u.last_name FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.user_id 
          WHERE 1=1";

if ($filter && $filter !== 'all') {
    $query .= " AND al.action = '" . $conn->real_escape_string($filter) . "'";
}

if ($search) {
    $query .= " AND (u.first_name LIKE '%" . $conn->real_escape_string($search) . "%' 
                    OR u.last_name LIKE '%" . $conn->real_escape_string($search) . "%'
                    OR al.description LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$query .= " ORDER BY al.timestamp DESC LIMIT 100";

$result = $conn->query($query);
$logs = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'timestamp' => date('M d, Y H:i A', strtotime($row['timestamp'])),
            'user_name' => ($row['first_name'] ?? 'System') . ' ' . ($row['last_name'] ?? ''),
            'action' => ucfirst($row['action']),
            'entity_type' => $row['entity_type'],
            'ip_address' => $row['ip_address'] ?? '—',
            'description' => $row['description']
        ];
    }
}

echo json_encode([
    'success' => true,
    'logs' => $logs
]);
?>
