<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'alianca_galeteria');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', 'http://localhost/alianca_galeteria');
define('APP_NAME', 'Aliança Galeteria');
define('APP_PHONE', '(11) 93210-1000');
define('APP_SECRET', 'alianca_galeteria_secret_key_2024_xJk9!');

define('SESSION_LIFETIME', 3600 * 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_MINUTES', 15);

define('STORAGE_PATH', __DIR__ . '/storage');
define('QRCODE_PATH', STORAGE_PATH . '/qrcodes');

ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

date_default_timezone_set('America/Sao_Paulo');
