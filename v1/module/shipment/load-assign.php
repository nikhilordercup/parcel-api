<?php
require "model/load-assign.php";
class Route_Assign
    {
    public

    function __construct($param)
        {
        $this->db = new DbHandler();
        $this->obj = new Common();
        $this->libraryObj = new Library();
        
        $this->modelObj = Load_Assign_Model::_getInstance();
        
        if (isset($param['start_time']))
            {
            $this->start_time = $param['start_time'];
            }

        if (isset($param['route_name']))
            {
            $this->route_name = $param['route_name'];
            }

        if (isset($param['driver_id']))
            {
            $this->driver_id = $param['driver_id'];
            }

        if (isset($param['email']))
            {
            $this->email = $param['email'];
            }

        if (isset($param['access_token']))
            {
            $this->access_token = $param['access_token'];
            }

        if (isset($param['route_id']))
            {
            $this->route_id = $param['route_id'];
            }

        if (isset($param['shipment_ticket']))
            {
            $this->shipment_ticket = $param['shipment_ticket'];
            }

        if (isset($param['from_route_id']))
            {
            $this->from_route_id = $param['from_route_id'];
            }

        if (isset($param['to_route_id']))
            {
            $this->to_route_id = $param['to_route_id']; //temp_route_id
            }

        if (isset($param['postcode']))
            {
            $this->postcode = $param['postcode'];
            }

        if (isset($param['shipment_id']))
            {
            $this->shipment_id = $param['shipment_id'];
            }

        if (isset($param['to_route_index']))
            {
            $this->to_route_index = $param['to_route_index'];
            }

        if (isset($param['drop_count']))
            {
            $this->drop_count = $param['drop_count'];
            }

        if (isset($param['company_id']))
            {
            $this->company_id = $param['company_id'];
            }

        if (isset($param['warehouse_id']))
            {
            $this->warehouse_id = $param['warehouse_id'];
            }

        if (isset($param['temp_route_id']))
            {
            $this->temp_route_id = $param['temp_route_id'];
            }
        }
    
    private
    function _add_shipment_life_history($tickets, $action, $action_code, $action_taken_by, $company_id, $warehouse_id, $driver_id = 0, $route_id = 0)
        {
        $this->obj->addShipmentlifeHistory($tickets, $action, $driver_id, $route_id, $company_id, $warehouse_id, $action_code, $action_taken_by);
        }
        
        
    private
    function _save_route()
        {
        $getRouteDetails = $this->modelObj->getRouteDetails($this->tickets_str, $this->access_token);//$this->_get_route_details();
        // create routes
            
        $createdRoute['route_id'] = $getRouteDetails[0]['route_id'];
        $createdRoute['custom_route'] = ($getRouteDetails[0]['route_id'] == 0) ? 'Y' : 'N';
        $createdRoute['driver_id'] = "0";
        $createdRoute['route_name'] = $this->route_name . '_' . date("d-m-Y") . '_' . rand(500, 4000);
        $createdRoute['is_active'] = 'Y';
        $createdRoute['status'] = '1';
        $createdRoute['is_optimized'] = $getRouteDetails[0]['is_optimized'];
        $createdRoute['optimized_type'] = $getRouteDetails[0]['optimized_type'];
        $createdRoute['last_optimized_time'] = $getRouteDetails[0]['last_optimized_time'];
        $createdRoute['company_id'] = $this->company_id;
        $createdRoute['warehouse_id'] = $this->warehouse_id;
        $createdRoute['route_type'] = $getRouteDetails[0]['route_type'];
        $shipment_route_id = $this->db->save("shipment_route", $createdRoute);
        
        if($createdRoute['route_type']=='SAMEDAY'){
          if(count($getRouteDetails)==1){
             return array(
                'status' => true,
                'message' => 'Requested Route can not Assigned to driver.',
                'post_data' => $firebaseObj->getCurrentAssignedRouteData()
            ); 
          }
          else{
           if(array_search('P', array_column($getRouteDetails, 'shipment_type')) !== False) {
            $loadIdentity  = $getRouteDetails[0]['load_identity'];
            $collectionjob = $this->modelObj->getNotAssignCollectionjob($loadIdentity); 
            if($collectionjob){
             $samedayshipmentticket =  $collectionjob['shipment_ticket'];  
            }
           }else{
              return array(
                'status' => true,
                'message' => 'Requested Route can not Assigned to driver.',
                'post_data' => $firebaseObj->getCurrentAssignedRouteData()
            );   
           } 
          }     
        }
        // Assign Route to Driver

        foreach($getRouteDetails as $shipdata)
            {
            // Update Cargo shipment

            $cargoshipment = array();
            if(($shipdata['route_type']=='SAMEDAY') && ($shipdata['shipment_type']=='P')){
             $shipdata['temp_shipment_ticket'] =   $samedayshipmentticket; 
            }
            $cargoshipment['is_shipment_routed'] = '1';
            $cargoshipment['shipment_routed_id'] = $shipment_route_id;
            $cargoshipment['is_driver_assigned'] = '0';
            $cargoshipment['current_status'] = 'S';
            $cargoshipment['icargo_execution_order'] = $shipdata['drop_execution_order'];
            $cargoshipment['distancemiles'] = $shipdata['distancemiles'];
            $cargoshipment['estimatedtime'] = $shipdata['estimatedtime'];
            $condition = "shipment_ticket = '" . $shipdata['temp_shipment_ticket'] . "'";
            $this->db->update('shipment', $cargoshipment, $condition);
            $this->_add_shipment_life_history($shipdata['temp_shipment_ticket'], 'Assign to Driver', 'ASSIGNTODRIVER', 'Controller', $this->company_id, $this->warehouse_id, '0', $shipment_route_id);
            }
        
        // check any delivery job exist;
        
        if($createdRoute['route_type']=='SAMEDAY'){
            $loadIdentity  = $getRouteDetails[0]['load_identity'];
            $deliveryjob = $this->modelObj->checkNotPendingDeliveryjob($loadIdentity); 
            if($deliveryjob){
               // add a collection job;   
                $collectionJob = $this->modelObj->getCollectionjobDetails($loadIdentity); 
                $collectionJob['shipment_ticket'] = $collectionJob['shipment_ticket'].'V'.rand(45,230);
                $collectionJob['shipment_routed_id'] = 0;
                $collectionJob['assigned_driver'] = 0;
                $collectionJob['assigned_vehicle'] = 0;
                $collectionJob['shipment_assigned_service_date'] = '1970-01-01';
                $collectionJob['shipment_assigned_service_time'] = '00:00:00';
                $collectionJob['is_shipment_routed'] = '0';
                $collectionJob['is_driver_assigned'] = '0';
                $collectionJob['current_status'] = 'C';
                $collectionJob['icargo_execution_order'] = '1';
                unset($collectionJob['shipment_id']);
                $shipment_route_id = $this->db->save("shipment", $collectionJob);
            }
        }
        
        if ($this->modelObj->deleteTempRoutes($this->route_id))
            {
            return array(
                'status' => true,
                'message' => 'Requested Route has been saved successfully.'
            );
            }
        }

    private
    function _save_and_assign_to_driver()
        {
        $samedayshipmentticket = '';
        $timeStamp = strtotime($this->start_time);
        $getRouteDetails = $this->modelObj->getRouteDetails($this->tickets_str, $this->access_token);//$this->_get_route_details();
        // create routes
        $createdRoute['route_id'] = $getRouteDetails[0]['route_id'];
        $createdRoute['custom_route'] = ($getRouteDetails[0]['route_id'] == 0) ? 'Y' : 'N';
        $createdRoute['route_name'] = $this->route_name;
        $createdRoute['driver_id'] = $this->driver_id;
        $createdRoute['assign_start_time'] = date('H:i:s', $timeStamp);
        $createdRoute['service_date'] = date('Y-m-d H:i:s', $timeStamp);
        $createdRoute['is_active'] = 'Y';
        $createdRoute['status'] = '1';
        $createdRoute['company_id'] = $this->company_id;
        $createdRoute['warehouse_id'] = $this->warehouse_id;
        $createdRoute['route_type'] = $getRouteDetails[0]['route_type'];
        $shipment_routed_id = $this->db->save("shipment_route", $createdRoute);
        if($createdRoute['route_type']=='SAMEDAY'){
          if(count($getRouteDetails)==1){
             return array(
                'status' => "error",
                'message' => 'Requested Route can not assigned to driver.',
                'post_data' => array()//$firebaseObj->getCurrentAssignedRouteData()
            ); 
          }
          else{
           if(array_search('P', array_column($getRouteDetails, 'shipment_type')) !== False) {
            $loadIdentity  = $getRouteDetails[0]['load_identity'];
            $collectionjob = $this->modelObj->getNotAssignCollectionjob($loadIdentity); 
            if($collectionjob){
             $samedayshipmentticket =  $collectionjob['shipment_ticket'];  
            }
           }else{
              return array(
                'status' => "error",
                'message' => 'Requested Route can not assigned to driver.',
                'post_data' => array()//$firebaseObj->getCurrentAssignedRouteData()
            );   
           } 
          }     
        }
        // Assign Route to Driver
        foreach($getRouteDetails as $shipdata)
            {
            $drivershipmet = array();
            if(($shipdata['route_type']=='SAMEDAY') && ($shipdata['shipment_type']=='P')){
             $shipdata['temp_shipment_ticket'] =   $samedayshipmentticket; 
            }
            $drivershipmet['shipment_ticket'] = $shipdata['temp_shipment_ticket'];
            $drivershipmet['driver_id'] = $this->driver_id;
            $drivershipmet['vehicle_id'] = $this->vehicle_id;
            $drivershipmet['shipment_route_id'] = $shipment_routed_id;
            $drivershipmet['assigned_date'] = date("Y-m-d");
            $drivershipmet['assigned_time'] = date("H:m:s");
            $drivershipmet['execution_order'] = $shipdata['execution_order'];
            $drivershipmet['distancemiles'] = $shipdata['distancemiles'];
            $drivershipmet['estimatedtime'] = $shipdata['estimatedtime'];

            // Update iCargo shipment

            $cargoshipment = array();
            $cargoshipment['shipment_routed_id'] = $shipment_routed_id;
            $cargoshipment['assigned_driver'] = $this->driver_id;
            $cargoshipment['assigned_vehicle'] = $this->vehicle_id;
            $cargoshipment['shipment_assigned_service_date'] = date("Y-m-d");
            $cargoshipment['shipment_assigned_service_time'] = date("H:m:s");
            $cargoshipment['is_shipment_routed'] = '1';
            $cargoshipment['is_driver_assigned'] = '1';
            $cargoshipment['current_status'] = 'O';
            $cargoshipment['icargo_execution_order'] = $shipdata['drop_execution_order'];
            $cargoshipment['distancemiles'] = $shipdata['distancemiles'];
            $cargoshipment['estimatedtime'] = $shipdata['estimatedtime'];
            $condition = "shipment_ticket = '" . $shipdata['temp_shipment_ticket'] . "'";
            $this->db->save("driver_shipment", $drivershipmet);
            $this->db->update('shipment', $cargoshipment, $condition);
            $this->_add_shipment_life_history($shipdata['temp_shipment_ticket'], 'Assign to Driver', 'ASSIGNTODRIVER', 'Controller', $this->company_id, $this->warehouse_id, $this->driver_id, $shipment_routed_id);
            }
        
        // check any delivery job exist;
        
        if($createdRoute['route_type']=='SAMEDAY'){
            $loadIdentity  = $getRouteDetails[0]['load_identity'];
            $deliveryjob = $this->modelObj->checkNotPendingDeliveryjob($loadIdentity); 
            if($deliveryjob){
               // add a collection job;   
                $collectionJob = $this->modelObj->getCollectionjobDetails($loadIdentity); 
                $collectionJob['shipment_ticket'] = $collectionJob['shipment_ticket'].'V'.rand(45,230);
                $collectionJob['shipment_routed_id'] = 0;
                $collectionJob['assigned_driver'] = 0;
                $collectionJob['assigned_vehicle'] = 0;
                $collectionJob['shipment_assigned_service_date'] = '1970-01-01';
                $collectionJob['shipment_assigned_service_time'] = '00:00:00';
                $collectionJob['is_shipment_routed'] = '0';
                $collectionJob['is_driver_assigned'] = '0';
                $collectionJob['current_status'] = 'C';
                $collectionJob['icargo_execution_order'] = '1';
                unset($collectionJob['shipment_id']);
                $shipment_route_id = $this->db->save("shipment", $collectionJob);
            }
        }
        
        if ($this->modelObj->deleteTempRoutes($this->route_id))
            {
            $firebaseObj = new Firebase_Route_Assign(array(
                "driver_id" => $this->driver_id,
                "route_id" => $shipment_routed_id,
                "warehouse_id" => $this->warehouse_id,
                "email" => $this->email,
                "company_id" => $this->company_id
            ));
            return array(
                'status' => "success",
                'message' => 'Requested Route has been Assigned to driver.',
                'post_data' => $firebaseObj->getCurrentAssignedRouteData()
            );
            }
        }

    /*public

    function recoveryRoute()
        {
        $firebaseObj = new Firebase_Route_Assign(array(
            "driver_id" => $this->driver_id,
            "route_id" => $this->route_id,
            "warehouse_id" => 1,
            'email' => 'chris@pedalandpost.co.uk',
            'company_id' => 92
        ));
        $data = $firebaseObj->recoverAssignedRouteData();
        return $data;
        }*/

    public

    function saveAndAssignToDriver()
        {
        $tickets = array();
        $driver_vehicle = $this->modelObj->getDriverAssignedVehicle($this->driver_id);//$this->_get_driver_assigned_vehicle();
        if (count($driver_vehicle))
            {
            $this->vehicle_id = $driver_vehicle['vehicle_id'];
            $shipment_tickets = $this->modelObj->getAllTicketsByRoute($this->route_id);//$this->_get_all_tickets_by_route();
            $route_type = $this->modelObj->getTempRouteDetail($this->route_id);
            foreach($shipment_tickets as $ticket)
                {
                array_push($tickets, $ticket['shipment_ticket']);
                }

            $this->tickets = $tickets;
            $this->tickets_str = implode("','", $tickets);
            if($route_type == 'SAME'){
              $data = $this->_save_and_assign_to_driver();  
            }else{
            // check warehouse status is YES
            $in_warehouse = $this->modelObj->checkAllShipmentInWarehouse($this->tickets_str);//$this->_check_all_shipment_in_warehouse();
            if ($in_warehouse['exist'] == 0)
                { //it may be zero
                $data = $this->_save_and_assign_to_driver();
                }
              else
                {
                $data = array(
                    'message' => 'All shipment of this selected route is (NOT) physically found',
                    'status' => "error"
                );
                }
             }
            }
          else
            {
            $data = array(
                'message' => 'Driver have no vehicle',
                'status' => "error"
            );
            }

        return $data;
        }

    public

    function saveRoute()
        {
        $data = array();
        $tickets = array();
        $shipment_tickets = $this->modelObj->getAllTicketsByRoute($this->route_id);//$this->_get_all_tickets_by_route();
        foreach($shipment_tickets as $ticket)
            {
            array_push($tickets, $ticket['shipment_ticket']);
            }

        $this->tickets = $tickets;
        $this->tickets_str = implode("','", $tickets);
        return $this->_save_route();
        }

    private
    function _get_execution_order($temp_route_id)
        {
        $records = array();
        $items = Load_Assign_Model::_getInstance()->getExecutionOrder($temp_route_id);
        
        foreach($items as $item)
            $records[$item["drop_name"]] = $item["drop_execution_order"];
            
        return $records;
        }

    private
    function update_route_drop_execution_order($temp_route_id)
        {
        $items = array();
        $sql = "SELECT temp_ship_id AS temp_ship_id, drop_name FROM `" . DB_PREFIX . "temp_routes_shipment` WHERE temp_route_id = $temp_route_id ORDER BY drop_execution_order";
        $records = $this->db->getAllRecords($sql);
        
        foreach($records AS $list)
            $items[$list["drop_name"]][] = $list["temp_ship_id"];
            
        $drop_execution_order = 1;
        foreach($items as $key => $item)
            {
            $sql = "UPDATE `" . DB_PREFIX . "temp_routes_shipment` SET  drop_execution_order =  $drop_execution_order WHERE temp_ship_id IN (" . implode(",",$item) . ")";
            $this->db->updateData($sql);
            ++$drop_execution_order;
            }
        }

    private
    function update_old_drop_execution_order($shipment_ticket, $last_execution_order)
        {
        $items = array();
        $sql = "SELECT temp_ship_id AS temp_ship_id, drop_name FROM `" . DB_PREFIX . "temp_routes_shipment` WHERE `drop_execution_order`>=$last_execution_order AND temp_shipment_ticket NOT IN ('$shipment_ticket') AND temp_route_id = $this->to_route_id ORDER BY drop_execution_order";
        $records = $this->db->getAllRecords($sql);
            
        foreach($records AS $list)
            $items[$list["drop_name"]][] = $list["temp_ship_id"];
            
        foreach($items as $key => $item)
            {
            $sql = "UPDATE `" . DB_PREFIX . "temp_routes_shipment` SET  drop_execution_order =  drop_execution_order+" . $this->drop_count . " WHERE temp_ship_id IN (" . implode(",",$item) . ")";
            $this->db->updateData($sql);
            }
        }

    private
    function update_new_drop_execution_order($shipment_ticket)
        {
        $drop_execution_order = 0;
        $items = array();
        $sql = "SELECT `temp_ship_id` AS temp_ship_id, drop_name FROM `" . DB_PREFIX . "temp_routes_shipment` WHERE temp_shipment_ticket IN('$shipment_ticket')";
        $records = $this->db->getAllRecords($sql);
        foreach($records AS $list)
            $items[$list["drop_name"]][] = $list["temp_ship_id"];
        
        foreach($items as $key => $item)
            {
            $drop_execution_order += $this->to_route_index;
            $sql = "UPDATE `" . DB_PREFIX . "temp_routes_shipment` SET  drop_execution_order =  $drop_execution_order WHERE temp_ship_id IN (" . implode(",",$item) . ")";
            $this->db->updateData($sql);
            }
        return $drop_execution_order;
        }

        public function moveCurrentDropToAnotherDrop(){
            $shipment_ticket = str_replace(",","','",$this->shipment_ticket);
            
            //update execution order first
            $last_execution_order = $this->update_new_drop_execution_order($shipment_ticket);
            
            //push the new drop(s) in new route
            $status = $this->db->update("temp_routes_shipment",array("temp_route_id"=>$this->to_route_id), "temp_shipment_ticket IN('$shipment_ticket')");
            
            //update execution after the new drop
            $this->update_old_drop_execution_order($shipment_ticket, $this->to_route_index);
            $this->update_route_drop_execution_order($this->to_route_id);
            
            //re order from route
            $this->update_route_drop_execution_order($this->from_route_id);
            
            if($status){
                return array("status"=>true,"message"=>"Drop(s) moved successfully",'execution_order'=>array($this->to_route_id=>$this->_get_execution_order($this->to_route_id),$this->from_route_id=>$this->_get_execution_order($this->from_route_id)));
            } else {
                return array("status"=>false,"message"=>"Drop(s) not moved");
            }
        }
    

    public

    function resolveDropError()
        {
        //$geo_data = $this->libraryObj->get_lat_long_by_postcode($this->postcode);
         $geo_data = $this->libraryObj->get_lat_long_by_address_for_resolve_route($this->postcode.',UK');
        // if(!empty($geo_data['latitude']) and !empty($geo_data['longitude'])){

        if ($geo_data['status'] == 'success')
            {
            $shipmentDetails = $this->modelObj->getShipmentDetails($this->shipment_id);//$this->_get_shipment_details($this->shipment_id);
            $addressId = $shipmentDetails['address_id'];
            $shipmentAddressDetails = $this->modelObj->getShipmentDetails($addressId);//$this->_get_shipment_address($addressId);
            //$search_string = $shipmentAddressDetails['address_line1'] . ' ' . $shipmentAddressDetails['address_line2'] . ' ' . $this->postcode;
            $status = $this->db->update('shipment', array(
                'shipment_postcode' => $this->postcode,
                'error_flag' => 0,
                'shipment_latitude' => $geo_data['latitude'],
                'shipment_longitude' => $geo_data['longitude']
            ) , "shipment_id = $this->shipment_id");
            $status2 = $this->db->update('address_book', array(
                'postcode' => $this->postcode,
                'latitude' => $geo_data['latitude'],
                'longitude' => $geo_data['longitude'],
                //'search_string' => $search_string
            ) , "id = $addressId");
            $this->db->update('temp_routes_shipment', array(
                'drop_name' => $this->postcode
            ) , "shipment_id = $this->shipment_id");
            if ($status)
                {
                return array(
                    'shipment_id' => $this->shipment_id, /*'route_index'=>$this->route_index,*/
                    'item_index' => $this->to_route_index,
                    'status' => true,
                    "message" => "Shipment error has been resolved successfully"
                );
                }
              else
                {
                return array(
                    'shipment_id' => $this->shipment_id, /*'route_index'=>$this->route_index,*/
                    'item_index' => $this->to_route_index,
                    'status' => false,
                    "message" => "Shipment error has not been resolved"
                );
                }
            }
          else
            {
            $this->db->update('temp_routes_shipment', array(
                'drop_name' => $this->postcode
            ) , "shipment_id = $this->shipment_id");
            return array(
                'shipment_id' => $this->shipment_id, /*'route_index'=>$this->route_index,*/
                'item_index' => $this->to_route_index,
                'status' => false,
                "message" => "Shipment error has not been resolved"
            );
            }
        }

    public

    function saveRoutePostId($param){
        $obj = new Shipment_Model();
        $status = $obj->saveRoutePostId($param["post_id"], $param["shipment_route_id"]);
        if($status){
            return array("status"=>"success", "message"=>"post id saved");
        }
        return array("status"=>"error", "message"=>"post id not saved");
    }
}
?>
