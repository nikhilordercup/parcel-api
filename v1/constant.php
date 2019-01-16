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

define('UKMAIL_URL','https://qa-api.ukmail.com/Services/');//define('UKMAIL_LIVE_URL','https://api.ukmail.com/Services/');  <- live url
define('UKMAIL_LOGIN_ENDPOINT_URL','UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl');
define('UKMAIL_COLLECTION_ENDPOINT_URL','UKMCollectionServices/UKMCollectionService.svc?wsdl');
define('UKMAIL_LABEL_ENDPOINT_URL','UKMConsignmentServices/UKMConsignmentService.svc?wsdl');
define('UKMAIL_CANCEL_ENDPOINT_URL','UKMConsignmentServices/UKMConsignmentService.svc?wsdl');
?>