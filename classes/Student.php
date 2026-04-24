<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

class Student extends BaseModel {
    
    /**
     * Fetch all students with related course and year information.
     */
    public function getAll(): array {
        $query = "SELECT s.*, c.course_name, y.year_name, 
                         ha.x_ray, ha.urinalysis, ha.hematology, ha.drug_test, ha.assessment_date,
                         ha.height, ha.weight, ha.blood_pressure, ha.pulse_rate, ha.lab_remarks, ha.clearance_status
                  FROM students s
                  LEFT JOIN courses c ON s.course_id = c.course_id
                  LEFT JOIN year_levels y ON s.year_id = y.year_id
                  LEFT JOIN health_assessments ha ON s.student_id = ha.student_id
                  ORDER BY s.last_name ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate registration statistics.
     */
    public function getStats(array $students): array {
        $stats = [
            'total' => count($students),
            'active' => 0,
            'pending' => 0,
            'inactive' => 0
        ];

        foreach ($students as $s) {
            if ($s['status'] === 'Active') $stats['active']++;
            elseif ($s['status'] === 'Pending review') $stats['pending']++;
            elseif ($s['status'] === 'Inactive') $stats['inactive']++;
        }

        return $stats;
    }

    /**
     * Get total count of students on file.
     */
    public function getCount(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    }

    /**
     * Get count of active students.
     */
    public function getActiveCount(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM students WHERE status = 'Active'")->fetchColumn();
    }

    /**
     * Helper to calculate age from birth date
     */
    public static function calculateAge(?string $birthDate): string {
        if (!$birthDate) return 'N/A';
        try {
            $today = new DateTime();
            $diff = $today->diff(new DateTime($birthDate));
            return (string)$diff->y;
        } catch (Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Get summary of enrollment health assessments.
     */
    public function getAssessmentSummary(): array {
        $query = "SELECT 
                    COUNT(CASE WHEN s.status = 'Active' THEN 1 END) as cleared,
                    COUNT(CASE WHEN s.status = 'Pending review' THEN 1 END) as pending,
                    COUNT(CASE WHEN ha.assessment_id IS NULL THEN 1 END) as not_assessed,
                    COUNT(CASE WHEN s.status = 'Pending review' AND ha.assessment_id IS NOT NULL THEN 1 END) as conditional
                  FROM students s
                  LEFT JOIN health_assessments ha ON s.student_id = ha.student_id";
        return $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update an existing student or create a new one based on student number.
     * Returns the student_id.
     */
    public function upsert(array $data): int {
        $existing = $this->db->prepare("SELECT student_id FROM students WHERE student_number = ?");
        $existing->execute([$data['student_number']]);
        $id = $existing->fetchColumn();

        if ($id) {
            $sql = "UPDATE students SET 
                        first_name = ?, last_name = ?, middle_name = ?, 
                        gender = ?, birth_date = ?, contact_number = ?, 
                        address = ?, emergency_contact = ?, email = ?, 
                        health_notes = ?, course_id = ?, year_id = ?,
                        status = 'Pending review'
                    WHERE student_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['first_name'], $data['last_name'], $data['middle_name'] ?? null,
                $data['gender'], $data['birth_date'], $data['contact_number'],
                $data['address'], $data['emergency_contact'], $data['email'],
                $data['health_notes'] ?? null, 
                $data['course_id'] ?? null, $data['year_id'] ?? null,
                (int)$id
            ]);
            return (int)$id;
        } else {
            $sql = "INSERT INTO students (
                        student_number, first_name, last_name, middle_name, 
                        gender, birth_date, contact_number, address, 
                        emergency_contact, email, health_notes, 
                        course_id, year_id, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending review')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['student_number'], $data['first_name'], $data['last_name'], $data['middle_name'] ?? null,
                $data['gender'], $data['birth_date'], $data['contact_number'],
                $data['address'], $data['emergency_contact'], $data['email'],
                $data['health_notes'] ?? null,
                $data['course_id'] ?? null, $data['year_id'] ?? null
            ]);
            return (int)$this->db->lastInsertId();
        }
    }

    /**
     * Get registration trend (this month vs last month).
     */
    public function getRegistrationTrend(): array {
        $thisMonth = (int)$this->db->query("SELECT COUNT(*) FROM students WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())")->fetchColumn();
        $lastMonth = (int)$this->db->query("SELECT COUNT(*) FROM students WHERE MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
        
        $total = $this->getCount();
        $diff = $thisMonth - $lastMonth;
        $trend = 'neutral';
        $label = 'registered students';

        if ($diff > 0) {
            $trend = 'up';
            $label = "↑ $thisMonth new this month";
        } elseif ($diff < 0) {
            $trend = 'down';
            $label = "↓ fewer than last month";
        }

        return ['count' => $total, 'trend' => $trend, 'label' => $label];
    }

    /**
     * Fetch all courses for filters.
     */
    public function getAllCourses(): array {
        return $this->db->query("SELECT * FROM courses ORDER BY course_name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all year levels for filters.
     */
    public function getAllYearLevels(): array {
        return $this->db->query("SELECT * FROM year_levels ORDER BY year_id ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update student status and health notes (remarks).
     */
    public function updateStatus(int $studentId, string $status, ?string $remarks = null): bool {
        $sql = "UPDATE students SET status = ?, health_notes = ? WHERE student_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $remarks, $studentId]);
    }
}
