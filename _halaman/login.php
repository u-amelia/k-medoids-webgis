<?php
$setTemplate = false;

if (isset($_POST['login'])) {
    $nama_user = $_POST['nama_user'];
    $password_user = $_POST['password_user'];

    $db->where("nama_user", $nama_user);
    $db->where("password_user", $password_user);
    $db->get("data_user");

    if ($db->count > 0) {
        $session->set("logged", true);
        $session->set("info", '<div class="alert alert-light-success color-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle"></i> Anda berhasil untuk login. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
        redirect(url('dashboard'));

    } else {
        $session->set("logged", false);
        $session->set("info", '<div class="alert alert-light-danger color-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-circle"></i> Username atau password anda salah!<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
        redirect(url('login'));
    }

}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Webgis Balita</title>
    <link rel="stylesheet" crossorigin href="<?=templates()?>/compiled/css/app.css">
    <link rel="stylesheet" crossorigin href="<?=templates()?>/compiled/css/app-dark.css">
    <link rel="stylesheet" crossorigin href="<?=templates()?>/compiled/css/auth.css">
</head>

<body>
    <script src="<?=templates()?>/static/js/initTheme.js"></script>
    <div id="auth">

        <div class="row h-100">
            <div class="col-lg-5 col-12">
                <div id="auth-left">
                    <h1 class="auth-title mt-4">Log in.</h1>
                    <p class="auth-subtitle mb-5">Masuk dengan data yang anda masukkan saat registrasi.</p>

                    <?=$session->pull("info")?>

                    <form method="post">
                        <div class="form-group position-relative has-icon-left mb-4">
                            <input type="text" class="form-control form-control-xl" name="nama_user"
                                placeholder="Username">
                            <div class="form-control-icon">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>
                        <div class="form-group position-relative has-icon-left mb-0">
                            <input type="password" class="form-control form-control-xl" name="password_user"
                                placeholder="Password">
                            <div class="form-control-icon">
                                <i class="bi bi-shield-lock"></i>
                            </div>
                        </div>
                        <button name="login" class="btn btn-primary btn-block btn-lg shadow-lg mt-5">Log in</button>
                    </form>

                </div>
            </div>
            <div class="col-lg-7 d-none d-lg-block">
                <div id="auth-right">

                </div>
            </div>
        </div>

    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"
        integrity="sha512-X/YkDZyjTf4wyc2Vy16YGCPHwAY8rZJY+POgokZjQB2mhIRFJCckEGc6YyX9eNsPfn0PzThEuNs+uaomE5CO6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
</body>

</html>