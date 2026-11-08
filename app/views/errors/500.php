<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - เกิดข้อผิดพลาดของระบบ | SENA Care</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body class="bg-light min-vh-100 py-4" style="display:grid;place-items:center;overflow-wrap:anywhere;">
    <div class="container text-center" style="width:100%;min-width:0;">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5">
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle p-4" style="width: 100px; height: 100px;">
                            <i class="bi bi-exclamation-triangle" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    <h1 class="display-5 fw-bold text-dark mb-2">500</h1>
                    <h4 class="fw-semibold text-secondary mb-3">เกิดข้อผิดพลาดของระบบ</h4>
                    <p class="text-muted mb-4"><?= isset($errorMessage) ? e($errorMessage) : 'เกิดข้อผิดพลาดภายในระบบ โปรดลองใหม่อีกครั้ง หรือติดต่อผู้ดูแลระบบ' ?></p>
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="javascript:location.reload()" class="btn btn-outline-secondary px-4 rounded-pill">
                            <i class="bi bi-arrow-clockwise me-1"></i> โหลดใหม่
                        </a>
                        <a href="<?= $baseUrl ?>/dashboard" class="btn btn-primary px-4 rounded-pill">
                            <i class="bi bi-house me-1"></i> ไปหน้าแรก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
