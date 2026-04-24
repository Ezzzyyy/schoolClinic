<?php
declare(strict_types=1);

class BaseModel {
    protected $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }
}
