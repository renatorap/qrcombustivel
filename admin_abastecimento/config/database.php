<?php
require_once 'config.php';

class Database {
    private $connection;
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $db = DB_NAME;

    public function connect() {
        // Disable mysqli exceptions so callers can handle prepare() failures gracefully
        mysqli_report(MYSQLI_REPORT_OFF);
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->db);

        if ($this->connection->connect_error) {
            die('Erro de conexão: ' . $this->connection->connect_error);
        }

        $this->connection->set_charset("utf8mb4");
        return $this->connection;
    }

    public function query($sql) {
        $result = $this->connection->query($sql);
        return $result;
    }

    public function prepare($sql) {
        // Wrap prepare to avoid throwing exceptions when SQL references missing columns
        try {
            $stmt = $this->connection->prepare($sql);
        } catch (\Throwable $e) {
            // Return false on failure; caller should handle fallback
            return false;
        }
        return $stmt;
    }

    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    public function getError() {
        return $this->connection->error;
    }
    
    public function affectedRows() {
        return $this->connection->affected_rows;
    }
    
    public function getConnection() {
        if (!$this->connection) {
            $this->connect();
        }
        return $this->connection;
    }

    public function close() {
        $this->connection->close();
    }
}

$db = new Database();
$db->connect();
?>