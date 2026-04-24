<div class="logout-popup-backdrop" id="logoutPopup" aria-hidden="true" role="dialog" aria-label="Confirm logout">
  <div class="logout-popup">
    <div class="logout-popup-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
        <polyline points="16 17 21 12 16 7"></polyline>
        <line x1="21" y1="12" x2="9" y2="12"></line>
      </svg>
    </div>
    <h3>Confirm Logout</h3>
    <p>Are you sure you want to log out of your account?</p>
    <div class="logout-popup-actions">
      <button class="popup-btn secondary" data-popup-cancel>Cancel</button>
      <button class="popup-btn danger" id="confirmLogoutBtn">Logout</button>
    </div>
  </div>
</div>

<!-- Generic Confirmation Popup -->
<div class="logout-popup-backdrop" id="genericConfirmPopup" aria-hidden="true" role="dialog" aria-label="Confirm action">
  <div class="logout-popup">
    <div class="logout-popup-icon" style="background: #fee2e2; color: #dc2626;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
    </div>
    <h3 id="genericConfirmTitle">Confirm Action</h3>
    <p id="genericConfirmMessage">Are you sure you want to proceed?</p>
    <div class="logout-popup-actions">
      <button class="popup-btn secondary" data-popup-cancel>Cancel</button>
      <button class="popup-btn danger" id="genericConfirmBtn">Confirm</button>
    </div>
  </div>
</div>