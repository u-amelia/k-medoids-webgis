<?php
$title = "Pengguna";
$judul = "Data Pengguna";
$url = "pengguna";

if (isset($_POST['save'])) {
    if ($_POST['id_user'] == "") {
        $data['nama_user'] = $_POST['nama_user'];
        $data['password_user'] = $_POST['password_user'];

        $db->insert("data_user", $data);
        ?>
<script type="text/javascript">
window.alert('Data Berhasil Disimpan!');
window.location.href = "<?=url('pengguna')?>";
</script>
<?php
} else {

        $data['nama_user'] = $_POST['nama_user'];
        $data['password_user'] = $_POST['password_user'];

        $db->where("id_user", $_POST['id_user']);
        $db->update("data_user", $data);
        ?>
<script type="text/javascript">
window.alert('Data Berhasil Diubah!');
window.location.href = "<?=url('pengguna')?>";
</script>
<?php

    }
}

if (isset($_GET['delete'])) {
    $db->where("id_user", $_GET['id']);
    $db->delete("data_user");
    ?>
<script type="text/javascript">
window.alert('Data Berhasil Dihapus!');
window.location.href = "<?=url('pengguna')?>";
</script>
<?php
}

if (isset($_GET['add']) or isset($_GET['edit'])) {

    $id_user = "";
    $nama_user = "";
    $password_user = "";

    if (isset($_GET['edit']) and isset($_GET['id'])) {
        $id = $_GET['id'];
        $db->where('id_user', $id);
        $row = $db->ObjectBuilder()->getOne('data_user');
        if ($db->count > 0) {
            $id_user = $row->id_user;
            $nama_user = $row->nama_user;
            $password_user = $row->password_user;
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
                            <form class="form" data-parsley-validate method="post">
                                <div class="row">
                                    <div class="row">
                                        <div class="col-md-6 col-12">
                                            <?=input_hidden('id_user', $id_user)?>
                                            <div class="form-group mandatory">
                                                <label for="kode-desa-column" class="form-label">Username</label>
                                                <?=input_text('nama_user', $nama_user, ['id' => 'kode-desa-column', 'class' => 'form-control', 'placeholder' => 'Username', 'name' => 'nama_user', 'data-parsley-required' => 'true'])?>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-12">
                                            <div class="form-group mandatory">
                                                <label for="password_user-column" class="form-label">Password</label>
                                                <?=input_text('password_user', $password_user, ['id' => 'password_user-column', 'class' => 'form-control', 'placeholder' => 'Password', 'name' => 'password_user', 'data-parsley-required' => 'true'])?>
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
                                        <a href="<?=url($url)?>" class="btn btn-secondary me-1 mb-1"><i
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
                Data Pengguna
                <a href="<?=url($url . '&add')?>" class="btn btn-success float-end"><i class="bi bi-cloud-plus"></i>
                    Tambah</a>

            </h2>
        </div>
        <div class="card-body">
            <div class="table-responsive datatable-minimal">
                <table class="table table-striped" id="table2">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>
                                <center>Actions</center>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
$i = 1;
    $getdata = $db->ObjectBuilder()->get('data_user');
    foreach ($getdata as $row) {
        ?>
                        <tr>
                            <td><?=$i++;?></td>
                            <td><?=$row->nama_user?></td>
                            <td><?=$row->password_user?></td>
                            <td>
                                <a href="<?=url($url . '&edit&id=' . $row->id_user)?>" class="btn btn-warning"><i
                                        class="bi bi-cloud-plus"></i>
                                    Edit </a>
                                <a href="<?=url($url . '&delete&id=' . $row->id_user)?>" class="btn btn-danger"
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