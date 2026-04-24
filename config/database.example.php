<?php
class Database {
    // Database Configuration
    // Update these values with your actual database credentials
    public $host = "localhost";
    public $username = "root";
    public $pass = ""; // Your MySQL password
    public $dbname = "school_clinic_db";

    protected $conn;

    public function connect()
    {
        try {
            $this->conn = new PDO(
                "mysql:host=$this->host;dbname=$this->dbname",
                $this->username,
                $this->pass
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $this->conn;

        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
}
?>
