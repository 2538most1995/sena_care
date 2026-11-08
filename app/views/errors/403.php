<?php
if (!defined('APP_ROOT')) die();
$baseUrl = rtrim((require APP_ROOT . '/app/config/config.php')['app_url'], '/');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - ไม่มีสิทธิ์เข้าถึง | SENA Care</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-5">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-lg rounded-4 p-4 p-md-5">
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle p-4" style="width: 100px; height: 100px;">
                            <i class="bi bi-shield-lock" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    <h1 class="display-5 fw-bold text-dark mb-2">403</h1>
                    <h4 class="fw-semibold text-secondary mb-3">ไม่มีสิทธิ์เข้าถึงส่วนนี้</h4>
                    <p class="text-muted mb-4">บัญชีของคุณไม่มีสิทธิ์ในการเข้าถึงหน้านี้ หรือเนื้อหาที่คุณพยายามเปิด กรุณาติดต่อผู้ดูแลระบบหากคิดว่านี่คือข้อผิดพลาด</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary px-4 rounded-pill">
                            <i class="bi bi-arrow-left me-1"></i> ย้อนกลับ
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
