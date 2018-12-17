<?php
require_once(dirname(dirname(dirname(__FILE__))).'/postcodeanywhere/lookup.php');
class View_Support extends Icargo{
	public $modelObj = null;

    private $_common_model_obj = null;

	public $shipmentAttemptConf = array();

	public function __construct($param){

        parent::__construct(array("email"=>$param['email'],"access_token"=>$param['access_token']));

        $this->modelObj  = Shipment_Model::getInstanse();  // Added By Roopesh

        $this->_common_model_obj  = new Common();  // Added By Roopesh

        if(isset($param['email'])){
			$this->primary_email = $param['email'];
		}

        if(isset($param['access_token'])){
            $this->access_token = $param['access_token'];
        }

        if(isset($param['company_id'])){
            $this->company_id = $param['company_id'];
        }

		if(isset($param['shipment_ticket'])){
			$this->shipment_ticket = $param['shipment_ticket'];
		}

		if(isset($param['warehouse_status'])){
			$this->warehouse_status = $param['warehouse_status'];
		}

		if(isset($param['shipment_route_id'])){
			$this->shipment_route_id = $param['shipment_route_id'];
		}
		if(isset($param['warehouse_status'])){
			$this->warehouse_status = $param['warehouse_status'];
		}
		if(isset($param['pickup_status'])){
			$this->pickup_status = $param['pickup_status'];
		}

        if(isset($param['driver_id'])){
			$this->driver_Id = $param['driver_id'];
		}

		if(isset($param['driver_name'])){
			$this->driver_name = $param['driver_name'];
		}
		if(isset($param['accept_status'])){
			$this->accept_status = $param['accept_status'];
		}
		if(isset($param['route_type'])){
			$this->route_type = $param['route_type'];
		}
		if(isset($param['warehouse_id'])){
			$this->warehouse_id = $param['warehouse_id'];
		}
		if(isset($param['start_time'])){
			$this->start_time = $param['start_time'];
		}
		if(isset($param['form_data'])){
			$this->form_data = $param['form_data'];
		}
		if(isset($param['comment'])){
			$this->comment = $param['comment'];
		}
		if(isset($param['next_date'])){
			$this->next_date = $param['next_date'];
		}
		if(isset($param['next_time'])){
			$this->next_time = $param['next_time'];
		}
		if(isset($param['failure_status'])){
			$this->failure_status = $param['failure_status'];
		}
		if(isset($param['contact_name'])){
			$this->contact_name = $param['contact_name'];
		}
        if(isset($param['email'])){
			$this->email = $param['email'];
		}
        if(isset($param['access_token'])){
			$this->access_token = $param['access_token'];
		}
        if(isset($param['post_id'])){
			$this->post_id = $param['post_id'];
		}
        if(isset($param['save_post_id'])){
			$this->save_post_id = $param['save_post_id'];
		}
        if(isset($param['shipment_address1'])){
			$this->shipment_address1 = $param['shipment_address1'];
		}
        if(isset($param['shipment_address2'])){
			$this->shipment_address2 = $param['shipment_address2'];
		}
        if(isset($param['shipment_address3'])){
			$this->shipment_address3 = $param['shipment_address3'];
		}
        if(isset($param['shipment_postcode'])){
			$this->shipment_postcode = $param['shipment_postcode'];
		}
		if(isset($param['address_id'])){
			$this->address_id = $param['address_id'];
		}
        if(isset($param['uid'])){
            $this->uid = $param['uid'];
        }

		if(isset($param['country_code'])){
			$this->country_code = $param['country_code'];
		}


	}

    private function _get_route_type($param){
        $key = strtolower($param);
        $type = array("s"=>"S", "v"=>"N", "n"=>"N");
        return $type[$key];
    }

    private function _assigned_loads(){
        $temp = array();

        $records = $this->modelObj->getActiveRoute($this->company_id);

        foreach($records as $key => $record){
            $shipmentData = $this->modelObj->getAssignRouteShipmentDetailsByShipmentRouteId($this->company_id, $record['shipment_route_id'], $record["driver_id"]);
            if(count($shipmentData)>0){
            $temp[$record['shipment_route_id']]['info']['row_id'] = $key;

            //get driver name
            $driverData = $this->modelObj->getCustomerById($record["driver_id"]);

            $temp[$record['shipment_route_id']]['info']['shipment_routed_id'] = $record["shipment_route_id"];
            $temp[$record['shipment_route_id']]['info']['route_id'] = $record["route_id"];
            $temp[$record['shipment_route_id']]['info']['assign_driver'] = $record["driver_id"];
            $temp[$record['shipment_route_id']]['info']['route_name'] = $record["route_name"];
            $temp[$record['shipment_route_id']]['info']['route_status'] = $record["status"];
            $temp[$record['shipment_route_id']]['info']['start_time'] = date("h:i A",strtotime($record['assign_start_time']));
            $temp[$record['shipment_route_id']]['info']['post_id'] = $record['firebase_id'];


            $temp[$record['shipment_route_id']]['info']['route_label'] = "NA";

            if($record['driver_id'] > 0){
              if($record['driver_accepted'] < 1){
                $routeLabel = 'Pending';
              }elseif(($record['driver_accepted'] > 0) && $record['is_route_started'] < 1){
                $routeLabel = 'Accepted';
              }elseif(($record['driver_accepted'] > 0) && $record['is_route_started'] > 0){
                $routeLabel = 'On Route';
              }
            }
            $temp[$record['shipment_route_id']]['info']['route_label'] = $routeLabel;
            $temp[$record['shipment_route_id']]['info']['is_pause'] = (isset($record['is_pause']) and $record['is_pause'] == 1) ? "paused" : "";

            $temp[$record['shipment_route_id']]['info']['driver_name'] = $driverData[0]["name"];
            $temp[$record['shipment_route_id']]['info']['uid'] = $driverData[0]["uid"];

            $temp[$record['shipment_route_id']]['info']['shipment_count'] = count($shipmentData);

            $customers = array();
            foreach($shipmentData as $shipment){

                $customers[$record['shipment_route_id']][$shipment["customer_id"]] = $shipment["customer_id"];

                $temp[$record['shipment_route_id']]['info']['shipment_postcodes'][] = $shipment['shipment_postcode'];
                $temp[$record['shipment_route_id']]['info']['shipment_ticket'][] = $shipment['shipment_ticket'];

                $temp[$record['shipment_route_id']]['info']['load_group_type'] = $this->_get_route_type(substr($shipment['instaDispatch_loadGroupTypeCode'],0,1));

                $temp[$record['shipment_route_id']]['info']['shipments'][] = array(
                    'service_type'=>$shipment['shipment_service_type'],
                    'icargo_execution_order'=>$shipment['icargo_execution_order'],
                    'drop_name'=>$this->_common_model_obj->getDropName(array("postcode"=>$shipment['shipment_postcode'],"address_1"=>$shipment['shipment_address1'],)),
                    'postcode'=>$shipment['shipment_postcode'],
                    'address_1'=>$shipment['shipment_address1'],
                    'shipment_id'=>$shipment['shipment_id'],
                    'current_status'=>$shipment['current_status'],
                    'shipment_routed_id'=>$shipment['shipment_routed_id'],
                    'shipment_ticket'=>$shipment['shipment_ticket'],
                    'shipment_geo_locations'=>array('latitude'=>$shipment['shipment_latitude'],'longitude'=>$shipment['shipment_longitude']),
                    'consignee_name'=>$shipment['shipment_customer_name']
                );
            }

            $temp[$record['shipment_route_id']]['info']['start_postcode'] = $temp[$record['shipment_route_id']]['info']['shipment_postcodes'][0];
            $temp[$record['shipment_route_id']]['info']['end_postcode'] = end($temp[$record['shipment_route_id']]['info']['shipment_postcodes']);

            foreach($customers as $shipment_route_id => $customer){
                //get customer name
                $customerData = $this->modelObj->getCustomerById(implode(",", $customer));

                foreach($customerData as $customer_data){
                    $temp[$shipment_route_id]['info']['customer_name'][] = $customer_data["name"];
                }

                //get parcel data
                $tickets = "'".implode("','", $temp[$shipment_route_id]["info"]["shipment_ticket"])."'";
                $parcels = $this->modelObj->getShipmentParcels($tickets);

                $temp[$record['shipment_route_id']]['info']['parcels'] = $parcels;
                $temp[$record['shipment_route_id']]['info']['parcel_count'] = count($parcels);
            }
        }
        }
        return $temp;
    }

	private function _assigned_loads25_June_2018(){
		$records = $this->modelObj->getAssignRouteShipmentDetails($this->company_id);

        $temp = array();
		foreach($records as $key => $record){
			$temp[$record['shipment_routed_id']]['info']['row_id'] = $key;
			$temp[$record['shipment_routed_id']]['info']['shipment_routed_id'] = $record['shipment_routed_id'];
			$temp[$record['shipment_routed_id']]['info']['driver_name'] = $record['driver_name'];

			$temp[$record['shipment_routed_id']]['info']['load_group_type'] = $this->_get_route_type(substr($record['instaDispatch_loadGroupTypeCode'],0,1));
			$temp[$record['shipment_routed_id']]['info']['shipment_postcodes'][] = $record['shipment_postcode'];
			$temp[$record['shipment_routed_id']]['info']['shipment_ticket'][] = $record['shipment_ticket'];
			$temp[$record['shipment_routed_id']]['info']['shipment_count'] = (isset($temp[$record['shipment_routed_id']]['info']['shipment_count'])) ? ++$temp[$record['shipment_routed_id']]['info']['shipment_count'] : 1 ;

			$customer_name = $this->modelObj->getCustomerById($record["customer_id"]);

			$customers[] = $customer_name[0]["name"];

            $temp[$record['shipment_routed_id']]['info']['shipments'][] = array(
                'service_type'=>$record['shipment_service_type'],
                'icargo_execution_order'=>$record['icargo_execution_order'],
                'drop_name'=>$this->_common_model_obj->getDropName(array("postcode"=>$record['shipment_postcode'],"address_1"=>$record['shipment_address1'],)),
                'postcode'=>$record['shipment_postcode'],
                'address_1'=>$record['shipment_address1'],
                'shipment_id'=>$record['shipment_id'],
                'current_status'=>$record['current_status'],
                'shipment_routed_id'=>$record['shipment_routed_id'],
                'shipment_ticket'=>$record['shipment_ticket'],
                'shipment_geo_locations'=>array('latitude'=>$record['shipment_latitude'],'longitude'=>$record['shipment_longitude']),
                'consignee_name'=>$record['shipment_customer_name']
            );
        }
        foreach($temp as $key => $data){
            $temp[$key]['info']['customer_name'] = array_unique($customers);
			$route_data = $this->modelObj->_get_assigned_route_detail($data['info']['shipment_routed_id']);
			$routeLabel  = 'NA';
			if($route_data['driver_id'] > 0){
			  if($route_data['driver_accepted'] < 1){
				$routeLabel = 'Pending';
			  }elseif(($route_data['driver_accepted'] > 0) && $route_data['is_route_started'] < 1){
				$routeLabel = 'Accepted';
			  }elseif(($route_data['driver_accepted'] > 0) && $route_data['is_route_started'] > 0){
                $routeLabel = 'On Route';
			  }
			}

			$temp[$key]['info']['route_id'] = $route_data['route_id'];
			$temp[$key]['info']['assign_driver'] = $route_data['driver_id'];
			$temp[$key]['info']['route_name'] = $route_data['route_name'];
			$temp[$key]['info']['route_status'] = $route_data['status'];
			$temp[$key]['info']['start_time'] = date("h:i A",strtotime($route_data['assign_start_time']));
			$temp[$key]['info']['route_label'] = $routeLabel;

            $temp[$key]['info']['is_pause'] = (isset($route_data['is_pause']) and $route_data['is_pause'] == 1) ? "paused" : "";


			$postcodes = $data['info']['shipment_postcodes'];
			$tickets = "'".implode("','", $data['info']['shipment_ticket'])."'";

			$temp[$key]['info']['parcels'] = $this->modelObj->getShipmentParcels($tickets);


			$temp[$key]['info']['parcel_count'] = count($temp[$key]['info']['parcels']);
			$temp[$key]['info']['end_postcode'] = array_pop($postcodes);
			$temp[$key]['info']['start_postcode'] = array_shift($postcodes);

            $temp[$key]['info']['uid'] = $route_data['uid'];
            $temp[$key]['info']['post_id'] = $route_data['firebase_id'];
		}
		return $temp;
	}

    private function _assigned_load(){
        $common_obj = new Common();
        $temp = array();
        $postcodes = array();
        $shipmentTickets = array();
        $completedShipment = 0;
        $route_status = "notCompleted";
        $customer = array();

        $records = $this->modelObj->getAssignRouteShipmentDetailsByShipmentRouteId($this->company_id, $this->shipment_route_id, $this->driver_Id);
        foreach($records as $key => $record){
            $shipment_service_type = ($record["shipment_service_type"]=="P") ? "Collection" : "Delivery";
            $temp['info']['row_id'] = $key;
            $temp['info']['shipment_routed_id'] = $record['shipment_routed_id'];
            $temp['info']['driver_name'] = $record['driver_name'];
            $temp['info']['load_group_type'] = $this->_get_route_type(substr($record['instaDispatch_loadGroupTypeCode'],0,1));
            $temp['info']['shipment_count'] = (isset($temp['info']['shipment_count'])) ? ++$temp['info']['shipment_count'] : 1 ;
            $temp['info']['shipment_geo_locations'][$record['shipment_ticket']] = array('shipment_service_type'=>$shipment_service_type,'latitude'=>$record['shipment_latitude'],'longitude'=>$record['shipment_longitude'],'postcode'=>$record['shipment_postcode'],'address_1'=>$record['shipment_address1'],'current_status'=>$record['current_status'],'icargo_execution_order'=>$record['icargo_execution_order'],'parcel_count'=>$record['shipment_total_item'],'consignee_name'=>$record['consignee_name'],'drop_name'=>$common_obj->getDropName(array('postcode'=>$record['shipment_postcode'],'address_1'=>$record['shipment_address1']),false));
            array_push($postcodes, $record['shipment_postcode']);
            array_push($shipmentTickets, $record['shipment_ticket']);
            array_push($customer, $record['customer_id']);
        }

        if(count($temp)>0)
        {
            foreach($temp['info']['shipment_geo_locations'] as $item){
                if($item["current_status"]=="D")
                    $completedShipment++;
            }

            if($completedShipment==$temp['info']['shipment_count']){
                $route_status = "completed";
            }


            $route_data = $this->modelObj->_get_assigned_route_detail($temp['info']['shipment_routed_id']);
            $routeLabel  = 'NA';
            if($route_data['driver_id'] > 0)
            {
                if($route_data['driver_accepted'] < 1)
                {
                $routeLabel = 'Pending';
                }
                elseif(($route_data['driver_accepted'] > 0) && $route_data['is_route_started'] < 1)
                {
                $routeLabel = 'Accepted';
                }
                elseif(($route_data['driver_accepted'] > 0) && $route_data['is_route_started'] > 0)
                {
                $routeLabel = 'On Route';
                }
            }
            $temp['info']['route_id'] = $route_data['route_id'];
            $temp['info']['post_id']  = $this->post_id;

            $temp['info']['is_pause'] = ($route_data['is_pause']==1)?'paused':'';
            $temp['info']['assign_driver'] = $route_data['driver_id'];
            $temp['info']['route_name'] = $route_data['route_name'];
            $temp['info']['route_status'] = $route_data['status'];
            $temp['info']['start_time'] = date("h:i A",strtotime($route_data['assign_start_time']));
            $temp['info']['route_label'] = $routeLabel;
            $temp['info']['shipment_ticket'] = $shipmentTickets;

            $tickets = implode("','", $shipmentTickets);
            $tickets = "'$tickets'";

            $parcels = $this->modelObj->getShipmentParcels($tickets);

            $customers = $this->modelObj->getCustomerById(implode("','", array_unique($customer)));
            $initials = array();
            foreach($customers as $key=>$customer){
                // show only initials
                $charSet = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $customer["name"]);
                $charSet = rtrim($charSet);
                $charSetArray = explode(" ", $charSet);
                array_push($initials, $charSetArray[0]);
            }

            $temp['info']['customers'] = $initials;


            $temp['info']['parcel_count'] = count($parcels);
            $temp['info']['end_postcode'] = array_pop($postcodes);
            $temp['info']['start_postcode'] = array_shift($postcodes);
            $temp['info']['uid']  = $this->uid;

            $temp['route_status'] = $route_status;
        }
        return $temp;
    }

	private function _unassigned_load(){
        $common_obj = new Common();
		$records = $this->modelObj->getUnAssignShipmentDetails($this->company_id);
		$temp = array();
        $customer = array();
        foreach($records as $key => $record){
		    $route_data = $this->modelObj->getShipmentRouteByShipmentRouteId($record['shipment_routed_id']);
			$temp[$route_data['shipment_route_id']]['info']['customer'][] = $record["customer_id"];
            $temp[$route_data['shipment_route_id']]['info']['row_id'] = $key;

			$temp[$route_data['shipment_route_id']]['info']['route_id'] = $route_data['route_id'];
			$temp[$route_data['shipment_route_id']]['info']['shipment_routed_id'] = $record['shipment_routed_id'];
			$temp[$route_data['shipment_route_id']]['info']['load_group_type'] = $this->_get_route_type(substr($record['instaDispatch_loadGroupTypeCode'],0,1));
			$temp[$route_data['shipment_route_id']]['info']['route_name'] = $route_data['route_name'];
			$temp[$route_data['shipment_route_id']]['info']['is_route_rejected'] = ($route_data['is_route_rejected']=='YES')?'Rejected':'';
			$temp[$route_data['shipment_route_id']]['info']['route_status'] = $route_data['status'];
			$temp[$route_data['shipment_route_id']]['info']['start_time'] = date("h:i A",strtotime($route_data['assign_start_time']));
			$temp[$route_data['shipment_route_id']]['info']['shipment_postcodes'][] = $record['shipment_postcode'];
			$temp[$route_data['shipment_route_id']]['info']['shipment_ticket'][] = $record['shipment_ticket'];
			//$temp[$route_data['shipment_route_id']]['info']['distance_miles'][] = $record['distance_miles'];
			//$temp[$route_data['shipment_route_id']]['info']['cities'][] = $record['shipment_customer_city'];
			//$temp[$route_data['shipment_route_id']]['info']['estimated_time'][] = strtotime($record['estimated_time']);
			$temp[$route_data['shipment_route_id']]['info']['shipment_count'] = (isset($temp[$route_data['shipment_route_id']]['info']['shipment_count'])) ? ++$temp[$route_data['shipment_route_id']]['info']['shipment_count'] : 1 ;
            $temp[$route_data['shipment_route_id']]['info']['shipment_geo_locations'][$record['shipment_ticket']] = array('latitude'=>$record['shipment_latitude'],'longitude'=>$record['shipment_longitude'],'postcode'=>$record['shipment_postcode'],'current_status'=>$record['current_status'],'icargo_execution_order'=>$record['icargo_execution_order'],'parcel_count'=>$record['shipment_total_item'],'drop_name'=>$common_obj->getDropName(array('postcode'=>$record['shipment_postcode'],'address_1'=>$record['shipment_address1']),false));

		}

		foreach($temp as $key => $data){
            $customers = $this->modelObj->getCustomerById(implode("','", array_unique($data["info"]["customer"])));
            $initials = array();
            foreach($customers as $key_1=>$customer){
                // show only initials
                $charSet = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $customer["name"]);
                $charSet = rtrim($charSet);
                $charSetArray = explode(" ", $charSet);
                array_push($initials, $charSetArray[0]);
            }

            $temp[$key]['info']['customers'] = $initials;

			$postcodes = $data['info']['shipment_postcodes'];
			//$cities = $data['info']['cities'];
			$temp[$key]['info']['parcels'] = $this->modelObj->getShipmentParcels("'".implode("','", $data['info']['shipment_ticket'])."'");

			$temp[$key]['info']['parcel_count'] = count($temp[$key]['info']['parcels']);

			$temp[$key]['info']['end_postcode'] = array_pop($postcodes);
			$temp[$key]['info']['start_postcode'] = array_shift($postcodes);
			//$temp[$key]['info']['city'] = array_shift($cities);

			//$temp[$key]['info']['route_duration'] = date("h:i A",strtotime(array_sum($data['info']['estimated_time'])));

			unset($data["info"]["customer"]);
		}
		return array_values($temp);
	}

    private

    function _completed_load($param){
        $records = $this->modelObj->getCompletedRouteShipmentDetailsByShipmentRouteIdAndSearchDate($this->company_id, $param["search_date"], $param["warehouse_id"]);
        foreach($records as $key => $record){
            $data = $this->modelObj->getShipmentsByShipmentRouteId($record["shipment_route_id"]);

            if(!$data){
                unset($records[$key]);
            }else{
                $startPostcode = current($data);
                $endPostcode = end($data);

                $shipment_id = array();
                $records[$key]["shipments"] = array();

                $records[$key]["start_postcode"] = $startPostcode["postcode"];
                $records[$key]["end_postcode"] = $endPostcode["postcode"];
                $records[$key]["shipment_count"] = count($data);
                $records[$key]["servicedate"] = date("Y-m-d",strtotime($record["service_date"]));
                $customers = array();

                foreach($data as $item){
                    array_push($shipment_id, $item["shipment_id"]);

                    $customer_name = $this->modelObj->getCustomerById($item["customer_id"]);
                    $customers[] = $customer_name[0]["name"];

                    array_push($records[$key]["shipments"], array("service_type"=>$item["shipment_service_type"],"icargo_execution_order"=>$item["icargo_execution_order"],"drop_name"=>$this->_common_model_obj->getDropName(array("postcode"=>$item["postcode"],"address_1"=>$item["address_line1"])),"postcode"=>$item["postcode"],"address_1"=>$item["address_line1"],"shipment_id"=>$item["shipment_id"],"shipment_ticket"=>$item["shipment_ticket"],"shipment_geo_locations"=>array("latitude"=>$item["latitude"],"longitude"=>$item["longitude"])));

                    $records[$key]["load_group_type"] = $this->_get_route_type(substr($item['instaDispatch_loadGroupTypeCode'],0,1));
                }

                $parcelCount = $this->modelObj->getParcelCountByShipmentId(implode(",",$shipment_id));

                $records[$key]["shipment_routed_id"] = $record["shipment_route_id"];
                $records[$key]["parcel_count"] = $parcelCount["parcel_count"];
                $records[$key]["customer_name"] = array_unique($customers);
            }
        }
        return $records;
    }

    public function loadView($param){

		//return array(/*'assigned_load'=>$this->_assigned_loads(),*/'completed_load'=>$this->_completed_load(array("search_date"=>$param["search_date"],"warehouse_id"=>$param["warehouse_id"])),'unassigned_load'=>$this->_unassigned_load(),/*'active_driver_list'=>$this->_get_active_drivers(),'failed_action'=>$this->_get_failed_action()*/);
        return array('assigned_load'=>$this->_assigned_loads(),'completed_load'=>$this->_completed_load(array("search_date"=>$param["search_date"],"warehouse_id"=>$param["warehouse_id"])),'unassigned_load'=>$this->_unassigned_load(),'active_driver_list'=>$this->_get_active_drivers(),'failed_action'=>$this->_get_failed_action());
	}

	public function loadAssignedView(){
		return array('assigned_load'=>$this->_assigned_load());//,'active_driver_list'=>$this->_get_active_drivers()
    }

    public function pauseAssignedView(){
        if($this->_route_paused()){
        	return array('assigned_load'=>$this->_assigned_load(),'active_driver_list'=>$this->_get_active_drivers());
      	}
    }

    private function _route_paused(){
         $condition     = "driver_id ='$this->driver_Id' AND shipment_route_id='$this->shipment_route_id'";
		 $status        = $this->modelObj->editContent("shipment_route",array('is_current'=>'N','is_pause'=>'1'),$condition);
         return $status;
    }
	private function _add_shipment_life_history($tickets, $action, $driverid, $routeid, $actionCode,$company_id){
	   return $this->_common_model_obj->addShipmentlifeHistory($tickets, $action, $driverid, $routeid,$company_id,$this->warehouse_id, $actionCode,'controller');
    }

	public function updateWarehouseStatus(){
		$shipment_ticket = explode(",", $this->shipment_ticket);
		$this->ticket_str = implode("','",$shipment_ticket);

        $condition                    = "shipment_ticket IN('$this->ticket_str')";
        $status                       = $this->modelObj->editContent("shipment", array('is_receivedinwarehouse' => $this->warehouse_status, 'warehousereceived_date' => date("Y-m-d H:m:s")), $condition);

        $status                       = $this->modelObj->editContent("shipments_parcel", array('is_receivedinwarehouse' => $this->warehouse_status, 'warehousereceived_date' => date("Y-m-d H:m:s")), $condition);

        if($this->warehouse_status == 'NO'){
			$actions     = 'Not Received in Warehouse';
        	$actionsCode = 'NOTRECEIVEDINWAREHOUSE';
		} else{
			$actions     = 'Received in Warehouse';
        	$actionsCode = 'RECEIVEDINWAREHOUSE';
		}
		foreach($shipment_ticket as $ticket){
			$this->_add_shipment_life_history($ticket, $actions, '0', '0', $actionsCode,$this->company_id);
		}

		return array('status'=>true, 'message'=>"Warehouse status updated successfully");
	}

    public function inWareHouseShipmentsDetails(){
		$allShipTickets = '"' .$this->shipment_ticket. '"';
		$shipRoute_id 	= $this->shipment_route_id;
		$company_id 	= $this->company_id;
		$warehouse_status 	= ($this->warehouse_status=='YES')?'YES':'NO';
		$cargoshipment                           = array();
        $cargoshipment['is_receivedinwarehouse'] = $warehouse_status;
        $cargoshipment['warehousereceived_date'] = date("Y-m-d H:m:s");
        $condition_for_ship                      = "shipment_ticket IN(" . $allShipTickets . ") AND company_id = $company_id AND shipment_routed_id = $shipRoute_id";
		$condition_for_parcel                    = "shipment_ticket IN(" . $allShipTickets . ") AND company_id = $company_id ";
        $status                                  = $this->modelObj->editContent("shipment", $cargoshipment, $condition_for_ship);

        $numaffected = $this->modelObj->getAffectedRows();

		$statusparcel                            = $this->modelObj->editContent("shipments_parcel", $cargoshipment, $condition_for_parcel);
        $alltickets                              = explode(",", $allShipTickets);
        foreach ($alltickets as $valticket) {
            $tickets     = str_replace('"', '', $valticket);
            $shipdetails = $this->modelObj->getShipmentStatusDetails($valticket);
            $actions     = ($warehouse_status == 'NO') ? 'Not Received in Warehouse' : 'Received in Warehouse';
            $actionsCode = ($warehouse_status == 'NO') ? 'NOTRECEIVEDINWAREHOUSE' : 'RECEIVEDINWAREHOUSE';
            $this->_add_shipment_life_history($valticket, $actions, $shipdetails['assigned_driver'], $shipdetails['shipment_routed_id'], $actionsCode,$company_id);
       }
		return array('message'=>'total number of affected shipments: '.$warehouse_status .'=>'.$numaffected,'status'=>true);
  }

    public function isDriverPickup(){
		$allShipTickets = '"' .$this->shipment_ticket. '"';
		$shipRoute_id 	= $this->shipment_route_id;
		$company_id 	= $this->company_id;
		$pickup_status 	= ($this->pickup_status=='YES')?'YES':'NO';
	$checkAllshipmentinWareHouse = $this->modelObj->getCheckAllshipmentinWareHouse($allShipTickets);
      if ($checkAllshipmentinWareHouse == 0) {
            $cargoshipment                                 = array();
            $cargoshipment['is_driverpickupfromwarehouse'] = $pickup_status;
            $cargoshipment['driver_pickuptime']            = date("Y-m-d H:m:s");
            $condition_for_ship                            = "shipment_ticket IN(" . $allShipTickets . ") AND company_id = $company_id AND shipment_routed_id = $shipRoute_id";
		    $condition_for_parcel                          = "shipment_ticket IN(" . $allShipTickets . ") AND company_id = $company_id ";
		    $status                                  	   = $this->modelObj->editContent("shipment", $cargoshipment, $condition_for_ship);
            $num_affected 								   = $this->modelObj->getAffectedRows();
		    $statusParcel                                  = $this->modelObj->editContent("shipments_parcel", $cargoshipment, $condition_for_parcel);
            $getshipmentDetails                            = $this->modelObj->getOperationalShipmentDetails($allShipTickets);


            $driverid                                      = $getshipmentDetails[0]['assigned_driver'];
            $routeid                                       = $getshipmentDetails[0]['shipment_routed_id'];
            $alltickets                                    = explode(",", $allShipTickets);
            foreach ($alltickets as $valticket) {
              //$tickets     = str_replace('"', '', $valticket);
              $actions     = ($pickup_status == 'NO') ? 'Not Received by Driver' : 'Received by Driver';
              $actionsCode = ($pickup_status == 'NO') ? 'NOTRECEIVEDBYDRIVER' : 'RECEIVEDBYDRIVER';
              $this->_add_shipment_life_history($valticket, $actions, $driverid, $routeid, $actionsCode,$company_id);
            }
            return array('status'=>true,'message'=>'total shipment affected : '.$pickup_status .'=>'. $num_affected);
            } else {
             return array(
                'status' => false,
                'message' => 'Shipment is not physically received in warehouse yet'
            );
           }
	 }

    public function routeaccept(){
        $shipRoute_id 	= $this->shipment_route_id;
        $driverid 	    = $this->driver_Id;
        $company_id 	= $this->company_id;
        $driverUsername = $this->driver_name;
        $allShipTickets = '"' .$this->shipment_ticket. '"';

        $fbObj = new Firebase_Route_Accept(array("shipment_route_id"=>$this->shipment_route_id,"driver_id"=>$this->driver_Id));

        if($this->addDriverTacking($driverUsername, $driverid,'ACCEPT',$shipRoute_id)){
            $alltickets = explode(",", $allShipTickets);
            $countdata = 0;
            foreach ($alltickets as $valticket) {
                $ticket    	   = str_replace('"', '', $valticket);
                $condition     = "shipment_ticket = '" . $ticket . "' AND company_id = '" . $company_id . "'";
                $status        = $this->modelObj->editContent("shipment", array('is_driver_accept' => 'YES','is_receivedinwarehouse'=>'YES'), $condition);
                $condition2    = "shipment_accepted='Pending' AND shipment_route_id = '" . $shipRoute_id . "' AND driver_id = '" . $driverid . "'  AND shipment_ticket = '" . $ticket . "'";
                $status2       = $this->modelObj->editContent("driver_shipment", array('shipment_accepted' => 'YES','taken_action_by' => 'Controller'
                ), $condition2);

                $numaffected = $this->modelObj->getAffectedRows();
                $countdata+=$numaffected;
                $actions     = "Shipment ACCEPT by " . $driverUsername;
                $actionsCode = 'ACCEPTBYDRIVER';
                $this->_add_shipment_life_history($ticket, $actions, $driverid, $shipRoute_id, $actionsCode,$company_id );
            }
            $conditionofRoute    = "shipment_route_id = '" . $shipRoute_id . "' AND driver_id = '" . $driverid . "'";
            $status3        = $this->modelObj->editContent("shipment_route", array('driver_accepted' => '1'), $conditionofRoute);

            return array('message'=>'Route has been accepted with Total '.$countdata.' '.$actions,'status'=>true,"firebase_data"=>$fbObj->acceptRoute(),"records"=>$this->loadAssignedView());
        }else{
            return array("status"=>"error", "message"=>"Route not accepted. Please try after few minutes.");
        }
    }

    public function acceptRejectShipments(){
    	$shipRoute_id 	= $this->shipment_route_id;
    	$route_data     = $this->modelObj->_get_assigned_route_detail($shipRoute_id);
    	$driverid 	    = $route_data['driver_id'];
    	$driverUsername = $route_data['name'];
        $company_id 	= $this->company_id;
    	$acceptStatus 	=  $this->accept_status;

    	$allShipTickets = '"' .$this->shipment_ticket. '"';
    	$alltickets = explode(",", $allShipTickets);
    	$countdata = 0;$numaffected=0;
    	foreach ($alltickets as $valticket) {
    		 $ticket    	= str_replace('"', '', $valticket);
    		 $condition     = "shipment_ticket = '" . $ticket . "' AND company_id = '" . $company_id . "'";
    		 $status        = $this->modelObj->editContent("shipment", array('is_driver_accept' => $acceptStatus), $condition);
    		 $condition2    = "shipment_route_id = '" . $shipRoute_id . "' AND driver_id = '" . $driverid . "'  AND shipment_ticket = '" . $ticket . "'";
    		 $status2        = $this->modelObj->editContent("driver_shipment", array('shipment_accepted' => $acceptStatus,'taken_action_by' => 'Controller'
    		  ), $condition2);
    		 $numaffected = $this->modelObj->getAffectedRows();
    		 $countdata+=$numaffected;
    		 $actions =  ($acceptStatus =='YES')?"Shipment ACCEPT by " . $driverUsername:"Shipment REJECT by " . $driverUsername;
    		 $actionsCode = ($acceptStatus =='YES')?'ACCEPTBYDRIVER':'REJECTBYDRIVER';
    	     $this->_add_shipment_life_history($valticket, $actions, $driverid, $shipRoute_id, $actionsCode,$company_id );
       }
       return array('message'=>'Total '.$countdata.' '.$actions,'status'=>true);
    }

    public function pickup_by_driver(){
    	$shipRoute_id 	= $this->shipment_route_id;
    	$route_data     = $this->modelObj->_get_assigned_route_detail($shipRoute_id);
    	$driverid 	    = $route_data['driver_id'];
    	$driverUsername = $route_data['name'];
        $company_id 	= $this->company_id;
    	$acceptStatus 	=  $this->accept_status;
    	$allShipTickets = '"' .$this->shipment_ticket. '"';
    	$alltickets = explode(",", $allShipTickets);
    	$countdata = 0;$numaffected=0;
    	foreach ($alltickets as $valticket) {
            $ticket    	= str_replace('"', '', $valticket);
            $condition     = "shipment_ticket = '" . $ticket . "' AND company_id = '" . $company_id . "'";
            $status        = $this->modelObj->editContent("shipment", array('is_driverpickupfromwarehouse' => $acceptStatus,'driver_pickuptime'=>date("Y-m-d H:m:s")), $condition);
            $status        = $this->modelObj->editContent("shipments_parcel", array('is_driverpickupfromwarehouse' => $statusParcel,'driver_pickuptime'=>date("Y-m-d H:m:s")), $condition);
            $numaffected = $this->modelObj->getAffectedRows();
            $countdata+=$numaffected;
            $actions =  ($acceptStatus =='YES')?"Received by Driver":"Not Received by Driver";
            $actionsCode = ($acceptStatus =='YES')?'RECEIVEDBYDRIVER':'NOTRECEIVEDBYDRIVER';
            $this->_add_shipment_life_history($valticket, $actions, $driverid, $shipRoute_id, $actionsCode,$company_id);
        }
        return array('message'=>'Total '.$countdata.' '.$actions,'status'=>true);
    }

    public function addDriverTacking($driveruserName, $driverid, $for, $shipment_route_id){
	    try{
            $trackid = '';
            $data                 = array();
            $data['drivercode']   = $driveruserName;
            $data['driver_id']    = $driverid;
            //$data['date']         = date("Y-m-d");
            //$data['time']         = date("H:m:s");
            $data['route_id']     = $shipment_route_id;
            $data['for']          = $for;
            $data['status']       = '1';
            $trackid              = $this->modelObj->addContent("api_driver_tracking", $data);
            return $trackid;
        }catch(Exception $e){
            return false;
        }
    }

    public function withdrawroute(){
		$shipment_route_id 	= $this->shipment_route_id;
		$route_data         = $this->modelObj->_get_assigned_route_detail($shipment_route_id);
        $firebase_post_id   = $route_data['firebase_id'];
		$driverid 	        = $route_data['driver_id'];
        $firebasedata       = array();
		$driverUsername     = $route_data['name'];
		$company_id 	    = $this->company_id;
		$ticketids          = '"' .$this->shipment_ticket. '"';
        $checkMoreShipmentofthisRoute = 0;

        $firebaseObj = new Firebase_Shipment_Withdraw_From_Route(array("shipmet_tickets"=>explode(",",str_replace('"','',$this->shipment_ticket)), "driver_id"=>$driverid, "shipment_route_id"=>$shipment_route_id,'get_drop_from'=>"shipment_ticket_after_carded"));

		$firebaseData = $firebaseObj->getShipmentFromRoute();
		//$firebase_profile = $temp_firebase_data['firebase_profile'];
		//unset($temp_firebase_data['firebase_profile']);
		//$firebase_data = $temp_firebase_data;

		//$firebase_data = array('firebase_data'=>$firebase_data,'firebase_profile'=>$firebase_profile,'firebase_post_id'=>$firebase_post_id);

        $releseShipment                             = array();
        $releseShipment['is_shipment_routed']       = '0';
        $releseShipment['shipment_routed_id']       = '0';
        $releseShipment['is_driver_assigned']       = '0';
        $releseShipment['is_driver_accept']         = 'Pending';
        $releseShipment['assigned_driver']          = '0';
        $releseShipment['assigned_vehicle']         = '0';
        $releseShipment['current_status']           = 'C';
        $releseShipment['distancemiles']            = '0.00';
        $releseShipment['estimatedtime']            = '0.00';
        $releseShipment['icargo_execution_order']   = '0';
        $condition                                  = "shipment_ticket IN(" . $ticketids . ")";
        $status                                     = $this->modelObj->editContent("shipment", $releseShipment, $condition);
		$numaffected = $this->modelObj->getAffectedRows();
        if($driverid>0  and $numaffected>0){
			$driverShipment                             = array();
			$driverShipment['shipment_accepted']        = 'Release';
			$driverShipment['is_driveraction_complete'] = 'Y';
			$status                                     = $this->modelObj->editContent("driver_shipment", $driverShipment, $condition);

            $alltickets                                 = explode(",", $ticketids);
		    foreach ($alltickets as $valticket) {
				$tickets = str_replace('"', '', $valticket);
				$actions     = "Relese from Driver";
				$actionsCode = 'RELEASEFROMDRIVER';
				$this->_add_shipment_life_history($valticket, $actions, $driverid, $shipment_route_id, $actionsCode,$company_id );
			 }
             $checkMoreShipmentofthisRoute = $this->modelObj->moreShipExistinThisRouteforDriverFromOperation($driverid, $shipment_route_id);

            if ($checkMoreShipmentofthisRoute == '0') {
                $condition = "shipment_route_id = '" . $shipment_route_id . "' AND driver_id = '" . $driverid . "'";
                 $status    = $this->modelObj->editContent("shipment_route", array('is_active' => 'N'), $condition);
             }
           }else{
               if($driverid==0  and $numaffected>0){

                   $alltickets = explode(",", $ticketids);
			       foreach ($alltickets as $valticket) {
					   $tickets = str_replace('"', '', $valticket);
					   $actions     = "Relese from Saved Route";
					   $actionsCode = 'RELEASEFROMSAVEDROUTE';
					   $this->_add_shipment_life_history($valticket, $actions, $driverid, $shipment_route_id, $actionsCode,$company_id );
				   }

             $checkMoreShipmentofthisRoute = $this->modelObj->moreShipExistinThisSavedRoute($shipment_route_id);
		     if ($checkMoreShipmentofthisRoute == '0') {
                 $condition = "shipment_route_id = '" . $shipment_route_id . "' AND driver_id = '0'";
                 $status    = $this->modelObj->editContent("shipment_route", array('is_active' => 'N'), $condition);
             }
           }
        }
	    $returnstatus = ($numaffected>0) ? "success" : "error";

        if($returnstatus == "success"){
            $fbData = $firebaseObj->withdrawShipments($firebaseData);
            if($fbData["jobCount"]==0){
                $completeRouteObj = new Route_Complete(array('shipment_route_id'=>$shipment_route_id,'company_id'=>$company_id,'email'=>$this->primary_email,'access_token'=>$this->access_token));
                $completeRouteObj->saveCompletedRoute();
            }
            return array('status' => $returnstatus,'message' => 'Total '.$numaffected.' Shipment has been released, left shipment(s) '.$checkMoreShipmentofthisRoute,"job_remaining"=>$fbData["jobCount"]);
        }
		return array('status' => $returnstatus,'message' => 'Total '.$numaffected.' Shipment has been released, left shipment(s) '.$checkMoreShipmentofthisRoute);
    }



    public function deleteroute(){
		$shipment_route_id 	= $this->shipment_route_id;
		$company_id 	    = $this->company_id;
		$ticketids          = '"' .$this->shipment_ticket. '"';
        $releseShipment                             = array();
        $releseShipment['is_shipment_routed']       = '0';
        $releseShipment['shipment_routed_id']       = '0';
        $releseShipment['is_driver_assigned']       = '0';
        $releseShipment['is_driver_accept']         = 'Pending';
        $releseShipment['assigned_driver']          = '0';
        $releseShipment['assigned_vehicle']         = '0';
        $releseShipment['current_status']           = 'C';
        $releseShipment['distancemiles']            = '0.00';
        $releseShipment['estimatedtime']            = '0.00';
        $releseShipment['icargo_execution_order']   = '0';
        $condition                                  = "shipment_ticket IN(" . $ticketids . ")";
        $status                                     = $this->modelObj->editContent("shipment", $releseShipment, $condition);
		$numaffected = $this->modelObj->getAffectedRows();

        if($numaffected>0){
             $alltickets = explode(",", $ticketids);
			   foreach ($alltickets as $valticket) {
				  $tickets = str_replace('"', '', $valticket);
				  $actions     = "Relese from Saved Route";
				  $actionsCode = 'RELEASEFROMSAVEDROUTE';
				  $this->_add_shipment_life_history($valticket, $actions, 0, $shipment_route_id, $actionsCode,$company_id );
				}
             $checkMoreShipmentofthisRoute = $this->modelObj->moreShipExistinThisSavedRoute($shipment_route_id);
		     if ($checkMoreShipmentofthisRoute == '0') {
                 $condition = "shipment_route_id = '" . $shipment_route_id . "' AND driver_id = '0'";
                 $status    = $this->modelObj->editContent("shipment_route", array('is_active' => 'N'), $condition);
                 $msg = "route has been deleted";
             }else{
                 $msg =  'Total '.$numaffected.' Shipment has been released, left shipment(s) '.$checkMoreShipmentofthisRoute;
             }
       }
	  $returnstatus = ($numaffected>0)?true:false;
		return array('status' => $returnstatus,'message' =>$msg,'left'=>$checkMoreShipmentofthisRoute);
    }

    public function withdrawrouteandsave(){
		$this->modelObj->startTransaction();

		if(!is_array($this->shipment_ticket)){
           $this->shipment_ticket = explode(",", str_replace("\",\"",",",$this->shipment_ticket));
		}
        $checkMoreShipmentofthisRouteDriver = 0;
		$shipment_route_id 	= $this->shipment_route_id;
		$route_data         = $this->modelObj->_get_assigned_route_detail($this->shipment_route_id);
		$driverid 	        = $route_data['driver_id'];
		$driverUsername     = $route_data['name'];
		$company_id 	    = $this->company_id;

		$status = $this->modelObj->releaseShipment(implode("','",$this->shipment_ticket));

        $numaffected = $this->modelObj->getAffectedRows();

        $returnstatus = ($numaffected>0) ? "success" : "error";

        if($driverid>0  and $numaffected>0){
            $status = $this->modelObj->releaseShipmentFromDriver(implode("','",$this->shipment_ticket));

            $firebaseObj        = new Firebase_Withdraw_Route(array("driver_id"=>$driverid, "shipment_route_id"=>$this->shipment_route_id));
            $firebaseObj->withdrawRoute();

            foreach ($this->shipment_ticket as $shipment_ticket)
                $this->_add_shipment_life_history($shipment_ticket, "Relese from Driver", $driverid, $this->shipment_route_id, "RELEASEFROMDRIVER",$company_id);

			$checkMoreShipmentofthisRouteDriver = $this->modelObj->moreShipExistinThisRouteforDriverFromOperation($driverid, $this->shipment_route_id);

			if ($checkMoreShipmentofthisRouteDriver == '0')
                $status = $this->modelObj->releaseShipmentFromRoute($this->shipment_route_id);
		}
        $this->modelObj->commitTransaction();

		return array('status' => $returnstatus,'message' => 'Total '.$numaffected.' Shipment has been released from route, left shipment(s) '.$checkMoreShipmentofthisRouteDriver);
    }

	public function getRouteDetailByID(){
		$shipRoute_id 	= $this->shipment_route_id;
		$route_type 	= $this->route_type;
		$route_data     = $this->modelObj->_get_assigned_route_detail($shipRoute_id);
		$routeLabel     = 'NA';
		$driver_id = 0;
		$driver_username = '';
		$data = array();
		if($route_data['driver_id'] > 0){
		  if($route_data['driver_accepted'] < 1){
			$routeLabel = 'PENDING';
		  }elseif(($route_data['driver_accepted'] > 0) && $route_data['is_route_started'] < 1){
			$routeLabel = 'ACCEPTED';
		  }elseif(($route_data['driver_accepted'] > 0) && $route_data['is_route_started'] > 0){
			$routeLabel = 'ONROUTE';
		  }
		  $driver_id = $route_data['driver_id'];
		  $driver_username = $route_data['name'];
		}
		$data = array('routeLabel'=>$routeLabel,'driver_id'=>$route_data['driver_id'],'driver_username'=>$route_data['name'],'route_type'=>$route_type);
		return $data;
  }

	public function getMoveToOtherRouteAcions(){
        return $this->modelObj->getMoveToOtherRouteAcions($this->company_id);
    }

	public function assignToCurrentRoute(){
        $shipment_tickets = implode("','", $this->shipment_ticket);
		$shipDetails = $this->modelObj->getShipmentDetailsByShipmentTicket($shipment_tickets);

        $lastExecutionOrder = $this->modelObj->getLastDropExecutionOrderOfRoute($this->shipment_route_id);

        $lastExecutionOrder = $lastExecutionOrder["execution_order"];

  		$successBucket = array();
        $failBucket = array();

	    foreach($shipDetails as $ship){
            if($ship['shipment_routed_id']!= $this->shipment_route_id){

                $ship['execution_order'] = ++$lastExecutionOrder;



		        if($ship['current_status']=='C'){

        		    if($this->assignShipment($ship,$this->shipment_route_id,$this->company_id,$this->warehouse_id)){
        				$successBucket[] = $ship['shipment_ticket'];
        			}else{
        			    $failBucket[] = $ship['shipment_ticket'];
        			}
		        }
                elseif($ship['current_status']=='S'){
    			    $this->shipment_ticket = $ship['shipment_ticket'];
    			    $this->shipment_route_id = $ship['shipment_routed_id'];
    			    $return = $this->withdrawroute();
    			    if($return['status']){
    				    if($this->assignShipment($ship,$this->shipment_route_id,$this->company_id,$this->warehouse_id)){
    						$successBucket[] = $ship['shipment_ticket'];
    					}else{
    						$failBucket[] = $ship['shipment_ticket'];
    					}
    				}else{
    				   $failBucket[] = $ship['shipment_ticket'];
    				}
		        }
                elseif($ship['current_status']=='O'){
    			    $this->shipment_ticket = $ship['shipment_ticket'];
    			    $this->shipment_route_id = $ship['shipment_routed_id'];
    			    $return = $this->withdrawroute();
    				if($return['status']){
    				    if($this->assignShipment($ship,$this->shipment_route_id,$this->company_id,$this->warehouse_id)){
    						$successBucket[] = $ship['shipment_ticket'];
    					}else{
    						$failBucket[] = $ship['shipment_ticket'];
    					}
    				}else{
    				   $failBucket[] = $ship['shipment_ticket'];
    				}
        		}else{
        			  $failBucket[] = $ship['shipment_ticket'];
        		}
    		}else{
    		   $failBucket[] = $ship['shipment_ticket'];
    		}
	    }
        $returnStatus = (count($failBucket)>0) ? "error" : "success";

        $routeDetail = $this->modelObj->getShipmentRouteByShipmentRouteId($this->shipment_route_id);
        $firebaseData = array();

        if(count($successBucket)>0){
            $firebaseObj = new Firebase_Route_Assign(array(
                "shipmet_tickets"=>$this->shipment_ticket,
                "driver_id"=>$routeDetail["driver_id"],
                "route_id"=>$this->shipment_route_id,
                "get_drop_from"=>"shipment_ticket")
            );
            $firebaseData = $firebaseObj->getCurrentAssignedShipmentData();
        }
        return array('status'=>$returnStatus,'message'=>count($successBucket).' Job(s) has been assigned to route ' . $routeDetail["route_name"]);
       }

    public function assignShipment($shipmentDetails,$shipment_route_id,$companyid,$warehouseid) {
	    $routeDetails = $this->modelObj->_get_assigned_route_detail($shipment_route_id);
        $shipment_ticket   = $shipmentDetails['shipment_ticket'];
        $driverid          = isset($routeDetails['driver_id'])?$routeDetails['driver_id']:0;
        $vehicalId         = isset($routeDetails['vehicle_id'])?$routeDetails['vehicle_id']:0;



		$insertData    = array(
			'shipment_ticket' => $shipment_ticket,
			'driver_id' => $driverid,
			'vehicle_id' => $vehicalId,
			'shipment_route_id' => $shipment_route_id,
			'assigned_date' => date("Y-m-d"),
			'assigned_time' => date("H:m:s"),
			'create_date' => date("Y-m-d"),
			'execution_order' => isset($shipmentDetails['execution_order'])?$shipmentDetails['execution_order']:0,
			'distancemiles' => isset($shipmentDetails['distancemiles'])?$shipmentDetails['distancemiles']:00.00,
			'estimatedtime' => isset($shipmentDetails['estimatedtime'])?$shipmentDetails['estimatedtime']:00.00);

         $dataTobeUpdate = array();
         if($routeDetails['driver_accepted']==1){
            $dataTobeUpdate['is_driver_accept'] = 'YES';
            $insertData["shipment_accepted"] = 'YES';
         }

         $this->modelObj->addContent("driver_shipment",$insertData);


		 $dataTobeUpdate['is_driver_assigned'] = ($driverid==0)?'0':'1';

		 $dataTobeUpdate['company_id'] = $companyid;
		 $dataTobeUpdate['warehouse_id'] = $warehouseid;
		 $dataTobeUpdate['shipment_assigned_service_time'] = date("H:m:s");
		 $dataTobeUpdate['is_shipment_routed'] = '1';
		 $dataTobeUpdate['assigned_driver'] = $driverid;
		 $dataTobeUpdate['assigned_vehicle'] = $vehicalId;
		 $dataTobeUpdate['current_status'] = ($driverid==0)?'S':'O';
		 $dataTobeUpdate['icargo_execution_order'] = isset($shipmentDetails['execution_order'])?$shipmentDetails['execution_order']:0;
		 $dataTobeUpdate['distancemiles'] = isset($shipmentDetails['distancemiles'])?$shipmentDetails['distancemiles']:00.00;
		 $dataTobeUpdate['estimatedtime'] = isset($shipmentDetails['estimatedtime'])?$shipmentDetails['estimatedtime']:00.00;
		 $dataTobeUpdate['shipment_routed_id'] = $shipment_route_id;
         $dataTobeUpdate['shipment_assigned_service_date'] = (!isset($routeDetails["service_date"])) ? '1970-01-01' : $routeDetails["service_date"];

         if($routeDetails["driver_accepted"]==1){
            $dataTobeUpdate['is_receivedinwarehouse'] = 'YES';
         }

		 $condition        = "shipment_ticket IN('".$shipment_ticket."')";

         $this->modelObj->editContent("shipment",$dataTobeUpdate,$condition);

         $numaffected = $this->modelObj->getAffectedRows();

         if($dataTobeUpdate['current_status']=='S'){
             $actions     = "Assign to Saved Route";
             $actionsCode = 'ASSIGNTOSAVEDROUTE';
         }else{
		     $actions     = "Assign to Driver";
		     $actionsCode = 'ASSIGNTODRIVER';
         }
		 //$shipment_ticket = '"' .$shipment_ticket. '"';

	     $this->_add_shipment_life_history($shipment_ticket, $actions, $driverid, $shipment_route_id, $actionsCode,$companyid );

		 $data =   ($numaffected>0) ?true:false;
         return $data;
    }

    public function _get_active_drivers(){
		$data =  $this->modelObj->getActiveDrivers($this->company_id);
		return $data;
   }

    public function _get_failed_action(){
		$data =  $this->modelObj->getAllowedFailActionsforController($this->company_id);
		return $data;
   }

    public function assignToDriver() {
	    $shipment_route_id = $this->shipment_route_id;
		$driverid          = $this->driver_Id;
		$start_time        = $this->start_time;
		$warehouse_id      = $this->warehouse_id;
		$company_id        = $this->company_id;
		$vehicalDetails    = $this->modelObj->_get_driver_assigned_vehicle($driverid);
        $vehicalId         = $vehicalDetails['vehicle_id'];
        $shipment_ticket   = $this->modelObj->findAllUndeliveredShipmentOfRoute($shipment_route_id);


		$updatedRoute['driver_id']         = $driverid;
        $updatedRoute['assign_start_time'] = date("H:i:s", strtotime($start_time));
        $updatedRoute['is_active']         = 'Y';
        $updatedRoute['status']            = '1';
		$condition        = "shipment_route_id = '".$shipment_route_id."'";
		$this->modelObj->editContent("shipment_route",$updatedRoute,$condition);

		$countdata = 0;
		if(count($shipment_ticket)>0){
			 foreach($shipment_ticket as $value){
			    $drivershipmet                                   = array();
				$drivershipmet['shipment_ticket']                = $value['shipment_ticket'];
				$drivershipmet['driver_id']                      = $driverid;
				$drivershipmet['vehicle_id']                     = $vehicalId;
				$drivershipmet['shipment_route_id']              = $shipment_route_id;
				$drivershipmet['assigned_date']                  = date("Y-m-d");
				$drivershipmet['assigned_time']                  = date("H:m:s");
				$drivershipmet['execution_order']                = $value['execution_order'];
				$drivershipmet['distancemiles']                  = $value['distancemiles'];
				$drivershipmet['estimatedtime']                  = $value['estimatedtime'];
			    $this->modelObj->addContent("driver_shipment", $drivershipmet);

				// Update Cargo shipment
				$cargoshipment                                   = array();
				$cargoshipment['shipment_routed_id']             = $shipment_route_id;
				$cargoshipment['assigned_driver']                = $driverid;
				$cargoshipment['assigned_vehicle']               = $vehicalId;
				$cargoshipment['shipment_assigned_service_date'] = date("Y-m-d");
				$cargoshipment['shipment_assigned_service_time'] = date("H:m:s");
				$cargoshipment['is_shipment_routed']             = '1';
				$cargoshipment['is_driver_assigned']             = '1';
				$cargoshipment['current_status']                 = 'O';
				$cargoshipment['icargo_execution_order']         = $value['execution_order'];
				$cargoshipment['distancemiles']                  = $value['distancemiles'];
				$cargoshipment['estimatedtime']                  = $value['estimatedtime'];
				$condition                                       = "shipment_ticket = '" . $value['shipment_ticket'] . "'";
				$this->modelObj->editContent("shipment", $cargoshipment,$condition);

				$actions     = "Assign to Driver";
				$actionsCode = 'ASSIGNTODRIVER';
				$shipment_ticket = '"' .$value['shipment_ticket']. '"';
				$this->_add_shipment_life_history($shipment_ticket, $actions, $driverid, $shipment_route_id, $actionsCode,$company_id );
				$numaffected = $this->modelObj->getAffectedRows();
		        $countdata+=$numaffected;
		    }
		}
		$data =   ($numaffected>0) ?"success":"error";

        if($data=="success"){
            $firebaseObj = new Firebase_Route_Assign(array("driver_id"=>$driverid,"route_id"=>$shipment_route_id,"warehouse_id"=>$warehouse_id,
                                               "email"=>$this->email,"company_id"=>$company_id));
            $postId = $firebaseObj->getCurrentAssignedRouteData();
            $this->modelObj->editContent("shipment_route",array("firebase_id"=>$postId),"shipment_route_id = '$shipment_route_id'");
            return array(
                'status'  => "success",
                'message' => "Requested Route has been Assigned to driver.",
                'postId'  => $postId
            );
       }else{
        return array('status'=>$data,'message'=>'Total '.count($numaffected).' Shipment has been Assign to driver.');
       }
    }

    public function getAllowFailedAction(){
		$company_id 	= $this->company_id;
		$shipment_ticket 	= $this->shipment_ticket;
		$shipmentStatus = $this->modelObj->findShipmentCurrentStatus($this->shipment_ticket);
		if($shipmentStatus["current_status"]!='D'){
			$data = $this->modelObj->getAllowedFailActionsforController($company_id);
			$driverComment = $this->modelObj->getDriverCommentByTicket($shipment_ticket);
			return array("status"=>"success", "failed_action"=>$data,'driver_comment'=>$driverComment);
		}else{
			return array("status"=>"error", 'message'=>"Shipment already delivered/collected");
		}

	}

    public function getDriverComments(){
		$shipment_ticket 	= $this->shipment_ticket;
		return $this->modelObj->getDriverCommentByTicket($shipment_ticket);
	}

	/*public function getFirebaseDataForCardedShipment(){
		$route_data = $this->modelObj->_get_assigned_route_detail($this->shipment_route_id);
		$driver_id  = $route_data['driver_id'];
		$firebaseObj = new Firebase_Shipment_Withdraw_From_Route(array("shipmet_tickets"=>explode(",",$this->shipment_ticket), "driver_id"=>$driver_id, "shipment_route_id"=>$this->shipment_route_id,'get_drop_from'=>"shipment_ticket_after_carded"));
        $firebase_data = $firebaseObj->withdrawShipmentFromRoute();
		return array("firebase_data"=>$firebase_data,"firebase_post_id"=>$route_data['firebase_id']);
	}*/

    private

    function _findCollectionShipmentStatusByLoadIdentity($load_identity){
        $allCollectionShipmentCount = $this->modelObj->findAllCollectionShipmentCountByLoadIdentity($load_identity);

        $allCollectedShipmentCount = $this->modelObj->findCollectedShipmentCountByLoadIdentity($load_identity);

        $allCardedShipmentCount = $this->modelObj->findCardedCollectedShipmentCountByLoadIdentity($load_identity);

        $notCollectedCount = $this->modelObj->findNotCollectedShipmentCountByLoadIdentity($load_identity);

        if($allCollectionShipmentCount > 1){
            if($allCollectedShipmentCount > 0 and $allCollectedShipmentCount<$allCollectionShipmentCount){
                //partly collected
                return array("tracking_code"=>"PARTLYCOLLECTED", "actions"=>"partly collected");
            }

            if($allCollectedShipmentCount > 0 and $allCollectedShipmentCount==$allCollectionShipmentCount){
                //collected
                return array("tracking_code"=>"COLLECTIONSUCCESS", "actions"=>"collected");
            }
        }
        elseif($allCollectionShipmentCount == 1){
            if($allCollectedShipmentCount > 0){
                //collected
                return array("tracking_code"=>"COLLECTIONSUCCESS", "actions"=>"collected");
            }

            if($allCollectedShipmentCount==0 and $allCardedShipmentCount>0){
                //collection carded
                return array("tracking_code"=>"RETURNINWAREHOUSE", "actions"=>"collection carded");
            }
        }
        elseif($notCollectedCount==$allCollectionShipmentCount){
            //awaiting collection
            return array("tracking_code"=>"COLLECTIONAWAITED", "actions"=>"collection awaited");
        }else{
            return array();
        }
    }

    private

    function _findDeliveryShipmentStatusByLoadIdentity($load_identity){
        $allDeliveryShipmentCount = $this->modelObj->findAllDeliveryShipmentCountByLoadIdentity($load_identity);

        $allDeliveredShipmentCount = $this->modelObj->findDeliveredShipmentCountByLoadIdentity($load_identity);

        $allCardedShipmentCount = $this->modelObj->findCardedDeliveryShipmentCountByLoadIdentity($load_identity);

        $notDeliveredCount = $this->modelObj->findNotDeliveredShipmentCountByLoadIdentity($load_identity);

        if($allDeliveryShipmentCount > 1){
            if($allDeliveredShipmentCount > 0 and $allDeliveredShipmentCount<$allDeliveryShipmentCount){
                //partly collected
                return array("tracking_code"=>"PARTLYDELIVERED", "actions"=>"partly delivered");
            }

            if($allDeliveredShipmentCount > 0 and $allDeliveredShipmentCount==$allDeliveryShipmentCount){
                //collected
                return array("tracking_code"=>"DELIVERYSUCCESS", "actions"=>"delivered");
            }
        }
        elseif($allDeliveryShipmentCount == 1){
            if($allDeliveredShipmentCount > 0){
                //delivered
                return array("tracking_code"=>"DELIVERYSUCCESS", "actions"=>"delivered");
            }

            if($allDeliveredShipmentCount==0 and $allCardedShipmentCount>0){
                //delivery carded
                return array("tracking_code"=>"RETURNINWAREHOUSE", "actions"=>"delivered carded");
            }
        }
        elseif($notDeliveredCount==$allDeliveryShipmentCount){
            //awaiting collection
            return array("tracking_code"=>"OUTFORDELIVERY", "actions"=>"out for delivery");
        }else{
            return array();
        }
    }

    private

    function _findTrackingStatus($load_identity, $shipment_type){
        if($shipment_type=='VENDOR'){
            return $this->_findDeliveryShipmentStatusByLoadIdentity($load_identity);
        }
        elseif($shipment_type=='SAME'){
            return $this->_findCollectionShipmentStatusByLoadIdentity($load_identity);
        }
    }

    private

    function saveTrackingStatus($tickets){
        //check any collection left
        $ticketStr = implode("','", explode(",", $tickets));

        $loadIdentity = $this->modelObj->findAssignedLoadIdentityByShipmentTicket($ticketStr);

        $temp = array();

        foreach($loadIdentity as $item)
            $temp[$item["load_identity"]] = $item;


        $temp = array_values($temp);

        foreach($temp as $item){
            $status = $this->_findTrackingStatus($item["load_identity"], strtoupper($item["load_type"]));

            if(count($status)>0){
                $common_obj->addShipmentlifeHistory($item["shipment_ticket"], $status["actions"], $item["driver_id"], $item["shipment_route_id"], $item["company_id"], $item["warehouse_id"], $status["tracking_code"], 'controller');
            }
        }
    }

    public function cardedbycontrollerAction($param){
			if (ctype_space($param["date"]) || empty($param["date"])) {
				$param["date"] = date("Y-m-d H:i");
			}
			$date = strtotime($param["date"]);
		$company_id 		= $this->company_id;
		$ticketids 			= '"' .$this->shipment_ticket. '"';

		$comments 			= $param["comment"];

		$nextdate 			= date("Y-m-d", $date);
		$nexttime 			= date("H:i:s", $date);

		//$nextdate 			= $this->next_date;
    $firebasedata       = array();
		//$nexttime 			= $this->next_time;
		$failure_status 	= $this->failure_status;
		$shipment_route_id 	= $this->shipment_route_id;
		$route_data         = $this->modelObj->_get_assigned_route_detail($shipment_route_id);
		$configurationData  = json_decode($this->modelObj->getconfigurationData($company_id),true);

        $this->shipmentAttemptConf      = array(
            'regularattemptconf' => $configurationData['regularattempt'],
            'phoneattemptconf' => $configurationData['phonetypeattempt']
        );
		$driverid 	        = $route_data['driver_id'];
        $firebase_post_id   = $route_data['firebase_id'];

        $firebaseObj = new Firebase_Shipment_Withdraw_From_Route(array("shipmet_tickets"=>explode(",",str_replace('"','',$this->shipment_ticket)), "driver_id"=>$driverid, "shipment_route_id"=>$shipment_route_id,'get_drop_from'=>"shipment_ticket_after_carded"));
        $firebaseData = $firebaseObj->getShipmentFromRoute();

		$getAllTicket       = explode(',',$ticketids);

        foreach ($getAllTicket as $eachTicket) {
			$eachShipmentDetails = $this->modelObj->getShipmentStatusDetails($eachTicket);
			$shipData                                 = array();
            $shipData['shipment_id']                  = $eachShipmentDetails['shipment_id'];
            $shipData['shipment_ticketnumber']        = $eachShipmentDetails['shipment_ticket'];
            $shipData['shipment_status']              = $eachShipmentDetails['current_status'];
            $shipData['create_date']                  = $eachShipmentDetails['shipment_create_date'];
            $shipData['driver_id']                    = $eachShipmentDetails['assigned_driver'];
            $shipData['vehicle_id']                   = $eachShipmentDetails['assigned_vehicle'];
            $shipData['notes']                        = $comments;
            $shipData['controller_choosed_status']    = $failure_status;
            $shipData['driver_choosed_status']        = $eachShipmentDetails['current_status'];
            $shipData['shipment_routed_id']           = $eachShipmentDetails['shipment_routed_id'];
            $shipData['last_assigned_service_date']   = $eachShipmentDetails['shipment_assigned_service_date'];
            $shipData['last_assigned_service_time']   = $eachShipmentDetails['shipment_assigned_service_time'];
            $shipData['actual_given_service_date']    = date("Y-m-d");
            $shipData['actual_given_service_time']    = date("H:m:s");
            $shipData['is_driverpickupfromwarehouse'] = $eachShipmentDetails['is_driverpickupfromwarehouse'];
            $shipData['driver_pickuptime']            = $eachShipmentDetails['driver_pickuptime'];
            $shipData['driver_comment']               = $eachShipmentDetails['driver_comment'];
            $shipData['next_schedule_date']           = date('Y-m-d', strtotime($nextdate));
            $shipData['next_schedule_time']           = $nexttime;
            $shipData['last_shipment_history_id']     = $eachShipmentDetails['last_history_id'];
			$shipData['company_id']                   = $company_id;
            $shipData['status']                       = '1';
			$historyid                                = $this->modelObj->addContent("shipment_history", $shipData);

            $tickets = str_replace('"', '', $eachTicket);
			$actions     = "Carded by Controller";
			$actionsCode = 'CARDEDBYCONTROLLER';
			$this->_add_shipment_life_history($eachTicket, $actions, $driverid, $shipment_route_id, $actionsCode,$company_id );

			$actions     = "Return in warehouse after carded by controller";
			$actionsCode = 'RETURNINWAREHOUSE';
			$this->_add_shipment_life_history($eachTicket, $actions, $driverid, $shipment_route_id, $actionsCode,$company_id );

            $releseShipment                           = array();
			$releseShipment['shipment_total_attempt'] = $eachShipmentDetails['shipment_total_attempt'] + 1;
            if ($eachShipmentDetails['instaDispatch_loadGroupTypeCode'] == 'Vendor') {
                $attempt = $this->shipmentAttemptConf['regularattemptconf'];
                if ($releseShipment['shipment_total_attempt'] >= $attempt) {
                    $releseShipment['current_status'] = 'Rit';
                } else {
                    $releseShipment['current_status'] = 'C';
                }
            } elseif ($eachShipmentDetails['instaDispatch_loadGroupTypeCode'] == 'PHONE') {
                $attempt = $this->shipmentAttemptConf['phoneattemptconf'];
                if ($releseShipment['shipment_total_attempt'] >= $attempt) {
                    $releseShipment['current_status'] = 'Rit';
                } else {
                    $releseShipment['current_status'] = 'C';
                }
            } else {
                $attempt                          = 0;
                $releseShipment['current_status'] = 'Ca';
            }
			if($releseShipment['current_status']!='Ca'){
				$releseShipment['icargo_execution_order']       = '0';
				$releseShipment['is_driverpickupfromwarehouse'] = 'NO';
				$releseShipment['driver_pickuptime']            = '1970-01-01 00:00:00';
				$releseShipment['driver_comment']               = '0';
				$releseShipment['assigned_vehicle']       = '0';
				$releseShipment['is_shipment_routed']     = '0';
				$releseShipment['shipment_routed_id']     = '0';
				$releseShipment['is_driver_assigned']     = '0';
				$releseShipment['is_driver_accept']       = 'Pending';
				$releseShipment['assigned_driver']        = '0';
			}

            $releseShipment['last_history_id']              = $historyid;
            $condition                                      = "shipment_ticket = '" . $tickets . "'";
            $status                                         = $this->modelObj->editContent("shipment", $releseShipment, $condition);
            if($releseShipment['current_status'] == 'Rit') {
              $actions     = "Maximum Attempt achieved, Move to Return Shipment";
			  $actionsCode = 'MAXATTEMPTSREACHED';
			  $this->_add_shipment_life_history($eachTicket	, $actions, $driverid, $shipment_route_id, $actionsCode,$company_id );
            }
            $driverShipment                             = array();
            $driverShipment['is_driveraction_complete'] = 'Y';
            $status     = $this->modelObj->editContent("driver_shipment", $driverShipment, $condition);
        }

			$this->saveTrackingStatus($ticketids/*$getAllTicket*/);
        Find_Save_Tracking::_getInstance()->saveTrackingStatus(array("ticket_str"=>$this->shipment_ticket, "form_code"=>"", "user_type"=>"Controller"));

		$checkMoreShipmentofthisRouteDriver = $this->modelObj->moreShipExistinThisRouteforDriverFromOperation($driverid, $shipment_route_id);
        if ($checkMoreShipmentofthisRouteDriver == 0) {
			 $condition = "shipment_route_id = '" . $shipment_route_id . "' AND driver_id = '" . $driverid . "'";
			 $status    = $this->modelObj->editContent("shipment_route", array('is_active' => 'N','is_pause'=>'0'), $condition);
		}

        $fbData = $firebaseObj->withdrawShipments($firebaseData);

        if($fbData["jobCount"]==0){
            $completeRouteObj = new Route_Complete(array('shipment_route_id'=>$shipment_route_id,'company_id'=>$this->company_id,'email'=>$this->primary_email,'access_token'=>$this->access_token));
            $completeRouteObj->saveCompletedRoute();
        }
		return array('status' => "success",'message' => 'Total '.count($getAllTicket).' carded request has been completed', "job_remaining"=>$fbData["jobCount"]);
	}

    public function deliveredbycontrollerAction($param){
			if (ctype_space($param["date"]) || empty($param["date"])) {
				$param["date"] = date("Y-m-d H:i");
			}

		$date = strtotime($param["date"]);

		$company_id 		= $this->company_id;
		$comments 			= $param["comment"];
		$nextdate 			= date("Y-m-d", $date);
		$nexttime 			= date("H:i:s", $date);
		$contact_name 		= $param["contact_name"];
		$shipment_route_id 	= $this->shipment_route_id;
		$route_data         = $this->modelObj->_get_assigned_route_detail($shipment_route_id);

		$driverid 	        = $route_data['driver_id'];
		$driverUsername		= $route_data['name'];

		$firebase_post_id   = $route_data['firebase_id'];

		$ticketarr       	= explode(',',$this->shipment_ticket);
		$podData['contact'] = $contact_name;
		$podData['comment'] = $comments;
		$servicedate        = $nextdate;
		$servicetime        = $nexttime;
		$lattitude          = '0.00000000';
		$longitude          = '0.00000000';
		$statusarr          = array();

        if (count($ticketarr) > 0) {
            $firebaseObj = new Firebase_Shipment_Deliver_From_Route(array("shipmet_tickets"=>explode(",",str_replace('"','',$this->shipment_ticket)), "driver_id"=>$driverid, "shipment_route_id"=>$shipment_route_id,'get_drop_from'=>"shipment_ticket"));
            $fbData = $firebaseObj->getShipmentFromRoute();

			foreach ($ticketarr as $ticket) {
				$statusdata = $this->deliveredShipment('"'.$ticket.'"', $driverid, $shipment_route_id, $driverUsername, $podData, $lattitude, $longitude, $servicedate, $servicetime,$company_id,'controller');
				array_push($statusarr, $statusdata);
			}
			Find_Save_Tracking::_getInstance()->saveTrackingStatus(array("ticket_str"=>$this->shipment_ticket, "form_code"=>"", "user_type"=>"Controller"));
			$fbStatus = $firebaseObj->DeliverShipments($fbData);
            if($fbStatus["jobCount"]==0){
                $completeRouteObj = new Route_Complete(array('shipment_route_id'=>$shipment_route_id,'company_id'=>$this->company_id,'email'=>$this->primary_email,'access_token'=>$this->access_token));
                $completeRouteObj->saveCompletedRoute();
            }
			return	array('message' => 'All shipment are Delivered for Driver(' . $driverUsername . ')','status' => "success", "job_remaining"=>$fbStatus["jobCount"]);
		}
    }

    public function deliveredShipment($eachTicket, $driverid, $shipment_route_id, $driverName, $podData, $lattitude, $longitude, $servicedate, $servicetime,$company_id, $user_type='driver'){
        if(($eachTicket != '') && ($driverid != '') && ($shipment_route_id != '')) {
    	    $ticket = str_replace('"', '', $eachTicket);
    	    $getshipmentDetails = $this->modelObj->getShipmentStatusDetails($eachTicket);
    	    $condition = "shipment_ticket = '" . $ticket . "'";
            $status    = $this->modelObj->editContent("shipment", array(
                'actual_given_service_date' => $servicedate,
                'actual_given_service_time' => $servicetime,
                'current_status' => 'D',
                'action_by' => ($user_type=='driver') ? 'driver' : 'controller'
            ), $condition);

    	    if ($getshipmentDetails['shipment_service_type'] == 'P' && $getshipmentDetails['instaDispatch_loadGroupTypeCode'] == 'SAME') {
                $conditionsameDay     = "instaDispatch_docketNumber = '" . $getshipmentDetails['instaDispatch_docketNumber'] . "'";
                $statussamedayNextDay = $this->modelObj->editContent("shipment", array(
                    'is_driverpickupfromwarehouse' => 'YES'
                ), $conditionsameDay);
            }
            $condition2 = "shipment_route_id = '" . $shipment_route_id . "'
    						  AND driver_id = '" . $driverid . "'
    						  AND shipment_ticket = '" . $ticket . "' AND shipment_accepted = 'YES' ";

            $status2    = $this->modelObj->editContent("driver_shipment", array(
                'shipment_status' => 'D',
                'is_driveraction_complete' => 'Y',
                'service_date' => date("Y-m-d"),
                'service_time' => date("H:m:s")
            ), $condition2);


            $getshipmentDetails 									= $this->modelObj->getShipmentStatusDetails($eachTicket);
    		$driverDelivered                                        = array();
            $driverDelivered['shipment_ticket']                     = $ticket;
            $driverDelivered['instaDispatch_docketNumber']          = $getshipmentDetails['instaDispatch_docketNumber'];
            $driverDelivered['instaDispatch_loadIdentity']          = $getshipmentDetails['instaDispatch_loadIdentity'];
            $driverDelivered['instaDispatch_jobIdentity']           = $getshipmentDetails['instaDispatch_jobIdentity'];
            $driverDelivered['instaDispatch_objectIdentity']        = $getshipmentDetails['instaDispatch_objectIdentity'];
            $driverDelivered['instaDispatch_objectTypeName']        = $getshipmentDetails['instaDispatch_objectTypeName'];
            $driverDelivered['shipment_service_type']               = $getshipmentDetails['shipment_service_type'];
            $driverDelivered['shipment_required_service_date']      = $getshipmentDetails['shipment_required_service_date'];
            $driverDelivered['shipment_required_service_starttime'] = $getshipmentDetails['shipment_required_service_starttime'];
            $driverDelivered['shipment_required_service_endtime']   = $getshipmentDetails['shipment_required_service_endtime'];
            $driverDelivered['shipment_assigned_service_date']      = $getshipmentDetails['shipment_assigned_service_date'];
            $driverDelivered['shipment_assigned_service_time']      = $getshipmentDetails['shipment_assigned_service_time'];
            $driverDelivered['actual_given_service_date']           = $servicedate;
            $driverDelivered['actual_given_service_time']           = $servicetime;
            $driverDelivered['shipment_latlong']                    = $getshipmentDetails['shipment_latlong'];
            $driverDelivered['shipment_create_date']                = $getshipmentDetails['shipment_create_date'];
            $driverDelivered['shipment_total_attempt']              = $getshipmentDetails['shipment_total_attempt'];
            $driverDelivered['shipment_routed_id']                  = $getshipmentDetails['shipment_routed_id'];
            $driverDelivered['shipment_pod']                        = $getshipmentDetails['shipment_pod'];
            $driverDelivered['assigned_driver']                     = $getshipmentDetails['assigned_driver'];
            $driverDelivered['assigned_vehicle']                    = $getshipmentDetails['assigned_vehicle'];
    		$driverDelivered['company_id']                    		= $getshipmentDetails['company_id'];
    		$driverDelivered['warehouse_id']                    	= $getshipmentDetails['warehouse_id'];
            $driverDelivered['dataof']                              = $getshipmentDetails['dataof'];
            $driverDelivered['search_string']                       = $getshipmentDetails['search_string'];
            $driverDeliveryid                                       = $this->modelObj->addContent("delivered_shipments", $driverDelivered);

		    $actions     = "Shipment Delivered By Driver";
		    $actionsCode = 'DELIVEREDBYDRIVER';
	        $this->_add_shipment_life_history($eachTicket, $actions, $driverid, $shipment_route_id, $actionsCode,$company_id );

		    if($podData != NULL and is_array($podData)) {
                $contactName = ($podData['contact'] != '') ? $podData['contact'] : '';
                $comment     = ($podData['comment'] != '') ? $podData['comment'] : '';
                if (($contactName != '') || ($comment != '')) {
                    $poddata                            = array();
                    $poddata['shipment_ticket']         = $ticket;
                    $poddata['driver_id']               = $driverid;
                    $poddata['type']                    = 'text';
                    $poddata['value']                   = 'text';


                    $poddata['comment']        = $comment;
                    $poddata['contact_person'] = $contactName;
                    //$poddata['date']                    = date("Y-m-d");
                    //$poddata['time']                    = date("H:m:s");
                    $poddata['status']                  = '1';
                    $poddata['latitude']                = $lattitude;
                    $poddata['longitude']               = $longitude;
                    $poddataid                          = $this->modelObj->addContent("shipments_pod", $poddata);
                }
			}
            $checkMoreShipmentofthisRouteDriver = $this->modelObj->moreShipExistinThisRouteforDriverFromOperation($driverid, $shipment_route_id);
			if ($checkMoreShipmentofthisRouteDriver == '0') {
				$condition = "shipment_route_id = '" . $shipment_route_id . "' AND driver_id = '" . $driverid . "'";
				$status    = $this->modelObj->editContent("shipment_route", array('is_active' => 'N'), $condition);
			}
			return array('status' => true,'message' => 'delivery request has been completed');
		}
    }

    public function inWareHouseCollectionShipments(){
			$company_id 		= $this->company_id;
			$ticketids 			= '"' .$this->shipment_ticket. '"';
			$allShipmentDetails = $this->modelObj->getAllShipmentDetailsByTicket($ticketids);
			$shipment_route_id 	= $this->shipment_route_id;
			$flage = array();
			foreach($allShipmentDetails as $shipdata){
				$ship_type = $shipdata['shipment_service_type'];
				$ship_current_status = $shipdata['current_status'];
				if($ship_type=='P' and $ship_current_status == 'D'){
				  $flage[] = 'true';
				  $conditionCollection    = "instaDispatch_docketNumber = '" . $shipdata['instaDispatch_docketNumber'] . "'";
				  $warehouseRecived       = $this->modelObj->editContent("shipment", array('is_receivedinwarehouse' => 'YES','warehousereceived_date' => date('Y-m-d')
                  ), $conditionCollection);
				}else{
					$flage[] = 'false';
				}
		    }
		     $countdata = array_count_values($flage);
			 if(in_array('true',$flage)){
			    return array('status' => true,'message' => 'selected shipment successfully received in warehouse');
			 }else{
				$countdata['true'] = isset($countdata['true'])?$countdata['true']:0;
				$countdata['false'] = isset($countdata['false'])?$countdata['false']:0;
			    return array('status' => false,'message' => 'Total '.$countdata['true'].' shipment successfully received in warehouse, and '.$countdata['false'].' shipment(s) has been failed please select only collection shipment(s) that are collected');
			 }
		}

    public function pickupbycontrollerAction(){
        $allShipTickets      = '"' .$this->shipment_ticket. '"';
		$shipRoute_id 	     = $this->shipment_route_id;
		$company_id 	     = $this->company_id;
		$warehouse_status 	 = ($this->warehouse_status=='YES')?'YES':'NO';
        $route_data          = $this->modelObj->_get_assigned_route_detail($shipRoute_id);
		$driverid 	         = $route_data['driver_id'];
        $firebase_post_id   = $route_data['firebase_id'];

        $firebaseObj = new Firebase_Shipment_Withdraw_From_Route(array("shipmet_tickets"=>explode(",",str_replace('"','',$this->shipment_ticket)), "driver_id"=>$driverid, "shipment_route_id"=>$shipment_route_id,'get_drop_from'=>"shipment_ticket"));
        $firebase_data = $firebaseObj->withdrawShipmentFromRoute();


		$cargoshipment                           = array();
        $cargoshipment['is_receivedinwarehouse'] = $warehouse_status;
        $cargoshipment['warehousereceived_date'] = date("Y-m-d H:m:s");
        $condition_for_ship                      = "shipment_ticket IN(" . $allShipTickets . ") AND company_id = $company_id AND shipment_routed_id = $shipRoute_id";
		$condition_for_parcel                    = "shipment_ticket IN(" . $allShipTickets . ") AND company_id = $company_id ";
        $status                                  = $this->modelObj->editContent("shipment", $cargoshipment, $condition_for_ship);

        $numaffected = $this->modelObj->getAffectedRows();

		$statusparcel                            = $this->modelObj->editContent("shipments_parcel", $cargoshipment, $condition_for_parcel);
        $alltickets                              = explode(",", $allShipTickets);
        foreach ($alltickets as $valticket) {
            $tickets     = str_replace('"', '', $valticket);
            $shipdetails = $this->modelObj->getShipmentStatusDetails($valticket);
            $actions     = ($warehouse_status == 'NO') ? 'Not Received in Warehouse' : 'Received in Warehouse';
            $actionsCode = ($warehouse_status == 'NO') ? 'NOTRECEIVEDINWAREHOUSE' : 'RECEIVEDINWAREHOUSE';
            $this->_add_shipment_life_history($valticket, $actions, $shipdetails['assigned_driver'], $shipdetails['shipment_routed_id'], $actionsCode,$company_id);
       }

       $checkMoreShipmentofthisRouteDriver = $this->modelObj->moreShipExistinThisRouteforDriverFromOperation($driverid, $shipRoute_id);
        if ($checkMoreShipmentofthisRouteDriver == '0') {
			$condition = "shipment_route_id = '" . $shipRoute_id . "' AND driver_id = '" . $driverid . "'";
			 $status    = $this->modelObj->editContent("shipment_route", array('is_active' => 'N'), $condition);
		     $firebasedata = $this->releaseRoute($driverid, $shipRoute_id);
        }
		return array('status' => true,'message' => 'Total '.count($alltickets).' shipment(s) successfully received in warehouse','left'=>$checkMoreShipmentofthisRouteDriver,'firebase_data'=>$firebase_data,'firebase_post_id'=>$firebase_post_id,'left'=>$checkMoreShipmentofthisRouteDriver);
     }

   private function releaseRoute($driverid, $shipRoute_id){
       $param  = array('driver_id'=>$driverid,'route_id'=>$shipRoute_id);
       $fbObj = new Firebase_Route_Release($param);
       $fbdata = $fbObj->getrelasedata();
       return $fbdata;
   }

   public function getShipmentCount(){
		$company_id 	= $this->company_id;
		$warehouse_id 	= $this->warehouse_id;
		$data = $this->modelObj->getShipmentCountsDb($company_id,$warehouse_id);
        $return = array('SAME'=>0,'NEXT'=>0);
        if(count($data)>0){foreach($data as $val){
           if($val['shiptype']=='SAME'){
             $return['SAME'] = $val['num'];
           }else{
            $return['NEXT']+= $val['num'];
           }
        }}
      return $return;
	}

    public function updateShipments(){
        $postData  =  array();
        $postData['shipment_address1'] = $this->shipment_address1;
        $postData['shipment_address2'] = $this->shipment_address2;
        $postData['shipment_address3'] = $this->shipment_address3;
        $postData['shipment_postcode'] = $this->shipment_postcode;

        $prevUpdateData = $this->modelObj->getShipmentDetails("'" . $this->shipment_ticket . "'");
             if($postData['shipment_postcode'] != '') {
                if($prevUpdateData[0]['shipment_postcode']!=$postData['shipment_postcode']){
                  $postCodeStatus = $this->checkUkPostcodeettern($postData['shipment_postcode']);
                     if ($postCodeStatus == 'pass') {
                          $shipment_latlong = $this->get_lat_longbyPostcode($postData['shipment_postcode'], "", "");
                          $updateShipment = $this->modelObj->editContent("shipment", array(
                           'shipment_latlong' => $shipment_latlong,
                           'shipment_address1' => $postData['shipment_address1'],
                           'shipment_address2' => $postData['shipment_address2'],
                           'shipment_address3' => $postData['shipment_address3'],
                           'shipment_postcode' => $postData['shipment_postcode']
                        ), "shipment_ticket='" . $this->shipment_ticket . "'");
                         $numaffected = $this->modelObj->getAffectedRows();
                    }else{
                         return array('message'=>'Please enter a valid postcode','status'=>true);
                 }
                }else{
                $updateShipment = $this->modelObj->editContent("shipment", array(
                           'shipment_address1' => $postData['shipment_address1'],
                           'shipment_address2' => $postData['shipment_address2'],
                           'shipment_address3' => $postData['shipment_address3']
                        ), "shipment_ticket='" . $this->shipment_ticket. "'");
                $numaffected = $this->modelObj->getAffectedRows();
            }


               // if($updateShipment) {
            if($numaffected > 0) {
                $prevAddress = $prevUpdateData[0]['shipment_address1'] . ',' . $prevUpdateData[0]['shipment_address2'] . ',' . $prevUpdateData[0]['shipment_address3'] . ',' . $prevUpdateData[0]['shipment_postcode'];

                $newAddress  = $postData['shipment_address1'] . ',' . $postData['shipment_address2'] . ',' . $postData['shipment_address3'] . ',' . $postData['shipment_postcode'];


            $actions             = 'Shipment Address Updated By Controller(' . $prevAddress . ' to ' . $newAddress . ')';
            $actionsCode         = 'ADDRESSUPDATE';
			$driverid            = $prevUpdateData[0]['assigned_driver'];
            $shipment_route_id   = $prevUpdateData[0]['shipment_routed_id'];
            $this->_add_shipment_life_history($this->shipment_ticket, $actions, $driverid, $shipment_route_id, $actionsCode,$this->company_id );

            return array('message'=>'Shipment Address has been Updated By Controller','status'=>'success');
            }else{
              return array('message'=>'Shipment Address has <b>Not</b> been Updated By Controller','status'=>'error');
            }


               if ($shipmentCurrentStatus['current_status'] == 'D') {
                 $img        = base64_encode(file_get_contents(Zend_Util_Path::getInstance()->imgUploadPath() . "/pod/" . $fileName));
                 $encodedImg = 'data: ' . mime_content_type($info['name']) . ';base64,' . $img;
                 $addShipmentPodData = $this->modelObj->addContent('iCargo_shipments_pod', array(
                    'driver_id' => $shipmentdetails['assigned_driver'],
                    'shipment_ticket' => $ticketid,
                    'type' => $postData['type'],
                    'value' => $encodedImg,
                    'delivery_comment' => $postData['delivery_comment'],
                    'delivery_contact_person' => $postData['delivery_contact_person'],
                    'pod_name' => $fileName
                ));
            }
        }

    }

    public function shipmentdetailsAction(){
        //$ticketid 			= $this->shipment_ticket;
        $ticketid 			= $this->shipment_ticket;
        $podData = array();
        $shipmentdetails     = $this->modelObj->getShipmentStatusDetails('"'.$ticketid.'"');
        $parcelDetails                                = $this->modelObj->getAllParceldataByTicket($ticketid);
        $shipmentTrackingDetails                      = $this->shipmentTrackingDetails($shipmentdetails);

        $shipmentdetails['shipment_service_type']     = ($shipmentdetails['shipment_service_type'] == 'P') ? 'Collection' : 'Delivery';

        $adressarr = array();
        $adressarr[] = ($shipmentdetails['shipment_customer_city'] !='')?$shipmentdetails['shipment_customer_city']:'';
        $adressarr[] = ($shipmentdetails['shipment_customer_country'] !='')?$shipmentdetails['shipment_customer_country']:'';
		$shipmentdetails['shipment_customer_details']	 = 	implode(',',array_filter($adressarr));

		$shipmentTrackingDetails['is_dassign_accept'] = ($shipmentTrackingDetails['divername'] != '') ? $shipmentTrackingDetails['is_dassign_accept'] : 'NA';
        $shipmentHistory                              = ($shipmentdetails['last_history_id'] != 0) ? $this->getShipmentStatusHistory($shipmentdetails['last_history_id']) : array();
        $shipmentRejectHistory                        = $this->modelObj->getAcceptRejectsShipmentStatusHistory($ticketid);
        $shipmentCurrentStatus                        = $this->modelObj->getShipmentCurrentStatusAndDriverId($ticketid);
        $shipmentLifeCycle                            = $this->modelObj->getShipmentLifeCycleHistory($ticketid);

        $trackingRecords = array();
        //remove duplicate record from tracking info
        foreach($shipmentLifeCycle as $item){
            $trackingRecords[$item["internel_action_code"]] = $item;
            $trackingRecords[$item["internel_action_code"]]["create_date"] = Library::_getInstance()->date_format($item["create_date"]);
        }
        //end of duplicate record from tracking info


        if ($shipmentdetails['current_status'] == 'D') {
            $existingPodData = $this->modelObj->getExistingPodData($ticketid);
            $contactName     = $commentData = '';
            foreach ($existingPodData as $key => $pod) {
                $contactName = $pod['contact_person'];
                $commentData = $pod['comment'];
           }
            $podData['delivery_contact_person'] =  $contactName;
            $podData['delivery_comment'] =  $commentData;
        }
        $returnData = array();
        $shipmentAdditionaldetails                  = $this->modelObj->getShipmentAdditionalDetails($ticketid);
        $returnData['shipmentData']                 = $shipmentdetails;
        $returnData['podData']                      = $podData;
        $returnData['shipmentAdditionaldetails']    = $shipmentAdditionaldetails;
        $returnData['parcelData']                   = $parcelDetails;
        $returnData['trackingData']                 = $shipmentTrackingDetails;
        $returnData['shipmentHistoryData']          = $shipmentHistory;
        $returnData['rejectHistoryData']            = $shipmentRejectHistory;
        $returnData['shipmentLifeCycle']            = array_values($trackingRecords);//$shipmentLifeCycle;
        $returnData['griddata']                     = $this->getshipmentdetailsjsonAction($ticketid);

        return $returnData;
    }

    public function shipmentTrackingDetails($getShipmentStatus)
    {
        $shipmentStatus                  = $getShipmentStatus['current_status'];
        $shipment_isRouted               = ($getShipmentStatus['is_shipment_routed'] == 1) ? 'Yes' : 'No';
        $shipment_isDAssign              = ($getShipmentStatus['is_driver_assigned'] == 1) ? 'Yes' : 'No';
        $shipment_isDAccept              = ($getShipmentStatus['is_driver_accept'] == 'Pending') ? 'Pending' : (($getShipmentStatus['is_driver_accept'] == 'YES') ? 'Accepted' : 'Rejected');
        $shipmentDriver                  = $getShipmentStatus['name'];
        $datastatus                      = array();
        $datastatus['is_routed']         = $shipment_isRouted;
        $datastatus['is_dassign']        = $shipment_isDAssign;
        $datastatus['is_dassign_accept'] = $shipment_isDAccept;
        $datastatus['divername']         = $shipmentDriver;
        switch ($shipmentStatus) {
            case 'C':
                $datastatus['ship_status'] = 'Unassigned Shipments';
                break;
            case 'O':
                if ($datastatus['is_dassign_accept'] == 'Rejected') {
                    $datastatus['ship_status'] = 'Rejected Shipments';
                } elseif ($datastatus['is_dassign_accept'] == 'Pending') {
                    $datastatus['ship_status'] = 'Assigned Shipments';
                } else {
                    $datastatus['ship_status'] = 'Operational Shipments';
                }
                break;
            case 'S':
                $datastatus['ship_status'] = 'Saved Shipments';
                break;
            case 'Dis':
                $datastatus['ship_status'] = 'Disputed Shipments';
                break;
            case 'Deleted':
                $datastatus['ship_status'] = 'Deleted Shipments';
                break;
            case 'D':
                $datastatus['ship_status'] = 'Delivered Shipments';
                break;
            case 'Ca':
                $datastatus['ship_status'] = 'Carded Shipments';
                break;
            case 'Rit':
                $datastatus['ship_status'] = 'Return Shipments';
                break;
        }
        return $datastatus;
    }

    public function getshipmentdetailsjsonAction($ticketid)
    {
        $shipmentdetails             = $this->modelObj->getShipmentStatusDetails('"'.$ticketid.'"');
        $refNo                       = $shipmentdetails['instaDispatch_jobIdentity'];
        $shipmentdetailsforReference = $this->modelObj->getShipmentDetailsByReference($refNo);
        $data                        = $innerdata = array();
        if(count($shipmentdetailsforReference) > 0) {
            $count = 1;
            foreach ($shipmentdetailsforReference as $value) {
                $address = '';
                $address .= ($value['shipment_address1'] != 'null') ? $value['shipment_address1'] . '<br/>' : '';
                $address .= ($value['shipment_address2'] != 'null') ? $value['shipment_address2'] . '<br/>' : '';
                $address .= ($value['shipment_address3'] != 'null') ? $value['shipment_address3'] . '<br/>' : '';
                if ($value['instaDispatch_loadGroupTypeCode'] == 'SAME') {
                    $typeCode = 'Same Day';
                } elseif ($value['instaDispatch_loadGroupTypeCode'] == 'NEXT') {
                    $typeCode = 'Next Day';
                } elseif ($value['instaDispatch_loadGroupTypeCode'] == 'PHONE') {
                    $typeCode = 'Phone';
                } else {
                    $typeCode = 'Regular';
                }
                $stage = '';
                switch ($value['current_status']) {
                    case 'C':
                        $stage = 'Unassigned Shipments';
                        break;
                    case 'O':
                        $stage = 'Operational Shipments';
                        break;
                    case 'S':
                        $stage = 'Saved Shipments';
                        break;
                    case 'Dis':
                        $stage = 'Disputed Shipments';
                        break;
                }

                $innerdata[] = array(
                    "sr" => $count,
                    "shipment_ticket" => $value['shipment_ticket'],
                    "shipment_consignment" => $value['instaDispatch_objectIdentity'],
                    "shipment_docket" => $value['instaDispatch_docketNumber'],
                    "shipment_ref" => $value['instaDispatch_jobIdentity'],
                    "shipment_service_type" => ($value['shipment_service_type'] == 'P') ? 'Collection' : 'Delivery',
                    "shipment_create_date" => date("d-m-Y", strtotime($value['shipment_create_date'])),
                    "shipment_required_service_date" => date("d-m-Y", strtotime($value['shipment_required_service_date'])),
                    "shipment_required_service_time" => $value['shipment_required_service_starttime'] . ' - ' . $value['shipment_required_service_endtime'],
                    "shipment_total_weight" => $value['shipment_total_weight'],
                    "shipment_total_volume" => $value['shipment_total_volume'],
                    "shipment_customer_name" => $value['shipment_customer_name'],
                    "shipment_customer_email" => $value['shipment_customer_email'],
                    "shipment_customer_phone" => $value['shipment_customer_phone'],
                    "shipment_postcode" => $value['shipment_postcode'],
                    "shipment_current_stage" => $stage,
                    "shipment_total_attempt" => $value['shipment_total_attempt'],
                    "dataof" => $value['dataof'],
                    "shipment_address" => $address,
                    "shipment_inWarehouse" => $value['is_receivedinwarehouse'],
                    "shipment_type" => $typeCode,
                    "shipment_driverPickup" => $value['is_driverpickupfromwarehouse']
                );
                $count++;
            }
            $data['rows'] = $innerdata;
            return $data;

        } else {

            $showdata = array(
                'rows' => array());


            return $showdata;
        }
    }
     public function getShipmentStatusHistory($shipmentHistoryid, $temparr = null)
    {
        $data                       = $this->modelObj->getShipmentStatusHistory($shipmentHistoryid);
        $history                    = array();
        $history['create_date']     = $data['create_date'];
        $history['driver_names']    = $data['name'];//$data['driver_unique_name'];

        $history['assigned_date']   = $data['last_assigned_service_date'];
        $history['assigned_time']   = $data['last_assigned_service_time'];
        $history['service_date']    = $data['actual_given_service_date'];
        $history['service_time']    = $data['actual_given_service_time'];
        $history['next_date']       = $data['next_schedule_date'];
        $history['next_time']       = $data['next_schedule_time'];
        $history['notes']           = $data['notes'];
        $history['driver_comment']  = $data['driver_comment'];
        $history['shipment_status'] = $data['shipment_status'];
        $temparr[]                  = $history;
        if ($data['last_shipment_history_id'] != 0) {
            return $this->getShipmentStatusHistory($data['last_shipment_history_id'], $temparr);
        }
        return $temparr;
    }

  public function getaddressbypostcode(){
          $pcaLookup = new Address_Lookup();
          $addresses = $pcaLookup->lookup($this->shipment_postcode,$this->country_code);
          if($addresses["status"]=="success"){
			  $container = json_decode(json_encode((array)$addresses['data']), TRUE);
			  $addresses = $pcaLookup->lookup($this->shipment_postcode,$this->country_code,$container[0]['id'][0]);
			  $records = array();
                foreach($addresses["data"] as $key => $list)
                {
                    array_push($records, array(
                        "address" => $list["place"].", ".$list["street"],
                        "id" => $list["id"],
                        "street" => $list["street"]
                    ));
                }
				return $records;
              //return $addresses["data"];
          }else{
              return array();
          }
    }

  public function getaddressdetailbyid(){
          $pcaLookup = new Address_Lookup();
          $addresses = $pcaLookup->lookupByID($this->address_id);
          if($addresses["status"]=="success"){
              return $addresses["data"];
          }else{
              return array();
          }
      }
  }
?>
