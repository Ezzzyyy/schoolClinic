<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Settings.php';

protectPage(1);

$db = new Database();
$conn = $db->connect();
$settingsModel = new Settings($conn);
$config = $settingsModel->getAll();
$allowedTabs = ['clinic', 'requirements', 'users', 'system', 'audit'];
$initialTab = $_GET['tab'] ?? 'clinic';
if (!in_array($initialTab, $allowedTabs, true)) {
  $initialTab = 'clinic';
}

// Clean up old audit logs with invalid actions
$allowedActions = ['updated', 'exported', 'create', 'add', 'delete'];
$placeholders = implode(',', array_fill(0, count($allowedActions), '?'));
$query = "DELETE FROM audit_logs WHERE LOWER(action) NOT IN ($placeholders)";
$stmt = $conn->prepare($query);
$stmt->execute(array_map('strtolower', $allowedActions));

// Get available roles from schema
$availableRoles = ['nurse', 'doctor'];
$roleColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
if ($roleColumn) {
  $type = (string)($roleColumn['Type'] ?? '');
  if (preg_match('/^enum\((.*)\)$/i', $type, $matches)) {
    $enumVals = str_getcsv($matches[1], ',', "'");
    $availableRoles = array_map('strtolower', array_map('trim', $enumVals));
  }
}

$pageTitle = 'Settings';
$activeModule = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
  <link rel="stylesheet" href="../assets/css/dashboard.css" />
  <link rel="stylesheet" href="../assets/css/settings.css" />
  <link rel="stylesheet" href="../assets/css/notifications_popup.css" />
  <style>
  .settings-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; }
  .settings-modal-overlay.show { opacity: 1; pointer-events: auto; }
  .settings-popup { background: white; width: 100%; max-width: 340px; padding: 32px; border-radius: 24px; text-align: center; transform: scale(0.9); transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
  .settings-modal-overlay.show .settings-popup { transform: scale(1); }
  .popup-icon { width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 28px; font-weight: bold; }
  .settings-popup.success .popup-icon { background: #ecfdf5; color: #10b981; }
  .settings-popup.error .popup-icon { background: #fef2f2; color: #ef4444; }
  .settings-popup.confirm .popup-icon { background: #eff6ff; color: #4f46e5; }
  .settings-popup h3 { margin-bottom: 8px; font-size: 20px; color: #111827; }
  .settings-popup p { color: #6b7280; font-size: 14px; margin-bottom: 24px; line-height: 1.5; }
  .popup-close-btn { width: 100%; padding: 12px; border-radius: 12px; border: none; background: #1f2937; color: white; font-weight: 600; cursor: pointer; transition: background 0.2s; }
  .popup-close-btn:hover { background: #111827; }
  </style>
  <script>
  function showPopup(msg, type, callback) {
      var overlay = document.createElement('div');
      overlay.className = 'settings-modal-overlay';
      var modal = document.createElement('div');
      modal.className = 'settings-popup ' + type;
      
      if (type === 'confirm') {
          var icon = '?';
          var title = 'Confirm';
          modal.innerHTML = '<div class="popup-icon">' + icon + '</div><h3>' + title + '</h3><p>' + msg + '</p><div style="display:flex;gap:10px;justify-content:center;"><button class="popup-close-btn" type="button" style="background:#6b7280;">Cancel</button><button class="popup-close-btn" type="button" style="background:#4f46e5;">Confirm</button></div>';
          document.body.appendChild(overlay);
          overlay.appendChild(modal);
          setTimeout(function() { overlay.classList.add('show'); }, 10);
          
          var buttons = modal.querySelectorAll('.popup-close-btn');
          buttons[0].onclick = function() {
              overlay.classList.remove('show');
              setTimeout(function() { overlay.remove(); }, 300);
          };
          buttons[1].onclick = function() {
              overlay.classList.remove('show');
              setTimeout(function() { 
                  overlay.remove();
                  if (callback) callback();
              }, 300);
          };
      } else {
          var icon = (type === 'success') ? '✓' : '⚠';
          var title = (type === 'success') ? 'Success!' : 'Error';
          modal.innerHTML = '<div class="popup-icon">' + icon + '</div><h3>' + title + '</h3><p>' + msg + '</p><button class="popup-close-btn" type="button">Dismiss</button>';
          document.body.appendChild(overlay);
          overlay.appendChild(modal);
          setTimeout(function() { overlay.classList.add('show'); }, 10);
          modal.querySelector('.popup-close-btn').onclick = function() {
              overlay.classList.remove('show');
              setTimeout(function() { overlay.remove(); }, 300);
          };
      }
  }
  function switchTab(btn, tabId) {
      var tabs = document.querySelectorAll('.stab');
      var contents = document.querySelectorAll('.stab-content');
      for (var i = 0; i < tabs.length; i++) tabs[i].classList.remove('active');
      for (var j = 0; j < contents.length; j++) contents[j].classList.remove('active');
      btn.classList.add('active');
      var target = document.getElementById('tab-' + tabId);
      if (target) target.classList.add('active');

      // Load users when users tab is clicked
      if (tabId === 'users') {
          loadUsers();
      }
  }

  function loadUsers() {
      var tbody = document.getElementById('usersList');
      if (!tbody) return;

      tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 28px; color: #6b7280;">Loading users...</td></tr>';

      fetch('../actions/getUsers.php')
      .then(response => response.json())
      .then(data => {
          if (!data.success || !data.users || data.users.length === 0) {
              tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 28px; color: #6b7280;">No staff accounts found.</td></tr>';
              return;
          }

          tbody.innerHTML = data.users.map(u => `
              <tr>
                  <td><strong>${u.name}</strong></td>
                  <td class="mono" style="font-size:11.5px;">${u.email}</td>
                  <td>${u.role}</td>
                  <td>${u.last_login}</td>
                  <td>
                      <span class="status-pill ${u.status === 'active' ? 'ok' : (u.status === 'inactive' ? 'neutral' : 'warn')}">${u.status}</span>
                  </td>
                  <td class="row-actions">
                      <button
                          class="row-action-btn secondary"
                          type="button"
                          onclick="openUserEditModal(this)"
                          data-user-id="${u.user_id}"
                          data-user-name="${u.name}"
                          data-role="${u.role_raw}"
                          data-status="${u.status}"
                      >Edit</button>
                  </td>
              </tr>
          `).join('');
      })
      .catch(error => {
          tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 28px; color: #ef4444;">Error loading users. Please try again.</td></tr>';
      });
  }
  </script>
</head>
<body>
<div class="app-shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="dashboard-body settings-page">
      <div class="settings-tabs">
        <button class="stab active" data-tab="clinic" onclick="switchTab(this, 'clinic')">Clinic Setup</button>
        <button class="stab" data-tab="requirements" onclick="switchTab(this, 'requirements')">Assessment Requirements</button>
        <button class="stab" data-tab="users" onclick="switchTab(this, 'users')">User Access</button>
        <button class="stab" data-tab="system" onclick="switchTab(this, 'system')">System</button>
        <button class="stab" data-tab="audit" onclick="switchTab(this, 'audit')">Audit Logs</button>
      </div>

      <div class="stab-content active" id="tab-clinic">
        <form class="settings-layout" action="../actions/saveSettings.php" method="POST" id="clinicSetupForm">
          <input type="hidden" name="form_type" value="clinic_setup" />
          <section class="card settings-card">
            <div class="card-header"><div class="card-title">Clinic Identity</div></div>
            <div class="settings-form-grid">
              <label class="sf-field full"><span>Clinic / School Name</span><input type="text" name="clinic_name" value="<?= e($config['clinic_name'] ?? '') ?>" /></label>
              <label class="sf-field"><span>School Year</span><input type="text" name="school_year" value="<?= e($config['school_year'] ?? '') ?>" /></label>
              <label class="sf-field"><span>Clinic Contact Number</span><input type="tel" name="clinic_contact" value="<?= e($config['clinic_contact'] ?? '') ?>" /></label>
              <label class="sf-field full"><span>Clinic Address</span><input type="text" name="clinic_address" value="<?= e($config['clinic_address'] ?? '') ?>" /></label>
              <label class="sf-field full"><span>Document Footer Text</span><textarea name="document_footer" rows="2"><?= e($config['document_footer'] ?? '') ?></textarea></label>
            </div>
          </section>
          <section class="card settings-card">
            <div class="card-header"><div class="card-title">Signatories</div></div>
            <div class="settings-form-grid">
              <label class="sf-field"><span>Primary Physician</span><input type="text" name="primary_physician" value="<?= e($config['primary_physician'] ?? '') ?>" /></label>
              <label class="sf-field"><span>License No.</span><input type="text" name="physician_license" value="<?= e($config['physician_license'] ?? '') ?>" /></label>
              <label class="sf-field"><span>Head Nurse</span><input type="text" name="head_nurse" value="<?= e($config['head_nurse'] ?? '') ?>" /></label>
              <label class="sf-field"><span>License No.</span><input type="text" name="nurse_license" value="<?= e($config['nurse_license'] ?? '') ?>" /></label>
            </div>
            <div class="settings-foot">
              <button class="settings-btn" type="submit">Save Changes</button>
            </div>
          </section>
        </form>
      </div>

      <div class="stab-content" id="tab-requirements">
        <form class="card settings-card" action="../actions/saveSettings.php" method="POST">
          <input type="hidden" name="form_type" value="assessment_reqs" />
          <div class="card-header">
            <div class="card-title">Health Submission Requirements</div>
            <span class="card-link">Applied globally to all student submissions</span>
          </div>
          <p class="settings-desc">Toggle which documents students are required to upload when submitting health requirements. Changes take effect immediately for new submissions.</p>
          <div class="req-toggle-list">
            <?php
            $reqs = [
                ['key' => 'req_xray', 'label' => 'Chest X-ray', 'desc' => 'PA view, taken within the last 6 months'],
                ['key' => 'req_urinalysis', 'label' => 'Urinalysis', 'desc' => 'Complete urinalysis, within last 3 months'],
                ['key' => 'req_cbc', 'label' => 'Hematology / CBC', 'desc' => 'Complete blood count, within last 3 months'],
                ['key' => 'req_drug_test', 'label' => 'Drug Test', 'desc' => 'Standard 5-panel, certified testing center'],
                ['key' => 'req_med_cert', 'label' => 'Medical Certificate', 'desc' => 'Signed by a licensed physician'],
                ['key' => 'req_vaccination', 'label' => 'Vaccination Card', 'desc' => 'Latest immunization record']
            ];
            foreach ($reqs as $r):
                $isReq = ($config[$r['key']] ?? 'optional') === 'required';
                $isEnabled = ($config[$r['key'] . '_enabled'] ?? '1') === '1';
            ?>
            <div class="rtog-item">
              <div class="rtog-info">
                <strong><?= $r['label'] ?></strong>
                <span><?= $r['desc'] ?></span>
              </div>
              <div class="rtog-controls">
                <select class="rtog-select" name="<?= $r['key'] ?>">
                  <option value="required" <?= $isReq ? 'selected' : '' ?>>Required</option>
                  <option value="optional" <?= !$isReq ? 'selected' : '' ?>>Optional</option>
                </select>
                <label class="toggle-switch">
                  <input type="checkbox" name="<?= $r['key'] ?>_enabled" value="1" <?= $isEnabled ? 'checked' : '' ?> />
                  <span class="toggle-track"></span>
                </label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="settings-foot">
            <button class="settings-btn" type="submit">Save Requirements</button>
          </div>
        </form>
      </div>

      <div class="stab-content" id="tab-users">
        <div class="card settings-card">
          <div class="card-header">
            <div class="card-title">Clinic Staff Accounts</div>
            <button class="settings-btn small" type="button" onclick="openAddUserModal()">Manage Users</button>
          </div>
          <div class="module-table-wrap">
            <table class="module-table settings-table">
              <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead>
              <tbody id="usersList">
                <tr><td colspan="6" style="text-align: center; padding: 28px; color: #6b7280;">Loading users...</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div id="userEditModal" class="settings-modal-overlay" aria-hidden="true" style="display:none;">
          <div class="settings-popup" role="dialog" aria-modal="true" aria-labelledby="userEditModalTitle" style="max-width:420px; text-align:left;">
            <h3 id="userEditModalTitle" style="margin:0 0 8px 0; font-size:20px; color:#111827;">Edit User Access</h3>
            <p id="userEditModalName" style="margin:0 0 18px 0; color:#6b7280; font-size:14px;"></p>
            <form action="../actions/updateUserAccess.php" method="POST" id="userEditForm">
              <input type="hidden" name="user_id" id="editUserId" value="" />
              <div style="display:grid; gap:14px;">
                <label class="sf-field" style="display:block;">
                  <span style="display:block; margin-bottom:6px;">Role</span>
                  <select name="role" id="editUserRole">
                    <?php foreach ($availableRoles as $r): ?>
                    <option value="<?= e($r) ?>"><?= ucfirst(e($r)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label class="sf-field" style="display:block;">
                  <span style="display:block; margin-bottom:6px;">Status</span>
                  <select name="status" id="editUserStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="pending review">Pending review</option>
                  </select>
                </label>
              </div>
              <div class="settings-foot" style="margin-top:18px;">
                <button class="settings-btn" type="submit">Save User</button>
                <button class="settings-btn secondary" type="button" onclick="closeUserEditModal()">Cancel</button>
              </div>
            </form>
          </div>
        </div>

        <div id="addUserModal" class="settings-modal-overlay" aria-hidden="true" style="display:none;">
          <div class="settings-popup" role="dialog" aria-modal="true" aria-labelledby="addUserModalTitle" style="max-width:420px; text-align:left;">
            <h3 id="addUserModalTitle" style="margin:0 0 8px 0; font-size:20px; color:#111827;">Add New User</h3>
            <p style="margin:0 0 18px 0; color:#6b7280; font-size:14px;">Create a new clinic staff account</p>
            <form action="../actions/createUser.php" method="POST" id="addUserForm">
              <div style="display:grid; gap:14px;">
                <label class="sf-field" style="display:block;">
                  <span style="display:block; margin-bottom:6px;">First Name</span>
                  <input type="text" name="first_name" placeholder="First name" required style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px;" />
                </label>
                <label class="sf-field" style="display:block;">
                  <span style="display:block; margin-bottom:6px;">Last Name</span>
                  <input type="text" name="last_name" placeholder="Last name" required style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px;" />
                </label>
                <label class="sf-field" style="display:block;">
                  <span style="display:block; margin-bottom:6px;">Email</span>
                  <input type="email" name="email" placeholder="email@example.com" required style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px;" />
                </label>
                <label class="sf-field" style="display:block;">
                  <span style="display:block; margin-bottom:6px;">Username</span>
                  <input type="text" name="username" placeholder="username" required style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px;" />
                </label>
                <label class="sf-field" style="display:block;">
                  <span style="display:block; margin-bottom:6px;">Password</span>
                  <input type="password" name="password" placeholder="Temporary password" required style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px;" />
                </label>
                <label class="sf-field" style="display:block;">
                  <span style="display:block; margin-bottom:6px;">Role</span>
                  <select name="role" required style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:6px;">
                    <?php foreach ($availableRoles as $r): ?>
                    <option value="<?= e($r) ?>"><?= ucfirst(e($r)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
              </div>
              <div class="settings-foot" style="margin-top:18px;">
                <button class="settings-btn" type="submit">Create User</button>
                <button class="settings-btn secondary" type="button" onclick="closeAddUserModal()">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="stab-content" id="tab-system">
        <form class="settings-layout" action="../actions/saveSettings.php" method="POST" id="systemSettingsForm">
          <input type="hidden" name="form_type" value="system_settings" />
          <section class="card settings-card">
            <div class="card-header"><div class="card-title">Email Notifications</div></div>
            <div class="settings-form-grid">
              <label class="sf-field full"><span>Enable Email Notifications</span>
                <div class="settings-toggle-row">
                  <label class="toggle-switch">
                    <input type="checkbox" name="email_enabled" value="1" <?= ($config['email_enabled'] ?? '0') === '1' ? 'checked' : '' ?> />
                    <span class="toggle-track"></span>
                  </label>
                  <span class="settings-toggle-hint">Allow system to send notification emails</span>
                </div>
              </label>
              <label class="sf-field"><span>Email Username</span><input type="email" name="email_username" value="<?= e($config['email_username'] ?? '') ?>" placeholder="your_email@gmail.com" /></label>
              <label class="sf-field"><span>Email Password</span><input type="password" name="email_password" value="" placeholder="App password (not regular password)" /></label>
              <label class="sf-field full"><span>From Email Address</span><input type="email" name="email_from_address" value="<?= e($config['email_from_address'] ?? '') ?>" placeholder="clinic@school.edu.ph" /></label>
              <label class="sf-field full"><span>From Name</span><input type="text" name="email_from_name" value="<?= e($config['email_from_name'] ?? '') ?>" placeholder="ClinIQ School Clinic" /></label>
            </div>
            <div class="settings-foot">
              <button class="settings-btn" type="submit">Save Email Settings</button>
              <button class="settings-btn secondary" type="button" onclick="testEmailConnection(this)">Test Connection</button>
            </div>
          </section>
          <section class="card settings-card">
            <div class="card-header"><div class="card-title">Session &amp; Security</div></div>
            <div class="settings-form-grid">
              <label class="sf-field"><span>Session Timeout</span>
                <select name="session_timeout">
                  <option value="30" <?= ($config['session_timeout'] ?? '60') == '30' ? 'selected' : '' ?>>30 minutes</option>
                  <option value="60" <?= ($config['session_timeout'] ?? '60') == '60' ? 'selected' : '' ?>>60 minutes</option>
                  <option value="120" <?= ($config['session_timeout'] ?? '60') == '120' ? 'selected' : '' ?>>120 minutes</option>
                </select>
              </label>
              <label class="sf-field"><span>Max Login Attempts</span><input type="number" name="max_login_attempts" value="<?= e($config['max_login_attempts'] ?? '5') ?>" min="1" max="10" /></label>
              <label class="sf-field full"><span>2-Factor Auth</span>
                <div class="settings-toggle-row">
                  <label class="toggle-switch">
                    <input type="checkbox" name="two_factor_auth" value="enabled" <?= ($config['two_factor_auth'] ?? 'disabled') === 'enabled' ? 'checked' : '' ?> />
                    <span class="toggle-track"></span>
                  </label>
                  <span class="settings-toggle-hint">Enable to require OTP on each login</span>
                </div>
              </label>
            </div>
            <div class="settings-foot"><button class="settings-btn" type="submit">Save Security Settings</button></div>
          </section>
          <section class="card settings-card">
            <div class="card-header"><div class="card-title">Database &amp; Backup</div></div>
            <div class="sys-info-list">
              <div class="sys-info-row"><span>Last Backup</span><strong><?= e($config['last_backup_date'] ?? 'Never') ?></strong></div>
              <div class="sys-info-row"><span>Data Retention Period</span><strong><?= e($config['data_retention_days'] ?? '365') ?> days</strong></div>
              <div class="sys-info-row"><span>App Version</span><strong>ClinIQ v1.0.0</strong></div>
            </div>
            <div class="settings-form-grid">
              <label class="sf-field full"><span>Auto Backup</span>
                <div class="settings-toggle-row">
                  <label class="toggle-switch">
                    <input type="checkbox" name="backup_enabled" value="1" <?= ($config['backup_enabled'] ?? '1') === '1' ? 'checked' : '' ?> />
                    <span class="toggle-track"></span>
                  </label>
                  <span class="settings-toggle-hint">Enable automatic daily backups at midnight</span>
                </div>
              </label>
              <label class="sf-field"><span>Data Retention (days)</span>
                <input type="number" name="data_retention_days" value="<?= e($config['data_retention_days'] ?? '365') ?>" min="30" max="3650" />
                <span style="font-size:12px; color:#6b7280;">Automatically archive records older than this period</span>
              </label>
            </div>
            <div class="settings-foot">
              <button class="settings-btn" type="submit">Save Backup Settings</button>
              <button class="settings-btn secondary" type="button" onclick="triggerManualBackup(this)">Create Backup Now</button>
            </div>
          </section>
        </form>
      </div>

      <div class="stab-content" id="tab-audit">
        <div class="card settings-card">
          <div class="card-header">
            <div class="card-title">System Audit Logs</div>
            <span class="card-link">All user actions and system events</span>
          </div>
          <p class="settings-desc">View activity logs of all users in the system. This log tracks login attempts, data modifications, and important system actions for compliance and security purposes.</p>
          <div class="audit-filters">
            <input type="text" placeholder="Search logs..." id="auditSearch" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; width: 200px;" />
            <select id="auditFilter" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
              <option value="">All Actions</option>
              <option value="updated">Updated</option>
              <option value="exported">Exported</option>
              <option value="create">Create</option>
              <option value="add">Add</option>
              <option value="delete">Delete</option>
            </select>
          </div>
          <div class="module-table-wrap">
            <table class="module-table settings-table">
              <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>IP Address</th><th>Details</th></tr></thead>
              <tbody id="auditLogsList">
                <tr><td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">No audit logs found. Logs will appear here as users interact with the system.</td></tr>
              </tbody>
            </table>
            <div id="auditPagination"></div>
          </div>
          <div class="settings-foot">
            <button class="settings-btn secondary" type="button" onclick="exportAuditLogs()">Export Logs</button>
            <button class="settings-btn secondary" type="button" onclick="clearOldLogs()">Clear Old Logs</button>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['msg'])): ?>
        showPopup(<?= json_encode($_SESSION['msg']) ?>, <?= json_encode($_SESSION['msg_type'] ?? 'success') ?>);
        <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
    <?php endif; ?>

  var initialTab = <?= json_encode($initialTab) ?>;
  if (initialTab && initialTab !== 'clinic') {
    var tabBtn = document.querySelector('.settings-tabs .stab[data-tab="' + initialTab + '"]');
    if (tabBtn) switchTab(tabBtn, initialTab);
  }
});

function testEmailConnection(button) {
  var form = document.getElementById('systemSettingsForm');
    if (!form) return;
    var formData = new FormData(form);
    formData.set('form_type', 'test_email');
    button.disabled = true;
    button.textContent = 'Testing...';
    fetch('../actions/saveSettings.php', { method: 'POST', body: formData })
    .then(response => response.json())
    .then(data => {
        button.disabled = false;
        button.textContent = 'Test Connection';
        showPopup(data.success ? 'Email configuration is valid!' : (data.message || 'Email test failed. Check your settings.'), data.success ? 'success' : 'error');
    })
    .catch(error => {
        button.disabled = false;
        button.textContent = 'Test Connection';
        showPopup('Connection test error: ' + error.message, 'error');
    });
}

function triggerManualBackup(button) {
    showPopup('Create a database backup now? This may take a moment.', 'confirm', () => {
        button.disabled = true;
        button.textContent = 'Creating backup...';
        fetch('../actions/saveSettings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'form_type=manual_backup'
        })
        .then(response => response.json())
        .then(data => {
            button.disabled = false;
            button.textContent = 'Create Backup Now';
            if (data.success) {
                showPopup('Backup created successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showPopup(data.message || 'Backup failed. Please try again.', 'error');
            }
        })
        .catch(error => {
            button.disabled = false;
            button.textContent = 'Create Backup Now';
            showPopup('Backup error: ' + error.message, 'error');
        });
    });
}

let currentAuditPage = 1;

function loadAuditLogs(filter = '', search = '', page = 1) {
    currentAuditPage = page;
    fetch('../actions/getAuditLogs.php?filter=' + encodeURIComponent(filter) + '&search=' + encodeURIComponent(search) + '&page=' + page)
    .then(response => response.json())
    .then(data => {
        var tbody = document.getElementById('auditLogsList');
        if (!tbody) return;
        if (!data.logs || data.logs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">No logs found.</td></tr>';
            updatePaginationControls(data.pagination);
            return;
        }
        tbody.innerHTML = data.logs.map(log => `
            <tr>
                <td style="font-size: 12px;">${log.timestamp}</td>
                <td>${log.user_name}</td>
                <td><span style="background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 4px; font-size: 12px;">${log.action}</span></td>
                <td>${log.entity_type || '—'}</td>
                <td style="font-size: 12px; color: #6b7280;">${log.ip_address}</td>
                <td>${log.description || '—'}</td>
            </tr>
        `).join('');
        updatePaginationControls(data.pagination);
    });
}

function updatePaginationControls(pagination) {
    var container = document.getElementById('auditPagination');
    if (!container) return;
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = `
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0;">
            <span style="font-size: 12px; color: #6b7280;">Showing page ${pagination.page} of ${pagination.total_pages} (${pagination.total} total)</span>
            <div style="display: flex; gap: 8px;">
                <button onclick="loadAuditLogs(document.getElementById('auditFilter').value, document.getElementById('auditSearch').value, ${pagination.page - 1})" 
                        ${pagination.has_prev ? '' : 'disabled'} 
                        style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; ${pagination.has_prev ? '' : 'opacity: 0.5; cursor: not-allowed;'}">
                    Previous
                </button>
                <button onclick="loadAuditLogs(document.getElementById('auditFilter').value, document.getElementById('auditSearch').value, ${pagination.page + 1})" 
                        ${pagination.has_next ? '' : 'disabled'} 
                        style="padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; cursor: pointer; ${pagination.has_next ? '' : 'opacity: 0.5; cursor: not-allowed;'}">
                    Next
                </button>
            </div>
        </div>
    `;
}

function exportAuditLogs() { window.location.href = '../actions/exportAuditLogs.php'; }

function clearOldLogs() {
    showPopup('Clear audit logs older than 90 days? This cannot be undone.', 'confirm', () => {
        fetch('../actions/saveSettings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'form_type=clear_old_logs'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPopup('Old logs cleared successfully!', 'success');
            } else {
                showPopup(data.message || 'Failed to clear logs.', 'error');
            }
        })
        .catch(error => {
            showPopup('Error clearing logs: ' + error.message, 'error');
        });
    });
}

function openUserEditModal(button) {
    var modal = document.getElementById('userEditModal');
    var userId = document.getElementById('editUserId');
    var userName = document.getElementById('userEditModalName');
    var userRole = document.getElementById('editUserRole');
    var userStatus = document.getElementById('editUserStatus');
    if (!modal || !userId || !userName || !userRole || !userStatus) return;

    userId.value = button.getAttribute('data-user-id') || '';
    userName.textContent = button.getAttribute('data-user-name') || 'User';
    userRole.value = (button.getAttribute('data-role') || 'nurse').toLowerCase();
    userStatus.value = (button.getAttribute('data-status') || 'active').toLowerCase();

    modal.style.display = 'flex';
    setTimeout(function() { modal.classList.add('show'); }, 10);
}

function closeUserEditModal() {
    var modal = document.getElementById('userEditModal');
    if (!modal) return;
    modal.classList.remove('show');
    setTimeout(function() { modal.style.display = 'none'; }, 220);
    loadUsers();
}

function openAddUserModal() {
    var modal = document.getElementById('addUserModal');
    if (!modal) return;
    document.getElementById('addUserForm').reset();
    modal.style.display = 'flex';
    setTimeout(function() { modal.classList.add('show'); }, 10);
}

function closeAddUserModal() {
    var modal = document.getElementById('addUserModal');
    if (!modal) return;
    modal.classList.remove('show');
    setTimeout(function() { modal.style.display = 'none'; }, 220);
    loadUsers();
}

window.addEventListener('load', function() {
    if (document.getElementById('auditLogsList')) loadAuditLogs();
    var auditFilter = document.getElementById('auditFilter');
    var auditSearch = document.getElementById('auditSearch');
    var timeout;
    if (auditFilter) {
        auditFilter.addEventListener('change', function() {
            loadAuditLogs(this.value, auditSearch ? auditSearch.value : '', 1);
        });
    }
    if (auditSearch) {
        auditSearch.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                loadAuditLogs(auditFilter ? auditFilter.value : '', auditSearch.value, 1);
            }, 300);
        });
    }
});
</script>
<script src="../assets/js/popup.js" defer></script>
<script src="../assets/js/notifications.js" defer></script>
</body>
</html>
