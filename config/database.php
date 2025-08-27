<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'u3145131_default';
    private $username = 'u3145131_default';
    private $password = 'DKjJS7mmku90hdB2';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            // Логируем ошибку без вывода в браузер
            error_log("Database connection error: " . $exception->getMessage());
            // Возвращаем null вместо echo
            return null;
        }
        return $this->conn;
    }
} 