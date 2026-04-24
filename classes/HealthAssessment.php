<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

class HealthAssessment extends BaseModel {

    private function ensureAttachmentColumns(): void {
        $columns = [
            'med_certificate' => "ALTER TABLE health_assessments ADD COLUMN med_certificate VARCHAR(255) DEFAULT NULL AFTER drug_test",
            'vaccination_card' => "ALTER TABLE health_assessments ADD COLUMN vaccination_card VARCHAR(255) DEFAULT NULL AFTER med_certificate",
        ];

        foreach ($columns as $column => $sql) {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM health_assessments LIKE ?");
            $stmt->execute([$column]);
            if (!$stmt || $stmt->fetchColumn() === false) {
                $this->db->exec($sql);
            }
        }
    }
    
    /**
     * Fetch an assessment record for a specific student.
     */
    public function getByStudentId(int $studentId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM health_assessments WHERE student_id = ?");
        $stmt->execute([$studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Create or update a health assessment record.
     */
    public function save(array $data): bool {
        $this->ensureAttachmentColumns();

        $existing = $this->getByStudentId((int)$data['student_id']);
        
        $fields = [
            'x_ray' => $data['x_ray'] ?? null,
            'urinalysis' => $data['urinalysis'] ?? null,
            'hematology' => $data['hematology'] ?? null,
            'drug_test' => $data['drug_test'] ?? null,
            'med_certificate' => $data['med_certificate'] ?? null,
            'vaccination_card' => $data['vaccination_card'] ?? null,
            'height' => $data['height'] ?? null,
            'weight' => $data['weight'] ?? null,
            'blood_pressure' => $data['blood_pressure'] ?? null,
            'pulse_rate' => $data['pulse_rate'] ?? null,
            'lab_remarks' => $data['lab_remarks'] ?? null,
            'clearance_status' => $data['clearance_status'] ?? 'pending',
            'assessment_date' => $data['assessment_date'] ?? date('Y-m-d'),
            'handled_by' => $data['handled_by'] ?? null
        ];

        if ($existing) {
            // Update
            $sql = "UPDATE health_assessments SET 
                        x_ray = COALESCE(?, x_ray),
                        urinalysis = COALESCE(?, urinalysis),
                        hematology = COALESCE(?, hematology),
                        drug_test = COALESCE(?, drug_test),
                        med_certificate = COALESCE(?, med_certificate),
                        vaccination_card = COALESCE(?, vaccination_card),
                        height = ?,
                        weight = ?,
                        blood_pressure = ?,
                        pulse_rate = ?,
                        lab_remarks = ?,
                        clearance_status = ?,
                        assessment_date = ?,
                        handled_by = COALESCE(?, handled_by)
                    WHERE student_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $fields['x_ray'], $fields['urinalysis'], $fields['hematology'], $fields['drug_test'],
                $fields['med_certificate'], $fields['vaccination_card'],
                $fields['height'], $fields['weight'], $fields['blood_pressure'], $fields['pulse_rate'],
                $fields['lab_remarks'], $fields['clearance_status'], $fields['assessment_date'],
                $fields['handled_by'], $data['student_id']
            ]);
        } else {
            // Insert
            $sql = "INSERT INTO health_assessments 
                    (student_id, x_ray, urinalysis, hematology, drug_test, med_certificate, vaccination_card, height, weight, blood_pressure, pulse_rate, lab_remarks, clearance_status, assessment_date, handled_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['student_id'], $fields['x_ray'], $fields['urinalysis'], $fields['hematology'], $fields['drug_test'],
                $fields['med_certificate'], $fields['vaccination_card'],
                $fields['height'], $fields['weight'], $fields['blood_pressure'], $fields['pulse_rate'],
                $fields['lab_remarks'], $fields['clearance_status'], $fields['assessment_date'], $fields['handled_by']
            ]);
        }
    }
}
