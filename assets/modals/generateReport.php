<div class="eh-modal" id="generateExportModal" role="dialog" aria-modal="true" aria-label="Generate & Export Report">
    <div class="eh-panel" style="max-width:560px;">

        <div class="eh-head">
            <div class="eh-head-icon" style="background:linear-gradient(135deg,#FCE7F3,#fbcfe8);color:#BE185D;">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="2" width="16" height="16" rx="2"/><path d="M6 7h8M6 11h8M6 15h4"/></svg>
            </div>
            <div class="eh-head-text">
                <h3>Generate &amp; Export Report</h3>
                <p>Configure your report parameters and choose an output format</p>
            </div>
            <button class="eh-close" data-close-modal type="button" aria-label="Close">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 1l12 12M13 1L1 13"/></svg>
            </button>
        </div>

        <div class="eh-body">
            <form class="eh-form-grid" id="generateReportForm" action="../actions/generateReport.php" method="POST" target="_blank">

                <div class="eh-section-label">Report Type &amp; Scope</div>
                <div class="eh-row two-col">
                    <label class="eh-field">
                        <span>Report Type</span>
                        <select name="reportType" required>
                            <option value="">Select type…</option>
                            <option>Illness Trend Report</option>
                            <option>Visit Frequency by Grade</option>
                            <option>Medicine Usage Report</option>
                            <option>Enrollment Clearance Summary</option>
                            <option>Referral Log</option>
                        </select>
                    </label>
                    <label class="eh-field">
                        <span>Course</span>
                        <select name="courseId">
                            <option value="">All Courses</option>
                            <?php
                            $stmt = $conn->query("SELECT course_id, course_name FROM courses ORDER BY course_name ASC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="'.$row['course_id'].'">'.htmlspecialchars($row['course_name']).'</option>';
                            }
                            ?>
                        </select>
                    </label>
                    <label class="eh-field">
                        <span>Year Level</span>
                        <select name="yearLevel">
                            <option value="">All Years</option>
                            <?php
                            $stmt = $conn->query("SELECT year_id, year_name FROM year_levels ORDER BY year_id ASC");
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="'.$row['year_id'].'">'.htmlspecialchars($row['year_name']).'</option>';
                            }
                            ?>
                        </select>
                    </label>
                </div>

                <div class="eh-section-label">Date Range</div>
                <div class="eh-row two-col">
                    <label class="eh-field">
                        <span>From</span>
                        <input type="date" name="dateFrom" />
                    </label>
                    <label class="eh-field">
                        <span>To</span>
                        <input type="date" name="dateTo" />
                    </label>
                </div>

                <div class="eh-section-label">Output Format</div>
                <div class="eh-row">
                    <label class="eh-field full">
                        <span>Format</span>
                        <div class="eh-clearance-group">
                            <label class="eh-radio-card">
                                <input type="radio" name="exportFormat" value="pdf" checked />
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" style="width:14px;height:14px;"><path d="M3 1h7l4 4v10H3V1z"/></svg>
                                <span>PDF</span>
                            </label>
                            <label class="eh-radio-card">
                                <input type="radio" name="exportFormat" value="excel" />
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" style="width:14px;height:14px;"><rect x="1" y="1" width="14" height="14" rx="2"/><path d="M4 5l3 6M10 5l-3 6M1 11h14"/></svg>
                                <span>Excel</span>
                            </label>
                            <label class="eh-radio-card">
                                <input type="radio" name="exportFormat" value="print" />
                                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" style="width:14px;height:14px;"><path d="M4 2h8v2H4zM3 4h10v8H3z"/><path d="M5 8h6M5 11h4"/></svg>
                                <span>Print</span>
                            </label>
                        </div>
                    </label>
                </div>

            </form>
        </div>

        <div class="eh-foot">
            <button class="module-btn secondary" data-close-modal type="button">Cancel</button>
            <button class="module-btn" type="submit" form="generateReportForm">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v8M4 7l4 5 4-5M2 14h12"/></svg>
                Generate Report
            </button>
        </div>
    </div>
</div>
