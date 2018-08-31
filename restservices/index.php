<?php
date_default_timezone_set('Europe/Bucharest');
require_once '../v1/constant.php';
require_once '../Credentials.php';
require_once '../v1/dbHandler.php';
//require_once '../v1/passwordHash.php';
//require_once '../v1/array_column.php';
require '../libs/Slim/Slim.php';
require '../vendor/autoload.php';
\Slim\Slim::registerAutoloader();
use Firebase\JWT\JWT;
$app = new \Slim\Slim();  
require_once '../v1/module/base/icargo.php';
require_once '../v1/module/carrier/model/carrier.php';
require_once '../v1/module/booking/collection.php';
require_once '../v1/module/carrier/CustomerCostFactor.php';
require_once '../v1/module/booking/model/Booking.php';
require_once '../v1/module/booking/Booking.php';
require_once '../v1/library.php';
require_once '../v1/module/coreprime/model/api.php';
require_once '../v1/postcode.php';
require_once '../v1/module/google/api.php';
require_once '../v1/module/coreprime/api.php';
require_once './Sameday.php';
require_once './Nextday.php';
require_once '../v1/module/notification/index.php';
require_once '../v1/module/notification/model/index.php';
require_once '../v1/module/notification/Consignee_Notification.php';
require_once '../v1/module/notification/Notification_Email.php';
require_once '../v1/module/allshipment/allshipments.php';
require_once '../v1/module/allshipment/model/allshipments.php';

    
require_once './model/restservicesModel.php';
require_once './restservices.php';

/*
require_once('model/api.php');


require_once '../v1/module/base/icargo.php';

require_once '../v1/module/booking/model/Booking.php';
require_once '../v1/module/booking/Shipment.php';
require_once '../v1/module/booking/Booking.php';
require_once '../v1/module/nextday/Nextday.php';
require_once '../v1/module/nextday/Nextday.php';
require_once '../v1/module/shipment/model/shipment.php';
require_once '../v1/module/shipment/save.php';


*/

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

function verifyToken($app,$r) {
    
    $response = array();
    $responceData = array();
    $error = false;
    try{
        $r = json_decode($r);
        $token = $app->request->headers->get("Authorization");
        $url    = $app->request->getScheme().'://';
        $url   .= $app->request->getHost();
        $url   .= $app->request->getPath();
        $parseUrl = explode('/',$url);
        array_pop($parseUrl);
        $fullReqUrl = implode('/',$parseUrl);
        $model = new restservices_Model();
        $tokenData = decodeJWtKey($token);
        $responceData = $model->getTokenofCustomer($tokenData);
    if(count($responceData)>0){
        if(CHECKED  && $responceData['url'] !=$fullReqUrl){
                $error = true;  
                $response["status"] = "fail";
                $response["message"] = 'your tried with unauthorized resource ';
                $response["error_code"] = "ERROR001";
        }
        elseif($token != $responceData['token']){
            $error = true;  
            $response["status"] = "fail";
            $response["message"] = 'wrong accesstoken passed';
            $response["error_code"] = "ERROR002";
        }
        else{
             if($responceData['access_token'] ==''){
                $pId   = $responceData['parent_id'];
                $access_token = base64_encode(rand()."-".uniqid()."-$pId");
                $status = $model->editContent("users",array("access_token"=>$access_token)," id = $pId");
                if($status){
                    $responceData['access_token'] = $access_token;
                }
            }
            $r->email                   = $responceData['email'];
            $r->access_token            = $responceData['access_token'];
            $r->customer_id             = $responceData['customer_id'];        
            $r->company_id              = $responceData['company_id'];
            $r->warehouse_id            = $responceData['warehouse_id'];         
            $r->warehouse_latitude      = $responceData['warehouse_latitude'];         
            $r->warehouse_longitude     = $responceData['warehouse_longitude'];
            $r->webToken                = $tokenData->identity;
            $r->status                  = 'success';
            return $r;
        }
    }
    else{
      $error = true;  
      $response["status"] = "fail";
      $response["message"] = 'your account is not inactive,Please contact to admin';
      $response["error_code"] = "ERROR003"; 
    }
     }catch(Exception $e){
         $error = true;  
         $response["status"] = "fail";
         $response["message"] = 'Invalid json object';//$e->getMessage();
         $response["error_code"] = "SYSERROR001";
         $response["error_desc"] = $e->getMessage();
         echo json_encode($response);die;
    }

if($error){
        $app = \Slim\Slim::getInstance();
        echoResponse(400, $response);
        $app->stop();
    }


}
function verifyTokenByPass($app,$r) {
    
    $response = array();
    $responceData = array();
    $error = false;
    try{
    $model = new restservices_Model();
    $responceData = $model->getTokenofCustomer((object)array('identity'=>1));
    if(count($responceData)>0){
            $r                          = (object)array();
            $r->email                   = $responceData['email'];
            $r->access_token            = $responceData['access_token'];
            $r->customer_id             = $responceData['customer_id'];        
            $r->company_id              = $responceData['company_id'];
            $r->warehouse_id            = $responceData['warehouse_id'];         
            $r->warehouse_latitude      = $responceData['warehouse_latitude'];         
            $r->warehouse_longitude     = $responceData['warehouse_longitude'];
            $r->webToken                = 1;
            $r->status                  = 'success';
            return $r;
        
    }
    else{
      $error = true;  
      $response["status"] = "fail";
      $response["message"] = 'your account is not inactive';
      $response["error_code"] = "ERROR003"; 
    }
     }catch(Exception $e){
         $error = true;  
         $response["status"] = "fail";
         $response["message"] = 'Invalid json object';//$e->getMessage();
         $response["error_code"] = "SYSERROR002";
         $response["error_desc"] = $e->getMessage();
         echo json_encode($response);die;
    }

if($error){
        $app = \Slim\Slim::getInstance();
        echoResponse(400, $response);
        $app->stop();
    }


}



    
function echoResponse($status_code, $response) {
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
    //$app->contentType('application/json');

    //echo $jwtString;
    echo json_encode($response);
}

function encodeJWtKey($data) {
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

   $jwtString = JWT::encode($data, $privateKey, 'RS256');
   
    echo json_encode($jwtString);
}

function decodeJWtKey($data) {
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

    try{
         $jwtData = JWT::decode($data, $publicKey, array('RS256')); 
    }
    catch(Exception $e){
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["status"] = "error";
        $response["message"] = 'unauthorized token';
        $response["error_code"] = "ERROR001"; 
        echoResponse(400, $response);
        $app->stop();
    }
    return $jwtData;

}

function rootPath(){
    return dirname(dirname(dirname(__FILE__)));
}
function json_validator($data=NULL) {
  if (!empty($data)) {
                @json_decode($data);
                return (json_last_error() === JSON_ERROR_NONE);
        }
        return false;
}
$app->run();
?>