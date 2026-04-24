document.addEventListener('DOMContentLoaded', () => {
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

                await loadNotifications();
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
        } catch (_error) {
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
});
