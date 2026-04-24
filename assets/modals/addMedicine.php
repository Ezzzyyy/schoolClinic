<div class="eh-modal" id="addMedicineModal" role="dialog" aria-modal="true" aria-label="Add Medicine">
    <div class="eh-panel" style="max-width:620px;">

        <div class="eh-head">
            <div class="eh-head-icon" style="background:linear-gradient(135deg,#FFEDD5,#fed7aa);color:#D97706;">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l2 4v8H2V8z"/><path d="M2 8h16M10 11v3M8 12h4"/></svg>
            </div>
            <div class="eh-head-text">
                <h3>Add New Medicine</h3>
                <p>Register a new item to the clinic inventory</p>
            </div>
            <button class="eh-close" data-close-modal type="button" aria-label="Close">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 1l12 12M13 1L1 13"/></svg>
            </button>
        </div>

        <div class="eh-body">
            <form class="eh-form-grid" id="addMedicineForm">

                <div class="eh-section-label">Medicine Details</div>
                <div class="eh-row two-col">
                    <label class="eh-field">
                        <span>Medicine Name</span>
                        <input type="text" name="medName" placeholder="Paracetamol 500mg" required />
                    </label>
                    <label class="eh-field">
                        <span>Category</span>
                        <select name="medCategory" required>
                            <option value="">Select category</option>
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
                        <select name="medUnit">
                            <option>tablets</option>
                            <option>capsules</option>
                            <option>bottles</option>
                            <option>sachets</option>
                            <option>ampules</option>
                            <option>vials</option>
                        </select>
                    </label>
                    <label class="eh-field">
                        <span>Opening Stock</span>
                        <input type="number" name="medQty" placeholder="100" min="0" required />
                    </label>
                    <label class="eh-field">
                        <span>Reorder Level</span>
                        <input type="number" name="reorderLevel" placeholder="30" min="0" />
                    </label>
                </div>

                <div class="eh-section-label">Storage &amp; Expiry</div>
                <div class="eh-row two-col">
                    <label class="eh-field">
                        <span>Expiry Date</span>
                        <input type="month" name="expiryDate" required />
                    </label>
                    <label class="eh-field">
                        <span>Storage Location</span>
                        <input type="text" name="location" placeholder="Cabinet A, Shelf 2" />
                    </label>
                </div>

                <label class="eh-field full">
                    <span>Notes <em style="font-style:normal;color:#9ca3af;font-size:10px;">(optional)</em></span>
                    <textarea name="medNotes" rows="2" placeholder="Dosage notes, supplier, or special handling instructions…"></textarea>
                </label>

            </form>
        </div>

        <div class="eh-foot">
            <button class="module-btn secondary" data-close-modal type="button">Cancel</button>
            <button class="module-btn" type="submit" form="addMedicineForm">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v12M2 8h12"/></svg>
                Add to Inventory
            </button>
        </div>
    </div>
</div>
