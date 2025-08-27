<?php
function showDatabaseError($error_message = '') {
    error_log("Database error: " . $error_message);
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Ошибка подключения - DocFlow</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-light d-flex align-items-center min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card border-0 shadow">
                        <div class="card-header bg-danger text-white text-center">
                            <i class="fas fa-database fa-2x mb-2"></i>
                            <h5 class="mb-0">Ошибка подключения к базе данных</h5>
                        </div>
                        <div class="card-body text-center p-4">
                            <div class="mb-4">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <p class="lead">Временные технические неполадки</p>
                                <p class="text-muted">Не удалось подключиться к базе данных. Пожалуйста, попробуйте позже или обратитесь к администратору.</p>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Назад
                                </a>
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Вернуться к входу
                                </a>
                            </div>
                        </div>
                        <div class="card-footer text-center text-muted">
                            <small>
                                <i class="fas fa-clock me-1"></i>
                                Если проблема повторяется, обратитесь к техподдержке
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Автообновление страницы через 30 секунд -->
        <script>
            setTimeout(function() {
                location.reload();
            }, 30000);
        </script>
    </body>
    </html>
    <?php
    exit();
}

function handleDatabaseException($e, $context = '') {
    $message = $context ? "$context: " . $e->getMessage() : $e->getMessage();
    showDatabaseError($message);
} 