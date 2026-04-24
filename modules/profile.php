<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

protectPage(1);

function ensureProfilePhotoColumn(PDO $conn): void
{
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
        $conn->exec("ALTER TABLE users ADD profile_photo VARCHAR(255) DEFAULT NULL AFTER email");
    }
}

$pageTitle = 'My Profile';
$activeModule = 'profile';

$db = new Database();
$conn = $db->connect();

ensureProfilePhotoColumn($conn);

$stmt = $conn->prepare('SELECT user_id, first_name, last_name, email, username, role, status, profile_photo FROM users WHERE user_id = ? LIMIT 1');
$stmt->execute([(int) ($_SESSION['user_id'] ?? 0)]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('../logout.php');
}

$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$displayRole = ucfirst((string) ($user['role'] ?? 'staff'));
$displayStatus = strtolower((string) ($user['status'] ?? 'active')) === 'active' ? 'Active &amp; On Duty' : 'Account Inactive';
$profilePhoto = trim((string) ($user['profile_photo'] ?? ''));
$profilePhotoUrl = $profilePhoto !== '' ? '../' . ltrim($profilePhoto, '/') : '';
$profileQrData = json_encode([
    'user_id' => (int) $user['user_id'],
    'name' => $displayName,
    'username' => $user['username'] ?? '',
    'role' => $user['role'] ?? '',
    'profile' => 'ClinIQ profile for ' . $displayName,
]);

$flashMsg = $_SESSION['profile_msg'] ?? '';
$flashType = $_SESSION['profile_msg_type'] ?? 'success';
unset($_SESSION['profile_msg'], $_SESSION['profile_msg_type']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
    <meta name="description" content="Manage your ClinIQ nurse profile, credentials, and account security." />
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../assets/css/profile.css" />
    <link rel="stylesheet" href="../assets/css/modal.css" />
    <link rel="stylesheet" href="../assets/css/notifications_popup.css" />
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        <div class="dashboard-body module-page">

            <?php if ($flashMsg !== ''): ?>
            <div style="margin-bottom:12px;padding:12px 14px;border-radius:10px;border:1px solid <?= $flashType === 'error' ? '#fecaca' : '#bbf7d0' ?>;background:<?= $flashType === 'error' ? '#fef2f2' : '#f0fdf4' ?>;color:<?= $flashType === 'error' ? '#991b1b' : '#166534' ?>;font-size:13px;font-weight:600;">
                <?= e($flashMsg) ?>
            </div>
            <?php endif; ?>

            <div class="profile-page-layout">
                <aside>
                    <div class="profile-sidebar-card">
                        <div class="profile-avatar-wrap">
                            <button class="avatar-edit-badge" type="button" id="changePhotoBtn" title="Change photo" aria-label="Change profile photo">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                                    <circle cx="12" cy="13" r="4"/>
                                </svg>
                            </button>
                            <div class="profile-big-avatar" id="profileAvatarWrap">
                                <img src="<?= e($profilePhotoUrl) ?>" alt="Profile photo" class="profile-avatar-image<?= $profilePhotoUrl !== '' ? '' : ' is-hidden' ?>" id="profileAvatarImg" />
                                <span class="profile-avatar-initials<?= $profilePhotoUrl !== '' ? ' is-hidden' : '' ?>" id="profileAvatarInitials"><?= e(getInitials($displayName)) ?></span>
                            </div>
                        </div>

                        <div class="profile-main-name"><?= e($displayName) ?></div>
                        <div class="profile-main-role"><?= e($displayRole) ?></div>
                        <div class="profile-role-badge">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M8 1l2 5h5l-4 3 1.5 5L8 11l-4.5 3L5 9 1 6h5z"/>
                            </svg>
                            <?= e($displayRole) ?> Account
                        </div>

                        <div class="profile-stats-strip">
                            <div class="p-stat-item">
                                <div class="p-stat-icon visits">
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path d="M8 2C5.2 2 3 4.2 3 7c0 3.8 5 9 5 9s5-5.2 5-9c0-2.8-2.2-5-5-5z"/>
                                        <circle cx="8" cy="7" r="2"/>
                                    </svg>
                                </div>
                                <span class="p-stat-val" id="statVisits">0</span>
                                <span class="p-stat-lbl">Visits</span>
                            </div>
                            <div class="p-stat-item">
                                <div class="p-stat-icon reports">
                                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <rect x="3" y="1" width="10" height="14" rx="1.5"/>
                                        <path d="M6 5h4M6 8h4M6 11h2"/>
                                    </svg>
                                </div>
                                <span class="p-stat-val" id="statReports">0</span>
                                <span class="p-stat-lbl">Reports</span>
                            </div>
                        </div>

                        <div class="profile-quick-actions">
                            <button class="profile-action-btn primary" type="button" id="downloadQrBtn">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="1" y="1" width="6" height="6" rx="1"/>
                                    <rect x="9" y="1" width="6" height="6" rx="1"/>
                                    <rect x="1" y="9" width="6" height="6" rx="1"/>
                                    <path d="M9 9h2v2H9zM13 9v2M9 13h2M13 13v2M11 11h4"/>
                                </svg>
                                Download Profile QR
                            </button>
                            <button class="profile-action-btn secondary" type="button" id="viewActivityBtn">
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <polyline points="1,12 5,7 9,9 15,3"/>
                                    <polyline points="11,3 15,3 15,7"/>
                                </svg>
                                View Activity Log
                            </button>
                        </div>

                        <div class="profile-status-row">
                            <span class="profile-status-dot"></span>
                            <?= $displayStatus ?>
                        </div>
                    </div>
                </aside>

                <div class="profile-content-area">
                    <section class="p-card" aria-labelledby="profInfoTitle">
                        <div class="p-card-header">
                            <div class="p-card-title-group">
                                <div class="p-card-icon blue">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="p-card-title" id="profInfoTitle">Professional Information</div>
                                    <div class="p-card-subtitle">Your public-facing clinical details</div>
                                </div>
                            </div>
                        </div>
                        <hr class="p-card-divider" />
                        <form class="p-form-grid" id="profInfoForm" novalidate action="../actions/saveProfile.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="form_type" value="profile_info" />
                            <input id="profilePhotoInput" name="profile_photo" type="file" accept="image/*" hidden />
                            <div class="p-field">
                                <label class="p-label" for="firstName">First Name</label>
                                <input id="firstName" name="first_name" type="text" class="p-input" value="<?= e($user['first_name'] ?? '') ?>" autocomplete="given-name" required />
                            </div>
                            <div class="p-field">
                                <label class="p-label" for="lastName">Last Name</label>
                                <input id="lastName" name="last_name" type="text" class="p-input" value="<?= e($user['last_name'] ?? '') ?>" autocomplete="family-name" required />
                            </div>
                            <div class="p-field">
                                <label class="p-label" for="emailAddr">Email Address</label>
                                <input id="emailAddr" name="email" type="email" class="p-input" value="<?= e($user['email'] ?? '') ?>" autocomplete="email" required />
                            </div>
                            <div class="p-field">
                                <label class="p-label" for="username">Username</label>
                                <input id="username" name="username" type="text" class="p-input" value="<?= e($user['username'] ?? '') ?>" autocomplete="username" required />
                            </div>
                            <div class="p-field full">
                                <label class="p-label" for="accountRole">Account Role</label>
                                <input id="accountRole" type="text" class="p-input" value="<?= e($displayRole) ?>" disabled />
                            </div>
                            <div class="p-btn-row">
                                <button type="reset" class="module-btn secondary">Discard</button>
                                <button type="submit" class="module-btn">Save Changes</button>
                            </div>
                        </form>
                    </section>

                    <section class="p-card" aria-labelledby="securityTitle">
                        <div class="p-card-header">
                            <div class="p-card-title-group">
                                <div class="p-card-icon amber">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="p-card-title" id="securityTitle">Security &amp; Credentials</div>
                                    <div class="p-card-subtitle">Change your login password</div>
                                </div>
                            </div>
                        </div>
                        <hr class="p-card-divider" />

                        <div class="p-security-hint">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="8" cy="8" r="7"/>
                                <path d="M8 7v4M8 5v.5"/>
                            </svg>
                            Use at least 8 characters with a mix of letters, numbers, and symbols.
                        </div>

                        <form class="p-form-grid" id="passwordForm" novalidate action="../actions/saveProfile.php" method="POST">
                            <input type="hidden" name="form_type" value="password_change" />
                            <div class="p-field">
                                <label class="p-label" for="currentPassword">Current Password</label>
                                <div class="p-password-wrap">
                                    <input id="currentPassword" name="current_password" type="password" class="p-input" placeholder="••••••••" autocomplete="current-password" required />
                                    <button type="button" class="p-pass-toggle" data-toggle-password="currentPassword" aria-label="Show current password" title="Show or hide password">
                                        <svg class="p-pass-icon p-pass-icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg class="p-pass-icon p-pass-icon-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17.94 17.94A10.4 10.4 0 0 1 12 19c-7 0-11-7-11-7a20 20 0 0 1 5.06-5.94"></path>
                                            <path d="M9.9 4.24A10.35 10.35 0 0 1 12 5c7 0 11 7 11 7a19.6 19.6 0 0 1-2.16 3.19"></path>
                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="p-field">
                                <label class="p-label" for="newPassword">New Password</label>
                                <div class="p-password-wrap">
                                    <input id="newPassword" name="new_password" type="password" class="p-input" placeholder="••••••••" autocomplete="new-password" required minlength="8" />
                                    <button type="button" class="p-pass-toggle" data-toggle-password="newPassword" aria-label="Show new password" title="Show or hide password">
                                        <svg class="p-pass-icon p-pass-icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg class="p-pass-icon p-pass-icon-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17.94 17.94A10.4 10.4 0 0 1 12 19c-7 0-11-7-11-7a20 20 0 0 1 5.06-5.94"></path>
                                            <path d="M9.9 4.24A10.35 10.35 0 0 1 12 5c7 0 11 7 11 7a19.6 19.6 0 0 1-2.16 3.19"></path>
                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="p-field">
                                <label class="p-label" for="confirmPassword">Confirm Password</label>
                                <div class="p-password-wrap">
                                    <input id="confirmPassword" name="confirm_password" type="password" class="p-input" placeholder="••••••••" autocomplete="new-password" required minlength="8" />
                                    <button type="button" class="p-pass-toggle" data-toggle-password="confirmPassword" aria-label="Show confirm password" title="Show or hide password">
                                        <svg class="p-pass-icon p-pass-icon-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        <svg class="p-pass-icon p-pass-icon-closed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17.94 17.94A10.4 10.4 0 0 1 12 19c-7 0-11-7-11-7a20 20 0 0 1 5.06-5.94"></path>
                                            <path d="M9.9 4.24A10.35 10.35 0 0 1 12 5c7 0 11 7 11 7a19.6 19.6 0 0 1-2.16 3.19"></path>
                                            <line x1="1" y1="1" x2="23" y2="23"></line>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="p-btn-row">
                                <button type="submit" class="module-btn secondary" id="changePasswordBtn">Change Password</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="profileQrModal" aria-hidden="true">
    <div class="modal-panel profile-mini-modal">
        <div class="modal-header">
            <div class="modal-student-identity">
                <div class="modal-avatar">QR</div>
                <div>
                    <h3 class="modal-student-name">Profile QR</h3>
                    <p class="modal-student-meta">Download a QR code for this profile</p>
                </div>
            </div>
            <button class="modal-close-btn" type="button" data-close-profile-modal aria-label="Close modal">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l10 10M13 3L3 13"/></svg>
            </button>
        </div>
        <div class="modal-body profile-qr-body">
            <div class="profile-qr-card">
                <img id="profileQrImage" alt="Profile QR code" />
                <div class="profile-qr-meta">
                    <strong><?= e($displayName) ?></strong>
                    <span>@<?= e($user['username'] ?? '') ?></span>
                </div>
            </div>
            <div class="p-btn-row" style="grid-column:1 / -1; margin-top: 14px; justify-content: center;">
                <a class="module-btn" id="downloadQrFileBtn" href="#" download="cliniq-profile-qr.png">Download QR Image</a>
            </div>
        </div>
    </div>
</div>

<div class="modal-backdrop" id="activityModal" aria-hidden="true">
    <div class="modal-panel profile-activity-modal">
        <div class="modal-header">
            <div class="modal-student-identity">
                <div class="modal-avatar">AL</div>
                <div>
                    <h3 class="modal-student-name">Activity Log</h3>
                    <p class="modal-student-meta">Recent profile actions</p>
                </div>
            </div>
            <button class="modal-close-btn" type="button" data-close-profile-modal aria-label="Close modal">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3l10 10M13 3L3 13"/></svg>
            </button>
        </div>
        <div class="modal-body">
            <div id="activityLogList" class="profile-activity-list">
                <div class="profile-activity-empty">Loading activity...</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const profilePhotoInput = document.getElementById('profilePhotoInput');
    const changePhotoBtn = document.getElementById('changePhotoBtn');
    const profileAvatarImg = document.getElementById('profileAvatarImg');
    const profileAvatarInitials = document.getElementById('profileAvatarInitials');
    const avatarWrap = document.getElementById('profileAvatarWrap');
    const qrModal = document.getElementById('profileQrModal');
    const activityModal = document.getElementById('activityModal');
    const qrImage = document.getElementById('profileQrImage');
    const downloadQrFileBtn = document.getElementById('downloadQrFileBtn');
    const activityLogList = document.getElementById('activityLogList');
    const statVisits = document.getElementById('statVisits');
    const statReports = document.getElementById('statReports');
    const profileQrData = <?= $profileQrData ?>;

    // Load profile statistics
    async function loadProfileStats() {
        try {
            const response = await fetch('../actions/getProfileStats.php');
            const data = await response.json();
            if (data.success) {
                if (statVisits) statVisits.textContent = data.visits.toLocaleString();
                if (statReports) statReports.textContent = data.reports.toLocaleString();
            }
        } catch (err) {
            console.error('Failed to load profile stats:', err);
        }
    }
    loadProfileStats();

    function openProfileModal(modal) {
        if (!modal) return;
        modal.classList.add('is-open');
        document.body.classList.add('modal-open');
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeProfileModal(modal) {
        if (!modal) return;
        modal.classList.remove('is-open');
        document.body.classList.remove('modal-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function buildQrUrl() {
        const data = encodeURIComponent(JSON.stringify(profileQrData));
        return 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=10&data=' + data;
    }

    async function prepareQrModal() {
        const qrUrl = buildQrUrl();
        if (qrImage) qrImage.src = qrUrl;
        if (downloadQrFileBtn) {
            downloadQrFileBtn.href = qrUrl;
        }
    }

    async function loadActivityLog() {
        if (!activityLogList) return;
        activityLogList.innerHTML = '<div class="profile-activity-empty">Loading activity...</div>';

        try {
            const response = await fetch('../actions/getProfileActivity.php', { cache: 'no-store' });
            const data = await response.json();

            if (!data.success || !Array.isArray(data.logs) || data.logs.length === 0) {
                activityLogList.innerHTML = '<div class="profile-activity-empty">No activity found yet.</div>';
                return;
            }

            activityLogList.innerHTML = data.logs.map((log) => `
                <div class="profile-activity-item">
                    <div class="profile-activity-dot"></div>
                    <div class="profile-activity-body">
                        <div class="profile-activity-top">
                            <strong>${log.action || 'Action'}</strong>
                            <span>${log.timestamp || ''}</span>
                        </div>
                        <div class="profile-activity-desc">${log.description || 'No description provided.'}</div>
                    </div>
                </div>
            `).join('');
        } catch (_error) {
            activityLogList.innerHTML = '<div class="profile-activity-empty">Unable to load activity log.</div>';
        }
    }

    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = btn.getAttribute('data-toggle-password');
            const input = document.getElementById(targetId);
            if (!input) return;

            const showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            btn.classList.toggle('is-showing', !showing);
            btn.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
        });
    });

    if (changePhotoBtn && profilePhotoInput) {
        changePhotoBtn.addEventListener('click', function () {
            profilePhotoInput.click();
        });
    }

    if (avatarWrap && profilePhotoInput) {
        avatarWrap.addEventListener('click', function () {
            profilePhotoInput.click();
        });
    }

    if (profilePhotoInput) {
        profilePhotoInput.addEventListener('change', function () {
            const file = profilePhotoInput.files && profilePhotoInput.files[0];
            if (!file) return;
            const previewUrl = URL.createObjectURL(file);
            if (profileAvatarImg) {
                profileAvatarImg.src = previewUrl;
                profileAvatarImg.classList.remove('is-hidden');
            }
            if (profileAvatarInitials) {
                profileAvatarInitials.classList.add('is-hidden');
            }
        });
    }

    document.getElementById('downloadQrBtn')?.addEventListener('click', async function () {
        await prepareQrModal();
        openProfileModal(qrModal);
    });

    document.getElementById('viewActivityBtn')?.addEventListener('click', async function () {
        openProfileModal(activityModal);
        await loadActivityLog();
    });

    document.querySelectorAll('[data-close-profile-modal]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            closeProfileModal(qrModal);
            closeProfileModal(activityModal);
        });
    });

    document.querySelectorAll('.modal-backdrop').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) closeProfileModal(modal);
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeProfileModal(qrModal);
            closeProfileModal(activityModal);
        }
    });
});
</script>
<script src="../assets/js/popup.js" defer></script>
<script src="../assets/js/notifications.js" defer></script>
</body>
</html>
