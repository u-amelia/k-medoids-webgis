<?php
$title = "Dashboard";
$judul = "Dashboard";
$fileJs = 'dashboardjs';
?>

<?=$session->pull("info")?>

<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_balita";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch and count status data
$sql = "SELECT status_balita, COUNT(*) as count FROM data_balita GROUP BY status_balita";
$result = $conn->query($sql);

$statusCounts = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $statusCounts[$row['status_balita']] = $row['count'];
    }
} else {
    echo "0 results";
}

// Fetch and count status data by desa
$sqlDesa = "SELECT alamat_balita, status_balita, COUNT(*) as count FROM data_balita GROUP BY alamat_balita, status_balita";
$resultDesa = $conn->query($sqlDesa);

$desaStatusCounts = [];
if ($resultDesa->num_rows > 0) {
    while ($row = $resultDesa->fetch_assoc()) {
        $desa = $row['alamat_balita'];
        $status = $row['status_balita'];
        if (!isset($desaStatusCounts[$desa])) {
            $desaStatusCounts[$desa] = [];
        }
        $desaStatusCounts[$desa][$status] = $row['count'];
    }
} else {
    echo "0 results for desa status";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <style>
    .page-heading {
        margin: 20px 0;
    }

    .page-content {
        padding: 20px;
    }

    .card {
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .card-header {
        background-color: #f7f7f7;
        padding: 10px 15px;
        border-bottom: 1px solid #ddd;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
    }

    .card-body {
        padding: 15px;
    }

    canvas {
        width: 100%;
        height: 300px;
        /* Ubah sesuai kebutuhan Anda */
    }
    </style>
</head>

<body>
    <div class="page-content">
        <section class="row">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Status Gizi Balita (Bar Chart)</h4>
                    </div>
                    <div class="card-body">
                        <div>
                            <canvas id="chart-profile-visit"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Status Gizi Balita (Line Chart)</h4>
                    </div>
                    <div class="card-body">
                        <div>
                            <canvas id="chart-profile-line"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Status Gizi Balita per Desa (Line Chart)</h4>
                    </div>
                    <div class="card-body">
                        <div>
                            <canvas id="chart-desa-status"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Parsing the data from PHP
    var statusCounts = <?php echo json_encode($statusCounts); ?>;
    var labels = Object.keys(statusCounts);
    var data = Object.values(statusCounts);

    // Define colors for each status
    var statusColors = {
        'Gizi Baik': 'rgba(0, 255, 0, 0.2)', // Hijau
        'Gizi Lebih': 'rgba(255, 165, 0, 0.2)', // Oranye
        'Gizi Kurang': 'rgba(255, 255, 0, 0.2)', // Kuning
        'Gizi Buruk': 'rgba(255, 0, 0, 0.2)', // Merah
        'Obesitas': 'rgba(128, 0, 128, 0.2)' // Ungu
    };

    var statusBorders = {
        'Gizi Baik': 'rgba(0, 255, 0, 1)', // Hijau
        'Gizi Lebih': 'rgba(255, 165, 0, 1)', // Oranye
        'Gizi Kurang': 'rgba(255, 255, 0, 1)', // Kuning
        'Gizi Buruk': 'rgba(255, 0, 0, 1)', // Merah
        'Obesitas': 'rgba(128, 0, 128, 1)' // Ungu
    };

    // Configuring the bar chart
    var ctxBar = document.getElementById('chart-profile-visit').getContext('2d');
    var myBarChart = new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '# of Balita',
                data: data,
                backgroundColor: 'rgba(75, 192, 192, 0.2)', // Custom color for bar chart
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Configuring the line chart
    var ctxLine = document.getElementById('chart-profile-line').getContext('2d');
    var myLineChart = new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '# of Balita',
                data: data,
                fill: false,
                borderColor: 'rgba(255, 0, 255)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Parsing and configuring the chart for desa status
    var desaStatusCounts = <?php echo json_encode($desaStatusCounts); ?>;
    console.log('Desa Status Counts:', desaStatusCounts);

    var desaLabels = Object.keys(desaStatusCounts);
    console.log('Desa Labels:', desaLabels);

    var statusLabels = Object.keys(statusCounts);

    // Initialize datasets with fixed colors for each status
    var datasets = statusLabels.map(status => {
        return {
            label: status,
            data: desaLabels.map(desa => desaStatusCounts[desa][status] || 0),
            borderColor: statusBorders[status] || '#000000', // Use default color if not found
            fill: false
        };
    });

    var ctxDesaStatus = document.getElementById('chart-desa-status').getContext('2d');
    var myDesaStatusChart = new Chart(ctxDesaStatus, {
        type: 'line',
        data: {
            labels: desaLabels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    </script>
</body>

</html>