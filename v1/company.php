<?php
//require_once("module/company/company.php");
/*$app->post('/getCompanyList', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	$obj = new Company($r);
	if($r->user_code=="super_admin")
		$response["company_list"] = $obj->getAllActiveCompanyList();
	else
		$response["company_list"] = $obj->getActiveCompanyListByCompanyId();
	echoResponse(200, $response);
});

$app->post('/getAllWarehouseData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	if($r->user_code=="super_admin")
		$response = $obj->getAllWarehouse();
	else
		$response = $obj->getWarehouseByCompanyId(array("company_id"=>$r->user_id));
		
	echoResponse(200, $response);
});

$app->post('/getWarehouseCompanyData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$response["company_list"] = $obj->getWarehouseCompanyData($r);
	echoResponse(200, $response);
 
});

$app->post('/getWarehouseData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$response["warehouse_data"] = $obj->getWarehouseDataByWarehouseId($r);
	echoResponse(200, $response);
});

$app->post('/addwarehouse', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$obj->addWareHouse($r->warehouse);
});

$app->post('/editwarehouse', function() use ($app) {
	$r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$obj->editWarehouse($r->warehouse,$r->id);
});
*/
?>