<?php
    // ==============================================
    // Konfigurasi Halaman
    // ==============================================
    $title = "Perhitungan K-Medoids";
    $judul = "K-Medoids";
    $url   = "perhitungan";

    // ==============================================
    // Konfigurasi dan Koneksi Database
    // ==============================================

    $dbHost = "localhost";
    $dbUser = "root";
    $dbPass = "";
    $dbName = "db_balita";

    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_errno) {
        throw new Exception("Koneksi database gagal: " . $mysqli->connect_error);
    }

    // ==============================================
    // Ambil Data Asli dari Database
    // ==============================================
    $query =
        "SELECT id_data_gizi, nama_gizi_desa,
            gizi_baik_desa, gizi_lebih_desa, gizi_kurang_desa,
            gizi_buruk_desa, obesitas_gizi_desa
     FROM data_gizi_desa
     ORDER BY id_data_gizi";

    $result = $mysqli->query($query);
    if (! $result) {
        die("Query gagal: " . $mysqli->error);
    }

    $data_raw_full = [];
    while ($row = $result->fetch_assoc()) {
        $data_raw_full[] = [
            'id_data_gizi'       => $row['id_data_gizi'],
            'nama_gizi_desa'     => $row['nama_gizi_desa'],
            'gizi_baik_desa'     => $row['gizi_baik_desa'],
            'gizi_lebih_desa'    => $row['gizi_lebih_desa'],
            'gizi_kurang_desa'   => $row['gizi_kurang_desa'],
            'gizi_buruk_desa'    => $row['gizi_buruk_desa'],
            'obesitas_gizi_desa' => $row['obesitas_gizi_desa'],
        ];
    }
    $result->free();

    if (count($data_raw_full) === 0) {
        die("<p>Tidak ada data dalam tabel <code>data_gizi_desa</code>.</p>");
    }

    // ==============================================
    // Fungsi Normalisasi Data
    // ==============================================
    function normalize_data(array $data_full): array
    {
        $m        = 5; // jumlah fitur
        $min_vals = array_fill(0, $m, PHP_FLOAT_MAX);
        $max_vals = array_fill(0, $m, -PHP_FLOAT_MAX);

        // Hitung nilai min dan max per fitur
        foreach ($data_full as $item) {
            $vals = [
                floatval($item['gizi_baik_desa']),
                floatval($item['gizi_lebih_desa']),
                floatval($item['gizi_kurang_desa']),
                floatval($item['gizi_buruk_desa']),
                floatval($item['obesitas_gizi_desa']),
            ];
            for ($j = 0; $j < $m; $j++) {
                if ($vals[$j] < $min_vals[$j]) {
                    $min_vals[$j] = $vals[$j];
                }
                if ($vals[$j] > $max_vals[$j]) {
                    $max_vals[$j] = $vals[$j];
                }
            }
        }

        $data_norm_features = [];
        $data_norm_full     = [];

        foreach ($data_full as $item) {
            $raw_vals = [
                floatval($item['gizi_baik_desa']),
                floatval($item['gizi_lebih_desa']),
                floatval($item['gizi_kurang_desa']),
                floatval($item['gizi_buruk_desa']),
                floatval($item['obesitas_gizi_desa']),
            ];
            $norm_feats = [];

            for ($j = 0; $j < $m; $j++) {
                $X    = $raw_vals[$j];
                $Xmin = $min_vals[$j];
                $Xmax = $max_vals[$j];

                if (($Xmax - $Xmin) == 0) {
                    $norm_feats[$j] = 0.0;
                } else {
                    $norm_feats[$j] = ($X - $Xmin) / ($Xmax - $Xmin);
                }
            }

            $data_norm_features[] = [
                'id'       => intval($item['id_data_gizi']),
                'name'     => $item['nama_gizi_desa'],
                'features' => $norm_feats,
            ];

            $data_norm_full[] = [
                'id_data_gizi'       => $item['id_data_gizi'],
                'nama_gizi_desa'     => $item['nama_gizi_desa'],
                'gizi_baik_desa'     => $norm_feats[0],
                'gizi_lebih_desa'    => $norm_feats[1],
                'gizi_kurang_desa'   => $norm_feats[2],
                'gizi_buruk_desa'    => $norm_feats[3],
                'obesitas_gizi_desa' => $norm_feats[4],
            ];
        }

        return [
            'features'   => $data_norm_features,
            'normalized' => $data_norm_full,
            'min_vals'   => $min_vals,
            'max_vals'   => $max_vals,
        ];
    }

    // Lakukan normalisasi
    $normalized_result  = normalize_data($data_raw_full);
    $data_norm_features = $normalized_result['features'];
    $data_norm_full     = $normalized_result['normalized'];
    $min_vals           = $normalized_result['min_vals'];
    $max_vals           = $normalized_result['max_vals'];

    // ==============================================
    // Fungsi K-Medoids (Utilities)
    // ==============================================

    /**
     * Inisialisasi medoids secara acak
     */
    function initialize_medoids(int $n, int $k): array
    {
        $indices = range(0, $n - 1);
        shuffle($indices);
        return array_slice($indices, 0, $k);
    }

    /**
     * Hitung jarak Euclidean antara dua vektor
     */
    function euclidean_distance(array $x, array $y): float
    {
        $sum = 0.0;
        foreach ($x as $j => $val) {
            $sum += pow($val - $y[$j], 2);
        }
        return sqrt($sum);
    }

    /**
     * Assign data ke cluster medoid terdekat dan hitung total cost
     */
    function assign_clusters_and_compute_distances(array $data_norm, array $medoid_idxs): array
    {
        $n           = count($data_norm);
        $k           = count($medoid_idxs);
        $assignments = array_fill(0, $n, -1);
        $total_cost  = 0.0;
        $distances   = array_fill(0, $n, array_fill(0, $k, 0.0));

        foreach ($data_norm as $i => $item) {
            $bestMedoidPos = null;
            $bestDistance  = PHP_FLOAT_MAX;

            foreach ($medoid_idxs as $j => $medIdx) {
                $d = euclidean_distance(
                    $item['features'],
                    $data_norm[$medIdx]['features']
                );
                $distances[$i][$j] = $d;

                if ($d < $bestDistance) {
                    $bestDistance  = $d;
                    $bestMedoidPos = $j;
                }
            }

            $assignments[$i] = $medoid_idxs[$bestMedoidPos];
            $total_cost += $bestDistance;
        }

        return [
            'assignments' => $assignments,
            'total_cost'  => $total_cost,
            'distances'   => $distances,
        ];
    }

    /**
     * Temukan medoids baru berdasarkan cluster assignment
     */
    function find_new_medoids(array $data_norm, array $assignments, array $medoid_idxs): array
    {
        $k        = count($medoid_idxs);
        $clusters = [];

        // Inisialisasi elemen cluster
        foreach ($medoid_idxs as $pos => $medIdx) {
            $clusters[$pos] = [];
        }

        // Kelompokkan index data per medoid
        foreach ($assignments as $i => $assignedMedIdx) {
            $posMed = array_search($assignedMedIdx, $medoid_idxs);
            if ($posMed !== false) {
                $clusters[$posMed][] = $i;
            }
        }

        $new_medoids = [];

        // Untuk tiap cluster, cari anggota yang minimalkan total jarak
        foreach ($clusters as $pos => $member_idxs) {
            if (empty($member_idxs)) {
                // Jika cluster kosong, gunakan medoid lama
                $new_medoids[$pos] = $medoid_idxs[$pos];
                continue;
            }

            $bestCost   = PHP_FLOAT_MAX;
            $bestMedoid = $medoid_idxs[$pos];

            foreach ($member_idxs as $candidateIdx) {
                $tempCost = 0.0;

                foreach ($member_idxs as $otherIdx) {
                    $tempCost += euclidean_distance(
                        $data_norm[$candidateIdx]['features'],
                        $data_norm[$otherIdx]['features']
                    );
                }

                if ($tempCost < $bestCost) {
                    $bestCost   = $tempCost;
                    $bestMedoid = $candidateIdx;
                }
            }

            $new_medoids[$pos] = $bestMedoid;
        }

        return ['new_medoids' => $new_medoids];
    }

    /**
     * Algoritma K-Medoids dengan penyimpanan history setiap iterasi
     */
    function k_medoids_with_history(
        array $data_norm,
        int $k,
        ?array $initial_medoid_idxs = null,
        int $max_iter = 100
    ): array {
        $n = count($data_norm);

        // Validasi medoids awal
        if (is_array($initial_medoid_idxs) && count($initial_medoid_idxs) === $k) {
            foreach ($initial_medoid_idxs as $idx) {
                if ($idx < 0 || $idx >= $n) {
                    $initial_medoid_idxs = null;
                    break;
                }
            }
        }

        // Jika medoids awal tidak valid atau null, lakukan random
        if ($initial_medoid_idxs === null) {
            $medoid_idxs = initialize_medoids($n, $k);
        } else {
            $medoid_idxs = $initial_medoid_idxs;
        }

        $medoids_history   = [];
        $distances_history = [];
        $prev_total_cost   = PHP_FLOAT_MAX;

        for ($iter = 0; $iter < $max_iter; $iter++) {
            $medoids_history[] = $medoid_idxs;

            // Hitung assignment dan jarak
            $assignResult        = assign_clusters_and_compute_distances($data_norm, $medoid_idxs);
            $assignments         = $assignResult['assignments'];
            $total_cost          = $assignResult['total_cost'];
            $distances           = $assignResult['distances'];
            $distances_history[] = $distances;

            // Cek konvergensi
            if ($total_cost >= $prev_total_cost) {
                break;
            }
            $prev_total_cost = $total_cost;

            // Cari medoids baru
            $newMedoidResult = find_new_medoids($data_norm, $assignments, $medoid_idxs);
            $new_medoids     = $newMedoidResult['new_medoids'];

            // Jika medoids tidak berubah (setelah sortir), berhenti
            $sorted_old = $medoid_idxs;
            sort($sorted_old);
            $sorted_new = $new_medoids;
            sort($sorted_new);

            if ($sorted_old === $sorted_new) {
                break;
            }

            $medoid_idxs = $new_medoids;
        }

        // Hitung hasil akhir
        $finalAssign = assign_clusters_and_compute_distances($data_norm, $medoid_idxs);

        return [
            'final_medoids'     => $medoid_idxs,
            'assignments'       => $finalAssign['assignments'],
            'total_cost'        => $finalAssign['total_cost'],
            'medoids_history'   => $medoids_history,
            'distances_history' => $distances_history,
        ];
    }

    // ==============================================
    // Fungsi Tampilan (Display)
    // Semua fungsi display berada di bagian ini
    // ==============================================

    function displayOriginalData(array $data)
    {
        echo "<section class='section'>";
        echo "<div class='card'>";
        echo "<div class='card-header'><h2>Data Asli</h2></div>";
        echo "<div class='card-body'><div class='table-responsive'>";
        echo "<table id='originalDataTable' class='table table-bordered mb-0' style='width: 100%'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Nama Desa</th>";
        echo "<th>Gizi Baik</th>";
        echo "<th>Gizi Lebih</th>";
        echo "<th>Gizi Kurang</th>";
        echo "<th>Gizi Buruk</th>";
        echo "<th>Obesitas</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        foreach ($data as $row) {
            echo "<tr>";
            echo "<td class='text-center'>" . htmlspecialchars($row['id_data_gizi']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_gizi_desa']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($row['gizi_baik_desa']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($row['gizi_lebih_desa']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($row['gizi_kurang_desa']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($row['gizi_buruk_desa']) . "</td>";
            echo "<td class='text-center'>" . htmlspecialchars($row['obesitas_gizi_desa']) . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
        echo "</div></div></div>";
        echo "</section>";

        // Inisialisasi DataTables
        echo "<script>$(document).ready(function() { $('#originalDataTable').DataTable(); });</script>";
    }

    function displayMinMax(array $min_vals, array $max_vals)
    {
        $features = [
            'Gizi Baik',
            'Gizi Lebih',
            'Gizi Kurang',
            'Gizi Buruk',
            'Obesitas',
        ];

        echo "<section class='section'>";
        echo "<div class='card'>";
        echo "<div class='card-header'><h2>Nilai Minimum & Maksimum (Sebelum Normalisasi)</h2></div>";
        echo "<div class='card-body'><div class='table-responsive'>";
        echo "<table id='minMaxTable' class='table table-bordered mb-0' style='width:100%'>";

                                     // Header fitur
        echo "<thead><tr><th></th>"; // Sel kosong untuk baris label
        foreach ($features as $feature) {
            echo "<th class='text-center'>" . htmlspecialchars($feature) . "</th>";
        }
        echo "</tr></thead>";

        // Nilai Min
        echo "<tbody>";
        echo "<tr><td><strong>Nilai Min</strong></td>";
        foreach ($min_vals as $min) {
            echo "<td class='text-center'>" . htmlspecialchars($min) . "</td>";
        }
        echo "</tr>";

        // Nilai Max
        echo "<tr><td><strong>Nilai Max</strong></td>";
        foreach ($max_vals as $max) {
            echo "<td class='text-center'>" . htmlspecialchars($max) . "</td>";
        }
        echo "</tr>";
        echo "</tbody>";

        echo "</table>";
        echo "</div></div></div>";
        echo "</section>";

        echo "<script>$(document).ready(function() { $('#minMaxTable').DataTable({ paging: false, searching: false, info: false }); });</script>";
    }

    function displayNormalizedData(array $data)
    {
        echo "<section class='section'>";
        echo "<div class='card'>";
        echo "<div class='card-header'><h2>Data Normalisasi</h2></div>";
        echo "<div class='card-body'><div class='table-responsive'>";
        echo "<table id='normalizedDataTable' class='table table-bordered mb-0' style='width: 100%'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Nama Desa</th>";
        echo "<th>Gizi Baik</th>";
        echo "<th>Gizi Lebih</th>";
        echo "<th>Gizi Kurang</th>";
        echo "<th>Gizi Buruk</th>";
        echo "<th>Obesitas</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        foreach ($data as $row) {
            echo "<tr>";
            echo "<td class='text-center'>" . htmlspecialchars($row['id_data_gizi']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nama_gizi_desa']) . "</td>";
            echo "<td class='text-center'>" . number_format($row['gizi_baik_desa'], 3, '.', '') . "</td>";
            echo "<td class='text-center'>" . number_format($row['gizi_lebih_desa'], 3, '.', '') . "</td>";
            echo "<td class='text-center'>" . number_format($row['gizi_kurang_desa'], 3, '.', '') . "</td>";
            echo "<td class='text-center'>" . number_format($row['gizi_buruk_desa'], 3, '.', '') . "</td>";
            echo "<td class='text-center'>" . number_format($row['obesitas_gizi_desa'], 3, '.', '') . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
        echo "</div></div></div>";
        echo "</section>";

        echo "<script>$(document).ready(function() { $('#normalizedDataTable').DataTable(); });</script>";
    }

    function displayFinalMedoids(array $medoids, array $data_norm_full, int $iterations)
    {
        echo "<section class='section'>";
        echo "<div class='card'>";
        echo "<div class='card-header'><h2>Medoid Iterasi " . ($iterations + 1) . "</h2></div>";
        echo "<div class='card-body'><div class='table-responsive'>";
        echo "<table id='finalMedoidsTable{$iterations}' class='table table-bordered mb-0' style='width: 100%'>";
        echo "<thead><tr>";
        echo "<th>ID</th>";
        echo "<th>Nama Desa</th>";
        echo "<th>Gizi Baik</th>";
        echo "<th>Gizi Lebih</th>";
        echo "<th>Gizi Kurang</th>";
        echo "<th>Gizi Buruk</th>";
        echo "<th>Obesitas</th>";
        echo "</tr></thead>";
        echo "<tbody>";

        foreach ($medoids as $idx) {
            $medoid = $data_norm_full[$idx];
            echo "<tr>";
            echo "<td class='text-center'>" . htmlspecialchars($medoid['id_data_gizi']) . "</td>";
            echo "<td>" . htmlspecialchars($medoid['nama_gizi_desa']) . "</td>";
            echo "<td class='text-center'>" . number_format($medoid['gizi_baik_desa'], 3, '.', '') . "</td>";
            echo "<td class='text-center'>" . number_format($medoid['gizi_lebih_desa'], 3, '.', '') . "</td>";
            echo "<td class='text-center'>" . number_format($medoid['gizi_kurang_desa'], 3, '.', '') . "</td>";
            echo "<td class='text-center'>" . number_format($medoid['gizi_buruk_desa'], 3, '.', '') . "</td>";
            echo "<td class='text-center'>" . number_format($medoid['obesitas_gizi_desa'], 3, '.', '') . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
        echo "</div></div></div>";
        echo "</section>";

        echo "<script>$(document).ready(function() { $('#finalMedoidsTable{$iterations}').DataTable(); });</script>";
    }

    function displayIterationDistances(array $data, array $medoids, array $distances, int $iteration)
    {
        echo "<section class='section'>";
        echo "<div class='card'>";
        echo "<div class='card-header'><h2>Jarak Iterasi " . ($iteration + 1) . "</h2></div>";
        echo "<div class='card-body'><div class='table-responsive'>";
        echo "<table id='distancesTable{$iteration}' class='table table-bordered mb-0' style='width: 100%'>";

        // Header
        echo "<thead><tr>";
        echo "<th>ID</th>";
        echo "<th>Nama Desa</th>";
        foreach ($medoids as $index => $medoid) {
            echo "<th>Count " . ($index + 1) . "</th>";
        }
        echo "<th>Kedekatan</th>";
        echo "</tr></thead>";

        // Body
        echo "<tbody>";
        $totalKedekatan = 0;
        foreach ($data as $dataIndex => $dataPoint) {
            $minDistance = min($distances[$dataIndex]);
            $totalKedekatan += $minDistance;

            echo "<tr>";
            echo "<td class='text-center'>" . htmlspecialchars($dataPoint['id_data_gizi']) . "</td>";
            echo "<td>" . htmlspecialchars($dataPoint['nama_gizi_desa']) . "</td>";
            foreach ($medoids as $medoidIndex => $medoid) {
                echo "<td class='text-center'>" . number_format($distances[$dataIndex][$medoidIndex], 3) . "</td>";
            }
            echo "<td class='text-center'>" . number_format($minDistance, 3) . "</td>";
            echo "</tr>";
        }
        echo "</tbody>";

        // Footer total kedekatan
        echo "<tfoot>";
        echo "<tr>";
        echo "<th colspan='7' style='text-align:right;'>Total Kedekatan:</th>";
        echo "<th colspan='1' class='text-center'>" . number_format($totalKedekatan, 3) . "</th>";
        echo "</tr>";
        echo "</tfoot>";

        echo "</table>";
        echo "</div></div></div>";
        echo "</section>";

        echo "<script>$(document).ready(function() { $('#distancesTable{$iteration}').DataTable(); });</script>";
    }

    function displayClusters(array $clusters, array $data_raw_full, array $distances_final)
    {
        $backgroundColors = [
            'Gizi Baik'   => '#56D800FF',
            'Gizi Lebih'  => '#DDD200FF',
            'Gizi Kurang' => '#FF6600FF',
            'Gizi Buruk'  => '#FF0000FF',
            'Obesitas'    => '#8C00FFFF',
        ];
        $categories = array_keys($backgroundColors);

        foreach ($clusters as $clusterIndex => $clusterAssoc) {
            $category        = $categories[$clusterIndex] ?? 'Tidak Diketahui';
            $backgroundColor = $backgroundColors[$category] ?? 'rgba(0, 0, 0, 0.2)';

            echo "<section class='section'>";
            echo "<div class='card'>";
            echo "<div class='card-header'>";
            echo "<h2 style='background-color: {$backgroundColor}; color: white; padding: 10px;'>Cluster " . ($clusterIndex + 1) . " - " . $category . "</h2>";
            echo "</div>";
            echo "<div class='card-body'><div class='table-responsive'>";
            echo "<table id='cluster" . ($clusterIndex + 1) . "Table' class='table table-bordered mb-0' style='width: 100%'>";
            echo "<thead><tr><th>ID</th><th>Nama Desa</th><th><center>Jarak ke Medoid</center></th></tr></thead>";
            echo "<tbody>";

            foreach ($clusterAssoc as $originalIndex => $dataRow) {
                echo "<tr>";
                echo "<td class='text-center'>" . htmlspecialchars($dataRow['id_data_gizi']) . "</td>";
                echo "<td>" . htmlspecialchars($dataRow['nama_gizi_desa']) . "</td>";
                echo "<td class='text-center'>" . number_format($distances_final[$originalIndex][$clusterIndex], 3, '.', '') . "</td>";
                echo "</tr>";
            }

            echo "</tbody>";
            echo "</table>";
            echo "</div></div></div>";
            echo "</section>";

            echo "<script>$(document).ready(function() { $('#cluster" . ($clusterIndex + 1) . "Table').DataTable(); });</script>";
        }
    }

    function displayScatterClusterChart(array $clusters, array $data_raw_full, array $distances_final)
    {
        $colors = [
            '#56D800FF',
            '#DDD200FF',
            '#FF6600FF',
            '#FF0000FF',
            '#8C00FFFF',
        ];

        $uniqueLabels = [];
        foreach ($clusters as $clusterAssoc) {
            foreach ($clusterAssoc as $dataPoint) {
                $label = $dataPoint['nama_gizi_desa'];
                if (! in_array($label, $uniqueLabels)) {
                    $uniqueLabels[] = $label;
                }
            }
        }
        sort($uniqueLabels);

        $datasets = [];
        foreach ($clusters as $clusterIndex => $clusterAssoc) {
            $dataPoints = [];

            foreach ($uniqueLabels as $label) {
                $found = false;
                foreach ($clusterAssoc as $origIdx => $dataRow) {
                    if (trim(strtolower($dataRow['nama_gizi_desa'])) === trim(strtolower($label))) {
                        if (isset($distances_final[$origIdx][$clusterIndex])) {
                            $dataPoints[] = [
                                'x' => $label,
                                'y' => round($distances_final[$origIdx][$clusterIndex], 3),
                            ];
                        }
                        $found = true;
                        break;
                    }
                }
            }

            $datasets[] = [
                'label'                => 'Cluster ' . ($clusterIndex + 1),
                'data'                 => $dataPoints,
                'pointBackgroundColor' => $colors[$clusterIndex] ?? '#000000FF',
                'pointBorderColor'     => $colors[$clusterIndex] ?? '#000000FF',
                'pointRadius'          => 5,
                'showLine'             => false,
            ];
        }

        echo "<section class='section'>";
        echo "<div class='card'>";
        echo "<div class='card-header'><h2>Grafik Scatter Plot Hasil Cluster</h2></div>";
        echo "<div class='card-body'><div style='width: 100%; height: 400px;'><canvas id='scatterClusterChart'></canvas></div></div></div>";
        echo "</section>";

        echo "<script>document.addEventListener('DOMContentLoaded', function() { var ctx = document.getElementById('scatterClusterChart').getContext('2d'); var scatterChart = new Chart(ctx, { type: 'scatter', data: { labels: " . json_encode($uniqueLabels) . ", datasets: " . json_encode($datasets) . " }, options: { responsive: true, maintainAspectRatio: false, scales: { x: { type: 'category', labels: " . json_encode($uniqueLabels) . ", ticks: { autoSkip: false, maxRotation: 90, minRotation: 45 }, title: { display: true, text: 'Nama Desa' } }, y: { beginAtZero: true, title: { display: true, text: 'Jarak' } } }, plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: function(context) { return context.dataset.label + ' – ' + context.raw.x + ': ' + context.raw.y; } } } } } }); });</script>";
    }

    function displayClusterChart(array $clusters, array $data_raw_full, array $distances_final)
    {
        $colors = [
            '#56D800FF',
            '#DDD200FF',
            '#FF6600FF',
            '#FF0000FF',
            '#8C00FFFF',
        ];

        $uniqueLabels = [];
        foreach ($clusters as $clusterAssoc) {
            foreach ($clusterAssoc as $dataPoint) {
                $label = $dataPoint['nama_gizi_desa'];
                if (! in_array($label, $uniqueLabels)) {
                    $uniqueLabels[] = $label;
                }
            }
        }
        sort($uniqueLabels);

        $datasets = [];
        foreach ($clusters as $index => $clusterAssoc) {
            $dataSeries = [];
            foreach ($uniqueLabels as $label) {
                $found = false;
                foreach ($clusterAssoc as $origIdx => $dataRow) {
                    if (trim(strtolower($dataRow['nama_gizi_desa'])) === trim(strtolower($label))) {
                        $dataSeries[] = isset($distances_final[$origIdx][$index])
                        ? round($distances_final[$origIdx][$index], 3)
                        : null;
                        $found = true;
                        break;
                    }
                }
                if (! $found) {
                    $dataSeries[] = null;
                }
            }

            $datasets[] = [
                'label'           => 'Cluster ' . ($index + 1),
                'data'            => $dataSeries,
                'borderColor'     => $colors[$index] ?? '#000000FF',
                'backgroundColor' => $colors[$index] ?? '#00000033',
                'fill'            => false,
                'borderWidth'     => 3,
                'spanGaps'        => true,
            ];
        }

        echo "<section class='section'>";
        echo "<div class='card'>";
        echo "<div class='card-header'><h2>Grafik Line Hasil Cluster</h2></div>";
        echo "<div class='card-body'><div style='width: 100%; height: 400px;'><canvas id='combinedChart'></canvas></div></div></div>";
        echo "</section>";

        echo "<script>document.addEventListener('DOMContentLoaded', function() { var ctx = document.getElementById('combinedChart').getContext('2d'); var chart = new Chart(ctx, { type: 'line', data: { labels: " . json_encode($uniqueLabels) . ", datasets: " . json_encode($datasets) . " }, options: { responsive: true, maintainAspectRatio: false, scales: { x: { ticks: { autoSkip: false, maxRotation: 90, minRotation: 45 } }, y: { beginAtZero: true } }, plugins: { legend: { position: 'top' }, tooltip: { callbacks: { label: function(context) { return context.dataset.label + ': ' + context.raw; } } } } } }); });</script>";
    }

    function findInitialMedoidIdxs(array $data_raw, array $data_norm, array $selectedIds, int $k): array
    {
        $idxs = [];
        foreach ($selectedIds as $id) {
            foreach ($data_norm as $idx => $entry) {
                if ($entry['id'] === $id) {
                    $idxs[] = $idx;
                    break;
                }
            }
        }
        if (count($idxs) !== $k) {
            throw new Exception("Jumlah medoid awal tidak sesuai dengan nilai k atau ID tidak ditemukan.");
        }
        return $idxs;
    }

    // Tutup koneksi database
    $mysqli->close();
?>

<?php
    // ==============================================
    // Pemrosesan Utama K-Medoids & Tampilan
    // ==============================================

    $k = 5;
    $n = count($data_norm_features);

    if ($k <= 0 || $k > $n) {
        echo "<div class='alert alert-danger'>Nilai <code>k</code> harus di antara 1 dan $n.</div>";
    } else {
        try {
            $selectedIds         = [26, 38, 39, 40, 41];
            $initial_medoid_idxs = findInitialMedoidIdxs($data_raw_full, $data_norm_features, $selectedIds, $k);
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            $initial_medoid_idxs = null;
        }

        $result            = k_medoids_with_history($data_norm_features, $k, $initial_medoid_idxs);
        $final_medoids     = $result['final_medoids'];
        $assignments       = $result['assignments'];
        $total_cost        = $result['total_cost'];
        $medoids_history   = $result['medoids_history'];
        $distances_history = $result['distances_history'];

        // Tampilkan data asli
        displayOriginalData($data_raw_full);

        // Tampilkan nilai min & max
        displayMinMax($min_vals, $max_vals);

        // Tampilkan data ter-normalisasi
        displayNormalizedData($data_norm_full);

        // Tampilkan medoids dan jarak per iterasi
        foreach ($medoids_history as $iter => $medoid_idxs) {
            displayFinalMedoids($medoid_idxs, $data_norm_full, $iter);
            displayIterationDistances($data_norm_full, $medoid_idxs, $distances_history[$iter], $iter);
        }

        // Siapkan data cluster final untuk tampilan
        $clusters_indices = [];
        foreach ($final_medoids as $pos => $medIdx) {
            $clusters_indices[$pos] = [];
        }
        foreach ($assignments as $i => $assignedMedIdx) {
            $posMedoid = array_search($assignedMedIdx, $final_medoids);
            if ($posMedoid !== false) {
                $clusters_indices[$posMedoid][] = $i;
            }
        }

        $clusters_for_display = [];
        foreach ($clusters_indices as $clusterIndex => $member_idxs) {
            $clusters_for_display[$clusterIndex] = [];
            foreach ($member_idxs as $origIdx) {
                $clusters_for_display[$clusterIndex][$origIdx] = $data_raw_full[$origIdx];
            }
        }

        $last_iter_index = count($distances_history) - 1;
        $distances_final = $distances_history[$last_iter_index];

        // Tampilkan tabel cluster\
        displayClusters($clusters_for_display, $data_raw_full, $distances_final);

        // Tampilkan scatter plot cluster
        displayScatterClusterChart($clusters_for_display, $data_raw_full, $distances_final);

        // Tampilkan line chart cluster
        displayClusterChart($clusters_for_display, $data_raw_full, $distances_final);
}
?>