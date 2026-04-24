<?php
require_once 'config/database.php';

try {
    $db = new Database();
    $conn = $db->connect();

    if ($conn) {
        echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #10B981; background: #ECFDF5; color: #065F46; border-radius: 8px;'>";
        echo "<h2>✅ Connected Successfully!</h2>";
        
        // Simple query to test data
        $stmt = $conn->query("SELECT COUNT(*) as student_count FROM students");
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        $count = $data['student_count'] ?? 0;
        
        echo "<p>The connection to <strong>{$db->dbname}</strong> is active.</p>";
        echo "<p><strong>Data Check:</strong> Found <strong>$count</strong> students in the database.</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='font-family: sans-serif; padding: 20px; border: 1px solid #EF4444; background: #FEF2F2; color: #991B1B; border-radius: 8px;'>";
    echo "<h2>❌ Connection Failed</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr><p><strong>Troubleshooting:</strong><br>1. Open XAMPP and start MySQL.<br>2. Go to PHPMyAdmin and create a database named <strong>school_clinic_db</strong>.<br>3. Import your <strong>schema/school-clinic.sql</strong> file.</p>";
    echo "</div>";
}
?>
