<?php
class Settings extends Icargo{ 
    
    public $modelObj = null;
	private $_user_id;
    private $_copany_id = null;
	protected $_parentObj;   
    
	private function _setUserId($v){
		$this->_user_id = $v;
	}
	private function _getUserId(){
		return $this->_user_id;
	}
	public function __construct($data){
        if(isset($data->company_id)){
           $this->_copany_id =  $data->company_id;
        }
		$this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
        $this->modelObj  = Settings_Model::getInstanse(); 

         $this->db = new DbHandler(); 
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
         $items =  $this->modelObj->getAllShipmentsStatus();

         foreach($items as $key=>$item){
            $trackingInternalCode = $this->modelObj->findTrackingCodeByShipmentCode($item["code"]);
            $temp = array();
            foreach($trackingInternalCode as $code)
                array_push($temp, $code["tracking_code"]);
            
            $items[$key]["tracking_internal_code"] = $temp;
         }

         $trackingCode =  $this->modelObj->getAllShipmentTrackingCode();

         return array("shipment_status"=>$items,"tracking_code"=>$trackingCode); 
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
    public function updateShipmentTracking($param){
          //$param = json_decode(json_encode($param),1);
          if(count($param)>0){
            $editstatus =  $this->modelObj->editContent("shipments_master",array('is_used_for_tracking'=>$param->tracking_status),"code = '".$param->code."'");
            if($editstatus){
                $response["status"] = "success";
    			$response["message"] = "Your action perform successfully"; 
            }else{
                $response["status"] = "error";
    			$response["message"] = "Failed to update our action. Please try again"; 
           }
        }else{
            $response["status"] = "error";
			$response["message"] = "Failed to update our action. Please try again";     
          }
      return $response; 
    }
    public function updateInternalTracking($param){
        $error = false;
        $this->db->startTransaction();
        $this->modelObj->deleteShipmentTrackingCodeByShipmentCode($param->code);

        foreach($param->selectedTrackingCode as $trackingCode){
            $lastId = $this->modelObj->saveShipmentTrackingCode($param->code, $trackingCode);
            if($lastId==0){
                $this->db->rollBackTransaction();
                $error = true;
                break;
            }
        }
        if(!$error){
            $this->db->commitTransaction();
            $response["status"] = "success";
            $response["message"] = "Your action perform successfully"; 
        }else{
            $response["status"] = "error";
            $response["message"] = "Failed to update our action. Please try again"; 
        }
        return $response; 
    }
    public function getAllCarrier(){
         $data =  $this->modelObj->getAllCarrier($this->_copany_id);
         return $data; 
	}
 }
?>