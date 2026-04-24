CREATE TABLE IF NOT EXISTS clinic_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Seed initial settings
INSERT IGNORE INTO clinic_settings (setting_key, setting_value) VALUES
('clinic_name', 'ClinIQ School Clinic'),
('school_year', '2026 – 2027'),
('clinic_contact', '+63 32 123 4567'),
('clinic_address', 'Main Building, Ground Floor, St. Mary''s Campus'),
('document_footer', 'This document is issued by the school clinic and is valid for internal compliance use only.'),
('primary_physician', 'Dr. Maria Reyes'),
('physician_license', '0123456'),
('head_nurse', 'Nurse Paula Santos'),
('nurse_license', 'RN-9876'),
('req_xray', 'required'),
('req_urinalysis', 'required'),
('req_cbc', 'required'),
('req_drug_test', 'optional'),
('req_med_cert', 'optional'),
('req_vaccination', 'optional'),
('session_timeout', '60'),
('max_login_attempts', '5'),
('two_factor_auth', 'disabled');
