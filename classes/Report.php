<?php
declare(strict_types=1);
require_once __DIR__ . '/BaseModel.php';

class Report extends BaseModel {

    /**
     * Get Medicine Inventory data for reports.
     */
    public function getMedicineInventory(): array {
        $query = "SELECT m.*, m.quantity as stock_level,
                         (SELECT COUNT(*) FROM visit_medicine vm WHERE vm.medicine_id = m.medicine_id) as dispensing_count,
                         (SELECT SUM(quantity_given) FROM visit_medicine vm WHERE vm.medicine_id = m.medicine_id) as total_dispensed
                  FROM medicines m
                  ORDER BY m.name ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Enrollment Health Clearance data for reports.
     */
    public function getEnrollmentClearance(int $courseId = null, int $yearLevel = null): array {
        $where = [];
        $params = [];
        if ($courseId) { $where[] = "s.course_id = ?"; $params[] = $courseId; }
        if ($yearLevel) { $where[] = "s.year_id = ?"; $params[] = $yearLevel; }
        
        $whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "SELECT s.student_number, s.first_name, s.last_name, c.course_name, y.year_name,
                         ha.clearance_status, ha.assessment_date, u.first_name as handler_first, u.last_name as handler_last
                  FROM students s
                  JOIN courses c ON s.course_id = c.course_id
                  JOIN year_levels y ON s.year_id = y.year_id
                  LEFT JOIN health_assessments ha ON s.student_id = ha.student_id
                  LEFT JOIN users u ON ha.handled_by = u.user_id
                  $whereSql
                  ORDER BY y.year_id ASC, s.last_name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get Clinic Visit Logs for reports.
     */
    public function getVisitLogs(string $startDate = null, string $endDate = null, int $courseId = null, int $yearLevel = null): array {
        $where = [];
        $params = [];
        if ($startDate && $endDate) {
            $where[] = "cv.visit_date BETWEEN ? AND ?";
            $params[] = $startDate . ' 00:00:00';
            $params[] = $endDate . ' 23:59:59';
        }
        if ($courseId) { $where[] = "s.course_id = ?"; $params[] = $courseId; }
        if ($yearLevel) { $where[] = "s.year_id = ?"; $params[] = $yearLevel; }

        $whereSql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "SELECT cv.*, s.student_number, s.first_name, s.last_name, vs.status_name,
                         u.first_name as handler_first, u.last_name as handler_last
                  FROM clinic_visits cv
                  JOIN students s ON cv.student_id = s.student_id
                  JOIN visit_status vs ON cv.visit_status = vs.status_id
                  JOIN users u ON cv.handled_by = u.user_id
                  $whereSql
                  ORDER BY cv.visit_date DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get aggregate trends of common illnesses.
     */
    public function getIllnessTrends(): array {
        $query = "SELECT complaint as illness, COUNT(*) as case_count,
                         MIN(visit_date) as first_seen, MAX(visit_date) as last_seen
                  FROM clinic_visits 
                  GROUP BY complaint 
                  ORDER BY case_count DESC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get visit frequency by grade level.
     */
    public function getVisitFrequency(): array {
        $query = "SELECT y.year_name, COUNT(cv.visit_id) as visit_count,
                         COUNT(DISTINCT s.student_id) as unique_students
                  FROM year_levels y
                  LEFT JOIN students s ON y.year_id = s.year_id
                  LEFT JOIN clinic_visits cv ON s.student_id = cv.student_id
                  GROUP BY y.year_id, y.year_name
                  ORDER BY y.year_id ASC";
        return $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Log a generated report into the database.
     */
    public function logReport(string $type, string $scope, int $userId, string $format, int $count): bool {
        $stmt = $this->db->prepare("INSERT INTO report_logs (report_type, scope, generated_by, format, record_count, status) VALUES (?, ?, ?, ?, ?, 'Ready')");
        return $stmt->execute([$type, $scope, $userId, $format, $count]);
    }

    /**
     * Get recent report logs.
     */
    public function getRecentLogs(int $limit = 10): array {
        $query = "SELECT rl.*, u.first_name, u.last_name 
                  FROM report_logs rl
                  JOIN users u ON rl.generated_by = u.user_id
                  ORDER BY rl.created_at DESC
                  LIMIT :limit";
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get summary stats for reports dashboard.
     */
    public function getReportSummary(): array {
        return [
            'total_medicines' => (int)$this->db->query("SELECT COUNT(*) FROM medicines")->fetchColumn(),
            'total_visits' => (int)$this->db->query("SELECT COUNT(*) FROM clinic_visits")->fetchColumn(),
            'cleared_students' => (int)$this->db->query("SELECT COUNT(*) FROM health_assessments WHERE clearance_status = 'cleared'")->fetchColumn(),
            'pending_assessments' => (int)$this->db->query("SELECT COUNT(*) FROM students s LEFT JOIN health_assessments ha ON s.student_id = ha.student_id WHERE ha.assessment_id IS NULL OR ha.clearance_status = 'pending'")->fetchColumn()
        ];
    }
}
