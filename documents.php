<?php
ob_start();
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
    
    // Параметры поиска и фильтрации
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $priority = isset($_GET['priority']) ? $_GET['priority'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Получение документов с фильтрацией
    if (!empty($search)) {
        $documents = $document->search($search, $category, $status);
    } else {
        $documents = $document->getAll($limit, $offset);
    }
    
    // Обновление статуса документа
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
        $doc_id = $_POST['doc_id'];
        $new_status = $_POST['new_status'];
        
        $doc = new Document();
        $doc->id = $doc_id;
        if ($doc->updateStatus($new_status)) {
            $doc->addHistory($doc_id, $user->id, 'status_changed', "Статус изменен на: $new_status");
            header("Location: documents.php?success=status_updated");
            exit();
        }
    }
    
} catch (Exception $e) {
    handleDatabaseException($e, 'documents.php');
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Документы - DocFlow</title>
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
                        <a class="nav-link active" href="documents.php">
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
        <!-- Заголовок страницы -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="fas fa-folder-open me-2 text-primary"></i>
                Управление документами
            </h2>
            <a href="upload.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Добавить документ
            </a>
        </div>

        <!-- Уведомления -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                switch($_GET['success']) {
                    case 'status_updated': echo 'Статус документа успешно обновлен'; break;
                    case 'uploaded': echo 'Документ успешно загружен'; break;
                    default: echo 'Операция выполнена успешно';
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Фильтры -->
            <div class="col-md-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Фильтры и поиск
                        </h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <!-- Поиск -->
                            <div class="mb-3">
                                <label class="form-label">Поиск по названию</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>" 
                                           placeholder="Введите название...">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Статус -->
                            <div class="mb-3">
                                <label class="form-label">Статус</label>
                                <select class="form-select" name="status">
                                    <option value="">Все статусы</option>
                                    <option value="draft" <?php echo $status == 'draft' ? 'selected' : ''; ?>>Черновик</option>
                                    <option value="review" <?php echo $status == 'review' ? 'selected' : ''; ?>>На рассмотрении</option>
                                    <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Утверждено</option>
                                    <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Отклонено</option>
                                    <option value="archived" <?php echo $status == 'archived' ? 'selected' : ''; ?>>Архив</option>
                                </select>
                            </div>

                            <!-- Приоритет -->
                            <div class="mb-3">
                                <label class="form-label">Приоритет</label>
                                <select class="form-select" name="priority">
                                    <option value="">Все приоритеты</option>
                                    <option value="low" <?php echo $priority == 'low' ? 'selected' : ''; ?>>Низкий</option>
                                    <option value="normal" <?php echo $priority == 'normal' ? 'selected' : ''; ?>>Обычный</option>
                                    <option value="high" <?php echo $priority == 'high' ? 'selected' : ''; ?>>Высокий</option>
                                    <option value="urgent" <?php echo $priority == 'urgent' ? 'selected' : ''; ?>>Срочный</option>
                                </select>
                            </div>

                            <!-- Категория -->
                            <div class="mb-3">
                                <label class="form-label">Категория</label>
                                <select class="form-select" name="category">
                                    <option value="">Все категории</option>
                                    <option value="contracts" <?php echo $category == 'contracts' ? 'selected' : ''; ?>>Договоры</option>
                                    <option value="reports" <?php echo $category == 'reports' ? 'selected' : ''; ?>>Отчеты</option>
                                    <option value="invoices" <?php echo $category == 'invoices' ? 'selected' : ''; ?>>Счета</option>
                                    <option value="internal" <?php echo $category == 'internal' ? 'selected' : ''; ?>>Внутренние</option>
                                    <option value="external" <?php echo $category == 'external' ? 'selected' : ''; ?>>Внешние</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Применить
                                </button>
                                <a href="documents.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Сбросить
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Быстрые действия
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="upload.php" class="btn btn-success btn-sm">
                                <i class="fas fa-upload me-2"></i>Загрузить документ
                            </a>
                            <a href="documents.php?status=draft" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-edit me-2"></i>Мои черновики
                            </a>
                            <a href="documents.php?status=review" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-clock me-2"></i>На рассмотрении
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Список документов -->
            <div class="col-md-9">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>
                            Список документов
                            <?php if ($documents->rowCount() > 0): ?>
                                <span class="badge bg-primary"><?php echo $documents->rowCount(); ?></span>
                            <?php endif; ?>
                        </h5>
                        
                        <!-- Переключатель вида -->
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check" name="view-mode" id="list-view" checked>
                            <label class="btn btn-outline-primary btn-sm" for="list-view">
                                <i class="fas fa-list"></i>
                            </label>
                            <input type="radio" class="btn-check" name="view-mode" id="grid-view">
                            <label class="btn btn-outline-primary btn-sm" for="grid-view">
                                <i class="fas fa-th"></i>
                            </label>
                        </div>
                    </div>

                    <div class="card-body">
                        <?php if ($documents->rowCount() > 0): ?>
                            <!-- Список документов -->
                            <div id="documents-list">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Документ</th>
                                                <th>Автор</th>
                                                <th>Статус</th>
                                                <th>Приоритет</th>
                                                <th>Дата создания</th>
                                                <th>Действия</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($doc = $documents->fetch(PDO::FETCH_ASSOC)): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php
                                                        $file_icon = 'fa-file-alt';
                                                        switch(strtolower($doc['file_type'])) {
                                                            case 'pdf': $file_icon = 'fa-file-pdf text-danger'; break;
                                                            case 'doc':
                                                            case 'docx': $file_icon = 'fa-file-word text-primary'; break;
                                                            case 'xls':
                                                            case 'xlsx': $file_icon = 'fa-file-excel text-success'; break;
                                                            case 'jpg':
                                                            case 'png':
                                                            case 'gif': $file_icon = 'fa-file-image text-info'; break;
                                                        }
                                                        ?>
                                                        <i class="fas <?php echo $file_icon; ?> fa-2x me-3"></i>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($doc['file_name']); ?>
                                                                (<?php echo round($doc['file_size']/1024, 1); ?> KB)
                                                            </small>
                                                            <?php if ($doc['description']): ?>
                                                                <br><small class="text-muted">
                                                                    <?php echo htmlspecialchars(substr($doc['description'], 0, 100)) . '...'; ?>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($doc['creator_name']); ?></strong>
                                                        <br><small class="text-muted">ID: <?php echo $doc['creator_id']; ?></small>
                                                    </div>
                                                </td>
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
                                                <td>
                                                    <?php
                                                    $priority_classes = [
                                                        'low' => 'text-success',
                                                        'normal' => 'text-secondary',
                                                        'high' => 'text-warning',
                                                        'urgent' => 'text-danger'
                                                    ];
                                                    $priority_icons = [
                                                        'low' => 'fa-arrow-down',
                                                        'normal' => 'fa-minus',
                                                        'high' => 'fa-arrow-up',
                                                        'urgent' => 'fa-exclamation-triangle'
                                                    ];
                                                    $priority_labels = [
                                                        'low' => 'Низкий',
                                                        'normal' => 'Обычный',
                                                        'high' => 'Высокий',
                                                        'urgent' => 'Срочный'
                                                    ];
                                                    ?>
                                                    <span class="<?php echo $priority_classes[$doc['priority']]; ?>">
                                                        <i class="fas <?php echo $priority_icons[$doc['priority']]; ?> me-1"></i>
                                                        <?php echo $priority_labels[$doc['priority']]; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo date('d.m.Y', strtotime($doc['created_at'])); ?>
                                                        <br><small class="text-muted"><?php echo date('H:i', strtotime($doc['created_at'])); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <a href="view_document.php?id=<?php echo $doc['id']; ?>" 
                                                           class="btn btn-outline-primary" title="Просмотр">
                                                            <i class="fas fa-eye me-1"></i>Просмотр
                                                        </a>
                                                        <a href="<?php echo $doc['file_path']; ?>" 
                                                           class="btn btn-outline-success" title="Скачать" download>
                                                            <i class="fas fa-download me-1"></i>Скачать
                                                        </a>
                                                        <?php if ($user->role == 'admin' || $user->id == $doc['creator_id']): ?>
                                                        <div class="dropdown">
                                                            <button class="btn btn-outline-secondary dropdown-toggle" 
                                                                    type="button" data-bs-toggle="dropdown">
                                                                <i class="fas fa-cog me-1"></i>Управление
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><h6 class="dropdown-header">Изменить статус</h6></li>
                                                                <?php foreach(['draft' => 'Черновик', 'review' => 'На рассмотрении', 'approved' => 'Утверждено', 'rejected' => 'Отклонено'] as $stat => $label): ?>
                                                                    <?php if ($stat != $doc['status']): ?>
                                                                    <li>
                                                                        <form method="POST" style="margin: 0;">
                                                                            <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                                            <input type="hidden" name="new_status" value="<?php echo $stat; ?>">
                                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                                <i class="fas fa-circle me-2" style="color: var(--bs-<?php echo $stat == 'approved' ? 'success' : ($stat == 'rejected' ? 'danger' : 'warning'); ?>);"></i>
                                                                                <?php echo $label; ?>
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Сетка документов (скрыта по умолчанию) -->
                            <div id="documents-grid" style="display: none;">
                                <!-- Будет реализовано через JavaScript -->
                            </div>

                        <?php else: ?>
                            <!-- Пустой список -->
                            <div class="text-center py-5">
                                <i class="fas fa-folder-open fa-4x text-muted mb-3"></i>
                                <h4 class="text-muted">Документы не найдены</h4>
                                <?php if (!empty($search) || !empty($status) || !empty($category)): ?>
                                    <p class="text-muted">По вашему запросу документы не найдены. Попробуйте изменить критерии поиска.</p>
                                    <a href="documents.php" class="btn btn-outline-primary">
                                        <i class="fas fa-times me-2"></i>Сбросить фильтры
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted">В системе пока нет документов. Загрузите первый документ.</p>
                                    <a href="upload.php" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Загрузить документ
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Пагинация (для будущего использования) -->
                <?php if ($documents->rowCount() >= $limit): ?>
                <nav aria-label="Навигация по страницам" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&category=<?php echo $category; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <li class="page-item active">
                            <span class="page-link"><?php echo $page; ?></span>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>&category=<?php echo $category; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Скрипт для переключения видов -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const listView = document.getElementById('list-view');
            const gridView = document.getElementById('grid-view');
            const documentsList = document.getElementById('documents-list');
            const documentsGrid = document.getElementById('documents-grid');

            listView.addEventListener('change', function() {
                if (this.checked) {
                    documentsList.style.display = 'block';
                    documentsGrid.style.display = 'none';
                }
            });

            gridView.addEventListener('change', function() {
                if (this.checked) {
                    documentsList.style.display = 'none';
                    documentsGrid.style.display = 'block';
                    // Здесь можно добавить код для генерации сетки
                }
            });
        });
    </script>
</body>
</html> 