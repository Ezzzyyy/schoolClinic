<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';

protectPage(1);

$pageTitle = 'Student Records';
$activeModule = 'studentRecords';

// Database connection
$db = new Database();
$conn = $db->connect();

// Instantiate Model
$studentModel = new Student($conn);

// Fetch all students and stats
$students = $studentModel->getAll();
$stats    = $studentModel->getStats($students);

$totalCount    = $stats['total'];
$activeCount   = $stats['active'];
$pendingCount  = $stats['pending'];
$inactiveCount = $stats['inactive'];

// Fetch courses for filter
$courses = $studentModel->getAllCourses();

// Prepare JSON data for modal
$recordData = [];
foreach ($students as $row) {
    $fullName = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']);
    $recordData[$row['student_id']] = [
        'id'        => $row['student_number'],
        'name'      => $fullName,
        'initials'  => strtoupper($row['first_name'][0] . $row['last_name'][0]),
        'gender'    => $row['gender'],
        'age'       => Student::calculateAge($row['birth_date']),
        'birthDate' => $row['birth_date'] ? date('F j, Y', strtotime($row['birth_date'])) : '---',
        'address'   => $row['address'] ?? '---',
        'contact'   => $row['contact_number'] ?? '---',
        'emergency' => $row['emergency_contact'] ?? '---',
        'course'    => $row['course_name'] ?? 'No Course',
        'year'      => $row['year_name'] ?? 'Unknown Year',
        'status'    => $row['status'],
        'email'     => $row['email'] ?? '---',
        'healthNotes' => $row['health_notes'] ?? 'None provided.',
        'x_ray'      => $row['x_ray'],
        'urinalysis' => $row['urinalysis'],
        'hematology' => $row['hematology'],
        'drug_test'  => $row['drug_test'],
        'assessmentDate' => $row['assessment_date'] ? date('M d, Y', strtotime($row['assessment_date'])) : '---'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
  <link rel="stylesheet" href="../assets/css/studentRecords.css" />
  <link rel="stylesheet" href="../assets/css/modal.css" />
  <link rel="stylesheet" href="../assets/css/notifications_popup.css" />
  <script src="../assets/js/popup.js" defer></script>
  <script src="../assets/js/notifications.js" defer></script>
</head>
<body>

<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="dashboard-body records-page">

      <!-- Toolbar -->
      <section class="records-toolbar" aria-label="Records tools">
        <div class="toolbar-left">
          <div class="group toolbar-search">
            <svg class="icon" aria-hidden="true" viewBox="0 0 24 24">
              <path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"/>
            </svg>
            <input class="input" type="search" placeholder="Search name or student number…" id="searchInput" />
          </div>
        </div>

        <div class="toolbar-right">
          <select class="toolbar-select" aria-label="Course filter" id="courseFilter">
            <option value="">All courses</option>
            <?php foreach ($courses as $c): ?>
              <option value="<?= e($c['course_name']) ?>"><?= e($c['course_name']) ?></option>
            <?php endforeach; ?>
          </select>

          <select class="toolbar-select" aria-label="Status filter" id="statusFilter">
            <option value="">All statuses</option>
            <option>Active</option>
            <option>Pending review</option>
            <option>Inactive</option>
          </select>
        </div>
      </section>

      <!-- Stats strip -->
      <div class="records-stats-strip">
        <div class="stat-pill">
          <span class="stat-pill-num" id="totalCount"><?= $totalCount ?></span>
          <span class="stat-pill-label">Total students</span>
        </div>
        <div class="stat-pill accent-green">
          <span class="stat-pill-num"><?= $activeCount ?></span>
          <span class="stat-pill-label">Active</span>
        </div>
        <div class="stat-pill accent-yellow">
          <span class="stat-pill-num"><?= $pendingCount ?></span>
          <span class="stat-pill-label">Pending</span>
        </div>
        <div class="stat-pill accent-red">
          <span class="stat-pill-num"><?= $inactiveCount ?></span>
          <span class="stat-pill-label">Inactive</span>
        </div>
      </div>

      <!-- Table -->
      <article class="card records-table-card">
        <div class="records-table-wrap">
          <table class="records-table" id="recordsTable">
            <thead>
              <tr>
                <th>Student</th>
                <th>Student No.</th>
                <th>Course &amp; Year</th>
                <th>Status</th>
                <th>Health Assessment</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($students as $row): 
                $statusClass = strtolower(str_replace(' ', '-', $row['status']));
                $initials = strtoupper($row['first_name'][0] . $row['last_name'][0]);
              ?>
              <tr data-student-id="<?= (int)$row['student_id'] ?>">
                <td>
                  <div class="student-cell">
                    <div class="student-avatar" style="--av-color: #6366f1"><?= e($initials) ?></div>
                    <div>
                      <div class="student-name"><?= e($row['first_name'] . ' ' . $row['last_name']) ?></div>
                      <div class="student-sub"><?= e($row['gender']) ?> · <?= Student::calculateAge($row['birth_date']) ?> yrs</div>
                    </div>
                  </div>
                </td>
                <td class="mono"><?= e($row['student_number']) ?></td>
                <td><?= e($row['course_name'] ?? 'No Course') ?> · <?= e($row['year_name'] ?? 'Unknown Year') ?></td>
                <td><span class="badge <?= $statusClass ?>"><?= e($row['status']) ?></span></td>
                <td>
                  <div class="assessment-badges">
                    <span class="doc-badge <?= $row['x_ray'] ? 'ok' : 'missing' ?>" title="<?= $row['x_ray'] ? 'X-ray submitted' : 'X-ray missing' ?>">XR</span>
                    <span class="doc-badge <?= $row['urinalysis'] ? 'ok' : 'missing' ?>" title="<?= $row['urinalysis'] ? 'Urinalysis submitted' : 'Urinalysis missing' ?>">UA</span>
                    <span class="doc-badge <?= $row['hematology'] ? 'ok' : 'missing' ?>" title="<?= $row['hematology'] ? 'Hematology submitted' : 'Hematology missing' ?>">HEM</span>
                    <span class="doc-badge <?= $row['drug_test'] ? 'ok' : 'missing' ?>" title="<?= $row['drug_test'] ? 'Drug test submitted' : 'Drug test missing' ?>">DT</span>
                  </div>
                </td>
                <td>
                  <button class="review-btn" type="button" onclick="openModal(<?= (int)$row['student_id'] ?>)">
                    Review
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 8h10M9 5l3 3-3 3"/></svg>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="table-footer">
          <span class="table-count">Showing <strong><?= $totalCount ?></strong> students</span>
          <div class="pagination">
            <button class="page-btn" id="btnPrev" disabled>
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 4L6 8l4 4"/></svg>
            </button>
            <span class="page-current" id="pageInfo">Page 1 of 1</span>
            <button class="page-btn" id="btnNext" disabled>
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 4l4 4-4 4"/></svg>
            </button>
          </div>
        </div>
      </article>

    </div>
  </div>
</div>

<!-- Student Review Modal -->
<?php include __DIR__ . '/../assets/modals/viewStudent.php'; ?>

<script>
const studentData = <?= json_encode($recordData) ?>;

let currentStudentId = null;

function openModal(studentId) {
  currentStudentId = studentId;
  const s = studentData[studentId];
  if (!s) return;

  const modal = document.getElementById('studentRecordModal');
  if (!modal) return;

  // Clear previous remarks
  const remarksField = document.getElementById('modalRemarks');
  if (remarksField) remarksField.value = '';

  // Header and Identity
  document.getElementById('modalAvatar').textContent = s.initials;
  document.getElementById('modalStudentName').textContent = s.name;
  document.getElementById('modalStudentMeta').textContent = `${s.id} · ${s.course} / ${s.year} · ${s.gender} · ${s.age} yrs`;

  // Profile fields
  document.getElementById('modalFullName').textContent = s.name;
  document.getElementById('modalStudentNo').textContent = s.id;
  document.getElementById('modalGender').textContent = s.gender;
  document.getElementById('modalBirthDate').textContent = s.birthDate;
  document.getElementById('modalAddress').textContent = s.address;
  document.getElementById('modalEmail').textContent = s.email;
  document.getElementById('modalContactNo').textContent = s.contact;
  document.getElementById('modalEmergencyContact').textContent = s.emergency;
  document.getElementById('modalCourse').textContent = s.course;
  document.getElementById('modalYear').textContent = s.year;
  document.getElementById('modalHealthNotes').textContent = s.healthNotes;

  // Health Assessments
  const container = document.getElementById('assessmentContainer');
  container.innerHTML = '';
  
  const docs = [
    { name: 'X-ray', key: 'x_ray' },
    { name: 'Urinalysis', key: 'urinalysis' },
    { name: 'Hematology', key: 'hematology' },
    { name: 'Drug Test', key: 'drug_test' }
  ];

  docs.forEach(doc => {
    const file = s[doc.key];
    const status = file ? 'submitted' : 'missing';
    const statusText = file ? 'Submitted' : 'Missing';
    const meta = file ? `Submitted ${s.assessmentDate} · ${file.split('/').pop()}` : 'Not yet submitted';
    
    container.innerHTML += `
      <div class="assessment-item ${status}">
        <div class="assessment-item-left">
          <div class="assessment-doc-icon ${status}">
            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="2" width="10" height="12" rx="1.2"/><path d="M6 6h4M6 9h4M6 12h2"/></svg>
          </div>
          <div>
            <div class="assessment-doc-name">${doc.name}</div>
            <div class="assessment-doc-meta">${meta}</div>
          </div>
        </div>
        <div class="assessment-item-right">
          <span class="doc-status ${status}">${statusText}</span>
          ${file ? `<button class="view-doc-btn" type="button" onclick="window.open('../${file}')">View PDF</button>` : ''}
        </div>
      </div>
    `;
  });

  modal.classList.add('is-open');
  document.body.classList.add('modal-open');
}

function closeModal() {
  const modal = document.getElementById('studentRecordModal');
  if (modal) {
    modal.classList.remove('is-open');
    document.body.classList.remove('modal-open');
  }
}

// Close on backdrop click
document.addEventListener('DOMContentLoaded', () => {
  const backdrop = document.querySelector('.modal-backdrop');
  if (backdrop) {
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) closeModal();
    });
  }

  // Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeModal();
  });

  // Approval Actions logic
  async function handleStatusUpdate(dbStatus) {
    if (!currentStudentId) return;

    const remarks = document.getElementById('modalRemarks')?.value || '';
    const btnContainer = document.querySelector('.approval-actions');
    
    // Simple loading state
    const originalContent = btnContainer.innerHTML;
    btnContainer.innerHTML = '<p style="padding:10px; color:#6366f1; font-weight:500;">Updating status...</p>';

    try {
      const formData = new FormData();
      formData.append('studentId', currentStudentId);
      formData.append('status', dbStatus);
      formData.append('remarks', remarks);

      const response = await fetch('../actions/updateStudentStatus.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();
      if (result.success) {
        // For simplicity, reload to reflect changes in table and stats
        location.reload();
      } else {
        alert('Error: ' + result.message);
        btnContainer.innerHTML = originalContent;
      }
    } catch (err) {
      alert('Connection error. Please try again.');
      btnContainer.innerHTML = originalContent;
    }
  }

  document.getElementById('btnApprove')?.addEventListener('click', () => handleStatusUpdate('Active'));
  document.getElementById('btnConditional')?.addEventListener('click', () => handleStatusUpdate('Pending review'));
  document.getElementById('btnReject')?.addEventListener('click', () => handleStatusUpdate('Pending review'));

  // Live search filter
  const searchInput = document.getElementById('searchInput');
  const courseFilter = document.getElementById('courseFilter');
  const statusFilter = document.getElementById('statusFilter');
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const pageInfo = document.getElementById('pageInfo');

  let currentPage = 1;
  const pageSize = 6;
  let filteredRows = [];

  function filterTable() {
    const q = searchInput.value.toLowerCase();
    const course = courseFilter.value.toLowerCase();
    const status = statusFilter.value.toLowerCase();
    const allRows = Array.from(document.querySelectorAll('#recordsTable tbody tr'));

    // 1. Identify which rows match the filters
    filteredRows = allRows.filter(row => {
      const name = row.querySelector('.student-name')?.textContent.toLowerCase() || '';
      const num = row.querySelector('.mono')?.textContent.toLowerCase() || '';
      const courseCell = row.cells[2]?.textContent.toLowerCase() || '';
      const statusCell = row.querySelector('.badge')?.textContent.toLowerCase() || '';

      const matchQ = !q || name.includes(q) || num.includes(q);
      const matchCourse = !course || courseCell.includes(course);
      const matchStatus = !status || statusCell.includes(status);

      return matchQ && matchCourse && matchStatus;
    });

    // 2. Reset back to page 1 if filters changed
    // (A more advanced version would check if the current page still exists, 
    // but resetting is standard/safe)
    currentPage = 1; 
    renderPagination();
  }

  function renderPagination() {
    const totalPages = Math.ceil(filteredRows.length / pageSize) || 1;
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;

    // Hide all rows first
    const allRows = document.querySelectorAll('#recordsTable tbody tr');
    allRows.forEach(r => r.style.display = 'none');

    // Show only the slice for the current page
    filteredRows.slice(start, end).forEach(row => {
      row.style.display = '';
    });

    // Update UI
    pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    btnPrev.disabled = (currentPage === 1);
    btnNext.disabled = (currentPage === totalPages);

    // Update the "Showing X students" text if needed
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

  searchInput?.addEventListener('input', filterTable);
  courseFilter?.addEventListener('change', filterTable);
  statusFilter?.addEventListener('change', filterTable);

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
    
    // Handle search parameter from global search
    const searchParam = params.get('search');
    if (searchParam) {
      searchInput.value = searchParam;
      filterTable();
    }
    
    if (params.get('highlight') !== 'student') return;

    const studentId = params.get('student_id');
    if (!studentId) return;

    searchInput.value = '';
    if (courseFilter) courseFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    filterTable();

    const allRows = Array.from(document.querySelectorAll('#recordsTable tbody tr'));
    const targetRow = allRows.find(r => (r.dataset.studentId || '') === studentId);
    if (!targetRow) return;

    const targetIndex = filteredRows.indexOf(targetRow);
    if (targetIndex >= 0) {
      currentPage = Math.floor(targetIndex / pageSize) + 1;
      renderPagination();
    }

    highlightElement(targetRow);
  }

  setTimeout(highlightFromQuery, 80);
});
</script>

</body>
</html>