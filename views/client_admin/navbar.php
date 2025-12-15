<?php
// views/client_admin/navbar.php

// Oturum başlatılmamışsa başlat
if (session_status() === PHP_SESSION_NONE) session_start();

// Kullanıcının başka etkinliği var mı kontrol et (Butonu göstermek için)
// Not: $db nesnesi sayfadan geliyor varsayıyoruz.
$dbCheck = new Database();
$myEventCount = $dbCheck->rowCount("SELECT id FROM events WHERE user_id = ?", [$_SESSION['user_id']]);

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm py-3">
    <div class="container-fluid px-4">
        
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="fa-solid fa-layer-group text-primary me-2"></i>Panel
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="fa-solid fa-images me-1"></i> Medya
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentPage, ['guests.php', 'schedule.php', 'sessions.php']) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-clipboard-list me-1"></i> Organizasyon
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="guests.php"><i class="fa-solid fa-users me-2"></i> Misafir Listesi</a></li>
                        <li><a class="dropdown-item" href="schedule.php"><i class="fa-solid fa-calendar-days me-2"></i> Program Akışı</a></li>
                        <li><a class="dropdown-item" href="sessions.php"><i class="fa-solid fa-door-open me-2"></i> Oturum Yönetimi</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentPage, ['raffle.php', 'polls.php']) ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Sahne & Ekran
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item" href="raffle.php"><i class="fa-solid fa-trophy me-2 text-warning"></i> Çekiliş Yap</a></li>
                        <li><a class="dropdown-item" href="polls.php"><i class="fa-solid fa-square-poll-vertical me-2 text-info"></i> Canlı Oylama</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../../public/<?= htmlspecialchars($event['slug']) ?>/akis" target="_blank">
                                <i class="fa-solid fa-tv me-2"></i> Canlı Duvarı Aç
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentPage == 'checkin.php' ? 'active' : '' ?>" href="checkin.php">
                        <i class="fa-solid fa-qrcode me-1 text-success"></i> Kapı Kontrol
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-mobile-screen-button me-1 text-warning"></i> Misafir Ekranları
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li>
                            <a class="dropdown-item" href="../../public/<?= htmlspecialchars($event['slug']) ?>/kayit" target="_blank">
                                <i class="fa-solid fa-user-plus me-2"></i> Kayıt Sayfası
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../../public/<?= htmlspecialchars($event['slug']) ?>/anket" target="_blank">
                                <i class="fa-solid fa-square-poll-vertical me-2"></i> Oylama Ekranı
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="../../public/<?= htmlspecialchars($event['slug']) ?>/paylas" target="_blank">
                                <i class="fa-solid fa-camera me-2"></i> Anı Paylaş
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="../../public/<?= htmlspecialchars($event['slug']) ?>" target="_blank">
                                <i class="fa-solid fa-house me-2"></i> Ana Sayfa (Landing)
                            </a>
                        </li>
                    </ul>
                </li>
                </ul>

            <div class="d-flex align-items-center border-start border-secondary ps-lg-3 ms-lg-3 mt-3 mt-lg-0">
                <div class="text-white text-end me-3 d-none d-lg-block" style="line-height: 1.2;">
                    <small class="text-white-50" style="font-size: 0.75rem;">AKTİF ETKİNLİK</small><br>
                    <span class="fw-bold"><?= htmlspecialchars($event['title']) ?></span>
                </div>
                
                <?php if ($myEventCount > 1): ?>
                    <a href="dashboard.php?switch_event=clear" class="btn btn-outline-light btn-sm me-2" title="Etkinlik Değiştir">
                        <i class="fa-solid fa-repeat"></i>
                    </a>
                <?php endif; ?>

                <a href="../../logout.php" class="btn btn-danger btn-sm px-3">
                    <i class="fa-solid fa-power-off"></i>
                </a>
            </div>

        </div>
    </div>
</nav>