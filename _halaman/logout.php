<?php
$setTemplate = false;

// Hancurkan sesi
$session->destroy();

// Redirect ke halaman login
redirect(url('login'));
