<?php
/*
 * Database configuration
 */
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_NAME', 'icargo_tuff');
define('DB_PREFIX', 'icargo_');
define('PDFURL', 'https://' . $_SERVER['HTTP_HOST'] . '/live/label');
define('BASE_PATH', __DIR__);
define('LABEL_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'label');
global $_GLOBAL_CONTAINER;
