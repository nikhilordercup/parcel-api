<?php
class Settings extends Icargo{ 
    
    public $modelObj = null;
	private $_user_id;
	protected $_parentObj;
	   
	private function _setUserId($v){
		$this->_user_id = $v;
	}
	
	private function _getUserId(){
		return $this->_user_id;
	}
	
	public function __construct($data){
		$this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
        $this->modelObj  = Settings_Model::getInstanse();  
	}
	
    public function getAllSettingRowData(){
       $data[] = array(
             'id'=>'1',
             'name'=>'Shipment Status',
             'code'=>'SHIPMENTSTATUS',
             'description'=>'Shipment status description going here',
             'action'=>'shipmentstatus',
             'actioncode'=>'getAllShipmentsStatus'
       );   
       $data[] = array(
             'id'=>'2',
             'name'=>'Invoice Status',
             'code'=>'INVOICESTATUS',
             'description'=>'Invoice status description going here',
             'action'=>'invoicestatus',
             'actioncode'=>'getAllInvoiceStatus'
       ); 
      $data[] = array(
             'id'=>'3',
             'name'=>'Invoice vs Shipment status',
             'code'=>'INVOICESHIPMENTSTATUS',
             'description'=>'Invoice shipment status description going here',
             'action'=>'invoiceshipmentstatus',
             'actioncode'=>'getAllInvoiceShipmentStatus'
       );
        
      return  $data;  
    }
    
    public function getAllInvoiceStatus(){
          $data =  $this->modelObj->getAllInvoiceStatus();
          return $data;  
	}
    
    public function getAllShipmentsStatus(){
         $data =  $this->modelObj->getAllShipmentsStatus();
          return $data; 
	}
    
    public function getAllInvoiceShipmentStatus(){
       $data =  $this->modelObj->getAllInvoiceShipmentStatus();
       return $data;
    }
    
    public function editInvoiceShipmentStatus($param){
      $editstatus =  $this->modelObj->editContent("shipments_master",array('is_used_for_invoice'=>$param->staus),"id = $param->data "); 
      if($editstatus){
            $response["status"] = "success";
			$response["message"] = "Your action perform successfully"; 
      }else{
            $response["status"] = "error";
			$response["message"] = "Failed to update our action. Please try again"; 
      }
      return $response; 
    }
 }
?>