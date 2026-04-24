<?php
$resolvedPageTitle = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== '' ? $pageTitle : 'Dashboard';
?>
<header class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= htmlspecialchars($resolvedPageTitle) ?></div>
  </div>

  <div class="topbar-right">
    <div class="group">
      <svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><g><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"></path></g></svg>
      <input placeholder="Search student..." type="search" class="input" />
    </div>

    <div class="notif-wrapper" style="position: relative;">
      <button class="notif-btn" type="button" aria-label="Notifications">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 2a4 4 0 0 1 4 4v3l1 2H3l1-2V6a4 4 0 0 1 4-4z"/><path d="M6.5 13a1.5 1.5 0 0 0 3 0"/></svg>
        <div class="notif-dot"></div>
      </button>
      <?php include __DIR__ . '/../assets/modals/notificationDropdown.php'; ?>
    </div>

    <a href="profile.php" class="profile-chip" style="text-decoration: none;">
      <div class="profile-avatar"><?= isset($_SESSION['full_name']) ? getInitials($_SESSION['full_name']) : '??' ?></div>
      <div class="profile-info">
        <div class="profile-name"><?= e($_SESSION['full_name'] ?? 'Guest') ?></div>
        <div class="profile-role" style="font-size: 0.75rem; opacity: 0.8;"><?= ucfirst(e($_SESSION['role'] ?? '')) ?></div>
      </div>
    </a>
  </div>
</header>
<script src="../assets/js/notifications.js" defer></script>
