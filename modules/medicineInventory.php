<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Medicine.php';

protectPage(1);

$pageTitle = 'Medicine Inventory';
$activeModule = 'medicineInventory';

// Database connection
$db = new Database();
$conn = $db->connect();

// Instantiate Model
$medModel = new Medicine($conn);

// Fetch all medicines and process analytics
$medicines = $medModel->getAll();
$analytics = $medModel->getAnalytics($medicines);

$totalMedsCount  = $analytics['total'];
$lowStockCount   = $analytics['low_stock'];
$nearExpiryCount = $analytics['near_expiry'];
$criticalCount   = $analytics['critical'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../assets/css/medicineInventory.css" />
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
                <div class="group"><svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"/></svg><input class="input" type="search" id="medSearchInput" placeholder="Search medicine name or category…" /></div>
                <div class="toolbar-actions">
                    <select class="module-select" id="categoryFilter">
                        <option value="">All categories</option>
                        <option>Analgesic</option>
                        <option>Antibiotic</option>
                        <option>Antihistamine</option>
                        <option>Antacid</option>
                        <option>First Aid</option>
                        <option>Vitamins / Supplements</option>
                        <option>Other</option>
                    </select>
                    <select class="module-select" id="stockFilter">
                        <option value="">All stock levels</option>
                        <option value="Healthy">Healthy</option>
                        <option value="Low">Low</option>
                        <option value="Critical">Critical</option>
                    </select>
                    <select class="module-select" id="expiryFilter">
                        <option value="">All expiry status</option>
                        <option value="Safe">Safe (90+ days)</option>
                        <option value="Near Expiry">Near Expiry (≤90 days)</option>
                        <option value="Expired">Expired</option>
                    </select>
                    <button class="module-btn" type="button" id="openAddMedModal">+ Add Medicine</button>
                </div>
            </section>

            <section class="module-kpi-grid four-col">
                <article class="module-kpi kpi-blue"><strong><?= $totalMedsCount ?></strong><span>Medicines tracked</span></article>
                <article class="module-kpi kpi-amber"><strong><?= $lowStockCount ?></strong><span>Low stock</span></article>
                <article class="module-kpi kpi-orange"><strong><?= $nearExpiryCount ?></strong><span>Near expiry</span></article>
                <article class="module-kpi kpi-red"><strong><?= $criticalCount ?></strong><span>Critical</span></article>
            </section>

            <section class="module-layout single">
                <article class="card">
                    <div class="card-header"><div class="card-title">Stock Table</div></div>
                    <div class="module-table-wrap">
                        <table class="module-table" id="medTable">
                            <thead><tr><th>Medicine</th><th>Category</th><th>On Hand</th><th>Reorder Level</th><th>Expiry</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($medicines as $med): 
                                    $expiryPretty = date('M Y', strtotime((string)$med['expiration_date']));
                                    $isUrgent = $med['display_status'] !== 'Healthy';
                                ?>
                                <tr data-medicine-id="<?= (int)$med['medicine_id'] ?>" data-category="<?= e($med['category']) ?>" data-status="<?= e($med['display_status']) ?>" data-expiry-status="<?= e($med['expiry_status'] ?? 'Safe') ?>">
                                    <td><strong><?= e($med['name']) ?></strong></td>
                                    <td><?= e($med['category']) ?></td>
                                    <td><?= (int)$med['quantity'] ?> <?= e($med['unit']) ?></td>
                                    <td><?= (int)$med['reorder_level'] ?></td>
                                    <td><?= e($expiryPretty) ?></td>
                                    <td><span class="status-pill <?= e($med['status_class']) ?>"><?= e($med['display_status']) ?></span></td>
                                    <td class="row-actions">
                                        <button class="row-action-btn <?= $isUrgent ? 'urgent' : '' ?>" data-action="restock"
                                            data-id="<?= (int)$med['medicine_id'] ?>"
                                            data-name="<?= e($med['name']) ?>"
                                            data-qty="<?= (int)$med['quantity'] ?>"
                                            data-unit="<?= e($med['unit']) ?>"
                                            type="button">Restock</button>
                                        <button class="row-action-btn secondary" data-action="edit"
                                            data-id="<?= (int)$med['medicine_id'] ?>" 
                                            data-name="<?= e($med['name']) ?>" 
                                            data-category="<?= e($med['category']) ?>"
                                            data-unit="<?= e($med['unit']) ?>" 
                                            data-qty="<?= (int)$med['quantity'] ?>" 
                                            data-reorder="<?= (int)$med['reorder_level'] ?>"
                                            data-expiry="<?= e($med['expiration_date']) ?>" 
                                            data-location="<?= e($med['location'] ?? '') ?>" 
                                            data-notes="<?= e($med['notes'] ?? '') ?>"
                                            type="button">Edit</button>
                                        <button class="row-action-btn secondary" data-action="delete"
                                            data-id="<?= (int)$med['medicine_id'] ?>"
                                            data-name="<?= e($med['name']) ?>"
                                            type="button" style="color:#dc2626;">Delete</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer" style="padding: 12px 20px; display:flex; justify-content:space-between; align-items:center; border-top: 1px solid var(--border-light);">
                        <span class="table-count" style="font-size:12.5px; color:var(--text-muted);">Showing <strong id="visibleCount"><?= count($medicines) ?></strong> medicines</span>
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

<?php include __DIR__ . '/../assets/modals/addMedicine.php'; ?>
<?php include __DIR__ . '/../assets/modals/editMedicine.php'; ?>
<?php include __DIR__ . '/../assets/modals/restockMedicine.php'; ?>
<?php include __DIR__ . '/../assets/popups/logout.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // ═══════════════════════════════════════
    //  UTILITY: AJAX POST helper
    // ═══════════════════════════════════════
    async function postAction(url, formData) {
        const resp = await fetch(url, { method: 'POST', body: formData });
        return resp.json();
    }

    // ═══════════════════════════════════════
    //  MODAL HELPERS
    // ═══════════════════════════════════════
    function openModal(id) { document.getElementById(id)?.classList.add('is-open'); }
    function closeAllModals() {
        document.querySelectorAll('.eh-modal').forEach(m => m.classList.remove('is-open'));
    }

    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', closeAllModals);
    });
    document.querySelectorAll('.eh-modal').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m) closeAllModals(); });
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeAllModals();
    });

    // ═══════════════════════════════════════
    //  ADD MEDICINE
    // ═══════════════════════════════════════
    document.getElementById('openAddMedModal')?.addEventListener('click', () => {
        openModal('addMedicineModal');
    });

    document.getElementById('addMedicineForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        const btn = document.querySelector('#addMedicineModal .eh-foot .module-btn:not(.secondary)');
        const origText = btn.innerHTML;

        if (!form.checkValidity()) { form.reportValidity(); return; }

        btn.disabled = true;
        btn.textContent = 'Adding…';

        try {
            const result = await postAction('../actions/addMedicine.php', new FormData(form));
            if (result.success) {
                closeAllModals();
                form.reset();
                location.reload();
            } else {
                alert(result.message);
            }
        } catch (err) {
            alert('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    });

    // ═══════════════════════════════════════
    //  EDIT MEDICINE
    // ═══════════════════════════════════════
    document.querySelectorAll('[data-action="edit"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            document.getElementById('editMedSubtitle').textContent = `Editing: ${d.name}`;
            document.getElementById('editMedId').value       = d.id;
            document.getElementById('editMedName').value     = d.name;
            document.getElementById('editMedQty').value      = d.qty;
            document.getElementById('editReorderLevel').value= d.reorder;
            document.getElementById('editLocation').value    = d.location;
            document.getElementById('editMedNotes').value    = d.notes;

            // Expiry: convert YYYY-MM-DD → YYYY-MM for month input
            if (d.expiry) {
                document.getElementById('editExpiryDate').value = d.expiry.substring(0, 7);
            }

            // Set select values
            const catSel  = document.getElementById('editMedCategory');
            const unitSel = document.getElementById('editMedUnit');
            [...catSel.options].forEach(o  => o.selected = o.text === d.category);
            [...unitSel.options].forEach(o => o.selected = o.text === d.unit);

            openModal('editMedicineModal');
        });
    });

    document.getElementById('editMedicineForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        const btn = document.querySelector('#editMedicineModal .eh-foot .module-btn:not(.secondary)');
        const origText = btn.innerHTML;

        if (!form.checkValidity()) { form.reportValidity(); return; }

        btn.disabled = true;
        btn.textContent = 'Saving…';

        try {
            const result = await postAction('../actions/updateMedicine.php', new FormData(form));
            if (result.success) {
                closeAllModals();
                location.reload();
            } else {
                alert(result.message);
            }
        } catch (err) {
            alert('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    });

    // ═══════════════════════════════════════
    //  RESTOCK MEDICINE
    // ═══════════════════════════════════════
    document.querySelectorAll('[data-action="restock"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset;
            document.getElementById('restockMedId').value = d.id;
            document.getElementById('restockMedName').value = d.name;
            document.getElementById('restockCurrentQty').value = `${d.qty} ${d.unit}`;
            document.getElementById('restockMedSubtitle').textContent = `Restock: ${d.name}`;
            document.getElementById('restockQtyInput').value = '';
            openModal('restockMedicineModal');
        });
    });

    document.getElementById('restockMedicineForm')?.addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        const btn = document.querySelector('#restockMedicineModal .eh-foot .module-btn:not(.secondary)');
        const origText = btn.innerHTML;

        if (!form.checkValidity()) { form.reportValidity(); return; }

        btn.disabled = true;
        btn.textContent = 'Restocking…';

        try {
            const result = await postAction('../actions/restockMedicine.php', new FormData(form));
            if (result.success) {
                closeAllModals();
                location.reload();
            } else {
                alert(result.message);
            }
        } catch (err) {
            alert('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    });

    // ═══════════════════════════════════════
    //  DELETE MEDICINE
    // ═══════════════════════════════════════
    document.querySelectorAll('[data-action="delete"]').forEach(btn => {
        btn.addEventListener('click', async () => {
            const name = btn.dataset.name;
            
            // Show custom confirmation
            showConfirmModal(`Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`, async () => {
                btn.disabled = true;
                btn.textContent = '…';

            try {
                const formData = new FormData();
                formData.append('medId', btn.dataset.id);
                const result = await postAction('../actions/deleteMedicine.php', formData);

                if (result.success) {
                    // Animate row removal
                    const row = btn.closest('tr');
                    row.style.transition = 'opacity 0.3s, transform 0.3s';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    setTimeout(() => { row.remove(); updateVisibleCount(); }, 300);
                } else {
                    alert(result.message);
                    btn.disabled = false;
                    btn.textContent = 'Delete';
                }
            } catch (err) {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Delete';
            }
        });
    });

    // ═══════════════════════════════════════
    //  SEARCH & FILTER + PAGINATION
    // ═══════════════════════════════════════
    const searchInput    = document.getElementById('medSearchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const stockFilter    = document.getElementById('stockFilter');
    const expiryFilter   = document.getElementById('expiryFilter');
    const btnPrev        = document.getElementById('btnPrev');
    const btnNext        = document.getElementById('btnNext');
    const pageInfo       = document.getElementById('pageInfo');

    let currentPage = 1;
    const pageSize = 8;
    let filteredRows = [];

    function filterTable() {
        const q      = (searchInput?.value || '').toLowerCase();
        const cat    = categoryFilter?.value || '';
        const stock  = stockFilter?.value || '';
        const expiry = expiryFilter?.value || '';
        const allRows = Array.from(document.querySelectorAll('#medTable tbody tr'));

        filteredRows = allRows.filter(row => {
            const name       = row.querySelector('td strong')?.textContent.toLowerCase() || '';
            const category   = row.dataset.category || '';
            const status     = row.dataset.status || '';
            const expiryStatus = row.dataset.expiryStatus || '';

            const matchQ     = !q || name.includes(q) || category.toLowerCase().includes(q);
            const matchCat   = !cat || category === cat;
            const matchSt    = !stock || status === stock;
            const matchExp   = !expiry || expiryStatus === expiry;

            return matchQ && matchCat && matchSt && matchExp;
        });

        currentPage = 1;
        renderPagination();
    }

    function renderPagination() {
        const totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
        if (currentPage > totalPages) currentPage = totalPages;

        const start = (currentPage - 1) * pageSize;
        const end   = start + pageSize;

        const allRows = document.querySelectorAll('#medTable tbody tr');
        allRows.forEach(r => r.style.display = 'none');
        filteredRows.slice(start, end).forEach(row => row.style.display = '');

        if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
        if (btnPrev)  btnPrev.disabled = (currentPage === 1);
        if (btnNext)  btnNext.disabled = (currentPage === totalPages);

        updateVisibleCount();
    }

    function updateVisibleCount() {
        const el = document.getElementById('visibleCount');
        if (el) el.textContent = filteredRows.length;
    }

    btnPrev?.addEventListener('click', () => {
        if (currentPage > 1) { currentPage--; renderPagination(); }
    });
    btnNext?.addEventListener('click', () => {
        const totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
        if (currentPage < totalPages) { currentPage++; renderPagination(); }
    });

    searchInput?.addEventListener('input', filterTable);
    categoryFilter?.addEventListener('change', filterTable);
    stockFilter?.addEventListener('change', filterTable);
    expiryFilter?.addEventListener('change', filterTable);

    // Initial render
    filterTable();

    function highlightElement(el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.style.transition = 'box-shadow 0.3s ease, background-color 0.3s ease';
        el.style.boxShadow = '0 0 0 3px rgba(91, 106, 240, 0.45)';
        el.style.backgroundColor = 'rgba(224, 231, 255, 0.55)';
        setTimeout(() => {
            el.style.boxShadow = '';
            el.style.backgroundColor = '';
        }, 2600);
    }

    function highlightFromQuery() {
        const params = new URLSearchParams(window.location.search);
        if (params.get('highlight') !== 'medicine') return;

        const medicineId = params.get('medicine_id');
        if (!medicineId) return;

        searchInput.value = '';
        if (categoryFilter) categoryFilter.value = '';
        if (stockFilter) stockFilter.value = '';
        if (expiryFilter) expiryFilter.value = '';
        filterTable();

        const allRows = Array.from(document.querySelectorAll('#medTable tbody tr'));
        const targetRow = allRows.find(r => (r.dataset.medicineId || '') === medicineId);
        if (!targetRow) return;

        const targetIndex = filteredRows.indexOf(targetRow);
        if (targetIndex >= 0) {
            currentPage = Math.floor(targetIndex / pageSize) + 1;
            renderPagination();
        }
        highlightElement(targetRow);
    }

    function showConfirmModal(message, callback) {
        const overlay = document.createElement('div');
        overlay.className = 'settings-modal-overlay';
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.4);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;opacity:0;pointer-events:none;transition:opacity 0.3s ease;';
        const modal = document.createElement('div');
        modal.className = 'settings-popup confirm';
        modal.style.cssText = 'background:white;width:100%;max-width:340px;padding:32px;border-radius:24px;text-align:center;transform:scale(0.9);transition:transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);box-shadow:0 20px 50px rgba(0,0,0,0.2);';
        modal.innerHTML = '<div style="width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:28px;font-weight:bold;background:#eff6ff;color:#4f46e5;">?</div><h3 style="margin-bottom:8px;font-size:20px;color:#111827;">Confirm</h3><p style="color:#6b7280;font-size:14px;margin-bottom:24px;line-height:1.5;">' + message + '</p><div style="display:flex;gap:10px;justify-content:center;"><button class="popup-cancel-btn" style="padding:12px 24px;border-radius:12px;border:none;background:#6b7280;color:white;font-weight:600;cursor:pointer;">Cancel</button><button class="popup-confirm-btn" style="padding:12px 24px;border-radius:12px;border:none;background:#4f46e5;color:white;font-weight:600;cursor:pointer;">Confirm</button></div>';
        document.body.appendChild(overlay);
        overlay.appendChild(modal);
        setTimeout(() => overlay.style.opacity = '1', 10);
        setTimeout(() => overlay.style.pointerEvents = 'auto', 10);
        setTimeout(() => modal.style.transform = 'scale(1)', 10);

        overlay.querySelector('.popup-cancel-btn').onclick = () => {
            overlay.style.opacity = '0';
            overlay.style.pointerEvents = 'none';
            modal.style.transform = 'scale(0.9)';
            setTimeout(() => overlay.remove(), 300);
        };

        overlay.querySelector('.popup-confirm-btn').onclick = () => {
            overlay.style.opacity = '0';
            overlay.style.pointerEvents = 'none';
            modal.style.transform = 'scale(0.9)';
            setTimeout(() => {
                overlay.remove();
                callback();
            }, 300);
        };
    }

    setTimeout(highlightFromQuery, 80);
});
</script>
<script src="../assets/js/popup.js" defer></script>
<script src="../assets/js/notifications.js" defer></script>
</body>
</html>
