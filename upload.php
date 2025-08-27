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
} catch (Exception $e) {
    handleDatabaseException($e, 'upload.php');
}

$error = '';
$success = '';

// Обработка загрузки файла
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $priority = $_POST['priority'];
    $tags = trim($_POST['tags']);
    
    // Валидация полей
    if (empty($title)) {
        $error = 'Введите название документа';
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Выберите файл для загрузки';
    } else {
        $file = $_FILES['document'];
        $file_name = $file['name'];
        $file_size = $file['size'];
        $file_tmp = $file['tmp_name'];
        $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Разрешенные типы файлов
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        if (!in_array($file_type, $allowed_types)) {
            $error = 'Неподдерживаемый тип файла. Разрешены: ' . implode(', ', $allowed_types);
        } elseif ($file_size > $max_size) {
            $error = 'Размер файла не должен превышать 10MB';
        } else {
            // Создание директории если не существует
            $upload_dir = 'uploads/documents/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Генерация уникального имени файла
            $unique_name = time() . '_' . uniqid() . '.' . $file_type;
            $file_path = $upload_dir . $unique_name;
            
            if (move_uploaded_file($file_tmp, $file_path)) {
                try {
                    // Сохранение в базу данных
                    $document = new Document();
                    $document->title = $title;
                    $document->description = $description;
                    $document->file_name = $file_name;
                    $document->file_path = $file_path;
                    $document->file_size = $file_size;
                    $document->file_type = $file_type;
                    $document->creator_id = $user->id;
                    $document->category = $category;
                    $document->priority = $priority;
                    $document->tags = $tags;
                    
                    if ($document->create()) {
                        // Добавление записи в историю
                        $document->addHistory($document->id, $user->id, 'created', 'Документ загружен в систему');
                        
                        header("Location: documents.php?success=uploaded");
                        exit();
                    } else {
                        $error = 'Ошибка при сохранении в базу данных';
                        unlink($file_path); // Удаляем файл если не удалось сохранить в БД
                    }
                } catch (Exception $e) {
                    $error = 'Ошибка при работе с базой данных';
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            } else {
                $error = 'Ошибка при загрузке файла';
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
    <title>Загрузка документа - DocFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .dropzone {
            border: 3px dashed #dee2e6;
            border-radius: 1rem;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
            cursor: pointer;
        }
        
        .dropzone:hover,
        .dropzone.dragover {
            border-color: #007bff;
            background-color: rgba(0, 123, 255, 0.05);
            transform: translateY(-2px);
        }
        
        .dropzone.dragover {
            border-style: solid;
            background-color: rgba(0, 123, 255, 0.1);
        }
        
        .file-preview {
            display: none;
            border: 2px solid #007bff;
            border-radius: 1rem;
            padding: 1rem;
            background-color: rgba(0, 123, 255, 0.05);
        }
        
        .file-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .upload-progress {
            display: none;
        }
    </style>
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
                        <a class="nav-link active" href="upload.php">
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
    <div class="container mt-5 pt-3">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Заголовок -->
                <div class="d-flex align-items-center mb-4">
                    <a href="documents.php" class="btn btn-outline-secondary me-3">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="mb-0">
                        <i class="fas fa-cloud-upload-alt me-2 text-primary"></i>
                        Загрузка документа
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

                <!-- Форма загрузки -->
                <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-file-upload me-2"></i>
                                Выбор файла
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Drag & Drop зона -->
                            <div class="dropzone" id="dropzone">
                                <div id="dropzone-content">
                                    <i class="fas fa-cloud-upload-alt file-icon text-primary"></i>
                                    <h4>Перетащите файл сюда</h4>
                                    <p class="text-muted mb-3">или нажмите для выбора файла</p>
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('document').click()">
                                        <i class="fas fa-folder-open me-2"></i>Выбрать файл
                                    </button>
                                </div>
                                
                                <!-- Предварительный просмотр файла -->
                                <div class="file-preview" id="filePreview">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-file-alt fa-2x text-primary me-3" id="fileIcon"></i>
                                            <div>
                                                <h6 class="mb-0" id="fileName"></h6>
                                                <small class="text-muted" id="fileSize"></small>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeFile()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="file" name="document" id="document" class="d-none" 
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif"
                                   required>
                            
                            <!-- Прогресс загрузки -->
                            <div class="upload-progress mt-3" id="uploadProgress">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted mt-1">Загрузка файла...</small>
                            </div>
                            
                            <!-- Информация о ограничениях -->
                            <div class="mt-3">
                                <small class="text-muted">
                                    <strong>Поддерживаемые форматы:</strong> PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG, GIF<br>
                                    <strong>Максимальный размер:</strong> 10 MB
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Информация о документе -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Информация о документе
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Название -->
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="Название документа" required
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                                <label for="title">
                                    <i class="fas fa-heading me-2"></i>Название документа *
                                </label>
                            </div>

                            <!-- Описание -->
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="description" name="description" 
                                          placeholder="Описание документа" style="height: 100px"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <label for="description">
                                    <i class="fas fa-align-left me-2"></i>Описание
                                </label>
                            </div>

                            <div class="row">
                                <!-- Категория -->
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <select class="form-select" id="category" name="category">
                                            <option value="internal" <?php echo (isset($_POST['category']) && $_POST['category'] == 'internal') ? 'selected' : ''; ?>>Внутренние</option>
                                            <option value="external" <?php echo (isset($_POST['category']) && $_POST['category'] == 'external') ? 'selected' : ''; ?>>Внешние</option>
                                            <option value="contracts" <?php echo (isset($_POST['category']) && $_POST['category'] == 'contracts') ? 'selected' : ''; ?>>Договоры</option>
                                            <option value="reports" <?php echo (isset($_POST['category']) && $_POST['category'] == 'reports') ? 'selected' : ''; ?>>Отчеты</option>
                                            <option value="invoices" <?php echo (isset($_POST['category']) && $_POST['category'] == 'invoices') ? 'selected' : ''; ?>>Счета</option>
                                        </select>
                                        <label for="category">
                                            <i class="fas fa-folder me-2"></i>Категория
                                        </label>
                                    </div>
                                </div>

                                <!-- Приоритет -->
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <select class="form-select" id="priority" name="priority">
                                            <option value="normal" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'normal') ? 'selected' : ''; ?>>Обычный</option>
                                            <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'low') ? 'selected' : ''; ?>>Низкий</option>
                                            <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'high') ? 'selected' : ''; ?>>Высокий</option>
                                            <option value="urgent" <?php echo (isset($_POST['priority']) && $_POST['priority'] == 'urgent') ? 'selected' : ''; ?>>Срочный</option>
                                        </select>
                                        <label for="priority">
                                            <i class="fas fa-flag me-2"></i>Приоритет
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Теги -->
                            <div class="form-floating mb-4">
                                <input type="text" class="form-control" id="tags" name="tags" 
                                       placeholder="Теги (через запятую)"
                                       value="<?php echo isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : ''; ?>">
                                <label for="tags">
                                    <i class="fas fa-tags me-2"></i>Теги (через запятую)
                                </label>
                            </div>

                            <!-- Кнопки -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-grid">
                                        <button type="submit" name="upload" class="btn btn-primary" id="uploadBtn">
                                            <i class="fas fa-upload me-2"></i>Загрузить документ
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-grid">
                                        <a href="documents.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i>Отмена
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Последние загруженные документы -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>Недавно загруженные
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $recent_docs = new Document();
                            $recent = $recent_docs->getByUserId($user->id);
                            
                            if ($recent->rowCount() > 0) {
                                echo '<div class="list-group list-group-flush">';
                                $count = 0;
                                while ($doc = $recent->fetch(PDO::FETCH_ASSOC) && $count < 3) {
                                    echo '<div class="list-group-item d-flex justify-content-between align-items-center">';
                                    echo '<div>';
                                    echo '<strong>' . htmlspecialchars($doc['title']) . '</strong>';
                                    echo '<br><small class="text-muted">' . date('d.m.Y H:i', strtotime($doc['created_at'])) . '</small>';
                                    echo '</div>';
                                    echo '<a href="view_document.php?id=' . $doc['id'] . '" class="btn btn-outline-primary btn-sm">';
                                    echo '<i class="fas fa-eye"></i>';
                                    echo '</a>';
                                    echo '</div>';
                                    $count++;
                                }
                                echo '</div>';
                                
                                if ($recent->rowCount() > 3) {
                                    echo '<div class="text-center mt-3">';
                                    echo '<a href="documents.php" class="btn btn-outline-primary btn-sm">Показать все</a>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<p class="text-muted mb-0">У вас пока нет загруженных документов</p>';
                            }
                        } catch (Exception $e) {
                            echo '<p class="text-muted mb-0">Ошибка при загрузке списка документов</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Скрипт для drag & drop -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropzone = document.getElementById('dropzone');
            const fileInput = document.getElementById('document');
            const dropzoneContent = document.getElementById('dropzone-content');
            const filePreview = document.getElementById('filePreview');
            const uploadForm = document.getElementById('uploadForm');
            const uploadBtn = document.getElementById('uploadBtn');
            const uploadProgress = document.getElementById('uploadProgress');

            // Клик по зоне загрузки
            dropzone.addEventListener('click', function(e) {
                if (e.target === dropzone || e.target.closest('#dropzone-content')) {
                    fileInput.click();
                }
            });

            // Drag & Drop события
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, unhighlight, false);
            });

            dropzone.addEventListener('drop', handleDrop, false);

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            function highlight(e) {
                dropzone.classList.add('dragover');
            }

            function unhighlight(e) {
                dropzone.classList.remove('dragover');
            }

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length > 0) {
                    fileInput.files = files;
                    showFilePreview(files[0]);
                }
            }

            // Изменение файла через input
            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    showFilePreview(e.target.files[0]);
                }
            });

            function showFilePreview(file) {
                const fileName = document.getElementById('fileName');
                const fileSize = document.getElementById('fileSize');
                const fileIcon = document.getElementById('fileIcon');
                
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                
                // Изменение иконки в зависимости от типа файла
                const extension = file.name.split('.').pop().toLowerCase();
                let iconClass = 'fa-file-alt';
                
                switch(extension) {
                    case 'pdf': iconClass = 'fa-file-pdf text-danger'; break;
                    case 'doc':
                    case 'docx': iconClass = 'fa-file-word text-primary'; break;
                    case 'xls':
                    case 'xlsx': iconClass = 'fa-file-excel text-success'; break;
                    case 'ppt':
                    case 'pptx': iconClass = 'fa-file-powerpoint text-warning'; break;
                    case 'jpg':
                    case 'jpeg':
                    case 'png':
                    case 'gif': iconClass = 'fa-file-image text-info'; break;
                    case 'txt': iconClass = 'fa-file-alt text-secondary'; break;
                }
                
                fileIcon.className = `fas ${iconClass} fa-2x`;
                
                dropzoneContent.style.display = 'none';
                filePreview.style.display = 'block';
                
                // Автозаполнение названия документа
                const titleInput = document.getElementById('title');
                if (!titleInput.value) {
                    titleInput.value = file.name.replace(/\.[^/.]+$/, '');
                }
            }

            function removeFile() {
                fileInput.value = '';
                dropzoneContent.style.display = 'block';
                filePreview.style.display = 'none';
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            // Симуляция прогресса загрузки
            uploadForm.addEventListener('submit', function(e) {
                if (fileInput.files.length === 0) {
                    e.preventDefault();
                    alert('Выберите файл для загрузки');
                    return;
                }
                
                uploadBtn.disabled = true;
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Загрузка...';
                uploadProgress.style.display = 'block';
                
                // Симуляция прогресса
                let progress = 0;
                const progressBar = uploadProgress.querySelector('.progress-bar');
                const interval = setInterval(() => {
                    progress += Math.random() * 15;
                    if (progress > 90) {
                        clearInterval(interval);
                        progress = 90;
                    }
                    progressBar.style.width = progress + '%';
                }, 200);
            });

            // Глобальная функция для удаления файла
            window.removeFile = removeFile;
        });
    </script>
</body>
</html> 