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
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $department = trim($_POST['department']);
    $phone = trim($_POST['phone']);
    
    // Валидация
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Заполните все обязательные поля';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } else {
        try {
            $user = new User();
            
            // Проверка на существование email
            $user->email = $email;
            if ($user->emailExists()) {
                $error = 'Пользователь с таким email уже существует';
            } else {
                // Проверка на существование username
                $user->username = $username;
                if ($user->usernameExists()) {
                    $error = 'Пользователь с таким логином уже существует';
                } else {
                    // Регистрация
                    $user->username = $username;
                    $user->email = $email;
                    $user->password = $password;
                    $user->full_name = $full_name;
                    $user->position = $position;
                    $user->department = $department;
                    $user->phone = $phone;
                    
                    if ($user->register()) {
                        $success = 'Регистрация успешно завершена! Теперь вы можете войти в систему.';
                    } else {
                        $error = 'Ошибка при регистрации. Попробуйте еще раз.';
                    }
                }
            }
        } catch (Exception $e) {
            handleDatabaseException($e, 'register.php');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - DocFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .register-card {
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
        .btn-register {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
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
            <div class="col-md-8 col-lg-6">
                <div class="card register-card border-0">
                    <div class="card-body p-5">
                        <!-- Логотип -->
                        <div class="text-center mb-4">
                            <i class="fas fa-file-invoice fa-3x brand-logo mb-3"></i>
                            <h2 class="brand-logo fw-bold">DocFlow</h2>
                            <p class="text-muted">Регистрация в системе документооборота</p>
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

                        <!-- Форма регистрации -->
                        <?php if (!$success): ?>
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="username" name="username" 
                                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                               placeholder="Логин" required>
                                        <label for="username">
                                            <i class="fas fa-user me-2"></i>Логин *
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                               placeholder="Email" required>
                                        <label for="email">
                                            <i class="fas fa-envelope me-2"></i>Email *
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="password" name="password" 
                                               placeholder="Пароль" required>
                                        <label for="password">
                                            <i class="fas fa-lock me-2"></i>Пароль *
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               placeholder="Подтвердите пароль" required>
                                        <label for="confirm_password">
                                            <i class="fas fa-lock me-2"></i>Подтвердите пароль *
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                       placeholder="ФИО" required>
                                <label for="full_name">
                                    <i class="fas fa-id-card me-2"></i>ФИО *
                                </label>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="position" name="position" 
                                               value="<?php echo isset($_POST['position']) ? htmlspecialchars($_POST['position']) : ''; ?>"
                                               placeholder="Должность">
                                        <label for="position">
                                            <i class="fas fa-briefcase me-2"></i>Должность
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="department" name="department" 
                                               value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>"
                                               placeholder="Отдел">
                                        <label for="department">
                                            <i class="fas fa-building me-2"></i>Отдел
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                       placeholder="Телефон">
                                <label for="phone">
                                    <i class="fas fa-phone me-2"></i>Телефон
                                </label>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-register text-white">
                                    <i class="fas fa-user-plus me-2"></i>Зарегистрироваться
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>

                        <!-- Ссылки -->
                        <div class="text-center">
                            <p class="mb-0">
                                Уже есть аккаунт? 
                                <a href="login.php" class="text-decoration-none fw-semibold">
                                    Войти в систему
                                </a>
                            </p>
                        </div>

                        <!-- Требования -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <small class="text-muted">
                                <strong>Требования к паролю:</strong><br>
                                • Минимум 6 символов<br>
                                • Поля отмеченные * обязательны для заполнения
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Дополнительная информация -->
                <div class="text-center mt-4 text-white">
                    <p class="mb-1">
                        <i class="fas fa-shield-alt me-2"></i>
                        Безопасная регистрация
                    </p>
                    <small class="opacity-75">
                        Все данные защищены и используются только внутри системы
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Валидация формы -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            // Проверка совпадения паролей
            function validatePasswords() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Пароли не совпадают');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
            
            // Автофокус на первое поле
            document.getElementById('username').focus();
        });
    </script>
</body>
</html> 