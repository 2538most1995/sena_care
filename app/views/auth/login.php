<?php
/**
 * Login Page
 */
if (!defined('APP_ROOT')) die();
$config = require APP_ROOT . '/app/config/config.php';
$baseUrl = rtrim($config['app_url'], '/');
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="เข้าสู่ระบบ - ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร.">
    <meta name="theme-color" content="#04395e">
    <title>เข้าสู่ระบบ | SENA Care</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css?v=<?= @filemtime(APP_ROOT . '/public/assets/css/style.css') ?: '1.0.1' ?>">

    <style>
        html {
            height: 100%;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
        }

        body.login-page {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            padding: 1.25rem;
            background: transparent;
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border: 1px solid #e4e9f0;
            border-radius: 20px;
            padding: 2.25rem 2rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 16px 40px rgba(15, 23, 42, 0.08);
        }

        .login-logo {
            width: 62px;
            height: 62px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.15rem;
            box-shadow: 0 10px 24px rgba(4, 57, 94, 0.28);
        }
        .login-logo i {
            font-size: 1.7rem;
            color: var(--on-primary);
        }

        .login-title {
            text-align: center;
            margin-bottom: 0.35rem;
            font-weight: 700;
            color: var(--primary-ink);
            font-size: 1.35rem;
            letter-spacing: -0.02em;
        }
        .login-subtitle {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
            font-size: 0.9rem;
        }

        .form-floating > .form-control {
            border-radius: 12px;
            border: 1px solid #dce2ea;
            padding: 1rem 1rem 0.5rem 1rem;
            height: 3.4rem;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }
        .form-floating > .form-control:focus {
            border-color: var(--primary-ink);
            box-shadow: 0 0 0 4px rgba(4, 57, 94, 0.14);
        }
        .form-floating > label {
            color: var(--text-muted);
        }

        .btn-login {
            width: 100%;
            height: 3.1rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            background: var(--primary-color);
            border: none;
            color: var(--on-primary);
            transition: background-color 0.18s ease, transform 0.15s ease, box-shadow 0.18s ease;
        }
        .btn-login:hover {
            background: var(--primary-hover);
            box-shadow: 0 8px 22px rgba(4, 57, 94, 0.3);
            color: #ffffff;
        }
        .btn-login:active {
            transform: translateY(1px) scale(0.99);
        }
        .btn-login .spinner-border {
            display: none;
        }
        .btn-login.loading .spinner-border {
            display: inline-block;
        }
        .btn-login.loading .btn-text {
            display: none;
        }

        .login-footer {
            text-align: center;
            color: #64748b;
            font-size: 0.85rem;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            z-index: 10;
            padding: 4px 8px;
            transition: color 0.15s ease;
        }
        .password-toggle:hover {
            color: var(--primary-ink);
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <h1 class="login-title">SENA Care</h1>
        <p class="login-subtitle">ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร.</p>

        <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> py-2 px-3 rounded-3" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-1"></i>
            <small><?= e($flash['message']) ?></small>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= $baseUrl ?>/login" id="loginForm" autocomplete="on">
            <?= csrfField() ?>

            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username"
                       placeholder="ชื่อผู้ใช้" required autofocus autocomplete="username"
                       value="<?= e($_POST['username'] ?? '') ?>">
                <label for="username"><i class="bi bi-person me-1"></i> ชื่อผู้ใช้หรืออีเมล</label>
            </div>

            <div class="form-floating mb-3 position-relative">
                <input type="password" class="form-control pe-5" id="password" name="password"
                       placeholder="รหัสผ่าน" required autocomplete="current-password">
                <label for="password"><i class="bi bi-lock me-1"></i> รหัสผ่าน</label>
                <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="แสดงรหัสผ่าน">
                    <i class="bi bi-eye" id="passwordIcon"></i>
                </button>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label small" for="remember">จดจำฉัน</label>
                </div>
            </div>

            <button type="submit" class="btn btn-login" id="loginBtn">
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                <span class="btn-text">เข้าสู่ระบบ</span>
            </button>
        </form>
    </div>

    <div class="login-footer">
        <p class="mb-0">&copy; <?= date('Y') + 543 ?> ระบบติดตามและเยี่ยมบ้านผู้เรียน สกร.</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('passwordIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });
    </script>
</body>
</html>
