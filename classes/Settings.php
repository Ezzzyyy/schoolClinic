<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

class Settings extends BaseModel {

    /**
     * Get all settings as an associative array.
     */
    public function getAll(): array {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM clinic_settings");
        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $results[$row['setting_key']] = $row['setting_value'];
        }
        return $results;
    }

    /**
     * Get a specific setting value.
     */
    public function get(string $key, $default = null): ?string {
        $stmt = $this->db->prepare("SELECT setting_value FROM clinic_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? (string)$val : $default;
    }

    /**
     * Save multiple settings.
     */
    public function saveMultiple(array $data): bool {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO clinic_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            foreach ($data as $key => $val) {
                $stmt->execute([$key, $val]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get all users for the settings table.
     */
    public function getUsers(): array {
        $this->ensureLastLoginColumn();
        $this->ensureStatusColumn();

        $hasLastLogin = false;
        $hasStatus = false;

        $colStmt = $this->db->query("SHOW COLUMNS FROM users");
        if ($colStmt) {
            foreach ($colStmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                if (($col['Field'] ?? '') === 'last_login') {
                    $hasLastLogin = true;
                }
                if (($col['Field'] ?? '') === 'status') {
                    $hasStatus = true;
                }
            }
        }

        $lastLoginExpr = $hasLastLogin ? 'last_login' : 'NULL AS last_login';
        $statusExpr = $hasStatus ? 'status' : "'Active' AS status";

        $query = "SELECT user_id, first_name, last_name, email, role, {$lastLoginExpr}, {$statusExpr} FROM users ORDER BY last_name ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ensure last_login column exists in users table.
     */
    private function ensureLastLoginColumn(): void {
        $colStmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'last_login'");
        if ($colStmt && $colStmt->fetch()) {
            return;
        }
        $this->db->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL AFTER email");
        // Set current timestamp for existing users
        $this->db->exec("UPDATE users SET last_login = NOW() WHERE last_login IS NULL");
    }

    /**
     * Ensure status column exists in users table.
     */
    private function ensureStatusColumn(): void {
        $colStmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'status'");
        if ($colStmt && $colStmt->fetch()) {
            return;
        }
        $this->db->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER role");
    }
}
