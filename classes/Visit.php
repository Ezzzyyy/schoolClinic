<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

class Visit extends BaseModel {

    /**
     * Fetch all visits with joins for students, status, and handlers.
     */
    public function getAll(): array {
        $query = "SELECT cv.*, 
                         s.first_name as student_first, s.last_name as student_last, s.student_number,
                         vs.status_name,
                         u.first_name as handler_first, u.last_name as handler_last
                  FROM clinic_visits cv
                  JOIN students s ON cv.student_id = s.student_id
                  JOIN visit_status vs ON cv.visit_status = vs.status_id
                  JOIN users u ON cv.handled_by = u.user_id
                  ORDER BY cv.visit_date DESC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the most recent visits.
     */
    public function getRecent(int $limit = 5): array {
        $query = "SELECT cv.*, s.first_name, s.last_name, s.student_number, c.course_name, vs.status_name
                  FROM clinic_visits cv
                  JOIN students s ON cv.student_id = s.student_id
                  LEFT JOIN courses c ON s.course_id = c.course_id
                  JOIN visit_status vs ON cv.visit_status = vs.status_id
                  ORDER BY cv.visit_date DESC
                  LIMIT $limit";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get aggregate trends of common illnesses.
     */
    public function getIllnessTrends(int $limit = 5): array {
        $query = "SELECT complaint, COUNT(*) as count 
                  FROM clinic_visits 
                  GROUP BY complaint 
                  ORDER BY count DESC 
                  LIMIT $limit";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate visit-related statistics.
     */
    public function getStats(array $visits): array {
        $todayDate = date('Y-m-d');
        $stats = [
            'today' => 0,
            'pending' => 0,
            'completed' => 0,
            'referred' => 0
        ];

        foreach ($visits as $v) {
            if (substr((string)$v['visit_date'], 0, 10) === $todayDate) $stats['today']++;
            
            if ($v['status_name'] === 'Pending') $stats['pending']++;
            elseif ($v['status_name'] === 'Completed') $stats['completed']++;
            elseif ($v['status_name'] === 'Referred') $stats['referred']++;
        }

        return $stats;
    }

    /**
     * Get visits today count directly.
     */
    public function getTodayCount(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM clinic_visits WHERE DATE(visit_date) = CURDATE()")->fetchColumn();
    }

    /**
     * Create a new clinic visit record.
     */
    public function create(array $data) {
        $stmt = $this->db->prepare("
            INSERT INTO clinic_visits (
                student_id, visit_date, complaint, symptoms, 
                diagnosis, treatment, visit_status, handled_by, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $success = $stmt->execute([
            $data['student_id'],
            $data['visit_date'],
            $data['complaint'],
            $data['symptoms'],
            $data['diagnosis'],
            $data['treatment'],
            $data['visit_status'],
            $data['handled_by'],
            $data['notes'] ?? null
        ]);

        return $success ? (int)$this->db->lastInsertId() : false;
    }

    /**
     * Get weekly statistics for the dashboard snapshot.
     */
    public function getWeeklySnapshot(): array {
        $query = "SELECT 
                    YEARWEEK(visit_date, 1) as week_id,
                    DATE_FORMAT(MIN(visit_date), '%b %d') as week_start,
                    COUNT(*) as total_visits,
                    SUM(CASE WHEN visit_status = 3 THEN 1 ELSE 0 END) as referrals,
                    SUM(CASE WHEN LOWER(notes) LIKE '%follow-up%' 
                                OR LOWER(complaint) LIKE '%follow-up%' 
                                OR LOWER(treatment) LIKE '%follow-up%' THEN 1 ELSE 0 END) as follow_ups,
                    (SELECT complaint FROM clinic_visits cv2 
                     WHERE YEARWEEK(cv2.visit_date, 1) = YEARWEEK(cv.visit_date, 1)
                     GROUP BY complaint ORDER BY COUNT(*) DESC LIMIT 1) as top_complaint
                  FROM clinic_visits cv
                  WHERE visit_date >= DATE_SUB(NOW(), INTERVAL 6 WEEK)
                  GROUP BY week_id
                  ORDER BY week_id DESC
                  LIMIT 4";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get comparison trend for today vs yesterday.
     * Returns an array with current count and trend direction.
     */
    public function getTodayTrend(): array {
        $today = (int)$this->db->query("SELECT COUNT(*) FROM clinic_visits WHERE DATE(visit_date) = CURDATE()")->fetchColumn();
        $yesterday = (int)$this->db->query("SELECT COUNT(*) FROM clinic_visits WHERE DATE(visit_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)")->fetchColumn();
        
        $diff = $today - $yesterday;
        $trend = 'neutral';
        $label = 'active clinic';

        if ($diff > 0) {
            $trend = 'up';
            $label = '↑ more than yesterday';
        } elseif ($diff < 0) {
            $trend = 'down';
            $label = '↓ less than yesterday';
        } else {
            $label = 'same as yesterday';
        }

        return ['count' => $today, 'trend' => $trend, 'label' => $label];
    }

    /**
     * Fetch all users who are clinic staff (nurses/doctors) for filters.
     */
    public function getAllHandlers(): array {
        $query = "SELECT user_id, first_name, last_name, role FROM users WHERE role IN ('nurse', 'doctor') ORDER BY last_name ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch pending medical certificates (for Certificate Queue).
     */
    public function getCertificates(): array {
        $query = "SELECT mc.*, 
                         COALESCE(s_v.first_name, s_a.first_name) as student_first, 
                         COALESCE(s_v.last_name, s_a.last_name) as student_last,
                         COALESCE(s_v.student_number, s_a.student_number) as student_number,
                         COALESCE(cv.visit_date, ha.assessment_date) as visit_date,
                         CASE 
                            WHEN mc.assessment_id IS NOT NULL THEN 'Health Clearance'
                            ELSE 'Medical Clearance'
                         END as cert_type,
                         u.first_name as handler_first, u.last_name as handler_last
                  FROM medical_certificates mc
                  LEFT JOIN clinic_visits cv ON mc.visit_id = cv.visit_id
                  LEFT JOIN students s_v ON cv.student_id = s_v.student_id
                  LEFT JOIN health_assessments ha ON mc.assessment_id = ha.assessment_id
                  LEFT JOIN students s_a ON ha.student_id = s_a.student_id
                  JOIN users u ON mc.issued_by = u.user_id
                  WHERE mc.status = 'pending'
                  ORDER BY mc.date_issued DESC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch released medical certificates (for Cleared Students page).
     */
    public function getReleasedCertificates(): array {
        $query = "SELECT mc.*, 
                         COALESCE(s_v.first_name, s_a.first_name) as student_first, 
                         COALESCE(s_v.last_name, s_a.last_name) as student_last,
                         COALESCE(s_v.student_number, s_a.student_number) as student_number,
                         COALESCE(cv.visit_date, ha.assessment_date) as visit_date,
                         CASE 
                            WHEN mc.assessment_id IS NOT NULL THEN 'Health Clearance'
                            ELSE 'Medical Clearance'
                         END as cert_type,
                         u.first_name as handler_first, u.last_name as handler_last
                  FROM medical_certificates mc
                  LEFT JOIN clinic_visits cv ON mc.visit_id = cv.visit_id
                  LEFT JOIN students s_v ON cv.student_id = s_v.student_id
                  LEFT JOIN health_assessments ha ON mc.assessment_id = ha.assessment_id
                  LEFT JOIN students s_a ON ha.student_id = s_a.student_id
                  JOIN users u ON mc.issued_by = u.user_id
                  WHERE mc.status = 'released'
                  ORDER BY mc.date_issued DESC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Release a certificate (move from queue to cleared).
     */
    public function releaseCertificate(int $certId): bool {
        $stmt = $this->db->prepare("UPDATE medical_certificates SET status = 'released' WHERE certificate_id = ?");
        return $stmt->execute([$certId]);
    }

    /**
     * Get certificate queue KPI statistics.
     */
    public function getCertificateStats(): array {
        $today = date('Y-m-d');
        $row = $this->db->query("
            SELECT 
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_total,
                COUNT(CASE WHEN status = 'pending' AND date_issued = '$today' THEN 1 END) as issued_today,
                COUNT(CASE WHEN status = 'released' THEN 1 END) as released_total,
                COUNT(CASE WHEN status = 'released' AND date_issued = '$today' THEN 1 END) as released_today
            FROM medical_certificates
        ")->fetch(PDO::FETCH_ASSOC);

        return $row ?: ['pending_total' => 0, 'issued_today' => 0, 'released_total' => 0, 'released_today' => 0];
    }

    /**
     * Get medicines dispensed during a specific visit.
     */
    public function getMedicines(int $visitId): array {
        $query = "SELECT vm.quantity_given, m.name, m.unit
                  FROM visit_medicine vm
                  JOIN medicines m ON vm.medicine_id = m.medicine_id
                  WHERE vm.visit_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$visitId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
