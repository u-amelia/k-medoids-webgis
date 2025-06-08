<?php
$title = "Balita";
$judul = "Data Balita";
$url = "databalita";
?>

<style>
.table-responsive {
    overflow-x: auto;
}

table {
    width: 100%;
}

table td,
table th {
    padding: 15px;
}
</style>

<?php
if (isset($_POST['save'])) {
    $data = [
        'nama_balita' => $_POST['nama_balita'],
        'nik_balita' => $_POST['nik_balita'],
        'lahir_balita' => $_POST['lahir_balita'],
        'kelamin_balita' => $_POST['kelamin_balita'],
        'alamat_balita' => $_POST['alamat_balita'],
        'berat_balita' => $_POST['berat_balita'],
        'tinggi_balita' => $_POST['tinggi_balita'],
        'lkp_balita' => $_POST['lkp_balita'],
        'lila_balita' => $_POST['lila_balita'],
        'status_balita' => $_POST['status_balita'],
    ];

    if (empty($_POST['id_balita'])) {
        $db->insert("data_balita", $data);
        echo '<script type="text/javascript">
            alert("Data Berhasil Disimpan!");
            window.location.href = "' . url('databalita') . '";
        </script>';
    } else {
        $db->where("id_balita", $_POST['id_balita']);
        $db->update("data_balita", $data);
        echo '<script type="text/javascript">
            alert("Data Berhasil Diubah!");
            window.location.href = "' . url('databalita') . '";
        </script>';
    }
}

if (isset($_GET['delete'])) {
    $db->where("id_balita", $_GET['id']);
    $db->delete("data_balita");
    echo '<script type="text/javascript">
        alert("Data Berhasil Dihapus!");
        window.location.href = "' . url('databalita') . '";
    </script>';
}

if (isset($_GET['add']) || isset($_GET['edit'])) {
    $id_balita = $nama_balita = $nik_balita = $lahir_balita = $kelamin_balita = $alamat_balita  = $berat_balita = $tinggi_balita = $lkp_balita = $lila_balita = $status_balita = "";

    if (isset($_GET['edit']) && isset($_GET['id'])) {
        $id = $_GET['id'];
        $db->where('id_balita', $id);
        $row = $db->ObjectBuilder()->getOne('data_balita');
        if ($db->count > 0) {
            $id_balita = $row->id_balita;
            $nama_balita = $row->nama_balita;
            $nik_balita = $row->nik_balita;
            $lahir_balita = $row->lahir_balita;
            $kelamin_balita = $row->kelamin_balita;
            $alamat_balita = $row->alamat_balita;
            $berat_balita = $row->berat_balita;
            $tinggi_balita = $row->tinggi_balita;
            $lkp_balita = $row->lkp_balita;
            $lila_balita = $row->lila_balita;
            $status_balita = $row->status_balita;
        }
    }
?>

<?php
    // Tambahkan kode ini di bagian atas sebelum HTML
    if (isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file'];

        // Validasi file
        if ($file['error'] == UPLOAD_ERR_OK) {
            $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
            if ($fileType != 'csv') {
                echo '<script>alert("Hanya file CSV yang diizinkan!");</script>';
            } else {
                // Baca file CSV
                $handle = fopen($file['tmp_name'], "r");
                $header = true;
                $successCount = 0;
                $errorCount = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if ($header) {
                        $header = false;
                        continue;
                    }

                    // Mapping data CSV ke struktur database
                    $insertData = [
                        'nama_balita'   => $data[0],
                        'nik_balita'    => $data[1],
                        'lahir_balita'  => $data[2],
                        'kelamin_balita' => $data[3],
                        'alamat_balita' => $data[4],
                        'berat_balita'  => $data[5],
                        'tinggi_balita' => $data[6],
                        'lkp_balita'    => $data[7],
                        'lila_balita'   => $data[8],
                        'status_balita' => $data[9]
                    ];

                    try {
                        $db->insert('data_balita', $insertData);
                        $successCount++;
                    } catch (Exception $e) {
                        $errorCount++;
                    }
                }
                fclose($handle);

                echo '<script>
                alert("Import selesai!\\nBerhasil: ' . $successCount . '\\nGagal: ' . $errorCount . '");
                window.location.href = "' . url($url) . '";
            </script>';
            }
        }
    }
    ?>

<div class="page-heading">
    <section id="multiple-column-form">
        <div class="row match-height">
            <div class="col-12">
                <div class="card">
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" data-parsley-validate method="post">
                                <input type="hidden" name="id_balita" value="<?= htmlspecialchars($id_balita) ?>">
                                <div class="row">
                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="nama_balita-column" class="form-label">Nama Balita</label>
                                            <input type="text" name="nama_balita" id="nama_balita-column"
                                                class="form-control" placeholder="Nama Balita"
                                                value="<?= htmlspecialchars($nama_balita) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="nik-balita-column" class="form-label">No. Induk
                                                Kependudukan</label>
                                            <input type="text" name="nik_balita" id="nik-balita-column"
                                                class="form-control" placeholder="No. Induk Kependudukan"
                                                value="<?= htmlspecialchars($nik_balita) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="lahir-balita-column" class="form-label">Tanggal Lahir</label>
                                            <input type="date" name="lahir_balita" id="lahir-balita-column"
                                                class="form-control" placeholder="Tanggal Lahir"
                                                value="<?= htmlspecialchars($lahir_balita) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="jenis-kelamin-column" class="form-label">Jenis Kelamin</label>
                                            <select name="kelamin_balita" id="jenis-kelamin-column" class="form-control"
                                                required>
                                                <option value="">Pilih Jenis Kelamin</option>
                                                <option value="Laki-Laki"
                                                    <?= ($kelamin_balita ?? '') == 'Laki-Laki' ? 'selected' : '' ?>>
                                                    Laki-Laki</option>
                                                <option value="Perempuan"
                                                    <?= ($kelamin_balita ?? '') == 'Perempuan' ? 'selected' : '' ?>>
                                                    Perempuan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="alamat-balita-column" class="form-label">Alamat</label>
                                            <input type="text" name="alamat_balita" id="alamat-balita-column"
                                                class="form-control" placeholder="Alamat"
                                                value="<?= htmlspecialchars($alamat_balita) ?>" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="berat-balita-column" class="form-label">Berat Badan</label>
                                            <input type="text" name="berat_balita" id="berat-balita-column"
                                                class="form-control" placeholder="Berat Badan"
                                                value="<?= htmlspecialchars($berat_balita) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="tinggi-balita-column" class="form-label">Tinggi Badan</label>
                                            <input type="text" name="tinggi_balita" id="tinggi-balita-column"
                                                class="form-control" placeholder="Tinggi Badan"
                                                value="<?= htmlspecialchars($tinggi_balita) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="lkp-balita-column" class="form-label">Lingkar Kepala</label>
                                            <input type="text" name="lkp_balita" id="lkp-balita-column"
                                                class="form-control" placeholder="Lingkar Kepala"
                                                value="<?= htmlspecialchars($lkp_balita) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="lila-balita-column" class="form-label">Lingkar Lengan
                                                Atas</label>
                                            <input type="text" name="lila_balita" id="lila-balita-column"
                                                class="form-control" placeholder="Lingkar Lengan Atas"
                                                value="<?= htmlspecialchars($lila_balita) ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-12">
                                        <div class="form-group mandatory">
                                            <label for="status-balita-column" class="form-label">Status</label>
                                            <select name="status_balita" id="status-balita-column" class="form-control"
                                                required>
                                                <option value="">Pilih Status</option>
                                                <option value="Gizi Buruk"
                                                    <?= $status_balita == 'Gizi Buruk' ? 'selected' : '' ?>>Gizi Buruk
                                                </option>
                                                <option value="Gizi Kurang"
                                                    <?= $status_balita == 'Gizi Kurang' ? 'selected' : '' ?>>Gizi Kurang
                                                </option>
                                                <option value="Gizi Baik"
                                                    <?= $status_balita == 'Gizi Baik' ? 'selected' : '' ?>>Gizi Baik
                                                </option>
                                                <option value="Gizi Lebih"
                                                    <?= $status_balita == 'Gizi Lebih' ? 'selected' : '' ?>>Gizi Lebih
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 d-flex justify-content-end">
                                            <button type="submit" name="save" class="btn btn-primary me-1 mb-1">
                                                <i class="bi bi-plus-square"></i> Submit
                                            </button>
                                            <a href="<?= url($url) ?>" class="btn btn-secondary me-1 mb-1">
                                                <i class="bi bi-reply-all"></i> Back
                                            </a>
                                        </div>
                                    </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
} else {
?>

<section class="section">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <?= htmlspecialchars($judul) ?>
                <a href="<?= url($url . '&add') ?>" class="btn btn-success float-end">
                    <i class="bi bi-cloud-plus"></i> Tambah
                </a>
            </h2>
        </div>
        <div class="card-body">
            <div class="table-responsive datatable-minimal">
                <table class="table display nowrap" id="table1">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Balita</th>
                            <th>NIK. Balita</th>
                            <th>Alamat</th>
                            <th>Detail</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $i = 1;
                            $getdata = $db->ObjectBuilder()->get('data_balita');
                            foreach ($getdata as $row) {
                            ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row->nama_balita) ?></td>
                            <td><?= htmlspecialchars($row->nik_balita) ?></td>
                            <td><?= htmlspecialchars($row->alamat_balita) ?></td>
                            <td>
                                <button class="btn btn-info" data-bs-toggle="modal"
                                    data-bs-target="#myModal<?= $row->id_balita ?>">
                                    <i class="bi bi-info-circle"></i> Detail
                                </button>
                            </td>
                            <td>
                                <a href="<?= url($url . '&edit&id=' . $row->id_balita) ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <a href="<?= url($url . '&delete&id=' . $row->id_balita) ?>" class="btn btn-danger"
                                    onclick="return confirm('Apakah Anda Ingin Menghapus Data ?')">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </td>
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

<?php
    foreach ($getdata as $row) {
    ?>
<div class="modal fade" id="myModal<?= $row->id_balita ?>" tabindex="-1" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">Detail untuk <?= htmlspecialchars($row->nama_balita) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <th scope="row">Nama Balita</th>
                            <td><?= htmlspecialchars($row->nama_balita) ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Nomor Induk Balita</th>
                            <td><?= htmlspecialchars($row->nik_balita) ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Tanggal Lahir</th>
                            <td><?= htmlspecialchars($row->lahir_balita) ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Jenis Kelamin</th>
                            <td><?= htmlspecialchars($row->kelamin_balita) ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Alamat</th>
                            <td><?= htmlspecialchars($row->alamat_balita) ?></td>
                        </tr>
                        <tr>
                            <th scope="row">Umur</th>
                            <td>
                                <?php
                                        // Pastikan data lahir_balita ada
                                        if (!empty($row->lahir_balita)) {
                                            $birthdate = new DateTime($row->lahir_balita);
                                            $today = new DateTime();
                                            $interval = $today->diff($birthdate);

                                            // Format umur: tahun, bulan, hari
                                            $tahun = $interval->y;
                                            $bulan = $interval->m;
                                            $hari = $interval->d;

                                            // Tampilkan dengan contoh format "3 tahun 3 bulan 5 hari"
                                            echo htmlspecialchars("$tahun tahun $bulan bulan $hari hari", ENT_QUOTES, 'UTF-8');
                                        } else {
                                            echo "Data tanggal lahir tidak tersedia";
                                        }
                                        ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Berat Balita</th>
                            <td><?= htmlspecialchars($row->berat_balita) ?> Kg</td>
                        </tr>
                        <tr>
                            <th scope="row">Tinggi Balita</th>
                            <td><?= htmlspecialchars($row->tinggi_balita) ?> Cm</td>
                        </tr>
                        <tr>
                            <th scope="row">Lingkar Kepala</th>
                            <td><?= htmlspecialchars($row->lkp_balita) ?> Cm</td>
                        </tr>
                        <tr>
                            <th scope="row">Lingkar Lengan Atas</th>
                            <td><?= htmlspecialchars($row->lila_balita) ?> Cm</td>
                        </tr>
                        <tr>
                            <th scope="row">Status</th>
                            <td><?= htmlspecialchars($row->status_balita) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i>
                    Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php
    }
}
?>