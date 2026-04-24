document.addEventListener('DOMContentLoaded', () => {
  const popup = document.getElementById('logoutPopup');
  const confirmBtn = document.getElementById('confirmLogoutBtn');
  if (!popup || !confirmBtn) {
    return;
  }

  const cancelButtons = popup.querySelectorAll('[data-popup-cancel]');
  let targetHref = '../logout.php';

  function openPopup(href) {
    targetHref = href || '../logout.php';
    popup.classList.add('is-open');
    popup.setAttribute('aria-hidden', 'false');
    document.body.classList.add('popup-open');
  }

  function closePopup() {
    popup.classList.remove('is-open');
    popup.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('popup-open');
  }

  document.querySelectorAll('a.nav-item.logout').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      openPopup(link.getAttribute('href') || '../logout.php');
    });
  });

  confirmBtn.addEventListener('click', () => {
    window.location.href = targetHref;
  });

  cancelButtons.forEach((button) => {
    button.addEventListener('click', closePopup);
  });

  popup.addEventListener('click', (event) => {
    if (event.target === popup) {
      closePopup();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && popup.classList.contains('is-open')) {
      closePopup();
    }
  });
});
