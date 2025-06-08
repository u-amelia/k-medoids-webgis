<?php
$title = "Kelompok Status Gizi";
$judul = "Data Kelompok Status Gizi";
$url = "gizi_desa";
?>

<style>
.card-header {
    display: flex;
    justify-content: space-between;
    /* Untuk memposisikan elemen di kiri dan kanan */
    align-items: center;
    /* Untuk memposisikan elemen secara vertikal tengah */
}

.form-right {
    margin-left: auto;
    /* Agar form berada di sebelah kanan */
}
</style>


<?php
// Fungsi untuk menghitung jumlah berdasarkan status gizi
function getNutritionalStatusCount($db, $desa)
{
    $db->where('alamat_balita', $desa);
    $db->groupBy('status_balita');
    $results = $db->get('data_balita', null, 'status_balita, COUNT(*) as count');

    $counts = [];
    foreach ($results as $row) {
        $counts[$row['status_balita']] = $row['count'];
    }

    return $counts;
}

// Variabel untuk menyimpan status penyimpanan
$saveSuccess = false;

if (isset($_POST['submit'])) {
    try {
        $db->startTransaction();

        // Hapus data dengan TRUNCATE
        $truncateResult = $db->rawQuery('TRUNCATE TABLE data_gizi_desa');

        // Validasi penghapusan data
        if ($db->getLastErrno()) {
            throw new Exception("Gagal menghapus data lama: " . $db->getLastError());
        }

        $db->groupBy('alamat_balita');
        $desa_data = $db->get('data_balita', null, 'alamat_balita');

        if ($db->getLastErrno()) {
            throw new Exception("Gagal mengambil data desa: " . $db->getLastError());
        }

        $insertData = [];

        foreach ($desa_data as $desa_row) {
            $desa = $desa_row['alamat_balita'];
            $counts = getNutritionalStatusCount($db, $desa);

            $insertData[] = [
                'nama_gizi_desa' => $desa,
                'gizi_baik_desa' => $counts['Gizi Baik'] ?? 0,
                'gizi_lebih_desa' => $counts['Gizi Lebih'] ?? 0,
                'gizi_kurang_desa' => $counts['Gizi Kurang'] ?? 0,
                'gizi_buruk_desa' => $counts['Gizi Buruk'] ?? 0,
                'obesitas_gizi_desa' => $counts['Obesitas'] ?? 0,
            ];
        }

        if (!empty($insertData)) {
            foreach ($insertData as $data) {
                $id = $db->insert('data_gizi_desa', $data);
                if (!$id) {
                    throw new Exception("Gagal menyimpan data: " . $db->getLastError());
                }
            }
        }

        $db->commit();
        $saveSuccess = true;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Error: " . $e->getMessage());
        $saveSuccess = false;
        // Tampilkan ke user
        echo "<script>console.error('" . addslashes($e->getMessage()) . "')</script>";
    }
}
?>

<!-- Tampilkan pesan jika ada data yang berhasil disimpan menggunakan SweetAlert2 -->
<?php if ($saveSuccess): ?>
<script type="text/javascript">
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: 'Data berhasil diperbarui.',
    confirmButtonText: 'OK',
    customClass: {
        confirmButton: 'btn btn-success'
    }
});
</script>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
<script type="text/javascript">
Swal.fire({
    icon: 'error',
    title: 'Gagal!',
    text: '<?php echo addslashes($errorMessage); ?>',
    confirmButtonText: 'OK',
    customClass: {
        confirmButton: 'btn btn-danger'
    }
});
</script>
<?php endif; ?>

<!-- Tabel Data -->
<section class="section">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><?= $judul ?></h2>
            <!-- Formulir untuk menyimpan data -->
            <div class="form-right">
                <form method="post" action="">
                    <button type="submit" name="submit" class="btn btn-success">Update Data</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive datatable-minimal">
                <table class="table display nowrap">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Desa</th>
                            <th>Gizi Baik</th>
                            <th>Gizi Lebih</th>
                            <th>Gizi Kurang</th>
                            <th>Gizi Buruk</th>
                            <th>Obesitas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $i = 1;

                        // Ambil data dari tabel data_gizi_desa untuk ditampilkan
                        $data_gizi_desa = $db->get('data_gizi_desa');

                        foreach ($data_gizi_desa as $row) {
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['nama_gizi_desa']) ?></td>
                            <td><?= htmlspecialchars($row['gizi_baik_desa']) ?></td>
                            <td><?= htmlspecialchars($row['gizi_lebih_desa']) ?></td>
                            <td><?= htmlspecialchars($row['gizi_kurang_desa']) ?></td>
                            <td><?= htmlspecialchars($row['gizi_buruk_desa']) ?></td>
                            <td><?= htmlspecialchars($row['obesitas_gizi_desa']) ?></td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>