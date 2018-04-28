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
		//$status = $this->getInstance()->update('shipment_route', array("is_pause"=>0,"is_current"=>"N","completed_date"=>date('Y-m-d h:i:s')),"shipment_route_id=".$param['shipment_route_id']);
		$status = $this->getInstance()->updateData("UPDATE `".DB_PREFIX."shipment_route` SET `is_pause`=0, `is_current`='N', `completed_date`=NOW() WHERE `shipment_route_id`=".$param['shipment_route_id']);
		if($status){
			return array("status"=>true,"message"=>"Complete route status saved");
		}
		else{
			return array("status"=>false,"message"=>"Complete route status not saved");
		}
	}
	
	
	// get all data by route id
	public function getDriverApiTrackingData($param){
		$apiData = $this->getInstance()->getAllRecords("SELECT * FROM " . DB_PREFIX . "api_driver_tracking WHERE route_id = '".$param['shipment_route_id']."' AND driver_id = '".$param['driver_id']."' group by create_date");
		return $apiData;
	}
	
	//driver id by shipment route id
	public function getDriverIdByShipmentRouteId($shipment_route_id){
		$driverId = $this->getInstance()->getRowRecord("SELECT assigned_driver FROM " . DB_PREFIX . "shipment WHERE shipment_routed_id = '".$shipment_route_id."'");
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
	
	
}
?>