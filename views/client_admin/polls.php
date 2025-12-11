<?php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();
$userId = $_SESSION['user_id'];
$event = $db->fetch("SELECT * FROM events WHERE user_id = ?", [$userId]);

// --- YENİ ANKET EKLEME ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_poll'])) {
    $question = trim($_POST['question']);
    $options = $_POST['options']; // Array olarak gelir
    
    // Soruyu kaydet
    $db->query("INSERT INTO polls (event_id, question, status) VALUES (?, ?, 'passive')", [$event['id'], $question]);
    $pollId = $db->lastInsertId();
    
    // Şıkları kaydet
    foreach ($options as $opt) {
        if (!empty(trim($opt))) {
            $db->query("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)", [$pollId, trim($opt)]);
        }
    }
    header("Location: polls.php");
    exit;
}

// --- DURUM DEĞİŞTİR (AKTİF/PASİF) ---
if (isset($_GET['toggle'])) {
    $pollId = $_GET['toggle'];
    // Önce hepsini pasif yap (Aynı anda tek anket aktif olsun diye)
    $db->query("UPDATE polls SET status = 'passive' WHERE event_id = ?", [$event['id']]);
    // Seçileni aktif yap
    $db->query("UPDATE polls SET status = 'active' WHERE id = ?", [$pollId]);
    header("Location: polls.php");
    exit;
}

// --- SİLME ---
if (isset($_GET['delete'])) {
    $db->query("DELETE FROM polls WHERE id = ?", [$_GET['delete']]);
    header("Location: polls.php");
    exit;
}

// Anketleri listele
$polls = $db->fetchAll("SELECT * FROM polls WHERE event_id = ? ORDER BY id DESC", [$event['id']]);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Anket Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold" href="dashboard.php">Yönetim Paneli</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-3">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Medya</a></li>
                    <li class="nav-item"><a class="nav-link active" href="polls.php">Anketler</a></li>
                </ul>
                <a href="../../logout.php" class="btn btn-danger btn-sm">Çıkış</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">Yeni Soru Oluştur</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="create_poll" value="1">
                            <div class="mb-3">
                                <label>Soru:</label>
                                <input type="text" name="question" class="form-control" placeholder="Örn: Yemeği beğendiniz mi?" required>
                            </div>
                            <label>Seçenekler:</label>
                            <div class="mb-2"><input type="text" name="options[]" class="form-control form-control-sm" placeholder="Seçenek 1" required></div>
                            <div class="mb-2"><input type="text" name="options[]" class="form-control form-control-sm" placeholder="Seçenek 2" required></div>
                            <div class="mb-2"><input type="text" name="options[]" class="form-control form-control-sm" placeholder="Seçenek 3 (Opsiyonel)"></div>
                            <div class="mb-3"><input type="text" name="options[]" class="form-control form-control-sm" placeholder="Seçenek 4 (Opsiyonel)"></div>
                            
                            <button type="submit" class="btn btn-success w-100">Oluştur</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">Anket Listesi</div>
                    <div class="card-body p-0">
                        <table class="table table-hover m-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Soru</th>
                                    <th>Durum</th>
                                    <th class="text-end">İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($polls as $poll): ?>
                                <tr>
                                    <td><?= htmlspecialchars($poll['question']) ?></td>
                                    <td>
                                        <?php if($poll['status'] == 'active'): ?>
                                            <span class="badge bg-success">YAYINDA</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Pasif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="../../public/<?= $event['slug'] ?>/anket-sonuc?poll_id=<?= $poll['id'] ?>" target="_blank" class="btn btn-sm btn-info text-white" title="Dev Ekrana Yansıt">
                                            <i class="fa-solid fa-tv"></i>
                                        </a>

                                        <?php if($poll['status'] == 'passive'): ?>
                                            <a href="?toggle=<?= $poll['id'] ?>" class="btn btn-sm btn-outline-success">Başlat</a>
                                        <?php else: ?>
                                            <a href="#" class="btn btn-sm btn-success disabled">Yayında</a>
                                        <?php endif; ?>
                                        
                                        <a href="?delete=<?= $poll['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Silmek istediğine emin misin?')"><i class="fa-solid fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>