<?php
require_once "model/rest.php";
class Process_Route
{   public $longitude = 0.00;
    public $latitude = 0.00;
    public function __construct($param)
    {
        if(isset($param->access_token))
        {
            $this->admin_access_token = $param->access_token;
        }
        if(isset($param->email))
        {
            $this->admin_email = $param->email;
        }
        if(isset($param->accessToken))
        {
            $this->access_token = $param->accessToken;
        }
        if(isset($param->driverUsername))
        {
            $this->driver_user_name = $param->driverUsername;
        }
        if(isset($param->loadActionCode))
        {
            $this->action = strtoupper($param->loadActionCode);
        }
        if(isset($param->actionType))
        {
            $this->primery_view_action = $param->actionType;
        }
        if(isset($param->latitude))
        {
            $this->latitude = $param->latitude;
        }
        if(isset($param->longitude))
        {
            $this->longitude = $param->longitude;
        }
        if(isset($param->shipment_route_id))
        {
            $this->shipment_route_id = $param->shipment_route_id;
        }
        if(isset($param->primary_email))
        {
            $this->driver_email = $param->primary_email;
        }
        if(isset($param->driver_id))
        {
            $this->driver_id = $param->driver_id;
        }
        if(isset($param->cancel_reason))
        {
            $this->cancel_reason = $param->cancel_reason;
        }
        if(isset($param->warehouse_id))
        {
            $this->warehouse_id = $param->warehouse_id;
        }
        if(isset($param->company_id))
        {
            $this->company_id = $param->company_id;
        }
        if(isset($param->gps_message_code))
        {
            $this->gps_message_code = $param->gps_message_code;
        }
        if(isset($param->profile_name))
        {
            $this->profile_name = $param->profile_name;
        }
        if(isset($param->post_id))
        {
            $this->post_id = $param->post_id;
        }
        if(isset($param->uid))
        {
            $this->uid = $param->uid;
        }
        if(isset($param->postId))
        {
            $this->post_id = $param->postId;
        }

        if(isset($param->driverCode))
        {
            $this->driver_id = $param->driverCode;
        }
        
        $this->model_rest = new Ws_Model_Rest();
        
    }
    
    private function _get_driver_company_warehouse()
    {
        return $this->model_rest->get_driver_company_warehouse($this->driver_id);
    }
    
    private function _get_driver_by_email()
    {
        return $this->model_rest->get_user_by_email($this->driver_email);
    }
    
    private function _get_driver_by_id()
    {
        return $this->model_rest->get_user_by_id($this->driver_id);
    }
    
    private function get_route_by_shipment_route_id()
    {
        return $this->model_rest->get_route_by_shipment_route_id($this->shipment_route_id);
    }
    
    private function _add_driver_tacking()
    {
        $data                 = array();
        $data['drivercode']   = $this->profile_name;
        $data['driver_id']    = $this->driver_id;
        $data['route_id']     = $this->shipment_route_id;
        $data['latitude']     = $this->latitude;
        $data['longitude']    = $this->longitude;
        $data['for']          = $this->action;
        $data['status']       = '1';
        $data['company_id']   = $this->company_id;
        $data['warehouse_id'] = $this->warehouse_id;
        $data['event_time']   = date("Y-m-d H:i", strtotime("now"));
        $trackid              = $this->model_rest->save("api_driver_tracking", $data);
        return $trackid;
    }
    
    private function _accept_shipment($ticket, $driver_id, $route_id) 
    {   
        if(($ticket != '') && ($driver_id != '') && ($route_id != ''))
        {
            $common_obj = new Common();
            $shipmentDetails = $this->model_rest->get_shipment_details_by_ticket($ticket);

            if(count($shipmentDetails)>0)
            {
                $shipmentDetails    = $shipmentDetails[0];
                $condition          = "shipment_ticket IN('$ticket')";

                $shpDataArr = array();
                $shpDataArr['is_driver_accept'] = 'YES';
                $shpDataArr['is_receivedinwarehouse'] = 'YES';
                $shpDataArr['is_driverpickupfromwarehouse'] = 'YES';
                $shpDataArr['warehousereceived_date'] = date('Y-m-d H:m:s');
                $shpDataArr['driver_pickuptime'] = date('Y-m-d H:m:s');

                $this->model_rest->update('shipment', $shpDataArr, $condition);

                $condition         = "shipment_accepted='Pending' AND shipment_route_id = '" . $shipmentDetails['shipment_routed_id'] . "' AND driver_id = '" . $shipmentDetails['assigned_driver'] . "'  AND shipment_ticket IN('$ticket')";
                $status = $this->model_rest->update('driver_shipment', array(
                        'shipment_accepted' => 'YES',
                        'taken_action_by' => 'Driver'
                    ), $condition);
                
                if ($status) 
                {   
                    $route_data = $this->get_route_by_shipment_route_id();
                    
                    $this->model_rest->update('shipment_route', array('driver_accepted' => 1), "shipment_route_id = '$this->shipment_route_id'");
                    
                    $driver_data = $this->_get_driver_by_email();
                    
                    $obj = new View_Support(array('access_token'=>$this->admin_access_token,'driver_id'=>$driver_id, 'email'=>$this->admin_email, 'company_id'=>$this->company_id,'shipment_route_id'=>$this->shipment_route_id,'post_id'=>$this->post_id,'uid'=>$this->uid));
	                $records = $obj->loadAssignedView();


	                //save shipment life history
                    $shipmentTickets = explode("','", $ticket);
                    foreach($shipmentTickets as $item){
                        $historyOfShip = $common_obj->addShipmentlifeHistory($item, 'Driver accepted', $driver_id, $this->shipment_route_id, $this->company_id, $this->warehouse_id, "DRIVERACCEPTED", 'driver');
                    }

                    return array(
                        'success' => true,
                        'status' => true,
                        'message' => "Route " . $route_data['route_name'] . " has been accepted by ".$driver_data['name'],
                        'records'=>$records
                    );
                }
            }
            else
            {   
                return array(
                    'success' => false,
                    'status' => false,
                    'message' => "Shipment not found"
                );
            }
        } 
        else 
        {
            return array(
                'success' => false,
                'status' => false,
                'message' => "Method argument missing"
            );
        }
    }
    
    private function _get_assigned_vehicle_for_shipment()
    {
        $data = $this->model_rest->get_assigned_vehicle_for_shipment($this->shipment_route_id);
        return $data['vehicle_id'];
    }
    
    private function _reject_shipment($ticket, $driver_id, $route_id)
    {
        if (($ticket != '') && ($driver_id != '') && ($route_id != '')) 
        {
            $shipmentDetails = $this->model_rest->get_shipment_details_by_ticket($ticket);
            if(count($shipmentDetails)>0)
            {
                $vehicle_id                            = $this->_get_assigned_vehicle_for_shipment();
                foreach($shipmentDetails as $shipmentDetail)
                {
                    $data                                  = array();
                    
                    if(empty($shipmentDetail['last_shipment_history_id']))
                        $shipmentDetail['last_shipment_history_id'] = 0;
                    
                    $data['dship_id']                      = $shipmentDetail['shipment_id'];
                    $data['shipment_ticket']               = $shipmentDetail['shipment_ticket'];
                    $data['shipment_route_id']             = $shipmentDetail['shipment_routed_id'];
                    $data['assigned_date']                 = $shipmentDetail['shipment_assigned_service_date'];
                    $data['assigned_time']                 = $shipmentDetail['shipment_assigned_service_time'];
                    $data['last_history_id']               = $shipmentDetail['last_shipment_history_id'];
                    $data['shipment_note']                 = $shipmentDetail['shipment_driver_note'];
                    $data['create_date']                   = $shipmentDetail['shipment_create_date'];
                    $data['last_accept_reject_history_id'] = $shipmentDetail['last_history_id'];
                    
                    $data['driver_id']                     = $this->driver_id;
                    $data['vehicle_id']                    = $vehicle_id;
                    $data['company_id']                    = $this->company_id;
                    $data['warehouse_id']                  = $this->warehouse_id;
                    
                    $data['shipment_accepted']             = 'No';  
                    $data['shipment_status']               = 0;
                    $data['service_date']                  = '1970-01-01';  
                    $data['service_time']                  = '00:00:00';
                    $data['taken_action_by']               = 'Driver';
                    $data['create_date_history']           = date("Y-m-d");

                    $this->model_rest->save("driver_accept_reject_history", $data);
                }
                
                
                $condition = "shipment_ticket IN('$ticket')";
                $this->model_rest->update('shipment', array('is_driver_accept' => 'No'), $condition);
                
                $condition = "shipment_route_id = '" . $shipmentDetails['shipment_routed_id'] . "' AND driver_id = '" . $shipmentDetails['assigned_driver'] . "'  AND shipment_ticket IN('$ticket')";
                $status = $this->model_rest->update('driver_shipment', array(
                        'shipment_accepted' => 'No',
                        'is_driveraction_complete' => 'N',
                        'taken_action_by' => 'Driver'
                    ), $condition);
                
               if ($status) 
               {   
                    $route_data = $this->get_route_by_shipment_route_id();
                    //'driver_accepted' => 2; rejected
                    $this->model_rest->update('shipment_route', array('driver_accepted' => 2, 'comment'=>$this->cancel_reason), "shipment_route_id = '$this->shipment_route_id'");
                    
                    $driver_data = $this->_get_driver_by_email();
                    return array(
                        'success' => true,
                        'status' => true,
                        'message' => "Route " . $route_data['route_name'] . " has been rejected by ".$driver_data['name']
                    );
                }
            }
            else
            {
                return array(
                    'success' => false,
                    'status' => false,
                    'message' => "Shipment not found"
                );
            }
        }
        else
        {
			return array(
                'success' => false,
                'status' => false,
                'message' => "Method argument missing"
            );
        }
    }
    
    private function _accept_route()
    {  
        $shipment_ticket = array();
        $company_warehouse = $this->_get_driver_company_warehouse();
        
        $this->company_id = $company_warehouse['company_id'];
        $this->warehouse_id = $company_warehouse['warehouse_id'];
        
        //$this->_add_driver_tacking();
        
        $shipments = $this->model_rest->get_shipment_ticket_by_shipment_route_id($this->shipment_route_id);

        foreach($shipments as $shipment)
            array_push($shipment_ticket, $shipment['shipment_ticket']);
        
        return $this->_accept_shipment(implode("','", $shipment_ticket), $this->driver_id, $this->shipment_route_id);
    }
    
    private function _reject_route()
    {   
        $shipment_ticket = array();

        $company_warehouse = $this->_get_driver_company_warehouse();
        
        $this->company_id = $company_warehouse['company_id'];
        $this->warehouse_id = $company_warehouse['warehouse_id'];
        $this->profile_name  = "nd 2 set rjkt rt";
        $this->_add_driver_tacking();
     
        $shipments = $this->model_rest->get_shipment_ticket_by_shipment_route_id($this->shipment_route_id);

        //save addShipmentlifeHistory
        $common_obj = new Common();

        foreach($shipments as $shipment){
            $common_obj->addShipmentlifeHistory($shipment['shipment_ticket'], 'Route rejected', $this->driver_id, $this->shipment_route_id, $this->company_id, $this->warehouse_id, "ROUTEREJECTED", 'driver');
            array_push($shipment_ticket, $shipment['shipment_ticket']);
        }



        
        return $this->_reject_shipment(implode("','", $shipment_ticket), $this->driver_id, $this->shipment_route_id);
        
    }
    
    private function _start_route()
    {   
        //set all active route to pause of driver
       // $this->model_rest->update('shipment_route', array('is_current'=>'N','is_pause'=>'1'),"driver_id ='$this->driver_id' AND is_current='Y'");
        /* comment above line for show all route in completed bucket,it written for make one route active at one time in out of all assign route for same driver*/
        //set the requested route to active
        $status = $this->model_rest->update('shipment_route', array('is_route_started'=>'1','is_current'=>'Y','is_pause'=>'0'),"driver_id ='$this->driver_id' AND shipment_route_id='$this->shipment_route_id'");
        if($status)
        {
            $route_data = $this->get_route_by_shipment_route_id();
            $driver_data = $this->_get_driver_by_id();
            $obj = new View_Support(array('access_token'=>$this->admin_access_token,'driver_id'=>$this->driver_id, 'email'=>$this->admin_email, 'company_id'=>$this->company_id,'shipment_route_id'=>$this->shipment_route_id,'post_id'=>$this->post_id,'uid'=>$this->uid));
	        $records = $obj->loadAssignedView();

	        //save addShipmentlifeHistory
            $common_obj = new Common();
            $shipmentsData = $this->model_rest->get_available_shipment_for_service_by_shipment_route_id($this->shipment_route_id);
            foreach($shipmentsData as $item){
                $common_obj->addShipmentlifeHistory($item["shipment_ticket"], 'Route started', $this->driver_id, $this->shipment_route_id, $this->company_id,$item["warehouse_id"], "ROUTESTART", 'driver');
                
            }

            Consignee_Notification::_getInstance()->sendRouteStartNotification(array("shipment_route_id"=>$this->shipment_route_id,"company_id"=>$this->company_id,"driver_id"=>$this->driver_id,"trigger_code"=>"agentStarted"));
            return array(
                'success' => true,
                'status' => true,
                'message' => "Route " . $route_data['route_name'] . " started by ".$driver_data['name'],
                'records'=>$records
            );
        }
        else
        {
            return array(
                'success' => false,
                'status' => false,
                'message' => "Route " . $route_data['route_name'] . " has not started by ".$driver_data['name']
            );
        }
    }
    
    
    private function _route_paused()
    {
        $status = $this->model_rest->update('shipment_route', array('is_current'=>'N','is_pause'=>'1'),"driver_id ='$this->driver_id' AND shipment_route_id='$this->shipment_route_id'");
        if($status)
        {
            $route_data = $this->get_route_by_shipment_route_id();
            $driver_data = $this->_get_driver_by_id();
            $obj = new View_Support(array('access_token'=>$this->admin_access_token,'driver_id'=>$this->driver_id, 'email'=>$this->admin_email, 'company_id'=>$this->company_id,'shipment_route_id'=>$this->shipment_route_id,'post_id'=>$this->post_id,'uid'=>$this->uid));
	        $records = $obj->loadAssignedView();

            //save addShipmentlifeHistory
            $common_obj = new Common();
            $shipmentsData = $this->model_rest->get_available_shipment_for_service_by_shipment_route_id($this->shipment_route_id);
            foreach($shipmentsData as $item){
                $common_obj->addShipmentlifeHistory($item["shipment_ticket"], 'Route paused', $this->driver_id, $this->shipment_route_id, $this->company_id, $item["warehouse_id"], "ROUTEPAUSED", 'driver');
            }

            return array(
                'success' => true,
                'status' => true,
                'message' => "Route " . $route_data['route_name'] . " paused by ".$driver_data['name'],
                'records'=>$records
            );
        }
        else
        {
            return array(
                'success' => false,
                'status' => false,
                'message' => "Route " . $route_data['route_name'] . " has not paused by ".$driver_data['name']
            );
        } 
        
    }
    
    
    private function _save_gps_location()
    {
        $this->action = $this->gps_message_code;
        return $this->_add_driver_tacking();
    }
    
    public function route_action()
    {
        switch($this->action)
        {  
            case 'ACCEPT':
                return $this->_accept_route();
                break;
            case 'REJECT':
                return $this->_reject_route();
                break;
            case 'START-ROUTE':
                return $this->_start_route();
                break;
            case 'SAVE-GPS-LOCATION':
                return $this->_save_gps_location();
                break;
           case 'PAUSED':
                return $this->_route_paused();
                break;
        }
        
        
            
    }
}
?>