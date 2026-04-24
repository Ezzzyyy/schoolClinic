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
        $query = "SELECT user_id, first_name, last_name, email, role, last_login, status FROM users ORDER BY last_name ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }
}
