<?php
session_start();
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';

$auth = new Auth();
$auth->requireRole('client_admin');
$db = new Database();
$userId = $_SESSION['user_id'];
$event = $db->fetch("SELECT * FROM events WHERE user_id = ?", [$userId]);

// YENİ KAYIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $time = $_POST['start_time'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']); // Textarea'dan gelen veriyi alır
    $icon = $_POST['icon'];
    
    $db->query("INSERT INTO event_schedule (event_id, start_time, title, description, icon) VALUES (?, ?, ?, ?, ?)", 
               [$event['id'], $time, $title, $desc, $icon]);
    header("Location: schedule.php");
    exit;
}

// SİLME
if (isset($_GET['delete'])) {
    $db->query("DELETE FROM event_schedule WHERE id = ?", [$_GET['delete']]);
    header("Location: schedule.php");
    exit;
}

$schedule = $db->fetchAll("SELECT * FROM event_schedule WHERE event_id = ? ORDER BY start_time ASC", [$event['id']]);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Akış Programı</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark mb-4 px-4 py-3">
        <a class="navbar-brand" href="dashboard.php">⬅ Panele Dön</a>
        <span class="text-white"><?= htmlspecialchars($event['title']) ?></span>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">Program Ekle</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="add_item" value="1">
                            
                            <div class="mb-3">
                                <label class="form-label">Saat</label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Başlık</label>
                                <input type="text" name="title" class="form-control" placeholder="Örn: Açılış Konuşması" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Açıklama / Konuşmacılar</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Konuşmacı 1: Ali Veli&#10;Konuşmacı 2: Ayşe Yılmaz&#10;Detay: Salon A"></textarea>
                                <div class="form-text">Birden fazla satır yazabilirsiniz.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">İkon</label>
                                <select name="icon" class="form-select">
                                    <option value="fa-microphone">🎤 Mikrofon</option>
                                    <option value="fa-utensils">🍽️ Yemek</option>
                                    <option value="fa-coffee">☕ Kahve Arası</option>
                                    <option value="fa-music">🎵 Müzik/Parti</option>
                                    <option value="fa-users">👥 Panel</option>
                                    <option value="fa-video">🎥 Sunum</option>
                                    <option value="fa-flag-checkered">🏁 Bitiş</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">Ekle</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header">Akış Listesi</div>
                    <ul class="list-group list-group-flush">
                        <?php foreach($schedule as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-start">
                                    <div class="bg-light rounded p-2 me-3 text-center" style="width:60px;">
                                        <strong><?= substr($item['start_time'], 0, 5) ?></strong>
                                    </div>
                                    <div>
                                        <h6 class="m-0 mb-1">
                                            <i class="fa-solid <?= $item['icon'] ?> me-2 text-muted"></i>
                                            <?= htmlspecialchars($item['title']) ?>
                                        </h6>
                                        <small class="text-muted" style="white-space: pre-line;"><?= htmlspecialchars($item['description']) ?></small>
                                    </div>
                                </div>
                                <a href="?delete=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bu maddeyi silmek istediğinize emin misiniz?')"><i class="fa-solid fa-trash"></i></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>