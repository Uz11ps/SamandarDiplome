<?php
require_once 'config/database.php';

class Document {
    private $conn;
    private $table_name = "documents";

    public $id;
    public $title;
    public $description;
    public $file_name;
    public $file_path;
    public $file_size;
    public $file_type;
    public $creator_id;
    public $status;
    public $priority;
    public $category;
    public $tags;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Проверяем подключение к базе данных
        if ($this->conn === null) {
            throw new Exception("Не удалось подключиться к базе данных");
        }
    }

    // Создание документа
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET title=:title, description=:description, file_name=:file_name, 
                      file_path=:file_path, file_size=:file_size, file_type=:file_type,
                      creator_id=:creator_id, priority=:priority, category=:category, tags=:tags";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":file_name", $this->file_name);
        $stmt->bindParam(":file_path", $this->file_path);
        $stmt->bindParam(":file_size", $this->file_size);
        $stmt->bindParam(":file_type", $this->file_type);
        $stmt->bindParam(":creator_id", $this->creator_id);
        $stmt->bindParam(":priority", $this->priority);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":tags", $this->tags);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Получение всех документов
    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT d.*, u.full_name as creator_name 
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.creator_id = u.id
                  ORDER BY d.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }

    // Получение документа по ID
    public function getById($id) {
        $query = "SELECT d.*, u.full_name as creator_name, u.position as creator_position
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.creator_id = u.id
                  WHERE d.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->title = $row['title'];
            $this->description = $row['description'];
            $this->file_name = $row['file_name'];
            $this->file_path = $row['file_path'];
            $this->file_size = $row['file_size'];
            $this->file_type = $row['file_type'];
            $this->creator_id = $row['creator_id'];
            $this->status = $row['status'];
            $this->priority = $row['priority'];
            $this->category = $row['category'];
            $this->tags = $row['tags'];
            return $row;
        }
        return false;
    }

    // Обновление статуса документа
    public function updateStatus($status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $this->id);
        
        return $stmt->execute();
    }

    // Поиск документов
    public function search($keyword, $category = '', $status = '') {
        $query = "SELECT d.*, u.full_name as creator_name 
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.creator_id = u.id
                  WHERE (d.title LIKE :keyword OR d.description LIKE :keyword OR d.tags LIKE :keyword)";
        
        if($category) {
            $query .= " AND d.category = :category";
        }
        if($status) {
            $query .= " AND d.status = :status";
        }
        
        $query .= " ORDER BY d.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $keyword = "%{$keyword}%";
        $stmt->bindParam(":keyword", $keyword);
        
        if($category) {
            $stmt->bindParam(":category", $category);
        }
        if($status) {
            $stmt->bindParam(":status", $status);
        }

        $stmt->execute();
        return $stmt;
    }

    // Получение документов пользователя
    public function getByUserId($user_id) {
        $query = "SELECT d.*, u.full_name as creator_name 
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.creator_id = u.id
                  WHERE d.creator_id = :user_id
                  ORDER BY d.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt;
    }

    // Добавление записи в историю документа
    public function addHistory($document_id, $user_id, $action, $comment = '') {
        $query = "INSERT INTO document_history 
                  SET document_id=:document_id, user_id=:user_id, action=:action, comment=:comment";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":document_id", $document_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":comment", $comment);

        return $stmt->execute();
    }

    // Получение истории документа
    public function getHistory($document_id) {
        $query = "SELECT h.*, u.full_name as user_name 
                  FROM document_history h
                  LEFT JOIN users u ON h.user_id = u.id
                  WHERE h.document_id = :document_id
                  ORDER BY h.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":document_id", $document_id);
        $stmt->execute();

        return $stmt;
    }

    // Статистика документов
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as drafts,
                    SUM(CASE WHEN status = 'review' THEN 1 ELSE 0 END) as in_review,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                  FROM " . $this->table_name;

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Получение общего количества документов
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    // Получение количества документов по статусам
    public function getCountByStatus() {
        $query = "SELECT status, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  GROUP BY status";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['status']] = $row['count'];
        }
        return $result;
    }

    // Получение количества документов по категориям
    public function getCountByCategory() {
        $query = "SELECT category, COUNT(*) as count 
                  FROM " . $this->table_name . " 
                  GROUP BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row['category']] = $row['count'];
        }
        return $result;
    }

    // Получение последних документов
    public function getRecent($limit = 5) {
        $query = "SELECT d.*, u.full_name as creator_name 
                  FROM " . $this->table_name . " d
                  LEFT JOIN users u ON d.creator_id = u.id
                  ORDER BY d.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt;
    }
} 