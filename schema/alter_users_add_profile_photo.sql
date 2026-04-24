USE school_clinic_db;

ALTER TABLE users
    ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL AFTER email;
