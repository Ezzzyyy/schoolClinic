<?php
$resolvedPageTitle = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== '' ? $pageTitle : 'Dashboard';

// Fetch user's profile photo from session or database
$profilePhoto = null;
if (isset($_SESSION['user_id'])) {
    // Check session first (updated immediately after profile change)
    if (isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo'])) {
        $photoPath = $_SESSION['profile_photo'];
        // Remove any existing ../ prefix to avoid duplication
        $photoPath = preg_replace('/^\.+\//', '', $photoPath);
        $profilePhoto = '../' . ltrim($photoPath, '/');
    } else {
        // Fall back to database query
        require_once __DIR__ . '/../config/database.php';
        $db = new Database();
        $conn = $db->connect();
        $stmt = $conn->prepare('SELECT profile_photo FROM users WHERE user_id = ? LIMIT 1');
        $stmt->execute([(int) $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && !empty($user['profile_photo'])) {
            $photoPath = $user['profile_photo'];
            // Remove any existing ../ prefix to avoid duplication
            $photoPath = preg_replace('/^\.+\//', '', $photoPath);
            $profilePhoto = '../' . ltrim($photoPath, '/');
            $_SESSION['profile_photo'] = $user['profile_photo']; // Cache in session
        }
    }
}
?>
<header class="topbar">
  <div class="topbar-left">
    <div class="page-title"><?= htmlspecialchars($resolvedPageTitle) ?></div>
  </div>

  <div class="topbar-right">
    <div class="group">
      <svg class="icon" aria-hidden="true" viewBox="0 0 24 24"><g><path d="M21.53 20.47l-3.66-3.66C19.195 15.24 20 13.214 20 11c0-4.97-4.03-9-9-9s-9 4.03-9 9 4.03 9 9 9c2.215 0 4.24-.804 5.808-2.13l3.66 3.66c.147.146.34.22.53.22s.385-.073.53-.22c.295-.293.295-.767.002-1.06zM3.5 11c0-4.135 3.365-7.5 7.5-7.5s7.5 3.365 7.5 7.5-3.365 7.5-7.5 7.5-7.5-3.365-7.5-7.5z"></path></g></svg>
      <input placeholder="Search student..." type="search" class="input" id="globalSearch" />
    </div>

    <div class="notif-wrapper" style="position: relative;">
      <button class="notif-btn" type="button" aria-label="Notifications">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M8 2a4 4 0 0 1 4 4v3l1 2H3l1-2V6a4 4 0 0 1 4-4z"/><path d="M6.5 13a1.5 1.5 0 0 0 3 0"/></svg>
        <div class="notif-dot"></div>
      </button>
      <?php include __DIR__ . '/../assets/modals/notificationDropdown.php'; ?>
    </div>

    <a href="profile.php" class="profile-chip" style="text-decoration: none;">
      <div class="profile-avatar header-profile-avatar" style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; max-width: 36px; max-height: 36px; border-radius: 50%; overflow: hidden; flex-shrink: 0;">
        <?php if ($profilePhoto): ?>
          <img src="<?= e($profilePhoto) ?>" alt="Profile" class="header-profile-img" style="width: 36px; height: 36px; max-width: 36px; max-height: 36px; object-fit: cover; border-radius: 50%; display: block;" />
        <?php else: ?>
          <?= isset($_SESSION['full_name']) ? getInitials($_SESSION['full_name']) : '??' ?>
        <?php endif; ?>
      </div>
      <div class="profile-info">
        <div class="profile-name"><?= e($_SESSION['full_name'] ?? 'Guest') ?></div>
        <div class="profile-role"><?= ucfirst(e($_SESSION['role'] ?? '')) ?></div>
      </div>
    </a>
  </div>
</header>
<link rel="stylesheet" href="../assets/css/notifications_popup.css" />
<script>
(function() {
    const notifBtn = document.querySelector('.notif-btn');
    const notifDropdown = document.getElementById('notificationDropdown');
    const notifList = document.getElementById('notifDropdownList');
    const notifBadge = document.getElementById('notifBadge');
    const markAllBtn = document.getElementById('markAllDropdownReadBtn');
    const notifDot = document.querySelector('.notif-dot');

    if (!notifBtn || !notifDropdown || !notifList) return;

    const iconMap = {
        inventory: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 7h14l1.5 5v6H1.5v-6L3 7z"/><path d="M7 7V5a3 3 0 0 1 6 0v2"/><path d="M10 12v2"/></svg>',
        student: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="10" cy="7" r="3.5"/><path d="M3 17c0-3.5 3-6 7-6s7 2.5 7 6"/></svg>',
        system: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="2" y="3" width="16" height="11" rx="2"/><path d="M7 17h6M10 14v3"/></svg>',
        reports: '<svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 12l3-3 4 4 7-7 3 3"/></svg>'
    };

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    };

    const updateUnreadIndicators = (unreadCount) => {
        if (notifBadge) {
            notifBadge.textContent = unreadCount > 0 ? `${unreadCount} New` : '0 New';
        }
        if (notifDot) {
            notifDot.style.display = unreadCount > 0 ? 'block' : 'none';
        }
    };

    const renderList = (notifications) => {
        if (!notifications.length) {
            notifList.innerHTML = `
                <div class="nd-item" style="padding-left: 18px;">
                    <div class="nd-body"><div class="nd-desc">No notifications yet.</div></div>
                </div>
            `;
            return;
        }

        notifList.innerHTML = notifications.map((item) => `
            <div class="nd-item ${item.is_read ? '' : 'unread'}" data-id="${item.id}" data-link="${escapeHtml(item.link_url || '')}">
                <div class="nd-unread-bar" ${item.is_read ? 'style="opacity:0"' : ''}></div>
                <div class="nd-icon ${escapeHtml(item.category)}">${iconMap[item.category] || iconMap.system}</div>
                <div class="nd-body">
                    <div class="nd-row">
                        <span class="nd-title">${escapeHtml(item.title)}</span>
                        <span class="nd-time">${escapeHtml(item.time_ago)}</span>
                    </div>
                    <div class="nd-desc">${escapeHtml(item.message)}</div>
                    <span class="nd-chip ${escapeHtml(item.category)}">${escapeHtml(item.category.charAt(0).toUpperCase() + item.category.slice(1))}</span>
                </div>
            </div>
        `).join('');

        notifList.querySelectorAll('.nd-item').forEach((el) => {
            el.addEventListener('click', async () => {
                const id = Number(el.dataset.id || 0);
                const link = el.dataset.link || '';

                if (id > 0 && el.classList.contains('unread')) {
                    await fetch('../actions/markNotificationsRead.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `notification_id=${encodeURIComponent(id)}`
                    });
                    el.classList.remove('unread');
                    const unreadBar = el.querySelector('.nd-unread-bar');
                    if (unreadBar) unreadBar.style.opacity = '0';
                }

                if (link) {
                    window.location.href = link;
                }

                loadNotifications();
            });
        });
    };

    const loadNotifications = async () => {
        try {
            const response = await fetch('../actions/getNotifications.php?limit=6', { cache: 'no-store' });
            const data = await response.json();

            if (!data.success) return;

            renderList(data.notifications || []);
            updateUnreadIndicators(Number(data.counts?.unread || 0));
        catch (_error) {
            notifList.innerHTML = `
                <div class="nd-item" style="padding-left: 18px;">
                    <div class="nd-body"><div class="nd-desc">Unable to load notifications.</div></div>
                </div>
            `;
        }
    };

    notifBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const opening = !notifDropdown.classList.contains('is-open');
        notifDropdown.classList.toggle('is-open');
        if (opening) {
            await loadNotifications();
        }
    });

    document.addEventListener('click', (e) => {
        if (!notifDropdown.contains(e.target) && !notifBtn.contains(e.target)) {
            notifDropdown.classList.remove('is-open');
        }
    });

    const viewAllLink = notifDropdown.querySelector('.nd-view-all');
    if (viewAllLink) {
        viewAllLink.addEventListener('click', () => {
            notifDropdown.classList.remove('is-open');
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') notifDropdown.classList.remove('is-open');
    });

    if (markAllBtn) {
        markAllBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            await fetch('../actions/markNotificationsRead.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'all=1'
            });
            await loadNotifications();
        });
    }

    loadNotifications();
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const globalSearch = document.getElementById('globalSearch');
    if (globalSearch) {
        globalSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                performGlobalSearch();
            }
        });

        // Optional: Add click handler to the search icon
        const searchIcon = globalSearch.previousElementSibling;
        if (searchIcon && searchIcon.tagName === 'svg') {
            searchIcon.style.cursor = 'pointer';
            searchIcon.addEventListener('click', performGlobalSearch);
        }
    }

    function performGlobalSearch() {
        const query = globalSearch.value.trim();
        if (query) {
            // Redirect to student records with search parameter
            window.location.href = 'studentRecords.php?search=' + encodeURIComponent(query);
        } else {
            // If no query, just go to student records
            window.location.href = 'studentRecords.php';
        }
    }
});
</script>
