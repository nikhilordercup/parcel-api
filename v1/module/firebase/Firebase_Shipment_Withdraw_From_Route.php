<?php
class Firebase_Shipment_Withdraw_From_Route extends Firebase
{	
    public function __construct($param){
        $this->fb = parent::__construct(array("shipment_route_id"=>$param['shipment_route_id'],"driver_id"=>$param['driver_id']));
        
        if(isset($param['get_drop_from'])){
            $this->fb->_get_drop_from = $param['get_drop_from'];
        }
        
        if(isset($param['shipmet_tickets'])){
            $this->fb->_shipment_tickets = $param['shipmet_tickets'];
        }
    }

    protected function _getFirebaseProfile()
    {
        return parent::_getFirebaseProfile();
    }

    protected function _getFirebaseIdByShipmentRouteId()
    {
        return parent::_getFirebaseIdByShipmentRouteId();
    }
    
	protected function _getDropOfCurrentRoute()
    {
        return parent::withdrawShipmentFromRoute();
    }

    protected function _getJobCountByShipmentRouteId()
    {
        return parent::_getJobCountByShipmentRouteId();
    }

    public function withdrawShipmentFromRoute(){
        return $this->_getDropOfCurrentRoute();
    }

    public function getShipmentFromRoute(){
        return $this->_getDropOfCurrentRoute();
    }

	public function withdrawShipments($items)
	{	
        $firebaseProfile = $this->_getFirebaseProfile();
        $firebaseId = $this->_getFirebaseIdByShipmentRouteId();

        $jobCount = $this->_getJobCountByShipmentRouteId(parent::_getRouteId());
        $jobCount = $jobCount["job_count"];

        $url = "route-posts/".$firebaseProfile["firebase_id"]."/".$firebaseId["firebase_id"];

        $data = array();
        $shipment_count = 0;
        $message = "shipment(s) released by controller";

        $data["route_info/job_remaining"] = $jobCount;
        if($jobCount==0)
            $data["code"] = "route/completed";

        foreach($items["shipments_drops"] as $drop_name=>$shipments){
            foreach($shipments["shipments"] as $shipment_ticket=>$shipment){
                $shipment_count++;
                $data["shipment_drops/$drop_name/shipments/$shipment_ticket"] = null;
            }
        }

        $fbObj = new Firebase_Api();

        //update firebase
        $fbObj->update($url, $data);

        $message = "$shipment_count $message";

        //route complete
        if($jobCount==0){
            $fbObj->delete($url);
            $message = "Route completed by controller";
        }

        $url = 'appservices/'.$firebaseProfile["firebase_id"].'/'.parent::_getDriverId();;

        $appServiceData = $fbObj->getAppServiceMessage($url);

        if(is_array($appServiceData) and count($appServiceData)>0){
            array_push($appServiceData["messages"], $message);
            $id = $fbObj->update($url, $appServiceData);
        }else{
            $appServiceData = array(
                "code" => "app/refresh",
                "sub-code" => "app refreshed from web",
                "messages" => array($message)
            );
            $id = $fbObj->update($url, $appServiceData);
        }
        return array("postId"=>$id, "jobCount"=>$jobCount);
    }
}
?>