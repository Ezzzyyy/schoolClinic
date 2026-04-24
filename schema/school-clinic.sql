-- ============================================================
-- SCHOOL CLINIC MANAGEMENT SYSTEM
-- SQL Schema
-- ============================================================

-- 1. Lookup / reference tables first (no dependencies)

CREATE TABLE year_levels (
    year_id     INT AUTO_INCREMENT PRIMARY KEY,
    year_name   VARCHAR(50) NOT NULL
);

CREATE TABLE courses (
    course_id   INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL
);

CREATE TABLE visit_status (
    status_id   INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(50) NOT NULL
);

-- 2. Users (staff: nurse, doctor, etc.)

CREATE TABLE users (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    first_name  VARCHAR(50)  NOT NULL,
    middle_name VARCHAR(50),
    last_name   VARCHAR(50)  NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('nurse', 'doctor') NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE password_resets (
    email      VARCHAR(100) NOT NULL,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (email),
    INDEX (token)
);

-- 3. Students

CREATE TABLE students (
    student_id        INT AUTO_INCREMENT PRIMARY KEY,
    student_number    VARCHAR(20)  NOT NULL UNIQUE,
    first_name        VARCHAR(50)  NOT NULL,
    middle_name       VARCHAR(50),
    last_name         VARCHAR(50)  NOT NULL,
    gender            ENUM('Male', 'Female', 'Other') NOT NULL,
    birth_date        DATE,
    contact_number    VARCHAR(20),
    address           TEXT,
    course_id         INT,
    year_id           INT,
    emergency_contact VARCHAR(100),
    email             VARCHAR(100),
    health_notes      TEXT,
    status            ENUM('Active', 'Pending review', 'Inactive') NOT NULL DEFAULT 'Pending review',
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_students_course FOREIGN KEY (course_id) REFERENCES courses(course_id),
    CONSTRAINT fk_students_year   FOREIGN KEY (year_id)   REFERENCES year_levels(year_id)
);

-- 4. Health Assessments

CREATE TABLE health_assessments (
    assessment_id   INT AUTO_INCREMENT PRIMARY KEY,
    student_id      INT          NOT NULL,
    x_ray           VARCHAR(255),               -- file path
    urinalysis      VARCHAR(255),               -- file path
    hematology      VARCHAR(255),               -- file path
    drug_test       VARCHAR(255),               -- file path
    med_certificate VARCHAR(255),               -- file path
    vaccination_card VARCHAR(255),              -- file path
    assessment_date DATE         NOT NULL,
    handled_by      INT          NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_ha_student    FOREIGN KEY (student_id) REFERENCES students(student_id),
    CONSTRAINT fk_ha_handled_by FOREIGN KEY (handled_by) REFERENCES users(user_id)
);

-- 5. Clinic Visits

CREATE TABLE clinic_visits (
    visit_id     INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT      NOT NULL,
    visit_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    complaint    TEXT,
    symptoms     TEXT,
    diagnosis    TEXT,
    treatment    TEXT,
    visit_status INT,
    handled_by   INT,
    notes        TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_cv_student      FOREIGN KEY (student_id)   REFERENCES students(student_id),
    CONSTRAINT fk_cv_visit_status FOREIGN KEY (visit_status) REFERENCES visit_status(status_id),
    CONSTRAINT fk_cv_handled_by   FOREIGN KEY (handled_by)   REFERENCES users(user_id)
);

-- 6. Medical Certificates

CREATE TABLE medical_certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    visit_id       INT  NOT NULL,
    issued_by      INT  NOT NULL,
    date_issued    DATE NOT NULL,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_mc_visit     FOREIGN KEY (visit_id)  REFERENCES clinic_visits(visit_id),
    CONSTRAINT fk_mc_issued_by FOREIGN KEY (issued_by) REFERENCES users(user_id)
);

-- 7. Medicines

CREATE TABLE medicines (
    medicine_id     INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    category        VARCHAR(50)  NOT NULL,
    description     TEXT,
    quantity        INT          NOT NULL DEFAULT 0,
    unit            VARCHAR(30),
    reorder_level   INT          DEFAULT 10,
    location        VARCHAR(100),
    notes           TEXT,
    expiration_date DATE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 8. Visit Medicine (medicines dispensed per visit)

CREATE TABLE visit_medicine (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    visit_id       INT NOT NULL,
    medicine_id    INT NOT NULL,
    quantity_given INT NOT NULL DEFAULT 1,

    CONSTRAINT fk_vm_visit    FOREIGN KEY (visit_id)    REFERENCES clinic_visits(visit_id),
    CONSTRAINT fk_vm_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id)
);

-- 9. Medicine Logs (inventory in/out tracking)

CREATE TABLE medicine_logs (
    log_id      INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT              NOT NULL,
    quantity    INT              NOT NULL,
    action_type ENUM('in','out') NOT NULL,
    date        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    handled_by  INT              NOT NULL,

    CONSTRAINT fk_ml_medicine   FOREIGN KEY (medicine_id) REFERENCES medicines(medicine_id),
    CONSTRAINT fk_ml_handled_by FOREIGN KEY (handled_by)  REFERENCES users(user_id)
);

-- ============================================================
-- INITIAL DATA / SEEDS
-- ============================================================

-- Year Levels
INSERT INTO year_levels (year_name) VALUES 
('1st Year'), 
('2nd Year'), 
('3rd Year'), 
('4th Year'), 
('5th Year');

-- Courses (20+)
INSERT INTO courses (course_name) VALUES 
('BS Information Technology'),
('BS Computer Science'),
('BS Nursing'),
('BS Business Administration'),
('BS Accountancy'),
('BS Civil Engineering'),
('BS Mechanical Engineering'),
('BS Electrical Engineering'),
('BS Architecture'),
('BS Psychology'),
('BS Criminology'),
('BS Pharmacy'),
('BS Medical Technology'),
('BS Biology'),
('BS Hospitality Management'),
('BS Tourism Management'),
('AB Communication'),
('AB Political Science'),
('AB English Language'),
('Bachelor of Elementary Education'),
('Bachelor of Secondary Education');

-- Visit Statuses
INSERT INTO visit_status (status_name) VALUES 
('Pending'), 
('Completed'), 
('Referred'), 
('Cancelled');

-- Users (Staff)
INSERT INTO users (first_name, last_name, email, username, password, role) VALUES 
('Paula', 'Gomez', 'paula@example.com', 'nurse_paula', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'nurse'),
('Antonio', 'Reyes', 'antonio@example.com', 'dr_reyes', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor');

-- Students
INSERT INTO students (student_number, first_name, last_name, gender, birth_date, course_id, year_id, address, status) VALUES 
('2024-1001', 'Juan', 'Dela Cruz', 'Male', '2005-05-15', 1, 2, 'Manila, Philippines', 'Active'),
('2024-1002', 'Ana', 'Lim', 'Female', '2006-08-20', 3, 1, 'Quezon City, Philippines', 'Pending review'),
('2023-0911', 'Ramon', 'Santos', 'Male', '2004-03-10', 4, 3, 'Makati, Philippines', 'Inactive'),
('2024-1099', 'Karl', 'Bautista', 'Male', '2007-01-25', 2, 1, 'Pasig, Philippines', 'Active'),
('2022-0850', 'Maria', 'Clara', 'Female', '2003-11-30', 5, 4, 'Taguig, Philippines', 'Active');

-- Health Assessments
INSERT INTO health_assessments (student_id, x_ray, urinalysis, hematology, assessment_date, handled_by) VALUES 
(1, 'uploads/xray/2024-1001.jpg', 'uploads/uri/2024-1001.pdf', 'uploads/hem/2024-1001.pdf', '2024-04-01', 1),
(2, 'uploads/xray/2024-1002.jpg', NULL, 'uploads/hem/2024-1002.pdf', '2024-04-05', 1),
(3, 'uploads/xray/2023-0911.jpg', 'uploads/uri/2023-0911.pdf', 'uploads/hem/2023-0911.pdf', '2024-04-10', 1),
(4, NULL, NULL, NULL, '2024-04-12', 1),
(5, 'uploads/xray/2022-0850.jpg', 'uploads/uri/2022-0850.pdf', 'uploads/hem/2022-0850.pdf', '2024-04-15', 1);

-- Clinic Visits
INSERT INTO clinic_visits (student_id, complaint, symptoms, diagnosis, treatment, visit_status, handled_by) VALUES 
(1, 'Headache', 'Dizziness, fatigue', 'Tension headache', 'Hydration + rest', 2, 2),
(2, 'Abdominal pain', 'Nausea, mild cramping', 'Gastritis', 'Antacid provided', 1, 1),
(3, 'Fever', '38.6°C, chills', 'Acute viral syndrome', 'Paracetamol + sent home', 3, 2),
(4, 'Cough', 'Sore throat, dry cough', 'Upper resp. irritation', 'Cough syrup advice', 2, 1),
(5, 'Sprained Ankle', 'Swelling, pain on movement', 'Grade 1 sprain', 'Ice pack + elastic bandage', 2, 2);

-- Medical Certificates
INSERT INTO medical_certificates (visit_id, issued_by, date_issued) VALUES 
(1, 2, '2026-04-15'),
(3, 2, '2026-04-14'),
(5, 2, '2026-04-19'),
(2, 1, '2026-04-15'),
(4, 1, '2026-04-14');

-- Medicines
INSERT INTO medicines (name, category, description, quantity, unit, reorder_level, location, notes, expiration_date) VALUES 
('Paracetamol 500mg', 'Analgesic', 'Pain reliever and fever reducer', 120, 'tabs', 60, 'Cabinet A, Shelf 1', '', '2026-10-15'),
('Amoxicillin 250mg', 'Antibiotic', 'Antibacterial medication', 40, 'caps', 80, 'Cabinet B, Shelf 2', 'Prescription required', '2026-07-20'),
('Cetirizine 10mg', 'Antihistamine', 'Allergy medication', 18, 'tabs', 50, 'Cabinet A, Shelf 3', '', '2026-05-12'),
('Mefenamic Acid 500mg', 'Analgesic', 'Nonsteroidal anti-inflammatory drug', 230, 'tabs', 100, 'Cabinet A, Shelf 2', '', '2026-12-01'),
('Betadine Solution', 'First Aid', 'Antiseptic solution', 3, 'bottles', 10, 'First Aid Cabinet', '', '2026-08-30');

-- Visit Medicine (Dispensed)
INSERT INTO visit_medicine (visit_id, medicine_id, quantity_given) VALUES 
(1, 1, 2),
(2, 5, 3),
(3, 1, 4),
(4, 4, 1),
(5, 3, 2);

-- Medicine Logs (Inventory movement)
INSERT INTO medicine_logs (medicine_id, quantity, action_type, handled_by) VALUES 
(1, 1000, 'in', 1),
(2, 500, 'in', 1),
(3, 300, 'in', 1),
(4, 150, 'in', 1),
(5, 250, 'in', 1);

