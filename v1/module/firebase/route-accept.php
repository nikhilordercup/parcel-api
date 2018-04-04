<?php
class Firebase_Route_Accept extends Firebase
{
	public function __construct($param)
    {
        $this->fb = parent::__construct(array("driver_id"=>$param['driver_id'],"shipment_route_id"=>$param["shipment_route_id"]));
    }
    
    protected function _getFirebaseProfile(){
        return parent::_getFirebaseProfile();
    }
    
    public function acceptRoute(){
        return array("shipment_route_id"=>parent::_getRouteId(), "firebase_profile"=>parent::_getFirebaseProfile());   
    }
}
?>