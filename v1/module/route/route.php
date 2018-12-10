<?php
class Route extends Icargo{
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
	
	/*start of all other queries of route modeule*/
	
	public function getRoutePostcodeByRouteId($param){
		return $this->_parentObj->db->getAllRecords("SELECT id,postcode FROM ".DB_PREFIX."route_postcode WHERE route_id = ".$param->route_id."");
	}
	
	public function getAllRouteDataByWarehouseId($param){
        return $this->_parentObj->db->getAllRecords("SELECT t1.id,t1.name,t1.company_id,t1.warehouse_id,t2.locality,t2.city FROM ".DB_PREFIX."routes as t1
        LEFT JOIN ".DB_PREFIX."route_locality AS t2 ON t2.route_id=t1.id WHERE t1.warehouse_id = ".$param->warehouse_id."");
		//return $this->_parentObj->db->getAllRecords("SELECT id,name FROM ".DB_PREFIX."routes WHERE warehouse_id = ".$param->warehouse_id);
	}
	
	public function removePostcode($param){
		return $this->_parentObj->db->updateData("DELETE FROM ".DB_PREFIX."route_postcode WHERE id = ".$param->postcodeId."");
	}
	
	public function getRouteDataByRouteId($param){
		return $this->_parentObj->db->getRowRecord("SELECT t1.id,t1.name,t1.company_id,t1.warehouse_id,t2.locality,t2.city FROM ".DB_PREFIX."routes as t1
        LEFT JOIN ".DB_PREFIX."route_locality AS t2 ON t2.route_id=t1.id WHERE t1.id = ".$param->route_id."");
	}
	
	public function getRouteDataByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id,t1.name,t1.company_id,t1.warehouse_id,t2.locality,t2.city FROM ".DB_PREFIX."routes as t1
        LEFT JOIN ".DB_PREFIX."route_locality AS t2 ON t2.route_id=t1.id WHERE t1.company_id=".$param->company_id."");
	}
	
	public function getRouteDataByCompanyAndWarehouseId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id,t1.name,t1.company_id,t1.warehouse_id,t2.locality,t2.city FROM ".DB_PREFIX."routes as t1
        LEFT JOIN ".DB_PREFIX."route_locality AS t2 ON t2.route_id=t1.id WHERE t1.company_id=".$param->company_id." AND t1.warehouse_id=".$param->warehouse_id."");
	}
    
	public function getAllRouteData($param){
        return $this->_parentObj->db->getAllRecords("SELECT t1.id,t1.name,t1.company_id,t1.warehouse_id,t2.locality,t2.city FROM ".DB_PREFIX."routes as t1
        INNER JOIN ".DB_PREFIX."route_locality AS t2 ON t2.route_id=t1.id");
		//return $this->_parentObj->db->getAllRecords("SELECT id,name FROM ".DB_PREFIX."routes WHERE warehouse_id = ".$param->warehouse_id);
	}
	
    /*public function getRouteDataByRouteId_($param){
		return $this->_parentObj->db->getRowRecord("SELECT name,company_id,warehouse_id FROM ".DB_PREFIX."routes WHERE id = ".$param->route_id."");
	}*/
	
    public function addRoute($param){
        $company_id = $param->company_id;
        $warehouse_id = $param->warehouse->warehouse_id;
        
		$data = array('name'=>$param->name,'company_id'=>$company_id,'warehouse_id'=>$warehouse_id,'create_date'=>"NOW()");
		$column_names = array('name','company_id','warehouse_id');
        $temp_postcode_id = array();
        $rejected_postcodes = array(); 
        
        if(isset($param->route_postcode)){
            $postcodes = explode(',', $param->route_postcode);
            
            foreach($postcodes as $postcode){
                // check company already saved the postcode or not
                $saved_postcode = $this->_parentObj->db->getRowRecord("SELECT postcode FROM " . DB_PREFIX . "route_postcode WHERE `company_id` = '$company_id' AND `postcode` = '$postcode'");
                
                if($saved_postcode==null){
                    $data = array('route_id'=>0,'postcode'=>$postcode,'company_id'=>$company_id,'warehouse_id'=>$warehouse_id);
                    $id = $this->_parentObj->db->save("route_postcode", $data);
                    array_push($temp_postcode_id, $id);
                }
                else{
                    //reject the postcode because one company set only one postcode for service
                    array_push($rejected_postcodes, $postcode);
                }
            }
        }
		if(count($rejected_postcodes)==0){
			$sql = "INSERT INTO `" . DB_PREFIX . "routes` SET `name` = '$param->name',`company_id` = '$company_id',`warehouse_id` = '$warehouse_id',`create_date` = NOW()";
			$route_id = $this->_parentObj->db->executeQuery($sql);
			if($route_id != NULL) {
				if((isset($param->locality) AND ($param->locality!='')) || (isset($param->city) AND ($param->city!=''))){
					$data = array('route_id'=>$route_id,'locality'=>$param->locality,'city'=>$param->city);
					$saveData = $this->_parentObj->db->save("route_locality", $data);
				}
				
				if(count($temp_postcode_id)>0){
					$sql = "UPDATE " . DB_PREFIX . "route_postcode SET route_id='$route_id' WHERE id IN(" . implode(',', $temp_postcode_id) . ")";
					$route_id = $this->_parentObj->db->updateData($sql);
				}
				if(isset($param->source)){
					$sql = "DELETE FROM " . DB_PREFIX . "company_default_registration_setup WHERE company_id=".$company_id." AND module_code='route'";            
					$this->_parentObj->db->delete($sql);
					$this->_parentObj->db->save("company_default_registration_setup",array('company_id'=>$company_id,'module_code'=>'route'));
				}
				
				$response["status"] = "success";
				$response["message"] = "Route created successfully";
				$response["rejected_postcodes"] = $rejected_postcodes;
				$response["saved_record"] = array('id'=>$route_id,'action'=>$route_id,'name'=>$param->name);
			}
		}else{
			if(count($temp_postcode_id)>0){
					$sql = "DELETE FROM " . DB_PREFIX . "route_postcode WHERE id IN(" . implode(',', $temp_postcode_id) . ")";
					$route_id = $this->_parentObj->db->delete($sql);
			}
			$response["status"] = "error";
			$rejectedPostcode = implode(',',$rejected_postcodes);
			$response["form_error"] = array('status'=>'error','message'=>'Route consists duplicate postcode - '.$rejectedPostcode);
			$response["message"] = "Failed to create route. Please try again";
		}
		return $response;
	}
    
	public function addRouteBKP($param){
		$data = array('name'=>$param->name,'company_id'=>$param->company->id,'warehouse_id'=>$param->warehouse->id,'create_date'=>"NOW()");
		$column_names = array('name','company_id','warehouse_id');
        
        $company_id = $param->company->id;
        $warehouse_id = $param->warehouse->id;
        
        $sql = "INSERT INTO `icargo_routes` SET `name` = '$param->name',`company_id` = '$company_id',`warehouse_id` = '$warehouse_id',`create_date` = NOW()";
        //$route_id = $this->_parentObj->db->save("routes", $data);
        $route_id = $this->_parentObj->db->updateData($sql);
        
        if ($route_id != NULL) {
			$column_names = array('route_id','postcode','company_id','warehouse_id');
            
            if(isset($param->route_postcode)){
                $postcodes = explode(',', $param->route_postcode);
                foreach($postcodes as $postcode){
                    $relationData = array('route_id'=>$route_id,'postcode'=>$postcode,'company_id'=>$param->company->id,'warehouse_id'=>$param->warehouse->id);
                    $relationTblEntry = $this->_parentObj->db->insertIntoTable($relationData, $column_names, DB_PREFIX."route_postcode");
                }
            }
			
			if($relationTblEntry!= NULL){
                $sql = "DELETE FROM " . DB_PREFIX . "company_default_registration_setup WHERE company_id=".$param->company->id." AND module_code='route'";
                            
                $deleteEntry = $this->_parentObj->db->delete($sql);
                            
                $this->_parentObj->db->save("company_default_registration_setup",array('company_id'=>$param->company->id,'module_code'=>'route'));
                
				$response["status"] = "success";
				$response["message"] = "Route created successfully";
			}else {
				$response["status"] = "error";
				$response["message"] = "Failed to create route. Please try again";
			}        
		}else{
			$response["status"] = "error";
			$response["message"] = "Failed to create route. Please try again";
		}
		return $response;
	}
	
	public function addPostcode($param){
		$column_names = array('route_id','postcode','company_id','warehouse_id');
		foreach($param->postcode->postcode as $postcode){
			$data = array('route_id'=>$param->route_id,'postcode'=>$postcode,'company_id'=>$param->company_id,'warehouse_id'=>$param->warehouse_id);
			$insert = $this->_parentObj->db->insertIntoTable($data, $column_names, DB_PREFIX."route_postcode");
		}
		if($insert!= NULL){
			$response["status"] = "success";
			$response["message"] = "Postcode added successfully";
			echoResponse(200, $response);
		}else {
			$response["status"] = "error";
			$response["message"] = "Failed to add postcode. Please try again";
			echoResponse(201, $response);
		}        	
	}
	
	public function editRoute($param){
            $updateFlag = false;
			$updateRouteData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."routes SET name='".$param->name."' WHERE id = ".$param->route_id."");
            if($updateRouteData!=NULL)
                $updateFlag = true;
            $updateLocalityData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."route_locality SET city='".$param->city."',locality='".$param->locality."' WHERE route_id = ".$param->route_id."");
            if($updateLocalityData!=NULL)
                $updateFlag = true;
            
            $postcodeExist = $this->_parentObj->db->getAllRecords("SELECT count(0) as exist FROM ".DB_PREFIX."route_postcode WHERE route_id = ".$param->route_id."");
             
            if($postcodeExist[0]['exist']>0){
               $this->_parentObj->db->delete("DELETE FROM ".DB_PREFIX."route_postcode WHERE route_id = ".$param->route_id.""); 
            }
            //echo "DELETE FROM ".DB_PREFIX."route_postcode WHERE route_id = ".$param->route_id."";die;
            $postcodes = explode(',',$param->route_postcode);
            
            foreach($postcodes as $postcode){
                        $data = array("route_id"=>$param->route_id,"postcode"=>$postcode,"company_id"=>$param->company_id,"warehouse_id"=>$param->warehouse->warehouse_id);
                        $insertPostcode = $this->_parentObj->db->save("route_postcode",$data);
            } 
            if($insertPostcode!=NULL)
                $updateFlag = true;
        
            if($updateFlag==true){
                        $response["status"] = "success";
				        $response["message"] = "Route details updated successfully";
				        //echoResponse(200, $response);
            }else{
                $response["status"] = "error";
                $response["message"] = "Failed to update route details. Please try again";
                //echoResponse(201, $response);
              }
        return $response;
			/*if ($updateData != NULL) {
				$response["status"] = "success";
				$response["message"] = "Route details updated successfully";
				echoResponse(200, $response);
			}else {
				$response["status"] = "error";
				$response["message"] = "Failed to update route details. Please try again";
				echoResponse(201, $response);
			}  */      
	}
	public function resolvePostcode($shipmentId,$postCode){
	    require_once __DIR__.'/../../library.php';
	    $library=new Library();
	    //$latlang=$library->get_lat_long_by_postcode($postCode);
        $latlang=$library->get_lat_long_by_address_for_resolve_route($postCode.',UK');
	    if($latlang['status']=='success'){
	        $data="shipment_latitude='".$latlang['latitude']."', shipment_longitude='".$latlang['longitude']."', error_flag=0";
            $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."shipment SET ".$data." WHERE shipment_id=$shipmentId");
            echo json_encode(array("success"=>true,'location'=>$latlang));
        }else{
            echo json_encode(array("success"=>false,'location'=>$latlang));
        }
    }
}
?>