USE school_clinic_db;

CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT(11) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (log_id),
    KEY fk_al_user (user_id),
    KEY timestamp (timestamp),
    KEY entity_type (entity_type),
    CONSTRAINT fk_al_user FOREIGN KEY (user_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
