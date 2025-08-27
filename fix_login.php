<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

echo "<h1>Исправление проблемы с логином admin</h1>";

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if (!$conn) {
        echo "<p>Ошибка подключения к базе данных.</p>";
        exit();
    }
    
    echo "<p>Соединение с базой данных установлено.</p>";
    
    // Проверяем существование пользователя admin
    $stmt = $conn->prepare("SELECT id, username, password, status, role FROM users WHERE username = 'admin'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p>Пользователь admin найден. Обновляем пароль и статус...</p>";
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Текущие данные пользователя admin:</p>";
        echo "<ul>";
        echo "<li>ID: " . $row['id'] . "</li>";
        echo "<li>Логин: " . $row['username'] . "</li>";
        echo "<li>Статус: " . $row['status'] . "</li>";
        echo "<li>Роль: " . $row['role'] . "</li>";
        echo "</ul>";
        
        // Обновляем пароль и статус
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = :password, status = 'active', role = 'admin', avatar = 'default-avatar.svg' WHERE username = 'admin'";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":password", $hashed_password);
        
        if ($stmt->execute()) {
            echo "<p>Пароль и статус тестового администратора успешно обновлены.</p>";
            echo "<p>Пароль: password</p>";
            echo "<p>Хэш пароля: " . $hashed_password . "</p>";
            echo "<p>Теперь вы можете <a href='login.php'>войти в систему</a> с логином <strong>admin</strong> и паролем <strong>password</strong>.</p>";
        } else {
            echo "<p>Ошибка при обновлении пароля.</p>";
        }
    } else {
        echo "<p>Пользователь admin не найден. Создаем нового администратора...</p>";
        
        // Создаем нового пользователя
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password, full_name, position, department, phone, avatar, role, status) 
                  VALUES ('admin', 'admin@docflow.local', :password, 'Администратор системы', 'Администратор', 'IT-отдел', '12345678', 'default-avatar.svg', 'admin', 'active')";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(":password", $hashed_password);
        
        if ($stmt->execute()) {
            echo "<p>Тестовый администратор успешно создан.</p>";
            echo "<p>Логин: admin</p>";
            echo "<p>Пароль: password</p>";
            echo "<p>Хэш пароля: " . $hashed_password . "</p>";
            echo "<p>Теперь вы можете <a href='login.php'>войти в систему</a>.</p>";
        } else {
            echo "<p>Ошибка при создании администратора.</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p>Ошибка базы данных: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p>Общая ошибка: " . $e->getMessage() . "</p>";
}
?> 