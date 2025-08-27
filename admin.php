<?php
ob_start();
session_start();
require_once 'classes/User.php';
require_once 'classes/Document.php';
require_once 'includes/error_handler.php';

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $user = new User();
    $user->getUserById($_SESSION['user_id']);
    
    if ($user->role !== 'admin') {
        header("Location: index.php?error=access_denied");
        exit();
    }
    
} catch (Exception $e) {
    handleDatabaseException($e, 'admin.php');
}

$error = '';
$success = '';

// Обработка действий администратора
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_user_role'])) {
        $user_id = $_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        try {
            $admin_user = new User();
            if ($admin_user->updateRole($user_id, $new_role)) {
                $success = 'Роль пользователя успешно обновлена';
            } else {
                $error = 'Ошибка при обновлении роли пользователя';
            }
        } catch (Exception $e) {
            $error = 'Ошибка при работе с базой данных';
        }
    }
    
    if (isset($_POST['toggle_user_status'])) {
        $user_id = $_POST['user_id'];
        $new_status = $_POST['new_status'];
        
        try {
            $admin_user = new User();
            if ($admin_user->updateStatus($user_id, $new_status)) {
                $success = 'Статус пользователя успешно изменен';
            } else {
                $error = 'Ошибка при изменении статуса пользователя';
            }
        } catch (Exception $e) {
            $error = 'Ошибка при работе с базой данных';
        }
    }
}

// Получение статистики
try {
    $document = new Document();
    $admin_user = new User();
    
    // Статистика документов
    $total_documents = $document->getTotalCount();
    $documents_by_status = $document->getCountByStatus();
    $documents_by_category = $document->getCountByCategory();
    $recent_documents = $document->getRecent(5);
    
    // Статистика пользователей
    $total_users = $admin_user->getTotalCount();
    $users_by_role = $admin_user->getCountByRole();
    $active_users = $admin_user->getActiveCount();
    $all_users = $admin_user->getAllUsers();
    
} catch (Exception $e) {
    $error = 'Ошибка при загрузке статистики';
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - DocFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <li class="nav-item">
                        <a class="nav-link active" href="admin.php">
                            <i class="fas fa-cog me-1"></i>Управление
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="assets/img/<?php echo $user->avatar; ?>" alt="Avatar" class="rounded-circle me-2" width="30" height="30">
                            <?php echo htmlspecialchars($user->full_name); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php">
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
        <!-- Заголовок -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-shield-alt me-2 text-primary"></i>
                Панель администратора
            </h2>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>Обновить
                </button>
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#systemInfoModal">
                    <i class="fas fa-info-circle me-2"></i>Система
                </button>
            </div>
        </div>

        <!-- Уведомления -->
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

        <!-- Статистические карточки -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $total_documents; ?></h4>
                                <p class="card-text">Всего документов</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-file-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $total_users; ?></h4>
                                <p class="card-text">Всего пользователей</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-white bg-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $active_users; ?></h4>
                                <p class="card-text">Активных пользователей</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-user-check fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="card-title"><?php echo $documents_by_status['review'] ?? 0; ?></h4>
                                <p class="card-text">На рассмотрении</p>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-clock fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Графики и статистика -->
            <div class="col-md-8">
                <!-- Статистика документов по статусам -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Статистика документов по статусам
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <canvas id="statusChart" width="400" height="200"></canvas>
                            </div>
                            <div class="col-md-6">
                                <canvas id="categoryChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Последние документы -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Последние загруженные документы
                        </h5>
                        <a href="documents.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-external-link-alt me-1"></i>Все документы
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_documents && $recent_documents->rowCount() > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Название</th>
                                            <th>Автор</th>
                                            <th>Статус</th>
                                            <th>Дата</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($doc = $recent_documents->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($doc['file_name']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($doc['creator_name']); ?></td>
                                            <td>
                                                <?php
                                                $status_classes = [
                                                    'draft' => 'bg-secondary',
                                                    'review' => 'bg-warning text-dark',
                                                    'approved' => 'bg-success',
                                                    'rejected' => 'bg-danger',
                                                    'archived' => 'bg-dark'
                                                ];
                                                $status_labels = [
                                                    'draft' => 'Черновик',
                                                    'review' => 'На рассмотрении',
                                                    'approved' => 'Утверждено',
                                                    'rejected' => 'Отклонено',
                                                    'archived' => 'Архив'
                                                ];
                                                ?>
                                                <span class="badge <?php echo $status_classes[$doc['status']]; ?>">
                                                    <?php echo $status_labels[$doc['status']]; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($doc['created_at'])); ?></td>
                                            <td>
                                                <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">Нет недавних документов</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Управление пользователями -->
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-users-cog me-2"></i>
                            Управление пользователями
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($all_users && $all_users->rowCount() > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php while ($usr = $all_users->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-start px-0">
                                    <div class="flex-grow-1">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($usr['full_name']); ?></h6>
                                            <small>
                                                <?php if ($usr['status'] == 'active'): ?>
                                                    <i class="fas fa-circle text-success" title="Активен"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-circle text-secondary" title="Неактивен"></i>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <p class="mb-1">
                                            <small class="text-muted"><?php echo htmlspecialchars($usr['username']); ?></small>
                                        </p>
                                        <div class="d-flex gap-1">
                                            <?php
                                            $role_classes = [
                                                'admin' => 'bg-danger',
                                                'manager' => 'bg-warning text-dark',
                                                'employee' => 'bg-primary'
                                            ];
                                            $role_labels = [
                                                'admin' => 'Администратор',
                                                'manager' => 'Менеджер',
                                                'employee' => 'Сотрудник'
                                            ];
                                            ?>
                                            <span class="badge <?php echo $role_classes[$usr['role']]; ?>">
                                                <?php echo $role_labels[$usr['role']]; ?>
                                            </span>
                                        </div>
                                        
                                        <?php if ($usr['id'] != $user->id): ?>
                                        <div class="mt-2">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button class="btn btn-outline-primary" 
                                                        onclick="showUserModal(<?php echo $usr['id']; ?>, '<?php echo htmlspecialchars($usr['full_name']); ?>', '<?php echo $usr['role']; ?>', '<?php echo $usr['status']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Изменить статус пользователя?')">
                                                    <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                                    <input type="hidden" name="new_status" value="<?php echo $usr['status'] == 'active' ? 'inactive' : 'active'; ?>">
                                                    <button type="submit" name="toggle_user_status" class="btn btn-outline-<?php echo $usr['status'] == 'active' ? 'warning' : 'success'; ?>">
                                                        <i class="fas fa-<?php echo $usr['status'] == 'active' ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">Нет пользователей</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования пользователя -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Редактирование пользователя
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="modal_user_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Пользователь</label>
                            <div>
                                <strong id="modal_user_name"></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_role" class="form-label">Роль</label>
                            <select class="form-select" name="new_role" id="new_role" required>
                                <option value="employee">Сотрудник</option>
                                <option value="manager">Менеджер</option>
                                <option value="admin">Администратор</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="update_user_role" class="btn btn-primary">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно информации о системе -->
    <div class="modal fade" id="systemInfoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-server me-2"></i>Информация о системе
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Система документооборота</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Название:</strong></td><td>DocFlow</td></tr>
                                <tr><td><strong>Версия:</strong></td><td>1.0.0</td></tr>
                                <tr><td><strong>PHP версия:</strong></td><td><?php echo PHP_VERSION; ?></td></tr>
                                <tr><td><strong>Сервер:</strong></td><td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Неизвестно'; ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>База данных</h6>
                            <table class="table table-sm">
                                <tr><td><strong>Тип:</strong></td><td>MySQL</td></tr>
                                <tr><td><strong>Хост:</strong></td><td>localhost</td></tr>
                                <tr><td><strong>База:</strong></td><td>u3145131_default</td></tr>
                                <tr><td><strong>Статус:</strong></td><td><span class="badge bg-success">Подключена</span></td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Статистика системы</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-primary"><?php echo $total_documents; ?></h4>
                                <small class="text-muted">Документов</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-success"><?php echo $total_users; ?></h4>
                                <small class="text-muted">Пользователей</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-info"><?php echo round(disk_free_space('.') / 1024 / 1024 / 1024, 1); ?> GB</h4>
                                <small class="text-muted">Свободно места</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Данные для графиков
        const statusData = <?php echo json_encode($documents_by_status); ?>;
        const categoryData = <?php echo json_encode($documents_by_category); ?>;

        // График статусов документов
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Черновик', 'На рассмотрении', 'Утверждено', 'Отклонено', 'Архив'],
                datasets: [{
                    data: [
                        statusData.draft || 0,
                        statusData.review || 0,
                        statusData.approved || 0,
                        statusData.rejected || 0,
                        statusData.archived || 0
                    ],
                    backgroundColor: [
                        '#6c757d',
                        '#ffc107',
                        '#198754',
                        '#dc3545',
                        '#343a40'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'По статусам'
                    }
                }
            }
        });

        // График категорий документов
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: ['Внутренние', 'Внешние', 'Договоры', 'Отчеты', 'Счета'],
                datasets: [{
                    data: [
                        categoryData.internal || 0,
                        categoryData.external || 0,
                        categoryData.contracts || 0,
                        categoryData.reports || 0,
                        categoryData.invoices || 0
                    ],
                    backgroundColor: [
                        '#0d6efd',
                        '#198754',
                        '#dc3545',
                        '#ffc107',
                        '#6f42c1'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'По категориям'
                    }
                }
            }
        });

        // Функция для показа модального окна редактирования пользователя
        function showUserModal(userId, userName, userRole, userStatus) {
            document.getElementById('modal_user_id').value = userId;
            document.getElementById('modal_user_name').textContent = userName;
            document.getElementById('new_role').value = userRole;
            
            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }
    </script>
</body>
</html> 