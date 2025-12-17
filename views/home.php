<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Manager Pro - Kurumsal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero { 
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1511578314322-379afb476865?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            height: 100vh;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .navbar-custom { background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><i class="fa-solid fa-calendar-check me-2"></i>Event Manager Pro</a>
            <div class="ms-auto">
                <a href="../views/auth/login.php" class="btn btn-outline-light btn-sm">
                    <i class="fa-solid fa-lock me-2"></i> Müşteri Girişi
                </a>
            </div>
        </div>
    </nav>

    <div class="hero">
        <div class="container animate__animated animate__fadeInUp">
            <h1 class="display-3 fw-bold mb-4">Etkinliklerinizi Dijitalleştirin</h1>
            <p class="lead mb-5 fs-4">Kurumsal etkinlikler, lansmanlar ve özel davetler için<br>yeni nesil interaktif yönetim platformu.</p>
            
            <div class="d-flex gap-3 justify-content-center">
                <a href="#features" class="btn btn-primary btn-lg px-5">Keşfet</a>
                <a href="../views/auth/login.php" class="btn btn-outline-light btn-lg px-5">Giriş Yap</a>
            </div>
        </div>
    </div>

    <div id="features" class="py-5 bg-white">
        <div class="container py-5">
            <div class="row text-center g-4">
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="fa-solid fa-qrcode fa-3x text-primary mb-3"></i>
                        <h4>QR Bilet Sistemi</h4>
                        <p class="text-muted">Misafirleriniz kayıt olsun, dijital biletlerini PDF olarak indirsin. Kapıda QR ile hızlı giriş sağlayın.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="fa-solid fa-camera-retro fa-3x text-primary mb-3"></i>
                        <h4>Canlı Anı Duvarı</h4>
                        <p class="text-muted">Etkinlik sırasında çekilen fotoğraflar anında dev ekrana yansısın. Etkileşimi zirveye taşıyın.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4">
                        <i class="fa-solid fa-shield-halved fa-3x text-primary mb-3"></i>
                        <h4>Güvenli & Kontrollü</h4>
                        <p class="text-muted">Tüm içerikler moderasyon panelinden geçer. İstemediğiniz hiçbir görüntü yayına girmez.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 text-center">
    <div class="container">
        <small>
            &copy; 2025 <a href="https://milimetre.com/" target="_blank" class="text-white text-decoration-none fw-bold">Milimetre</a>. 
            Tüm hakları saklıdır.
        </small>
    </div>
</footer>

</body>
</html>