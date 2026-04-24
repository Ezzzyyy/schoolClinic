<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Report.php';

protectPage(1);

$db = new Database();
$conn = $db->connect();

$pageTitle = 'Reports';
$activeModule = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../assets/css/reports.css" />
    <link rel="stylesheet" href="../assets/css/settings.css" />
    <link rel="stylesheet" href="../assets/css/notifications_popup.css" />
    <style>
        .notif-highlight-row {
            transition: box-shadow 0.3s ease, background-color 0.3s ease;
            box-shadow: inset 0 0 0 3px rgba(91, 106, 240, 0.45);
            background-color: rgba(224, 231, 255, 0.55);
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        <div class="dashboard-body module-page">

            <section class="module-toolbar">
                <form class="toolbar-actions" style="margin-left:0; flex-wrap: wrap;" action="../actions/generateReport.php" method="POST" target="_blank">
                    <select class="module-select" name="reportType">
                        <option value="Illness Trend Report">Illness trend report</option>
                        <option value="Visit Frequency by Grade">Visit frequency report</option>
                        <option value="Medicine Usage Report">Medicine usage report</option>
                        <option value="Enrollment Clearance Summary">Enrollment clearance report</option>
                    </select>
                    <select class="module-select" name="dateRangeQuick">
                        <option value="30">Last 30 days</option>
                        <option value="semester">This semester</option>
                        <option value="year">This school year</option>
                    </select>
                    <input type="hidden" name="exportFormat" value="pdf">
                    <button class="module-btn" type="submit">Quick Export (PDF)</button>
                    <button class="module-btn secondary" type="button" id="openModalBtn">Advanced Options...</button>
                </form>
            </section>

<?php
$reportModel = new Report($conn);
$stats = $reportModel->getReportSummary();
?>
            <section class="module-kpi-grid four-col">
                <article class="module-kpi kpi-blue"><strong><?= $stats['total_visits'] ?></strong><span>Total clinic visits</span></article>
                <article class="module-kpi kpi-amber"><strong><?= $stats['pending_assessments'] ?></strong><span>Assessments pending</span></article>
                <article class="module-kpi kpi-green"><strong><?= $stats['cleared_students'] ?></strong><span>Cleared students</span></article>
                <article class="module-kpi kpi-orange"><strong><?= $stats['total_medicines'] ?></strong><span>Medicine varieties</span></article>
            </section>

            <section class="module-layout single">
                <article class="card">
                    <div class="card-header">
                        <div class="card-title">Generated Reports</div>
                    </div>
                    <div class="module-table-wrap">
                        <table class="module-table">
                            <thead><tr><th>Report</th><th>Scope</th><th>Prepared By</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php
                                $availableReports = [
                                    ['name' => 'Weekly Illness Trend', 'type' => 'Illness Trend Report', 'scope' => 'Last 7 Days', 'by' => 'System'],
                                    ['name' => 'Visit Frequency by Grade', 'type' => 'Visit Frequency by Grade', 'scope' => 'All Levels', 'by' => 'System'],
                                    ['name' => 'Medicine Usage Pulse', 'type' => 'Medicine Usage Report', 'scope' => 'Current Stock', 'by' => 'System'],
                                    ['name' => 'Enrollment Clearance Summary', 'type' => 'Enrollment Clearance Summary', 'scope' => 'Current Semester', 'by' => 'System']
                                ];

                                foreach ($availableReports as $r):
                                ?>
                                <tr>
                                    <td><strong><?= e($r['name']) ?></strong></td>
                                    <td><?= e($r['scope']) ?></td>
                                    <td><?= e($r['by']) ?></td>
                                    <td><?= date('M d, H:i') ?></td>
                                    <td><span class="status-pill ok">Ready</span></td>
                                    <td class="row-actions">
                                        <form action="../actions/generateReport.php" method="POST" target="_blank" style="display:inline;">
                                            <input type="hidden" name="reportType" value="<?= e($r['type']) ?>">
                                            <input type="hidden" name="exportFormat" value="print">
                                            <button class="row-action-btn" type="submit">Print</button>
                                        </form>
                                        <form action="../actions/generateReport.php" method="POST" target="_blank" style="display:inline;">
                                            <input type="hidden" name="reportType" value="<?= e($r['type']) ?>">
                                            <input type="hidden" name="exportFormat" value="pdf">
                                            <button class="row-action-btn secondary" type="submit">Export PDF</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

            <!-- Print Queue -->
            <section class="module-layout single" style="margin-top: 6px;">
                <article class="card">
                    <div class="card-header">
                        <div class="card-title">Recent Report History</div>
                        <span class="card-link">Latest activity</span>
                    </div>
                    <div class="module-table-wrap">
                        <table class="module-table">
                            <thead><tr><th>Job ID</th><th>Type</th><th>Format</th><th>Records</th><th>Requested By</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php
                                $logs = $reportModel->getRecentLogs(10);
                                if (empty($logs)):
                                ?>
                                    <tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-muted);">No report activity logged yet.</td></tr>
                                <?php else: ?>
                                <?php foreach ($logs as $log): 
                                    $jobId = 'PRT-' . str_pad((string)$log['log_id'], 3, '0', STR_PAD_LEFT);
                                ?>
                                <tr data-report-log-id="<?= (int)$log['log_id'] ?>">
                                    <td class="mono"><?= $jobId ?></td>
                                    <td><?= e($log['report_type']) ?></td>
                                    <td><span class="status-pill neutral"><?= strtoupper($log['format']) ?></span></td>
                                    <td><?= (int)$log['record_count'] ?></td>
                                    <td><?= e($log['first_name'] . ' ' . $log['last_name']) ?></td>
                                    <td>
                                        <?php 
                                            $stat = strtolower($log['status']);
                                            $pillClass = ($stat === 'ready' || $stat === 'printed') ? 'ok' : (($stat === 'queued') ? 'pending' : 'warn');
                                        ?>
                                        <span class="status-pill <?= $pillClass ?>"><?= e($log['status']) ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../assets/modals/generateReport.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // "Advanced Options" button opens modal
    document.getElementById('openModalBtn')?.addEventListener('click', () => {
        document.getElementById('generateExportModal')?.classList.add('is-open');
    });

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => document.querySelectorAll('.eh-modal').forEach(m => m.classList.remove('is-open')));
    });
    document.querySelectorAll('.eh-modal').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m) m.classList.remove('is-open'); });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('.eh-modal').forEach(m => m.classList.remove('is-open'));
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('highlight') === 'report') {
        const logId = params.get('log_id') || '';
        if (logId) {
            const targetRow = document.querySelector(`tr[data-report-log-id="${logId}"]`);
            if (targetRow) {
                targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetRow.classList.add('notif-highlight-row');
                setTimeout(() => targetRow.classList.remove('notif-highlight-row'), 2600);
            }
        }
    }

});
</script>
<script src="../assets/js/popup.js" defer></script>
<script src="../assets/js/notifications.js" defer></script>
</body>
</html>

