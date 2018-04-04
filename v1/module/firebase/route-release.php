<?php
class Firebase_Route_Release extends Firebase
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
    }
    
    public function getrelasedata(){
        return array('firebase_profile'=>parent::_getFirebaseProfile(),'shipment_route_id'=>parent::_getRouteId(),'driver_id'=>parent::_getDriverId());
    }
}
?>