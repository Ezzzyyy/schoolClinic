-- ============================================================
-- ALTER: health_assessments — add clearance_status column
-- Run this ONCE against your school clinic database.
-- ============================================================

-- Add clearance_status column if it doesn't exist
ALTER TABLE health_assessments
  ADD COLUMN clearance_status ENUM('pending', 'cleared', 'rejected') DEFAULT 'pending' AFTER vaccination_card;
