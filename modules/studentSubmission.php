<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Student.php';
require_once __DIR__ . '/../classes/Settings.php';

$db = new Database();
$conn = $db->connect();
$studentModel = new Student($conn);
$settingsModel = new Settings($conn);
$settings = $settingsModel->getAll();

$requirementDefinitions = [
    ['id' => 'xray', 'key' => 'req_xray', 'label' => 'Chest X-ray', 'enabled_default' => true, 'required_default' => true],
    ['id' => 'urinalysis', 'key' => 'req_urinalysis', 'label' => 'Urinalysis', 'enabled_default' => true, 'required_default' => true],
    ['id' => 'hematology', 'key' => 'req_cbc', 'label' => 'Hematology / CBC', 'enabled_default' => true, 'required_default' => true],
    ['id' => 'drugTest', 'key' => 'req_drug_test', 'label' => 'Drug Test', 'enabled_default' => true, 'required_default' => false],
    ['id' => 'medCert', 'key' => 'req_med_cert', 'label' => 'Medical Certificate', 'enabled_default' => false, 'required_default' => false],
    ['id' => 'vaccination', 'key' => 'req_vaccination', 'label' => 'Vaccination Card', 'enabled_default' => false, 'required_default' => false],
];

$requirementConfig = [];
foreach ($requirementDefinitions as $definition) {
    $isEnabled = ($settings[$definition['key'] . '_enabled'] ?? ($definition['enabled_default'] ? '1' : '0')) === '1';
    $isRequired = ($settings[$definition['key']] ?? ($definition['required_default'] ? 'required' : 'optional')) === 'required';

    $requirementConfig[] = [
        'id' => $definition['id'],
        'label' => $definition['label'],
        'enabled' => $isEnabled,
        'required' => $isRequired,
    ];
}

$courses = $studentModel->getAllCourses();
$years   = $studentModel->getAllYearLevels();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Submission - ClinIQ</title>
    <meta name="description" content="Submit your personal information and health assessment documents for clinic enrollment clearance." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/studentSubmission.css" />
    <link rel="stylesheet" href="../includes/functions.php"> <!-- For e() function if needed, but I'll define it or use htmlspecialchars -->
</head>
<?php
// Simple escape helper if functions.php is not included or e() is missing
if (!function_exists('e')) {
    function e($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}
?>
<body>

    <div class="bg-blob blob-a"></div>
    <div class="bg-blob blob-b"></div>
    <div class="bg-blob blob-c"></div>

    <main class="submission-shell">
        <header class="submission-header">
            <a href="../login.php" class="back-link" aria-label="Back to login">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 3L5 8l5 5"/></svg>
                Back to Login
            </a>
            <div class="brand-wrap" aria-label="ClinIQ">
                <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                </svg>
                <span class="brand-text">Clin<span>IQ</span></span>
            </div>
        </header>

        <section class="hero-card">
            <p class="hero-kicker">Student Portal</p>
            <h1>Health Requirement Submission</h1>
            <p>Submit your personal details and upload required health assessment documents for clinic review.</p>
        </section>

        <form class="submission-form" id="submissionForm" novalidate>

            <!-- ── Personal Information ── -->
            <section class="form-card">
                <div class="section-head">
                    <h2>Personal Information</h2>
                    <p>Required details for profile matching and enrollment verification.</p>
                </div>

                <div class="form-grid name-grid">
                    <label class="field">
                        <span>First Name <em>*</em></span>
                        <input type="text" name="firstName" placeholder="Juan" required />
                    </label>
                    <label class="field">
                        <span>Middle Name</span>
                        <input type="text" name="middleName" placeholder="Santos (optional)" />
                    </label>
                    <label class="field">
                        <span>Last Name <em>*</em></span>
                        <input type="text" name="lastName" placeholder="Dela Cruz" required />
                    </label>
                    <label class="field suffix-field">
                        <span>Suffix</span>
                        <select name="suffix">
                            <option value="">None</option>
                            <option>Jr.</option>
                            <option>Sr.</option>
                            <option>II</option>
                            <option>III</option>
                            <option>IV</option>
                        </select>
                    </label>
                </div>

                <div class="form-grid two-col">
                    <label class="field">
                        <span>Student Number <em>*</em></span>
                        <input type="text" name="studentNo" placeholder="2026-1023" required />
                    </label>
                    <label class="field">
                        <span>Email Address <em>*</em></span>
                        <input type="email" name="email" placeholder="student@school.edu" required />
                    </label>

                    <label class="field">
                        <span>Course / Grade <em>*</em></span>
                        <select name="courseId" required>
                            <option value="">Select course</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= e($c['course_id']) ?>"><?= e($c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label class="field">
                        <span>Year Level <em>*</em></span>
                        <select name="yearId" required>
                            <option value="">Select year level</option>
                            <?php foreach ($years as $y): ?>
                                <option value="<?= e($y['year_id']) ?>"><?= e($y['year_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="field">
                        <span>Birthdate <em>*</em></span>
                        <input type="date" name="birthdate" required />
                    </label>
                    <label class="field">
                        <span>Gender <em>*</em></span>
                        <select name="gender" required>
                            <option value="">Select gender</option>
                            <option>Male</option>
                            <option>Female</option>
                            <option>Prefer not to say</option>
                        </select>
                    </label>

                    <label class="field">
                        <span>Contact Number <em>*</em></span>
                        <input type="tel" name="contactNo" placeholder="09xx-xxx-xxxx" required />
                    </label>
                    <label class="field">
                        <span>Emergency Contact <em>*</em></span>
                        <input type="text" name="emergency" placeholder="Name + mobile number" required />
                    </label>

                    <label class="field full">
                        <span>Home Address <em>*</em></span>
                        <textarea name="address" rows="2" placeholder="House no., street, barangay, city" required></textarea>
                    </label>
                </div>
            </section>

            <!-- ── Health Assessment Requirements ── -->
            <section class="form-card">
                <div class="section-head">
                    <h2>Health Assessment Requirements</h2>
                    <p>Upload the documents required by your clinic. Check each item to confirm you are attaching it.</p>
                </div>

                <!-- Dynamic requirements list -->
                <div class="req-list" id="reqList">
                    <!-- Requirements are rendered by JS — simulating what the clinic admin has enabled -->
                </div>

                <label class="field full" style="margin-top: 16px;">
                    <span>Medical Notes / Existing Conditions <em style="font-style:normal;color:#9ca3af;">(optional)</em></span>
                    <textarea name="healthNotes" rows="3" placeholder="Allergies, asthma, medications, or any other relevant conditions"></textarea>
                </label>
            </section>

            <div class="form-actions">
                <button type="button" class="btn secondary" onclick="window.location.href='../login.php'">Cancel</button>
                <button type="submit" class="btn primary">Submit Requirements</button>
            </div>

            <p class="form-msg" id="formMsg" aria-live="polite"></p>
        </form>
    </main>

<script>
const requirementConfig = <?= json_encode($requirementConfig, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

function renderRequirements(config) {
    const list = document.getElementById('reqList');
    if (!list) return;

    const enabled = config.filter(r => r.enabled);

    if (enabled.length === 0) {
        list.innerHTML = '<p style="color:#6b7280;font-size:13px;">No documents are currently required. Proceed to submit.</p>';
        return;
    }

    list.innerHTML = enabled.map(req => `
        <div class="req-item" id="req-wrap-${req.id}">
            <div class="req-header">
                <label class="req-check-label" for="req-check-${req.id}">
                    <input type="checkbox" id="req-check-${req.id}" class="req-toggle" data-target="${req.id}" />
                    <span class="req-checkmark"></span>
                    <span class="req-title">${req.label}</span>
                    ${req.required
                        ? '<span class="req-pill required">Required</span>'
                        : '<span class="req-pill optional">Optional</span>'}
                </label>
            </div>
            <div class="req-upload-area" id="req-upload-${req.id}" style="display:none;">
                <label class="upload-dropzone" for="file-${req.id}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12V4M8 8l4-4 4 4"/>
                    </svg>
                    <span>Click or drag to upload <strong>${req.label}</strong></span>
                    <span class="upload-hint">PDF, JPG, PNG — max 10 MB</span>
                    <input type="file" id="file-${req.id}" name="${req.id}"
                           accept=".pdf,.png,.jpg,.jpeg"
                           ${req.required ? 'required' : ''} />
                </label>
                <div class="upload-preview" id="preview-${req.id}"></div>
            </div>
        </div>
    `).join('');

    // Toggle upload area when checkbox is checked
    document.querySelectorAll('.req-toggle').forEach(chk => {
        chk.addEventListener('change', () => {
            const area = document.getElementById('req-upload-' + chk.dataset.target);
            if (area) area.style.display = chk.checked ? 'block' : 'none';
        });
    });

    // File preview
    document.querySelectorAll('[id^="file-"]').forEach(inp => {
        inp.addEventListener('change', () => {
            const previewId = 'preview-' + inp.id.replace('file-', '');
            const preview = document.getElementById(previewId);
            if (!preview) return;
            if (inp.files?.[0]) {
                preview.innerHTML = `
                    <div class="file-chip">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 1h5l4 4v10H4V1z"/><path d="M9 1v4h4"/></svg>
                        <span>${inp.files[0].name}</span>
                        <button type="button" class="file-remove" data-inp="${inp.id}">&times;</button>
                    </div>`;
                preview.querySelector('.file-remove')?.addEventListener('click', () => {
                    inp.value = '';
                    preview.innerHTML = '';
                });
            }
        });
    });
}

renderRequirements(requirementConfig);

// Form submission
document.getElementById('submissionForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const msg = document.getElementById('formMsg');
    const btn = this.querySelector('button[type="submit"]');

    if (!this.checkValidity()) {
        msg.textContent = 'Please complete all required fields and uploads.';
        msg.className = 'form-msg error';
        this.reportValidity();
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Submitting...';
    msg.textContent = 'Processing your submission...';
    msg.className = 'form-msg';

    try {
        const formData = new FormData(this);
        const response = await fetch('../actions/processSubmission.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            msg.textContent = result.message;
            msg.className = 'form-msg success';
            this.reset();
            // Reset dynamic fields
            document.querySelectorAll('.req-toggle').forEach(chk => chk.checked = false);
            document.querySelectorAll('.req-upload-area').forEach(area => area.style.display = 'none');
            document.querySelectorAll('[id^="preview-"]').forEach(p => p.innerHTML = '');
            
            // Scroll to message
            msg.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            msg.textContent = result.message;
            msg.className = 'form-msg error';
        }
    } catch (err) {
        msg.textContent = 'Connection error. Please try again.';
        msg.className = 'form-msg error';
    } finally {
        btn.disabled = false;
        btn.textContent = 'Submit Requirements';
    }
});
</script>

</body>
</html>
