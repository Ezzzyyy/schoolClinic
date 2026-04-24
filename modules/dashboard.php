<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

// New Classes
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/Visit.php';
require_once __DIR__ . '/../classes/Medicine.php';

protectPage(1);

$pageTitle = 'Dashboard';
$activeModule = 'dashboard';

// Database connection
$db = new Database();
$conn = $db->connect();

// Instantiate Models
$studentModel = new Student($conn);
$visitModel   = new Visit($conn);
$medModel     = new Medicine($conn);

// 1. KPI Stats
$visitTrend   = $visitModel->getTodayTrend();
$studentTrend = $studentModel->getRegistrationTrend();
$totalStudentsCount = $studentTrend['count'];
$visitsTodayCount   = $visitTrend['count'];

$activeCount    = $studentModel->getActiveCount();
$lowStockCount  = $medModel->getLowStockCount();
$clearedPercent = $totalStudentsCount > 0 ? round(($activeCount / $totalStudentsCount) * 100) : 0;

// 2. Recent Clinic Visits (Top 5)
$recentVisits = $visitModel->getRecent(5);

// 3. Common Illnesses (Top 5)
$illnesses = $visitModel->getIllnessTrends(5);
$maxIllnessCount = count($illnesses) > 0 ? $illnesses[0]['count'] : 1;

// 4. Medicine Snapshot (Lowest 5)
$medsSnapshot = $medModel->getLowestStock(5);

// 5. Weekly KPI Snapshot
$weeklySnapshot = $visitModel->getWeeklySnapshot();

// 6. Enrollment Assessment Summary
$enrollmentSummary = $studentModel->getAssessmentSummary();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> — ClinIQ</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
</head>
<body>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <!-- ══════════════════════════════════════
       MAIN CONTENT
  ══════════════════════════════════════ -->
  <div class="main-content">

    <?php include __DIR__ . '/../includes/header.php'; ?>

    <!-- DASHBOARD BODY -->
    <div class="dashboard-body">
      <section class="dashboard-headliner" aria-label="Dashboard heading">
        <h1>Welcome back, <?= e($_SESSION['full_name'] ?? 'User') ?>!</h1>
        <p>Here is your clinic overview for today.</p>
      </section>

      <!-- ── KPI STATS ── -->
      <div class="stat-grid">

        <div class="stat-card blue-tint">
          <div class="stat-info">
            <div class="stat-label">Visits Today</div>
            <div class="stat-value"><?= number_format((float)$visitsTodayCount) ?></div>
            <div class="stat-change <?= $visitTrend['trend'] ?>"><?= e($visitTrend['label']) ?></div>
          </div>
          <div class="stat-icon blue">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 18s-7-4.5-7-9a7 7 0 0 1 14 0c0 4.5-7 9-7 9z"/></svg>
          </div>
        </div>

        <div class="stat-card green-tint">
          <div class="stat-info">
            <div class="stat-label">Students on File</div>
            <div class="stat-value"><?= number_format((float)$totalStudentsCount) ?></div>
            <div class="stat-change <?= $studentTrend['trend'] ?>"><?= e($studentTrend['label']) ?></div>
          </div>
          <div class="stat-icon green">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="10" cy="7" r="4"/><path d="M3 17c0-3.9 3.1-7 7-7s7 3.1 7 7"/></svg>
          </div>
        </div>

        <div class="stat-card red-tint">
          <div class="stat-info">
            <div class="stat-label">Low Stock Meds</div>
            <div class="stat-value"><?= (int)$lowStockCount ?></div>
            <div class="stat-change <?= $lowStockCount > 0 ? 'down' : 'neutral' ?>">
              <?= $lowStockCount > 0 ? '↓ attention needed' : 'all items stocked' ?>
            </div>
          </div>
          <div class="stat-icon red">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l2 4v8H2V8z"/><path d="M2 8h16M10 11v3M8 12h4"/></svg>
          </div>
        </div>

        <div class="stat-card amber-tint">
          <div class="stat-info">
            <div class="stat-label">Cleared for Enrollment</div>
            <div class="stat-value"><?= (int)$clearedPercent ?>%</div>
            <div class="stat-change up">↑ <?= (int)$activeCount ?> / <?= (int)$totalStudentsCount ?> active</div>
          </div>
          <div class="stat-icon amber">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 2l2 6h6l-5 4 2 6-5-4-5 4 2-6-5-4h6z"/></svg>
          </div>
        </div>

      </div>

      <!-- ── HEALTH KPIS ── -->
      <div class="card" style="margin-bottom: 14px;">
        <div class="card-header">
          <div class="card-title">Weekly KPI Snapshot</div>
          <div>
            <select class="dash-select"><option>Last 6 weeks</option></select>
          </div>
        </div>
        <table class="visit-table">
          <thead><tr><th>Week</th><th>Visits</th><th>Top Complaint</th><th>Follow-up Cases</th><th>Referral Rate</th></tr></thead>
          <tbody>
            <?php if (empty($weeklySnapshot)): ?>
              <tr><td colspan="5" style="text-align:center; padding: 20px;">No visit data available for the past month.</td></tr>
            <?php else: ?>
              <?php foreach ($weeklySnapshot as $wk): 
                $refRate = $wk['total_visits'] > 0 ? round(($wk['referrals'] / $wk['total_visits']) * 100) : 0;
              ?>
              <tr>
                <td>Starts <?= e($wk['week_start']) ?></td>
                <td><?= e((string)$wk['total_visits']) ?></td>
                <td><?= e($wk['top_complaint'] ?: 'None') ?></td>
                <td><?= e((string)$wk['follow_ups']) ?></td>
                <td><?= $refRate ?>%</td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- ── MID ROW: Recent Visits + Common Illnesses ── -->
      <div class="mid-row">

        <!-- Recent Visits -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Recent Clinic Visits</div>
            <a href="visitLogs.php" class="card-link">View All</a>
          </div>
          <table class="visit-table">
            <thead>
              <tr>
                <th>Student</th>
                <th>Complaint</th>
                <th>Time</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentVisits as $visit): 
                $initials = strtoupper($visit['first_name'][0] . $visit['last_name'][0]);
                $statusClass = strtolower($visit['status_name']);
                $timeFormatted = date('h:i A', strtotime((string)$visit['visit_date']));
              ?>
              <tr>
                <td>
                  <div class="student-cell">
                    <div class="student-avatar" style="--av-color:#5B6AF0"><?= e($initials) ?></div>
                    <div>
                      <div class="student-name"><?= e($visit['first_name'] . ' ' . $visit['last_name']) ?></div>
                      <div class="student-grade"><?= e($visit['course_name'] ?? 'Unlisted Course') ?></div>
                    </div>
                  </div>
                </td>
                <td><span class="complaint-text"><?= e($visit['complaint']) ?></span></td>
                <td><span class="visit-time"><?= e($timeFormatted) ?></span></td>
                <td><span class="badge <?= $statusClass ?>"><?= e($visit['status_name']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Common Illnesses -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Common Illnesses</div>
            <a href="reports.php" class="card-link">See Reports</a>
          </div>
          <div class="illness-list">
            <?php 
            $colors = ['#5B6AF0', '#34D399', '#FBBF24', '#A78BFA', '#F87171'];
            foreach ($illnesses as $idx => $ill): 
              $percent = round(($ill['count'] / $maxIllnessCount) * 100);
              $color = $colors[$idx % count($colors)];
            ?>
            <div class="illness-item">
              <div class="illness-color-dot" style="background:<?= $color ?>"></div>
              <span class="illness-name"><?= e($ill['complaint']) ?></span>
              <div class="illness-bar-wrap">
                <div class="illness-bar" style="width:<?= (int)$percent ?>%; background:<?= $color ?>"></div>
              </div>
              <span class="illness-count"><?= (int)$ill['count'] ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

      </div>

      <!-- ── BOTTOM ROW ── -->
      <div class="bottom-row">

        <!-- Medicine Inventory Snapshot -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Medicine Inventory</div>
            <a href="medicineInventory.php" class="card-link">View All</a>
          </div>
          <div>
            <?php foreach ($medsSnapshot as $med): 
              $isLow = $med['quantity'] <= $med['reorder_level'];
              $badgeColor = $isLow ? '#FEE2E2' : '#D1FAE5';
              $textColor = $isLow ? '#991B1B' : '#065F46';
            ?>
            <div class="med-row">
              <span class="med-name"><?= e($med['name']) ?></span>
              <span class="med-qty"><?= (int)$med['quantity'] ?> <?= e($med['unit']) ?></span>
              <span class="badge" style="background:<?= $badgeColor ?>; color:<?= $textColor ?>"><?= $isLow ? 'Low' : 'OK' ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Enrollment Health Assessment -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Enrollment Health Assessment</div>
          </div>
          <div class="enroll-grid">
            <div class="enroll-mini">
              <div class="enroll-num"><?= (int)$enrollmentSummary['cleared'] ?></div>
              <div class="enroll-lbl">Fully Cleared</div>
            </div>
            <div class="enroll-mini">
              <div class="enroll-num"><?= (int)$enrollmentSummary['conditional'] ?></div>
              <div class="enroll-lbl">Conditional Cert</div>
            </div>
            <div class="enroll-mini">
              <div class="enroll-num"><?= (int)$enrollmentSummary['pending'] ?></div>
              <div class="enroll-lbl">Pending Review</div>
            </div>
            <div class="enroll-mini">
              <div class="enroll-num"><?= (int)$enrollmentSummary['not_assessed'] ?></div>
              <div class="enroll-lbl">Not Yet Assessed</div>
            </div>
          </div>
        </div>

        <!-- Reminder Banner -->
        <div class="reminder-banner">
          <div class="reminder-text-wrap">
            <div class="reminder-eyebrow">Reminder</div>
            <div class="reminder-title">Log Today's<br>Remaining Visits</div>
          </div>
          <button class="reminder-btn" onclick="location.href='visitLogs.php'">Go to Visit Log</button>
        </div>

      </div>

    </div><!-- /dashboard-body -->
  </div><!-- /main-content -->
</div><!-- /app-shell -->

<?php include __DIR__ . '/../assets/popups/logout.php'; ?>
<script src="../assets/js/popup.js" defer></script>

</body>
</html>