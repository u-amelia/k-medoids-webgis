<?php

// Informasi halaman
$title = "Perhitungan K-Medoids";
$judul = "K-Medoids";
$url   = "perhitungan";

// Definisi nilai $k di sini
$k = 5;

// Panggil fungsi untuk melakukan clustering K-Medoids
performKMedoidsClustering($k);

/**
 * Fungsi utama untuk melakukan clustering K-Medoids
 * @param int $k Jumlah cluster yang diinginkan
 */
function performKMedoidsClustering($k)
{
    // Konfigurasi koneksi database
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "db_balita";

    // Buat koneksi
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Koneksi ke database gagal: " . $conn->connect_error);
    }

    // Ambil data
    $sql = "SELECT id_data_gizi, nama_gizi_desa, gizi_baik_desa, gizi_lebih_desa,
                      gizi_kurang_desa, gizi_buruk_desa, obesitas_gizi_desa
               FROM data_gizi_desa";
    $result = $conn->query($sql);

    if ($result->num_rows === 0) {
        echo "Tidak ada data yang ditemukan.";
        $conn->close();
        return;
    }

    // Simpan ke array
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "id_data_gizi"       => $row["id_data_gizi"],
            "nama_gizi_desa"     => $row["nama_gizi_desa"],
            "gizi_baik_desa"     => $row["gizi_baik_desa"],
            "gizi_lebih_desa"    => $row["gizi_lebih_desa"],
            "gizi_kurang_desa"   => $row["gizi_kurang_desa"],
            "gizi_buruk_desa"    => $row["gizi_buruk_desa"],
            "obesitas_gizi_desa" => $row["obesitas_gizi_desa"],
        ];
    }

    // Tampilkan data & normalisasi
    displayOriginalData($data);
    $normalizedData = normalizeData($data);
    displayNormalizedData($normalizedData);

    // Medoid awal
    $medoids = findInitialMedoids($normalizedData, $k);

    // Hitung total kedekatan iterasi “0” (sebelum loop)
    list(, $d0)         = assignToClusters($normalizedData, $medoids, $k);
    $prevTotalKedekatan = array_sum(array_map('min', $d0));

    $iterations    = 0;
    $maxIterations = 100;

    // Loop: minimal 2 iterasi, berhenti jika tidak ada perbaikan atau medoid tidak berubah
    while ($iterations < $maxIterations) {
        echo "<h3>Medoids Iterasi ke-" . ($iterations + 1) . "</h3>";
        displayFinalMedoids($medoids);

        // Assignment & jarak
        list($clusters, $distances) = assignToClusters($normalizedData, $medoids, $k);

        // Hitung total kedekatan iterasi sekarang
        $currentTotalKedekatan = array_sum(array_map('min', $distances));

        // Tampilkan tabel jarak + kedekatan + total
        displayIterationDistances($normalizedData, $medoids, $distances, $iterations);

        // hitung medoid baru
        $newMedoids = updateMedoids($clusters);

        // hitung total kedekatan untuk medoid baru
        list(, $newDistances) = assignToClusters($normalizedData, $newMedoids, $k);
        $newTotal             = array_sum(array_map('min', $newDistances));

        // hentikan jika tidak ada perbaikan (newTotal >= prev) atau medoid sama
        if ($newTotal >= $prevTotalKedekatan || medoidsEqual($medoids, $newMedoids)) {
            break;
        }

        // simpan medoid dan total baru untuk iterasi berikut
        $medoids            = $newMedoids;
        $prevTotalKedekatan = $newTotal;

        $iterations++;
    }

    // Hasil akhir (pastikan menggunakan medoid terakhir)
    list($clusters, $distances) = assignToClusters($normalizedData, $medoids, $k);
    displayClusters($clusters, $distances);
    displayClusterChart($clusters, $distances);

    $conn->close();
}

/**
 * Fungsi untuk menampilkan jarak setiap iterasi beserta kedekatan (jarak minimum)
 * dan total kedekatan
 * @param array $data Data yang digunakan
 * @param array $medoids Medoids saat ini
 * @param array $distances Jarak antara data dan medoid
 * @param int $iteration Iterasi saat ini
 */
function displayIterationDistances($data, $medoids, $distances, $iteration)
{
    echo "<section class='section'>";
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h2>Jarak Iterasi " . ($iteration + 1) . "</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<div class='table-responsive'>";
    echo "<table id='distancesTable' class='table table-bordered mb-0' style='width: 100%'>";

    // Header tabel
    echo "<thead>";
    echo "<tr>";
    echo "<th>ID</th>";
    echo "<th>Nama Desa</th>";
    foreach ($medoids as $index => $medoid) {
        echo "<th>Count " . ($index + 1) . "</th>";
    }
    echo "<th>Kedekatan</th>";
    echo "</tr>";
    echo "</thead>";

    // Body tabel
    echo "<tbody>";
    $totalKedekatan = 0; // inisialisasi total
    foreach ($data as $dataIndex => $dataPoint) {
        // hitung kedekatan (jarak minimum) untuk baris ini
        $minDistance = min($distances[$dataIndex]);
        $totalKedekatan += $minDistance;

        echo "<tr>";
        echo "<td class='text-center'>{$dataPoint['id_data_gizi']}</td>";
        echo "<td>{$dataPoint['nama_gizi_desa']}</td>";
        foreach ($medoids as $medoidIndex => $medoid) {
            echo "<td class='text-center'>" . round($distances[$dataIndex][$medoidIndex], 3) . "</td>";
        }
        echo "<td class='text-center font-weight-bold'>" . round($minDistance, 3) . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";

    // Footer dengan total kedekatan
    echo "<tfoot>";
    echo "<tr>";
    // colspan = 2 (ID + Nama Desa) + k kolom medoid
    $colspan = 2 + count($medoids);
    echo "<td colspan='{$colspan}' class='text-right font-weight-bold'>Total Kedekatan:</td>";
    echo "<td class='text-center font-weight-bold'>" . round($totalKedekatan, 3) . "</td>";
    echo "</tr>";
    echo "</tfoot>";

    echo "</table>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</section>";
}

/**
 * Fungsi untuk menampilkan data asli
 * @param array $data Data yang akan ditampilkan
 */
function displayOriginalData($data)
{
    echo "<section class='section'>";
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h2>Data Asli</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<div class='table-responsive'>";
    echo "<table id='originalDataTable' class='table table-bordered mb-0' style='width: 100%'>";
    echo "
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Desa</th>
                <th>Gizi Baik</th>
                <th>Gizi Lebih</th>
                <th>Gizi Kurang</th>
                <th>Gizi Buruk</th>
                <th>Obesitas</th>
            </tr>
        </thead>";
    echo "<tbody>";

    foreach ($data as $row) {
        echo "<tr>";
        echo "<td class='text-center'>" . $row["id_data_gizi"] . "</td>";
        echo "<td>" . $row["nama_gizi_desa"] . "</td>";
        echo "<td class='text-center'>" . $row["gizi_baik_desa"] . "</td>";
        echo "<td class='text-center'>" . $row["gizi_lebih_desa"] . "</td>";
        echo "<td class='text-center'>" . $row["gizi_kurang_desa"] . "</td>";
        echo "<td class='text-center'>" . $row["gizi_buruk_desa"] . "</td>";
        echo "<td class='text-center'>" . $row["obesitas_gizi_desa"] . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</section>";

    // Inisialisasi DataTables untuk tabel ini
    echo "
    <script>
        $(document).ready(function() {
            $('#originalDataTable').DataTable();
        });
    </script>
    ";
}

/**
 * Fungsi untuk menormalisasi data
 * @param array $data Data yang akan dinormalisasi
 * @return array Data yang sudah dinormalisasi
 */
function normalizeData($data)
{
    $normalizedData = [];
    $columns        = ["gizi_baik_desa", "gizi_lebih_desa", "gizi_kurang_desa", "gizi_buruk_desa", "obesitas_gizi_desa"];

    // Cari nilai minimum dan maksimum untuk setiap kolom
    $minValues = [];
    $maxValues = [];
    foreach ($columns as $column) {
        $minValues[$column] = min(array_column($data, $column));
        $maxValues[$column] = max(array_column($data, $column));
    }

    foreach ($data as $row) {
        $normalizedRow = $row;
        foreach ($columns as $column) {
            $range = $maxValues[$column] - $minValues[$column];
            if ($range > 0) {
                $normalizedRow[$column] = ($row[$column] - $minValues[$column]) / $range;
            } else {
                $normalizedRow[$column] = 0;
            }
        }
        $normalizedData[] = $normalizedRow;
    }

    return $normalizedData;
}

/**
 * Fungsi untuk menampilkan data normalisasi
 * @param array $data Data yang sudah dinormalisasi
 */
function displayNormalizedData($data)
{
    echo "<section class='section'>";
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h2>Data Normalisasi</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<div class='table-responsive'>";
    echo "<table id='normalizedDataTable1' class='table table-bordered mb-0' style='width: 100%'>";
    echo "
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Desa</th>
                <th>Gizi Baik (Norm)</th>
                <th>Gizi Lebih (Norm)</th>
                <th>Gizi Kurang (Norm)</th>
                <th>Gizi Buruk (Norm)</th>
                <th>Obesitas (Norm)</th>
            </tr>
        </thead>";
    echo "<tbody>";

    foreach ($data as $row) {
        echo "<tr>";
        echo "<td class='text-center'>" . $row["id_data_gizi"] . "</td>";
        echo "<td>" . $row["nama_gizi_desa"] . "</td>";
        echo "<td class='text-center'>" . round($row["gizi_baik_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($row["gizi_lebih_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($row["gizi_kurang_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($row["gizi_buruk_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($row["obesitas_gizi_desa"], 3) . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</section>";

    // Inisialisasi DataTables untuk tabel ini
    echo "
    <script>
        $(document).ready(function() {
            $('#normalizedDataTable1').DataTable();
        });
    </script>
    ";
}

/**
 * Fungsi untuk memilih medoid awal
 * @param array $data Data yang diambil dari database
 * @param int $k Jumlah cluster
 * @return array Medoids awal
 */
function findInitialMedoids($data, $k)
{
                                         // Daftar ID yang dipilih sebagai medoid awal
    $selectedIds = [26, 38, 39, 40, 41]; // Ganti dengan ID yang diinginkan

    $medoids = [];

    foreach ($selectedIds as $id) {
        foreach ($data as $entry) {
            if ($entry['id_data_gizi'] == $id) {
                $medoids[] = $entry;
                break; // Berhenti mencari setelah menemukan
            }
        }
    }

    // Pastikan jumlah medoid sesuai dengan $k
    if (count($medoids) !== $k) {
        throw new Exception("Jumlah medoid awal tidak sesuai dengan nilai k");
    }

    return $medoids;
}

/**
 * Fungsi untuk menampilkan medoid awal
 * @param array $medoids Medoids awal
 */
function displayInitialMedoids($medoids)
{
    echo "<section class='section'>";
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h2>Medoid Awal</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<div class='table-responsive'>";
    echo "<table id='initialMedoidsTable' class='table table-bordered mb-0' style='width: 100%'>";
    echo "
        <thead>
            <tr>
                <th>ID</th>
                <th>Nama Desa</th>
                <th>Gizi Baik</th>
                <th>Gizi Lebih</th>
                <th>Gizi Kurang</th>
                <th>Gizi Buruk</th>
                <th>Obesitas</th>
            </tr>
        </thead>";
    echo "<tbody>";

    foreach ($medoids as $medoid) {
        echo "<tr>";
        echo "<td class='text-center'>" . $medoid["id_data_gizi"] . "</td>";
        echo "<td>" . $medoid["nama_gizi_desa"] . "</td>";
        echo "<td class='text-center'>" . round($medoid["gizi_baik_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($medoid["gizi_lebih_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($medoid["gizi_kurang_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($medoid["gizi_buruk_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($medoid["obesitas_gizi_desa"], 3) . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</section>";

    // Inisialisasi DataTables untuk tabel ini
    echo "
    <script>
        $(document).ready(function() {
            $('#initialMedoidsTable').DataTable();
        });
    </script>
    ";
}

/**
 * Fungsi untuk mengupdate medoids
 * @param array $clusters Daftar cluster
 * @return array Medoids baru
 */
function updateMedoids($clusters)
{
    $newMedoids = [];
    foreach ($clusters as $cluster) {
        if (empty($cluster)) {
            continue;
        }
        // Lewati jika cluster kosong
        $medoid       = findMedoid($cluster);
        $newMedoids[] = $medoid;
    }
    return $newMedoids;
}

/**
 * Fungsi untuk menemukan medoid dalam cluster
 * @param array $cluster Cluster yang akan diproses
 * @return array Medoid
 */
function findMedoid($cluster)
{
    $minDistance = INF;
    $medoid      = null;

    foreach ($cluster as $point) {
        $totalDistance = 0;
        foreach ($cluster as $otherPoint) {
            $totalDistance += euclideanDistance($point, $otherPoint);
        }
        if ($totalDistance < $minDistance) {
            $minDistance = $totalDistance;
            $medoid      = $point;
        }
    }
    return $medoid;
}

/**
 * Fungsi untuk menampilkan medoids akhir
 * @param array $medoids Medoids akhir
 */
function displayFinalMedoids($medoids)
{
    echo "<section class='section'>";
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<div class='table-responsive'>";
    echo "<table id='finalMedoidsTable' class='table table-bordered mb-0' style='width: 100%'>";
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

    foreach ($medoids as $medoid) {
        echo "<tr>";
        echo "<td class='text-center'>" . $medoid["id_data_gizi"] . "</td>";
        echo "<td>" . $medoid["nama_gizi_desa"] . "</td>";
        echo "<td class='text-center'>" . round($medoid["gizi_baik_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($medoid["gizi_lebih_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($medoid["gizi_kurang_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($medoid["gizi_buruk_desa"], 3) . "</td>";
        echo "<td class='text-center'>" . round($medoid["obesitas_gizi_desa"], 3) . "</td>";
        echo "</tr>";
    }

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
    echo "</section>";

    // Inisialisasi DataTables untuk tabel ini
    echo "
    <script>
        $(document).ready(function() {
            $('#finalMedoidsTable').DataTable();
        });
    </script>
    ";
}

/**
 * Fungsi untuk menampilkan cluster
 * @param array $clusters Daftar cluster
 * @param array $distances Jarak antara data dan medoid
 */
function displayClusters($clusters, $distances)
{
    // Tentukan array kategori gizi dan warna latar belakang yang sesuai
    $backgroundColors = [
        'Gizi Baik'   => 'rgba(0, 255, 0, 0.2)',   // Hijau
        'Gizi Lebih'  => 'rgba(255, 255, 0, 0.2)', // Kuning
        'Gizi Kurang' => 'rgba(255, 165, 0, 0.2)', // Oranye
        'Gizi Buruk'  => 'rgba(255, 0, 0, 0.2)',   // Merah
        'Obesitas'    => 'rgba(128, 0, 128, 0.2)', // Ungu
    ];

    // Daftar kategori untuk setiap cluster
    $categories = array_keys($backgroundColors);

    foreach ($clusters as $index => $cluster) {
        // Pilih kategori berdasarkan indeks cluster
        $category        = isset($categories[$index]) ? $categories[$index] : 'Tidak Diketahui';
        $backgroundColor = isset($backgroundColors[$category]) ? $backgroundColors[$category] : 'rgba(0, 0, 0, 0.2)'; // Warna default transparan hitam jika tidak ada warna yang tersedia

        echo "<section class='section'>";
        echo "<div class='card'>";
        echo "<div class='card-header'>";
        // Menambahkan latar belakang dengan warna kategori, dan teks tetap putih
        echo "<h2 style='background-color: " . $backgroundColor . "; color: white; padding: 10px;'>Cluster " . ($index + 1) . " - " . $category . "</h2>";
        echo "</div>";
        echo "<div class='card-body'>";
        echo "<div class='table-responsive'>";
        echo "<table id='cluster" . ($index + 1) . "Table' class='table table-bordered mb-0' style='width: 100%'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Nama Desa</th>";
        echo "<th><center>Jarak ke Medoid</center></th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        foreach ($cluster as $dataIndex => $dataPoint) {
            echo "<tr>";
            echo "<td class='text-center'>" . $dataPoint["id_data_gizi"] . "</td>";
            echo "<td>" . $dataPoint["nama_gizi_desa"] . "</td>";
            echo "<td class='text-center'>" . round($distances[$dataIndex][$index], 3) . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</section>";

        // Inisialisasi DataTables untuk tabel ini
        echo "
        <script>
            $(document).ready(function() {
                $('#cluster" . ($index + 1) . "Table').DataTable();
            });
        </script>
        ";
    }
}

/**
 * Fungsi untuk menghitung jarak Euclidean
 * @param array $point1 Titik pertama
 * @param array $point2 Titik kedua
 * @return float Jarak Euclidean
 */
function euclideanDistance($point1, $point2)
{
    $sum = 0;
    foreach ($point1 as $key => $value) {
        if ($key !== "id_data_gizi" && $key !== "nama_gizi_desa") {
            $sum += pow($value - $point2[$key], 2);
        }
    }
    return sqrt($sum);
}

/**
 * Fungsi untuk menggabungkan data ke dalam cluster
 * @param array $data Data yang dinormalisasi
 * @param array $medoids Medoids saat ini
 * @param int $k Jumlah cluster
 * @return array Daftar cluster dan jarak
 */
function assignToClusters($data, $medoids, $k)
{
    $clusters  = array_fill(0, $k, []); // Membuat array kosong untuk setiap cluster
    $distances = [];                    // Array untuk menyimpan jarak

    foreach ($data as $dataIndex => $dataPoint) {
        $distances[$dataIndex] = [];
        foreach ($medoids as $medoidIndex => $medoid) {
            // Hitung jarak antara dataPoint dan medoid
            $distance                            = euclideanDistance($dataPoint, $medoid);
            $distances[$dataIndex][$medoidIndex] = $distance;
        }
        // Cari medoid terdekat dengan dataPoint
        $closestMedoidIndex              = array_search(min($distances[$dataIndex]), $distances[$dataIndex]);
        $dataPoint['orig_index']         = $dataIndex;
        $clusters[$closestMedoidIndex][] = $dataPoint;
    }

    return [$clusters, $distances]; // Kembalikan cluster dan jarak
}

/**
 * Fungsi untuk memeriksa apakah dua medoids sama
 * @param array $medoids1 Medoids pertama
 * @param array $medoids2 Medoids kedua
 * @return bool Apakah medoids sama
 */
function medoidsEqual($medoids1, $medoids2)
{
    // Tambahkan pengecekan jumlah medoid
    if (count($medoids1) !== count($medoids2)) {
        return false; // Jika jumlahnya berbeda, medoids pasti tidak sama
    }

    $epsilon = 0.00001;
    foreach ($medoids1 as $index => $medoid1) {
        // Pastikan medoid2 dengan index yang sama ada (seharusnya aman setelah pengecekan count)
        if (! isset($medoids2[$index])) {
            return false; // Seharusnya tidak terjadi jika count sama, tapi untuk keamanan
        }
        $medoid2 = $medoids2[$index];

        foreach ($medoid1 as $key => $value) {
            // Pastikan key juga ada di medoid2
            if (! array_key_exists($key, $medoid2)) {
                return false; // Struktur medoid berbeda
            }

            if ($key === "id_data_gizi" || $key === "nama_gizi_desa") {
                if ($medoid1[$key] !== $medoid2[$key]) {
                    return false;
                }
            } else {
                // Pastikan kedua nilai adalah numerik sebelum perbandingan absolut
                if (is_numeric($value) && is_numeric($medoid2[$key])) {
                    if (abs($value - $medoid2[$key]) > $epsilon) {
                        return false;
                    }
                } elseif ($value !== $medoid2[$key]) {
                    // Jika bukan numerik, lakukan perbandingan biasa
                    return false;
                }
            }
        }
    }
    return true;
}

/**
 * Fungsi untuk menampilkan chart dari cluster
 * @param array $clusters Daftar cluster
 * @param array $distances Jarak antara data dan medoid
 */
function displayClusterChart($clusters, $distances)
{
    // Warna untuk setiap cluster
    $colors = [
        'rgba(0, 255, 0, 0.2)',   // Hijau
        'rgba(255, 255, 0, 0.2)', // Kuning
        'rgba(255, 165, 0, 0.2)', // Oranye
        'rgba(255, 0, 0, 0.2)',   // Merah
        'rgba(128, 0, 128, 0.2)', // Ungu
    ];

    // Persiapkan labels unik (nama desa)
    $uniqueLabels = [];
    foreach ($clusters as $cluster) {
        foreach ($cluster as $dataPoint) {
            $label = $dataPoint['nama_gizi_desa'];
            if (! in_array($label, $uniqueLabels)) {
                $uniqueLabels[] = $label; // Tambahkan label jika belum ada
            }
        }
    }

    // **Urutkan labels secara abjad**
    sort($uniqueLabels);

    // Persiapkan datasets untuk setiap cluster
    $datasets = [];
    foreach ($clusters as $index => $cluster) {
        $data = [];

        foreach ($uniqueLabels as $label) {
            // Cari dataPoint yang sesuai dengan label
            $dataPoint = array_filter($cluster, function ($point) use ($label) {
                return trim(strtolower($point['nama_gizi_desa'])) === trim(strtolower($label));
            });

            // Pastikan dataPoint ditemukan
            if (! empty($dataPoint)) {
                $dataPoint = array_values($dataPoint)[0]; // Ambil data pertama yang cocok
                $data[]    = isset($distances[$dataPoint['id_data_gizi']][$index])
                ? round($distances[$dataPoint['id_data_gizi']][$index], 3)
                : null; // Biarkan null jika jarak tidak ditemukan
            } else {
                $data[] = null; // Biarkan null untuk desa yang tidak ada di cluster ini
            }
        }

        // Tambahkan dataset untuk cluster
        $datasets[] = [
            'label'           => 'Cluster ' . ($index + 1),
            'data'            => $data,
            'borderColor'     => str_replace('0.2', '1', $colors[$index] ?? 'rgba(0, 0, 0, 1)'),
            'backgroundColor' => $colors[$index] ?? 'rgba(0, 0, 0, 0.2)',
            'fill'            => false,
            'borderWidth'     => 3,
            'spanGaps'        => true, // Pastikan garis tetap terhubung meskipun ada null
        ];
    }

    // Output chart container
    echo "<section class='section'>";
    echo "<div class='card'>";
    echo "<div class='card-header'>";
    echo "<h2>Grafik Hasil Cluster</h2>";
    echo "</div>";
    echo "<div class='card-body'>";
    echo "<div style='width: 100%; height: 400px;'><canvas id='combinedChart'></canvas></div>";
    echo "</div>";
    echo "</div>";
    echo "</section>";

    // Inisialisasi Chart.js untuk chart
    echo "
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var ctx = document.getElementById('combinedChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: " . json_encode($uniqueLabels) . ", // Label nama desa
                    datasets: " . json_encode($datasets) . " // Data cluster
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 90,
                                minRotation: 45
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    return tooltipItem.dataset.label + ': ' + tooltipItem.raw;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
    ";
}