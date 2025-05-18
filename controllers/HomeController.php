<?php
namespace App\Controllers;

class HomeController
{
    public function index($request = null, $response = null, $args = [])
    {
        global $pdo;

        // Fetch the last QR code created
        $stmt = $pdo->query('SELECT qr_code_path FROM qrcodes ORDER BY created_at DESC LIMIT 1');
        $lastQrCode = $stmt->fetchColumn();

        // Fetch QR code counts per day for the line chart (last 30 days)
        $stmt = $pdo->query('SELECT DATE(created_at) as day, COUNT(*) as count FROM qrcodes WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY day ORDER BY day');
        $qrCodeStats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $days = array_column($qrCodeStats, 'day');
        $counts = array_column($qrCodeStats, 'count');

        // Fetch total QR codes
        $totalQr = $pdo->query('SELECT COUNT(*) FROM qrcodes')->fetchColumn();
        // Fetch total classic (static) QR codes
        $totalClassic = $pdo->query('SELECT COUNT(*) FROM qrcodes WHERE redirect_url IS NULL')->fetchColumn();
        // Fetch total redirected (dynamic) QR codes
        $totalRedirected = $pdo->query('SELECT COUNT(*) FROM qrcodes WHERE redirect_url IS NOT NULL')->fetchColumn();

        // Generate the HTML for the grid (2 columns)
        $html = '<div class="container d-flex justify-content-center align-items-center min-vh-75 push-down" style="max-width:1200px;">'
            .'<div class="row w-100">';

        // Left column: 3 colored cards + last QR
        $html .= '<div class="col-lg-6 mb-4 d-flex flex-column align-items-center">';
        // 3 colored cards
        $html .= '<div class="row mb-4 w-100 justify-content-center">';
        $html .= '<div class="col-12 mb-3 d-flex justify-content-center">'
            .'<div class="card text-white bg-primary h-100 shadow text-center w-100">'
            .'<div class="card-body d-flex flex-column align-items-center">'
            .'<h5 class="card-title">Total QR Codes</h5>'
            .'<p class="card-text display-6">' . htmlspecialchars($totalQr) . '</p>'
            .'</div></div></div>';
        $html .= '<div class="col-12 mb-3 d-flex justify-content-center">'
            .'<div class="card text-white bg-success h-100 shadow text-center w-100">'
            .'<div class="card-body d-flex flex-column align-items-center">'
            .'<h5 class="card-title">Total Classic</h5>'
            .'<p class="card-text display-6">' . htmlspecialchars($totalClassic) . '</p>'
            .'</div></div></div>';
        $html .= '<div class="col-12 mb-3 d-flex justify-content-center">'
            .'<div class="card text-white bg-info h-100 shadow text-center w-100">'
            .'<div class="card-body d-flex flex-column align-items-center">'
            .'<h5 class="card-title">Total Redirected</h5>'
            .'<p class="card-text display-6">' . htmlspecialchars($totalRedirected) . '</p>'
            .'</div></div></div>';
        $html .= '</div>';
        // Last QR code
        if ($lastQrCode) {
            $html .= '<div class="card mt-4 text-center flex-grow-1 shadow w-100 d-flex align-items-center">'
                .'<div class="card-body d-flex flex-column align-items-center">'
                .'<h5 class="card-title">Last QR Code Created</h5>'
                .'<img src="' . htmlspecialchars($lastQrCode) . '" alt="Last QR Code" class="qr-code-img">'
                .'</div></div>';
        } else {
            $html .= '<div class="card mt-4 text-center flex-grow-1 w-100 d-flex align-items-center">'
                .'<div class="card-body d-flex flex-column align-items-center">'
                .'<h5 class="card-title">Last QR Code Created</h5>'
                .'<p>No QR codes found.</p>'
                .'</div></div>';
        }
        $html .= '</div>';

        // Right column: graph + quick preview
        $html .= '<div class="col-lg-6 d-flex flex-column align-items-center">';
        $html .= '<div class="card w-100 shadow mb-4 flex-grow-1 d-flex align-items-center">';
        $html .= '<div class="card-body d-flex flex-column align-items-center">';
        $html .= '<h3 class="card-title text-center mb-4">QR Codes Created Per Day (Last 30 Days)</h3>';
        $html .= '<canvas id="qrLineChart" width="350" height="220"></canvas>';
        $html .= '</div></div>';
        // Quick preview of the list (no image, 5 results)
        $stmt = $pdo->query('SELECT original_url, created_at FROM qrcodes ORDER BY created_at DESC LIMIT 5');
        $previewRows = $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $html .= '<div class="card w-100 shadow mb-3 d-flex align-items-center">';
        $html .= '<div class="card-body d-flex flex-column align-items-center">';
        $html .= '<h5 class="card-title mb-3">Latest 5 QR Codes</h5>';
        $html .= '<div class="table-responsive w-100"><table class="table table-sm table-bordered mb-0 text-center w-100">';
        foreach ($previewRows as $row) {
            $html .= '<tr>';
            $html .= '<td class="text-truncate w-100" style="max-width:none;">' . htmlspecialchars($row['original_url']) . '</td>';
            $html .= '</tr>';
        }
        $html .= '</table></div>';
        $html .= '</div></div>';

        $html .= '</div></div>';

        // Add Chart.js CDN and chart script
        $html .= '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
        $html .= '<script>
            const ctx = document.getElementById("qrLineChart").getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: {
                    labels: ' . json_encode($days) . ',
                    datasets: [{
                        label: "QR Codes Created",
                        data: ' . json_encode($counts) . ',
                        borderColor: "#36a2eb",
                        backgroundColor: "rgba(54,162,235,0.2)",
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: true }
                    },
                    scales: {
                        x: { title: { display: true, text: "Day" } },
                        y: { title: { display: true, text: "QR Codes Created" }, beginAtZero: true }
                    }
                }
            });
        </script>';

        // Build the full HTML page with menu and content
        ob_start();
        $menu = new \App\Controllers\MainMenuController();
        $menu->showMenu();
        $menuHtml = ob_get_clean();

        $fullHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Generator</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/index.css">
</head>
<body>';
        $fullHtml .= $menuHtml;
        $fullHtml .= $html;
        $fullHtml .= '</body></html>';

        if ($response && method_exists($response, 'getBody')) {
            $response->getBody()->write($fullHtml);
            return $response;
        } else {
            echo $fullHtml;
            return null;
        }
    }
}