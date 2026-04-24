<div class="eh-modal" id="editMedicineModal" role="dialog" aria-modal="true" aria-label="Edit Medicine">
    <div class="eh-panel" style="max-width:620px;">

        <div class="eh-head">
            <div class="eh-head-icon" style="background:linear-gradient(135deg,#EEF0FD,#dde3fc);color:#5B6AF0;">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M13 2l5 5-10 10H3v-5L13 2z"/></svg>
            </div>
            <div class="eh-head-text">
                <h3>Edit Medicine</h3>
                <p id="editMedSubtitle">Update stock details for this item</p>
            </div>
            <button class="eh-close" data-close-modal type="button" aria-label="Close">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 1l12 12M13 1L1 13"/></svg>
            </button>
        </div>

        <div class="eh-body">
            <form class="eh-form-grid" id="editMedicineForm">
                <input type="hidden" name="medId" id="editMedId" />

                <div class="eh-section-label">Medicine Details</div>
                <div class="eh-row two-col">
                    <label class="eh-field">
                        <span>Medicine Name</span>
                        <input type="text" id="editMedName" name="medName" required />
                    </label>
                    <label class="eh-field">
                        <span>Category</span>
                        <select id="editMedCategory" name="medCategory">
                            <option>Analgesic</option>
                            <option>Antibiotic</option>
                            <option>Antihistamine</option>
                            <option>Antacid</option>
                            <option>First Aid</option>
                            <option>Vitamins / Supplements</option>
                            <option>Other</option>
                        </select>
                    </label>
                </div>

                <div class="eh-row three-col">
                    <label class="eh-field">
                        <span>Unit</span>
                        <select id="editMedUnit" name="medUnit">
                            <option>tablets</option>
                            <option>capsules</option>
                            <option>bottles</option>
                            <option>sachets</option>
                            <option>ampules</option>
                            <option>vials</option>
                        </select>
                    </label>
                    <label class="eh-field">
                        <span>On Hand (qty)</span>
                        <input type="number" id="editMedQty" name="medQty" min="0" />
                    </label>
                    <label class="eh-field">
                        <span>Reorder Level</span>
                        <input type="number" id="editReorderLevel" name="reorderLevel" min="0" />
                    </label>
                </div>

                <div class="eh-section-label">Storage &amp; Expiry</div>
                <div class="eh-row two-col">
                    <label class="eh-field">
                        <span>Expiry Date</span>
                        <input type="month" id="editExpiryDate" name="expiryDate" />
                    </label>
                    <label class="eh-field">
                        <span>Storage Location</span>
                        <input type="text" id="editLocation" name="location" placeholder="Cabinet A, Shelf 2" />
                    </label>
                </div>

                <label class="eh-field full">
                    <span>Notes <em style="font-style:normal;color:#9ca3af;font-size:10px;">(optional)</em></span>
                    <textarea id="editMedNotes" name="medNotes" rows="2" placeholder="Dosage notes, supplier, or special handling…"></textarea>
                </label>

            </form>
        </div>

        <div class="eh-foot">
            <button class="module-btn secondary" data-close-modal type="button">Cancel</button>
            <button class="module-btn" type="submit" form="editMedicineForm">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 8l4 4 8-8"/></svg>
                Save Changes
            </button>
        </div>
    </div>
</div>
