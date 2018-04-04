<?php
class Company extends Icargo{
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
    
    private function _setCompanyId($v){
		$this->_company_id = $v;
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
    
    private function _getCompanyId(){
		return $this->_company_id;
	}
	
	public function __construct($data){
		$this->_parentObj = parent::__construct(array("email"=>$data->email, "access_token"=>$data->access_token));
		
		if(isset($data->email)){
			$this->_setEmail($data->email);
		}
		
		if(isset($data->access_token)){
			$this->_setAccessToken($data->access_token);
		}
		
		if(isset($data->user_id)){
			$this->_setUserId($data->user_id);
		}
        
        if(isset($data->company_id)){
			$this->_setCompanyId($data->company_id);
		}
	}
	
    public function getDriverDataById(){
		return $this->_parentObj->db->getRowRecord("SELECT t1.name,t1.email,t1.phone,t1.address_1,t1.address_2,t1.city,t1.postcode,t1.state,t1.country,t2.vehicle_category_id,t3.id as vehicle_id,t3.plate_no,t4.category_name FROM ".DB_PREFIX."users as t1 LEFT JOIN ".DB_PREFIX."driver_vehicle AS t2 ON t1.id = t2.driver_id LEFT JOIN ".DB_PREFIX."vehicle AS t3 ON t2.vehicle_id = t3.id LEFT JOIN ".DB_PREFIX."vehicle_category AS t4 ON t4.id = t2.vehicle_category_id where t1.id = ".$this->_user_id."");
	}
    
	public function getUserDataById(){
		return $this->_parentObj->db->getRowRecord("SELECT name,email,phone,address_1,address_2,city,postcode,state,country FROM ".DB_PREFIX."users where id = ".$this->_user_id."");
	}
	
	public function getAllWarehouse(){
		return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id),latitude,longitude,name,email,phone,address_1,address_2,city,postcode AS warehouse_name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.warehouse_id");
	}
	
	public function getWarehouseByCompanyId($param){
		if($param['user_code']=='company'){
			return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id),latitude,longitude,name,email,phone,address_1,address_2,city,postcode FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.warehouse_id WHERE t2.company_id = ".$param["company_id"]);
		}
		else{
			return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id),latitude,longitude,name,email,phone,address_1,address_2,city,postcode FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.warehouse_id INNER JOIN ".DB_PREFIX."company_users AS t3 ON t1.id = t3.warehouse_id WHERE t2.company_id = ".$param["company_id"]." AND t3.user_id=".$param['user_id']);
		}		
	}
	
	public function getVehicleByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t2.category_name,plate_no,model,color,brand,max_weight,max_width,max_height,max_length,max_volume,t1.id FROM ".DB_PREFIX."vehicle as t1 INNER JOIN ".DB_PREFIX."vehicle_category AS t2 ON t1.category_id = t2.id WHERE t1.company_id = ".$param["company_id"]);
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
	
	public function getVehicleCompanyData($param){
		return $this->_parentObj->db->getRowRecord("SELECT t1.id,t1.name FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."vehicle AS t2 ON t1.id = t2.company_id where t2.id = ".$param->vehicle_id." AND user_level=2");		
    }
   
    public function getWarehouseDataByWarehouseId($param){
		return $this->_parentObj->db->getRowRecord("SELECT name,email,phone,address_1,address_2,city,postcode,state,country,id FROM ".DB_PREFIX."warehouse WHERE id = ".$param->id."");
    }
	
	public function getVehicleDataByVehicleId($param){
		return $this->_parentObj->db->getRowRecord("SELECT t2.category_name,plate_no,model,color,brand,max_weight,max_width,max_height,max_length,max_volume FROM ".DB_PREFIX."vehicle as t1 INNER JOIN ".DB_PREFIX."vehicle_category AS t2 ON t1.category_id = t2.id WHERE t1.id = ".$param->id."");
    }
    
    private function _getIncompleteModuleData(){
        $setupObj = new Setup((object) array("email"=>$this->_getEmail(), "access_token"=>$this->_getAccessToken(), "user_id"=>$this->_getCompanyId()));
        $setup_response = $setupObj->getDefaultRegistrationData();
        return $setup_response;
    }
	
	private function _getWarehouseList(){
        $records = $this->db->getAllRecords("SELECT WT.`id` AS `warehouse_id`, `name` AS `warehouse_name` FROM ".DB_PREFIX."warehouse as WT INNER JOIN ".DB_PREFIX."company_warehouse AS CWT ON CWT.warehouse_id = WT.id WHERE CWT.`company_id`='".$this->_company_id."' AND CWT.`status`=1");
		return $records;
    }
    
    public function addWareHouse($param){
        $libObj = new Library();
		$postCodeObj = new Postcode();
		$isValidPostcode = $postCodeObj->validate($param->postcode);
		if($isValidPostcode){
			$latLngArr = $libObj->get_lat_long_by_postcode($param->postcode);
			//if($latLngArr['longitude']!='' || $latLngArr['longitude']!=''){
				$insertData = array("phone"=>$param->phone,"name"=>$param->name,"email"=>$param->user_email,"postcode"=>$param->postcode,"address_1"=>$param->address_1,"address_2"=>$param->address_2,"city"=>$param->city,"state"=>$param->state,"country"=>$param->country,"latitude"=>$latLngArr['latitude'],"longitude"=>$latLngArr['longitude']);
				$column_names = array('phone', 'name', 'email', 'postcode','address_1','address_2','city','state','country','latitude','longitude');    
				$warehouse_id = $this->_parentObj->db->insertIntoTable($insertData, $column_names, DB_PREFIX."warehouse");
				if ($warehouse_id != NULL) {
					$relationData = array('company_id'=>$param->company_id,'warehouse_id'=>$warehouse_id);
					$column_names = array('company_id', 'warehouse_id');
					$relationTblEntry = $this->_parentObj->db->insertIntoTable($relationData, $column_names, DB_PREFIX."company_warehouse");
					if($relationTblEntry!= NULL){
						if(isset($param->source)){
							$setupData = array('company_id'=>$param->company_id,'module_code'=>'warehouse');
							$column_names = array('company_id', 'module_code');
							$isSetupDataExist = $this->_parentObj->db->getOneRecord("SELECT COUNT(1) AS exist FROM ".DB_PREFIX."company_default_registration_setup WHERE company_id=".$param->company_id." AND module_code='warehouse'");
							if($isSetupDataExist['exist']>1){
								
								$sql = "DELETE FROM " . DB_PREFIX . "company_default_registration_setup WHERE company_id=".$param->company_id." AND module_code='warehouse'";
								
								$deleteEntry = $this->_parentObj->db->delete($sql);
								
								$setupTblEntry = $this->_parentObj->db->insertIntoTable($setupData, $column_names, DB_PREFIX."company_default_registration_setup");
							}
							else{
								$setupTblEntry = $this->_parentObj->db->insertIntoTable($setupData, $column_names, DB_PREFIX."company_default_registration_setup");		
							}	
						}
						$insertData['id'] = $warehouse_id;
						$insertData['action'] = $warehouse_id;
						$insertData['address'] = $param->address_1.' '.$param->address_2;
						$response["status"] = "success";
						$response["message"] = "Warehouse created successfully";
						$response["saved_record"] = $insertData;
					}else {
						$response["status"] = "error";
						$response["message"] = "Failed to create warehouse. Please try again";
					} 
				}
			/* }
			else{
				$response["status"] = "error";
				$response["message"] = "Failed to create warehouse.Geo position of supplied postcode is not found please supply valid postcode.";
			} */
		}else{
			$response["status"] = "error";
            $response["message"] = "Invalid postcode";	
		}
        return $response;
	}
    
    public function editWarehouse($param){
        $updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."warehouse SET name='$param->name',phone='$param->phone',address_1='$param->address_1',address_2='$param->address_2',postcode='$param->postcode',city='$param->city',country='$param->country',state='$param->state' WHERE id = '$param->id'");
        if ($updateData) {
            $response["status"] = "success";
            $response["message"] = "Warehouse details updated successfully";
        }else {
            $response["status"] = "error";
            $response["message"] = "Failed to update warehouse details. Please try again";
        }
        return $response;
	}
    
	public function addController($param){
            $data = array('name'=>$param->name,'phone'=>$param->phone,'address_1'=>$param->address_1,'address_2'=>$param->address_2,'city'=>$param->city,'postcode'=>$param->postcode,'state'=>$param->state,'country'=>$param->country);

            $condition = 'id='.$param->company->id;
            $update = $this->_parentObj->db->update("users",$data,$condition);
            
            
			if ($update != NULL) {
					$response["status"] = "success";
					$response["message"] = "Controller created successfully";  
			}else{
				$response["status"] = "error";
				$response["message"] = "Failed to create controller. Please try again";
			}
		
        return $response;
	}

    
    public function save($param){
        switch($param->source){
            case "warehouse" :
                $data = $this->addWareHouse($param);
                $temp = $this->_getIncompleteModuleData();
                $data["incomplete_modules"] = $temp['setup_data'];
                $data["setup_status"] = $temp['setup_status'];
				$data["warehouse_lists"] = $this->_getWarehouseList();
                break;    
            
            case "controller" :
			    $data = $this->addController($param);
                $temp = $this->_getIncompleteModuleData();
                $data["incomplete_modules"] = $temp['setup_data'];
                $data["setup_status"] = $temp['setup_status'];
				$data["warehouse_lists"] = $this->_getWarehouseList();
                break;
				
			case "addcontroller" :
			    $controllerObj = new Controller($param);
			    $data = $controllerObj->addController($param);
                $temp = $this->_getIncompleteModuleData();
                //$data["incomplete_modules"] = $temp['setup_data'];
                //$data["setup_status"] = $temp['setup_status'];
				$data["warehouse_lists"] = $this->_getWarehouseList();
                break;	
                
            case "driver" :
                $driverObj = new Driver($param);
                $data = $driverObj->addDriver($param);
                $temp = $this->_getIncompleteModuleData();
                $data["incomplete_modules"] = $temp['setup_data'];//$this->_getIncompleteModuleData();
                $data["setup_status"] = $temp['setup_status'];
				$data["warehouse_lists"] = $this->_getWarehouseList();
                break;  
                
            case "route":
            
                $routeObj = new Route($param);
                $data = $routeObj->addRoute($param);
                $temp = $this->_getIncompleteModuleData();
                $data["incomplete_modules"] = $temp['setup_data'];
                $data["setup_status"] = $temp['setup_status'];
				$data["warehouse_lists"] = $this->_getWarehouseList();
                break;
                
            case "vehicle":
                $routeObj = new Vehicle($param);
	            $data = $routeObj->addVehicle($param);
                $temp = $this->_getIncompleteModuleData();
                $data["incomplete_modules"] = $temp['setup_data'];
                $data["setup_status"] = $temp['setup_status'];
				$data["warehouse_lists"] = $this->_getWarehouseList();
                break;
        }
        return $data;
	}
}
?>