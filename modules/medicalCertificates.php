<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Visit.php';

protectPage(1);

$pageTitle = 'Medical Certificates';
$activeModule = 'medicalCertificates';

$db = new Database();
$conn = $db->connect();
$visitModel = new Visit($conn);

// Get certificate queue data
$certificates = $visitModel->getCertificates();
$stats = $visitModel->getCertificateStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
	<link rel="stylesheet" href="../assets/css/dashboard.css" />
	<link rel="stylesheet" href="../assets/css/medicalCertificates.css" />
	<link rel="stylesheet" href="../assets/css/notifications_popup.css" />
	<style>
		.notif-highlight {
			transition: box-shadow 0.3s ease, background-color 0.3s ease;
			box-shadow: 0 0 0 3px rgba(91, 106, 240, 0.45);
			background-color: rgba(224, 231, 255, 0.35);
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
				<div class="group">
					<svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"/></svg>
					<input class="input" type="search" placeholder="Search certificate number or student" />
				</div>
				<div class="toolbar-actions">
					<select class="module-select" aria-label="Issue status">
						<option>All statuses</option>
						<option>Draft</option>
						<option>Ready to release</option>
						<option>Released</option>
					</select>
				</div>
			</section>

			<section class="module-kpi-grid">
				<article class="module-kpi"><strong><?= $stats['issued_today'] ?></strong><span>Issued today</span></article>
				<article class="module-kpi"><strong><?= $stats['pending_total'] ?></strong><span>For signature</span></article>
				<article class="module-kpi"><strong><?= $stats['released_total'] ?></strong><span>Released</span></article>
				<article class="module-kpi"><strong><?= $stats['released_today'] ?></strong><span>Released today</span></article>
			</section>

			<section class="module-layout">
				<article class="card" id="certificateQueueCard">
					<div class="card-header"><div class="card-title">Certificate Queue</div></div>
					<div class="module-table-wrap">
						<table class="module-table">
							<thead>
								<tr>
									<th>Certificate No.</th>
									<th>Student</th>
									<th>Visit Date</th>
									<th>Type</th>
									<th>Issued By</th>
									<th>Status</th>
									<th>Action</th>
								</tr>
							</thead>
							<tbody>
								<?php if (empty($certificates)): ?>
									<tr><td colspan="7" style="text-align:center; padding:20px; color:#6b7280;">No pending certificates in queue.</td></tr>
								<?php else: ?>
									<?php foreach ($certificates as $cert): ?>
										<tr>
											<td class="mono">MC-<?= $cert['certificate_id'] ?></td>
											<td><?= htmlspecialchars($cert['student_first'] . ' ' . $cert['student_last']) ?></td>
											<td><?= date('M d, Y', strtotime($cert['visit_date'])) ?></td>
											<td><?= htmlspecialchars($cert['cert_type']) ?></td>
											<td><?= htmlspecialchars($cert['handler_first'] . ' ' . $cert['handler_last']) ?></td>
											<td><span class="status-pill pending">Pending</span></td>
											<td>
												<button class="module-btn secondary" style="padding:4px 8px; font-size:12px;" onclick="releaseCertificate(<?= $cert['certificate_id'] ?>)">Release</button>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</article>

				<article class="card" id="composeCertificateCard">
					<div class="card-header"><div class="card-title">Compose Certificate</div></div>
					<form id="composeCertificateForm" class="form-grid">
						<label class="field"><span>Student ID</span><input type="text" name="student_id" placeholder="2024-1001" required /></label>
						<label class="field"><span>Visit Date</span><input type="date" name="visit_date" required /></label>
						<label class="field full"><span>Diagnosis Summary</span><textarea name="diagnosis" rows="2" placeholder="Acute upper respiratory irritation" required></textarea></label>
						<label class="field full"><span>Recommendations</span><textarea name="recommendations" rows="3" placeholder="Allowed to attend classes with hydration and medication schedule."></textarea></label>
						<label class="field"><span>Restrictions</span><input type="text" name="restrictions" placeholder="No PE for 3 days" /></label>
						<label class="field"><span>Issuing Doctor</span><input type="text" name="issuing_doctor" placeholder="Dr. Reyes" required /></label>
						<label class="field"><span>Template</span>
							<select name="template">
								<option value="A">Template A</option>
								<option value="B">Template B</option>
								<option value="Conditional">Conditional</option>
							</select>
						</label>
						<div style="margin-top:12px;">
							<button type="submit" class="module-btn">Create Certificate</button>
						</div>
					</form>
				</article>
			</section>
		</div>
	</div>
</div>

<!-- Confirmation Modal -->
<div class="modal-backdrop" id="confirmModal" role="dialog" aria-modal="true" aria-label="Confirm action">
    <div class="modal-panel" style="max-width: 400px;">
        <div class="modal-header">
            <h2 class="modal-student-name">Confirm Release</h2>
            <button class="modal-close-btn" type="button" id="closeConfirmModal" aria-label="Close modal">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l10 10M13 3L3 13"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <p id="confirmMessage" style="color: var(--text-main); font-size: 14px; line-height: 1.5;">Are you sure you want to release this certificate?</p>
        </div>
        <div class="modal-footer">
            <div class="modal-footer-actions">
                <button class="toolbar-button secondary" type="button" id="cancelConfirm">Cancel</button>
                <button class="toolbar-button" type="button" id="confirmAction">Confirm</button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="../assets/css/modal.css" />
<style>
.toast {
	position: fixed;
	top: 20px;
	right: 20px;
	padding: 12px 20px;
	border-radius: 8px;
	color: white;
	font-size: 14px;
	font-weight: 500;
	z-index: 2000;
	animation: slideIn 0.3s ease;
	box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.toast.success { background: #10b981; }
.toast.error { background: #ef4444; }
@keyframes slideIn {
	from { transform: translateX(100%); opacity: 0; }
	to { transform: translateX(0); opacity: 1; }
}
</style>

<script>
let pendingReleaseId = null;
const confirmModal = document.getElementById('confirmModal');
const confirmMessage = document.getElementById('confirmMessage');
const confirmActionBtn = document.getElementById('confirmAction');
const cancelConfirmBtn = document.getElementById('cancelConfirm');
const closeConfirmModalBtn = document.getElementById('closeConfirmModal');

function showToast(message, type = 'success') {
	const toast = document.createElement('div');
	toast.className = `toast ${type}`;
	toast.textContent = message;
	document.body.appendChild(toast);
	setTimeout(() => {
		toast.style.opacity = '0';
		setTimeout(() => toast.remove(), 300);
	}, 3000);
}

function showConfirmModal(message, callback) {
	confirmMessage.textContent = message;
	confirmModal.classList.add('is-open');
	document.body.classList.add('modal-open');
	pendingReleaseId = callback;
}

function hideConfirmModal() {
	confirmModal.classList.remove('is-open');
	document.body.classList.remove('modal-open');
	pendingReleaseId = null;
}

cancelConfirmBtn?.addEventListener('click', hideConfirmModal);
closeConfirmModalBtn?.addEventListener('click', hideConfirmModal);
confirmModal?.addEventListener('click', e => {
	if (e.target === confirmModal) hideConfirmModal();
});

confirmActionBtn?.addEventListener('click', () => {
	if (pendingReleaseId) {
		pendingReleaseId();
	}
	hideConfirmModal();
});

function releaseCertificate(certId) {
	showConfirmModal('Are you sure you want to release this certificate?', () => {
		const formData = new FormData();
		formData.append('certId', certId);

		fetch('../actions/releaseCertificate.php', {
			method: 'POST',
			body: formData
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				showToast('Certificate released successfully!', 'success');
				setTimeout(() => location.reload(), 1500);
			} else {
				showToast('Error: ' + data.message, 'error');
			}
		})
		.catch(error => {
			showToast('Connection error. Please try again.', 'error');
		});
	});
}

document.addEventListener('DOMContentLoaded', () => {
	// Handle compose certificate form submission
	const composeForm = document.getElementById('composeCertificateForm');
	if (composeForm) {
		composeForm.addEventListener('submit', async (e) => {
			e.preventDefault();

			const formData = new FormData(composeForm);

			try {
				const response = await fetch('../actions/saveCertificate.php', {
					method: 'POST',
					body: formData
				});

				const data = await response.json();

				if (data.success) {
					showToast('Certificate created successfully!', 'success');
					composeForm.reset();
					setTimeout(() => location.reload(), 1500);
				} else {
					showToast('Error: ' + data.message, 'error');
				}
			} catch (error) {
				showToast('Connection error. Please try again.', 'error');
			}
		});
	}

	const params = new URLSearchParams(window.location.search);
	if (params.get('highlight') !== 'pending_certificates') return;

	const target = document.getElementById('certificateQueueCard');
	if (!target) return;

	target.scrollIntoView({ behavior: 'smooth', block: 'center' });
	target.classList.add('notif-highlight');
	setTimeout(() => target.classList.remove('notif-highlight'), 2600);
});
</script>
<script src="../assets/js/popup.js" defer></script>
<script src="../assets/js/notifications.js" defer></script>
</body>
</html>

