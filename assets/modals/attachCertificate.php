<div class="eh-modal" id="attachCertModal" role="dialog" aria-modal="true" aria-label="Attach Certificate">
    <div class="eh-panel" style="max-width:560px;">

        <div class="eh-head">
            <div class="eh-head-icon" style="background:linear-gradient(135deg,#D1FAE5,#a7f3d0);color:#059669;">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 2h8l5 5v11H4V2z"/><path d="M12 2v5h5M8 11h5M8 14h3"/></svg>
            </div>
            <div class="eh-head-text">
                <h3>Attach Certificate</h3>
                <p>Upload a signed medical clearance document</p>
            </div>
            <button class="eh-close" data-close-modal type="button" aria-label="Close">
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 1l12 12M13 1L1 13"/></svg>
            </button>
        </div>

        <div class="eh-body">
            <form class="eh-form-grid" id="attachCertForm" enctype="multipart/form-data">
                <input type="hidden" name="certificate_id" id="ac_cert_id" />

                <label class="eh-field full">
                    <span>Certificate File</span>
                    <div class="eh-dropzone" id="certDropzone">
                        <div class="eh-dropzone-icon">
                            <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M7 27v5a1 1 0 001 1h24a1 1 0 001-1v-5M20 6v20M12 13l8-7 8 7"/></svg>
                        </div>
                        <p class="eh-dropzone-main">Drag &amp; drop your file here, or <label for="certFileInput" class="eh-dropzone-browse">browse</label></p>
                        <p class="eh-dropzone-hint">PDF, JPG, PNG — max 5 MB</p>
                        <input type="file" id="certFileInput" name="certFile" accept=".pdf,.jpg,.jpeg,.png" />
                    </div>
                    <div class="eh-file-preview" id="certFilePreview"></div>
                </label>

                <label class="eh-field full">
                    <span>Remarks <em style="font-style:normal;color:#9ca3af;font-size:10px;">(optional)</em></span>
                    <textarea rows="2" name="certNotes" placeholder="Notes about this certificate or any restrictions…"></textarea>
                </label>

            </form>
        </div>

        <div class="eh-foot">
            <button class="module-btn secondary" data-close-modal type="button">Cancel</button>
            <button class="module-btn" type="submit" form="attachCertForm">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 10v4h12v-4M8 2v8M5 6l3-4 3 4"/></svg>
                Attach &amp; Save
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    const inp = document.getElementById('certFileInput');
    const preview = document.getElementById('certFilePreview');
    inp?.addEventListener('change', () => {
        if (!inp.files?.[0]) { preview.innerHTML = ''; return; }
        preview.innerHTML = `
            <div class="eh-file-chip">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 1h7l4 4v10H3V1z"/><path d="M10 1v4h4"/></svg>
                <span>${inp.files[0].name}</span>
                <span class="eh-file-size">${(inp.files[0].size/1024).toFixed(0)} KB</span>
                <button type="button" class="eh-file-remove" onclick="this.closest('.eh-file-chip').remove(); document.getElementById('certFileInput').value='';">&times;</button>
            </div>`;
    });
})();
</script>
