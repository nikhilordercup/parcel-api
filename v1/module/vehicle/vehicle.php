<?php
class Vehicle extends Icargo{
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
	}
	
	public function getAssignedDriverAndVehicle(){
		return $this->_parentObj->db->getAllRecords("SELECT driver_id,vehicle_id FROM ".DB_PREFIX."driver_vehicle");
	}
	
	public function getAssignedDriverAndVehicleByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT driver_id,vehicle_id FROM ".DB_PREFIX."driver_vehicle as t1 LEFT JOIN ".DB_PREFIX."company_users AS t2 ON t2.user_id=t1.driver_id WHERE t2.company_id=".$param->company_id."");
	}
	
	public function getDriversWithNoVehicles($id){
		if($id!='')
		  $condition = " AND t1.id NOT IN ($id)";
		else 
		$condition = ""; 
		return $this->_parentObj->db->getAllRecords("SELECT id,name FROM ".DB_PREFIX."users AS t1 WHERE t1.user_level = 4 AND t1.status=1$condition");
	}
	
	public function getVehicleCategoryData(){
		return $this->_parentObj->db->getAllRecords("SELECT id,category_name FROM ".DB_PREFIX."vehicle_category WHERE status=1");
	}
	
	public function getVehicleCategoryDataByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT id,category_name FROM ".DB_PREFIX."vehicle_category WHERE status=1 AND company_id=".$param->company_id."");
	}
	
	public function getVehicleCategoryDataById($id){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id,t1.category_name FROM ".DB_PREFIX."vehicle_category as t1 LEFT JOIN ".DB_PREFIX."vehicle as t2 ON t1.id = t2.category_id WHERE t2.id=$id group by t1.id");
	}
	
	public function getFreeVehiclesOLD($catId,$vehicleId,$company_id=''){
		if($company_id!=''){
			if($vehicleId!=''){
				return $this->_parentObj->db->getAllRecords("SELECT *,t1.id as vehicle_id,t2.category_name FROM icargo_vehicle AS t1 LEFT JOIN ".DB_PREFIX."vehicle_category as t2 ON t1.category_id = t2.id WHERE t1.status=1 AND t1.category_id IN($catId) AND t2.company_id = $company_id AND t1.id NOT IN($vehicleId)");
			}else{
				return $this->_parentObj->db->getAllRecords("SELECT *,t1.id as vehicle_id,t2.category_name FROM ".DB_PREFIX."vehicle AS t1 LEFT JOIN ".DB_PREFIX."vehicle_category as t2 ON t1.category_id = t2.id WHERE t1.status=1  AND t2.company_id = $company_id AND t1.category_id IN($catId)");
			}		
		}else{
			if($vehicleId!='')
				return $this->_parentObj->db->getAllRecords("SELECT *,t1.id as vehicle_id,t2.category_name FROM icargo_vehicle AS t1 LEFT JOIN ".DB_PREFIX."vehicle_category as t2 ON t1.category_id = t2.id WHERE t1.status=1 AND t1.category_id IN($catId) AND t1.id NOT IN($vehicleId)");
			else
				return $this->_parentObj->db->getAllRecords("SELECT *,t1.id as vehicle_id,t2.category_name FROM ".DB_PREFIX."vehicle AS t1 LEFT JOIN ".DB_PREFIX."vehicle_category as t2 ON t1.category_id = t2.id WHERE t1.status=1 AND t1.category_id IN($catId)");	
		}
	}

    public function getFreeVehicles($catId,$vehicleId,$company_id=''){
        if($company_id!=''){
            if($vehicleId!='' AND $catId!=''){
                return $this->_parentObj->db->getAllRecords("SELECT *,t1.id as vehicle_id,t2.category_name FROM icargo_vehicle AS t1 LEFT JOIN ".DB_PREFIX."vehicle_category as t2 ON t1.category_id = t2.id WHERE t1.status=1 AND t1.category_id IN($catId) AND t2.company_id = $company_id AND t1.id NOT IN($vehicleId)");
            }else{
                return $this->_parentObj->db->getAllRecords("SELECT *,t1.id as vehicle_id,t2.category_name FROM ".DB_PREFIX."vehicle AS t1 LEFT JOIN ".DB_PREFIX."vehicle_category as t2 ON t1.category_id = t2.id WHERE t1.status=1  AND t2.company_id = $company_id");
            }
        }else{
            if($vehicleId!='' AND $catId!='')
                return $this->_parentObj->db->getAllRecords("SELECT *,t1.id as vehicle_id,t2.category_name FROM icargo_vehicle AS t1 LEFT JOIN ".DB_PREFIX."vehicle_category as t2 ON t1.category_id = t2.id WHERE t1.status=1 AND t1.category_id IN($catId) AND t1.id NOT IN($vehicleId)");
            else
                return $this->_parentObj->db->getAllRecords("SELECT *,t1.id as vehicle_id,t2.category_name FROM ".DB_PREFIX."vehicle AS t1 LEFT JOIN ".DB_PREFIX."vehicle_category as t2 ON t1.category_id = t2.id WHERE t1.status=1");
        }
    }

	
	
	public function addVehicle($param){
        $categoryExist = $this->_parentObj->db->getAllRecords("SELECT count(0) as exist,id FROM `icargo_vehicle_category` where category_name='".strtolower($param->vehicle_category)."' AND company_id = ".$param->company_id." group by id");
        if(count($categoryExist)>0){
			foreach($categoryExist as $val){
				$category_id = $val['id'];
			}
		}else{
			$insertData = array('category_name'=>strtolower($param->vehicle_category),'company_id'=>$param->company_id,'status'=>1);
			$category_id = $this->_parentObj->db->save("vehicle_category", $insertData);
		}
		/*foreach($categoryExist as $val){
			if($val['exist']>0){
				$category_id = $val['id'];
			}else{
				$insertData = array('category_name'=>strtolower($param->vehicle_category),'company_id'=>$param->company_id);
				$category_id = $this->_parentObj->db->save("vehicle_category", $insertData);
			}
		}*/
		if($category_id!= NULL){
			$columnData = array('company_id'=>$param->company_id,'category_id'=>$category_id,'plate_no'=>$param->plate_no,'model'=>$param->model,'brand'=>$param->brand,'color'=>$param->color,'max_weight'=>$param->max_weight,'max_width'=>$param->max_width,'max_height'=>$param->max_height,'max_length'=>$param->max_length,'max_volume'=>$param->max_volume);
			
			//$vehicle_id = $this->_parentObj->db->insertIntoTable($columnData, $column_names, DB_PREFIX."vehicle");
			$vehicle_id = $this->_parentObj->db->save("vehicle", $columnData);
			
			if($vehicle_id!= NULL){
				if(isset($param->source)){
                    $sql = "DELETE FROM " . DB_PREFIX . "company_default_registration_setup WHERE company_id=".$param->company_id." AND module_code='vehicle'";

                    $deleteEntry = $this->_parentObj->db->delete($sql);

                    $this->_parentObj->db->save("company_default_registration_setup",array('company_id'=>$param->company_id,'module_code'=>'vehicle'));
                }
                    $columnData['id'] = $vehicle_id;
                    $columnData['action'] = $vehicle_id;
                    $columnData['maxweight'] = $param->max_weight;
                    $columnData['maxwidth'] = $param->max_width;
                    $columnData['maxheight'] = $param->max_height;
                    $columnData['maxlength'] = $param->max_length;
                    $columnData['maxvolume'] = $param->max_volume;
                    $columnData['plateno'] = $param->plate_no;
                    $columnData['category'] = strtolower($param->vehicle_category);
                    $response["status"] = "success";
                    $response["message"] = "Vehicle added successfully";
                    $response["saved_record"] = $columnData;
            }
			}else {
				$response["status"] = "error";
				$response["message"] = "Failed to add vehicle. Please try again";
			}
        return $response;
	}
    
	public function assignVehicle($param){
		$column_names = array('driver_id','vehicle_id','vehicle_category_id');
		
		$columnData = array('driver_id'=>$param->driver->id,'vehicle_id'=>$param->vehicle_id->id,'vehicle_category_id'=>$param->selceted_category);
		
		$insert = $this->_parentObj->db->insertIntoTable($columnData, $column_names, DB_PREFIX."driver_vehicle");
		if($insert!= NULL){
			$response["status"] = "success";
			$response["message"] = "Vehicle assigned successfully";
			echoResponse(200, $response);
		}else {
			$response["status"] = "error";
			$response["message"] = "Failed to assign vehicle. Please try again";
			echoResponse(201, $response);
		} 
	}
	
	public function editVehicle($param){
        $updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."vehicle SET plate_no='".$param->plate_no."',model='".$param->model."',color='".$param->color."',brand='".$param->brand."',max_width='".$param->max_width."',max_height='".$param->max_height."',max_length='".$param->max_length."',max_weight='".$param->max_weight."',max_volume='".$param->max_volume."' WHERE id = ".$param->id."");
			if ($updateData != NULL) {
				$response["status"] = "success";
				$response["message"] = "Vehicle details updated successfully";
			}else {
				$response["status"] = "error";
				$response["message"] = "Failed to update vehicle details. Please try again";
			}
            return $response;
	}
}
?>