<?php

require_once("module/route/route.php");

$app->Post('/getRouteCompanyData', function() use ($app) {
	$response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Route($r);
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


$app->post('/getControllerList', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Route($r);
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
	$obj = new Route($r);
	if($r->user_code=='super_admin'){
		$response["warehouse_list"] = $obj->getAllActiveWarehouse();
	}else if($r->user_code=='company'){
		$response["warehouse_list"] = $obj->getActiveWareHouseListByCompanyId(array("company_id"=>$r->user_id));
	}else{
		$response["warehouse_list"] = $obj->getWarehouseListByUserId($r);
	}
	echoResponse(200, $response);
 
});

$app->post('/getRouteWarehouseData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Route($r);
	$response["warehouse_list"] = $obj->getWarehouseListByUserId($r);
	echoResponse(200, $response);
 
});

$app->post('/getRouteControllerData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Route($r);
	$response["controller_list"] = $obj->getRouteWarehouseData($r);
	echoResponse(200, $response);
 
});


$app->post('/getWarehouseList', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Route($r);
	$response["warehouse_list"] = $obj->getWarehouseListByComapnyId($r);
	echoResponse(200, $response);
});

$app->post('/getRoutePostcodes', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Route($r);
	$response["route_data"] = $obj->getRoutePostcodeByRouteId($r);
	echoResponse(200, $response);
});
$app->post('/routeDataByRouteId', function() use ($app) {
	$response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Route($r);
	$response = $obj->getRouteDataByRouteId($r);
	echoResponse(200, $response);
});

$app->post('/getAllRouteData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Route($r);
	//$response = $obj->getAllRouteDataByWarehouseId($r);
	if($r->user_code == 'super_admin'){
		$response = $obj->getAllRouteData($r);
	}
	else{
		$response = $obj->getRouteDataByCompanyAndWarehouseId($r);
		//$response = $obj->getRouteDataByCompanyId($r);
	}
    //print_r($response);die;
	echoResponse(200, $response);
});

$app->post('/addroute', function() use ($app) {
	$r = json_decode($app->request->getBody());
	$r->warehouse_id = $r->route->warehouse->warehouse_id;
	$r->postcode = $r->route->route_postcode;
	$r->name = $r->route->name;
    verifyRequiredParams(array('access_token','company_id','warehouse_id','name','postcode'),$r);
    
    $obj = new Route($r);
    $r->route->company_id = $r->company_id;
	$response = $obj->addRoute($r->route);
	if($response["status"] == "success"){
		echoResponse(200, $response);
	} else {
		echoResponse(201, $response);
	}
});

$app->post('/addpostcode', function() use ($app) {
	$r = json_decode($app->request->getBody());
	$obj = new Route($r);
	$obj->addPostcode($r);
});

$app->post('/editroute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	$obj = new Route($r);
	$response = $obj->editRoute($r);
	/*if($delResp!= NULL){
		$response['status'] = 'success';
		$response['message'] = 'Route postcode removed successfully';	
	}else{
        $response['status'] = 'error';
		$response['message'] = 'An error occurred while removing postcode, please try again later';
	}*/
	echoResponse(200, $response);
});
$app->post('/resolvePostcode', function () use ($app){
    $r = json_decode($app->request->getBody());
    $shipmentId=$r->shipment_id;
    $postCode=$r->postcode;
    $r=new Route($r);
    $r->resolvePostcode($shipmentId,$postCode);
});
?>