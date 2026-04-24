-- ============================================================
-- ALTER: medical_certificates — add assessment_id, status, 
-- file_path, remarks columns if they don't exist.
-- Run this ONCE against the school_clinic database.
-- ============================================================

-- Add assessment_id FK (already referenced in code)
ALTER TABLE medical_certificates
  ADD COLUMN assessment_id INT NULL AFTER visit_id,
  ADD CONSTRAINT fk_mc_assessment FOREIGN KEY (assessment_id) REFERENCES health_assessments(assessment_id);

-- Make visit_id nullable (certificates can come from assessments, not just visits)
ALTER TABLE medical_certificates MODIFY visit_id INT NULL;

-- Add status workflow column
ALTER TABLE medical_certificates
  ADD COLUMN status ENUM('pending','released') NOT NULL DEFAULT 'pending';

-- Add file attachment and remarks
ALTER TABLE medical_certificates
  ADD COLUMN file_path VARCHAR(255) NULL,
  ADD COLUMN remarks TEXT NULL;

-- Update existing seed certificates to 'released' (they were already issued)
UPDATE medical_certificates SET status = 'released' WHERE certificate_id > 0;
