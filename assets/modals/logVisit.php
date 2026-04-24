<div class="log-visit-modal" id="logVisitModal" role="dialog" aria-modal="true" aria-labelledby="logVisitTitle">
	<div class="log-visit-panel">
		<div class="log-visit-head">
			<div>
				<h3 id="logVisitTitle">Log New Clinic Visit</h3>
				<p>Record a new student clinic visit and medicines dispensed.</p>
			</div>
			<button class="log-visit-close" type="button" aria-label="Close" data-close-log-modal>
				<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l10 10M13 3L3 13"/></svg>
			</button>
		</div>

		<form id="logVisitForm" novalidate>
			<div class="log-visit-body">
				<div class="log-visit-grid">
					<label class="log-field">
						<span>Student (ID or Name)</span>
						<input type="text" name="student_id" list="studentList" placeholder="Search student…" required />
						<datalist id="studentList">
							<!-- Populated by PHP/JS -->
						</datalist>
					</label>

					<!-- Shared datalist for medicines -->
					<datalist id="medList">
						<!-- Populated by PHP/JS -->
					</datalist>

					<label class="log-field">
						<span>Visit Date &amp; Time</span>
						<input type="datetime-local" name="visit_date" required />
					</label>

					<label class="log-field full">
						<span>Complaint / Chief Complaint</span>
						<input type="text" name="complaint" placeholder="e.g. Headache, stomach pain" required />
					</label>

					<label class="log-field full">
						<span>Symptoms</span>
						<textarea name="symptoms" rows="2" placeholder="Describe observable symptoms…" required></textarea>
					</label>

					<label class="log-field full">
						<span>Diagnosis</span>
						<input type="text" name="diagnosis" placeholder="e.g. Tension headache, URTI" required />
					</label>

					<label class="log-field full">
						<span>Treatment Given</span>
						<textarea name="treatment" rows="2" placeholder="e.g. Hydration, rest, monitoring, wound care" required></textarea>
					</label>

					<label class="log-field">
						<span>Visit Status</span>
						<select name="visit_status" required>
							<option value="Pending">Pending</option>
							<option value="Completed">Completed</option>
							<option value="Referred">Referred</option>
						</select>
					</label>

					<label class="log-field">
						<span>Handled By</span>
						<input type="text" value="<?= e($_SESSION['full_name'] ?? 'Staff') ?>" disabled />
					</label>

					<label class="log-field full">
						<span>notes</span>
						<textarea name="notes" rows="2" placeholder="Additional notes"></textarea>
					</label>
				</div>

				<!-- Dispensed Medicines Section -->
				<div class="log-medicine-section">
					<div class="log-med-header">
						<span>Medicines Dispensed</span>
						<button type="button" class="visit-btn secondary small" id="addMedicineRowBtn">+ Add Medicine</button>
					</div>
					<div class="log-med-col-labels">
						<span>Medicine Name</span>
						<span>Qty</span>
						<span></span>
					</div>
					<div id="medicineRowsContainer">
						<div class="med-empty-state">No medicines added yet. Click "+ Add Medicine" to begin.</div>
					</div>
				</div>
			</div>

			<div class="log-visit-footer">
				<p class="log-visit-msg" id="logVisitMsg" aria-live="polite"></p>
				<div class="log-visit-actions">
					<button class="visit-btn secondary" type="button" data-close-log-modal>Cancel</button>
					<button class="visit-btn" type="submit">Save Visit</button>
				</div>
			</div>
		</form>
	</div>
</div>
