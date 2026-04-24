<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

protectPage(1);

$pageTitle = 'Notifications';
$activeModule = 'notification';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - ClinIQ</title>
    <meta name="description" content="All system alerts, student updates and inventory warnings." />
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link rel="stylesheet" href="../assets/css/notifications.css" />
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include __DIR__ . '/../includes/header.php'; ?>
        <div class="dashboard-body notif-page">

            <!-- Top bar: tabs + action -->
            <div class="notif-topbar">
                <nav class="notif-tabs" role="tablist" aria-label="Filter notifications">
                    <button class="notif-tab active" role="tab" data-filter="all">All <span class="notif-tab-count" id="tabCountAll">0</span></button>
                    <button class="notif-tab" role="tab" data-filter="inventory">Inventory <span class="notif-tab-count" id="tabCountInventory">0</span></button>
                    <button class="notif-tab" role="tab" data-filter="student">Students <span class="notif-tab-count" id="tabCountStudent">0</span></button>
                    <button class="notif-tab" role="tab" data-filter="system">System <span class="notif-tab-count" id="tabCountSystem">0</span></button>
                    <button class="notif-tab" role="tab" data-filter="reports">Reports <span class="notif-tab-count" id="tabCountReports">0</span></button>
                </nav>
                <button class="notif-mark-all-btn" id="markAllReadBtn" type="button">
                    <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 8l4 4 8-8"/></svg>
                    Mark all read
                </button>
            </div>

            <!-- Unread banner (shown when there are unreads) -->
            <div class="notif-unread-banner" id="unreadBanner" style="display: none;">
                <div class="notif-unread-dot-pulse"></div>
                <span id="unreadBannerText"><strong>0 unread</strong> notifications require your attention</span>
            </div>

            <!-- Notification list -->
            <div class="notif-list" id="notifList"></div><!-- /notif-list -->

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.notif-tab');
    const notifList = document.getElementById('notifList');
    const unreadBanner = document.getElementById('unreadBanner');
    const unreadBannerText = document.getElementById('unreadBannerText');
    let activeFilter = 'all';

    const iconMap = {
        inventory: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16l2 8v10H2V12z"/><path d="M2 12h20M12 16v3"/></svg>',
        student: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="5"/><path d="M3 21c0-5 4-9 9-9s9 4 9 9"/></svg>',
        system: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>',
        reports: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 12l3-3 4 4 7-7 4 4"/></svg>'
    };

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    };

    const updateCounts = (counts) => {
        document.getElementById('tabCountAll').textContent = String(counts.all || 0);
        document.getElementById('tabCountInventory').textContent = String(counts.inventory || 0);
        document.getElementById('tabCountStudent').textContent = String(counts.student || 0);
        document.getElementById('tabCountSystem').textContent = String(counts.system || 0);
        document.getElementById('tabCountReports').textContent = String(counts.reports || 0);

        const unread = Number(counts.unread || 0);
        if (unread > 0) {
            unreadBanner.style.display = 'flex';
            unreadBannerText.innerHTML = `<strong>${unread} unread</strong> notifications require your attention`;
        } else {
            unreadBanner.style.display = 'none';
        }
    };

    const renderList = (notifications) => {
        if (!notifications.length) {
            notifList.innerHTML = '<div class="notif-item"><div class="notif-content"><p class="notif-desc">No notifications found.</p></div></div>';
            return;
        }

        notifList.innerHTML = notifications.map((item) => `
            <div class="notif-item ${item.is_read ? '' : 'unread'} cat-${escapeHtml(item.category)}" data-id="${item.id}" data-link="${escapeHtml(item.link_url || '')}">
                <div class="notif-icon-wrap ${escapeHtml(item.category)}">
                    ${iconMap[item.category] || iconMap.system}
                </div>
                <div class="notif-content">
                    <div class="notif-row-top">
                        <span class="notif-title">${escapeHtml(item.title)}</span>
                        <span class="notif-time">${escapeHtml(item.time_ago)}</span>
                    </div>
                    <p class="notif-desc">${escapeHtml(item.message)}</p>
                    <div class="notif-chips"><span class="notif-chip ${escapeHtml(item.category)}">${escapeHtml(item.category.charAt(0).toUpperCase() + item.category.slice(1))}</span></div>
                </div>
                ${item.is_read ? '' : '<div class="notif-unread-pip" title="Unread"></div>'}
            </div>
        `).join('');

        notifList.querySelectorAll('.notif-item').forEach((el) => {
            el.addEventListener('click', async () => {
                const id = Number(el.dataset.id || 0);
                const link = el.dataset.link || '';

                if (id > 0 && el.classList.contains('unread')) {
                    await fetch('../actions/markNotificationsRead.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `notification_id=${encodeURIComponent(id)}`
                    });
                }

                if (link) {
                    window.location.href = link;
                    return;
                }

                await loadNotifications(activeFilter);
            });
        });
    };

    const loadNotifications = async (filter = 'all') => {
        const params = new URLSearchParams({ limit: '100' });
        if (filter !== 'all') {
            params.set('filter', filter);
        }

        try {
            const response = await fetch(`../actions/getNotifications.php?${params.toString()}`, { cache: 'no-store' });
            const data = await response.json();

            if (!data.success) {
                notifList.innerHTML = '<div class="notif-item"><div class="notif-content"><p class="notif-desc">Unable to load notifications.</p></div></div>';
                return;
            }

            renderList(data.notifications || []);
            updateCounts(data.counts || {});
        } catch (_error) {
            notifList.innerHTML = '<div class="notif-item"><div class="notif-content"><p class="notif-desc">Unable to load notifications.</p></div></div>';
        }
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', async () => {
            tabs.forEach((t) => t.classList.remove('active'));
            tab.classList.add('active');
            activeFilter = tab.dataset.filter || 'all';
            await loadNotifications(activeFilter);
        });
    });

    document.getElementById('markAllReadBtn')?.addEventListener('click', async () => {
        await fetch('../actions/markNotificationsRead.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'all=1'
        });
        await loadNotifications(activeFilter);
    });

    loadNotifications(activeFilter);
});
</script>
</body>
</html>
