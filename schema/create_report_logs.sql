CREATE TABLE IF NOT EXISTS report_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(100) NOT NULL,
    scope VARCHAR(255),
    generated_by INT,
    format ENUM('pdf', 'excel', 'print') DEFAULT 'pdf',
    record_count INT DEFAULT 0,
    status ENUM('Queued', 'Ready', 'Printed', 'Error') DEFAULT 'Ready',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(user_id)
);
