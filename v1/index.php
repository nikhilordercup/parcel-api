<?php
require_once 'constant.php';
require_once 'dbHandler.php';
require_once 'passwordHash.php';
require_once 'array_column.php';
require '.././libs/Slim/Slim.php';
require '../vendor/autoload.php';

\Slim\Slim::registerAutoloader();

use Firebase\JWT\JWT;

$app = new \Slim\Slim();

/*$corsOptions = array(
    //"origin" => array('*'),
    "origin" => array('https://app-tree.co.uk','https://route.instadispatch.com'),
    //"exposeHeaders" => array("X-My-Custom-Header", "X-Another-Custom-Header"),
    "maxAge" => 1728000,
    "allowCredentials" => false,
    "allowMethods" => array("POST, GET, PUT, PATCH, PATCH, DELETE, HEAD, OPTIONS"),
    "allowHeaders" => array("X-PINGOTHER, Accept, Content-Type, Pragma, X-Requested-With")
);*/

// User id from db - Global Variable
$user_id = NULL;
require_once 'library.php';
require_once 'module/base/icargo.php';
require_once 'authentication.php';

require_once 'dashboard.php';
require_once 'controller.php';
require_once 'driver.php';
require_once 'route.php';
require_once 'vehicle.php';

//validate and beautify postcode
require_once 'postcode.php';

require_once 'api.php';
require_once 'common.php';
require_once 'default-form.php';
require_once 'dev.test.php';

require_once 'module/route/complete.php';
require_once 'module/route/model/complete.php';


require_once 'module/shipment/model/shipment.php';
require_once 'module/shipment/save.php';
require_once 'module/shipment/load-shipments.php';
require_once 'module/shipment/load-assign.php';
require_once 'module/shipment/view-support.php';
require_once 'module/shipment/optimize.php';
require_once 'module/shipment/load-route-details.php';

require_once 'module/company/company.php';
require_once 'module/company/setup.php';
require_once 'module/ws/idriver.php';

require_once 'module/firebase/model/rest.php';
require_once 'module/firebase/firebase.php';
require_once 'module/firebase/shipment-withdraw-from-route.php';
require_once 'module/firebase/route-accept.php';
require_once 'module/firebase/route-assign.php';
require_once 'module/firebase/route-release.php';

require_once 'module/chargebee/Chargebee.php';
require_once 'module/chargebee/Webhook.php';

//require_once 'module/carrier/customer.php';
require_once 'module/google/api.php';
require_once 'module/coreprime/api.php';
require_once 'module/addressbook/addressbook.php';
require_once 'module/import/Profile.php';
require_once 'module/configuration/FormConfiguration.php';
require_once 'module/customer/customer.php';
require_once 'module/customer/model/customer.php';

require_once 'module/master/master.php';
require_once 'module/configuration/ConfigurationManager.php';

require_once 'module/allshipment/allshipments.php';
require_once 'module/allshipment/model/allshipments.php';

require_once 'module/settings/settings.php';
require_once 'module/settings/model/settings.php';
require_once 'module/invoice/invoice.php';
require_once 'module/invoice/model/invoice.php';
require_once 'module/voucher/voucher.php';
require_once 'module/voucher/model/voucher.php';

require_once 'module/webevent/index.php';
require_once 'module/webevent/model/index.php';

require_once 'module/notification/index.php';
require_once 'module/notification/model/index.php';

require_once 'module/notification/Consignee_Notification.php';
require_once 'module/notification/Notification_Email.php';
require_once 'module/quotation/quotation.php';
require_once 'module/report/report.php';

require_once 'module/carrier/Environment.php';
require_once 'module/carrier/model/carrier.php';
require_once 'module/carrier/CustomerCostFactor.php';

require_once 'module/booking/model/Booking.php';
require_once 'module/booking/Shipment.php';
require_once 'module/booking/Booking.php';

require_once 'module/carrier/Carrier.php';
require_once 'module/carrier/Coreprime/Ukmail/Ukmail.php';
require_once 'module/nextday/Nextday.php';
require_once 'module/allshipment/allshipments.php';
require_once 'module/allshipment/model/allshipments.php';

require_once 'module/shipment/shipment_tracking.php';

require_once 'pod_signature.php';//no need to keep separate file to save image. This is already defined in libraray

require_once 'module/package/Module_Package_Index.php';

require_once 'module/booking/collection.php';


/**
 * Verifying required params posted or not
 */

date_default_timezone_set('Europe/Bucharest');

function verifyRequiredParams($required_fields,$request_params) {
    $error = false;
    $error_fields = "";
    foreach ($required_fields as $field) {
        if (!isset($request_params->$field) || strlen(trim($request_params->$field)) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["status"] = "error";
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoResponse(400, $response);
        $app->stop();
    }
}

function echoResponse($status_code, $response) {//print_r($response);die;
    $app = \Slim\Slim::getInstance();

    $privateKey = <<<EOD
-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQC8kGa1pSjbSYZVebtTRBLxBz5H4i2p/llLCrEeQhta5kaQu/Rn
vuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t0tyazyZ8JXw+KgXTxldMPEL9
5+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4ehde/zUxo6UvS7UrBQIDAQAB
AoGAb/MXV46XxCFRxNuB8LyAtmLDgi/xRnTAlMHjSACddwkyKem8//8eZtw9fzxz
bWZ/1/doQOuHBGYZU8aDzzj59FZ78dyzNFoF91hbvZKkg+6wGyd/LrGVEB+Xre0J
Nil0GReM2AHDNZUYRv+HYJPIOrB0CRczLQsgFJ8K6aAD6F0CQQDzbpjYdx10qgK1
cP59UHiHjPZYC0loEsk7s+hUmT3QHerAQJMZWC11Qrn2N+ybwwNblDKv+s5qgMQ5
5tNoQ9IfAkEAxkyffU6ythpg/H0Ixe1I2rd0GbF05biIzO/i77Det3n4YsJVlDck
ZkcvY3SK2iRIL4c9yY6hlIhs+K9wXTtGWwJBAO9Dskl48mO7woPR9uD22jDpNSwe
k90OMepTjzSvlhjbfuPN1IdhqvSJTDychRwn1kIJ7LQZgQ8fVz9OCFZ/6qMCQGOb
qaGwHmUK6xzpUbbacnYrIM6nLSkXgOAwv7XXCojvY614ILTK3iXiLBOxPu5Eu13k
eUz9sHyD6vkgZzjtxXECQAkp4Xerf5TGfQXGXhxIX52yH+N2LtujCdkQZjXAsGdm
B2zNzvrlgRmgBrklMTrMYgm1NPcW+bRLGcwgW2PTvNM=
-----END RSA PRIVATE KEY-----
EOD;

    $publicKey = <<<EOD
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC8kGa1pSjbSYZVebtTRBLxBz5H
4i2p/llLCrEeQhta5kaQu/RnvuER4W8oDH3+3iuIYW4VQAzyqFpwuzjkDI+17t5t
0tyazyZ8JXw+KgXTxldMPEL95+qVhgXvwtihXC1c5oGbRlEDvDF6Sa53rcFVsYJ4
ehde/zUxo6UvS7UrBQIDAQAB
-----END PUBLIC KEY-----
EOD;


    $jwtString = JWT::encode($response, $privateKey, 'RS256');
    //echo "Encode:\n" . print_r($jwt, true) . "\n";

    //$decoded = JWT::decode($jwt, $publicKey, array('RS256'));

    //$decoded_array = (array) $decoded;
    //echo "Decode:\n" . print_r($decoded_array, true) . "\n";

    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo $jwtString;
    //echo json_encode($response);
}

function rootPath(){
    return dirname(dirname(dirname(__FILE__)));
}

$app->run();
?>