<?php
// Router untuk server bawaan PHP:  php -S localhost:8080 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . $path)) {
    if (preg_match('#^/(application|system|database)/#', $path) || substr($path, -4) === '.php') {
        http_response_code(403);
        exit('Akses ditolak');
    }
    return false;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
