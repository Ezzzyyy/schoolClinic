<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(1);

$pageTitle = 'Print Records';
$activeModule = 'prints';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
	<link rel="stylesheet" href="../assets/css/dashboard.css" />
	<link rel="stylesheet" href="../assets/css/prints.css" />
</head>
<body>
<div class="app-shell">
	<?php include __DIR__ . '/../includes/sidebar.php'; ?>
	<div class="main-content">
		<?php include __DIR__ . '/../includes/header.php'; ?>
		<div class="dashboard-body module-page">
			<section class="module-toolbar">
				<div class="toolbar-actions" style="margin-left:0;">
					<select class="module-select"><option>Visit Logs</option><option>Student Records</option><option>Inventory</option><option>Reports</option></select>
					<select class="module-select"><option>A4 Portrait</option><option>A4 Landscape</option><option>Legal</option></select>
					<button class="module-btn" type="button">Print Queue</button>
				</div>
			</section>
			<section class="module-kpi-grid">
				<article class="module-kpi"><strong>9</strong><span>Queued jobs</span></article>
				<article class="module-kpi"><strong>6</strong><span>Printed today</span></article>
				<article class="module-kpi"><strong>2</strong><span>Needs reprint</span></article>
				<article class="module-kpi"><strong>1</strong><span>Paused</span></article>
			</section>
			<section class="module-layout">
				<article class="card">
					<div class="card-header"><div class="card-title">Print Job Queue</div></div>
					<div class="module-table-wrap">
						<table class="module-table">
							<thead><tr><th>Job ID</th><th>Type</th><th>Record count</th><th>Requested by</th><th>Status</th></tr></thead>
							<tbody>
								<tr><td class="mono">PRT-091</td><td>Visit Logs</td><td>42</td><td>Ma. Reyes</td><td><span class="status-pill pending">Queued</span></td></tr>
								<tr><td class="mono">PRT-090</td><td>Medical Certificates</td><td>12</td><td>Dr. Lim</td><td><span class="status-pill ok">Printed</span></td></tr>
								<tr><td class="mono">PRT-089</td><td>Inventory Report</td><td>5</td><td>Ma. Reyes</td><td><span class="status-pill warn">Reprint</span></td></tr>
							</tbody>
						</table>
					</div>
				</article>
				<article class="card">
					<div class="card-header"><div class="card-title">Preview Checklist</div></div>
					<ul class="module-list">
						<li>Confirm page orientation and margins.</li>
						<li>Validate header and signature blocks.</li>
						<li>Check grouped order before batch print.</li>
					</ul>
				</article>
			</section>
		</div>
	</div>
</div>
</body>
</html>

