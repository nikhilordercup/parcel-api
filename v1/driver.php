<?php
require_once("module/driver/driver.php");

$app->Post('/getDriverCompanyData', function() use ($app) {
	$response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	if($r->user_code=="super_admin"){
		//$response["warehouse_list"] = $obj->getAllActiveWareHouseList(array("company_id"=>$r->user_id));
		$response["company_list"] = $obj->getActiveCompanyList();
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


$app->post('/getDriverDataById', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new Company($r);
    $response["user_data"] = $obj->getDriverDataById();
    
    $obj = new Common();
    $countryData = $obj->countryList();
    $response["countryData"] = $countryData;
    
    echoResponse(200, $response);
 
});

$app->post('/getControllerList', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Driver($r);
    if($r->user_code!='controller'){
		$response["controller_list"] = $obj->getControllerDataByWarehouseId($r);
	}else{
		$response["controller_list"] = $obj->getUserDataByUserId($r);
	}
	
	echoResponse(200, $response);  
});

$app->post('/getWarehouseListByUserId', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	if($r->user_code=='super_admin'){
		$response["warehouse_list"] = $obj->getAllActiveWarehouse();
	}else if($r->user_code=='company'){
		$response["warehouse_list"] = $obj->getActiveWareHouseListByCompanyId(array("company_id"=>$r->user_id));
	}else{
		$response["warehouse_list"] = $obj->getWarehouseListByUserId($r);
	}
	echoResponse(200, $response);
 
});

$app->post('/getDriverWarehouseData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	$response["warehouse_list"] = $obj->getWarehouseListByUserId($r);
	echoResponse(200, $response);
 
});

$app->post('/getDriverControllerData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	$response["controller_list"] = $obj->getDriverWarehouseData($r);
	echoResponse(200, $response);
 
});


$app->post('/getWarehouseList', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	$response["warehouse_list"] = $obj->getWarehouseListByComapnyId($r);
	echoResponse(200, $response);
});

$app->post('/getDriverData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$r->user_level = 4;
	$obj = new Driver($r);
	$response["user_data"] = $obj->getUserDataByUserId($r);
	echoResponse(200, $response);
});

$app->post('/getAllDriverData', function() use ($app) {
    //$response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	
	//$response = $obj->getAllDriverDataByWarehouseId($r);
	if(isset($r->user_code) && $r->user_code == 'super_admin'){
		$response['driver_data'] = $obj->getAllDriverData($r);
	}
	else{
		$response['driver_data'] = $obj->getDriverDataByCompanyAndWarehouseId($r);
		//$response = $obj->getDriverDataByCompanyId($r);
	}
	
	/*else if($r->user_code == 'controller'){
		//working on this module....
		
		//$response = $obj->getDriverDataByControllerId($r);
	}*/
        
	$obj = new Common();
        $countryData = $obj->countryList();
        $response["countryData"] = $countryData;
        
	echoResponse(200, $response);
});

$app->post('/adddriver', function() use ($app) {
	require_once 'passwordHash.php';
	$r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	$obj->addDriver($r->driver);
});

$app->post('/editdriver', function() use ($app) {
    $response = array();
	$r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	$response = $obj->editDriver($r);
    echoResponse(200, $response);
});

?>