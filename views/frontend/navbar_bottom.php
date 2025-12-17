<?php
// views/frontend/navbar_bottom.php

// Eğer bu dosya tek başına çağrılırsa diye dil kontrolü (Güvenlik)
if (!isset($lang)) {
    // Üst dosyadan gelmediyse varsayılan olarak TR yükle veya boş bırakma
    $lang = require __DIR__ . '/../../lang/tr.php';
}
?>
<style>
    body { padding-bottom: 80px; } /* Menü içeriği kapatmasın */
    .bottom-nav {
        position: fixed; bottom: 0; left: 0; width: 100%;
        background: rgba(20, 20, 20, 0.95); backdrop-filter: blur(10px);
        border-top: 1px solid rgba(255,255,255,0.1);
        display: flex; justify-content: space-around; padding: 12px 0; z-index: 9999;
    }
    
    .nav-btn {
        color: #888; text-decoration: none; text-align: center; font-size: 0.7rem;
        display: flex; flex-direction: column; align-items: center;
        flex: 1; /* 5 butonu sığdırmak için width yerine flex:1 kullandık */
        transition: 0.2s;
    }
    .nav-btn i { font-size: 1.4rem; margin-bottom: 4px; }
    
    /* Aktif menü rengini admin panelinden gelen renge göre ayarla */
    .nav-btn.active { color: <?= $primaryColor ?? '#0d6efd' ?>; transform: translateY(-3px); }
    .nav-btn.active i { filter: drop-shadow(0 0 5px <?= $primaryColor ?? '#0d6efd' ?>); }
</style>

<div class="bottom-nav">
    
    <a href="program" class="nav-btn <?= (isset($action) && $action == 'program') ? 'active' : '' ?>">
        <i class="fa-solid fa-calendar-days"></i> 
        <?= $lang['nav_program'] ?>
    </a>

    <a href="canli" class="nav-btn <?= (isset($action) && $action == 'canli') ? 'active' : '' ?>">
        <i class="fa-solid fa-fire"></i> 
        <?= $lang['nav_stream'] ?>
    </a>

    <a href="paylas" class="nav-btn <?= (isset($action) && $action == 'paylas') ? 'active' : '' ?>">
        <i class="fa-solid fa-camera"></i> 
        <?= $lang['nav_share'] ?>
    </a>

    <a href="anket" class="nav-btn <?= (isset($action) && $action == 'anket') ? 'active' : '' ?>">
        <i class="fa-solid fa-square-poll-vertical"></i> 
        <?= $lang['nav_vote'] ?>
    </a>

    <a href="biletim" class="nav-btn <?= (isset($action) && $action == 'biletim') ? 'active' : '' ?>">
        <i class="fa-solid fa-ticket"></i> 
        <?= $lang['nav_ticket'] ?>
    </a>

</div>