<div class="eh-modal" id="restockMedicineModal" role="dialog" aria-modal="true" aria-label="Restock Medicine">
    <div class="eh-panel" style="max-width:440px;">

        <div class="eh-head">
            <div class="eh-head-icon" style="background:linear-gradient(135deg,#D1FAE5,#A7F3D0);color:#059669;">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h12l2 4v8H2V8z"/><path d="M2 8h16M10 11v3M8 12h4"/></svg>
            </div>
            <div class="eh-head-text">
                <h3>Restock Medicine</h3>
                <p id="restockMedSubtitle">Add stock for this item</p>
            </div>
            <button class="eh-close" data-close-modal type="button" aria-label="Close">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 1l12 12M13 1L1 13"/></svg>
            </button>
        </div>

        <div class="eh-body">
            <form class="eh-form-grid" id="restockMedicineForm">
                <input type="hidden" name="medId" id="restockMedId" />

                <label class="eh-field full">
                    <span>Medicine</span>
                    <input type="text" id="restockMedName" readonly style="background:#f9fafb; color:#6b7280;" />
                </label>

                <label class="eh-field full">
                    <span>Current Stock</span>
                    <input type="text" id="restockCurrentQty" readonly style="background:#f9fafb; color:#6b7280;" />
                </label>

                <label class="eh-field full">
                    <span>Quantity to Add <em style="color:#dc2626;">*</em></span>
                    <input type="number" name="restockQty" id="restockQtyInput" min="1" placeholder="e.g. 100" required />
                </label>
            </form>
        </div>

        <div class="eh-foot">
            <button class="module-btn secondary" data-close-modal type="button">Cancel</button>
            <button class="module-btn" type="submit" form="restockMedicineForm" style="background:linear-gradient(135deg,#059669,#10B981);">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v12M2 8h12"/></svg>
                Confirm Restock
            </button>
        </div>
    </div>
</div>
