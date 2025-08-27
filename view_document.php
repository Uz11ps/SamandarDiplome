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

// Проверка ID документа
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: documents.php");
    exit();
}

$doc_id = (int)$_GET['id'];
$error = '';
$success = '';

try {
    $user = new User();
    $user->getUserById($_SESSION['user_id']);
    
    $document = new Document();
    $doc_data = $document->getById($doc_id);
    
    if (!$doc_data) {
        header("Location: documents.php?error=not_found");
        exit();
    }
    
    // Добавление записи о просмотре в историю
    $document->addHistory($doc_id, $user->id, 'viewed', 'Документ просмотрен');
    
    // Получение истории документа
    $history = $document->getHistory($doc_id);
    
} catch (Exception $e) {
    handleDatabaseException($e, 'view_document.php');
}

// Обработка действий с документом
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $new_status = $_POST['new_status'];
        $comment = trim($_POST['comment']);
        
        try {
            $doc = new Document();
            $doc->id = $doc_id;
            if ($doc->updateStatus($new_status)) {
                $doc->addHistory($doc_id, $user->id, 'status_changed', 
                    "Статус изменен на: $new_status" . ($comment ? ". Комментарий: $comment" : ""));
                $success = 'Статус документа успешно обновлен';
                
                // Обновляем данные документа
                $doc_data = $document->getById($doc_id);
                $history = $document->getHistory($doc_id);
            } else {
                $error = 'Ошибка при обновлении статуса';
            }
        } catch (Exception $e) {
            $error = 'Ошибка при работе с базой данных';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($doc_data['title']); ?> - DocFlow</title>
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
        <!-- Заголовок -->
        <div class="d-flex align-items-center mb-4">
            <a href="documents.php" class="btn btn-outline-secondary me-3">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h2 class="mb-0">
                <i class="fas fa-file-alt me-2 text-primary"></i>
                Просмотр документа
            </h2>
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

        <div class="row">
            <!-- Основная информация о документе -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php
                            $file_icon = 'fa-file-alt';
                            switch(strtolower($doc_data['file_type'])) {
                                case 'pdf': $file_icon = 'fa-file-pdf text-danger'; break;
                                case 'doc':
                                case 'docx': $file_icon = 'fa-file-word text-primary'; break;
                                case 'xls':
                                case 'xlsx': $file_icon = 'fa-file-excel text-success'; break;
                                case 'ppt':
                                case 'pptx': $file_icon = 'fa-file-powerpoint text-warning'; break;
                                case 'jpg':
                                case 'jpeg':
                                case 'png':
                                case 'gif': $file_icon = 'fa-file-image text-info'; break;
                            }
                            ?>
                            <i class="fas <?php echo $file_icon; ?> me-2"></i>
                            <?php echo htmlspecialchars($doc_data['title']); ?>
                        </h5>
                        
                        <div class="d-flex gap-2">
                            <a href="<?php echo $doc_data['file_path']; ?>" class="btn btn-success" download>
                                <i class="fas fa-download me-2"></i>Скачать
                            </a>
                            <?php if ($user->role == 'admin' || $user->id == $doc_data['creator_id']): ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                                <i class="fas fa-edit me-2"></i>Изменить статус
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Описание -->
                        <?php if ($doc_data['description']): ?>
                        <div class="mb-4">
                            <h6><i class="fas fa-align-left me-2"></i>Описание</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($doc_data['description'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Метаданные -->
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-info-circle me-2"></i>Информация о файле</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Имя файла:</strong></td>
                                        <td><?php echo htmlspecialchars($doc_data['file_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Размер:</strong></td>
                                        <td><?php echo round($doc_data['file_size']/1024, 1); ?> KB</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Тип файла:</strong></td>
                                        <td><?php echo strtoupper($doc_data['file_type']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="col-md-6">
                                <h6><i class="fas fa-tags me-2"></i>Классификация</h6>
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Статус:</strong></td>
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
                                            <span class="badge <?php echo $status_classes[$doc_data['status']]; ?>">
                                                <?php echo $status_labels[$doc_data['status']]; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Приоритет:</strong></td>
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
                                            <span class="<?php echo $priority_classes[$doc_data['priority']]; ?>">
                                                <i class="fas fa-flag me-1"></i>
                                                <?php echo $priority_labels[$doc_data['priority']]; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Категория:</strong></td>
                                        <td>
                                            <?php
                                            $category_labels = [
                                                'internal' => 'Внутренние',
                                                'external' => 'Внешние',
                                                'contracts' => 'Договоры',
                                                'reports' => 'Отчеты',
                                                'invoices' => 'Счета'
                                            ];
                                            echo $category_labels[$doc_data['category']] ?? $doc_data['category'];
                                            ?>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Теги -->
                        <?php if ($doc_data['tags']): ?>
                        <div class="mt-3">
                            <h6><i class="fas fa-tags me-2"></i>Теги</h6>
                            <?php
                            $tags = explode(',', $doc_data['tags']);
                            foreach ($tags as $tag) {
                                $tag = trim($tag);
                                if ($tag) {
                                    echo '<span class="badge bg-light text-dark me-1">' . htmlspecialchars($tag) . '</span>';
                                }
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- История изменений -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            История изменений
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($history->rowCount() > 0): ?>
                            <div class="timeline">
                                <?php while ($hist = $history->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="d-flex mb-3">
                                    <div class="flex-shrink-0">
                                        <?php
                                        $action_icons = [
                                            'created' => 'fa-plus-circle text-success',
                                            'viewed' => 'fa-eye text-info',
                                            'edited' => 'fa-edit text-warning',
                                            'approved' => 'fa-check-circle text-success',
                                            'rejected' => 'fa-times-circle text-danger',
                                            'status_changed' => 'fa-exchange-alt text-primary',
                                            'forwarded' => 'fa-share text-info'
                                        ];
                                        $icon = $action_icons[$hist['action']] ?? 'fa-circle text-secondary';
                                        ?>
                                        <i class="fas <?php echo $icon; ?> fa-lg"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <strong><?php echo htmlspecialchars($hist['user_name']); ?></strong>
                                                <?php
                                                $action_labels = [
                                                    'created' => 'создал документ',
                                                    'viewed' => 'просмотрел документ',
                                                    'edited' => 'отредактировал документ',
                                                    'approved' => 'утвердил документ',
                                                    'rejected' => 'отклонил документ',
                                                    'status_changed' => 'изменил статус',
                                                    'forwarded' => 'переслал документ'
                                                ];
                                                echo $action_labels[$hist['action']] ?? $hist['action'];
                                                ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y H:i', strtotime($hist['created_at'])); ?>
                                            </small>
                                        </div>
                                        <?php if ($hist['comment']): ?>
                                        <div class="mt-1">
                                            <small class="text-muted"><?php echo htmlspecialchars($hist['comment']); ?></small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">История изменений пуста</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Боковая панель -->
            <div class="col-md-4">
                <!-- Информация об авторе -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2"></i>Автор документа
                        </h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-circle fa-3x text-primary"></i>
                        </div>
                        <h6><?php echo htmlspecialchars($doc_data['creator_name']); ?></h6>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($doc_data['creator_position'] ?? 'Должность не указана'); ?></p>
                        <small class="text-muted">ID: <?php echo $doc_data['creator_id']; ?></small>
                    </div>
                </div>

                <!-- Даты -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-calendar me-2"></i>Временные метки
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Создан:</strong><br>
                            <small class="text-muted">
                                <?php echo date('d.m.Y в H:i', strtotime($doc_data['created_at'])); ?>
                            </small>
                        </div>
                        <div>
                            <strong>Обновлен:</strong><br>
                            <small class="text-muted">
                                <?php echo date('d.m.Y в H:i', strtotime($doc_data['updated_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>Быстрые действия
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="<?php echo $doc_data['file_path']; ?>" class="btn btn-outline-success btn-sm" download>
                                <i class="fas fa-download me-2"></i>Скачать файл
                            </a>
                            <a href="documents.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list me-2"></i>Все документы
                            </a>
                            <a href="upload.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-upload me-2"></i>Загрузить новый
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно изменения статуса -->
    <?php if ($user->role == 'admin' || $user->id == $doc_data['creator_id']): ?>
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Изменение статуса документа
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Текущий статус</label>
                            <div>
                                <span class="badge <?php echo $status_classes[$doc_data['status']]; ?> fs-6">
                                    <?php echo $status_labels[$doc_data['status']]; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_status" class="form-label">Новый статус</label>
                            <select class="form-select" name="new_status" id="new_status" required>
                                <option value="">Выберите статус...</option>
                                <?php foreach($status_labels as $status => $label): ?>
                                    <?php if ($status != $doc_data['status']): ?>
                                    <option value="<?php echo $status; ?>"><?php echo $label; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comment" class="form-label">Комментарий (необязательно)</label>
                            <textarea class="form-control" name="comment" id="comment" rows="3" 
                                      placeholder="Добавьте комментарий к изменению статуса..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Изменить статус</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 