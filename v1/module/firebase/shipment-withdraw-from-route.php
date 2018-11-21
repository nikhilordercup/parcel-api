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
    
	protected function _getDropOfCurrentRoute()
    {
        return parent::withdrawShipmentFromRoute();
    }
	
	public function withdrawShipmentFromRoute()
	{	
		return $this->_getDropOfCurrentRoute();
    }
}
?>