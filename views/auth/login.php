<?php
// views/auth/login.php
require_once __DIR__ . '/../../src/Auth.php';

$auth = new Auth();
$error = '';

// Eğer zaten giriş yapmışsa, paneline yönlendir
if ($auth->isLoggedIn()) {
    if ($auth->getRole() == 'super_admin') {
        header("Location: ../super_admin/dashboard.php");
    } else {
        header("Location: ../client_admin/dashboard.php");
    }
    exit;
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($auth->login($username, $password)) {
        // Başarılı! Yönlendir...
        if ($_SESSION['role'] == 'super_admin') {
            // Şimdilik test için basit bir echo, sonra dashboard'a yönlendireceğiz
            header("Location: ../super_admin/dashboard.php");
            exit;
        } else {
            header("Location: ../client_admin/dashboard.php");
            exit;
        }
    } else {
        $error = "Kullanıcı adı veya şifre hatalı!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Etkinlik Yönetim Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); background: white; }
        .brand-logo { font-weight: bold; font-size: 1.5rem; color: #0d6efd; text-align: center; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="brand-logo">Etkinlik Yönetim Paneli</div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger text-center"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="username" class="form-control" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
        </form>
        
        <div class="text-center mt-3 text-muted" style="font-size: 0.8rem;">
            &copy; 2025 Etkinlik Yönetim A.Ş.
        </div>
    </div>

</body>
</html>