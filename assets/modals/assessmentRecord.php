<div class="eh-modal" id="assessmentRecordModal" role="dialog" aria-modal="true" aria-label="Record Assessment">
    <div class="eh-panel">

        <div class="eh-head">
            <div class="eh-head-icon" style="background:linear-gradient(135deg,#EBF0FE,#dde3fc);color:#5B6AF0;">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 2a6 6 0 100 12A6 6 0 0010 2zM10 7v3M10 12h.01"/></svg>
            </div>
            <div class="eh-head-text">
                <h3>Record Assessment</h3>
                <p>Enter vitals and clearance decision for this student</p>
            </div>
            <button class="eh-close" data-close-modal type="button" aria-label="Close">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 1l12 12M13 1L1 13"/></svg>
            </button>
        </div>

        <div class="eh-body">
            <form class="eh-form-grid" id="assessmentForm">
                <input type="hidden" name="student_id" id="as_student_id" />

                <div class="eh-section-label">Student &amp; Visit</div>
                <div class="eh-row two-col">
                    <label class="eh-field">
                        <span>Student Number</span>
                        <input type="text" id="as_student_num" placeholder="2024-1001" readonly style="background:#f8fafc;" />
                    </label>
                    <label class="eh-field">
                        <span>Assessment Date</span>
                        <input type="date" name="assessment_date" id="as_date" required />
                    </label>
                </div>

                <div class="eh-section-label">Vitals</div>
                <div class="eh-row four-col">
                    <label class="eh-field">
                        <span>Height</span>
                        <input type="text" name="height" id="as_height" placeholder="170 cm" />
                    </label>
                    <label class="eh-field">
                        <span>Weight</span>
                        <input type="text" name="weight" id="as_weight" placeholder="58 kg" />
                    </label>
                    <label class="eh-field">
                        <span>Blood Pressure</span>
                        <input type="text" name="blood_pressure" id="as_bp" placeholder="120/80 mmHg" />
                    </label>
                    <label class="eh-field">
                        <span>Pulse Rate</span>
                        <input type="text" name="pulse_rate" id="as_pulse" placeholder="74 bpm" />
                    </label>
                </div>

                <div class="eh-section-label">Lab &amp; Decision</div>
                <div class="eh-row">
                    <label class="eh-field full">
                        <span>Lab Results / Remarks</span>
                        <textarea name="lab_remarks" id="as_labs" rows="3" placeholder="X-ray clear, urinalysis normal, drug test pending…"></textarea>
                    </label>
                </div>
                <div class="eh-row">
                    <label class="eh-field full">
                        <span>Clearance Decision</span>
                        <div class="eh-clearance-group">
                            <label class="eh-radio-card">
                                <input type="radio" name="clearance_status" value="cleared" id="as_status_cleared" />
                                <span class="rc-dot green"></span>
                                <span>Cleared</span>
                            </label>
                            <label class="eh-radio-card">
                                <input type="radio" name="clearance_status" value="conditional" id="as_status_conditional" />
                                <span class="rc-dot amber"></span>
                                <span>Conditional</span>
                            </label>
                            <label class="eh-radio-card">
                                <input type="radio" name="clearance_status" value="pending" id="as_status_pending" />
                                <span class="rc-dot gray"></span>
                                <span>Pending</span>
                            </label>
                        </div>
                    </label>
                </div>

            </form>
        </div>

        <div class="eh-foot">
            <button class="module-btn secondary" data-close-modal type="button">Cancel</button>
            <button class="module-btn" type="submit" form="assessmentForm">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 8l4 4 8-8"/></svg>
                Save Assessment
            </button>
        </div>
    </div>
</div>
