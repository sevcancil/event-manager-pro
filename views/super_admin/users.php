<?php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('super_admin');
$db = new Database();

$users = $db->fetchAll("SELECT * FROM users WHERE role = 'client_admin' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Müşteri Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h3>Müşteri Listesi</h3>
        <a href="dashboard.php" class="btn btn-secondary mb-3">Geri Dön</a>
        
        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>Firma Adı</th>
                    <th>Kullanıcı Adı</th>
                    <th>Durum</th>
                    <th>Oluşturma Tarihi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= $u['status'] ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Pasif</span>' ?></td>
                    <td><?= $u['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>