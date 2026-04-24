-- ============================================================
-- ALTER: medical_certificates — add certificate composition fields
-- Run this ONCE against your school clinic database.
-- ============================================================

-- Add certificate composition fields
ALTER TABLE medical_certificates
  ADD COLUMN diagnosis TEXT NULL AFTER date_issued,
  ADD COLUMN recommendations TEXT NULL AFTER diagnosis,
  ADD COLUMN restrictions VARCHAR(255) NULL AFTER recommendations,
  ADD COLUMN issuing_doctor VARCHAR(100) NULL AFTER restrictions,
  ADD COLUMN template VARCHAR(50) NULL DEFAULT 'A' AFTER issuing_doctor;
