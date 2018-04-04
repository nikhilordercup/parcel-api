<?php
require_once("module/controller/controller.php");
//require_once 'passwordHash.php';
//require_once("module/authentication/authentication.php");
	
$app->Post('/getCompanyData', function() use ($app) {
	$response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Controller($r);
	if($r->user_code=="super_admin"){
		//$response["warehouse_list"] = $obj->getAllActiveWareHouseList(array("company_id"=>$r->user_id));
		$response["company_list"] = $obj->getActiveCompanyList(array("company_id"=>$r->user_id));
	}
	else if($r->user_code=="company"){
		$response["warehouse_list"] = $obj->getActiveWareHouseListByCompanyId(array("company_id"=>$r->user_id));
		$response["company_list"] = $obj->getActiveCompanyListByCompanyId(array("company_id"=>$r->user_id));
	} 
	else if($r->user_code=="controller"){
		$response["warehouse_list"] = $obj->getActiveWareHouseListByControllerId(array("controller_id"=>$r->user_id));
		$response["company_list"] = $obj->getActiveCompanyListByControllerId(array("controller_id"=>$r->user_id));
	}
	
    echoResponse(200, $response);
});

$app->post('/getControllerCompanyData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Controller($r);
	$response["company_list"] = $obj->getControllerCompanyData($r);
	echoResponse(200, $response);
 
});

$app->post('/getControllerWarehouseData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Controller($r);
	$response["warehouse_list"] = $obj->getControllerWarehouseData($r);
	echoResponse(200, $response);
 
});

$app->post('/getWarehouseList', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Controller($r);
	$response["warehouse_list"] = $obj->getWarehouseListByComapnyId($r);
	echoResponse(200, $response);
});

$app->post('/getWarehouseListByControllerId', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Controller($r);
	$response["warehouse_list"] = $obj->getActiveWareHouseListByControllerId($r);
	echoResponse(200, $response);
});

$app->post('/getControllerData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$r->user_level = '2,3';
	$obj = new Controller($r);
	$response["user_data"] = $obj->getUserDataByUserId($r);
	echoResponse(200, $response);
});
$app->post('/getAllControllerData', function() use ($app) {
    //$response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Controller($r);
	if($r->user_code == 'super_admin'){
		$response = $obj->getAllControllerData($r);
	}
	else
		$response = $obj->getControllerDataByCompanyAndWarehouseId($r);
		//$response = $obj->getControllerDataByCompanyId($r);
	
	echoResponse(200, $response);
});
$app->post('/addcontroller', function() use ($app) {
	require_once 'passwordHash.php';
	$r = json_decode($app->request->getBody());
	$obj = new Controller($r);
	$obj->addController($r->controller);
});

$app->post('/editcontroller', function() use ($app) {
    $response = array();
	$r = json_decode($app->request->getBody());
	$obj = new Controller($r);
	$response = $obj->editController($r);
    echoResponse(200, $response);
	//$obj->editController($r->controller,$r->user_id);
});

?>