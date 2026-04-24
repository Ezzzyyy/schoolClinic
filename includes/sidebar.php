<?php
$activeModule = $activeModule ?? '';
if (!function_exists('navActive')) {
		function navActive(string $module, string $activeModule): string
		{
				return $module === $activeModule ? ' active' : '';
		}
}
?>
<aside class="sidebar">
	<a href="dashboard.php" class="sidebar-brand" aria-label="ClinIQ home">
		<svg class="brand-icon" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
			<rect width="36" height="36" rx="10" fill="#EEF0FD"/>
			<polyline points="5,18 11,18 14,10 18,26 22,13 25,18 31,18" fill="none" stroke="#5B6AF0" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
		<span class="brand-name">Clin<span class="iq">IQ</span></span>
	</a>

	<nav class="sidebar-nav" aria-label="Main navigation">
		<span class="nav-section-label">Main</span>
		<a href="dashboard.php" class="nav-item<?= navActive('dashboard', $activeModule) ?>">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="2" width="5" height="5" rx="1.2"/><rect x="9" y="2" width="5" height="5" rx="1.2"/><rect x="2" y="9" width="5" height="5" rx="1.2"/><rect x="9" y="9" width="5" height="5" rx="1.2"/></svg>
			Dashboard
		</a>
		<a href="studentRecords.php" class="nav-item<?= navActive('studentRecords', $activeModule) ?>">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="8" cy="5.5" r="2.8"/><path d="M2.5 13.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5"/></svg>
			Student Records
		</a>

		<span class="nav-section-label">Clinic</span>
		<a href="visitLogs.php" class="nav-item<?= navActive('visitLogs', $activeModule) ?>">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="3" width="12" height="10" rx="1.2"/><path d="M5 3V2M11 3V2M2 7h12"/></svg>
			Visit Log
		</a>
		<a href="enrollmentHealth.php" class="nav-item<?= navActive('enrollmentHealth', $activeModule) ?>">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 2h10l1 3v9H2V5z"/><path d="M2 5h12M8 8v4M6 10h4"/></svg>
			Health Clearance
		</a>
		<a href="clearedStudents.php" class="nav-item<?= navActive('clearedStudents', $activeModule) ?>">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M2 8l4 4 8-8"/></svg>
			Cleared Students
		</a>

		<span class="nav-section-label">Inventory &amp; Reports</span>
		<a href="medicineInventory.php" class="nav-item<?= navActive('medicineInventory', $activeModule) ?>">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M4 2h8l2 3v9H2V5z"/><path d="M2 5h12M7 8h2M8 7v2"/></svg>
			Medicine Inventory
		</a>
		<a href="reports.php" class="nav-item<?= navActive('reports', $activeModule) ?>">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="2" y="2" width="12" height="12" rx="1.2"/><path d="M5 6h6M5 9h6M5 12h3"/></svg>
			Reports
		</a>

		<div class="nav-divider"></div>
		<a href="settings.php" class="nav-item<?= navActive('settings', $activeModule) ?>">
			<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="8" cy="8" r="2.2"/><path d="M8 1.5v1.2M8 13.3v1.2M1.5 8h1.2M13.3 8h1.2M3.6 3.6l.85.85M11.55 11.55l.85.85M11.55 4.45l.85-.85M3.6 12.4l.85-.85"/></svg>
			Settings
		</a>

		<div class="sidebar-bottom">
			<a href="../logout.php" class="nav-item logout">
				<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 3H3v10h3M10 5l3 3-3 3M13 8H7"/></svg>
				Log out
			</a>
		</div>
	</nav>
</aside>
