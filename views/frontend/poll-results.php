<?php
require_once __DIR__ . '/../../src/Database.php';
$db = new Database();

$pollId = $_GET['poll_id'] ?? 0;
if (!$pollId) die("Anket ID belirtilmedi.");

// Anket bilgilerini çek
$poll = $db->fetch("SELECT * FROM polls WHERE id = ?", [$pollId]);

// AJAX MODU: Grafik verilerini döndür
if (isset($_GET['data_json'])) {
    header('Content-Type: application/json');
    // Her şıkkın oy sayısını al
    $results = $db->fetchAll("
        SELECT o.option_text, COUNT(v.id) as vote_count 
        FROM poll_options o 
        LEFT JOIN poll_votes v ON o.id = v.option_id 
        WHERE o.poll_id = ? 
        GROUP BY o.id
    ", [$pollId]);
    
    echo json_encode($results);
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Canlı Sonuçlar</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: black; color: white; height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0; font-family: sans-serif; }
        h1 { font-size: 3rem; margin-bottom: 40px; text-align: center; }
        .chart-container { position: relative; width: 80%; height: 60vh; }
    </style>
</head>
<body>

    <h1><?= htmlspecialchars($poll['question']) ?></h1>
    
    <div class="chart-container">
        <canvas id="pollChart"></canvas>
    </div>

    <script>
        const ctx = document.getElementById('pollChart').getContext('2d');
        let myChart;

        // Grafiği İlk Kez Oluştur
        function initChart(labels, data) {
            myChart = new Chart(ctx, {
                type: 'bar', // 'pie' veya 'doughnut' da yapabilirsin
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Oy Sayısı',
                        data: data,
                        backgroundColor: ['#3498db', '#e74c3c', '#f1c40f', '#2ecc71'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, ticks: { color: 'white' } }, x: { ticks: { color: 'white', font: {size: 20} } } },
                    plugins: { legend: { display: false } }
                }
            });
        }

        // Verileri Sürekli Güncelle
        function updateData() {
            fetch(window.location.href + '&data_json=1')
                .then(res => res.json())
                .then(data => {
                    const labels = data.map(item => item.option_text);
                    const counts = data.map(item => item.vote_count);

                    if (!myChart) {
                        initChart(labels, counts);
                    } else {
                        myChart.data.labels = labels;
                        myChart.data.datasets[0].data = counts;
                        myChart.update();
                    }
                });
        }

        // 2 Saniyede bir güncelle
        setInterval(updateData, 2000);
        updateData();
    </script>

</body>
</html>