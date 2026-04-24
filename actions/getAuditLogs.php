<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$perPage = 7;
$offset = ($page - 1) * $perPage;

$query = "SELECT al.*, u.first_name, u.last_name FROM audit_logs al
          LEFT JOIN users u ON al.user_id = u.user_id
          WHERE 1=1";

$countQuery = "SELECT COUNT(*) as total FROM audit_logs al
               LEFT JOIN users u ON al.user_id = u.user_id
               WHERE 1=1";

$params = [];
$countParams = [];

if ($filter && $filter !== 'all') {
    $query .= " AND LOWER(al.action) = LOWER(?)";
    $countQuery .= " AND LOWER(al.action) = LOWER(?)";
    $params[] = $filter;
    $countParams[] = $filter;
}

if ($search) {
    $query .= " AND (u.first_name LIKE ?
                    OR u.last_name LIKE ?
                    OR al.description LIKE ?)";
    $countQuery .= " AND (u.first_name LIKE ?
                         OR u.last_name LIKE ?
                         OR al.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
}

$query .= " ORDER BY al.timestamp DESC LIMIT $perPage OFFSET $offset";

// Get total count
$countStmt = $conn->prepare($countQuery);
$countStmt->execute($countParams);
$totalResult = $countStmt->fetch(PDO::FETCH_ASSOC);
$total = (int)$totalResult['total'];

$stmt = $conn->prepare($query);
$stmt->execute($params);
$result = $stmt;
$logs = [];

if ($result && $result->rowCount() > 0) {
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
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
    'logs' => $logs,
    'pagination' => [
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage),
        'has_next' => $page < ceil($total / $perPage),
        'has_prev' => $page > 1
    ]
]);
?>
