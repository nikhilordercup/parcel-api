<?php
/*require_once("module/driver/driver.php");
//require_once("module/controller/controller.php");
$app->post('/getControllerList', function() use ($app) {
    $response = array();
	 //verifyRequiredParams(array(),$r->warehouse);
    $r = json_decode($app->request->getBody());
	
	$obj = new Driver($r);
	
	if($r->user_code=="company"){
		$response["controller_list"] = $obj->getControllerListByComapnyId($r);
	}
	else if($r->user_code=="company"){
		
	} 
	else if($r->user_code=="controller"){
		
	}
	echoResponse(200, $response);
   
    
});

$app->post('/getWarehouseList', function() use ($app) {
    $response = array();
	 //verifyRequiredParams(array(),$r->warehouse);
    $r = json_decode($app->request->getBody());
	
	$obj = new Driver($r);
 
});*/

//  function test(){
    //$response = array();
    //$r = json_decode($app->request->getBody());
	//$r->user_level = 4;
	//$obj = new Driver($r);
	//$response["user_data"] = $obj->getUserDataByUserId($r);
	$responseJson = '[{"name": "Kavita","gender": "female","company": "PCS"}]';
	
	echo $responseJson;
//};

/*$app->post('/adddriver', function() use ($app) {
	require_once 'passwordHash.php';
	$r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	$obj->addDriver($r->driver);
});

$app->post('/editdriver', function() use ($app) {
	$r = json_decode($app->request->getBody());
	$obj = new Driver($r);
	$obj->editDriver($r->driver,$r->user_id);
});*/

?>