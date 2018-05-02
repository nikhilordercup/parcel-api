<?php
class Route_Complete extends Icargo{   
	public function __construct($param){
		parent::__construct(array("email"=>$param['email'],"access_token"=>$param['access_token']));
		
		$this->shipment_route_id = $param['shipment_route_id'];
		$this->company_id = $param['company_id'];
		$this->modelObj = new Route_Model_Complete();
	}
	
	public function getDriverTimeTracking($param){
		$data = $this->modelObj->getDriverApiTrackingData($param);
		return $data;
	}
	
	public function saveCompletedRoute(){
		//get assigned driver id by shipment route id
		$driverId = $this->modelObj->getDriverIdByShipmentRouteId($this->shipment_route_id);
		$status = $this->modelObj->save(array("shipment_route_id"=>$this->shipment_route_id));

		if($status['status']==true){
			//save driver time tracking
			$apiTrackingData = $this->getDriverTimeTracking(array("shipment_route_id"=>$this->shipment_route_id,"driver_id"=>$driverId["assigned_driver"]));
			//echo '<pre/>';print_r($apiTrackingData);die;
			$itemCount = count($apiTrackingData);
			$itemCount--;
			$j = 0;
			$result = array();
			for($I=0; $I<$itemCount;$I++){
				$j++;
				$temp1 = $apiTrackingData[$I];
				$temp2 = $apiTrackingData[$j];
				$timestamp1 = strtotime($temp1["create_date"]);
				$timestamp2 = strtotime($temp2["create_date"]);
				$timestampDiff = $timestamp2 - $timestamp1;
				if(!isset($result[$temp1['for']])){
					$result[$temp1['for']] = array();	
				}
				$result[$temp1['for']][] = $timestampDiff;
			}
			//echo '<pre/>';print_r($result);die;
			foreach($result as $type=>$time_taken){
				$saveDriverTimeData = $this->modelObj->saveDriverTimeData(array("shipment_route_id"=>$this->shipment_route_id,"driver_id"=>$driverId["assigned_driver"],"status"=>$type,"time_taken"=>array_sum($time_taken),"create_date"=>date('Y-m-d')));
			}
			
			return $status;
		}
	}
}
?>