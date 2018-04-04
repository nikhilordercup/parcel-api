<?php
class Route_Complete extends Icargo{   
	public function __construct($param){
		parent::__construct(array("email"=>$param['email'],"access_token"=>$param['access_token']));
		
		$this->shipment_route_id = $param['shipment_route_id'];
		$this->company_id = $param['company_id'];
	}
	
	public function saveCompletedRoute(){
		$modelObj = new Route_Model_Complete();
		$status = $modelObj->save(array("shipment_route_id"=>$this->shipment_route_id));
		return $status;
	}
}
?>