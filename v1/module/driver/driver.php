<?php
class Driver extends Icargo{ 
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
	}
	
	/*start of company list related query*/
	
	public function getActiveCompanyList(){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS company_id, t1.name AS company_name FROM ".DB_PREFIX."users AS t1 WHERE t1.status = 1 AND t1.user_level = 2 ORDER BY company_name");
	}
	public function getActiveCompanyListByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS company_id, t1.name AS company_name FROM ".DB_PREFIX."users AS t1 WHERE t1.id = ".$param['company_id']." AND t1.status = 1");
	}
	
	public function getActiveCompanyListByControllerId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS company_id, t1.name AS company_name FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."warehouse_controller AS t2 ON t1.id = t2.company_id WHERE t2.controller_id = ".$param["controller_id"]." AND t1.status = 1");
	}
	
	/*start of warehouse list related query*/
	
	public function getWarehouseListByComapnyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(WT.id) AS warehouse_id, WT.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS WT INNER JOIN ".DB_PREFIX."warehouse_controller AS WCT ON WCT.warehouse_id=WT.id WHERE WCT.company_id=".$param->company_id."");
	}
	public function getActiveWareHouseListByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS warehouse_id, t1.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.warehouse_id WHERE t2.company_id = ".$param["company_id"]." AND t1.status = 1");
	}
	
	public function getActiveWareHouseListByControllerId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS warehouse_id, t1.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."warehouse_controller AS t2 ON t1.id = t2.warehouse_id WHERE t2.controller_id = ".$param["controller_id"]." AND t1.status = 1");
	}
	
	public function getWarehouseListByUserId($param){
		return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id) AS warehouse_id, t1.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t1.id = t2.warehouse_id WHERE t2.user_id = ".$param->user_id."");
	}
	
	public function getAllActiveWarehouse(){
		return $this->_parentObj->db->getAllRecords("SELECT id AS warehouse_id, name AS warehouse_name FROM ".DB_PREFIX."warehouse WHERE status = 1");
	}
	
	/*start of controller list related query*/
	
	public function getAllActiveControllerList($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS controller_id, t1.name AS controller_name FROM ".DB_PREFIX."users AS t1 WHERE t1.status = 1 AND t1.user_level = 3");
	}
	
	public function getControllerDataByWarehouseId($param){
		return $this->_parentObj->db->getAllRecords("SELECT name,email,phone,address_1,address_2,city,postcode,UT.id FROM ".DB_PREFIX."users AS UT INNER JOIN ".DB_PREFIX."warehouse_controller AS WCT ON WCT.warehouse_id=UT.id WHERE UT.user_level=3 AND WCT.warehouse_id=".$param->warehouse_id."");
	}
	
	public function getControllerListByComapnyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT UT.id AS controller_id, UT.name AS controller_name FROM ".DB_PREFIX."users AS UT INNER JOIN ".DB_PREFIX."warehouse_controller AS WCT ON WCT.controller_id=UT.id WHERE UT.user_level=3 AND WCT.warehouse_id=".$param->warehouse_id."");
	}
	
	/*start of all other queries of driver modeule*/
	
	public function getUserDataByUserId($param){
		return $this->_parentObj->db->getAllRecords("SELECT name,email,phone,address_1,address_2,city,postcode,id FROM ".DB_PREFIX."users WHERE id = ".$param->user_id." AND user_level = ".$param->user_level."");
	}
	
	public function getAllDriverDataByWarehouseId($param){
        return $this->_parentObj->db->getAllRecords("SELECT t1.id,name,email,phone,address_1,address_2,city,postcode FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t2.user_id=t1.id WHERE t2.warehouse_id = ".$param->warehouse_id." AND user_level = 4");
	}
	
	public function getAllDriverData($param){
        return $this->_parentObj->db->getAllRecords("SELECT t1.id,name,email,phone,address_1,address_2,city,postcode FROM ".DB_PREFIX."users AS t1 WHERE user_level = 4 AND status = 1");
	}
    
	public function getDriverDataByCompanyId($param){
        return $this->_parentObj->db->getAllRecords("SELECT t1.id,name,email,phone,address_1,address_2,city,postcode,t1.access_token FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t2.user_id=t1.id WHERE t2.company_id = ".$param->company_id." AND user_level = 4");
	}
	
	public function getDriverDataByCompanyAndWarehouseId($param){
        return $this->_parentObj->db->getAllRecords("SELECT t1.id,name,email,phone,address_1,address_2,city,postcode,t1.access_token FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t2.user_id=t1.id WHERE t2.company_id = ".$param->company_id." AND t2.warehouse_id = ".$param->warehouse_id." AND user_level = 4 AND t1.status = 1");
	}
	
	public function getDriverAllDataByCompanyId($param){
        return $this->_parentObj->db->getAllRecords("SELECT t1.id,name,email,phone,address_1,address_2,city,postcode,t1.access_token,count(t3.route_id) as task FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t2.user_id=t1.id LEFT JOIN ".DB_PREFIX."shipment_route AS t3 ON t3.driver_id=t1.id WHERE t2.company_id = ".$param->company_id." AND user_level = 4 AND t3.is_active = 'Y'");
	}
	
    public function getDriverByDriverId($driver_id){
        return $this->_parentObj->db->getRowRecord("SELECT t1.id,name,email,phone,address_1,address_2,city,postcode,access_token,uid FROM ".DB_PREFIX."users AS t1 WHERE id = '$driver_id'");
    }
	
	private function getAllDriversByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id,name,access_token,uid FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t2.user_id=t1.id WHERE t2.company_id = ".$param->company_id." AND user_level = 4 AND t1.status = 1");
	}
	
	public function getAllDrivers($param){
		$records = $this->getAllDriversByCompanyId($param);
		foreach($records as $key=>$record){
			if($record['access_token']!=''){
				$records[$key]['status'] = 'online';
			}else{
				$records[$key]['status'] = 'offline';
			}
		}
		return $records;
	}
	
	public function getDriverId($param){
        if(isset($param->driver_id)){
            $record = $this->getDriverByDriverId($param->driver_id);
            $record['status'] = 'offline';
            if(isset($record["access_token"]) AND $record["access_token"]!=""){
                $record['status'] = 'online';
            }
            return $record;
        }
        return array();
    }

    
    /*public function getActiveDrivers($param){
		$drivers = $this->getDriverByDriverId($param);
		$data = array();
		//foreach($drivers as $driver){
			//$taskData = $this->getDriverTaskCount($param);
			//$driver['tasks'] = $taskData;
			//$driver['task_count'] = count($taskData);
			//array_push($data,$driver);
		//}
		print_r($data);die;
		return $data;
    }*/

	public function addDriver($param){
        $savedrecord = array();
        $isDriverExists = $this->_parentObj->db->getOneRecord("select 1 from icargo_users where email='".$param->user_email."'");
		if(!$isDriverExists){
			$param->password = passwordHash::hash($param->password);
			$param->user_level = 4;
			$param->register_in_firebase = 1;
            $data = array('parent_id'=>$param->company_id,'name'=>$param->name,'contact_name'=>$param->name,'phone'=>$param->phone,'email'=>$param->user_email,'password'=>$param->password,'address_1'=>$param->address_1,'address_2'=>$param->address_2,'city'=>$param->city,'postcode'=>$param->postcode,'user_level'=>$param->user_level,'uid'=>$param->uid,'register_in_firebase'=>$param->register_in_firebase,'state'=>$param->state,'country'=>$param->country);
            
			//$driver_id = $this->_parentObj->db->insertIntoTable($param, $column_names, DB_PREFIX."users");
            $driver_id = $this->_parentObj->db->save("users", $data);
            
			if ($driver_id != NULL) {
				$relationData = array('company_id'=>$param->company_id,'warehouse_id'=>$param->warehouse_id,'user_id'=>$driver_id);
				$column_names = array('company_id','warehouse_id','user_id');
				$relationTblEntry = $this->_parentObj->db->insertIntoTable($relationData, $column_names, DB_PREFIX."company_users");
				$assignVehicle = $this->_parentObj->db->save("driver_vehicle",array('driver_id'=>$driver_id,'vehicle_id'=>$param->vehicle_id->id,'vehicle_category_id'=>$param->vehicle_id->vehicle_category_id));
				if($assignVehicle==NULL){
					$response["status"] = "error";
					$response["message"] = "Failed to assign vehicle to driver. Please try again";
				}
				
				if($relationTblEntry!= NULL){
					if(isset($param->source)){
						$sql = "DELETE FROM " . DB_PREFIX . "company_default_registration_setup WHERE company_id=".$param->company_id." AND module_code='driver'";
						$deleteEntry = $this->_parentObj->db->delete($sql);
						$this->_parentObj->db->save("company_default_registration_setup",array('company_id'=>$param->company_id,'module_code'=>'driver'));
					}
                    $data['id'] = $driver_id;
                    $data['action'] = $driver_id;
                    $data['address'] = $param->address_1.' '.$param->address_2;
					$response["status"] = "success";
					$response["message"] = "Driver created successfully";
                    $response["saved_record"] = $data;//$this->getDriverByDriverId($driver_id);
				}else {
					$response["status"] = "error";
					$response["message"] = "Failed to create driver. Please try again";
				}        
			}else{
				$response["status"] = "error";
				$response["message"] = "Failed to create driver. Please try again";
			}
		}else{
			$response["status"] = "error";
			$response["message"] = "Driver with the provided email already exists!";
		}
        return $response;
	}
	
	public function editDriver($param){//print_r($param);die;
        $updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."users SET name='".$param->name."',phone='".$param->phone."',address_1='".$param->address_1."',address_2='".$param->address_2."',postcode='".$param->postcode."',city='".$param->city."',state='".$param->state."',country='".$param->country."' WHERE id = ".$param->id."");
		$assignVehicleUpdate = $this->_parentObj->db->updateData("UPDATE `".DB_PREFIX."driver_vehicle` SET `vehicle_id` = ".$param->vehicle_id->id.",`vehicle_category_id` = ".$param->vehicle_id->vehicle_category_id." WHERE `driver_id` = ".$param->id."");
		if ($updateData != NULL) {
            $response["status"] = "success";
            $response["message"] = "Driver details updated successfully";
        }else {
            $response["status"] = "error";
            $response["message"] = "Failed to update driver details. Please try again";
        }
        return $response;
	}
}
?>