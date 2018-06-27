<?php
class Route_Model_Complete{
	public static $_db = null;

	public static function getInstance(){
		if(self::$_db==null){
			self::$_db = new DbHandler();
		}
		return self::$_db;
	}
	
	public function save($param){ 
        $status = $this->getInstance()->updateData("UPDATE `".DB_PREFIX."shipment_route` SET `is_pause`=0, is_active = 'N',`is_current`='N', `completed_date`=NOW() WHERE `shipment_route_id`=".$param['shipment_route_id']);
		if($status){
			return array("status"=>true,"message"=>"Complete route status saved");
		}
		else{
			return array("status"=>false,"message"=>"Complete route status not saved");
		}
	}
	
	
	// get all data by route id
	public function getDriverApiTrackingData($param){
	    //$apiData = $this->getInstance()->getAllRecords("SELECT * FROM " . DB_PREFIX . "api_driver_tracking WHERE route_id = '".$param['shipment_route_id']."' AND driver_id = '".$param['driver_id']."' group by create_date");
        $apiData = $this->getInstance()->getAllRecords("SELECT * FROM " . DB_PREFIX . "api_driver_tracking WHERE route_id = '".$param['shipment_route_id']."' AND driver_id = '".$param['driver_id']."' ORDER BY track_id ASC");
		return $apiData;
	}
	
	//driver id by shipment route id
	public function getDriverIdByShipmentRouteId($shipment_route_id){
		$driverId = $this->getInstance()->getRowRecord("SELECT assigned_driver, warehouse_id FROM " . DB_PREFIX . "shipment WHERE shipment_routed_id = '".$shipment_route_id."'");
		return $driverId;
	}
	
	//save driver time data
	public function saveDriverTimeData($data){
		$status = $this->getInstance()->save("driver_time_tracking",$data);
		if($status){
			return array("status"=>true,"message"=>"Driver data saved successfully");
		}
		else{
			return array("status"=>false,"message"=>"Driver data not saved");
		}
	}

    public function saveDriverApiTracking($param){
        try{
            $this->getInstance()->save("api_driver_tracking",$param);
        }catch(Exception $e){

        }
    }

    //driver id by shipment route id
    public function getDriverByDriverId($user_id){
        $data = $this->getInstance()->getRowRecord("SELECT name as profile_name FROM " . DB_PREFIX . "users WHERE id = '".$user_id."'");
        return $data;
    }
}
?>