<?php
class Setup extends Icargo{
	private $_user_id;
	private $_access_token;
	private $_email;
	protected $_parentObj;
	
	private function _setUserId($v){
		$this->_user_id = $v;
	}
	
	private function _setEmail($v){
		$this->_email = $v;
	}
	
	private function _setAccessToken($v){
		$this->_access_token = $v;
	}
	
	private function _getUserId(){
		return $this->_user_id;
	}
	
	private function _getEmail(){
		return $this->_email;
	}
	
	private function _getAccessToken(){
		return $this->_access_token;
	}
	
	public function __construct($data){
		$this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token,"verifyChargeBee"=>false));
		
		if(isset($data->email)){
			$this->_setEmail($data->email);
		}
		
		if(isset($data->access_token)){
			$this->_setAccessToken($data->access_token);
		}
		
		if(isset($data->user_id)){
			$this->_setUserId($data->user_id);
		}
	}
	
    public function getDefaultRegistrationData(){
        $defaultRegData = $this->_parentObj->db->getAllRecords("SELECT setup_order, module_code, name, description, image FROM ".DB_PREFIX."default_registration_settings WHERE status=1 ORDER by setup_order");
        
        $companyRegData = $this->_parentObj->db->getAllRecords("SELECT module_code FROM ".DB_PREFIX."company_default_registration_setup WHERE company_id=$this->_user_id AND status=1");
        
        $temp_1 = $temp_2 = array();
        
        $libraryObj = new Library();
        
        foreach($defaultRegData as $data)
            array_push($temp_1, $data['module_code']);
        
        foreach($companyRegData as $data)
            array_push($temp_2, $data['module_code']);
        
        $diff = array_diff($temp_1, $temp_2);
        
        //$status = (count($diff)==0) ? true : false;
        $status = (count($diff)==0) ? 'completed' : 'not_completed';
        
        foreach($diff as $key => $data)
            $defaultRegData[$key]['status'] = false;
        
        foreach($defaultRegData as $key => $data)
        {
            if(!isset($defaultRegData[$key]['status']))
                $defaultRegData[$key]['status'] = true;
            
            $defaultRegData[$key]['image'] = $data['image'];//$libraryObj->base_url().'/assets/icons/'.$data['image'];
        }
        
        //return $defaultRegData;
        return array("setup_data"=>$defaultRegData, "setup_status"=>$status);
    }
    
	public function getAllWarehouse(){
		return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id),latitude,longitude,name,email,phone,address_1,address_2,city,postcode AS warehouse_name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.warehouse_id");
	}
	
	public function getWarehouseByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id),latitude,longitude,name,email,phone,address_1,address_2,city,postcode FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.warehouse_id WHERE t2.company_id = ".$param["company_id"]);
	}
	
	public function getActiveCompanyListByCompanyId(){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS company_id, t1.name AS company_name FROM ".DB_PREFIX."users AS t1 WHERE t1.id = ".$this->_getUserId()." AND t1.status = 1");
	}
	
	public function getActiveCompanyListByUserId(){
		//SELECT t1.id, t1.name FROM `icargo_users` AS t1 INNER JOIN icargo_company_users AS t2 ON t1.id = t2.company_id WHERE t2.user_id = 20
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS company_id, t1.name AS company_name FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t1.id = t2.company_id WHERE t2.user_id = ".$this->_getUserId()." AND t1.status = 1");
	}
	
	public function getAllActiveCompanyList(){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS company_id, t1.name AS company_name FROM ".DB_PREFIX."users AS t1 WHERE t1.user_level = 2 AND t1.status = 1");
	}
	
	public function getWarehouseCompanyData($param){
		return $this->_parentObj->db->getRowRecord("SELECT t1.id,t1.name FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.company_id where t2.warehouse_id = ".$param->warehouse_id." AND user_level=2");		
   }
   
   public function getWarehouseDataByWarehouseId($param){
		return $this->_parentObj->db->getRowRecord("SELECT name,email,phone,address_1,address_2,city,postcode,id FROM ".DB_PREFIX."warehouse WHERE id = ".$param->id."");
	}
	
	public function addWareHouse($param){
		$column_names = array('phone', 'name', 'email', 'postcode','address_1','address_2','city');
		$warehouse_id = $this->_parentObj->db->insertIntoTable($param, $column_names, DB_PREFIX."warehouse");
		if ($warehouse_id != NULL) {
			$relationData = array('company_id'=>$param->company->id,'warehouse_id'=>$warehouse_id);
			$column_names = array('company_id', 'warehouse_id');
			$relationTblEntry = $this->_parentObj->db->insertIntoTable($relationData, $column_names, DB_PREFIX."company_warehouse");
			if($relationTblEntry!= NULL){
				$response["status"] = "success";
				$response["message"] = "Warehouse created successfully";
				echoResponse(200, $response);
			}else {
				$response["status"] = "error";
				$response["message"] = "Failed to create warehouse. Please try again";
				echoResponse(201, $response);
        	} 
        }
	}
	
	public function editWarehouse($param,$id){
			$updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."warehouse SET name='".$param->name."',email='".$param->email."',phone='".$param->phone."',address_1='".$param->address_1."',address_2='".$param->address_2."',postcode='".$param->postcode."',city='".$param->city."' WHERE id = ".$id."");
			if ($updateData != NULL) {
				$response["status"] = "success";
				$response["message"] = "Warehouse details updated successfully";
				echoResponse(200, $response);
			}else {
				$response["status"] = "error";
				$response["message"] = "Failed to update warehouse details. Please try again";
				echoResponse(201, $response);
			}        
	}
}
?>
