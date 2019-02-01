<?php
/* 
 * Database configuration
 */
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
define('DB_NAME', 'icargo_tuff');
define('DB_PREFIX', 'icargo_');
define('PDFURL','http://'.$_SERVER['HTTP_HOST'].'/label');
define('BASE_PATH',__DIR__);
define('LABEL_PATH',BASE_PATH.DIRECTORY_SEPARATOR.'label');
define('BASE_URL','http://api.icargo.in/');
define('UI_BASE_URL','http://localhost/icargo/');
global $_GLOBAL_CONTAINER;
