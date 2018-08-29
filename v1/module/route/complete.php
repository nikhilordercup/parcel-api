<?php
class Route_Complete extends Icargo{   
	public function __construct($param){
		//parent::__construct(array("email"=>$param['email'],"access_token"=>$param['access_token']));
		
		$this->shipment_route_id = $param['shipment_route_id'];
		$this->company_id = $param['company_id'];
		$this->modelObj = new Route_Model_Complete();
	}
	
	public function getDriverTimeTracking($param){
		$data = $this->modelObj->getDriverApiTrackingData($param);
		return $data;
	}
    
    private function _add_driver_tacking(){
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
        $trackid              = $this->modelObj->saveDriverApiTracking($data);
        return $trackid;
    }
	
	public function saveCompletedRoute(){
		//get assigned driver id by shipment route id
        try{
            $driverId = $this->modelObj->getDriverIdByShipmentRouteId($this->shipment_route_id);

            $status = $this->modelObj->save(array("shipment_route_id"=>$this->shipment_route_id));
          
            $this->driver_id = $driverId["assigned_driver"];
            $this->warehouse_id = $driverId["warehouse_id"];

            $driverData = $this->modelObj->getDriverByDriverId($this->driver_id);

            $this->profile_name = $driverData["profile_name"];

            $this->action    = "route_completed";
            $this->latitude  = "0.000";
            $this->longitude = "0.000";
            
            if($status['status']){
                //save driver time tracking
                $apiTrackingData = $this->getDriverTimeTracking(array("shipment_route_id"=>$this->shipment_route_id,"driver_id"=>$this->driver_id));

                $itemCount = count($apiTrackingData);
                $itemCount--;
                $totalTimeTaken = 0;
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
                        $result[$temp1['for']] = 0;
                    }
                    $result[$temp1['for']] = $result[$temp1['for']] + $timestampDiff;

                    $totalTimeTaken += $timestampDiff;
                }

                foreach($result as $type=>$time_taken){
                    $this->modelObj->saveDriverTimeData(array("shipment_route_id"=>$this->shipment_route_id,"driver_id"=>$driverId["assigned_driver"],"status"=>$type,"time_taken"=>$time_taken,"create_date"=>date('Y-m-d')));
                }
                $this->_add_driver_tacking();

                return $status;
            }
        } catch(Exception $e){print_r($e);
            return array("status"=>"error", "message"=>"Route completed event not saved");
        }
	}
}
?>