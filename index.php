<?php
include '_loader.php';

$setTemplate = true;

if (isset($_GET['halaman'])) {
    $halaman = $_GET['halaman'];
} else {
    $halaman = 'Dashboard';
}

ob_start();
$file = '_halaman/' . $halaman . '.php';

if (!file_exists($file)) {
    include '_halaman/error.php';
} else {
    include $file;
}

$content = ob_get_contents();
ob_end_clean();

if ($setTemplate == true) {
    if ($session->get("logged") !== true) {
        redirect(url('login'));
    }
    ?>

<!DOCTYPE html>
<html lang="en">
<?php include '_layouts/head.php';?>

<body>
    <script src="<?=templates()?>/static/js/initTheme.js"></script>
    <div id="app">
        <?php include '_layouts/sidebar.php';?>

        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            <div class="page-heading">
                <div class="page-title">
                    <div class="row">
                        <div class="col-12 col-md-6 order-md-1 order-last">
                            <h3><?=$title?></h3>
                            <p class="text-subtitle text-muted">Penyusunan, kelompokan, dan tata letak data.</p>
                        </div>
                        <div class="col-12 col-md-6 order-md-2 order-first">
                            <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="<?=url($url)?>"><?=$title?></a></li>
                                    <li class="breadcrumb-item active" aria-current="page"><?=$judul?></li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
                <?php echo $content; ?>
            </div>
            <?php include '_layouts/footer.php';?>
        </div>
        <?php include '_layouts/javascript.php';?>
</body>

</html>

<?php
} else {
    echo $content;
}

if (isset($fileJs)) {
    include '_halaman/js/' . $fileJs . '.php';
}

?>