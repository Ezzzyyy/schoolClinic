<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(1);

$pageTitle = 'Enrollment Health Assessment';
$activeModule = 'enrollment';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
	<link rel="stylesheet" href="../assets/css/dashboard.css" />
	<link rel="stylesheet" href="../assets/css/enrollment.css" />
</head>
<body>
<div class="app-shell">
	<?php include __DIR__ . '/../includes/sidebar.php'; ?>
	<div class="main-content">
		<?php include __DIR__ . '/../includes/header.php'; ?>
		<div class="dashboard-body module-page">
			<section class="module-toolbar">
				<div class="group"><svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"/></svg><input class="input" type="search" placeholder="Search student or enrollment batch" /></div>
				<div class="toolbar-actions">
					<select class="module-select"><option>All clearance</option><option>Cleared</option><option>Conditional</option><option>Pending</option></select>
					<button class="module-btn secondary" type="button">+ New Assessment</button>
				</div>
			</section>
			<section class="module-kpi-grid">
				<article class="module-kpi"><strong>36</strong><span>Assessed this week</span></article>
				<article class="module-kpi"><strong>11</strong><span>Pending labs</span></article>
				<article class="module-kpi"><strong>7</strong><span>Conditional</span></article>
				<article class="module-kpi"><strong>18</strong><span>Cleared</span></article>
			</section>
			<section class="module-layout">
				<article class="card">
					<div class="card-header"><div class="card-title">Enrollment Assessment Tracker</div></div>
					<div class="module-table-wrap">
						<table class="module-table">
							<thead><tr><th>Student ID</th><th>Vitals</th><th>Lab Status</th><th>Allergy Flag</th><th>Clearance</th><th>Updated</th></tr></thead>
							<tbody>
								<tr><td class="mono">2024-1001</td><td>120/80, 58kg</td><td>Complete</td><td>Amoxicillin</td><td><span class="status-pill ok">Cleared</span></td><td>Apr 15</td></tr>
								<tr><td class="mono">2024-1002</td><td>110/70, 49kg</td><td>Missing Drug Test</td><td>None</td><td><span class="status-pill pending">Pending</span></td><td>Apr 14</td></tr>
								<tr><td class="mono">2023-0911</td><td>118/75, 66kg</td><td>Complete</td><td>Asthma</td><td><span class="status-pill warn">Conditional</span></td><td>Apr 14</td></tr>
							</tbody>
						</table>
					</div>
				</article>
				<article class="card">
					<div class="card-header"><div class="card-title">Assessment Input</div></div>
					<div class="form-grid">
						<label class="field"><span>Student ID</span><input type="text" placeholder="2024-1001" /></label>
						<label class="field"><span>Date</span><input type="date" /></label>
						<label class="field"><span>Height</span><input type="text" placeholder="170 cm" /></label>
						<label class="field"><span>Weight</span><input type="text" placeholder="58 kg" /></label>
						<label class="field"><span>Blood Pressure</span><input type="text" placeholder="120/80" /></label>
						<label class="field"><span>Pulse</span><input type="text" placeholder="74 bpm" /></label>
						<label class="field full"><span>Lab Results</span><textarea rows="2" placeholder="X-ray clear, urinalysis normal, drug test pending"></textarea></label>
						<label class="field full"><span>Clearance Decision</span><select><option>Cleared</option><option>Conditional</option><option>Pending</option></select></label>
					</div>
				</article>
			</section>
		</div>
	</div>
</div>
</body>
</html>

