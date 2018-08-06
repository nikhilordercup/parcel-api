<?php
class Firebase_Withdraw_Route extends Firebase
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
	
	public function withdrawRoute()
	{	
        $firebaseProfile = $this->_getFirebaseProfile();
        $firebaseId = $this->_getFirebaseIdByShipmentRouteId();
        $postData = $this->_getDropOfCurrentRoute();
        $fbObj = new Firebase_Api();

        $fbObj->delete('route-posts/'.$firebaseProfile["firebase_id"].'/'.$firebaseId["firebase_id"]);
        $postId = $fbObj->save('appservices/'.$firebaseProfile["firebase_id"], array(
            "code"              => "shipment/withdraw-from-route",
            "shipment_route_id" => parent::_getRouteId(),
            "uid"               => $firebaseProfile["firebase_id"],
            "shipment_drops"    => $postData["shipments_drops"],
            "route_post_id"     => $firebaseId["firebase_id"],
            "withdraw_type"     => "route"
        ));
        return $postId;
    }
}
?>