<?php
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class Firebase_Route_Assign extends Firebase
{
	private $_driver_id;
	private $_shipment_route_id;
	private $_load_scan_flag= true;
    private $_route_type= "delivery";

    public function __construct($param)
    {
        $this->fbObj = parent::__construct(array(
            "shipment_route_id" => $param['route_id'],
            "driver_id" => $param['driver_id']
        ));
        if(isset($param['warehouse_id'])){
            $this->_setWarehouseId($param['warehouse_id']);
        }
        
        if(isset($param['email'])){
            $this->_setEmail($param['email']);
        }
        
        if(isset($param['company_id'])){
            $this->_setCompanyId($param['company_id']);
        }

        if(isset($param['get_drop_from'])){
            $this->fbObj->_get_drop_from = $param['get_drop_from'];
        }
        
        if(isset($param['shipmet_tickets'])){
            $this->fbObj->_shipment_tickets = $param['shipmet_tickets'];
        }
    }

    private function _getFirebaseCredential(){
        return $this->_getFbCredential();
    }

    private function _getFirebaseDbUrl(){
        return $this->_getFirebaseDb();
    }
    
    private function _setWarehouseId($v)
    {
		return $this->warehouse_id = $v;
	}
    
    private function _setEmail($v)
    {
		return $this->email = $v;
	}
    
    private function _setCompanyId($v)
    {
		return $this->company_id = $v;
	}
	
    private function _getWarehouseId()
    {
		return $this->warehouse_id;
	}
    
    private function _getCompanyId()
    {
		return $this->company_id;
	}
    
    private function _getLoadScanFlag()
    {
		return $this->_load_scan_flag;
	}
    
    private function _getEmail()
    {
		return $this->email;
	}
	
    private function _getDropOfCurrentRoute()
    {
        return parent::_assignedShipmentOfRoute();
    }
    
    private function _get_route_type($input)
    {
        foreach($input as $data)
        {
            if($data['shipment_service_type']=='P')
            {
               return 'collection';
            }
        }
        return 'delivery';
    }
    
	public function recoverAssignedRouteData()
	{
		$routeInfo = $this->fbObj->modelObj->recoverAssignedRouteOfDriverByRouteId(parent::_getDriverId(),parent::_getRouteId());
        
        $route_data = $this->_getDropOfCurrentRoute();
        
        $this->_route_type  = $this->_get_route_type($route_data);
        
        //if($shipment['shipment_service_type']=="P")
        //    $this->_route_type             = "collection";
        //$tempData = $this->_shipmentDrops($this->_getDropOfCurrentRoute());
        
        $tempData = $this->_shipmentDrops($route_data);
        $routeInfo['total_parcel']  = $tempData['total_parcel'];
		$routeInfo['job_remaining'] = $tempData['job_remaining'];
        $routeInfo['route_type']    = $this->_route_type;
        
		$routeInfo['time_remaining'] = array_sum($tempData['time_remaining']);
        $routeInfo['actual_start_time'] = $routeInfo['assign_start_time'];
		$routeInfo['start_time'] = $routeInfo['assign_start_time'];
        $routeInfo['last_optimized_time'] = $this->_formate_date($routeInfo['last_optimized_time'],'Y-m-d H:i:s');
        
		$routeInfo['route_optimized_from'] = base64_encode(json_encode($this->_getWarehouseDetail()));
		$routeInfo['assigned_by'] = $this->_getUserByEmail();
        
        //driver data
        $driver_data = $this->_getUserById($this->_getDriverId());
        
		return array('code'=>'route/assigned','route_info'=>$routeInfo,'shipment_drops'=>$tempData['shipments_drops'],'uid'=>$this->_getDriverFirebaseId(),'username'=>$driver_data['name'],'warehouse_id'=>$this->_getWarehouseId(),'company_id'=>$this->_getCompanyId());//,"shipment_tickets"=>parent::_getShipmentTickets()
    }

    private function _assignRoute($data){
        $serviceAccount = ServiceAccount::fromJsonFile($this->_getFirebaseCredential());
        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            // The following line is optional if the project id in your credentials file
            // is identical to the subdomain of your Firebase project. If you need it,
            // make sure to replace the URL with the URL of your project.
            ->withDatabaseUri($this->_getFirebaseDbUrl())
            ->create();

        $database = $firebase->getDatabase();

        $newPost = $database
            ->getReference('route-posts/'.$data["uid"])
            ->push($data);
        return $newPost->getKey();
    }
	
	public function getCurrentAssignedRouteData()
	{
		$routeInfo = $this->fbObj->modelObj->getAssignedRouteOfDriverByRouteId(parent::_getDriverId(),parent::_getRouteId());
        
        $route_data = $this->_getDropOfCurrentRoute();

        $this->_route_type  = $this->_get_route_type($route_data);
        
        $tempData = $this->_shipmentDrops($route_data);

        $routeInfo['total_parcel']  = $tempData['total_parcel'];
		$routeInfo['job_remaining'] = $tempData['job_remaining'];
        $routeInfo['route_type']    = $this->_route_type;
        
		$routeInfo['time_remaining'] = array_sum($tempData['time_remaining']);
        $routeInfo['actual_start_time'] = $routeInfo['assign_start_time'];
		$routeInfo['start_time'] = $routeInfo['assign_start_time'];
        $routeInfo['last_optimized_time'] = $this->_formate_date($routeInfo['last_optimized_time'],'Y-m-d H:i:s');
        
		$routeInfo['route_optimized_from'] = base64_encode(json_encode($this->_getWarehouseDetail()));
		$routeInfo['assigned_by'] = $this->_getUserByEmail();
        
        //driver data
        $driver_data = $this->_getUserById($this->_getDriverId());

        $notification = new Push_Notification_Index(array("user_id"=>array($this->_getDriverId())));

        $postId = $this->_assignRoute(array(
            'code'=>'route/assigned',
            'route_info'=>$routeInfo,
            'shipment_drops'=>$tempData['shipments_drops'],
            'uid'=>$this->_getDriverFirebaseId(),
            'username'=>$driver_data['name'],
            'warehouse_id'=>$this->_getWarehouseId(),
            'company_id'=>$this->_getCompanyId()
        ));

        $notification->sendRouteAssignNotification();

        return $postId;

        //return array('code'=>'route/assigned','route_info'=>$routeInfo,'shipment_drops'=>$tempData['shipments_drops'],'uid'=>$this->_getDriverFirebaseId(),'username'=>$driver_data['name'],'warehouse_id'=>$this->_getWarehouseId(),'company_id'=>$this->_getCompanyId());
    }
	
	private function _shipmentDrops($shipmentDrops)
	{
		$total_parcel = 0;
		$route_all_shipments_tickets = array();
		$drop_count = count($shipmentDrops);
        // added by nishant. job count will be the count of shipments of all drops. changed on 11-aug-2017
        $job_count = 0;
	    if($drop_count > 0) {
			$time_remaining = array();
			foreach($shipmentDrops as $key => $drop) { 
			     
				$service_start_timestamp = strtotime($drop['service_starttime']);
				$service_end_timestamp = strtotime($drop['service_endtime']);
				
				$seconds = $service_end_timestamp - $service_start_timestamp;
				$hours = floor($seconds / 3600);
				$minutes = $hours * 60;
				$total_parcel += $drop['totparcel'];
				array_push($route_all_shipments_tickets,$drop['tickets']);
				
				$shipment_tickets = str_replace(";","','",$drop['tickets']);
				 
			    $consignee_info = $this->fbObj->modelObj->getShipmentCustomerDetailByShipTicket($this->_getRouteId(),$this->_getDriverId(),$shipment_tickets);

                $shipData = array();
				foreach($consignee_info as $value){
                    
                    $value['warehousereceived_date'] = $this->_formate_date($value['warehousereceived_date'], 'Y-m-d H:i:s'); 
                    $value['driver_pickuptime']      = $this->_formate_date($value['driver_pickuptime'], 'Y-m-d H:i:s'); 
                    $value['disputedate']            = $this->_formate_date($value['disputedate'], 'Y-m-d');
                    $value['form_status']            = "pending";
                    $value['form_code']              = "pending";
                    
				    $shipData[$value['shipment_ticket']] = $value;
				    $parcelsdata = $this->_getAllParcelOfDrop($value['shipment_ticket']);
				    $parcelData  = array();
				    foreach($parcelsdata as $value){
						if($this->_getLoadScanFlag()){
							$value['scan'] = array('load_scan'=>true);
						} else {
							$value['scan'] = array();
						}
					    
				        $parcelData[$value['parcel_ticket']] = $value;
				    }
				    $shipData[$value['shipment_ticket']]['parcels'] = $parcelData;
				}
				$shipmentDrops[$key]["icargo_execution_order"] = $consignee_info[0]['icargo_execution_order'];
				$shipmentDrops[$key]["status"] = 'pending';  
				$shipmentDrops[$key]["time_remaining"] = $minutes;
				$shipmentDrops[$key]["shipments"] = $shipData;
				$shipmentDrops[$key][".priority"] = $drop_count--;
				$job_count += count($shipData);
				array_push($time_remaining,$minutes);
			}
            return array(/*'job_remaining'=>count($shipmentDrops),*/'job_remaining'=>$job_count,'total_parcel'=>$total_parcel,'shipments_drops'=>$shipmentDrops,'time_remaining'=>$time_remaining);
		} else {
			//unset($routeRecords[$key]);	
		}
	}
	
	private function _getAllParcelOfDrop($shipment_tickets)
    {
		$parcels = $this->fbObj->modelObj->getAllParcelOfRoute($this->_getRouteId(),$shipment_tickets);
		return $parcels;
	}
    
    private function _formate_date($input, $pattern)
    {
        if(date('Y', strtotime($input))!=1970)
            return date($pattern, strtotime($input));
        else
            return "1970-01-01 00:00:00";
    }
	
    private function _getDriverFirebaseId()
    {
        $firebase_data = $this->fbObj->modelObj->getUserFirebaseProfile($this->_getDriverId());
        return $firebase_data['firebase_id'];
    }
    
    private function _getWarehouseDetail()
    {
        $data = $this->fbObj->modelObj->getWarehouseDetail($this->_getWarehouseId());
        return array("address"=>$data['name'].', '.$data['address_1'].', '.$data['address_2'].', '.$data['city'],"postcode"=>$data['postcode'],'lat'=>$data['latitude'],'lng'=>$data['longitude']);
    }
    
    private function _getUserByEmail()
    {
        $data = $this->fbObj->modelObj->getUserByEmail($this->_getEmail());
        return $data['name'];
    }
    
    private function _getUserById($user_id)
    {
        $data = $this->fbObj->modelObj->getUserById($user_id);
        return $data;
    }

    public function getCurrentAssignedShipmentData()
    {   
        $routeData = $this->_getRouteDetailByShipmentRouteId();
        $shipmentData = $this->_getDropOfCurrentRoute();
        return array("code"=>"shipment/assigned-to-current-route","uid"=>$routeData["uid"],"target_post_id"=>$routeData["firebase_id"],"shipment_route_id"=>$routeData["shipment_route_id"],"shipments"=>$this->_shipmentDrops($shipmentData));
    }
}
?>