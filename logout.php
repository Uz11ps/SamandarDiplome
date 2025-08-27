<?php
ob_start(); // Начинаем буферизацию вывода
session_start();

// Очистка всех переменных сессии
$_SESSION = array();

// Удаление cookie сессии
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Уничтожение сессии
session_destroy();

// Перенаправление на страницу входа
header("Location: login.php");
exit();