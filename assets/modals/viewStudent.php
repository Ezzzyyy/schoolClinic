<?php
// assets/modals/studentRecord.php
// Student record review modal — viewed by doctor/admin
// Populated dynamically via JS (or PHP if passed $student data)
?>

<div class="modal-backdrop" id="studentRecordModal" role="dialog" aria-modal="true" aria-labelledby="modalStudentName">

  <div class="modal-panel">

    <!-- Modal header -->
    <div class="modal-header">
      <div class="modal-student-identity">
        <div class="modal-avatar" id="modalAvatar" style="--av-color:#6C63FF">JD</div>
        <div>
          <h2 class="modal-student-name" id="modalStudentName">Juan Dela Cruz</h2>
          <p class="modal-student-meta" id="modalStudentMeta">2024-1001 · BSIT / 2nd Year · Male · 19 yrs</p>
        </div>
      </div>
      <button class="modal-close-btn" type="button" onclick="closeModal()" aria-label="Close modal">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3l10 10M13 3L3 13"/></svg>
      </button>
    </div>

    <!-- Modal tabs -->
    <div class="modal-tabs" role="tablist">
      <button class="modal-tab active" role="tab" data-tab="profile" onclick="switchTab(this, 'profile')">Profile</button>
      <button class="modal-tab" role="tab" data-tab="assessments" onclick="switchTab(this, 'assessments')">Health Assessments</button>
      <button class="modal-tab" role="tab" data-tab="visits" onclick="switchTab(this, 'visits')">Visit History</button>
    </div>

    <div class="modal-body">

      <!-- TAB: Profile -->
      <div class="modal-tab-panel active" id="tab-profile">
        <div class="modal-info-grid">
          <div class="modal-info-section">
            <h3 class="modal-section-label">Personal Information</h3>
            <div class="info-rows">
              <div class="info-row">
                <span class="info-key">Full name</span>
                <span class="info-val" id="modalFullName">---</span>
              </div>
              <div class="info-row">
                <span class="info-key">Student No.</span>
                <span class="info-val mono" id="modalStudentNo">---</span>
              </div>
              <div class="info-row">
                <span class="info-key">Gender</span>
                <span class="info-val" id="modalGender">---</span>
              </div>
              <div class="info-row">
                <span class="info-key">Date of birth</span>
                <span class="info-val" id="modalBirthDate">---</span>
              </div>
              <div class="info-row">
                <span class="info-key">Address</span>
                <span class="info-val" id="modalAddress">---</span>
              </div>
              <div class="info-row">
                <span class="info-key">Email</span>
                <span class="info-val" id="modalEmail">---</span>
              </div>
              <div class="info-row">
                <span class="info-key">Contact No.</span>
                <span class="info-val" id="modalContactNo">---</span>
              </div>
              <div class="info-row">
                <span class="info-key">Emergency contact</span>
                <span class="info-val" id="modalEmergencyContact">---</span>
              </div>
            </div>
          </div>

          <div class="modal-info-section">
            <h3 class="modal-section-label">Academic</h3>
            <div class="info-rows">
              <div class="info-row">
                <span class="info-key">Course</span>
                <span class="info-val" id="modalCourse">---</span>
              </div>
              <div class="info-row">
                <span class="info-key">Year level</span>
                <span class="info-val" id="modalYear">---</span>
              </div>
            </div>

            <h3 class="modal-section-label" style="margin-top:1.4rem">Health Notes</h3>
            <div class="info-rows">
              <div class="info-row" style="flex-direction:column; align-items:flex-start; gap:8px;">
                <p class="info-val" id="modalHealthNotes" style="font-size:0.9rem; line-height:1.5; color:var(--text-main);">---</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: Health Assessments -->
      <div class="modal-tab-panel" id="tab-assessments">
        <div class="assessments-list">

          <div id="assessmentContainer">
            <!-- Assessment items injected by JS -->
          </div>

        </div>

        <!-- Approval section -->
        <div class="approval-section">
          <h3 class="modal-section-label">Assessment Approval</h3>
          <p class="approval-note">One or more documents are still missing. You may still approve with remarks.</p>
          <label class="form-field full-width">
            <span>Remarks / Notes</span>
            <textarea id="modalRemarks" rows="3" placeholder="Enter any remarks for this student's health assessment…"></textarea>
          </label>
          <div class="approval-actions">
            <button id="btnApprove" class="approval-btn approve" type="button">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 8l3.5 3.5L13 5"/></svg>
              Approve
            </button>
            <button id="btnConditional" class="approval-btn conditional" type="button">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 4v4M8 11v.5"/></svg>
              Conditional
            </button>
            <button id="btnReject" class="approval-btn reject" type="button">
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4l8 8M12 4l-8 8"/></svg>
              Request resubmission
            </button>
          </div>
        </div>
      </div>

      <!-- TAB: Visit History -->
      <div class="modal-tab-panel" id="tab-visits">
        <div class="visit-timeline" id="visitTimelineContainer">
          <!-- Visit items injected by JS -->
          <p class="empty-state">No visit history available for this student.</p>
        </div>
      </div>

    </div><!-- end modal-body -->

    <!-- Modal footer -->
    <div class="modal-footer">
      <span class="modal-footer-status">
        <span class="status-dot ok"></span>
        Active profile
      </span>
      <div class="modal-footer-actions">
        <button class="toolbar-button secondary" type="button" onclick="closeModal()">Close</button>
        <button class="toolbar-button" type="button">Save changes</button>
      </div>
    </div>

  </div>
</div>

<script>
function switchTab(btn, tabId) {
  // Deactivate all tabs and panels
  document.querySelectorAll('.modal-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.modal-tab-panel').forEach(p => p.classList.remove('active'));
  // Activate selected
  btn.classList.add('active');
  document.getElementById('tab-' + tabId)?.classList.add('active');

  // Load visit history when visits tab is clicked
  if (tabId === 'visits' && typeof currentStudentId !== 'undefined' && currentStudentId) {
    loadVisitHistory(currentStudentId);
  }
}

async function loadVisitHistory(studentId) {
  const container = document.getElementById('visitTimelineContainer');
  if (!container) return;

  container.innerHTML = '<p style="text-align:center; padding:20px; color:#6b7280;">Loading visit history...</p>';
  container.style.padding = '16px';

  try {
    const response = await fetch(`../actions/getStudentVisits.php?student_id=${studentId}`);
    const data = await response.json();

    if (!data.success || !data.visits || data.visits.length === 0) {
      container.innerHTML = '<p class="empty-state" style="text-align:center; padding:20px; color:#6b7280;">No visit history available for this student.</p>';
      return;
    }

    container.innerHTML = '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); gap:14px;">' + data.visits.map(v => `
      <div style="border:1px solid #e2e8f0; border-radius:12px; background:#ffffff; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
          <div style="font-size:12px; font-weight:600; color:#1f2937;">${v.visit_date}</div>
          <span style="font-size:11px; font-weight:700; padding:4px 10px; border-radius:12px; text-transform:capitalize; background:${v.status.toLowerCase() === 'pending' ? '#fef3c7' : v.status.toLowerCase() === 'completed' ? '#dcfce7' : '#fee2e2'}; color:${v.status.toLowerCase() === 'pending' ? '#92400e' : v.status.toLowerCase() === 'completed' ? '#166534' : '#991b1b'};">${v.status}</span>
        </div>
        <div style="padding:12px 14px;">
          <div style="margin-bottom:10px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Complaint</div>
            <div style="font-size:13px; color:#1f2937; line-height:1.4;">${v.complaint}</div>
          </div>
          <div style="margin-bottom:10px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Diagnosis</div>
            <div style="font-size:13px; color:#1f2937; line-height:1.4;">${v.diagnosis}</div>
          </div>
          ${v.symptoms ? `
          <div style="margin-bottom:10px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Symptoms</div>
            <div style="font-size:13px; color:#1f2937; line-height:1.4;">${v.symptoms}</div>
          </div>` : ''}
          ${v.treatment ? `
          <div style="margin-bottom:10px;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Treatment</div>
            <div style="font-size:13px; color:#1f2937; line-height:1.4;">${v.treatment}</div>
          </div>` : ''}
          ${v.notes ? `
          <div style="margin-bottom:0;">
            <div style="font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Notes</div>
            <div style="font-size:13px; color:#1f2937; line-height:1.4;">${v.notes}</div>
          </div>` : ''}
        </div>
        <div style="padding:10px 14px; border-top:1px solid #e2e8f0; background:#f8fafc;">
          <div style="font-size:11px; color:#6b7280;">Handled by: ${v.handler}</div>
        </div>
      </div>
    `).join('') + '</div>';
  } catch (error) {
    container.innerHTML = '<p style="text-align:center; padding:20px; color:#ef4444;">Error loading visit history. Please try again.</p>';
  }
}
</script>