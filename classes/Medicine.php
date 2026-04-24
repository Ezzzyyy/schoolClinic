<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

class Medicine extends BaseModel {

    /**
     * Fetch all medicines sorted by name.
     */
    public function getAll(): array {
        return $this->db->query("SELECT * FROM medicines ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate inventory statistics and statuses.
     */
    public function getAnalytics(array &$medicines): array {
        $stats = [
            'total' => count($medicines),
            'low_stock' => 0,
            'near_expiry' => 0,
            'critical' => 0
        ];

        $threeMonthsSec = 90 * 24 * 60 * 60;
        $now = time();

        foreach ($medicines as &$m) {
            // Expiry check
            $expiryTime = strtotime((string)$m['expiration_date']);
            if ($expiryTime) {
                $daysUntilExpiry = ($expiryTime - $now) / (24 * 60 * 60);
                
                if ($daysUntilExpiry < 0) {
                    $m['expiry_status'] = 'Expired';
                    $stats['near_expiry']++; // Count expired as near expiry for the KPI
                } elseif ($daysUntilExpiry <= 90) {
                    $m['expiry_status'] = 'Near Expiry';
                    $stats['near_expiry']++;
                } else {
                    $m['expiry_status'] = 'Safe';
                }
            } else {
                $m['expiry_status'] = 'Safe'; // No expiry date = safe
            }

            // Status Logic
            $reorder = (int)$m['reorder_level'];
            $qty = (int)$m['quantity'];

            if ($qty <= 5 || $qty <= ($reorder * 0.2)) {
                $stats['critical']++;
                $m['display_status'] = 'Critical';
                $m['status_class'] = 'warn';
            } elseif ($qty <= $reorder) {
                $stats['low_stock']++;
                $m['display_status'] = 'Low';
                $m['status_class'] = 'pending';
            } else {
                $m['display_status'] = 'Healthy';
                $m['status_class'] = 'ok';
            }
        }

        return $stats;
    }

    /**
     * Deduct stock and log the usage for a clinic visit.
     */
    public function dispense(int $medicineId, int $quantity, int $handledBy, int $visitId): bool {
        try {
            $this->db->beginTransaction();

            // 1. Deduct from medicines table
            $stmt = $this->db->prepare("UPDATE medicines SET quantity = quantity - ? WHERE medicine_id = ?");
            $stmt->execute([$quantity, $medicineId]);

            // 2. Log in medicine_logs
            $stmt = $this->db->prepare("INSERT INTO medicine_logs (medicine_id, quantity, action_type, handled_by) VALUES (?, ?, 'out', ?)");
            $stmt->execute([$medicineId, $quantity, $handledBy]);

            // 3. Record in visit_medicine
            $stmt = $this->db->prepare("INSERT INTO visit_medicine (visit_id, medicine_id, quantity_given) VALUES (?, ?, ?)");
            $stmt->execute([$visitId, $medicineId, $quantity]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Dispense error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get count of low stock medicines based on individual reorder levels.
     */
    public function getLowStockCount(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM medicines WHERE quantity <= reorder_level")->fetchColumn();
    }

    /**
     * Get a snapshot of items with lowest quantity.
     */
    public function getLowestStock(int $limit = 5): array {
        return $this->db->query("SELECT * FROM medicines ORDER BY quantity ASC LIMIT $limit")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add a new medicine to inventory and log the initial stock.
     */
    public function add(array $data, int $handledBy): int {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(
                "INSERT INTO medicines (name, category, quantity, unit, reorder_level, expiration_date, location, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['name'],
                $data['category'],
                (int)$data['quantity'],
                $data['unit'],
                (int)($data['reorder_level'] ?? 10),
                $data['expiration_date'],
                $data['location'] ?? null,
                $data['notes'] ?? null
            ]);

            $newId = (int)$this->db->lastInsertId();

            // Log the initial stock entry
            if ((int)$data['quantity'] > 0) {
                $log = $this->db->prepare(
                    "INSERT INTO medicine_logs (medicine_id, quantity, action_type, handled_by) VALUES (?, ?, 'in', ?)"
                );
                $log->execute([$newId, (int)$data['quantity'], $handledBy]);
            }

            $this->db->commit();
            return $newId;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Medicine add error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update an existing medicine's details.
     */
    public function update(int $id, array $data): bool {
        try {
            $stmt = $this->db->prepare(
                "UPDATE medicines
                 SET name = ?, category = ?, quantity = ?, unit = ?,
                     reorder_level = ?, expiration_date = ?, location = ?, notes = ?
                 WHERE medicine_id = ?"
            );
            return $stmt->execute([
                $data['name'],
                $data['category'],
                (int)$data['quantity'],
                $data['unit'],
                (int)($data['reorder_level'] ?? 10),
                $data['expiration_date'],
                $data['location'] ?? null,
                $data['notes'] ?? null,
                $id
            ]);
        } catch (Exception $e) {
            error_log("Medicine update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restock a medicine: add quantity and log the movement.
     */
    public function restock(int $id, int $qty, int $handledBy): bool {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE medicines SET quantity = quantity + ? WHERE medicine_id = ?");
            $stmt->execute([$qty, $id]);

            $log = $this->db->prepare(
                "INSERT INTO medicine_logs (medicine_id, quantity, action_type, handled_by) VALUES (?, ?, 'in', ?)"
            );
            $log->execute([$id, $qty, $handledBy]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Medicine restock error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a medicine if it has no dispensing records.
     */
    public function delete(int $id): bool {
        // Check for references in visit_medicine
        $check = $this->db->prepare("SELECT COUNT(*) FROM visit_medicine WHERE medicine_id = ?");
        $check->execute([$id]);
        if ((int)$check->fetchColumn() > 0) {
            return false; // Cannot delete — has dispensing history
        }

        try {
            $this->db->beginTransaction();

            // Remove logs first
            $this->db->prepare("DELETE FROM medicine_logs WHERE medicine_id = ?")->execute([$id]);
            // Then remove the medicine
            $this->db->prepare("DELETE FROM medicines WHERE medicine_id = ?")->execute([$id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Medicine delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get a single medicine by ID.
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM medicines WHERE medicine_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
