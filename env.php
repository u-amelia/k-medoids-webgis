<?php
defined('env') or exit('Akses langsung ke script ini di blokir');

$setDb['db_host'] = 'localhost';
$setDb['db_name'] = 'db_balita';
$setDb['db_user'] = 'root';
$setDb['db_password'] = '';

// folder templates
$template = 'templates/dist';

// session
$setSession['prefix'] = 'webgis-gizi-balita-php';

// URI
$setUri['base'] = 'http://localhost/webgis-gizi-balita-php/';
$setUri['assets'] = 'assets/';
