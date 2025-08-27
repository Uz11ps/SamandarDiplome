<?php
// Скрипт для создания тестового администратора
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'classes/User.php';
require_once 'includes/error_handler.php';

try {
    // Проверяем, существует ли уже администратор с логином admin
    $user = new User();
    $user->username = 'admin';
    
    if ($user->usernameExists()) {
        echo "<p>Пользователь с логином 'admin' уже существует в базе данных.</p>";
        echo "<p>Обновляем пароль и статус для тестового администратора...</p>";
        
        // Получаем ID пользователя с логином admin
        $query = "SELECT id FROM users WHERE username = 'admin'";
        $stmt = $user->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $admin_id = $result['id'];
        
        // Обновляем пароль для тестового администратора
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $query = "UPDATE users SET password = :password, status = 'active', role = 'admin', avatar = 'default-avatar.svg' WHERE id = :id";
        $stmt = $user->conn->prepare($query);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $admin_id);
        
        if ($stmt->execute()) {
            echo "<p>Пароль и статус тестового администратора успешно обновлены.</p>";
            echo "<p>Логин: admin</p>";
            echo "<p>Пароль: password</p>";
            echo "<p>Теперь вы можете <a href='login.php'>войти в систему</a>.</p>";
        } else {
            echo "<p>Ошибка при обновлении пароля.</p>";
        }
    } else {
        // Создаем нового администратора
        $user->username = 'admin';
        $user->email = 'admin@docflow.local';
        $user->password = 'password'; // Будет хэшироваться в методе register()
        $user->full_name = 'Администратор системы';
        $user->position = 'Администратор';
        $user->department = 'IT-отдел';
        $user->phone = '12345678';
        $user->avatar = 'default-avatar.svg';
        $user->role = 'admin';
        $user->status = 'active';
        
        // Вручную устанавливаем роль и статус, так как они не включены в метод register
        if ($user->register()) {
            // Обновляем роль и статус
            $query = "UPDATE users SET role = 'admin', status = 'active', avatar = 'default-avatar.svg' WHERE username = 'admin'";
            $stmt = $user->conn->prepare($query);
            
            if ($stmt->execute()) {
                echo "<p>Тестовый администратор успешно создан.</p>";
                echo "<p>Логин: admin</p>";
                echo "<p>Пароль: password</p>";
                echo "<p>Теперь вы можете <a href='login.php'>войти в систему</a>.</p>";
            } else {
                echo "<p>Ошибка при установке роли администратора.</p>";
            }
        } else {
            echo "<p>Ошибка при создании тестового администратора.</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>Ошибка: " . $e->getMessage() . "</p>";
}
?> 