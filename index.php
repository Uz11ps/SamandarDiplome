<?php
ob_start(); // Начинаем буферизацию вывода
session_start();
require_once 'classes/User.php';
require_once 'classes/Document.php';
require_once 'includes/error_handler.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $user = new User();
    $user->getUserById($_SESSION['user_id']);

    $document = new Document();
    $documents = $document->getAll(10);
    $stats = $document->getStats();
} catch (Exception $e) {
    handleDatabaseException($e, 'index.php');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Система документооборота</title>
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
                        <a class="nav-link active" href="index.php">
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
        <div class="row">
            <!-- Левая панель -->
            <div class="col-md-3">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Статистика</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Всего документов</small>
                            <div class="h4 text-primary"><?php echo $stats['total']; ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Черновики</small>
                            <div class="h5 text-secondary"><?php echo $stats['drafts']; ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">На рассмотрении</small>
                            <div class="h5 text-warning"><?php echo $stats['in_review']; ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Утверждено</small>
                            <div class="h5 text-success"><?php echo $stats['approved']; ?></div>
                        </div>
                        <div class="mb-0">
                            <small class="text-muted">Отклонено</small>
                            <div class="h5 text-danger"><?php echo $stats['rejected']; ?></div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Быстрые действия</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="upload.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-upload me-2"></i>Загрузить документ
                            </a>
                            <a href="documents.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-search me-2"></i>Найти документ
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Основная область -->
            <div class="col-md-9">
                <!-- Приветствие -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body bg-gradient-primary text-white">
                        <h4 class="mb-1">Добро пожаловать, <?php echo htmlspecialchars($user->full_name); ?>!</h4>
                        <p class="mb-0"><?php echo htmlspecialchars($user->position); ?> | <?php echo htmlspecialchars($user->department); ?></p>
                    </div>
                </div>

                <!-- Последние документы -->
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Последние документы</h5>
                        <a href="documents.php" class="btn btn-sm btn-outline-primary">Показать все</a>
                    </div>
                    <div class="card-body">
                        <?php if ($documents->rowCount() > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Название</th>
                                            <th>Автор</th>
                                            <th>Статус</th>
                                            <th>Приоритет</th>
                                            <th>Дата</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($doc = $documents->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-alt text-primary me-2"></i>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                                        <?php if ($doc['description']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars(substr($doc['description'], 0, 50)) . '...'; ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($doc['creator_name']); ?></td>
                                            <td>
                                                <?php
                                                $status_classes = [
                                                    'draft' => 'bg-secondary',
                                                    'review' => 'bg-warning',
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
                                            <td>
                                                <?php
                                                $priority_classes = [
                                                    'low' => 'text-success',
                                                    'normal' => 'text-secondary',
                                                    'high' => 'text-warning',
                                                    'urgent' => 'text-danger'
                                                ];
                                                $priority_labels = [
                                                    'low' => 'Низкий',
                                                    'normal' => 'Обычный',
                                                    'high' => 'Высокий',
                                                    'urgent' => 'Срочный'
                                                ];
                                                ?>
                                                <span class="<?php echo $priority_classes[$doc['priority']]; ?>">
                                                    <?php echo $priority_labels[$doc['priority']]; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($doc['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn btn-outline-primary" title="Просмотр">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo $doc['file_path']; ?>" class="btn btn-outline-success" title="Скачать" download>
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Документы не найдены</h5>
                                <p class="text-muted">Загрузите первый документ в систему</p>
                                <a href="upload.php" class="btn btn-primary">
                                    <i class="fas fa-upload me-2"></i>Загрузить документ
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 