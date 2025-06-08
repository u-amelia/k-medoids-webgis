<?php
    $title = "Desa";
    $judul = "Data Desa";
    $url   = "desa";

    if (isset($_POST['save'])) {

        $file = upload('geojson_desa', 'geojson');
        if ($file != false) {
            $data['geojson_desa'] = $file;
        }

        if ($_POST['id_desa'] == "") {
            $data['kode_desa'] = $_POST['kode_desa'];
            $data['kecamatan'] = $_POST['kecamatan'];
            $data['nama_desa'] = $_POST['nama_desa'];

            $exec = $db->insert("data_desa", $data);

            $info = '<div class="alert alert-light-success color-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle"></i> Data berhasil ditambahkan. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        } else {

            $data['kode_desa'] = $_POST['kode_desa'];
            $data['kecamatan'] = $_POST['kecamatan'];
            $data['nama_desa'] = $_POST['nama_desa'];

            $db->where("id_desa", $_POST['id_desa']);

            $exec = $db->update("data_desa", $data);

            $info = '<div class="alert alert-light-success color-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle"></i> Data berhasil diubah. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';

            if ($exec) {
                $session->set('info', $info);
            } else {
                $session->set('info', '<div class="alert alert-light-danger color-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-circle"></i> Proses gagal dilakukan! <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
            }
        }
        redirect(url($url));
    }

    if (isset($_GET['delete'])) {
        $db->where("id_desa", $_GET['id']);
        $exec = $db->delete("data_desa");

        $info = '<div class="alert alert-light-success color-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle"></i> Data berhasil dihapus. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';

        if ($exec) {
            $session->set('info', $info);
        } else {
            $session->set('info', '<div class="alert alert-light-danger color-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-circle"></i> Proses gagal dilakukan! <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
        }
        redirect(url($url));
    }

    if (isset($_GET['add']) or isset($_GET['edit'])) {

        $id_desa      = "";
        $kode_desa    = "";
        $kecamatan    = "";
        $nama_desa    = "";
        $geojson_desa = "";

        if (isset($_GET['edit']) and isset($_GET['id'])) {
            $id = $_GET['id'];
            $db->where('id_desa', $id);
            $row = $db->ObjectBuilder()->getOne('data_desa');
            if ($db->count > 0) {
                $id_desa      = $row->id_desa;
                $kode_desa    = $row->kode_desa;
                $kecamatan    = $row->kecamatan;
                $nama_desa    = $row->nama_desa;
                $geojson_desa = $row->geojson_desa;
            }
        }
    ?>

<div class="page-heading">
    <!-- // Basic multiple Column Form section start -->
    <section id="multiple-column-form">
        <div class="row match-height">
            <div class="col-12">
                <div class="card">
                    <div class="card-content">
                        <div class="card-body">
                            <form class="form" data-parsley-validate method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="row">
                                        <div class="col-md-6 col-12">
                                            <?php echo input_hidden('id_desa', $id_desa) ?>
                                            <div class="form-group mandatory">
                                                <label for="kode-desa-column" class="form-label">Kode Desa</label>
                                                <?php echo input_text('kode_desa', $kode_desa, ['id' => 'kode-desa-column', 'class' => 'form-control', 'placeholder' => 'Kode Nama', 'name' => 'kode_desa', 'data-parsley-required' => 'true']) ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group mandatory">
                                                <label for="nama-desa-column" class="form-label">Nama Desa</label>
                                                <?php echo input_text('nama_desa', $nama_desa, ['id' => 'nama-desa-column', 'class' => 'form-control', 'placeholder' => 'Nama Desa', 'name' => 'nama_desa', 'data-parsley-required' => 'true']) ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group mandatory">
                                                <label for="kecamatan-column" class="form-label">Kecamatan</label>
                                                <?php echo input_text('kecamatan', $kecamatan, ['id' => 'kecamatan-column', 'class' => 'form-control', 'placeholder' => 'Kecamatan', 'name' => 'kecamatan', 'data-parsley-required' => 'true']) ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group mandatory">
                                                <label class="form-label">GeoJSON</label>
                                                <?php echo input_file('geojson_desa', $geojson_desa) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-12 d-flex justify-content-end">
                                        <button type="submit" name="save" class="btn btn-primary me-1 mb-1"> <i
                                                class="bi bi-plus-square"></i>
                                            Submit
                                        </button>
                                        <a href="<?php echo url($url) ?>" class="btn btn-secondary me-1 mb-1"><i
                                                class="bi bi-reply-all"></i>
                                            Back</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- // Basic multiple Column Form section end -->
</div>

<?php
    } else {
    ?>

<!-- Minimal jQuery Datatable start -->
<section class="section">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                Data Desa
                <a href="<?php echo url($url . '&add') ?>" class="btn btn-success float-end"><i
                        class="bi bi-cloud-plus"></i>
                    Tambah</a>
            </h2>
        </div>

        <?php echo $session->pull("info") ?>

        <div class="card-body">
            <div class="table-responsive datatable-minimal">
                <table class="table table-striped" id="table2">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode Desa</th>
                            <th>Kecamatan</th>
                            <th>Nama Desa</th>
                            <th>GeoJSON</th>
                            <th>
                                <center>Actions</center>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $i       = 1;
                                $getdata = $db->ObjectBuilder()->get('data_desa');
                                foreach ($getdata as $row) {
                                ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td><?php echo $row->kode_desa ?></td>
                            <td><?php echo $row->kecamatan ?></td>
                            <td><?php echo $row->nama_desa ?></td>
                            <td><a href="<?php echo assets('unggah/geojson/' . $row->geojson_desa) ?>"
                                    target="_BLANK"><?php echo $row->geojson_desa ?></a></td>
                            <td>
                                <a href="<?php echo url($url . '&edit&id=' . $row->id_desa) ?>"
                                    class="btn btn-warning"><i class="bi bi-cloud-plus"></i>
                                    Edit </a>
                                <a href="<?php echo url($url . '&delete&id=' . $row->id_desa) ?>" class="btn btn-danger"
                                    onclick="return confirm('Apakah Anda Ingin Menghapus Data ?')"><i
                                        class="bi bi-file-earmark-x"></i>
                                    Delete </a>
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
<!-- Minimal jQuery Datatable end -->

<?php
}?>