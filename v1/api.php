<?php
/*
* logout api is used to clear the user access token
*/
$app->post('/logout', function() use ($app) {
	$r = json_decode($app->request->getBody());
	$response = array();
	$db = new DbHandler();
    verifyRequiredParams(array('user_id'),$r);
	$resetAccessToken = $db->removeAccessToken($r->user_id);
	if($resetAccessToken!=NULL){
		$response["status"] = "success";
		$response["message"] = "Logged out successfully";
	}else{
		$response["status"] = "error";
		$response["message"] = "Logout error,please try again";
    }
	echoResponse(200, $response);
});

/*
* saveShipment api is used to save instadispatch shipment booking
*/
$app->post('/getPreparedRoute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','company_id','warehouse_id','routetype'),$r);

	$obj = new loadShipment(array('company_id'=>$r->company_id,'access_token'=>$r->access_token,'warehouse_id'=>$r->warehouse_id,'routetype'=>$r->routetype));
	$records = $obj->testCompanyConfiguration();
	//if($records['status']){
		$records = $obj->loadPreparedRoute();
		echoResponse(200, $records);
	/*} else {
	    echoResponse(200, $records);
	}*/
});

/*
* saveShipment api is used to save instadispatch shipment booking
*/
$app->post('/saveShipment', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('file'),$r->transmission);
	$obj = new shipment(array("root_path"=>rootPath(),"file_name"=>$r->transmission->file));
	$status = $obj->save();

	echoResponse(200, $status);
});

/*
* loadWarehouseShipments api is used to load all shipment(s) of warehouse
*/
$app->post('/loadWarehouseShipments', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('company_id','warehouse_id','shipment_type'),$r);
	$obj = new loadShipment(array('access_token'=>$r->access_token,'email'=>$r->email,'company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id,'shipment_type'=>$r->shipment_type,'user_id'=>$r->user_id, "start_date"=>$r->start_date, "end_date"=>$r->end_date));
	$records = $obj->shipments();
	echoResponse(200, $records);
});

/*
* assignRoute api is used to assign route to driver
*/
$app->post('/assignRoute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());

	verifyRequiredParams(array('access_token','email','driver_id','route_name','start_time','route_id','warehouse_id','company_id'),$r);
	$obj = new Route_Assign(array('access_token'=>$r->access_token, 'email'=>$r->email, 'driver_id'=>$r->driver_id, 'route_name'=>$r->route_name, 'start_time'=>$r->start_time, 'route_id'=>$r->route_id,'company_id'=>$r->company_id, 'warehouse_id'=>$r->warehouse_id));
	$records = $obj->saveAndAssignToDriver();

	echoResponse(200, $records);
});

/*
* assignRoute api is used to assign route to driver
*/
$app->post('/saveRoute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());

	verifyRequiredParams(array('access_token','email','route_id','route_name','company_id','warehouse_id'),$r);
	$obj = new Route_Assign(array('access_token'=>$r->access_token, 'email'=>$r->email, 'route_name'=>$r->route_name, 'route_id'=>$r->route_id, 'company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id));
	$records = $obj->saveRoute();

	echoResponse(200, $records);
});

/*
* assignRoute api is used to assign route to driver
*/
$app->post('/loadLeftContent', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());

	verifyRequiredParams(array('access_token','email','company_id','search_date','warehouse_id'),$r);
	$obj = new View_Support(array('access_token'=>$r->access_token,'email'=>$r->email,'company_id'=>$r->company_id));
	$records = $obj->loadView(array("search_date"=>$r->search_date,"warehouse_id"=>$r->warehouse_id));
    echoResponse(200, $records);
});

$app->post('/loadAssignedRouteByShipmentRouteId', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','company_id','shipment_route_id','assigned_driver_id'),$r);
	$obj = new View_Support(array('access_token'=>$r->access_token, 'email'=>$r->email, 'company_id'=>$r->company_id,'shipment_route_id'=>$r->shipment_route_id,'driver_id'=>$r->assigned_driver_id,'post_id'=>$r->post_id,'save_post_id'=>$r->save_post_id,'uid'=>""));//$r->uid
	$records = $obj->loadAssignedView();
	echoResponse(200, $records);
});
/*
* updateWarehouseStatus api is used to update the warehouse status
*/
$app->post('/updateWarehouseStatus', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','company_id','shipment_ticket','warehouse_id','warehouse_status'),$r);

	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token, 'company_id'=>$r->company_id, 'shipment_ticket'=>$r->shipment_ticket, 'warehouse_id'=>$r->warehouse_id, 'warehouse_status'=>$r->warehouse_status));
	$records = $obj->updateWarehouseStatus();
	echoResponse(200, $records);
});

$app->post('/getShipmentStatus', function() use ($app){
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('warehouse_id','shipment_ticket','company_id'),$r);
	$r->shipment_ticket = str_replace(',',"','",$r->shipment_ticket);
	$obj = new LoadShipment(array('company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id,'shipment_ticket'=>$r->shipment_ticket,'access_token'=>$r->access_token)); //testLoadShipment

	$records = $obj->testCompanyConfiguration();

	if($records['status']){
		$result = $obj->shipmentStatus();
		//if($result['status']){
			$obj->requestRoutesData();
		//}
		echoResponse(200, $records);
	} else {
	    echoResponse(200, $records);
	}
});
$app->post('/getSameDayShipmentStatus', function() use ($app){
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('warehouse_id','shipment_ticket','company_id'),$r);
	$r->shipment_ticket = str_replace(',',"','",$r->shipment_ticket);
	$obj = new LoadShipment(array('company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id,'shipment_ticket'=>$r->shipment_ticket,'access_token'=>$r->access_token)); //testLoadShipment

	$records = $obj->testCompanyConfiguration();

	if($records['status']){
		$result = $obj->shipmentStatusSameDay();
		//if($result['status']){
			$obj->requestRoutesSamedayData();
		//}
		echoResponse(200, $records);
	} else {
	    echoResponse(200, $records);
	}
});
$app->post('/getCompanyList', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	$obj = new Company($r);
	if($r->user_code=="super_admin")
		$response["company_list"] = $obj->getAllActiveCompanyList();
	elseif($r->user_code=="company")
		$response["company_list"] = $obj->getActiveCompanyListByCompanyId();
    else
       $response["company_list"] = $obj->getActiveCompanyListByUserId();
	echoResponse(200, $response);
});

$app->post('/getAllWarehouseData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	if($r->user_code=="super_admin")
		$response = $obj->getAllWarehouse();
	else{
        $response = $obj->getWarehouseByCompanyId(array("company_id"=>$r->company_id,"user_code"=>$r->user_code,"user_id"=>$r->user_id));
	}
	//echoResponse(200, $response);
        $obj = new Common();
        $countryData = $obj->countryList();
        echoResponse(200, array("response"=>$response,"countryData"=>$countryData));

});

$app->post('/getWarehouseCompanyData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$response["company_list"] = $obj->getWarehouseCompanyData($r);
	echoResponse(200, $response);

});

$app->post('/getVehicleCompanyData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$response["company_list"] = $obj->getVehicleCompanyData($r);
	echoResponse(200, $response);
});

$app->post('/getWarehouseData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$response["warehouse_data"] = $obj->getWarehouseDataByWarehouseId($r);

        $obj = new Common();
        $countryData = $obj->countryList();
        $response["countryData"] = $countryData;
	echoResponse(200, $response);
});

$app->post('/getVehicleData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$response["vehicle_data"] = $obj->getVehicleDataByVehicleId($r);
	echoResponse(200, $response);
});

$app->post('/addwarehouse', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$obj->addWareHouse($r->warehouse);
});

/*$app->post('/editwarehouse', function() use ($app) {
	$r = json_decode($app->request->getBody());
	$obj = new Company($r);
	$obj->editWarehouse($r->warehouse,$r->id);
});*/

$app->post('/editwarehouse', function() use ($app) {
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('name','email','phone','address_1','address_2','postcode','city','state','country'),$r);
	$postCodeObj = new Postcode();
	$isValidPostcode = $postCodeObj->validate($r->postcode);
	if($isValidPostcode){
		$obj = new Company($r);
		$response = $obj->editWarehouse($r);
	}else{
		$response["status"] = "error";
        $response["message"] = "Invalid postcode";
	}
	echoResponse(200, $response);
});

$app->post('/samedaydriverassign', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());

	verifyRequiredParams(array('warehouse_id','company_id','driver_id','assign_time','email','route_name'),$r); //,'shipment_type'

    $params = array('assign_time'=>$r->assign_time,'driver_id'=>$r->driver_id,'company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id,'shipment_ticket'=>$r->shipment_ticket,'email'=>$r->email,'route_name'=>$r->route_name);
	$obj = new loadShipment($params);

	$records = $obj->sameDayDriverAssign($params);
	if($records['status']){
		echoResponse(200, $records);
	} else{
		echoResponse(201, $records);
	}

});

$app->post('/moveCurrentDropToAnotherDrop', function() use ($app) {
	$r = json_decode($app->request->getBody());
	 //verifyRequiredParams(array('access_token','email','shipment_ticket','temp_route_id','execution_order'),$r);
	 verifyRequiredParams(array('access_token','email','shipment_ticket','to_route_id','to_route_index','from_route_id','drop_count'),$r);

	 $params = array('shipment_ticket'=>$r->shipment_ticket,'to_route_id'=>$r->to_route_id,'to_route_index'=>$r->to_route_index,'from_route_id'=>$r->from_route_id,'drop_count'=>$r->drop_count);
	 $obj = new Route_Assign($params);
	 $result = $obj->moveCurrentDropToAnotherDrop();
	 echoResponse(200, $result);
});
$app->post('/getMoveToDisputeAcions', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	$obj = new loadShipment($r->email,$r->access_token);
	$response["actions"] = $obj->getActiveMoveToDisputeActions();
	echoResponse(200, $response);
});
$app->post('/movetodispute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','disputeid','company_id','warehouse_id','shipment_ticket'),$r);
	$params = array('shipment_ticket'=>$r->shipment_ticket,'disputeid'=>$r->disputeid,'company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id,'shipment_type'=>$r->shipment_type);
	$obj = new loadShipment($params);
	$response["actions"] = $obj->moveToDispute();
	echoResponse(200, $response);
});
$app->post('/optimizeroute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','geo_data'),$r); // 'latitude','longitude','temp_route_id','data_index'
	$params = array('geo_data'=>$r->geo_data); // ,'longitude'=>$r->longitude,'temp_route_id'=>$r->temp_route_id,'data_index'=>$r->data_index
	$obj = new Route_Optimize($params);
	$response = $obj->tour();
	echoResponse(200, $response);
});
$app->post('/eta', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','geo_data','warehouse_id'),$r);
	$params = array('geo_data'=>$r->geo_data,'eta'=>true,'warehouse_id'=>$r->warehouse_id);
	$obj = new Route_Optimize($params);
	$response = $obj->eta();
	echoResponse(200, $response);
});
$app->post('/resolveDropError', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','postcode','shipment_id','route_index','item_index'),$r);
	$params = array('postcode'=>$r->postcode,'shipment_id'=>$r->shipment_id,'to_route_index'=>$r->route_index,'item_index'=>$r->item_index);
	$obj = new Route_Assign($params);
	$response = $obj->resolveDropError();
	echoResponse(200, $response);
});

/*
* assignRouteShipments api is used to assign route details
*/
$app->post('/getRouteDetail', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('route_type','route_id','email','access_token','company_id'),$r);
	$obj = new loadRouteDetails(array('route_type'=>$r->route_type,'route_id'=>$r->route_id,'company_id'=>$r->company_id,'email'=>$r->email,'access_token'=>$r->access_token,'warehouse_id'=>$r->warehouse_id));
	$records = $obj->loadRouteShipmentsDetails();
    echoResponse(200, $records);
});

$app->post('/inWarehouse', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','shipment_ticket','company_id','warehouse_status','warehouse_id','email','access_token'),$r);
	$obj = new View_Support(array('shipment_route_id'=>$r->shipment_route_id,'shipment_ticket'=>$r->shipment_ticket,'company_id'=>$r->company_id,'warehouse_status'=>$r->warehouse_status,'warehouse_id'=>$r->warehouse_id,'email'=>$r->email,'access_token'=>$r->access_token));
	$records = $obj->inWareHouseShipmentsDetails();
	echoResponse(200, $records);
});
$app->post('/driverPickup', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','shipment_ticket','warehouse_id','company_id','pickup_status','email','access_token'),$r);
	$obj = new View_Support(array('shipment_route_id'=>$r->shipment_route_id,'warehouse_id'=>$r->warehouse_id,'shipment_ticket'=>$r->shipment_ticket,'company_id'=>$r->company_id,'pickup_status'=>$r->pickup_status,'email'=>$r->email,'access_token'=>$r->access_token));
	$records = $obj->isDriverPickup();
	echoResponse(200, $records);
});

$app->post('/exportrunsheet', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','driver_id','company_id','email','access_token'),$r);
	$obj = new View_Support(array('shipment_route_id'=>$r->shipment_route_id,'driver_id'=>$r->driver_id,'company_id'=>$r->company_id,'email'=>$r->email,'access_token'=>$r->access_token));
	$records = $obj->exportrunsheet();
	echoResponse(200, $records);
});

$app->post('/routeaccept', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','driver_id','company_id','shipment_ticket','driver_name','warehouse_id','email','access_token'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_route_id'=>$r->shipment_route_id,'driver_id'=>$r->driver_id,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket,'driver_name'=>$r->driver_name,'warehouse_id'=>$r->warehouse_id,'post_id'=>$r->post_id,'uid'=>$r->uid));
	$records = $obj->routeaccept();
	echoResponse(200, $records);
});
$app->post('/acceptReject', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','warehouse_id','company_id','shipment_ticket','accept_status'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_route_id'=>$r->shipment_route_id,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket,'warehouse_id'=>$r->warehouse_id,'accept_status'=>$r->accept_status));
	$records = $obj->acceptRejectShipments();
	echoResponse(200, $records);
	});
/*$app->post('/driverPickup', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','warehouse_id','company_id','shipment_ticket','accept_status'),$r);
	$obj = new View_Support(array('shipment_route_id'=>$r->shipment_route_id,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket,'warehouse_id'=>$r->warehouse_id,'accept_status'=>$r->accept_status));
	$records = $obj->pickup_by_driver();
	echoResponse(200, $records);
	});*/
$app->post('/withdrawroute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','warehouse_id','company_id','shipment_ticket'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_route_id'=>$r->shipment_route_id,'warehouse_id'=>$r->warehouse_id,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket));
	$records = $obj->withdrawroute();
	echoResponse(200, $records);
});
$app->post('/deleteroute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','warehouse_id','company_id','shipment_ticket'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_route_id'=>$r->shipment_route_id,'warehouse_id'=>$r->warehouse_id,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket));
	$records = $obj->deleteroute();
	echoResponse(200, $records);
});

$app->post('/withdrawrouteandsave', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','warehouse_id','company_id','shipment_ticket'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_route_id'=>$r->shipment_route_id,'warehouse_id'=>$r->warehouse_id,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket));
	$records = $obj->withdrawrouteandsave();
	echoResponse(200, $records);
});

$app->post('/getRouteDetailByID', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','shipment_route_id','route_type'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_route_id'=>$r->shipment_route_id,'route_type'=>$r->route_type));
	$records = $obj->getRouteDetailByID();
	echoResponse(200, $records);
});

$app->post('/getMoveToOtherRouteAcions', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
    //verifyRequiredParams(array('access_token','email'),$r);
    $obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'company_id'=>$r->company_id));
	$response["actions"] = $obj->getMoveToOtherRouteAcions();
	echoResponse(200, $response);
});
$app->post('/assignToCurrentRoute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());

    $r->shipment_ticket = implode(',', $r->shipment_ticket);
	verifyRequiredParams(array('access_token','email','shipment_route_id','company_id','warehouse_id','shipment_ticket'),$r);

	$shipmentTickets = explode(',', $r->shipment_ticket);
    $params = array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_ticket'=>$shipmentTickets,'shipment_route_id'=>$r->shipment_route_id,'company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id);
	$obj = new View_Support($params);
	$response = $obj->assignToCurrentRoute();
	echoResponse(200, $response);
});

$app->post('/assignUnassignRoute', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','driver_id','start_time','route_id','company_id','warehouse_id'),$r);
	$obj = new View_Support(array('access_token'=>$r->access_token, 'email'=>$r->email, 'driver_id'=>$r->driver_id, 'start_time'=>$r->start_time, 'shipment_route_id'=>$r->route_id, 'company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id));
	$records = $obj->assignToDriver();

	echoResponse(200, $records);
});
$app->post('/getFailAction', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','company_id','shipment_ticket'),$r);
	$obj = new View_Support(array('access_token'=>$r->access_token, 'email'=>$r->email,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket));
	$records = $obj->getAllowFailedAction();
	echoResponse(200, $records);
});

$app->post('/getFirebaseDataForCardedShipment', function() use ($app) {
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','shipment_ticket','shipment_route_id'),$r);
	$obj = new View_Support(array('access_token'=>$r->access_token, 'email'=>$r->email,'shipment_ticket'=>$r->shipment_ticket,'shipment_route_id'=>$r->shipment_route_id));
	$records = $obj->getFirebaseDataForCardedShipment();
	echoResponse(200, $records);
});

$app->post('/cardedbycontroller', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
    if(!isset($r->comment)){
        $r->comment = "";
    }
	verifyRequiredParams(array('access_token','email','company_id','shipment_ticket',/*'comment','next_date','next_time','next_date_time',*/'failure_status','shipment_route_id'),$r);

	$obj = new View_Support(array('access_token'=>$r->access_token, 'email'=>$r->email,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket,/*'comment'=>
	$r->comment,'next_date'=>date('Y-m-d',$datetime),'next_time'=>date('h:i:s',$datetime),*/'failure_status'=>$r->failure_status,'shipment_route_id'=>$r->shipment_route_id,'warehouse_id'=>$r->warehouse_id));
	$records = $obj->cardedbycontrollerAction(array("date"=>$r->next_date_time, "comment"=>$r->comment));
	echoResponse(200, $records);
});
$app->post('/pickupbycontroller', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','company_id','shipment_ticket','shipment_route_id','warehouse_status','warehouse_id'),$r);
	$obj = new View_Support(array('access_token'=>$r->access_token, 'email'=>$r->email,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket,'shipment_route_id'=>$r->shipment_route_id,'warehouse_status'=>$r->warehouse_status,'warehouse_id'=>$r->warehouse_id));
	$records = $obj->pickupbycontrollerAction();
	echoResponse(200, $records);
});

$app->post('/deliveredbycontroller', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','company_id','shipment_ticket',/*'comment','next_date','next_time','next_date_time','contact_name',*/'shipment_route_id'),$r);
	$datetime = strtotime($r->next_date_time);
    if(!isset($r->contact_name)){
        $r->contact_name = "";
    }
    if(!isset($r->comment)){
        $r->comment = "";
    }

	$obj = new View_Support(array('access_token'=>$r->access_token, 'email'=>$r->email,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket,/*'comment'=>
	$r->comment,'next_date'=>date('Y-m-d',$datetime),'next_time'=>date('h:i:s',$datetime),'contact_name'=>$r->contact_name,*/'shipment_route_id'=>$r->shipment_route_id,'warehouse_id'=>$r->warehouse_id));
	$records = $obj->deliveredbycontrollerAction(array("date"=>$r->next_date_time, "contact_name"=>$r->contact_name,"comment"=>$r->comment));
	echoResponse(200, $records);
});
$app->post('/returntowarehouse', function() use ($app) {  /*will work furthur*/
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','company_id','shipment_ticket','shipment_route_id'),$r);
	$obj = new View_Support(array('access_token'=>$r->access_token, 'email'=>$r->email,'company_id'=>$r->company_id,'shipment_ticket'=>$r->shipment_ticket,'shipment_route_id'=>$r->shipment_route_id,));
	$records = $obj->returntowarehouseAction();
	echoResponse(200, $records);
});
$app->post('/inWarehouseCollected', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','shipment_ticket','company_id','warehouse_status'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_route_id'=>$r->shipment_route_id,'shipment_ticket'=>$r->shipment_ticket,'company_id'=>$r->company_id,'warehouse_status'=>$r->warehouse_status));
	$records = $obj->inWareHouseCollectionShipments();
	echoResponse(200, $records);
});
// handle idriver app all request
$app->post('/rest', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new Idriver();
    $response = $obj->services($r);
    echoResponse(200, $response);
});

$app->post('/getUserDataById', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	if($r->module_code=='driver'){
		$response["user_data"] = $obj->getDriverDataById();
	}else{
		$response["user_data"] = $obj->getUserDataById();
	}
        $obj = new Common();
        $countryData = $obj->countryList();
        $response["countryData"] = $countryData;
	//$response["user_data"] = $obj->getDriverDataById();
	//$response["user_data"] = $obj->getUserDataById();
	echoResponse(200, $response);

});

$app->post('/setupForm', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());

	if($r->source=='warehouse'){
		verifyRequiredParams(array('access_token','company_id','name','email','phone','address_1','city','postcode','state','country'),$r);
	}
	elseif($r->source=='controller'){
		verifyRequiredParams(array('access_token','company_id','warehouse_id','phone','address_1','city','postcode','state','country'),$r);
	}
	elseif($r->source=='driver'){
		//if($r->setup = 'dashboard'){
        /*if((isset($r->setup)) AND ($r->setup == 'dashboard')){
			$r->company_id = $r->company->id;
			$r->warehouse_id = $r->warehouse->warehouse_id;
		}*/
		verifyRequiredParams(array('access_token','company_id','warehouse_id','email','password','phone','address_1','city','postcode','state','country'),$r);
	}
	elseif($r->source=='vehicle'){
		verifyRequiredParams(array('access_token','company_id','model','brand','color','max_weight','max_width','max_height','max_length','max_volume','plate_no','vehicle_category'),$r);
	}
	elseif($r->source=='route'){
		verifyRequiredParams(array('access_token','company_id','warehouse_id','name','postcode'),$r);
	}
    $obj = new company($r);
	$status = $obj->save($r);

	echoResponse(200, $status);
});

$app->post('/getAllVehicleData', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Company($r);
	if($r->user_code=="super_admin")
		$response = $obj->getAllVehicle();
	else
		$response = $obj->getVehicleByCompanyId(array("company_id"=>$r->company_id));

	echoResponse(200, $response);
});

$app->post('/getShipmentCount', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('company_id','warehouse_id'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id));
	$records = $obj->getShipmentCount();
	echoResponse(200, $records);
});
$app->post('/getShipmentDetail', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','shipment_ticket'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_ticket'=>$r->shipment_ticket));
	$records = $obj->shipmentdetailsAction();
	echoResponse(200, $records);
});
$app->post('/updateShipments', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','shipment_ticket'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_ticket'=>$r->shipment_ticket,'shipment_address1'=>$r->shipment_address1,'shipment_address2'=>$r->shipment_address2,'shipment_address3'=>$r->shipment_address3,'shipment_postcode'=>$r->shipment_postcode,'warehouse_id'=>$r->warehouse_id,'company_id'=>$r->company_id));
	$records = $obj->updateShipments();
	echoResponse(200, $records);
});

$app->post('/importShipment', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','company_id','warehouse_id','customer_id','user_level'),$r);
	$obj = new shipment(array('file_name'=>$r->file_name,'job_type'=>$r->job_type,'tempdata'=>$r->tempdata,'company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id,'customer_id'=>$r->customer_id,'user_level'=>$r->user_level,'country_code'=>$r->country_code));
	$status = $obj->importshipment();
	echoResponse(200, $status);
});
$app->post('/addShipment', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','company_id','warehouse_id','customer_id','user_level'),$r);
	$obj = new shipment(array('job_type'=>$r->job_type,'tempdata'=>$r->tempdata,'company_id'=>$r->company_id,'warehouse_id'=>$r->warehouse_id,'customer_id'=>$r->customer_id,'user_level'=>$r->user_level));
	$status = $obj->addshipmentDetail();
	echoResponse(200, $status);
});
$app->post('/getaddressbypostcode', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','shipment_postcode'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'shipment_postcode'=>$r->shipment_postcode,'country_code'=>$r->country_code));
	$status = $obj->getaddressbypostcode();
	echoResponse(200, $status);
});
$app->post('/getaddressdetailbyid', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
   verifyRequiredParams(array('email','access_token','address_id'),$r);
	$obj = new View_Support(array('email'=>$r->email,'access_token'=>$r->access_token,'address_id'=>$r->address_id));
	$status = $obj->getaddressdetailbyid();
	echoResponse(200, $status);
});

$app->post('/PauseAssignedRouteByShipmentRouteId', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','company_id','shipment_route_id','assigned_driver_id'),$r);
	$obj = new View_Support(array('access_token'=>$r->access_token, 'email'=>$r->email, 'company_id'=>$r->company_id,'shipment_route_id'=>$r->shipment_route_id,'driver_id'=>$r->assigned_driver_id,'post_id'=>$r->post_id,'save_post_id'=>$r->save_post_id,"uid"=>$r->uid));
	$response = $obj->pauseAssignedView();
	echoResponse(200, $response);
});

/*$app->post('/routeCompleted', function() use($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('shipment_route_id','company_id','email','access_token'),$r);
	$obj = new Route_Complete(array('shipment_route_id'=>$r->shipment_route_id,'company_id'=>$r->company_id,'email'=>$r->email,'access_token'=>$r->access_token));
	$response = $obj->saveCompletedRoute();
	echoResponse(200, $response);
});*/

$app->post('/recoverRoute', function() use ($app){
	$obj = new Route_Assign(array('route_id'=>16, 'driver_id'=>98));
	$response = $obj->recoveryRoute();
	echoResponse(200, $response);
});

$app->post('/addRouteBox', function() use ($app){
	$r = json_decode($app->request->getBody());
	$obj = new loadShipment(array('company_id'=>$r->company_id,'access_token'=>$r->access_token,'warehouse_id'=>$r->warehouse_id,'routetype'=>$r->routetype));
	$response = $obj->addRouteBox();
	echoResponse(200, $response);
});

$app->post('/removeRouteBox', function() use ($app){
	$r = json_decode($app->request->getBody());
	$obj = new loadShipment(array('company_id'=>$r->company_id,'access_token'=>$r->access_token,'warehouse_id'=>$r->warehouse_id));
	$response = $obj->removeRouteBox($r->route_id);
	echoResponse(200, $response);
});

$app->post('/getseachdatabyparameter', function() use ($app){
	$r = json_decode($app->request->getBody());
	$obj = new loadShipment(array('company_id'=>$r->company_id,'access_token'=>$r->access_token,'warehouse_id'=>$r->warehouse_id));
	$response = $obj->getSearchData($r->search_param);
	echoResponse(200, $response);
});

$app->post('/getalldrivers', function() use ($app){
	$response = array();
	$r = json_decode($app->request->getBody());
	$obj = new Driver($r);
    $response = $obj->getAllDrivers($r);
	echoResponse(200, $response);
});
$app->post('/createPlan', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('plan_name','invoice_name','invoice_notes','description','price','currency_code','period','period_unit', 'trial_period_unit','free_quantity','status','billing_cycle','controller_count','driver_count','warehouse_count','email','access_token'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->createPlan($r);
	echoResponse(200, $response);
});

$app->post('/listPlan', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->listPlan();
	echoResponse(200, $response);
});

$app->post('/getPlanById', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','plan_id'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->getPlanById($r);
	echoResponse(200, $response);
});

$app->post('/editPlan', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('plan_name','invoice_name','invoice_notes','description','price','currency_code','period','period_unit', 'trial_period_unit','free_quantity','status','billing_cycle','controller_count','driver_count','warehouse_count','email','access_token'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->updatePlan($r);
	echoResponse(200, $response);
});

$app->post('/createSubscription', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('plan_id','plan_quantity','plan_unit_price','start_date','trial_end','billing_cycles','auto_collection','terms_to_charge','invoice_notes','invoice_immediately','email','access_token'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->createSubscription($r);
	echoResponse(200, $response);
});

$app->post('/getSubscriptionById', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','chargebee_subscription_id'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->getSubscriptionById($r);
	echoResponse(200, $response);
});

$app->post('/editSubscription', function() use ($app){
	$r = json_decode($app->request->getBody());

	if(empty($r->prorata) || !isset($r->prorata)){
		$r->prorata = 0;
	}
	verifyRequiredParams(array('plan_id','auto_collection','billing_cycles','invoice_immediately','plan_unit_price','prorata','subscription_id'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->editSubscription($r);
	echoResponse(200, $response);
});

$app->post('/listSubscription', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->listSubscription();
	echoResponse(200, $response);
});

$app->post('/createCustomer', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('billing_city','billing_country','billing_first_name','billing_last_name','billing_line1','billing_state','billing_zip','first_name','last_name','customer_email'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->createCustomer($r);
	echoResponse(200, $response);
});

$app->post('/getChargebeeCustomerById', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','chargebee_customer_id'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->getChargebeeCustomerById($r);
	echoResponse(200, $response);
});

$app->post('/editCustomer', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('billing_city','billing_country','billing_first_name','billing_last_name','billing_line1','billing_state','billing_zip','first_name','last_name','customer_email'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->editCustomer($r);
	echoResponse(200, $response);
});

$app->post('/listCustomer', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->listCustomer();
	echoResponse(200, $response);
});

$app->post('/listAllPlanForCustomerRegistration', function() use ($app){
	$obj = new Module_Chargebee();
	$response = $obj->listAllPlanForCustomerRegistration();
	echoResponse(200, $response);
});

$app->post('/listAllCustomerAndPlanForSubscription', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->listAllCustomerAndPlanForSubscription();
	echoResponse(200, $response);
});

$app->post('/getPlanDetailForSubscription', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','plan_id','start_date'),$r);
	$obj = new Module_Chargebee($r);
	$response = $obj->getPlanDetailForSubscription($r);
	echoResponse(200, $response);
});

$app->post('/saveCard', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','billing_address_line1','billing_address_line2','billing_city','billing_country','billing_postcode','billing_state','card_exp_month','card_exp_year','card_first_name','card_last_name','card_number','card_type','company_id','security_code','user_id'),$r);
	$r->verifyChargeBee = false;

	$obj = new Module_Chargebee($r);
	$response = $obj->saveCard($r);
	echoResponse(200, $response);
});


$app->post('/customerCurrentPlan', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','company_id'),$r);
	$r->verifyChargeBee = false;

	$obj = new Module_Chargebee($r);
	$response = $obj->getCustomerCurrentPlan($r);
	echoResponse(200, $response);
});


$app->post('/upgradeCustomerPlan', function() use ($app){
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','company_id','plan_id'),$r);
	$r->verifyChargeBee = false;

	$obj = new Module_Chargebee($r);
	$response = $obj->upgradeCustomerPlan($r);
	echoResponse(200, $response);
});

$app->post('/webHook', function() use ($app){
	$response = array();
	$r = json_decode($app->request->getBody());

	$obj = new Module_Chargebee_Webhook();
	$obj->consume($r);
});

$app->post('/getPendingJobs', function() use ($app){
	$response = array();
	$r = json_decode($app->request->getBody());
	$obj = new loadShipment(array('company_id'=>$r->company_id,'access_token'=>$r->access_token,'email'=>$r->email,'warehouse_id'=>$r->warehouse_id));
    $response = $obj->getAllPendingJobCount();
	echoResponse(200, $response);
});

$app->post('/getRunsheetData',function() use ($app){
	$response = array();
	$r = json_decode($app->request->getBody());
	$obj = new loadShipment(array('company_id'=>$r->company_id,'access_token'=>$r->access_token,'email'=>$r->email,'warehouse_id'=>$r->warehouse_id));
    $response = $obj->getRunsheetData($r->routeid);
	echoResponse(200, $response);
});
$app->post('/getDriverByDriverId', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new Driver($r);
    $response = $obj->getDriverId($r);
    echoResponse(200, $response);
});

$app->post('/saveCarrierCustomer', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
 //verifyRequiredParams(array('email','access_token','customer_email','address_1','address_2','city','company_id','country','name','password','phone','postcode','state','parent_id'),$r);
   verifyRequiredParams(array('email','access_token','company_id','parent_id'),$r);
    $obj = new Customer($r);
    $response = $obj->saveCustomer($r);
    echoResponse(200, $response);
});

$app->post('/saveCarrierCustomerFirebaseInfo', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','customer_id'),$r);
    $obj = new Customer($r);
    $response = $obj->saveCarrierCustomerFirebaseInfo($r);
    echoResponse(200, $response);
});

$app->post('/getGeoPositionFromPostcode', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','postcode'),$r);
    $obj = new Module_Google_Api($r);
    $response = $obj->getGeoPositionFromPostcode($r);
    echoResponse(200, $response);
});
/**
 * Import Profile Routes
 */
$app->post('/imports/profile/add', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','profile_name','access_token'),$r);
    $obj = new Profile();
    $obj->save($r->company_id,$r->profile_type,$r->profile_name,json_encode($r->profile_data));
    $response = [];
    echoResponse(200, $response);
});
$app->post('/imports/profile/list', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','access_token'),$r);
    $obj = new Profile();
    $response = $obj->fetchAll($r->company_id,$r->profile_type);
    echoResponse(200, $response);
});
$app->post('/imports/profile/update', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','profile_name','access_token','profile_id'),$r);
    $obj = new Profile();
    $obj->update($r->profile_id,$r->company_id,$r->profile_type,$r->profile_name,json_encode($r->profile_data));
    $response = [];
    echoResponse(200, $response);
});
$app->post('/imports/profile/delete', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('profile_id','access_token'),$r);
    $obj = new Profile();
    $obj->delete($r->profile_id);
    $response = $obj->delete($r['profile_id']);
    echoResponse(200, $response);
});
$app->post('/configuration/create-new', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','access_token'),$r);
    $obj = new ConfigurationManager();
    $response=$obj->addConfiguration($r->company_id,json_encode($r->config_data));
    echoResponse(200, $response);
});
$app->post('/configuration/update-configuration', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','access_token'),$r);
    $obj = new ConfigurationManager();
    $response=$obj->updateConfiguration($r->company_id,json_encode($r->config_data));
    echoResponse(200, $response);
});
$app->post('/configuration/fetch-configuration', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','access_token'),$r);
    $obj = new ConfigurationManager();
    $response=$obj->listConfiguration($r->company_id);
    if(!is_null($response))$response=json_decode(stripcslashes($response['config_data']));
    else $response=[];
    echoResponse(200, $response);
});

$app->post('/configuration/create-forms', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','access_token'),$r);
    $obj = new FormConfiguration();
    $response=$obj->addConfiguration($r->company_id,json_encode($r->config_data),json_encode($r->extra_data));
    echoResponse(200, $response);
});
$app->post('/configuration/update-forms', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','access_token'),$r);
    $obj = new FormConfiguration();
    $response=$obj->updateFormConfiguration($r->company_id,$r->form_config);
    echoResponse(200, $response);
});
$app->post('/configuration/fetch-forms', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','access_token'),$r);
    $obj = new FormConfiguration();
    $response=$obj->listFormConfiguration($r->company_id);

    if(!is_null($response)){
        $res['form_data']=json_decode($response['config_data']);
    }else{
			  $formObj = new Default_Form();
				$res['form_data'] = json_decode($formObj->getForm());
		}
    echoResponse(200, $res);
});
$app->post('/configuration/fetch-all', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','access_token'),$r);
    $obj = new ConfigurationManager();
    $response=$obj->listAllConfiguration($r->company_id);
    $res=[];
    if(!is_null($response)){
        foreach ($response as $v){
            if($v['configuration_type']=="APP")$res['config_data']=json_decode(stripcslashes($v['config_data']));
            if($v['configuration_type']=="APP_FORM"){
                $res['form_data']=json_decode(($v['config_data']));
            }
        }
    }
if(!isset($res['config_data']))
        $res['config_data']=$obj->getDefaultData();
    echoResponse(200, $res);
});

$app->post('/getGeolocationAndDistanceMatrix', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','service_date'),$r);
    $obj = new Module_Google_Api($r);
    try{
			$response = $obj->getGeolocationAndDistanceMatrix($r);
		}catch(Exception $e){
	      $response = array('status'=>'error','message'=>'distance matrix error','details'=>$e->getMessage());
    }
    echoResponse(200, $response);exit();
});

$app->post('/getAvailableServices', function() use($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','transit_distance','transit_time','number_of_collections','number_of_drops','total_waiting_time','service_date'),$r);
    $obj = new Module_Coreprime_Api($r);
    $response = $obj->getAllServices($r);
    echoResponse(200, $response);
});
$app->post('/getAvailableServices2', function() use($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','transit_distance','transit_time','number_of_collections','number_of_drops','total_waiting_time','service_date'),$r);
    $obj = new Module_Coreprime_Api($r);
    $response = $obj->getAllServices2($r);
    echoResponse(200, $response);
});
$app->post('/searchAddress', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','customer_id','search_postcode'),$r);
    $obj = new Module_Addressbook_Addressbook($r);
    $response = $obj->getAllAddresses($r);
    echoResponse(200, $response);
});

$app->post('/searchAddressById', function() use($app){
    $r = json_decode($app->request->getBody());

	if(is_object($r->id)){
		foreach($r->id as $id){
			$r->id = $id;
		}
	}
    verifyRequiredParams(array('access_token','email','customer_id','id','address_origin'),$r);
    $obj = new Module_Addressbook_Addressbook($r);
    $response = $obj->searchAddressById($r);
    echoResponse(200, $response);
});

$app->post('/bookShipment', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('service_date','transit_time','transit_distance','email','access_token','company_id','customer_id'),$r);
    $obj = new shipment(array('country_code'=>$r->country_code));
    $response = $obj->bookSameDayShipment($r);
    echoResponse(200, $response);
});


$app->post('/getSamedayShipmentByCustomerId', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','customer_id'),$r);
    //$obj = new shipment();
    //$response = $obj->bookSameDayShipment($r);
    //echoResponse(200, $response);
});

$app->post('/searchCustomer', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email','keywords','warehouse_id'),$r);
    $obj = new Controller($r);
    $response = $obj->getCustomerByControllerId($r);
    echoResponse(200, $response);
});

$app->post('/getAllCustomerData', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	if(isset($r->user_code) && $r->user_code == 'super_admin'){
		$response["customer_data"] = $obj->getAllCustomerData($r);
	}
	else{
		$response["customer_data"] = $obj->getCustomerDataByCompanyId($r);
	}

        $obj = new Common();
        $countryData = $obj->countryList();
        $response["countryData"] = $countryData;

	echoResponse(200, $response);
});
$app->post('/getAllMasterRowData', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Master($r);
    $response = $obj->getAllMasterRowData($r);
    echoResponse(200, $response);
});
$app->post('/getAllCouriers', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Master($r);
    $response = $obj->getAllCouriers($r);
    echoResponse(200, $response);
});
$app->post('/getAllCourierServices', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Master($r);
    $response = $obj->getAllCourierServices($r);
    echoResponse(200, $response);
});
$app->post('/getAllCourierSurcharge', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Master($r);
    $response = $obj->getAllCourierSurcharge($r);
    echoResponse(200, $response);
});

$app->post('/getAllShipmentsStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Settings($r);
    $response = $obj->getAllShipmentsStatus($r);
    echoResponse(200, $response);
});
$app->post('/getAllInvoiceStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Settings($r);
    $response = $obj->getAllInvoiceStatus($r);
    echoResponse(200, $response);
});

$app->post('/savedata', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Master($r);
    $response = $obj->saveData($r);
    echoResponse(200, $response);
});
$app->post('/editCourierAccount', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Master($r);
    $response = $obj->editCourierAccount($r);
    echoResponse(200, $response);
});
$app->post('/editStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Master($r);
    $response = $obj->editStatus($r);
    echoResponse(200, $response);
});
$app->post('/editServiceAccount', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Master($r);
    $response = $obj->editServiceAccount($r);
    echoResponse(200, $response);
});
$app->post('/editSurchargeAccount', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Master($r);
    $response = $obj->editSurchargeAccount($r);
    echoResponse(200, $response);
});

$app->post('/saveCarrierCustomerPickupInfo', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','address_1','address_2','city','customer_id','country','postcode','state'),$r);
    $obj = new Customer($r);
    $response = $obj->saveCarrierCustomerPickupInfo($r);
    echoResponse(200, $response);
});
$app->post('/saveCarrierCustomerBillingInfo', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','address_1','address_2','city','customer_id','country','postcode','state'),$r);
    $obj = new Customer($r);
    $response = $obj->saveCarrierCustomerBillingInfo($r);
    echoResponse(200, $response);
});
$app->post('/getAllCouriersofCustomer', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getAllCouriersofCustomer($r);
    echoResponse(200, $response);
});
$app->post('/editCustomerStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->editCustomerStatus($r);
    echoResponse(200, $response);
});
$app->post('/editCustomerAccountStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->editCustomerAccountStatus($r);
    echoResponse(200, $response);
});
$app->post('/getAllCouriersofCustomerAccount', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id','customer_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getAllCouriersofCustomerAccount($r);
    echoResponse(200, $response);
});
$app->post('/getAllCourierServicesForCustomer', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id','customer_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getAllCourierServicesForCustomer($r);
    echoResponse(200, $response);
});
$app->post('/editServiceAccountStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->editServiceAccountStatus($r);
    echoResponse(200, $response);
});
$app->post('/getAllCourierSurchargeForCustomer', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getAllCourierSurchargeForCustomer($r);
    echoResponse(200, $response);
});
$app->post('/editSurchargeAccountStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->editSurchargeAccountStatus($r);
    echoResponse(200, $response);
});
$app->post('/customerdetail', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response['customer_data'] = $obj->customerdetail($r);

    $obj = new Common();
    $countryData = $obj->countryList();
    $response["countryData"] = $countryData;

    echoResponse(200, $response);
});

$app->post('/editCustomerPersonalDetails', function() use ($app){
	$r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id'),$r);
	$obj = new Customer($r);
	$response = $obj->editCustomerPersonalDetails($r);
	echoResponse(200, $response);
});
$app->post('/editCustomerPickupDetails', function() use ($app){
	$r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id'),$r);
	$obj = new Customer($r);
	$response = $obj->editCustomerPickupDetails($r);
	echoResponse(200, $response);
});
$app->post('/editCustomerBillingDetails', function() use ($app){
	$r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id'),$r);
	$obj = new Customer($r);
	$response = $obj->editCustomerBillingDetails($r);
	echoResponse(200, $response);
});
$app->post('/getAllCourierDataOfSelectedCustomer', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getAllCourierDataOfSelectedCustomer($r);
    echoResponse(200, $response);
});
$app->post('/editSelectedCustomerAccountStatusFromView', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->editSelectedCustomerAccountStatus($r);
    echoResponse(200, $response);
});
$app->post('/getAllCourierDataOfSelectedCustomerwithStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getAllCourierDataOfSelectedCustomerwithStatus($r);\
    echoResponse(200, $response);
});
$app->post('/getAllCourierServicesForSelectedCustomer', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id','customer_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getAllCourierServicesForSelectedCustomer($r);
    echoResponse(200, $response);
});
$app->post('/getAllCourierSurchargeForSelectedCustomer', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id','customer_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getAllCourierSurchargeForSelectedCustomer($r);
    echoResponse(200, $response);
});

$app->post('/editSelectedcustomerServiceAccountStatusFromView', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->editSelectedcustomerServiceAccountStatus($r);
    echoResponse(200, $response);
});
$app->post('/editSelectedcustomerSurchargeAccountStatusFromView', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->editSelectedcustomerSurchargeAccountStatus($r);
    echoResponse(200, $response);
});
$app->post('/getallshipments', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','warehouse_id'),$r);
    $obj = new allShipments($r);
    //$response = $obj->getallshipments((object)array('company_id'=>$r->company_id,'access_token'=>$r->access_token,'warehouse_id'=>$r->warehouse_id,'datalimitpre'=>$r->datalimitpre,'datalimitpost'=>$r->datalimitpost,'data'=>$r->data));
		$response = $obj->getallshipments($r);
    echoResponse(200, $response);
});

/**list user start**/
$app->post('/getCustomerAllUserData', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	verifyRequiredParams(array('access_token','company_id','warehouse_id'),$r);
	$response = $obj->getUserDataByCustomerId($r);
	echoResponse(200, $response);
});
/**list user end**/

/**customer address list start**/
$app->post('/getCustomerAllAddressData', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	verifyRequiredParams(array('access_token','company_id','warehouse_id'),$r);
	$response = $obj->getCustomerAddressDataByCustomerId($r);
	echoResponse(200, $response);
});
/**customer address list end**/

$app->post('/getUserAddressDataById', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Customer($r);
    $response = $obj->getUserAddressDataByUserId($r);
    echoResponse(200, $response);
});

$app->post('/editDefaultAddress', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Customer($r);
    $response = $obj->editDefaultAddress($r);
    echoResponse(200, $response);
});

$app->post('/addUser', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','customer_id','company_id','warehouse_id','phone'),$r);
	$obj = new Customer($r);
	$status = $obj->addUser($r);
	echoResponse(200, $status);
});

$app->post('/addAddress', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','customer_id','company_id','phone','address_1','city','postcode','state','country'),$r);
	$obj = new Customer($r);
	$status = $obj->addAddress($r);
	echoResponse(200, $status);
});

$app->post('/getAddressDataById', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new Customer($r);
    $response["address_data"] = $obj->getAddressDataById($r);

    $obj = new Common();
    $countryData = $obj->countryList();
    $response["countryData"] = $countryData;
    echoResponse(200, $response);
});

$app->post('/deleteUserById', function() use ($app) {
	$response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
    $response = $obj->deleteUserById($r);
	echoResponse(200, $response);
});

$app->post('/deleteAddressById', function() use ($app) {
	$response = array();
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
    $response = $obj->deleteAddressById($r);
	echoResponse(200, $response);
});

$app->post('/editUser', function() use ($app) {
    $response = array();
	$r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	$response = $obj->editUser($r);
    echoResponse(200, $response);
});

$app->post('/editAddress', function() use ($app) {
    $response = array();
	$r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	$response = $obj->editAddress($r);
    echoResponse(200, $response);
});
$app->post('/setUserDefaultAddress', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Customer($r);
    $response = $obj->setUserDefaultAddress($r);
    echoResponse(200, $response);
});
$app->post('/searchAddressByUserId', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Customer($r);
    $response = $obj->searchAddressByUserId($r);
    echoResponse(200, $response);
});

$app->post('/startRoute', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','company_id','shipment_route_id','event_code'),$r);
    $obj = new Webevent_Index($r);
    $response = $obj->registerEvent($r);
    echoResponse(200, $response);
});

$app->post('/updateNotificationTemplates', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('email','access_token','company_id'),$r);
    $obj = new Notification_Index($r);
    $response = $obj->saveTemplate($r);
    echoResponse(200, $response);
});

$app->post('/updateNotificationStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    //verifyRequiredParams(array('access_token','company_id','email','jobtype','trigger_code','trigger_type','type'),$r);
    verifyRequiredParams(array('access_token','company_id','email','trigger_code','trigger_type'),$r);
    $obj = new Notification_Index($r);
    $response = $obj->updateNotificationStatus($r);
    echoResponse(200, $response);
});

$app->post('/getNotificationStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    //verifyRequiredParams(array('access_token','company_id','email','type','jobtype'),$r);
    verifyRequiredParams(array('access_token','company_id','email'),$r);
	$obj = new Notification_Index($r);
    $response = $obj->getNotificationStatus($r);
    echoResponse(200, $response);
});

$app->post('/loadCustomerAndUserByCustomerId', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new Controller($r);
    $response = $obj->loadCustomerAndUserByCustomerId(array("controller_id"=>$r->company_id, "warehouse_id"=>$r->warehouse_id));
    echoResponse(200, $response);
});

$app->post('/setDefaultUser', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id','user_id'),$r);
    $obj = new Customer($r);
    $response = $obj->setDefaultUser($r);
    echoResponse(200, $response);
});

$app->post('/getCustomerDefaultUser', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','customer_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getCustomerDefaultUser($r);
    echoResponse(200, $response);
});

$app->post('/searchAddressTest', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','customer_id','search_postcode'),$r);
    $obj = new Module_Addressbook_Addressbook($r);
    $response = $obj->getAllAddressesTest($r);
    echoResponse(200, $response);
});
$app->post('/sameday', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','identity'),$r);
	$obj = new allShipments($r);
	$records = $obj->getSameDayShipmentDetails(array('email'=>$r->email,'access_token'=>$r->access_token,'identity'=>$r->identity));
	echoResponse(200, $records);
});
$app->post('/nextday', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','identity'),$r);
	$obj = new allShipments($r);
	$records = $obj->getNextDayShipmentDetails(array('email'=>$r->email,'access_token'=>$r->access_token,'identity'=>$r->identity));
	echoResponse(200, $records);
});
$app->post('/getAllSettingRowData', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Settings($r);
    $response = $obj->getAllSettingRowData($r);
    echoResponse(200, $response);
});
$app->post('/getAllInvoiceShipmentStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Settings($r);
    $response = $obj->getAllInvoiceShipmentStatus($r);
    echoResponse(200, $response);
});
$app->post('/editInvoiceShipmentStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Settings($r);
    $response = $obj->editInvoiceShipmentStatus($r);
    echoResponse(200, $response);
});
$app->post('/getallinvoice', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Invoice($r);
    $response = $obj->getallinvoice($r);
    echoResponse(200, $response);
});
$app->post('/getallvoucher', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Voucher($r);
    $response = $obj->getallvoucher($r);
    echoResponse(200, $response);
});
$app->post('/createInvoice', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Invoice($r);
    $response = $obj->createInvoice($r);
    echoResponse(200, $response);
});
$app->post('/cancelinvoices', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Invoice($r);
    $response = $obj->cancelInvoices($r);
    echoResponse(200, $response);
});
$app->post('/createInvoicepdf', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Invoice($r);
    $response = $obj->createInvoicepdf($r);
    echoResponse(200, $response);
});
$app->post('/saveInvoicepdf', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Invoice($r);
    $response = $obj->saveInvoicepdf($r);
    echoResponse(200, $response);
});
$app->post('/shipmentTracking', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('identity'),$r);
    $obj = new Module_Shipment_Tracking();
    $response = $obj->getTracking($r);
    //echoResponse(200, $response);
    echo json_encode($response);exit();
});

/*start of report module comment by kavita 20march2018*/
$app->post('/getAllActiveReports', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','company_id'),$r);
    $obj = new Report($r);
    $response = $obj->getAllActiveReportByCompanyId($r);
    echoResponse(200, $response);
});

$app->post('/generateReport', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','company_id'),$r);
    $obj = new Report($r);
    $response = $obj->generateReport($r);
    echoResponse(200, $response);
});

/*end of report module comment by kavita 20march2018*/
$app->post('/loadCountry', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $countryId = isset($r->id) ? $r->id : 0;
    $obj = new Common();
    $response = $obj->countryList(array("controller_id"=>$r->company_id, 'id' => $countryId));
    echoResponse(200, $response);
});

/*$app->post('/getParcelPackage', function() use ($app){
    $r = json_decode($app->request->getBody());
    $dummyData = array("0"=>array("name"=>"Parcels","id"=>"1"));
    echoResponse(200, $dummyData);
});*/

$app->post('/getNextdayAvailableCarrier', function() use ($app){
	$r = json_decode($app->request->getBody());
    $obj = new Nextday($r);
    $response = $obj->searchNextdayCarrierAndPrice();

    if($response["status"]=="error"){
        echoResponse(500, $response);
    }else{
        echoResponse(200, $response);
    }
});
$app->post('/searchNextdayCarrierAndPriceManual', function() use ($app){
	$r = json_decode($app->request->getBody());
    $obj = new Nextday($r);
    $response = $obj->searchNextdayCarrierAndPriceManual($r);
    if($response["status"]=="error"){
        echoResponse(500, $response);
    }else{
        echoResponse(200, $response);
    }
});
$app->post('/bookNextDayJob', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new Nextday($r);
    $response = $obj->saveBooking($r);
    echoResponse(200, $response);
});

$app->post('/getPriceDetails', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new allShipments($r);
    $response = $obj->getPriceDetails($r);
    if($response["status"]=="error"){
        echoResponse(500, $response);
    }else{
        echoResponse(200, $response);
    }
});

/* $app->post('/loadCountry', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Common();
    $response = $obj->countryList(array("controller_id"=>$r->company_id));
    echoResponse(200, $response);
});*/

$app->post('/getParcelPackage', function() use ($app){
	$r = json_decode($app->request->getBody());
    $obj = new Module_Package_Index($r);
    $response = $obj->getPackages($r);
    echoResponse(200, $response);
});

$app->post('/savePackage', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new Module_Package_Index($r);
    $response = $obj->savePackage($r);
    if($response["status"]=="error"){
        echoResponse(500, $response);
    }else{
        echoResponse(200, $response);
    }
});

/*$app->post('/getPriceDetails', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new allShipments($r);
    $response = $obj->getPriceDetails($r);
    if($response["status"]=="error"){
        echoResponse(500, $response);
    }else{
        echoResponse(200, $response);
    }
});*/

/*start of save quote feature comment by kavita 2april2018*/
$app->post('/sendQuoteEmail', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','company_id'),$r);
    $obj = new Quotation($r);
    $response = $obj->sendQuoteEmail($r);
    echoResponse(200, $response);
});
$app->post('/getAllSavedQuotes', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
	verifyRequiredParams(array('access_token','email','company_id'),$r);
	$obj = new Quotation($r);
	if($r->user_code=="super_admin")
		$response = $obj->getAllSavedQuotes($r);
	else
		$response = $obj->getAllSavedQuotesByCompanyId(array("company_id"=>$r->company_id));

	echoResponse(200, $response);
});
$app->post('/getQuoteData', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','company_id','quote_number'),$r);
    $obj = new Quotation($r);
    $response = $obj->getQuoteDataByQuoteNumber($r);
    echoResponse(200, $response);
});

$app->post('/updateCarrierPrice', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','job_identity','job_type'),$r);
	$obj = new allShipments($r);
	$records = $obj->updateCarrierPrice(array('email'=>$r->email,'access_token'=>$r->access_token,'job_identity'=>$r->job_identity,'data'=>$r->data,'applypriceoncustomer'=>$r->applypriceoncustomer,'company_id'=>$r->company_id,'user'=>$r->user,'job_type'=>$r->job_type));
	echoResponse(200, $records);
});
$app->post('/updateCustomerPrice', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','job_identity','job_type'),$r);
	$obj = new allShipments($r);
	$records = $obj->updateCustomerPrice(array('email'=>$r->email,'access_token'=>$r->access_token,'job_identity'=>$r->job_identity,'data'=>$r->data,'applypriceoncustomer'=>$r->applypriceoncustomer,'company_id'=>$r->company_id,'user'=>$r->user,'job_type'=>$r->job_type));
	echoResponse(200, $records);
});


$app->post('/getbookedCarrierSurcharge', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','job_identity','company_id'),$r);
	$obj = new allShipments($r);
	$records = $obj->getbookedCarrierSurcharge(array('email'=>$r->email,'access_token'=>$r->access_token,'job_identity'=>$r->job_identity,'company_id'=>$r->company_id));
	echoResponse(200, $records);
});

/*start report type*/
$app->post('/getAllActiveReportsByServiceType', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','company_id','service_type'),$r);
    $obj = new Report($r);
    $response = $obj->getAllActiveReportsByServiceType($r);
    echoResponse(200, $response);
});
/*end report type*/

/*start download report csv*/
$app->post('/downloadReportCsv', function() use($app){
    $r = json_decode($app->request->getBody());
    $obj = new Report($r);
    $response = $obj->downloadReportCsv($r);
    echoResponse(200, $response);
});

$app->post('/setCustomerDefaultWarehouse', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','address_id','customer_id'),$r);
    $obj = new Customer($r);
    $response = $obj->setCustomerDefaultWarehouse($r);
    echoResponse(200, $response);
});

$app->post('/setCustomerWarehouse', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','address_id','customer_id'),$r);
    $obj = new Customer($r);
    $response = $obj->setCustomerWarehouse($r);
    echoResponse(200, $response);
});

$app->post('/setInternalCarrier', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','company_id','carrier_id','status'),$r);
    $obj = new Master($r);
    $response = $obj->setCompanyInternalCarrier($r);
    echoResponse(200, $response);
});

$app->post('/updateShipmentTracking', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new Settings($r);
    $response = $obj->updateShipmentTracking($r);
    echoResponse(200, $response);
});
$app->post('/updateInternalTracking', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new Settings($r);
    $response = $obj->updateInternalTracking($r);
    echoResponse(200, $response);
});
$app->post('/allowedTrackingstatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new allShipments($r);
    $response = $obj->allowedTrackingstatus($r);
    echoResponse(200, $response);
});
$app->post('/addCustomTracking', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new allShipments($r);
    $response = $obj->addCustomTracking($r);
    echoResponse(200, $response);
});
$app->post('/deleteCustomTracking', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new allShipments($r);
    $response = $obj->deleteCustomTracking($r);
    echoResponse(200, $response);
});
$app->post('/addCustomPod', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new allShipments($r);
    $response = $obj->addCustomPod($r);
    echoResponse(200, $response);
});

$app->post('/saveCarrier', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new Master($r);
    $response = $obj->saveCarrier($r);
    echoResponse(200, $response);
});

$app->post('/getAllWarehouseAddressByCompanyAndUser', function() use ($app) {
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new Controller($r);
    $response = $obj->getAllWarehouseAddressByCompanyAndUser(array("company_id" => $r->company_id, "user_id" => $r->user_id, "is_warehouse" => isset($r->is_warehouse) ? $r->is_warehouse : ''));
    echoResponse(200, $response);
});

$app->post('/imports/profile/samedaylist', function() use($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','access_token'),$r);
    $obj = new Profile();
    $response = $obj->fetchAll($r->company_id,'Same Day');
    echoResponse(200, $response);
});
$app->post('/getAllCarrier', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Settings($r);
    $response = $obj->getAllCarrier($r);
    echoResponse(200, $response);
});
$app->post('/getAllowedAllShipmentsStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new allShipments($r);
    $response = $obj->getAllowedAllShipmentsStatus($r);
    echoResponse(200, $response);
});
$app->post('/getAllowedAllServices', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new allShipments($r);
    $response = $obj->getAllowedAllServices($r);
    echoResponse(200, $response);
});
$app->post('/getAllShipmentsCarrier', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new allShipments($r);
    $response = $obj->getAllCarrier($r);
    echoResponse(200, $response);
});

$app->post('/getAllMasterCouriers', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','user_id'),$r);
    $obj = new Master($r);
    $response = $obj->getAllMasterCouriers($r);
    echoResponse(200, $response);
});
$app->post('/printLabelByLoadIdentity', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array(/* 'load_identity', */'company_id'),$r);
    $obj = new allShipments($r);
    $response = $obj->printLabelByLoadIdentity($r);
    echoResponse(200, $response);
});

$app->post('/saveRoutePostId', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $data = array("shipment_route_id"=>$r->shipment_route_id, "post_id"=>$r->post_id, "company_id"=>$r->company_id, "email"=>$r->email, "access_token"=>$r->access_token);
    $obj = new Route_Assign($data);
    $response = $obj->saveRoutePostId($data);
	echoResponse(200, $response);
});

$app->post('/getAddressBySearchString', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email','search_str','customer_id'),$r);
    $obj = new Customer($r);
    $response = $obj->getAddressBySearchString($r);
    echoResponse(200, $response);
});

$app->post('/getServiceFlowType', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email','service_id'),$r);
    $obj = new Master($r);
    $response = $obj->getServiceFlowType($r->service_id);
    echoResponse(200, $response);
});

$app->post('/editcountry', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Country($r);
    $response = $obj->updateCountry($r);
    if($response) {
        $results = array('status' => 'success', 'message' =>'Country updated successfully.');
    } else {
        $results = array('status' => 'error', 'message' =>'Please try again.');
    }
    echoResponse(200, $results);
});

$app->post('/loadNonDuitableCountry', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Country($r);
    $response = $obj->loadNonDuitableCountry($r);

    echoResponse(200, $response);
});

$app->post('/updateNonDutiable', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Country($r);
    $response = $obj->updateNonDutiable($r);

    if($response) {
        $results = array('status' => 'success', 'message' =>'Non dutiable list removed successfully.');
    } else {
        $results = array('status' => 'error', 'message' =>'Please try again.');
    }
    echoResponse(200, $results);
});
/*
 * Author: Amita Pandey
 * Date: 29-June-2018
 * Purpose: Used for adding non-dutiable country
 */
$app->post('/addNonDutiable', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Country($r);
    $response = $obj->addNonDutiable($r);

    if($response) {
        $results = array('status' => 'success', 'message' =>'Non dutiable country added successfully.');
    } else {
        $results = array('status' => 'error', 'message' =>'Please try again.');
    }
    echoResponse(200, $results);
});

$app->post('/checkDutiableCountry', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Common();
    $response = $obj->checkDutiableCountry($r);

    if($response) {
        $results = array('status' => 'error', 'message' =>'Please try again.');
    } else {
        $results = array('status' => 'success', 'result' => $response);
    }
    echoResponse(200, $results);
});


/*$app->post('/temp', function() use ($app) {
	$db = new DbHandler();
	$sql = "SELECT shipment_latlong, shipment_id from icargo_shipment;";
	$records = $db->getAllRecords($sql);
	foreach($records as $record){
		$temp = explode(',',$record['shipment_latlong']);
		$sql = "UPDATE icargo_shipment SET shipment_latitude = '" . $temp[0] . "', shipment_longitude = '" . $temp[1] . "' WHERE shipment_id = '". $record['shipment_id'] ."';";
		echo $sql.'<br>';
	}
});*/
/*start of cancel shipment*/
$app->post('/cancelShipmentByLoadIdentity', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','load_identity'),$r);
    $obj = new allShipments($r);
    $response = $obj->cancelShipmentByLoadIdentity($r);
    echoResponse(200, $response);
});
/*end of cancel shipment*/

$app->post('/saveNextdayQuotation', function() use ($app) {
    $r = json_decode($app->request->getBody());
    $obj = new Quotation($r);
    $response = $obj->saveAndSendNextdayQuotation($r);
    echoResponse(200, $response);
});

$app->post('/loadQuotationByQuotationId', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new Quotation($r);
    $response = $obj->loadQuotationByQuotationId($r);
    echoResponse(200, $response);
});

$app->post('/savePickup', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new Pickup($r);
    $response = $obj->savePickupForCustomer($r);
    echoResponse(200, $response);
});
$app->post('/getAllPickups', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new Pickup($r);
    $response = $obj->getAllPickups($r);
    echoResponse(200, $response);
});
$app->post('/saveUpdateAddress', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new Pickup($r);
    $response = $obj->saveUpdateAddress($r);
    echoResponse(200, $response);
});
$app->post('/savePickupForShipment', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new Nextday($r);
    $response = $obj->assignPickupForShipment($r);
    echoResponse(200, $response);
});
$app->post('/getPickupDetail', function() use ($app){
    $r = json_decode($app->request->getBody());
    $obj = new Pickup($r);
    $response = $obj->getPickupDetail($r);
    echoResponse(200, $response);
});

$app->post('/searchAllDefaultWarehouseAddress', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','email','customer_id','search_postcode'),$r);
    $obj = new Module_Addressbook_Addressbook($r);
    $response = $obj->getAllDefaultWarehouseAddressBySearchKey($r);
    echoResponse(200, $response);
});

$app->post('/releaseShipmentFromSameday', function() use ($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email','shipment_route_id','warehouse_id'),$r);
    $obj = new Shipment_Sameday_Release(array("email"=>$r->email, "access_token"=>$r->access_token));
    $response = $obj->releaseShipments($r);
    echoResponse(200, $response);
});

$app->post('/withdrawAssignedRoute', function() use ($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email','shipment_route_id'),$r);
    $obj = new Route_Release(array("email"=>$r->email, "access_token"=>$r->access_token));
    $response = $obj->routeReleaseFromDriver($r);
    echoResponse(200, $response);
});

$app->post('/updateTrackingStatus', function() use ($app){
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new Route_Release(array("email"=>$r->email, "access_token"=>$r->access_token));
    $response = $obj->routeReleaseFromDriver($r);
    echoResponse(200, $response);
});
$app->post('/getCustomerAllTransactionData', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	verifyRequiredParams(array('access_token','company_id','warehouse_id'),$r);
	$response = $obj->getCustomerAllTransactionData($r);
	echoResponse(200, $response);
});
$app->post('/unholdJob', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new allShipments($r);
    $response = $obj->unholdJob($r);
    echoResponse(200, $response);
});
$app->post('/holdJob', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new allShipments($r);
    $response = $obj->holdJob($r);
    echoResponse(200, $response);
});
$app->post('/getlogo', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new allShipments($r);
    $response = $obj->getlogo($r);
    echoResponse(200, $response);
});
$app->post('/recurringShipmentDetails', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token'),$r);
	$obj = new allShipments($r);
	$records = $obj->getRecurringShipmentDetails($r);
	echoResponse(200, $records);
});
$app->post('/recurringJob', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token'),$r);
	$obj = new allShipments($r);
	$records = $obj->bookRecurringJob($r);
	echoResponse(200, $records);
});

$app->post('/getRecurringJobs', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','company_id'),$r);
	$obj = new allShipments($r);
	$records = $obj->getRecurringJobs($r);
	echoResponse(200, $records);
});

$app->post('/editRecurringjobStatus', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','company_id','descid'),$r);
	$obj = new allShipments($r);
	$records = $obj->editRecurringjobStatus($r);
	echoResponse(200, $records);
});
$app->post('/deleteRecurringjobStatus', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','company_id','descid','jobref'),$r);
	$obj = new allShipments($r);
	$records = $obj->deleteRecurringjobStatus($r);
	echoResponse(200, $records);
});

$app->post('/recurringJobDetails', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','job_identity'),$r);
	$obj = new allShipments($r);
	$records = $obj->recurringJobDetails($r);
	echoResponse(200, $records);
});
$app->post('/updateRecurringJob', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token'),$r);
	$obj = new allShipments($r);
	$records = $obj->updateRecurringJob($r);
	echoResponse(200, $records);
});
$app->post('/getAllRecurringBreakdown', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token','company_id','job_reference'),$r);
	$obj = new allShipments($r);
	$records = $obj->getAllRecurringBreakdown($r);
	echoResponse(200, $records);
});
$app->post('/checkEligibleForCancel', function() use ($app) {
	$response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new allShipments($r);
    $response = $obj->checkEligibleForCancel($r);
    echoResponse(200, $response);
});
$app->post('/cancelJob', function() use ($app) {
	$response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new allShipments($r);
    $response = $obj->cancelJob($r);
    echoResponse(200, $response);
});
$app->post('/getAuthorizationData', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	verifyRequiredParams(array('access_token','company_id','warehouse_id','customer_id'),$r);
	$response = $obj->getAuthorizationData($r);
	echoResponse(200, $response);
});
$app->post('/editAuthorizationStatus', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	verifyRequiredParams(array('access_token','company_id','descid','customer_id','status'),$r);
	$response = $obj->editAuthorizationStatus($r);
	echoResponse(200, $response);
});
$app->post('/editAuth', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	verifyRequiredParams(array('access_token','company_id','descid','title','url','description'),$r);
	$response = $obj->editAuthorization($r);
	echoResponse(200, $response);
});
$app->post('/addAuth', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	verifyRequiredParams(array('access_token','company_id','title','customer_id','url','description'),$r);
	$response = $obj->addAuthorization($r);
	echoResponse(200, $response);
});
$app->post('/payinvoices', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Invoice($r);
    $response = $obj->payinvoices($r);
    echoResponse(200, $response);
});
$app->post('/loadPostpaidCustomer', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new Invoice($r);
    $response = $obj->loadPostpaidCustomer($r->company_id);
    echoResponse(200, $response);
});
$app->post('/saveEasyPostTracking', function() use ($app){
    $r = json_decode(file_get_contents("php://input"));
    $obj = new Easypost_Tracking($r->data);
    $obj->saveTracking();
    exit();
});
/*
$app->post('/createTracking', function() use ($app){
    $r = json_decode(file_get_contents("php://input"));
    $tracking_code = "1174215114";
    $carrier = "DHLExpress";
    $obj = new Create_Tracking();
    $obj->createTracking($tracking_code, $carrier);
    exit();
});*/
$app->post('/checkEligibleforRecurring', function() use ($app) {
	$response = array();
	$r = json_decode($app->request->getBody());
	verifyRequiredParams(array('email','access_token'),$r);
	$obj = new allShipments($r);
	$records = $obj->checkEligibleforRecurring($r);
	echoResponse(200, $records);
});
$app->post('/loadPrepaidCustomer', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id','email'),$r);
    $obj = new Invoice($r);
    $response = $obj->loadPrepaidCustomer($r->company_id);
    echoResponse(200, $response);
});
$app->post('/prepaidrecharge', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Invoice($r);
    $response = $obj->prepaidrecharge($r);
    echoResponse(200, $response);
});
$app->post('/downloadAccountStatements', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	verifyRequiredParams(array('access_token','company_id','customer_id','from','to'),$r);
	$response = $obj->downloadAccountStatements($r);
	echoResponse(200, $response);
});
$app->post('/getProfileInfo', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new Company($r);
    $response = $obj->getUserProfileInfo($r);
    echoResponse(200, $response);
});

$app->post('/updateProfile', function() use ($app){
    $response = array();
    $r = (object)$app->request->post();
    $obj = new Company($r);
    $response = $obj->updateUserInfo($r);
    echoResponse(200, $response);
});
$app->post('/createStripeCustomer', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new ServiceProvider($r);
    $response = $obj->createStripeCustomer($r);
    echoResponse(200, $response);
});
$app->post('/getStripeCustomer', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new ServiceProvider($r);
    $response = $obj->getStripeCustomer($r);
    echoResponse(200, $response);
});
$app->post('/saveCustomerToken', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new ServiceProvider($r);
    $response = $obj->saveCustomerToken($r);
    echoResponse(200, $response);
});
$app->post('/getCustomerServiceProvider', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new ServiceProvider($r);
    $response = $obj->getCustomerServiceProvider($r);
    //print_r($response); die;
    echoResponse(200, $response);
});
$app->post('/getServiceProviderById', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new ServiceProvider($r);
    $response = $obj->getServiceProviderById($r);
    //print_r($response); die;
    echoResponse(200, $response);
});
$app->post('/saveCustomerTransaction', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    $obj = new ServiceProvider($r);
    $response = $obj->saveCustomerTransaction($r);
    //print_r($response); die;
    echoResponse(200, $response);
});
$app->post('/checkInvoiceNumber', function() use ($app){
    $response = array();
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('access_token','company_id'),$r);
    $obj = new Invoice($r);
    $response = $obj->checkInvoiceNumber($r);
    //print_r($response); die;
    echoResponse(200, $response);
});
$app->post('/checkCustomerData', function() use ($app) {
    $r = json_decode($app->request->getBody());
	$obj = new Customer($r);
	verifyRequiredParams(array('access_token','company_id'),$r);
	$response = $obj->checkCustomerData($r);
	echoResponse(200, $response);
});
$app->post('/getCarrierSurcharge', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','courier_id','email','access_token'),$r);
    $obj = new allShipments($r);
    $response = $obj->getCarrierSurcharge($r);
    echoResponse(200, $response);
});
$app->post('/getCarrierServices', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','courier_id','email','access_token'),$r);
    $obj = new allShipments($r);
    $response = $obj->getCarrierServices($r);
    echoResponse(200, $response);
});
$app->post('/getCarriersofCompany', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','email','access_token'),$r);
    $obj = new allShipments($r);
    $response = $obj->getCarriersofCompany($r);
    echoResponse(200, $response);
});
$app->post('/getNextDayCarriersofCompany', function() use ($app) {
    $r = json_decode($app->request->getBody());
    verifyRequiredParams(array('company_id','email','access_token'),$r);
    $obj = new allShipments($r);
    $response = $obj->getNextDayCarriersofCompany($r);
    echoResponse(200, $response);
});



GridConfiguration::initRoutes($app);
CustomFilterConfiguration::initRoutes($app);
DriverController::initRoutes($app);
SubscriptionController::initRoutes($app);

$app->post('/apiLogin', function() use ($app){
	$r = json_decode($app->request->getBody());
	$obj = new Firebase_User_Management();
	verifyRequiredParams(array('email','password'),$r->auth);
	$response = $obj->customerLogin($r->auth->email,$r->auth->password);
	echoResponse(200, $response);
});

$app->post('/apiForgotPassword', function() use ($app){
	$r = json_decode($app->request->getBody());
	$obj = new Firebase_User_Management();
	verifyRequiredParams(array('email'),$r->auth);
	$response = $obj->forgotPassword($r->auth->email);
	echoResponse(200, $response);
});

$app->post('/apiSignup', function() use ($app){
	$r = json_decode($app->request->getBody());
	$obj = new Firebase_User_Management();
	verifyRequiredParams(array('email','password'),$r);
	$response = $obj->customerSignup($r);
	echoResponse(200, $response);
});

$app->post('/checkChangedAddress', function() use ($app){
	$r = json_decode($app->request->getBody());
	$obj = new Module_Addressbook_Addressbook($r);
  $response = $obj->checkChangedAddress($r);
  echoResponse(200, $response);
});

$app->post('/fixAddressString', function() use ($app){//delete after execution
	$r = json_decode($app->request->getBody());
	$obj = new Module_Addressbook_Addressbook($r);
  $response = $obj->getAllAddressesFromAddressBook();
  echoResponse(200, $response);
});

$app->post('/fixDrivingModeAndRoundTrip', function() use ($app){//delete after execution
	$db = new DbHandler();
	$sql = "SELECT * FROM icargo_configuration";
	$records = $db->getAllRecords($sql);

	foreach($records as $record){
	    $conf = json_decode($record["configuration_json"]);

			if(!isset($conf->round_trip))
			    $conf->round_trip = ROUND_TRIP;

			if(!isset($conf->driving_mode))
			    $conf->driving_mode = DRIVING_MODE;

			$confJson = json_encode($conf);
			$id = $record["id"];
			$updateSql = "UPDATE icargo_configuration SET configuration_json='$confJson' WHERE id='$id'";
			$db->updateData($updateSql);
	}
});
