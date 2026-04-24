<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Report.php';

protectPage(1);

$db = new Database();
$conn = $db->connect();
ensureAuditLogsTable($conn);

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

$reportType = $_POST['reportType'] ?? '';
$dateFrom   = $_POST['dateFrom'] ?? '';
$dateTo     = $_POST['dateTo'] ?? '';
$format     = $_POST['exportFormat'] ?? 'pdf';
$courseId   = isset($_POST['courseId']) && $_POST['courseId'] !== '' ? (int)$_POST['courseId'] : null;
$yearLevel  = isset($_POST['yearLevel']) && $_POST['yearLevel'] !== '' ? (int)$_POST['yearLevel'] : null;

// Handle quick filters from the toolbar
$quickRange = $_POST['dateRangeQuick'] ?? '';
if (!empty($quickRange)) {
    $dateTo = date('Y-m-d');
    if ($quickRange === '30') {
        $dateFrom = date('Y-m-d', strtotime('-30 days'));
    } elseif ($quickRange === 'semester') {
        // Simple logic for semester (current vs previous 6 months)
        $dateFrom = date('Y-m-d', strtotime('-5 months'));
    } elseif ($quickRange === 'year') {
        $dateFrom = date('Y-m-d', strtotime('-1 year'));
    }
}

if (empty($reportType)) {
    die("Error: Report type is required.");
}

$reportModel = new Report($conn);

$data = [];
$title = "";
$headers = [];

switch ($reportType) {
    case 'Medicine Usage Report':
        $title = "Medicine Inventory & Usage Report";
        $data = $reportModel->getMedicineInventory();
        $headers = ['Medicine Name', 'Category', 'Unit', 'Stock Level', 'Total Dispensed'];
        break;
    
    case 'Enrollment Clearance Summary':
        $title = "Student Health Clearance Report";
        $data = $reportModel->getEnrollmentClearance($courseId, $yearLevel);
        $headers = ['Student ID', 'Name', 'Course', 'Level', 'Status', 'Date Cleared'];
        break;

    case 'Illness Trend Report':
        $title = "Common Illness Trend Report";
        $data = $reportModel->getIllnessTrends();
        $headers = ['Illness / Complaint', 'Total Cases', 'First Recorded', 'Last Recorded'];
        break;

    case 'Visit Frequency by Grade':
        $title = "Clinic Visit Frequency by Year Level";
        $data = $reportModel->getVisitFrequency();
        $headers = ['Year Level', 'Total Visits', 'Unique Students'];
        break;

    default:
        $title = "Clinic Visit Log Report";
        $data = $reportModel->getVisitLogs($dateFrom, $dateTo, $courseId, $yearLevel);
        $headers = ['Date', 'Student', 'ID', 'Complaint', 'Diagnosis', 'Status', 'Handled By'];
        break;
}

// Log report generation
writeAuditLog($conn, (int)($_SESSION['user_id'] ?? 0), 'exported', 'report', null, "Generated report: $reportType");

if (empty($data)) {
    die("Error: No data found for the report.");
}

$reportModel->logReport(
    $reportType,
    ($dateFrom && $dateTo) ? "$dateFrom to $dateTo" : "Full History",
    (int)($_SESSION['user_id'] ?? 1),
    $format,
    count($data)
);

$dateRange = "Generated on " . date('M d, Y H:i');
if ($dateFrom && $dateTo) {
    $dateRange .= " | Period: " . date('M d, Y', strtotime($dateFrom)) . " to " . date('M d, Y', strtotime($dateTo));
}

// ---------------------------------------------------------
// EXCEL EXPORT (HTML Table method)
// ---------------------------------------------------------
if ($format === 'excel') {
    $filename = str_replace(' ', '_', $title) . "_" . date('Ymd') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>';
    echo '<table border="1">';
    echo '<tr><th colspan="'.count($headers).'" style="font-size:18px; background-color:#EEF2FF;">' . htmlspecialchars($title) . '</th></tr>';
    echo '<tr><th colspan="'.count($headers).'" style="background-color:#F9FAFB;">' . htmlspecialchars($dateRange) . '</th></tr>';
    echo '<tr>';
    foreach ($headers as $h) {
        echo '<th style="background-color:#4F46E5; color:#FFFFFF; font-weight:bold;">' . htmlspecialchars($h) . '</th>';
    }
    echo '</tr>';

    foreach ($data as $row) {
        echo '<tr>';
        if ($reportType === 'Medicine Usage Report') {
            echo '<td>'.e($row['name']).'</td><td>'.e($row['category']).'</td><td>'.e($row['unit']).'</td><td>'.(int)$row['stock_level'].'</td><td>'.(int)$row['total_dispensed'].'</td>';
        } else if ($reportType === 'Enrollment Clearance Summary') {
            echo '<td>'.e($row['student_number']).'</td><td>'.e($row['first_name'] . ' ' . $row['last_name']).'</td><td>'.e($row['course_name']).'</td><td>'.e($row['year_name']).'</td><td>'.e($row['clearance_status']).'</td><td>'.e($row['assessment_date'] ?? '---').'</td>';
        } else if ($reportType === 'Illness Trend Report') {
            echo '<td>'.e($row['illness']).'</td><td>'.(int)$row['case_count'].'</td><td>'.e($row['first_seen']).'</td><td>'.e($row['last_seen']).'</td>';
        } else if ($reportType === 'Visit Frequency by Grade') {
            echo '<td>'.e($row['year_name']).'</td><td>'.(int)$row['visit_count'].'</td><td>'.(int)$row['unique_students'].'</td>';
        } else {
            echo '<td>'.e(date('M d, Y', strtotime($row['visit_date']))).'</td><td>'.e($row['first_name'] . ' ' . $row['last_name']).'</td><td>'.e($row['student_number']).'</td><td>'.e($row['complaint']).'</td><td>'.e($row['diagnosis']).'</td><td>'.e($row['status_name']).'</td><td>'.e($row['handler_first'] . ' ' . $row['handler_last']).'</td>';
        }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

// ---------------------------------------------------------
// PDF / PRINT VIEW (Modern CSS Print Template)
// ---------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <style>
        @page { size: A4; margin: 20mm; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; line-height: 1.5; margin: 0; padding: 0; }
        .report-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; }
        .system-name { font-size: 24px; font-weight: 800; color: #4F46E5; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; }
        .report-title { font-size: 18px; font-weight: 600; color: #334155; margin: 0; }
        .report-meta { font-size: 12px; color: #64748b; margin-top: 8px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
        th { background-color: #f8fafc; color: #475569; font-weight: 700; text-align: left; padding: 10px 8px; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 8px; border-bottom: 1px dotted #e2e8f0; color: #334155; vertical-align: top; }
        tr:nth-child(even) { background-color: #fcfcfc; }
        
        .status-pill { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: 600; text-transform: uppercase; }
        .status-ok { background: #dcfce7; color: #166534; }
        .status-warn { background: #fef9c3; color: #854d0e; }
        .status-pending { background: #f1f5f9; color: #475569; }

        .footer { position: fixed; bottom: 0; width: 100%; font-size: 10px; color: #94a3b8; text-align: center; padding-top: 10px; border-top: 1px solid #f1f5f9; }
        .page-num:after { content: counter(page); }

        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
        }

        .controls { position: fixed; top: 20px; right: 20px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; z-index: 100; }
        .btn { padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; font-size: 13px; }
        .btn-primary { background: #4F46E5; color: white; }
        .btn-secondary { background: #f1f5f9; color: #475569; margin-right: 8px; }
    </style>
</head>
<body>

    <div class="controls no-print">
        <button class="btn btn-secondary" onclick="window.close()">Close Preview</button>
        <button class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="report-header">
        <div class="system-name">ClinIQ School Clinic</div>
        <h1 class="report-title"><?= htmlspecialchars($title) ?></h1>
        <div class="report-meta"><?= htmlspecialchars($dateRange) ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <?php foreach ($headers as $h): ?>
                    <th><?= htmlspecialchars($h) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <?php if ($reportType === 'Medicine Usage Report'): ?>
                        <td><strong><?= e($row['name']) ?></strong><br/><small style="color:#64748b"><?= e($row['description'] ?? '') ?></small></td>
                        <td><?= e($row['category']) ?></td>
                        <td><?= e($row['unit']) ?></td>
                        <td>
                            <?php 
                                $stockClass = ($row['stock_level'] <= $row['reorder_level']) ? 'status-warn' : 'status-ok';
                            ?>
                            <span class="status-pill <?= $stockClass ?>"><?= (int)$row['stock_level'] ?></span>
                        </td>
                        <td><?= (int)$row['total_dispensed'] ?></td>

                    <?php elseif ($reportType === 'Enrollment Clearance Summary'): ?>
                        <td><code style="font-family:monospace;"><?= e($row['student_number']) ?></code></td>
                        <td><strong><?= e($row['first_name'] . ' ' . $row['last_name']) ?></strong></td>
                        <td><?= e($row['course_name']) ?></td>
                        <td><?= e($row['year_name']) ?></td>
                        <td>
                            <?php 
                                $statClass = ($row['clearance_status'] === 'cleared') ? 'status-ok' : (($row['clearance_status'] === 'conditional') ? 'status-warn' : 'status-pending');
                            ?>
                            <span class="status-pill <?= $statClass ?>"><?= e($row['clearance_status'] ?? 'pending') ?></span>
                        </td>
                        <td><?= e($row['assessment_date'] ?? '---') ?></td>

                    <?php elseif ($reportType === 'Illness Trend Report'): ?>
                        <td><strong><?= e($row['illness']) ?></strong></td>
                        <td><span class="status-pill status-ok"><?= (int)$row['case_count'] ?> cases</span></td>
                        <td><?= e(date('M d, Y', strtotime($row['first_seen']))) ?></td>
                        <td><?= e(date('M d, Y', strtotime($row['last_seen']))) ?></td>

                    <?php elseif ($reportType === 'Visit Frequency by Grade'): ?>
                        <td><strong><?= e($row['year_name']) ?></strong></td>
                        <td><span class="status-pill status-ok"><?= (int)$row['visit_count'] ?> visits</span></td>
                        <td><?= (int)$row['unique_students'] ?> students</td>

                    <?php else: ?>
                        <td><?= e(date('M d, Y', strtotime($row['visit_date']))) ?></td>
                        <td><strong><?= e($row['first_name'] . ' ' . $row['last_name']) ?></strong></td>
                        <td><code><?= e($row['student_number']) ?></code></td>
                        <td><?= e($row['complaint']) ?></td>
                        <td><?= e($row['diagnosis']) ?></td>
                        <td><span class="status-pill status-pending"><?= e($row['status_name']) ?></span></td>
                        <td><?= e($row['handler_first'] . ' ' . $row['handler_last']) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="footer">
        ClinIQ Management System - Confidential Report - Page <span class="page-num"></span>
    </div>

    <script>
        // Auto-open print dialog if requested
        <?php if ($format === 'print'): ?>
        window.onload = function() { window.print(); }
        <?php endif; ?>
    </script>
</body>
</html>
