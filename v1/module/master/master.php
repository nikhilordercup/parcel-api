<?php
class Master extends Icargo{
	private $_user_id;
    private $_company_id;
	protected $_parentObj;
	
	private function _setUserId($v){
		$this->_user_id = $v;
	}
	private function _setCompanyId($v){
		$this->_user_id = $v;
	}
	
	private function _getUserId(){
		return $this->_user_id;
	}
	
	public function __construct($data){
        $this->_company_id = $data->company_id;
        $this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
	}
	
	public function getActiveWareHouseListByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS warehouse_id, t1.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.warehouse_id WHERE t2.company_id = ".$param["company_id"]." AND t1.status = 1");
	}

    public function getAllMasterRowData(){
       $data[] = array(
             'id'=>'1',
             'name'=>'Courier',
             'code'=>'COURIER',
             'description'=>'Courier description going here',
             'action'=>'courier',
             'actioncode'=>'getAllCouriers'
       );
      $data[] = array(
             'id'=>'1',
             'name'=>'Services',
             'code'=>'SERVICES',
             'description'=>'Services description going here',
             'action'=>'services',
             'actioncode'=>'getAllCourierServices'
       );  
      $data[] = array(  
             'id'=>'1',
             'name'=>'Surcharge',
             'code'=>'SURCHARGE',
             'description'=>'Surcharge description going here',
             'action'=>'surcharge',
             'actioncode'=>'getAllCourierSurcharge'
       );  
     /* $data[] = array(
             'id'=>'1',
             'name'=>'Shipment Status',
             'code'=>'SHIPMENTSTATUS',
             'description'=>'Shipment status description going here',
             'action'=>'shipmentstatus',
             'actioncode'=>'getAllShipmentsStatus'
       );   
       $data[] = array(
             'id'=>'1',
             'name'=>'Invoice Status',
             'code'=>'INVOICESTATUS',
             'description'=>'Invoice status description going here',
             'action'=>'invoicestatus',
             'actioncode'=>'getAllInvoiceStatus'
       ); */  
      return  $data;  
    }
    
    public function getAllCouriers($param){  
      $subquery = ($param->viewid!='courier') ?' AND t2.status = 1 AND t1.status = 1':'';
      $data =  $this->_parentObj->db->getAllRecords("SELECT t1.pickup AS support_pickup, t1.pickup_surcharge AS pickup_surcharge, t1.is_internal AS internal, t1.id AS id,t1.courier_id AS cid,t1.status, t1.account_number,t1.company_ccf_operator_service as ccf_operator,t1.company_ccf_operator_surcharge as surcharge_operator,t1.company_ccf_value as ccf,t1.company_surcharge_value as surcharge,t2.name,t2.code,t2.icon,t2.description,t2.is_self  FROM ".DB_PREFIX."courier_vs_company AS t1 INNER JOIN ".DB_PREFIX."courier AS t2 ON t1.courier_id = t2.id WHERE t1.company_id = ".$this->_company_id." $subquery");
       foreach( $data as $key=>$val){
           $data[$key]['internal'] = ($val["internal"]==1) ? true :  false;
           $data[$key]['action'] = 'editCourierAccount';
           $data[$key]['actioncode'] = 'INNER';
           $data[$key]['status'] = ($val['status']==1)?true:false;
       }
      return  $data;  
    }
   /*  
    public function getAllInvoiceStatus($param){
        $data =   $this->_parentObj->db->getAllRecords("SELECT t1.* FROM ".DB_PREFIX."invoice_master AS t1 WHERE  t1.status = '1'");
      return  $data;  
	}
    public function getAllShipmentsStatus($param){
        $data =   $this->_parentObj->db->getAllRecords("SELECT t1.* FROM ".DB_PREFIX."shipments_master AS t1 WHERE  t1.status ='1'");
      return  $data;  
	}
    */
     public function getAllCourierServices($param){
         $data  = $this->_parentObj->db->getAllRecords("
         SELECT L.id, A.service_name,A.service_code,A.service_icon,A.service_description,C.name as courier_name,C.code as courier_code,L.company_service_ccf as ccf,L.company_ccf_operator as ccf_operator,L.company_service_code as custom_service_code,L.company_service_name as custom_service_name,L.status
         FROM ".DB_PREFIX."courier_vs_services_vs_company as L 
         INNER JOIN ".DB_PREFIX."courier_vs_services AS A ON L.service_id = A.id 
         INNER JOIN ".DB_PREFIX."courier_vs_company AS B ON B.courier_id = A.courier_id AND B.company_id = ".$this->_company_id." 
         INNER JOIN ".DB_PREFIX."courier as C on C.id = A.courier_id WHERE L.company_id = ".$this->_company_id." AND  L.courier_id = ".$param->cid." ");
       foreach( $data as $key=>$val){
           $data[$key]['action'] = 'editServiceAccount';
           $data[$key]['actioncode'] = 'INNER';
           $data[$key]['status'] = ($val['status']==1)?true:false;
       }
      return  $data;  
    }
  public function getAllCourierSurcharge($param){
         $data  = $this->_parentObj->db->getAllRecords("
         SELECT L.id, A.surcharge_name,A.surcharge_code,A.surcharge_icon,A.surcharge_description,C.name as courier_name,C.code as courier_code,L.company_surcharge_surcharge as surcharge,L.company_surcharge_code as custom_surcharge_code,L.company_surcharge_name as custom_surcharge_name,L.status,L.company_ccf_operator as ccf_operator
         FROM ".DB_PREFIX."courier_vs_surcharge_vs_company as L 
         INNER JOIN ".DB_PREFIX."courier_vs_surcharge AS A ON L.surcharge_id = A.id 
         INNER JOIN ".DB_PREFIX."courier_vs_company AS B ON B.courier_id = A.courier_id AND B.company_id = ".$this->_company_id."
         INNER JOIN ".DB_PREFIX."courier as C on C.id = A.courier_id WHERE L.company_id = ".$this->_company_id." AND  L.courier_id = ".$param->cid." ");
       foreach( $data as $key=>$val){
           $data[$key]['action'] = 'editSurchargeAccount';
           $data[$key]['actioncode'] = 'INNER';
           $data[$key]['status'] = ($val['status']==1)?true:false;
       }
      return  $data;  
    }
  
    
   public function saveccfHistoryForCarrier($param,$type){
        $req_ccf                  = isset($param->data->ccf)?$param->data->ccf:0.00;
        $req_surcharge            = isset($param->data->surcharge)?$param->data->surcharge:0.00;
        $req_ccf_operator         = isset($param->data->ccf_operator)?$param->data->ccf_operator:'NONE';
        $req_surcharge_operator   = isset($param->data->surcharge_operator)?$param->data->surcharge_operator:'NONE';
        $data = $this->_parentObj->db->getRowRecord("
            SELECT courier_id,company_id,id as reference_id,company_surcharge_value,company_ccf_value,company_ccf_operator_surcharge,company_ccf_operator_service,ccf_history
            FROM ".DB_PREFIX."courier_vs_company as L 
            WHERE id = ".$param->data->id."");
            $company_ccf_value               =  $data['company_ccf_value'];
            $company_surcharge_value         =  $data['company_surcharge_value'];
            $company_ccf_operator_service    =  $data['company_ccf_operator_service'];
            $company_ccf_operator_surcharge  =  $data['company_ccf_operator_surcharge'];
            $ccf_history                     =  $data['ccf_history'];
            $reference_id                    =  $data['reference_id'];
            $courier_id                      =  $data['courier_id'];
            $company_id                      =  $data['company_id'];     
            $lastId                          =  $data['ccf_history'];     
        if($req_ccf==$company_ccf_value && $req_surcharge==$company_surcharge_value && $req_ccf_operator==$company_ccf_operator_service && $req_surcharge_operator==$company_ccf_operator_surcharge){
            //No Histoty
        }else{
            $dataToBeinsert = array();
            $dataToBeinsert['pid'] = $ccf_history;
            $dataToBeinsert['type'] = $type;
            $dataToBeinsert['carrier_id'] = $courier_id;
            $dataToBeinsert['company_id'] = $company_id;
            $dataToBeinsert['customer_id'] = isset($param->customer_id)?$param->customer_id:0;
            $dataToBeinsert['created_by'] = $param->user_id;
            $dataToBeinsert['create_date'] = date("Y-m-d");
            $dataToBeinsert['create_time'] = date("H:m:s");
            $dataToBeinsert['reference_id'] = $reference_id;
            $dataToBeinsert['status'] = '1';
            $dataToBeinsert['ccf_value'] = $company_ccf_value;
            $dataToBeinsert['ccf_operator'] = $company_ccf_operator_service;
            $dataToBeinsert['surcharge_value'] = $company_surcharge_value;
            $dataToBeinsert['surcharge_operator'] = $company_ccf_operator_surcharge;
            $column_names = array('pid','type','carrier_id','company_id','customer_id','created_by',
                                      'create_date','create_time','reference_id','status','ccf_value',
                                      'ccf_operator','surcharge_value','surcharge_operator');
            $lastId = $this->_parentObj->db->insertIntoTable($dataToBeinsert,$column_names, DB_PREFIX."ccf_history");
        } 
       //'CARRIER', 'SERVICE', 'SURCHARGE', 'CUSTOMER', 'CUSTOMER_CARRIER', 'CUSTOMER_SERVICE', 'CUSTOMER_SURCHARGE', 'NONE'
       //'FLAT', 'PERCENTAGE', 'NONE'
            return $lastId;
   } 
    
    
    public function saveccfHistoryForServices($param,$type){
        $req_ccf                  = isset($param->data->ccf)?$param->data->ccf:0.00;
        $req_ccf_operator         = isset($param->data->ccf_operator)?$param->data->ccf_operator:'NONE';
        $data = $this->_parentObj->db->getRowRecord("
          SELECT courier_id,company_id,id as reference_id,company_service_ccf,company_ccf_operator,ccf_history
            FROM ".DB_PREFIX."courier_vs_services_vs_company as L 
            WHERE id = ".$param->data->id."");
            $company_ccf_value               =  $data['company_service_ccf'];
            $company_ccf_operator            =  $data['company_ccf_operator'];
            $ccf_history                     =  $data['ccf_history'];
            $reference_id                    =  $data['reference_id'];
            $courier_id                      =  $data['courier_id'];
            $company_id                      =  $data['company_id'];     
            $lastId                          =  $data['ccf_history'];  
       
        if($req_ccf==$company_ccf_value &&  $req_ccf_operator==$company_ccf_operator){
            //No Histoty
        }else{  
            $dataToBeinsert = array();
            $dataToBeinsert['pid'] = $ccf_history;
            $dataToBeinsert['type'] = $type;
            $dataToBeinsert['carrier_id'] = $courier_id;
            $dataToBeinsert['company_id'] = $company_id;
            $dataToBeinsert['customer_id'] = isset($param->customer_id)?$param->customer_id:0;
            $dataToBeinsert['created_by'] = $param->user_id;
            $dataToBeinsert['create_date'] = date("Y-m-d");
            $dataToBeinsert['create_time'] = date("H:m:s");
            $dataToBeinsert['reference_id'] = $reference_id;
            $dataToBeinsert['status'] = '1';
            $dataToBeinsert['ccf_value'] = $company_ccf_value;
            $dataToBeinsert['ccf_operator'] = $company_ccf_operator;
            $column_names = array('pid','type','carrier_id','company_id','customer_id','created_by',
                                      'create_date','create_time','reference_id','status','ccf_value','ccf_operator');
           $lastId = $this->_parentObj->db->insertIntoTable($dataToBeinsert,$column_names, DB_PREFIX."ccf_history");
        }
            return $lastId;
   } 
    
      
    public function saveccfHistoryForSurcharge($param,$type){
        $req_surcharge            = isset($param->data->surcharge)?$param->data->surcharge:0.00;
        $req_surcharge_operator   = isset($param->data->surcharge_operator)?$param->data->surcharge_operator:'NONE';
        $data = $this->_parentObj->db->getRowRecord("
          SELECT courier_id,company_id,id as reference_id,company_surcharge_surcharge,company_ccf_operator,ccf_history
            FROM ".DB_PREFIX."courier_vs_surcharge_vs_company as L 
            WHERE id = ".$param->data->id."");
            $company_surcharge_value         =  $data['company_surcharge_surcharge'];
            $company_ccf_operator            =  $data['company_ccf_operator'];
            $ccf_history                     =  $data['ccf_history'];
            $reference_id                    =  $data['reference_id'];
            $courier_id                      =  $data['courier_id'];
            $company_id                      =  $data['company_id'];     
            $lastId                          =  $data['ccf_history'];  
        if($req_surcharge==$company_surcharge_value &&  $req_surcharge_operator==$company_ccf_operator){
            //No Histoty
        }else{  
            $dataToBeinsert = array();
            $dataToBeinsert['pid'] = $ccf_history;
            $dataToBeinsert['type'] = $type;
            $dataToBeinsert['carrier_id'] = $courier_id;
            $dataToBeinsert['company_id'] = $company_id;
            $dataToBeinsert['customer_id'] = isset($param->customer_id)?$param->customer_id:0;
            $dataToBeinsert['created_by'] = $param->user_id;
            $dataToBeinsert['create_date'] = date("Y-m-d");
            $dataToBeinsert['create_time'] = date("H:m:s");
            $dataToBeinsert['reference_id'] = $reference_id;
            $dataToBeinsert['status'] = '1';
            $dataToBeinsert['surcharge_value'] = $company_surcharge_value;
            $dataToBeinsert['surcharge_operator'] = $company_ccf_operator;
            $column_names = array('pid','type','carrier_id','company_id','customer_id','created_by',
                                      'create_date','create_time','reference_id','status','surcharge_value','surcharge_operator');
           $lastId = $this->_parentObj->db->insertIntoTable($dataToBeinsert,$column_names, DB_PREFIX."ccf_history");
        } 
            return $lastId;
   } 
  
    
    public function editCourierAccount($param){

       $ccf_history_id  = $this->saveccfHistoryForCarrier($param,'CARRIER');   
       $support_pickup = ($param->data->support_pickup=='true') ? 1 : 0 ;
       $ccf_operator = $param->data->ccf_operator;
       $surcharge_operator = $param->data->surcharge_operator;
       $surcharge = $param->data->surcharge;
       $surcharge = $param->data->surcharge;
       $ccf = $param->data->ccf;
       $update_date = date('Y-m-d');
       $user_id = $param->user_id;
       $pickup_surcharge = $param->data->pickup_surcharge;

       $id = $param->data->id;


       $updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."courier_vs_company SET 
       ccf_history='$ccf_history_id',
       company_ccf_operator_service='$ccf_operator',
       company_ccf_operator_surcharge='$surcharge_operator',
       company_surcharge_value='$surcharge',
       company_ccf_value='$ccf',
       update_date='$update_date',
       updated_by='$user_id',
       pickup='$support_pickup',
       pickup_surcharge='$pickup_surcharge'
       WHERE id = '$id'");
		if ($updateData != NULL) {
			$response["status"] = "success";
			$response["message"] = "Courier details updated successfully";
		}else {
			$response["status"] = "error";
			$response["message"] = "Failed to update Courier details. Please try again";
		}
        return $response;
        
    }
    
    
    
    public function editServiceAccount($param){
        $ccf_history_id  = $this->saveccfHistoryForServices($param,'SERVICE');   
        $updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."courier_vs_services_vs_company SET company_service_name='".$param->data->custom_service_name."',
        company_ccf_operator='".$param->data->ccf_operator."',
        ccf_history='".$ccf_history_id."',
        company_service_code='".$param->data->custom_service_code."',company_service_ccf='".$param->data->ccf."',update_date='".date('Y-m-d')."',updated_by='".$param->user_id."' WHERE id = ".$param->data->id."");
		if ($updateData != NULL) {
			$response["status"] = "success";
			$response["message"] = "Service details updated successfully";
		}else {
			$response["status"] = "error";
			$response["message"] = "Failed to update Service details. Please try again";
		}
        return $response;
        
    }
   public function editSurchargeAccount($param){
       $ccf_history_id  = $this->saveccfHistoryForSurcharge($param,'SURCHARGE');  
       $updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."courier_vs_surcharge_vs_company SET company_surcharge_name='".$param->data->custom_surcharge_name."',
       company_ccf_operator='".$param->data->ccf_operator."',
       ccf_history='".$ccf_history_id."',
       company_surcharge_code='".$param->data->custom_surcharge_code."',company_surcharge_surcharge='".$param->data->surcharge."',updated_date='".date('Y-m-d')."',updated_by='".$param->user_id."' WHERE id = ".$param->data->id."");
		if ($updateData != NULL) {
			$response["status"] = "success";
			$response["message"] = "Surcharge details updated successfully";
		}else {
			$response["status"] = "error";
			$response["message"] = "Failed to update Surcharge details. Please try again";
		}
        return $response;
    } 
    public function editStatus($param){
        switch($param->action){
          case "editCourierAccount":
          $table = 'courier_vs_company';
          break;  
          case "editServiceAccount":
          $table = 'courier_vs_services_vs_company';
          break;        
          case "editSurchargeAccount":
          $table = 'courier_vs_surcharge_vs_company';
          break;         
                
              
        }  
        
        $updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX.$table." SET status='".$param->status."' WHERE id = ".$param->descid."");
		if ($updateData != NULL) {
			$response["status"] = "success";
			$response["message"] = "Your action perform successfully";
		}else {
			$response["status"] = "error";
			$response["message"] = "Failed to update our action. Please try again";
		}
        return $response;
        
    }

    public function saveData($data){ 
        
       $img = file_get_contents('"'.$data->icon[0]->lfDataUrl.'"');
        print_r($img);die;
        $uploads_dir =  dirname(dirname(dirname(dirname(__DIR__)))).'/assets/images/carrier';
        $filename = $data->icon[0]->lfFileName;
        //echo $uploads_dir.'/'.$filename;die;
        //print_r($data->icon[0]->lfDataUrl);die;
       // print_r(glob(realpath(dirname(__FILE__))));
        //$uploads_dir
        //$name = basename($_FILES["pictures"]["name"][$key]);
        move_uploaded_file($data->icon[0]->lfDataUrl,$uploads_dir.'/'.$filename);

            
    //print_r($data->icon[0]->lfDataUrl);die;
   
    }

    private function _disableCompanyInternalCarrier($status, $company_id){
        $sql = "UPDATE " . DB_PREFIX . "courier_vs_company SET is_internal = '0' WHERE company_id = '$company_id'";
        return $this->_parentObj->db->updateData($sql);
    }

    private function _updateCompanyInternalCarrier($status, $company_id, $carrier_id){
        $sql = "UPDATE " . DB_PREFIX . "courier_vs_company SET is_internal = '$status' WHERE company_id = '$company_id' AND courier_id = '$carrier_id'";
        return $this->_parentObj->db->updateData($sql);
    }

    public function setCompanyInternalCarrier($param){
        $this->_disableCompanyInternalCarrier($param->status, $param->company_id);

        $status = $this->_updateCompanyInternalCarrier($param->status, $param->company_id, $param->carrier_id);

        if($status){
            return array("status"=>"success", "message"=>"Carrier updated successfully");
        }else{
            return array("status"=>"error", "message"=>"Carrier not updated");
        }
    }
    
}
?>