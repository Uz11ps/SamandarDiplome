<?php
ob_start(); // Начинаем буферизацию вывода
session_start();
require_once 'classes/User.php';
require_once 'includes/error_handler.php';

$error = '';
$success = '';

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        try {
            $user = new User();
            if ($user->login($username, $password)) {
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                $_SESSION['full_name'] = $user->full_name;
                $_SESSION['role'] = $user->role;
                
                header("Location: index.php");
                exit();
            } else {
                $error = 'Неверный логин или пароль';
            }
        } catch (Exception $e) {
            handleDatabaseException($e, 'login.php');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - DocFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .brand-logo {
            background: linear-gradient(45deg, #007bff, #0056b3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .btn-login {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 123, 255, 0.3);
        }
        .form-floating input:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.15);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card border-0">
                    <div class="card-body p-5">
                        <!-- Логотип -->
                        <div class="text-center mb-4">
                            <i class="fas fa-file-invoice fa-3x brand-logo mb-3"></i>
                            <h2 class="brand-logo fw-bold">DocFlow</h2>
                            <p class="text-muted">Система документооборота</p>
                        </div>

                        <!-- Сообщения об ошибках/успехе -->
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($success); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <!-- Форма входа -->
                        <form method="POST" action="">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Логин или email" required>
                                <label for="username">
                                    <i class="fas fa-user me-2"></i>Логин или email
                                </label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Пароль" required>
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Пароль
                                </label>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-login text-white">
                                    <i class="fas fa-sign-in-alt me-2"></i>Войти в систему
                                </button>
                            </div>
                        </form>

                        <!-- Ссылки -->
                        <div class="text-center">
                            <p class="mb-0">
                                Нет аккаунта? 
                                <a href="register.php" class="text-decoration-none fw-semibold">
                                    Зарегистрироваться
                                </a>
                            </p>
                        </div>

                        <!-- Тестовые данные -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <small class="text-muted">
                                <strong>Тестовые данные:</strong><br>
                                Логин: admin<br>
                                Пароль: password
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Дополнительная информация -->
                <div class="text-center mt-4 text-white">
                    <p class="mb-1">
                        <i class="fas fa-shield-alt me-2"></i>
                        Безопасная авторизация
                    </p>
                    <small class="opacity-75">
                        Система документооборота организации
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Автофокус на поле логина -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html> 