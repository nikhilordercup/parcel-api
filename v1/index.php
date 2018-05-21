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
require_once 'module/carrier/Ukmail.php';
require_once 'module/nextday/Nextday.php';
require_once 'module/allshipment/allshipments.php';
require_once 'module/allshipment/model/allshipments.php';

require_once 'module/shipment/shipment_tracking.php';

require_once 'pod_signature.php';

require_once 'module/package/Module_Package_Index.php';


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

function echoResponse($status_code, $response) {
    $app = \Slim\Slim::getInstance();

    $key = "example_key";

    $token = array(
        "iss" => "http://example.org",
        "aud" => "http://example.com",
        "iat" => 1356999524,
        "nbf" => 1357000000
    );

    $jwt = JWT::encode($response, $key);

echo $jwt;die;
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');


print_r($response);die;
    echo json_encode($response);
}

function rootPath(){
    return dirname(dirname(dirname(__FILE__)));
}

$app->run();
?>