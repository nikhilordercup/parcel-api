<?php
define('ENV','dev');//dev //test 
define('CHECKED',true);
define('LABEL_URL', '/');//do not use
define('LABEL_FOLDER','label');  //use this variable to find label directory
define('ROUND_TRIP','NO');
define('DRIVING_MODE','DRIVING');
define('PDFURL','http://'.$_SERVER['HTTP_HOST'].'/api/label');
define('BASE_PATH',__DIR__);
define('LABEL_PATH',BASE_PATH.DIRECTORY_SEPARATOR.'label');
global $_GLOBAL_CONTAINER;
?>