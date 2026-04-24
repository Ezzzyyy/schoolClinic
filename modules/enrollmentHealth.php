<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/Visit.php';

protectPage(1);

$pageTitle = 'Health Clearance';
$activeModule = 'enrollmentHealth';

$db = new Database();
$conn = $db->connect();
$studentModel = new Student($conn);
$visitModel   = new Visit($conn);

// Fetch stats
$summary = $studentModel->getAssessmentSummary();

// Assessment Tracker: only students who are NOT yet "Active" (not cleared)
$allStudents = $studentModel->getAll();
$pendingStudents = array_filter($allStudents, function($s) {
    return $s['status'] !== 'Active';
});

// Certificate Queue: pending certificates
$certificates = $visitModel->getCertificates();
$certStats    = $visitModel->getCertificateStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
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

            <!-- Tabs -->
            <div class="module-tabs">
                <button class="module-tab active" data-target="tab-assessment">Assessment Tracker</button>
                <button class="module-tab" data-target="tab-certificates">Certificate Queue</button>
            </div>

            <!-- TAB 1: Assessment Tracker -->
            <div id="tab-assessment" class="tab-content active">
                <section class="module-toolbar">
                    <div class="group"><svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"/></svg><input class="input" type="search" placeholder="Search student or ID…" id="trackerSearch" /></div>
                    <div class="toolbar-actions">
                        <select class="module-select" id="trackerClearanceFilter">
                            <option value="">All clearance</option>
                            <option>Pending review</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                </section>
                <section class="module-kpi-grid four-col">
                    <article class="module-kpi kpi-blue"><strong><?= count($pendingStudents) ?></strong><span>Awaiting clearance</span></article>
                    <article class="module-kpi kpi-amber"><strong><?= $summary['not_assessed'] ?></strong><span>Not assessed</span></article>
                    <article class="module-kpi kpi-orange"><strong><?= $summary['conditional'] ?></strong><span>Conditional</span></article>
                    <article class="module-kpi kpi-green"><strong><?= $summary['cleared'] ?></strong><span>Cleared (total)</span></article>
                </section>
                <section class="module-layout single">
                    <article class="card">
                        <div class="card-header"><div class="card-title">Enrollment Assessment Tracker</div></div>
                        <div class="module-table-wrap">
                            <table class="module-table" id="trackerTable">
                                <thead><tr><th>Student ID</th><th>Student Name</th><th>Grade</th><th>Lab Status</th><th>Clearance</th><th>Updated</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php if (empty($pendingStudents)): ?>
                                        <tr><td colspan="7" style="text-align:center; padding:24px; color:var(--text-muted);">All students have been cleared! 🎉</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($pendingStudents as $row): 
                                        $files = ['x_ray', 'urinalysis', 'hematology', 'drug_test'];
                                        $present = 0;
                                        foreach ($files as $f) if ($row[$f]) $present++;
                                        
                                        $labStatus = ($present === 4) ? 'Complete' : ($present . '/4 files');
                                        if ($present === 0) $labStatus = 'Missing all';

                                        $statusClass = 'pending';
                                        if ($row['assessment_date'] && $row['status'] === 'Pending review') $statusClass = 'warn';
                                        
                                        $date = $row['assessment_date'] ? date('M d', strtotime($row['assessment_date'])) : '---';
                                    ?>
                                    <tr>
                                        <td class="mono"><?= e($row['student_number']) ?></td>
                                        <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                        <td><?= e($row['year_name']) ?></td>
                                        <td><?= e($labStatus) ?></td>
                                        <td><span class="status-pill <?= $statusClass ?>"><?= e($row['status']) ?></span></td>
                                        <td><?= e($date) ?></td>
                                        <td>
                                            <button class="row-action-btn" 
                                                    data-modal="assessmentRecord" 
                                                    data-student-id="<?= (int)$row['student_id'] ?>"
                                                    data-student-num="<?= e($row['student_number']) ?>"
                                                    data-height="<?= e($row['height'] ?? '') ?>"
                                                    data-weight="<?= e($row['weight'] ?? '') ?>"
                                                    data-bp="<?= e($row['blood_pressure'] ?? '') ?>"
                                                    data-pulse="<?= e($row['pulse_rate'] ?? '') ?>"
                                                    data-labs="<?= e($row['lab_remarks'] ?? '') ?>"
                                                    data-clearance="<?= e($row['clearance_status'] ?? 'pending') ?>"
                                                    type="button">Record</button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer" style="padding: 12px 20px; display:flex; justify-content:space-between; align-items:center; border-top: 1px solid var(--border-light);">
                            <span class="table-count" style="font-size:12px; color:var(--text-muted);">Showing <strong id="trackerCount"><?= count($pendingStudents) ?></strong> students</span>
                            <div class="pagination" style="display:flex; align-items:center; gap:10px;">
                                <button class="page-btn" id="btnPrevTracker" style="width:28px; height:28px; border-radius:6px; border:1px solid var(--border-light); background:#fff; cursor:pointer;" disabled>
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" style="width:12px; height:12px;"><path d="M10 4L6 8l4 4"/></svg>
                                </button>
                                <span class="page-current" id="pageInfoTracker" style="font-size:12px; font-weight:600;">Page 1 of 1</span>
                                <button class="page-btn" id="btnNextTracker" style="width:28px; height:28px; border-radius:6px; border:1px solid var(--border-light); background:#fff; cursor:pointer;" disabled>
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" style="width:12px; height:12px;"><path d="M6 4l4 4-4 4"/></svg>
                                </button>
                            </div>
                        </div>
                    </article>
                </section>
            </div>

            <!-- TAB 2: Certificate Queue -->
            <div id="tab-certificates" class="tab-content">
                <section class="module-toolbar">
                    <div class="group">
                        <svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"/></svg>
                        <input class="input" type="search" placeholder="Search certificate number or student…" id="certSearch" />
                    </div>
                    <div class="toolbar-actions">
                        <a href="clearedStudents.php" class="module-btn secondary" style="text-decoration:none;">View Cleared List 🡺</a>
                    </div>
                </section>
                <section class="module-kpi-grid four-col">
                    <article class="module-kpi kpi-blue"><strong><?= (int)$certStats['issued_today'] ?></strong><span>Issued today</span></article>
                    <article class="module-kpi kpi-amber"><strong><?= (int)$certStats['pending_total'] ?></strong><span>Pending release</span></article>
                    <article class="module-kpi kpi-green"><strong><?= (int)$certStats['released_total'] ?></strong><span>Released total</span></article>
                    <article class="module-kpi kpi-orange"><strong><?= (int)$certStats['released_today'] ?></strong><span>Released today</span></article>
                </section>
                <section class="module-layout single">
                    <article class="card">
                        <div class="card-header"><div class="card-title">Certificate Queue</div></div>
                        <div class="module-table-wrap">
                            <table class="module-table" id="certTable">
                                <thead><tr><th>Certificate No.</th><th>Student</th><th>Student No.</th><th>Date Issued</th><th>Type</th><th>Issued By</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <?php if (empty($certificates)): ?>
                                        <tr><td colspan="8" style="text-align:center; padding:24px; color:var(--text-muted);">No pending certificates in the queue.</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($certificates as $c): 
                                        $mcNum = 'MC-' . date('Y', strtotime($c['date_issued'])) . '-' . str_pad((string)$c['certificate_id'], 4, '0', STR_PAD_LEFT);
                                        $date = date('M d, Y', strtotime($c['date_issued']));
                                    ?>
                                    <tr>
                                        <td class="mono">
                                            <?= e($mcNum) ?>
                                            <?php if ($c['file_path']): ?>
                                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-left:4px;color:var(--primary);vertical-align:middle;" title="File attached"><path d="M5 5v5a3 3 0 006 0V4a2 2 0 10-4 0v6a1 1 0 002 0V5"/></svg>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($c['student_first'] . ' ' . $c['student_last']) ?></td>
                                        <td class="mono"><?= e($c['student_number'] ?? '---') ?></td>
                                        <td><?= e($date) ?></td>
                                        <td><?= e($c['cert_type']) ?></td>
                                        <td><?= e($c['handler_first'] . ' ' . $c['handler_last']) ?></td>
                                        <td><span class="status-pill pending">Pending</span></td>
                                        <td class="row-actions">
                                            <button class="row-action-btn" data-action="release" 
                                                    data-cert-id="<?= (int)$c['certificate_id'] ?>"
                                                    data-student="<?= e($c['student_first'] . ' ' . $c['student_last']) ?>"
                                                    type="button">Release</button>
                                            <button class="row-action-btn secondary" data-modal="attachCert" data-cert-id="<?= (int)$c['certificate_id'] ?>" type="button">Attach</button>
                                            <?php if ($c['file_path']): ?>
                                                <a href="../<?= e($c['file_path']) ?>" target="_blank" class="row-action-btn secondary" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">View</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-footer" style="padding: 12px 20px; display:flex; justify-content:space-between; align-items:center; border-top: 1px solid var(--border-light);">
                            <span class="table-count" style="font-size:12px; color:var(--text-muted);">Showing <strong id="certCount"><?= count($certificates) ?></strong> certificates</span>
                            <div class="pagination" style="display:flex; align-items:center; gap:10px;">
                                <button class="page-btn" id="btnPrevCert" style="width:28px; height:28px; border-radius:6px; border:1px solid var(--border-light); background:#fff; cursor:pointer;" disabled>
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" style="width:12px; height:12px;"><path d="M10 4L6 8l4 4"/></svg>
                                </button>
                                <span class="page-current" id="pageInfoCert" style="font-size:12px; font-weight:600;">Page 1 of 1</span>
                                <button class="page-btn" id="btnNextCert" style="width:28px; height:28px; border-radius:6px; border:1px solid var(--border-light); background:#fff; cursor:pointer;" disabled>
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" style="width:12px; height:12px;"><path d="M6 4l4 4-4 4"/></svg>
                                </button>
                            </div>
                        </div>
                    </article>
                </section>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/../assets/modals/assessmentRecord.php'; ?>
<?php include __DIR__ . '/../assets/modals/attachCertificate.php'; ?>
<?php include __DIR__ . '/../assets/popups/logout.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // ═══ Tab switching ═══
    const tabs = document.querySelectorAll('.module-tab');
    const contents = document.querySelectorAll('.tab-content');
    
    function switchTab(targetId) {
        tabs.forEach(t => {
            t.classList.toggle('active', t.dataset.target === targetId);
        });
        contents.forEach(c => {
            c.classList.toggle('active', c.id === targetId);
        });
        window.location.hash = targetId;
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            switchTab(tab.dataset.target);
        });
    });

    // Check hash on load
    if (window.location.hash) {
        const hash = window.location.hash.substring(1);
        if (document.getElementById(hash)) {
            switchTab(hash);
        }
    }

    // ═══ AJAX helper ═══
    async function postAction(url, formData) {
        const resp = await fetch(url, { method: 'POST', body: formData });
        return resp.json();
    }

    // ═══ Pagination helper ═══
    function initPagination(config) {
        let currentPage = 1;
        const pageSize = 5;
        const allRows = Array.from(document.querySelectorAll(config.tableSelector + ' tbody tr'));
        
        function render() {
            const totalPages = Math.ceil(allRows.length / pageSize) || 1;
            if (currentPage > totalPages) currentPage = totalPages;

            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            allRows.forEach((row, idx) => {
                row.style.display = (idx >= start && idx < end) ? '' : 'none';
            });

            if (config.pageInfo) config.pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
            if (config.btnPrev) config.btnPrev.disabled = (currentPage === 1);
            if (config.btnNext) config.btnNext.disabled = (currentPage === totalPages);
        }

        config.btnPrev?.addEventListener('click', () => {
            if (currentPage > 1) { currentPage--; render(); }
        });
        config.btnNext?.addEventListener('click', () => {
            const totalPages = Math.ceil(allRows.length / pageSize) || 1;
            if (currentPage < totalPages) { currentPage++; render(); }
        });

        render();
    }

    initPagination({
        tableSelector: '#trackerTable',
        btnPrev: document.getElementById('btnPrevTracker'),
        btnNext: document.getElementById('btnNextTracker'),
        pageInfo: document.getElementById('pageInfoTracker')
    });

    initPagination({
        tableSelector: '#certTable',
        btnPrev: document.getElementById('btnPrevCert'),
        btnNext: document.getElementById('btnNextCert'),
        pageInfo: document.getElementById('pageInfoCert')
    });

    // ═══ Assessment Record modal ═══
    document.querySelectorAll('[data-modal="assessmentRecord"]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('as_student_id').value = btn.dataset.studentId || '';
            document.getElementById('as_student_num').value = btn.dataset.studentNum || '';
            document.getElementById('as_height').value = btn.dataset.height || '';
            document.getElementById('as_weight').value = btn.dataset.weight || '';
            document.getElementById('as_bp').value = btn.dataset.bp || '';
            document.getElementById('as_pulse').value = btn.dataset.pulse || '';
            document.getElementById('as_labs').value = btn.dataset.labs || '';
            
            const dateInp = document.getElementById('as_date');
            if (dateInp) dateInp.value = new Date().toISOString().split('T')[0];

            const status = btn.dataset.clearance || 'pending';
            const rad = document.querySelector(`input[name="clearance_status"][value="${status}"]`);
            if (rad) rad.checked = true;

            document.getElementById('assessmentRecordModal')?.classList.add('is-open');
        });
    });

    // ═══ Assessment Form Submit ═══
    const assessmentForm = document.getElementById('assessmentForm');
    assessmentForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = assessmentForm.closest('.eh-panel').querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        try {
            btn.disabled = true;
            btn.textContent = 'Saving...';

            const formData = new FormData(assessmentForm);
            const status = formData.get('clearance_status');
            const res = await fetch('../actions/saveAssessment.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.success) {
                if (status === 'cleared' || status === 'conditional') {
                    window.location.hash = 'tab-certificates';
                }
                location.reload();
            } else {
                alert(data.message || 'Error saving assessment');
            }
        } catch (err) {
            console.error(err);
            alert('A system error occurred.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // ═══ Release Certificate ═══
    document.querySelectorAll('[data-action="release"]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const student = btn.dataset.student;
            if (!confirm(`Release certificate for ${student}?\n\nThis will move it to the Cleared Students list.`)) return;

            btn.disabled = true;
            btn.textContent = 'Releasing…';

            try {
                const formData = new FormData();
                formData.append('certId', btn.dataset.certId);
                const result = await postAction('../actions/releaseCertificate.php', formData);

                if (result.success) {
                    const row = btn.closest('tr');
                    row.style.transition = 'opacity 0.3s, transform 0.3s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => { 
                        row.remove();
                        // Update KPIs if returned
                        if (result.stats) {
                            const kpis = document.querySelectorAll('#tab-certificates .module-kpi strong');
                            if (kpis.length >= 4) {
                                kpis[0].textContent = result.stats.issued_today;
                                kpis[1].textContent = result.stats.pending_total;
                                kpis[2].textContent = result.stats.released_total;
                                kpis[3].textContent = result.stats.released_today;
                            }
                        }
                    }, 300);
                } else {
                    alert(result.message);
                    btn.disabled = false;
                    btn.textContent = 'Release';
                }
            } catch (err) {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Release';
            }
        });
    });

    // ═══ Attach Certificate modal ═══
    document.querySelectorAll('[data-modal="attachCert"]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('ac_cert_id').value = btn.dataset.certId || '';
            document.getElementById('attachCertModal')?.classList.add('is-open');
        });
    });

    // ═══ Attach Certificate Form ═══
    const attachCertForm = document.getElementById('attachCertForm');
    attachCertForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = attachCertForm.closest('.eh-panel').querySelector('button[type="submit"]');
        const originalText = btn.innerHTML;
        
        try {
            btn.disabled = true;
            btn.textContent = 'Uploading...';
            const formData = new FormData(attachCertForm);
            const res = await fetch('../actions/attachCertificate.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) { 
                window.location.hash = 'tab-certificates';
                location.reload(); 
            }
            else { alert(data.message || 'Error uploading certificate'); }
        } catch (err) {
            alert('A system error occurred.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // ═══ Close modals ═══
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.eh-modal').forEach(m => m.classList.remove('is-open'));
        });
    });
    document.querySelectorAll('.eh-modal').forEach(m => {
        m.addEventListener('click', e => { if(e.target === m) m.classList.remove('is-open'); });
    });
    document.addEventListener('keydown', e => {
        if(e.key === 'Escape') document.querySelectorAll('.eh-modal').forEach(m => m.classList.remove('is-open'));
    });
});
</script>
<script src="../assets/js/popup.js" defer></script>
<script src="../assets/js/notifications.js" defer></script>
</body>
</html>
