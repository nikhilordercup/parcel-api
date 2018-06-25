<?php
class loadRouteDetails extends Library
{
    
	/*-- start ---*/
	public $route_type = null;
	public $route_id = 0;
	public $company_id = 0;
    public $access_token = null;
	public $email = null;
	public $modelObj = null;
   /*-- End --*/
	public $data = array();
    public $warehouse_id = 0;
    public $shipment_type = null;
    private $_common_model_obj = null;
	

	
    public $drop_type = array();
	public $service_type = array();
	private $_execution_order = 'shipment_executionOrder';
    public function __construct($param){
        $this->db = new DbHandler();
		if (isset($param['company_id'])) {
            $this->company_id = $param['company_id'];
        }
		if (isset($param['access_token'])) {
            $this->access_token = $param['access_token'];
        }
        if (isset($param['warehouse_id'])) {
            $this->warehouse_id = $param['warehouse_id'];
        }
        if (isset($param['route_type'])) {
            $this->route_type = $param['route_type'];
        }
        if (isset($param['route_id'])) {
            $this->route_id = $param['route_id'];
        }
		if (isset($param['email'])) {
            $this->email = $param['email'];
        }
        $this->modelObj  = Shipment_Model::getInstanse();
        $this->_common_model_obj  = new Common();  // Added By Roopesh
    }
    
	
	/* Route details work start here*/
	
	
	public function loadRouteShipmentsDetails(){
		switch($this->route_type){
		 case 'assignroutedetails':
		 $return = $this->getAssignedRouteDetails();
		 break;
		 case 'unassignroutedetails':
		 $return = $this->getUnAssignedRouteDetails();
		 break;
		default:
		$return = $this->getAssignedRouteDetails();
		}
	return $return ;
	}
	
    private function getAssignedRouteDetails(){
		$data = $this->modelObj->getAssignedShipmentData($this->company_id,$this->warehouse_id,$this->route_id);
       
		$tempdata = array();
        if(is_array($data) and count($data)>0){ 
			foreach($data as $key => $value){
                $tempdata[$key] = $value;
                $shipmentCurrentStaus = "";
                if($value['service_type']=='P'){
                    if($value['current_status']=='D'){
                        $shipmentCurrentStaus = 'Collected';  
                    }elseif($value['current_status']=='Ca'){
                        $shipmentCurrentStaus = 'Carded';
                    }elseif($value['current_status']=='O'){
                        $shipmentCurrentStaus = 'Not Collected yet';
                    }else{
                       $shipmentCurrentStaus = 'No Status'; 
                    }
                }
                elseif($value['service_type']=='D'){
                   if($value['current_status']=='D'){
                        $shipmentCurrentStaus = 'Delivered';  
                    }elseif($value['current_status']=='Ca'){
                        $shipmentCurrentStaus = 'Carded';
                    }elseif($value['current_status']=='O'){
                        $shipmentCurrentStaus = 'Not Delivered yet';
                    }else{
                       $shipmentCurrentStaus = 'No Status'; 
                    }    
                }
                else{
                    $shipmentCurrentStaus = 'No Status'; 
                }
                
                
             $load_group_type_code = strtolower($value["shipment_type"]);
            
           if($value["service_type"] == "D") {
                $service_type = "Delivery";
            }
            elseif ($value["service_type"] == "P") {
                $service_type = "Collection";
            }
            
              if($load_group_type_code == "vendor") {
                 $type = "Retail";
              }
              elseif ($load_group_type_code == "next") {
                $type = "Next Day";
              }
              elseif ($load_group_type_code == "phone") {
                $type = "Phone";
              }
              elseif ($load_group_type_code == "same") {
                $type = "Same Day";
              }
            
               $tempdata[$key]['type'] = $type;      
                $tempdata[$key]['service_type'] = $service_type;   
                $tempdata[$key]['current_code'] = $value['current_status'];
                $tempdata[$key]['drop_name'] = $this->_common_model_obj->getDropName(array("postcode"=>$value['postcode'],"address_1"=>$value['address1']));
                
                
               $ticketID  = "'".$value['shipment_ticket']."'";	
			   $tempdata[$key]['parcels'] 	  = $this->modelObj->getShipmentParcels($ticketID);
               $tempdata[$key]['current_status'] = $shipmentCurrentStaus; 
               //$data[$key]['action'] = '<a ng-href="#shipmentedetails/'.$ticketID.'">Action</a>'; 
               $tempdata[$key]['action'] = "Action";     
            }
        } 
	return array("aaData"=>$tempdata);
	}
	private function getUnAssignedRouteDetails(){
		$data = $this->modelObj->getUnAssignedShipmentData($this->company_id,$this->warehouse_id,$this->route_id);
		$tempdata = array();
        if(is_array($data) and count($data)>0){
			foreach($data as $key => $value){
            $tempdata[$key] =   $value;
            $load_group_type_code = strtolower($value["shipment_type"]);
            
           if($value["service_type"] == "D") {
                $service_type = "Delivery";
            }
            elseif ($value["service_type"] == "P") {
                $service_type = "Collection";
            }
            
              if($load_group_type_code == "vendor") {
                 $type = "Retail";
              }
              elseif ($load_group_type_code == "next") {
                $type = "Next Day";
              }
              elseif ($load_group_type_code == "phone") {
                $type = "Phone";
              }
              elseif ($load_group_type_code == "same") {
                $type = "Same Day";
              }
            
            $tempdata[$key]['type'] = $type;      
            $tempdata[$key]['service_type'] = $service_type;   
            $tempdata[$key]['current_code'] = $value['current_status'];
            $ticketID  = "'".$value['shipment_ticket']."'";	
			$tempdata[$key]['parcels'] 	  = $this->modelObj->getShipmentParcels($ticketID);
            $tempdata[$key]['action'] = "Action";
         }
        } 
	return array("aaData"=>$tempdata);
	}
	
    
	/* Route details work end here */
}
?>