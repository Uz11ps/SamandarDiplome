<?php
ob_start(); // Начинаем буферизацию вывода
session_start();
require_once 'classes/User.php';
require_once 'includes/error_handler.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $user = new User();
    $user->getUserById($_SESSION['user_id']);
} catch (Exception $e) {
    handleDatabaseException($e, 'profile.php load');
}

$error = '';
$success = '';

// Обработка обновления профиля
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $department = trim($_POST['department']);
    $phone = trim($_POST['phone']);
    
    if (empty($full_name)) {
        $error = 'ФИО не может быть пустым';
    } else {
        $user->full_name = $full_name;
        $user->position = $position;
        $user->department = $department;
        $user->phone = $phone;
        
        // Обработка загрузки аватара
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (in_array($_FILES['avatar']['type'], $allowed_types) && $_FILES['avatar']['size'] <= $max_size) {
                $upload_dir = 'assets/img/avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $new_filename = 'avatar_' . $user->id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    $user->avatar = 'avatars/' . $new_filename;
                }
            } else {
                $error = 'Неподдерживаемый формат файла или размер превышает 2MB';
            }
        }
        
        if (empty($error)) {
            try {
                if ($user->updateProfile()) {
                    $success = 'Профиль успешно обновлен';
                    $_SESSION['full_name'] = $user->full_name;
                } else {
                    $error = 'Ошибка при обновлении профиля';
                }
            } catch (Exception $e) {
                handleDatabaseException($e, 'profile.php update');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль пользователя - DocFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-file-invoice me-2"></i>
                DocFlow
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Главная
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documents.php">
                            <i class="fas fa-folder me-1"></i>Документы
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">
                            <i class="fas fa-upload me-1"></i>Загрузить
                        </a>
                    </li>
                    <?php if($user->role == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="fas fa-cog me-1"></i>Управление
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="assets/img/<?php echo $user->avatar; ?>" alt="Avatar" class="rounded-circle me-2" width="30" height="30">
                            <?php echo htmlspecialchars($user->full_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="profile.php">
                                <i class="fas fa-user me-2"></i>Профиль
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Выход
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Основной контент -->
    <div class="container-fluid mt-5 pt-3">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Заголовок -->
                <div class="d-flex align-items-center mb-4">
                    <a href="index.php" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="mb-0">
                        <i class="fas fa-user-circle me-2 text-primary"></i>
                        Профиль пользователя
                    </h2>
                </div>

                <!-- Карточка профиля -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-id-card me-2"></i>
                            Личная информация
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Сообщения -->
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

                        <!-- Форма профиля -->
                        <form method="POST" action="" enctype="multipart/form-data">
                            <!-- Аватар -->
                            <div class="text-center mb-4">
                                <div class="position-relative d-inline-block">
                                    <img src="assets/img/<?php echo $user->avatar; ?>" 
                                         alt="Avatar" 
                                         class="rounded-circle border border-3 border-primary" 
                                         width="120" 
                                         height="120"
                                         id="avatarPreview">
                                    <label for="avatar" class="position-absolute bottom-0 end-0 btn btn-primary btn-sm rounded-circle">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <input type="file" id="avatar" name="avatar" accept="image/*" class="d-none">
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">Нажмите на камеру для смены аватара</small>
                                </div>
                            </div>

                            <!-- Основная информация -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="username" 
                                               value="<?php echo htmlspecialchars($user->username); ?>" 
                                               disabled>
                                        <label for="username">
                                            <i class="fas fa-user me-2"></i>Логин
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="email" 
                                               value="<?php echo htmlspecialchars($user->email); ?>" 
                                               disabled>
                                        <label for="email">
                                            <i class="fas fa-envelope me-2"></i>Email
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user->full_name); ?>" 
                                       required>
                                <label for="full_name">
                                    <i class="fas fa-id-card me-2"></i>ФИО
                                </label>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="position" name="position" 
                                               value="<?php echo htmlspecialchars($user->position); ?>">
                                        <label for="position">
                                            <i class="fas fa-briefcase me-2"></i>Должность
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="department" name="department" 
                                               value="<?php echo htmlspecialchars($user->department); ?>">
                                        <label for="department">
                                            <i class="fas fa-building me-2"></i>Отдел
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($user->phone); ?>">
                                <label for="phone">
                                    <i class="fas fa-phone me-2"></i>Телефон
                                </label>
                            </div>

                            <!-- Системная информация -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="role" 
                                               value="<?php echo $user->role == 'admin' ? 'Администратор' : ($user->role == 'manager' ? 'Менеджер' : 'Сотрудник'); ?>" 
                                               disabled>
                                        <label for="role">
                                            <i class="fas fa-shield-alt me-2"></i>Роль
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="status" 
                                               value="<?php echo $user->status == 'active' ? 'Активен' : 'Неактивен'; ?>" 
                                               disabled>
                                        <label for="status">
                                            <i class="fas fa-circle me-2"></i>Статус
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Кнопки -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Сохранить изменения
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Дополнительная информация -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Дополнительная информация
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <i class="fas fa-calendar-alt fa-2x text-primary mb-2"></i>
                                <h6>Дата регистрации</h6>
                                <p class="text-muted mb-0"><?php echo date('d.m.Y', strtotime($user->created_at ?? 'now')); ?></p>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-clock fa-2x text-success mb-2"></i>
                                <h6>Последнее обновление</h6>
                                <p class="text-muted mb-0"><?php echo date('d.m.Y H:i', strtotime($user->updated_at ?? 'now')); ?></p>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-shield-check fa-2x text-warning mb-2"></i>
                                <h6>Уровень доступа</h6>
                                <p class="text-muted mb-0">
                                    <?php echo $user->role == 'admin' ? 'Полный' : ($user->role == 'manager' ? 'Расширенный' : 'Базовый'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Предварительный просмотр аватара -->
    <script>
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html> 