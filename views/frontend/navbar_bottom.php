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
        display: flex; flex-direction: column; align-items: center; width: 25%;
        transition: 0.2s;
    }
    .nav-btn i { font-size: 1.4rem; margin-bottom: 4px; }
    .nav-btn.active { color: <?= $primaryColor ?? '#0d6efd' ?>; transform: translateY(-3px); }
    .nav-btn.active i { filter: drop-shadow(0 0 5px <?= $primaryColor ?? '#0d6efd' ?>); }
</style>

<div class="bottom-nav">
    <a href="program" class="nav-btn <?= (isset($action) && $action == 'program') ? 'active' : '' ?>">
        <i class="fa-solid fa-calendar-days"></i> Program
    </a>
    <a href="paylas" class="nav-btn <?= (isset($action) && $action == 'paylas') ? 'active' : '' ?>">
        <i class="fa-solid fa-camera"></i> Paylaş
    </a>
    <a href="anket" class="nav-btn <?= (isset($action) && $action == 'anket') ? 'active' : '' ?>">
        <i class="fa-solid fa-square-poll-vertical"></i> Oyla
    </a>
    <a href="biletim" class="nav-btn <?= (isset($action) && $action == 'biletim') ? 'active' : '' ?>">
        <i class="fa-solid fa-ticket"></i> Biletim
    </a>
</div>