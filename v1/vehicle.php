<?php
require_once("module/vehicle/vehicle.php");

$app->post('/getDriverList', function() use ($app) {
    $response = array();$driverId = array();
    $r = json_decode($app->request->getBody());
	$obj = new Vehicle($r);
	$allDriversWithVehicles = $obj->getAssignedDriverAndVehicle();
	foreach($allDriversWithVehicles as $data){
		array_push($driverId,$data['driver_id']);
	}
	$response["driver_list"] = $obj->getDriversWithNoVehicles(implode(',',$driverId));
	echoResponse(200, $response);
});

$app->post('/getVehicleListByCategoryId', function() use ($app) {
    $response = array();$vehicleId = array();$categoryId = array();
    $r = json_decode($app->request->getBody());
	$obj = new Vehicle($r);
	if($r->user_code == 'super_admin'){
		$category_data = $obj->getVehicleCategoryData($r);
		$allVehiclesAssignedToDrivers = $obj->getAssignedDriverAndVehicle($r);
	}
	else{
		$category_data = $obj->getVehicleCategoryDataByCompanyId($r);
		$allVehiclesAssignedToDrivers = $obj->getAssignedDriverAndVehicleByCompanyId($r);
	}
	//$allVehiclesAssignedToDrivers = $obj->getAssignedDriverAndVehicle();
	foreach($allVehiclesAssignedToDrivers as $data){
		if($data['vehicle_id']!=0)
			array_push($vehicleId,$data['vehicle_id']);
	}
	foreach($category_data as $value){
		array_push($categoryId,$value['id']);
	}

	if($r->user_code != 'super_admin'){
		if(count($vehicleId)>0){
			$response["vehicle_list"] = $obj->getFreeVehicles(implode(',',$categoryId),implode(',',$vehicleId),$r->company_id);
		}else{
			$response["vehicle_list"] = $obj->getFreeVehicles(implode(',',$categoryId),'',$r->company_id);
		}	
	}else{
		if(count($vehicleId)>0){
			$response["vehicle_list"] = $obj->getFreeVehicles(implode(',',$categoryId),implode(',',$vehicleId));
		}else{
			$response["vehicle_list"] = $obj->getFreeVehicles(implode(',',$categoryId),'');
		}
	}
	echoResponse(200, $response);
});


/*$app->post('/getVehicleList', function() use ($app) {
    $response = array();$vehicleId = array();
    $r = json_decode($app->request->getBody());
	$obj = new Vehicle($r);
	$allVehiclesAssignedToDrivers = $obj->getAssignedDriverAndVehicle();
	foreach($allVehiclesAssignedToDrivers as $data){
		array_push($vehicleId,$data['vehicle_id']);
	}
	$response["vehicle_list"] = $obj->getFreeVehicles(implode(',',$vehicleId));
	echoResponse(200, $response);
});*/


$app->post('/getVehicleCategory', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Vehicle($r);
	//$response["category_list"] = $obj->getVehicleCategoryData($r);
	if($r->user_code == 'super_admin'){
		$response = $obj->getVehicleCategoryData($r);
	}
	else{
		$response = $obj->getVehicleCategoryDataByCompanyId($r);
	}
	echoResponse(200, $response);
 
});
$app->post('/getVehicleCategoryById', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Vehicle($r);
	$response = $obj->getVehicleCategoryDataById($r->vehicle_id);
	echoResponse(200, $response);
 
});


$app->post('/addvehicle', function() use ($app) {
	$r = json_decode($app->request->getBody());
	$obj = new Vehicle($r);
	$obj->addVehicle($r->vehicle);
});
$app->post('/assignvehicletodriver', function() use ($app) {
	$r = json_decode($app->request->getBody());
	$obj = new Vehicle($r);
	$obj->assignVehicle($r->vehicle);
});


$app->post('/editvehicle', function() use ($app) {
    $response = array();
	$r = json_decode($app->request->getBody());
	$obj = new Vehicle($r);
	$response = $obj->editVehicle($r);
    echoResponse(200, $response);  
});

?>