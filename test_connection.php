<?php
ob_start();
require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Тест подключения - DocFlow</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
    <div class='container mt-5'>
        <div class='row justify-content-center'>
            <div class='col-md-6'>
                <div class='card'>
                    <div class='card-header'>
                        <h5 class='mb-0'>Тест подключения к базе данных</h5>
                    </div>
                    <div class='card-body'>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<div class='alert alert-success'>
                <i class='fas fa-check-circle'></i> 
                Подключение к базе данных успешно!
              </div>";
        
        // Проверяем существование таблиц
        $tables = ['users', 'documents', 'document_history', 'document_assignments'];
        echo "<h6>Проверка таблиц:</h6><ul class='list-group'>";
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SHOW TABLES LIKE :table");
            $stmt->bindParam(':table', $table);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                        $table 
                        <span class='badge bg-success'>✓</span>
                      </li>";
            } else {
                echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                        $table 
                        <span class='badge bg-danger'>✗</span>
                      </li>";
            }
        }
        echo "</ul>";
        
        echo "<div class='mt-3'>
                <a href='init_db.php' class='btn btn-primary'>Инициализировать БД</a>
                <a href='login.php' class='btn btn-success'>Перейти к входу</a>
              </div>";
        
    } else {
        echo "<div class='alert alert-danger'>
                <i class='fas fa-times-circle'></i> 
                Ошибка подключения к базе данных!
              </div>";
    }
    
} catch(Exception $e) {
    echo "<div class='alert alert-danger'>
            <i class='fas fa-exclamation-triangle'></i> 
            Ошибка: " . htmlspecialchars($e->getMessage()) . "
          </div>";
}

echo "          </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
ob_end_flush(); 