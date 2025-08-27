<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<h2>Инициализация базы данных</h2>";
        
        // Читаем SQL файл
        $sql = file_get_contents('sql/init.sql');
        
        // Выполняем SQL команды
        $conn->exec($sql);
        
        echo "<div style='color: green;'>✓ База данных успешно инициализирована!</div>";
        echo "<div style='margin-top: 10px;'>";
        echo "<strong>Тестовые данные для входа:</strong><br>";
        echo "Логин: admin<br>";
        echo "Пароль: password<br>";
        echo "</div>";
        echo "<div style='margin-top: 10px;'>";
        echo "<a href='login.php'>Перейти к авторизации</a>";
        echo "</div>";
        
    } else {
        echo "<div style='color: red;'>Ошибка подключения к базе данных</div>";
    }
    
} catch(PDOException $e) {
    echo "<div style='color: red;'>Ошибка: " . $e->getMessage() . "</div>";
} 