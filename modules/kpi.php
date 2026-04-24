<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(1);

$pageTitle = 'KPI Dashboard';
$activeModule = 'kpi';
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
	<link rel="stylesheet" href="../assets/css/dashboard.css" />
	<link rel="stylesheet" href="../assets/css/kpi.css" />
</head>
<body>
<div class="app-shell">
	<?php include __DIR__ . '/../includes/sidebar.php'; ?>
	<div class="main-content">
		<?php include __DIR__ . '/../includes/header.php'; ?>
		<div class="dashboard-body module-page">
			<section class="module-toolbar">
				<div class="toolbar-actions" style="margin-left:0;">
					<select class="module-select"><option>Last 7 days</option><option>Last 30 days</option><option>This semester</option></select>
					<select class="module-select"><option>All cohorts</option><option>Junior High</option><option>Senior High</option><option>College</option></select>
				</div>
			</section>

			<section class="module-kpi-grid">
				<article class="module-kpi"><strong>412</strong><span>Total visits</span></article>
				<article class="module-kpi"><strong>57</strong><span>Avg visits / week</span></article>
				<article class="module-kpi"><strong>24%</strong><span>Respiratory cases</span></article>
				<article class="module-kpi"><strong>89%</strong><span>Resolved same day</span></article>
			</section>

			<section class="module-layout single">
				<article class="card">
					<div class="card-header"><div class="card-title">Weekly KPI Snapshot</div></div>
					<div class="module-table-wrap">
						<table class="module-table">
							<thead><tr><th>Week</th><th>Visits</th><th>Top Complaint</th><th>Follow-up Cases</th><th>Referral Rate</th></tr></thead>
							<tbody>
								<tr><td>Week 1</td><td>48</td><td>Headache</td><td>7</td><td>4%</td></tr>
								<tr><td>Week 2</td><td>53</td><td>Cough / Colds</td><td>5</td><td>6%</td></tr>
								<tr><td>Week 3</td><td>61</td><td>Abdominal pain</td><td>8</td><td>5%</td></tr>
								<tr><td>Week 4</td><td>57</td><td>Fever</td><td>6</td><td>4%</td></tr>
							</tbody>
						</table>
					</div>
				</article>
			</section>
		</div>
	</div>
</div>
</body>
</html>

