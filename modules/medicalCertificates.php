<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(1);

$pageTitle = 'Medical Certificates';
$activeModule = 'medicalCertificates';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
	<link rel="stylesheet" href="../assets/css/dashboard.css" />
	<link rel="stylesheet" href="../assets/css/medicalCertificates.css" />
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
					<button class="module-btn secondary" type="button">+ New Certificate</button>
				</div>
			</section>

			<section class="module-kpi-grid">
				<article class="module-kpi"><strong>12</strong><span>Issued today</span></article>
				<article class="module-kpi"><strong>4</strong><span>For signature</span></article>
				<article class="module-kpi"><strong>9</strong><span>Ready release</span></article>
				<article class="module-kpi"><strong>3</strong><span>With restrictions</span></article>
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
								</tr>
							</thead>
							<tbody>
								<tr><td class="mono">MC-2026-0415</td><td>Juan Dela Cruz</td><td>Apr 15, 2026</td><td>Medical Clearance</td><td>Dr. Reyes</td><td><span class="status-pill ok">Released</span></td></tr>
								<tr><td class="mono">MC-2026-0414</td><td>Ana Lim</td><td>Apr 14, 2026</td><td>Fit to Return</td><td>Dr. Lim</td><td><span class="status-pill pending">For Signature</span></td></tr>
								<tr><td class="mono">MC-2026-0413</td><td>Ramon Santos</td><td>Apr 13, 2026</td><td>Medical Clearance</td><td>Dr. Reyes</td><td><span class="status-pill warn">Draft</span></td></tr>
								<tr><td class="mono">MC-2026-0412</td><td>Maria Cruz</td><td>Apr 12, 2026</td><td>Conditional Clearance</td><td>Dr. Lim</td><td><span class="status-pill neutral">Ready to Release</span></td></tr>
							</tbody>
						</table>
					</div>
				</article>

				<article class="card">
					<div class="card-header"><div class="card-title">Compose Certificate</div></div>
					<div class="form-grid">
						<label class="field"><span>Student ID</span><input type="text" placeholder="2024-1001" /></label>
						<label class="field"><span>Visit Date</span><input type="date" /></label>
						<label class="field full"><span>Diagnosis Summary</span><textarea rows="2" placeholder="Acute upper respiratory irritation"></textarea></label>
						<label class="field full"><span>Recommendations</span><textarea rows="3" placeholder="Allowed to attend classes with hydration and medication schedule."></textarea></label>
						<label class="field"><span>Restrictions</span><input type="text" placeholder="No PE for 3 days" /></label>
						<label class="field"><span>Issuing Doctor</span><input type="text" placeholder="Dr. Reyes" /></label>
					</div>
					<div class="module-badge-row" style="margin-top:12px;">
						<span class="module-badge">Template A</span>
						<span class="module-badge">Template B</span>
						<span class="module-badge">Conditional</span>
					</div>
				</article>
			</section>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
	const params = new URLSearchParams(window.location.search);
	if (params.get('highlight') !== 'pending_certificates') return;

	const target = document.getElementById('certificateQueueCard');
	if (!target) return;

	target.scrollIntoView({ behavior: 'smooth', block: 'center' });
	target.classList.add('notif-highlight');
	setTimeout(() => target.classList.remove('notif-highlight'), 2600);
});
</script>

</body>
</html>

