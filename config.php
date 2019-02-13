<?php
/* 
 * Database configuration
 */
define('DB_USERNAME', 'app_stable');
define('DB_PASSWORD', 'pcs@pcs');
define('DB_HOST', 'localhost');
define('DB_NAME', 'app_stable');
define('DB_PREFIX', 'icargo_');
define('PDFURL','http://'.$_SERVER['HTTP_HOST'].'/label');
define('BASE_PATH',__DIR__);
define('ROOT_DIR',__DIR__);
define('LABEL_PATH',BASE_PATH.DIRECTORY_SEPARATOR.'label');
define('BASE_URL','https://api.instadispatch.com/app-allignment/parcel-api/');
define('UI_BASE_URL','https://app-tree.co.uk/app-stable/');
global $_GLOBAL_CONTAINER;