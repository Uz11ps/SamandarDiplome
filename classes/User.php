<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $position;
    public $department;
    public $phone;
    public $avatar;
    public $role;
    public $status;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Проверяем подключение к базе данных
        if ($this->conn === null) {
            throw new Exception("Не удалось подключиться к базе данных");
        }
    }

    // Регистрация пользователя
    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET username=:username, email=:email, password=:password, 
                      full_name=:full_name, position=:position, department=:department, phone=:phone";

        $stmt = $this->conn->prepare($query);

        // Хэширование пароля
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Привязка значений
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":position", $this->position);
        $stmt->bindParam(":department", $this->department);
        $stmt->bindParam(":phone", $this->phone);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Авторизация пользователя
    public function login($username, $password) {
        $query = "SELECT id, username, email, password, full_name, position, department, phone, avatar, role, status 
                  FROM " . $this->table_name . " 
                  WHERE (username = :username OR email = :username) AND status = 'active'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->full_name = $row['full_name'];
                $this->position = $row['position'];
                $this->department = $row['department'];
                $this->phone = $row['phone'];
                $this->avatar = $row['avatar'];
                $this->role = $row['role'];
                $this->status = $row['status'];
                return true;
            }
        }
        return false;
    }

    // Получение пользователя по ID
    public function getUserById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            $this->position = $row['position'];
            $this->department = $row['department'];
            $this->phone = $row['phone'];
            $this->avatar = $row['avatar'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    // Обновление профиля
    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " 
                  SET full_name=:full_name, position=:position, department=:department, 
                      phone=:phone, avatar=:avatar 
                  WHERE id=:id";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":position", $this->position);
        $stmt->bindParam(":department", $this->department);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":avatar", $this->avatar);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Проверка существования email
    public function emailExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Проверка существования username
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    // Получение всех пользователей
    public function getAllUsers() {
        $query = "SELECT id, username, email, full_name, position, department, role, status, created_at 
                  FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Получение общего количества пользователей
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Получение количества активных пользователей
    public function getActiveCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Получение количества пользователей по ролям
    public function getCountByRole() {
        $query = "SELECT role, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  GROUP BY role";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['role']] = $row['count'];
        }
        return $result;
    }

    // Обновление роли пользователя
    public function updateRole($user_id, $new_role) {
        $query = "UPDATE " . $this->table_name . " SET role = :role WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":role", $new_role);
        $stmt->bindParam(":id", $user_id);
        
        return $stmt->execute();
    }

    // Обновление статуса пользователя
    public function updateStatus($user_id, $new_status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $new_status);
        $stmt->bindParam(":id", $user_id);
        
        return $stmt->execute();
    }
} 