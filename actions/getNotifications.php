<?php
declare(strict_types=1);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

function ensureNotificationsSchema(PDO $conn): void
{
    $columnCheck = $conn->query("SHOW COLUMNS FROM notifications LIKE 'source_key'");
    if (!$columnCheck || !$columnCheck->fetch(PDO::FETCH_ASSOC)) {
        $conn->exec("ALTER TABLE notifications ADD COLUMN source_key VARCHAR(150) NULL AFTER message");
        $conn->exec("CREATE INDEX idx_notifications_source_key ON notifications (source_key)");
    }
}

function upsertGlobalNotification(
    PDO $conn,
    string $category,
    string $title,
    string $message,
    string $sourceKey,
    ?string $linkUrl = null
): void {
    $findSql = "SELECT notification_id, message, title, link_url, is_read
                FROM notifications
                WHERE user_id IS NULL
                  AND source_key = :source_key
                ORDER BY notification_id DESC
                LIMIT 1";

    $findStmt = $conn->prepare($findSql);
    $findStmt->bindValue(':source_key', $sourceKey, PDO::PARAM_STR);
    $findStmt->execute();
    $existing = $findStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $needsUpdate =
            ($existing['message'] ?? '') !== $message ||
            ($existing['title'] ?? '') !== $title ||
            (($existing['link_url'] ?? null) !== $linkUrl);

        if ($needsUpdate) {
            $updateSql = "UPDATE notifications
                          SET category = :category,
                              title = :title,
                              message = :message,
                              link_url = :link_url,
                              is_read = 0,
                              updated_at = NOW()
                          WHERE notification_id = :notification_id";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bindValue(':category', $category, PDO::PARAM_STR);
            $updateStmt->bindValue(':title', $title, PDO::PARAM_STR);
            $updateStmt->bindValue(':message', $message, PDO::PARAM_STR);
            $updateStmt->bindValue(':link_url', $linkUrl, $linkUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $updateStmt->bindValue(':notification_id', (int) $existing['notification_id'], PDO::PARAM_INT);
            $updateStmt->execute();
        }
        return;
    }

    $insertSql = "INSERT INTO notifications
                    (user_id, category, title, message, source_key, link_url, is_read, created_at)
                  VALUES
                    (NULL, :category, :title, :message, :source_key, :link_url, 0, NOW())";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bindValue(':category', $category, PDO::PARAM_STR);
    $insertStmt->bindValue(':title', $title, PDO::PARAM_STR);
    $insertStmt->bindValue(':message', $message, PDO::PARAM_STR);
    $insertStmt->bindValue(':source_key', $sourceKey, PDO::PARAM_STR);
    $insertStmt->bindValue(':link_url', $linkUrl, $linkUrl === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $insertStmt->execute();
}

function markResolvedByPrefix(PDO $conn, string $prefix, array $activeKeys): void
{
    if (empty($activeKeys)) {
        $sql = "UPDATE notifications
                SET is_read = 1, updated_at = NOW()
                WHERE user_id IS NULL
                  AND is_read = 0
                  AND source_key LIKE :prefix";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':prefix', $prefix . '%', PDO::PARAM_STR);
        $stmt->execute();
        return;
    }

    $placeholders = [];
    $params = [':prefix' => $prefix . '%'];
    foreach ($activeKeys as $idx => $key) {
        $ph = ':k' . $idx;
        $placeholders[] = $ph;
        $params[$ph] = $key;
    }

    $sql = "UPDATE notifications
            SET is_read = 1, updated_at = NOW()
            WHERE user_id IS NULL
              AND is_read = 0
              AND source_key LIKE :prefix
              AND source_key NOT IN (" . implode(', ', $placeholders) . ")";
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->execute();
}

function syncActionableNotifications(PDO $conn): void
{
    $lowStockSql = "SELECT medicine_id, name, quantity, COALESCE(unit, 'units') AS unit, COALESCE(reorder_level, 10) AS reorder_level
                    FROM medicines
                    WHERE quantity <= COALESCE(reorder_level, 10)
                    ORDER BY quantity ASC
                    LIMIT 20";
    $lowStockRows = $conn->query($lowStockSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $activeLowStockKeys = [];
    foreach ($lowStockRows as $row) {
        $medicineId = (int) $row['medicine_id'];
        $quantity = (int) $row['quantity'];
        $reorderLevel = (int) $row['reorder_level'];
        $name = (string) ($row['name'] ?? 'Medicine');
        $unit = trim((string) ($row['unit'] ?? 'units'));
        $sourceKey = 'low_stock:' . $medicineId;
        $activeLowStockKeys[] = $sourceKey;

        upsertGlobalNotification(
            $conn,
            'inventory',
            'Low Stock: ' . $name,
            'Only ' . $quantity . ' ' . $unit . ' left. Reorder level is ' . $reorderLevel . '.',
            $sourceKey,
            'medicineInventory.php?highlight=medicine&medicine_id=' . $medicineId
        );
    }
    markResolvedByPrefix($conn, 'low_stock:', $activeLowStockKeys);

    $pendingStudentSql = "SELECT student_id, student_number, first_name, last_name
                          FROM students
                          WHERE status = 'Pending review'
                          ORDER BY created_at DESC
                          LIMIT 20";
    $pendingStudents = $conn->query($pendingStudentSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $activePendingStudentKeys = [];
    foreach ($pendingStudents as $row) {
        $studentId = (int) $row['student_id'];
        $sourceKey = 'pending_student:' . $studentId;
        $activePendingStudentKeys[] = $sourceKey;

        $studentNumber = trim((string) ($row['student_number'] ?? ''));
        $fullName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));

        upsertGlobalNotification(
            $conn,
            'student',
            'Pending Student Review',
            $fullName . ' (' . $studentNumber . ') is waiting for health assessment review.',
            $sourceKey,
            'studentRecords.php?highlight=student&student_id=' . $studentId
        );
    }
    markResolvedByPrefix($conn, 'pending_student:', $activePendingStudentKeys);

    $pendingCertSql = "SELECT COUNT(*)
                       FROM medical_certificates
                       WHERE status = 'pending'";
    $pendingCertCount = (int) $conn->query($pendingCertSql)->fetchColumn();
    if ($pendingCertCount > 0) {
        upsertGlobalNotification(
            $conn,
            'system',
            'Pending Medical Certificates',
            $pendingCertCount . ' certificate request(s) are waiting to be released.',
            'pending_certificates:count',
            'medicalCertificates.php?highlight=pending_certificates'
        );
    } else {
        markResolvedByPrefix($conn, 'pending_certificates:', []);
    }

    $recentReportsSql = "SELECT log_id, report_type, created_at
                         FROM report_logs
                         WHERE status = 'Ready'
                           AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                         ORDER BY created_at DESC
                         LIMIT 10";
    $recentReports = $conn->query($recentReportsSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $activeReportKeys = [];
    foreach ($recentReports as $row) {
        $logId = (int) $row['log_id'];
        $sourceKey = 'report_ready:' . $logId;
        $activeReportKeys[] = $sourceKey;

        upsertGlobalNotification(
            $conn,
            'reports',
            'Report Ready: ' . (string) $row['report_type'],
            'A report is ready for viewing and export.',
            $sourceKey,
            'reports.php?highlight=report&log_id=' . $logId
        );
    }
    markResolvedByPrefix($conn, 'report_ready:', $activeReportKeys);
}

function timeAgo(string $datetime): string
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return 'Just now';
    }

    $diff = time() - $timestamp;
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' min ago';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' hr ago';
    }
    if ($diff < 604800) {
        return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    }

    return date('M d, Y', $timestamp);
}

try {
    $db = new Database();
    $conn = $db->connect();
    ensureNotificationsSchema($conn);
    syncActionableNotifications($conn);

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $filter = $_GET['filter'] ?? 'all';
    $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 50;

    $params = [':user_id' => $userId];
    $where = '(n.user_id IS NULL OR n.user_id = :user_id)';

    if (in_array($filter, ['inventory', 'student', 'system', 'reports'], true)) {
        $where .= ' AND n.category = :category';
        $params[':category'] = $filter;
    }

    $sql = "SELECT n.notification_id, n.category, n.title, n.message, n.link_url, n.is_read, n.created_at
            FROM notifications n
            WHERE {$where}
            ORDER BY n.is_read ASC, n.created_at DESC
            LIMIT :limit";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $notifications = [];
    foreach ($rows as $row) {
        $notifications[] = [
            'id' => (int) $row['notification_id'],
            'category' => $row['category'],
            'title' => $row['title'],
            'message' => $row['message'],
            'link_url' => $row['link_url'],
            'is_read' => (int) $row['is_read'] === 1,
            'created_at' => $row['created_at'],
            'time_ago' => timeAgo($row['created_at'])
        ];
    }

    $countsSql = "SELECT
                    SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) AS unread,
                    SUM(CASE WHEN n.category = 'inventory' THEN 1 ELSE 0 END) AS inventory,
                    SUM(CASE WHEN n.category = 'student' THEN 1 ELSE 0 END) AS student,
                    SUM(CASE WHEN n.category = 'system' THEN 1 ELSE 0 END) AS system_count,
                    SUM(CASE WHEN n.category = 'reports' THEN 1 ELSE 0 END) AS reports,
                    COUNT(*) AS total
                  FROM notifications n
                  WHERE (n.user_id IS NULL OR n.user_id = :user_id)";

    $countsStmt = $conn->prepare($countsSql);
    $countsStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $countsStmt->execute();
    $counts = $countsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'counts' => [
            'unread' => (int) ($counts['unread'] ?? 0),
            'inventory' => (int) ($counts['inventory'] ?? 0),
            'student' => (int) ($counts['student'] ?? 0),
            'system' => (int) ($counts['system_count'] ?? 0),
            'reports' => (int) ($counts['reports'] ?? 0),
            'all' => (int) ($counts['total'] ?? 0)
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load notifications.'
    ]);
}
