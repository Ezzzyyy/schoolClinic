<div class="logout-popup-backdrop" id="logoutPopup" aria-hidden="true">
	<div class="logout-popup" role="dialog" aria-modal="true" aria-labelledby="logoutPopupTitle">
		<div class="logout-popup-icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
				<path d="M16 17l5-5-5-5"/>
				<path d="M21 12H9"/>
				<path d="M13 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8"/>
			</svg>
		</div>

		<h3 id="logoutPopupTitle">Log out now?</h3>
		<p>Are you sure you want to end your current ClinIQ session?</p>

		<div class="logout-popup-actions">
			<button type="button" class="popup-btn secondary" data-popup-cancel>Cancel</button>
			<button type="button" class="popup-btn danger" id="confirmLogoutBtn">Log out</button>
		</div>
	</div>
</div>