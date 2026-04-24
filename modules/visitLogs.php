<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/Visit.php';
require_once __DIR__ . '/../classes/Medicine.php';

protectPage(1);

$pageTitle = 'Visit Logs';
$activeModule = 'visitLogs';

// Database connection
$db = new Database();
$conn = $db->connect();

// Instantiate Models
$studentModel = new Student($conn);
$visitModel   = new Visit($conn);
$medModel     = new Medicine($conn);

// Fetch data
$allStudents = $studentModel->getAll();
$allMedicines = $medModel->getAll();
$medModel->getAnalytics($allMedicines); // Calculates display statuses

// Fetch all visits and stats
$visits = $visitModel->getAll();
$stats  = $visitModel->getStats($visits);

$todayCount     = $stats['today'];
$pendingCount   = $stats['pending'];
$completedCount = $stats['completed'];
$referredCount  = $stats['referred'];

// Generate JSON for modal
$visitDataJson = [];
foreach ($visits as $v) {
    $visitDataJson[$v['visit_id']] = [
        'student' => $v['student_first'] . ' ' . $v['student_last'],
        'id' => $v['student_number'],
        'date' => date('M d, Y h:i A', strtotime((string)$v['visit_date'])),
        'complaint' => $v['complaint'],
        'diagnosis' => $v['diagnosis'] ?: 'Not diagnosed',
        'symptoms' => $v['symptoms'],
        'treatment' => $v['treatment'],
        'notes' => $v['notes'] ?? 'None',
        'status' => $v['status_name'],
        'statusClass' => strtolower($v['status_name']),
        'medicines' => $visitModel->getMedicines((int)$v['visit_id'])
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
    <meta name="description" content="View and manage all clinic visit records." />
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../assets/css/visitLogs.css" />
    <link rel="stylesheet" href="../assets/css/modal.css" />
    <link rel="stylesheet" href="../assets/css/notifications_popup.css" />
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        <div class="dashboard-body visit-page">

            <!-- Toolbar -->
            <section class="visit-toolbar" aria-label="Visit tools">
                <div class="toolbar-left">
                    <div class="group visit-search">
                        <svg class="icon" aria-hidden="true" viewBox="0 0 24 24">
                            <path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"/>
                        </svg>
                        <input class="input" type="search" id="visitSearch" placeholder="Search by student, complaint, or handler…" />
                    </div>
                </div>
                <div class="toolbar-right">
                    <select class="visit-select" id="statusFilter" aria-label="Status filter">
                        <option value="">All statuses</option>
                        <option>Pending</option>
                        <option>Completed</option>
                        <option>Referred</option>
                    </select>
                    <select class="visit-select" id="doctorFilter" aria-label="Handled by">
                        <option value="">All handlers</option>
                        <?php 
                        $handlers = $visitModel->getAllHandlers();
                        foreach ($handlers as $h): 
                            $prefix = ($h['role'] === 'doctor') ? 'Dr. ' : 'Nurse ';
                            $fullName = $h['first_name'] . ' ' . $h['last_name'];
                        ?>
                            <option value="<?= e($fullName) ?>"><?= e($prefix . $h['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="visit-btn" id="openLogVisitModal" type="button">+ Log Visit</button>
                </div>
            </section>

            <!-- Stats strip -->
            <div class="visit-stats-strip">
                <div class="visit-stat-pill">
                    <span class="visit-stat-num"><?= $todayCount ?></span>
                    <span class="visit-stat-label">Today's Visits</span>
                </div>
                <div class="visit-stat-pill accent-amber">
                    <span class="visit-stat-num"><?= $pendingCount ?></span>
                    <span class="visit-stat-label">Pending Review</span>
                </div>
                <div class="visit-stat-pill accent-green">
                    <span class="visit-stat-num"><?= $completedCount ?></span>
                    <span class="visit-stat-label">Completed</span>
                </div>
                <div class="visit-stat-pill accent-red">
                    <span class="visit-stat-num"><?= $referredCount ?></span>
                    <span class="visit-stat-label">Referred</span>
                </div>
            </div>

            <!-- Table -->
            <section class="visit-layout">
                <article class="card visit-table-card">
                    <div class="card-header">
                        <div class="card-title">Visit Log</div>
                    </div>
                    <div class="visit-table-wrap">
                        <table class="visit-log-table" id="visitTable">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Date &amp; Time</th>
                                    <th>Complaint</th>
                                    <th>Diagnosis</th>
                                    <th>Status</th>
                                    <th>Handler</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visits as $row): 
                                    $initials = strtoupper($row['student_first'][0] . $row['student_last'][0]);
                                    $statusClass = strtolower($row['status_name']);
                                    $dateFormatted = date('M d, Y', strtotime((string)$row['visit_date']));
                                    $timeFormatted = date('h:i A', strtotime((string)$row['visit_date']));
                                ?>
                                <tr>
                                    <td>
                                        <div class="visit-student-cell">
                                            <div class="visit-avatar" style="background:#EEF0FD;color:#5B6AF0;"><?= e($initials) ?></div>
                                            <div>
                                                <div class="visit-name"><?= e($row['student_first'] . ' ' . $row['student_last']) ?></div>
                                                <div class="visit-id mono"><?= e($row['student_number']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e($dateFormatted) ?><br><span class="visit-time-sub"><?= e($timeFormatted) ?></span></td>
                                    <td><?= e($row['complaint']) ?></td>
                                    <td><?= e($row['diagnosis'] ?: '-') ?></td>
                                    <td><span class="visit-status <?= $statusClass ?>"><?= e($row['status_name']) ?></span></td>
                                    <td><?= e($row['handler_first'] . ' ' . $row['handler_last']) ?></td>
                                    <td><button class="visit-action-btn" data-visit="<?= (int)$row['visit_id'] ?>" type="button">View</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer" style="padding: 12px 20px; display:flex; justify-content:space-between; align-items:center; border-top: 1px solid var(--border-light);">
                        <span class="table-count" style="font-size:12.5px; color:var(--text-muted);">Showing <strong><?= count($visits) ?></strong> visits</span>
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

<!-- View Visit Detail Modal -->
<div class="modal-backdrop" id="viewVisitModal" role="dialog" aria-modal="true" aria-label="Visit details">
    <div class="modal-panel" style="max-width: 650px;">
        <div class="modal-header">
            <div>
                <h2 class="modal-student-name" id="vdSubtitle">Loading…</h2>
                <p class="modal-student-meta">Visit Record</p>
            </div>
            <button class="modal-close-btn" type="button" data-close-vd aria-label="Close modal">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l10 10M13 3L3 13"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-info-grid">
                <div class="modal-info-section">
                    <h3 class="modal-section-label">Visit Information</h3>
                    <div class="info-rows">
                        <div class="info-row">
                            <span class="info-key">Student</span>
                            <span class="info-val" id="vdStudent">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Date &amp; Time</span>
                            <span class="info-val" id="vdDate">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Complaint</span>
                            <span class="info-val" id="vdComplaint">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-key">Diagnosis</span>
                            <span class="info-val" id="vdDiagnosis">—</span>
                        </div>
                    </div>
                </div>
                <div class="modal-info-section">
                    <h3 class="modal-section-label">Details</h3>
                    <div class="info-rows">
                        <div class="info-row" style="flex-direction:column; align-items:flex-start; gap:8px;">
                            <span class="info-key">Symptoms</span>
                            <p class="info-val" id="vdSymptoms" style="font-size:0.9rem; line-height:1.5; color:var(--text-main);">—</p>
                        </div>
                        <div class="info-row" style="flex-direction:column; align-items:flex-start; gap:8px;">
                            <span class="info-key">Treatment</span>
                            <p class="info-val" id="vdTreatment" style="font-size:0.9rem; line-height:1.5; color:var(--text-main);">—</p>
                        </div>
                        <div class="info-row" style="flex-direction:column; align-items:flex-start; gap:8px;">
                            <span class="info-key">Medicines Dispensed</span>
                            <div id="vdMedicines" style="display:flex; flex-wrap:wrap; gap:6px;">—</div>
                        </div>
                        <div class="info-row" style="flex-direction:column; align-items:flex-start; gap:8px;">
                            <span class="info-key">Notes</span>
                            <p class="info-val" id="vdNotes" style="font-size:0.9rem; line-height:1.5; color:var(--text-main);">—</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Status update section -->
            <div class="approval-section" id="statusUpdateSection">
                <h3 class="modal-section-label">Visit Status Update</h3>
                <p class="approval-note">Update the status of this clinic visit record.</p>
                <label class="form-field full-width">
                    <span>Remarks / Notes</span>
                    <textarea id="vdRemarks" rows="3" placeholder="Enter any remarks for this visit…"></textarea>
                </label>
                <div class="approval-actions">
                    <button id="btnPending" class="approval-btn conditional" data-status="1" data-status-name="Pending" type="button">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="6"/><path d="M8 5v3M8 11v.5"/></svg>
                        Pending
                    </button>
                    <button id="btnCompleted" class="approval-btn approve" data-status="2" data-status-name="Completed" type="button">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l3.5 3.5L13 5"/></svg>
                        Completed
                    </button>
                    <button id="btnReferred" class="approval-btn reject" data-status="3" data-status-name="Referred" type="button">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4l8 8M12 4l-8 8"/></svg>
                        Referred
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <span class="modal-footer-status">
                <span id="vdStatus"></span>
            </span>
            <div class="modal-footer-actions">
                <button class="toolbar-button secondary" type="button" data-close-vd>Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="modal-backdrop" id="confirmModal" role="dialog" aria-modal="true" aria-label="Confirm action">
    <div class="modal-panel" style="max-width: 400px;">
        <div class="modal-header">
            <h2 class="modal-student-name">Confirm Status Change</h2>
            <button class="modal-close-btn" type="button" id="closeConfirmModal" aria-label="Close modal">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l10 10M13 3L3 13"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage" style="color: var(--text-main); font-size: 14px; line-height: 1.5;">Are you sure you want to change the visit status?</p>
        </div>
        <div class="modal-footer">
            <div class="modal-footer-actions">
                <button class="toolbar-button secondary" type="button" id="cancelConfirm">Cancel</button>
                <button class="toolbar-button" type="button" id="confirmAction">Confirm</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../assets/modals/logVisit.php'; ?>

<script>
const visitData = <?= json_encode($visitDataJson) ?>;
const availableMedicines = <?= json_encode($allMedicines) ?>;

document.addEventListener('DOMContentLoaded', () => {
    // ── Medicine Row Logic ──
    const addMedBtn = document.getElementById('addMedicineRowBtn');
    const medContainer = document.getElementById('medicineRowsContainer');

    // ── Shared Med List Populator ──
    const medList = document.getElementById('medList');
    if (medList && availableMedicines) {
        availableMedicines.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.name;
            opt.textContent = `${m.quantity} ${m.unit} on hand`;
            if (parseInt(m.quantity) <= 0) opt.disabled = true;
            medList.appendChild(opt);
        });
    }

    function createMedRow() {
        // Remove empty state if present
        const emptyState = medContainer?.querySelector('.med-empty-state');
        if (emptyState) emptyState.remove();

        const row = document.createElement('div');
        row.className = 'med-row';

        // Hidden ID — actual value sent to backend
        const idHidden = document.createElement('input');
        idHidden.type = 'hidden';
        idHidden.name = 'medicine_id[]';

        // Searchable medicine input
        const searchField = document.createElement('input');
        searchField.type = 'text';
        searchField.placeholder = 'Type to search medicine…';
        searchField.setAttribute('list', 'medList');
        searchField.className = 'med-search-field';
        searchField.required = true;

        // Resolve ID when user picks from the datalist
        searchField.addEventListener('change', () => {
            const val = searchField.value.trim();
            const match = availableMedicines.find(m => m.name === val);
            idHidden.value = match ? match.medicine_id : '';
            if (match && parseInt(match.quantity) <= 0) {
                searchField.setCustomValidity('Out of stock!');
            } else if (!match) {
                searchField.setCustomValidity('Please select a valid medicine from the list.');
            } else {
                searchField.setCustomValidity('');
            }
        });

        // Quantity
        const qtyField = document.createElement('input');
        qtyField.type = 'number';
        qtyField.name = 'quantity[]';
        qtyField.min = '1';
        qtyField.value = '1';
        qtyField.required = true;
        qtyField.placeholder = 'Qty';
        qtyField.className = 'med-qty-field';

        // Remove row button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'med-row-remove';
        removeBtn.setAttribute('aria-label', 'Remove medicine');
        removeBtn.innerHTML = '&times;';
        removeBtn.addEventListener('click', () => {
            row.remove();
            if (!medContainer.querySelector('.med-row')) {
                medContainer.innerHTML = '<div class="med-empty-state">No medicines added yet. Click "+ Add Medicine" to begin.</div>';
            }
        });

        row.appendChild(idHidden);
        row.appendChild(searchField);
        row.appendChild(qtyField);
        row.appendChild(removeBtn);
        medContainer.appendChild(row);
        searchField.focus();
    }

    // Attach medicine add button
    addMedBtn?.addEventListener('click', createMedRow);

    // Filtering
    const searchInput = document.getElementById('visitSearch');
    const statusFilter = document.getElementById('statusFilter');
    const doctorFilter = document.getElementById('doctorFilter');
    const btnPrev = document.getElementById('btnPrev');
    const btnNext = document.getElementById('btnNext');
    const pageInfo = document.getElementById('pageInfo');

    let currentPage = 1;
    const pageSize = 5;
    let filteredRows = [];

    function filterRows() {
        const q = (searchInput?.value||'').toLowerCase();
        const s = (statusFilter?.value||'').toLowerCase();
        const d = (doctorFilter?.value||'').toLowerCase();
        const allRows = Array.from(document.querySelectorAll('#visitTable tbody tr'));
        
        filteredRows = allRows.filter(row => {
            const txt = row.textContent.toLowerCase();
            const rowStatus = row.querySelector('.visit-status')?.textContent.toLowerCase()||'';
            const rowDoc = row.cells[5]?.textContent.toLowerCase()||'';
            return (!q||txt.includes(q)) && (!s||rowStatus.includes(s)) && (!d||rowDoc.includes(d));
        });

        currentPage = 1;
        renderPagination();
    }

    function renderPagination() {
        const totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        // Hide all rows in the table
        const allRows = document.querySelectorAll('#visitTable tbody tr');
        allRows.forEach(row => row.style.display = 'none');

        // Show only the current page's slice of filtered rows
        filteredRows.slice(start, end).forEach(row => {
            row.style.display = '';
        });

        // Update UI info
        if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        if (btnPrev) btnPrev.disabled = (currentPage === 1);
        if (btnNext) btnNext.disabled = (currentPage === totalPages);

        const countEl = document.querySelector('.table-count strong');
        if (countEl) countEl.textContent = filteredRows.length;
    }

    btnPrev?.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            renderPagination();
        }
    });

    btnNext?.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
        if (currentPage < totalPages) {
            currentPage++;
            renderPagination();
        }
    });

    searchInput?.addEventListener('input', filterRows);
    statusFilter?.addEventListener('change', filterRows);
    doctorFilter?.addEventListener('change', filterRows);

    // Initial render
    filterRows();

    // View modal
    const viewModal = document.getElementById('viewVisitModal');
    let currentVisitId = null;
    document.querySelectorAll('.visit-action-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const v = visitData[btn.dataset.visit];
            if (!v) return;
            currentVisitId = btn.dataset.visit;
            document.getElementById('vdSubtitle').textContent = `${v.student} · ${v.id}`;
            document.getElementById('vdStudent').textContent = `${v.student} (${v.id})`;
            document.getElementById('vdDate').textContent = v.date;
            document.getElementById('vdComplaint').textContent = v.complaint;
            document.getElementById('vdDiagnosis').textContent = v.diagnosis;
            document.getElementById('vdSymptoms').textContent = v.symptoms;
            document.getElementById('vdTreatment').textContent = v.treatment;
            document.getElementById('vdNotes').textContent = v.notes;

            // Populate Medicines
            const medContainer = document.getElementById('vdMedicines');
            medContainer.innerHTML = '';
            if (v.medicines && v.medicines.length > 0) {
                v.medicines.forEach(m => {
                    const badge = document.createElement('span');
                    badge.style.background = '#F3F4F6';
                    badge.style.padding = '4px 10px';
                    badge.style.borderRadius = '12px';
                    badge.style.fontSize = '12px';
                    badge.style.border = '1px solid #E5E7EB';
                    badge.textContent = `${m.name} x${m.quantity_given} ${m.unit}`;
                    medContainer.appendChild(badge);
                });
            } else {
                medContainer.textContent = 'None';
            }

            const st = document.getElementById('vdStatus');
            st.textContent = v.status;
            st.className = `visit-status ${v.statusClass}`;

            // Populate remarks field
            const remarksField = document.getElementById('vdRemarks');
            if (remarksField) remarksField.value = v.notes || '';

            // Hide status update section if completed
            const statusUpdateSection = document.getElementById('statusUpdateSection');
            if (v.statusClass === 'completed') {
                statusUpdateSection.style.display = 'none';
            } else {
                statusUpdateSection.style.display = 'block';
                // Disable the button that matches current status
                const statusMap = { 'pending': '1', 'completed': '2', 'referred': '3' };
                const currentStatusId = statusMap[v.statusClass] || '';
                document.querySelectorAll('[data-status]').forEach(btn => {
                    btn.disabled = false;
                    if (btn.dataset.status === currentStatusId) {
                        btn.disabled = true;
                    }
                });
            }

            viewModal?.classList.add('is-open');
            document.body.classList.add('modal-open');
        });
    });

    document.querySelectorAll('[data-close-vd]').forEach(b => b.addEventListener('click', () => {
        viewModal?.classList.remove('is-open');
        document.body.classList.remove('modal-open');
    }));
    viewModal?.addEventListener('click', e => {
        if(e.target===viewModal) {
            viewModal.classList.remove('is-open');
            document.body.classList.remove('modal-open');
        }
    });

    // Update visit status via buttons
    let pendingStatusUpdate = null;
    const confirmModal = document.getElementById('confirmModal');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmActionBtn = document.getElementById('confirmAction');
    const cancelConfirmBtn = document.getElementById('cancelConfirm');
    const closeConfirmModalBtn = document.getElementById('closeConfirmModal');

    function showConfirmModal(message, callback) {
        confirmMessage.textContent = message;
        confirmModal.classList.add('is-open');
        document.body.classList.add('modal-open');
        pendingStatusUpdate = callback;
    }

    function hideConfirmModal() {
        confirmModal.classList.remove('is-open');
        document.body.classList.remove('modal-open');
        pendingStatusUpdate = null;
    }

    cancelConfirmBtn?.addEventListener('click', hideConfirmModal);
    closeConfirmModalBtn?.addEventListener('click', hideConfirmModal);
    confirmModal?.addEventListener('click', e => {
        if (e.target === confirmModal) hideConfirmModal();
    });

    confirmActionBtn?.addEventListener('click', () => {
        if (pendingStatusUpdate) {
            pendingStatusUpdate();
        }
        hideConfirmModal();
    });

    document.querySelectorAll('[data-status]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!currentVisitId) return;

            const newStatusId = btn.dataset.status;
            const statusName = btn.dataset.statusName;
            const remarks = document.getElementById('vdRemarks')?.value || '';

            if (!newStatusId) return;

            // Show custom confirmation modal
            showConfirmModal(`Are you sure you want to mark this visit as ${statusName}?`, async () => {
                // Disable all status buttons
                document.querySelectorAll('[data-status]').forEach(b => {
                    b.disabled = true;
                });

                const originalText = btn.textContent;
                btn.textContent = 'Updating...';

                try {
                    const formData = new FormData();
                    formData.append('visit_id', currentVisitId);
                    formData.append('status_id', newStatusId);
                    formData.append('remarks', remarks);

                    const response = await fetch('../actions/updateVisitStatus.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Reload to reflect changes
                        location.reload();
                    } else {
                        alert('Error: ' + result.message);
                        btn.textContent = originalText;
                        // Re-enable all status buttons on error
                        document.querySelectorAll('[data-status]').forEach(b => {
                            b.disabled = false;
                        });
                    }
                } catch (err) {
                    alert('Connection error. Please try again.');
                    btn.textContent = originalText;
                    // Re-enable all status buttons on error
                    document.querySelectorAll('[data-status]').forEach(b => {
                        b.disabled = false;
                    });
                }
            });
        });
    });

    // Log visit modal
    const logBtn = document.getElementById('openLogVisitModal');
    const logModal = document.getElementById('logVisitModal');
    const logForm = document.getElementById('logVisitForm');
    const logMsg = document.getElementById('logVisitMsg');

    function resetMedSection() {
        if (medContainer) {
            medContainer.innerHTML = '<div class="med-empty-state">No medicines added yet. Click "+ Add Medicine" to begin.</div>';
        }
    }

    function closeLogModal() {
        logModal?.classList.remove('is-open');
        document.body.classList.remove('log-visit-open');
    }

    logBtn?.addEventListener('click', () => { logModal?.classList.add('is-open'); document.body.classList.add('log-visit-open'); });
    document.querySelectorAll('[data-close-log-modal]').forEach(b => b.addEventListener('click', closeLogModal));
    logModal?.addEventListener('click', e => { if (e.target === logModal) closeLogModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeLogModal(); viewModal?.classList.remove('is-open'); } });

    // Populate datalist
    const studentList = document.getElementById('studentList');
    const studentsArr = <?= json_encode($allStudents) ?>;
    studentsArr.forEach(s => {
        let opt = document.createElement('option');
        opt.value = s.student_number;
        opt.textContent = `${s.first_name} ${s.last_name}`;
        studentList?.appendChild(opt);
    });

    logForm?.addEventListener('submit', async e => {
        e.preventDefault();
        if(!logForm.checkValidity()){ logForm.reportValidity(); return; }
        
        const formData = new FormData(logForm);
        if(logMsg) { logMsg.textContent = 'Saving...'; logMsg.className = 'log-visit-msg'; }

        try {
            const resp = await fetch('../actions/saveVisit.php', {
                method: 'POST',
                body: formData
            });
            const result = await resp.json();
            
            if (result.success) {
                if (logMsg) { logMsg.textContent = result.message; logMsg.className = 'log-visit-msg success'; }
                logForm?.reset();
                resetMedSection();
                setTimeout(() => { location.reload(); }, 1000);
            } else {
                if(logMsg){ logMsg.textContent = result.message; logMsg.className = 'log-visit-msg warn'; }
            }
        } catch (err) {
            if(logMsg){ logMsg.textContent = 'Network error. Try again.'; logMsg.className = 'log-visit-msg warn'; }
        }
    });
});
</script>
<script src="../assets/js/popup.js" defer></script>
<script src="../assets/js/notifications.js" defer></script>
</body>
</html>
