<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Visit.php';

protectPage(1);

$pageTitle = 'Cleared Students';
$activeModule = 'clearedStudents';

$db = new Database();
$conn = $db->connect();
$visitModel = new Visit($conn);

$released = $visitModel->getReleasedCertificates();
$certStats = $visitModel->getCertificateStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
    <meta name="description" content="View all students with released health clearance certificates." />
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../assets/css/enrollmentHealth.css" />
    <link rel="stylesheet" href="../assets/css/settings.css" />
    <link rel="stylesheet" href="../assets/css/notifications_popup.css" />
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        <div class="dashboard-body module-page">

            <section class="module-toolbar">
                <div class="group">
                    <svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"/></svg>
                    <input class="input" type="search" id="clearedSearch" placeholder="Search student name or certificate number…" />
                </div>
                <div class="toolbar-actions">
                    <select class="module-select" id="certTypeFilter">
                        <option value="">All types</option>
                        <option>Health Clearance</option>
                        <option>Medical Clearance</option>
                    </select>
                    <a href="enrollmentHealth.php" class="module-btn secondary" style="text-decoration:none;">🡸 Back to Queue</a>
                </div>
            </section>

            <section class="module-kpi-grid four-col">
                <article class="module-kpi kpi-green"><strong><?= (int)$certStats['released_total'] ?></strong><span>Total released</span></article>
                <article class="module-kpi kpi-blue"><strong><?= (int)$certStats['released_today'] ?></strong><span>Released today</span></article>
                <article class="module-kpi kpi-amber"><strong><?= (int)$certStats['pending_total'] ?></strong><span>Still pending</span></article>
                <article class="module-kpi kpi-orange"><strong><?= count($released) ?></strong><span>Certificates on file</span></article>
            </section>

            <section class="module-layout single">
                <article class="card">
                    <div class="card-header">
                        <div class="card-title">Released Certificates</div>
                    </div>
                    <div class="module-table-wrap">
                        <table class="module-table" id="clearedTable">
                            <thead>
                                <tr>
                                    <th>Certificate No.</th>
                                    <th>Student</th>
                                    <th>Student No.</th>
                                    <th>Type</th>
                                    <th>Date Issued</th>
                                    <th>Issued By</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($released)): ?>
                                    <tr><td colspan="8" style="text-align:center; padding:24px; color:var(--text-muted);">No released certificates yet.</td></tr>
                                <?php else: ?>
                                <?php foreach ($released as $c): 
                                    $mcNum = 'MC-' . date('Y', strtotime($c['date_issued'])) . '-' . str_pad((string)$c['certificate_id'], 4, '0', STR_PAD_LEFT);
                                    $date = date('M d, Y', strtotime($c['date_issued']));
                                ?>
                                <tr data-type="<?= e($c['cert_type']) ?>">
                                    <td class="mono"><?= e($mcNum) ?></td>
                                    <td><strong><?= e($c['student_first'] . ' ' . $c['student_last']) ?></strong></td>
                                    <td class="mono"><?= e($c['student_number'] ?? '---') ?></td>
                                    <td><?= e($c['cert_type']) ?></td>
                                    <td><?= e($date) ?></td>
                                    <td><?= e($c['handler_first'] . ' ' . $c['handler_last']) ?></td>
                                    <td><span class="status-pill ok">Released</span></td>
                                    <td>
                                        <?php if ($c['file_path']): ?>
                                            <a href="../<?= e($c['file_path']) ?>" target="_blank" class="row-action-btn secondary" style="text-decoration:none;">View</a>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);font-size:11px;">No file</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer" style="padding: 12px 20px; display:flex; justify-content:space-between; align-items:center; border-top: 1px solid var(--border-light);">
                        <span class="table-count" style="font-size:12.5px; color:var(--text-muted);">Showing <strong id="visibleCount"><?= count($released) ?></strong> certificates</span>
                        <div class="pagination" style="display:flex; align-items:center; gap:12px;">
                            <button class="page-btn" id="btnPrev" style="width:32px; height:32px; border-radius:8px; border:1px solid var(--border-light); background: #fff; cursor:pointer;" disabled>
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" style="width:14px; height:14px;"><path d="M10 4L6 8l4 4"/></svg>
                            </button>
                            <span class="page-current" id="pageInfo" style="font-size:12.5px; color:var(--text-primary); font-weight:600;">Page 1 of 1</span>
                            <button class="page-btn" id="btnNext" style="width:32px; height:32px; border-radius:8px; border:1px solid var(--border-light); background: #fff; cursor:pointer;" disabled>
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" style="width:14px; height:14px;"><path d="M6 4l4 4-4 4"/></svg>
                            </button>
                        </div>
                    </div>
                </article>
            </section>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../assets/popups/logout.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput  = document.getElementById('clearedSearch');
    const typeFilter   = document.getElementById('certTypeFilter');
    const btnPrev      = document.getElementById('btnPrev');
    const btnNext      = document.getElementById('btnNext');
    const pageInfo     = document.getElementById('pageInfo');

    let currentPage = 1;
    const pageSize = 8;
    let filteredRows = [];

    function filterTable() {
        const q    = (searchInput?.value || '').toLowerCase();
        const type = typeFilter?.value || '';
        const allRows = Array.from(document.querySelectorAll('#clearedTable tbody tr'));

        filteredRows = allRows.filter(row => {
            const text     = row.textContent.toLowerCase();
            const rowType  = row.dataset.type || '';
            const matchQ   = !q || text.includes(q);
            const matchType = !type || rowType === type;
            return matchQ && matchType;
        });

        currentPage = 1;
        renderPagination();
    }

    function renderPagination() {
        const totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * pageSize;
        const end   = start + pageSize;

        const allRows = document.querySelectorAll('#clearedTable tbody tr');
        allRows.forEach(r => r.style.display = 'none');
        filteredRows.slice(start, end).forEach(row => row.style.display = '');

        if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        if (btnPrev) btnPrev.disabled = (currentPage === 1);
        if (btnNext) btnNext.disabled = (currentPage === totalPages);

        const el = document.getElementById('visibleCount');
        if (el) el.textContent = filteredRows.length;
    }

    btnPrev?.addEventListener('click', () => { if (currentPage > 1) { currentPage--; renderPagination(); } });
    btnNext?.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
        if (currentPage < totalPages) { currentPage++; renderPagination(); }
    });

    searchInput?.addEventListener('input', filterTable);
    typeFilter?.addEventListener('change', filterTable);
    filterTable();
});
</script>
<script src="../assets/js/popup.js" defer></script>
<script src="../assets/js/notifications.js" defer></script>
</body>
</html>
