<?php
class Controller extends Icargo{
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
	
	public function getActiveWareHouseListByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS warehouse_id, t1.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.warehouse_id WHERE t2.company_id = ".$param["company_id"]." AND t1.status = 1");
	}
	
	public function getActiveWareHouseListByControllerId($param){
	return $this->_parentObj->db->getAllRecords("SELECT t1.id AS warehouse_id, t1.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t1.id = t2.warehouse_id WHERE t2.user_id = ".$param->id." AND t1.status = 1");
	}
	
	public function getActiveCompanyList(){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS company_id, t1.name AS company_name FROM ".DB_PREFIX."users AS t1 WHERE t1.status = 1 AND t1.user_level = 2 ORDER BY company_name");
	}
	
	public function getActiveCompanyListByControllerId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS company_id, t1.name AS company_name FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t1.id = t2.company_id WHERE t2.controller_id = ".$param["controller_id"]." AND t1.status = 1");
	}
	
	public function getAllActiveWareHouseList($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS warehouse_id, t1.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_warehouse AS t2 ON t1.id = t2.warehouse_id WHERE t1.status = 1");
	}
	
	public function getActiveCompanyListByCompanyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id AS company_id, t1.name AS company_name FROM ".DB_PREFIX."users AS t1 WHERE t1.id = ".$param['company_id']." AND t1.status = 1");
	}
	
	public function getUserDataByUserId($param){
		return $this->_parentObj->db->getAllRecords("SELECT name,email,phone,address_1,address_2,city,postcode,state,country,user_level,id FROM ".DB_PREFIX."users WHERE id = ".$param->user_id." AND user_level IN(".$param->user_level.")");
	}
	
	public function getAllControllerData($param){
		return $this->_parentObj->db->getAllRecords("SELECT id,name,email,phone,address_1,address_2,city,postcode FROM ".DB_PREFIX."users WHERE user_level = ".$param->user_level."");
	}
	public function getControllerDataByCompanyId($param){
        return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id), t2.user_id as user_id,name,email,phone,address_1,address_2,city,postcode FROM ".DB_PREFIX."users AS t1 LEFT JOIN ".DB_PREFIX."company_users AS t2 ON t1.id = t2.user_id where t2.company_id = ".$param->company_id." AND (t1.user_level=2 OR t1.user_level=3) group by t1.email");
		//return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id), t2.user_id as user_id,name,email,phone,address_1,address_2,city,postcode FROM ".DB_PREFIX."users AS t1 LEFT JOIN ".DB_PREFIX."company_users AS t2 ON t1.id = t2.user_id where t2.company_id = ".$param->user_id." AND (t1.user_level=2 OR t1.user_level=3) group by t1.email");
	}
	
	public function getControllerDataByCompanyAndWarehouseId($param){
        return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(t1.id), t2.user_id as user_id,name,email,phone,address_1,address_2,city,postcode FROM ".DB_PREFIX."users AS t1 LEFT JOIN ".DB_PREFIX."company_users AS t2 ON t1.id = t2.user_id where t2.company_id = ".$param->company_id." AND t2.warehouse_id = ".$param->warehouse_id." AND (t1.user_level=2 OR t1.user_level=3) group by t1.email");
    }

	public function getControllerCompanyData($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id,t1.name FROM ".DB_PREFIX."users AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t1.id = t2.company_id where t2.user_id = ".$param->controller_id." AND user_level=2");		
    }
   
    public function getControllerWarehouseData($param){
		return $this->_parentObj->db->getAllRecords("SELECT t1.id,t1.name FROM ".DB_PREFIX."warehouse AS t1 INNER JOIN ".DB_PREFIX."company_users AS t2 ON t1.id = t2.warehouse_id where t2.user_id = ".$param->controller_id."");		
    } 
   
    public function getWarehouseListByComapnyId($param){
		return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(WT.id) AS warehouse_id, WT.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS WT INNER JOIN ".DB_PREFIX."company_users AS WCT ON WCT.warehouse_id=WT.id WHERE WCT.company_id=".$param->company_id."");
	}
    /*public function getWarehouseListByControllerId($controllerid){
		return $this->_parentObj->db->getAllRecords("SELECT DISTINCT(WT.id) AS warehouse_id, WT.name AS warehouse_name FROM ".DB_PREFIX."warehouse AS WT INNER JOIN ".DB_PREFIX."company_users AS WCT ON WCT.warehouse_id=WT.id WHERE WCT.id=".$controllerid."");
	} */

    private

    function _getCustomerByControllerIdAndKeyword($parent_id,$warehouse_id,$keywords){
        $sql = "SELECT UT.id as id, UT.name as name, email as email FROM " . DB_PREFIX . "users AS UT LEFT JOIN " . DB_PREFIX . "company_users AS WT ON WT.user_id=UT.id WHERE (name LIKE '%$keywords%' OR email LIKE '%$keywords%') AND UT.user_level=5 AND WT.warehouse_id=$warehouse_id AND UT.status=1";/*UT.parent_id = $parent_id AND */

        return $this->_parentObj->db->getAllRecords($sql);
    }
	
	private

    function _getCustomerByCompanyId($company_id, $warehouse_id){
        //$sql = "SELECT UT.id as id, UT.name as name, email as email FROM " . DB_PREFIX . "users AS UT WHERE UT.user_level=5 AND UT.parent_id='$company_id' ORDER BY name";
        //echo $sql;die;
        $sql = "SELECT UT.id as id, UT.name as name, email as email FROM " . DB_PREFIX . "users AS UT INNER JOIN " . DB_PREFIX . "company_users AS CUT  ON CUT.user_id=UT.id WHERE CUT.company_id  = '$company_id' AND CUT.warehouse_id='$warehouse_id' AND UT.user_level  = '5' AND UT.status=1";
        return $this->_parentObj->db->getAllRecords($sql);
    }

    private

    function _getUserByCustomerId($customer_id){
        $sql = "SELECT UT.id as id, UT.name as name, email as email,UT.is_default AS is_default FROM " . DB_PREFIX . "users AS UT INNER JOIN `" . DB_PREFIX . "user_address` AS UAT ON UAT.user_id=UT.id WHERE UT.user_level=6 AND UT.parent_id='$customer_id' AND UT.status=1";
        return $this->_parentObj->db->getAllRecords($sql);
    }

    private

    function _getUserCollectionAddressByUserId($user_id){
        //$sql = "SELECT address_line1 as address_line1, address_line2 as address_line2, postcode as postcode, city as city, country as country, latitude as latitude, longitude as longitude, state as state, company_name as company_name FROM " . DB_PREFIX . "address_book AS AT WHERE AT.customer_id='$customer_id'";
		//$sql = "SELECT UAT.user_id AS user_id, AT.address_line1 as address_line1, AT.address_line2 as address_line2, AT.postcode as postcode, AT.city as city, AT.country as country, AT.latitude as latitude, AT.longitude as longitude, AT.state as state, AT.company_name as company_name,AT.company_name as company_name,AT.name,AT.phone,AT.email FROM " . DB_PREFIX . "address_book AS AT INNER JOIN ".DB_PREFIX."user_address as UAT ON AT.id=UAT.address_id WHERE UAT.user_id='$user_id'";
	    $sql = "SELECT AT.id AS address_id, UAT.user_id AS user_id, AT.address_line1 as address_line1, AT.address_line2 as address_line2, AT.postcode as postcode, AT.city as city, AT.country as country, AT.latitude as latitude, AT.longitude as longitude, AT.state as state, AT.company_name as company_name,AT.company_name as company_name,AT.name,AT.phone,AT.email, AT.iso_code AS alpha3_code FROM " . DB_PREFIX . "address_book AS AT INNER JOIN ".DB_PREFIX."user_address as UAT ON AT.id=UAT.address_id WHERE UAT.user_id='$user_id' AND UAT.default_address='Y'";

        return $this->_parentObj->db->getRowRecord($sql);
    }
	
    private function _getDefaultUserByCustomerId($customer_id){
        $sql = "SELECT UT.id as id, UT.name as name, email as email,UT.is_default FROM " . DB_PREFIX . "users AS UT WHERE UT.user_level=5 AND UT.id='$customer_id' AND UT.is_default=1";
        return $this->_parentObj->db->getRowRecord($sql);
    }
	
	private function _getCustomerCollectionAddressByCustomerId($customer_id){
		$sql = "SELECT AT.id AS address_id, AT.customer_id AS user_id, AT.address_line1 as address_line1, AT.address_line2 as address_line2, AT.postcode as postcode, AT.city as city, AT.country as country, AT.latitude as latitude, AT.longitude as longitude, AT.state as state, AT.company_name as company_name,AT.company_name as company_name,AT.name,AT.phone,AT.email, AT.iso_code AS alpha3_code FROM " . DB_PREFIX . "address_book AS AT INNER JOIN " . DB_PREFIX . "user_address AS UAT ON UAT.address_id=AT.id WHERE UAT.user_id='$customer_id' AND UAT.default_address='Y'";
		return $this->_parentObj->db->getRowRecord($sql);
    }
	
	/*public function addController($param){
		$isControllerExists = $this->_parentObj->db->getOneRecord("select 1 from icargo_users where email='".$param->user_email."'");
		if(!$isControllerExists){
			$param->password = passwordHash::hash($param->password);
			$param->user_level = 3;
			$param->register_in_firebase = 1; 
			
            $data = array('name'=>$param->name,'contact_name'=>$param->name,'phone'=>$param->phone,'email'=>$param->user_email,'password'=>$param->password,'address_1'=>$param->address_1,'address_2'=>$param->address_2,'city'=>$param->city,'postcode'=>$param->postcode,'user_level'=>$param->user_level,'uid'=>$param->uid,'register_in_firebase'=>$param->register_in_firebase,'state'=>$param->state,'country'=>$param->country);
			
            //$controller_id = $this->_parentObj->db->insertIntoTable($param, $column_names, DB_PREFIX."users");
            
            $controller_id = $this->_parentObj->db->save("users", $data);

            
        if ($controller_id != NULL) {
            foreach($param->warehouse as $value){
                $relationData = array('company_id'=>$param->company_id,'warehouse_id'=>$value->id,'user_id'=>$controller_id);
                    $column_names = array('company_id','warehouse_id', 'user_id');
                    $relationTblEntry = $this->_parentObj->db->insertIntoTable($relationData, $column_names, DB_PREFIX."company_users");
            }
            if($relationTblEntry!= NULL){
                $data['user_id'] = $controller_id;
                $data['action'] =  $controller_id;
                $data['address'] = $param->address_1.' '.$param->address_2;
                $response["status"] = "success";
                $response["message"] = "Controller created successfully";
                $response["saved_record"] = $data;
            }else {
                $response["status"] = "error";
                $response["message"] = "Failed to create controller. Please try again";
            }        
        }else{
				$response["status"] = "error";
				$response["message"] = "Failed to create controller. Please try again";
			}
		}else{
			$response["status"] = "error";
			$response["message"] = "Controller with the provided email already exists!";
        }
        return $response;
	}
	
	public function editController($param){
		//print_r($param);die;
        $updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."users SET name='".$param->name."',phone='".$param->phone."',address_1='".$param->address_1."',address_2='".$param->address_2."',postcode='".$param->postcode."',city='".$param->city."',state='".$param->state."',country='".$param->country."' WHERE id = ".$param->id."");
		if ($updateData != NULL) {
			$response["status"] = "success";
			$response["message"] = "Controller details updated successfully";
		}else {
			$response["status"] = "error";
			$response["message"] = "Failed to update controller details. Please try again";
		}
        return $response;
	}*/

    public function addController($param){
        $isControllerExists = $this->_parentObj->db->getOneRecord("select 1 from icargo_users where email='".$param->user_email."'");
        if(!$isControllerExists){
            $param->password = passwordHash::hash($param->password);
            $param->user_level = 3;
            $param->register_in_firebase = 1; 
            
            $data = array('parent_id'=>$param->company_id,'name'=>$param->name,'contact_name'=>$param->name,'phone'=>$param->phone,'email'=>$param->user_email,'password'=>$param->password,'address_1'=>$param->address_1,'address_2'=>$param->address_2,'city'=>$param->city,'postcode'=>$param->postcode,'user_level'=>$param->user_level,'uid'=>$param->uid,'register_in_firebase'=>$param->register_in_firebase,'state'=>$param->state,'country'=>$param->country);
           
            //$controller_id = $this->_parentObj->db->insertIntoTable($param, $column_names, DB_PREFIX."users");
            
            $controller_id = $this->_parentObj->db->save("users", $data);

            
        if ($controller_id != NULL) {
            foreach($param->warehouse as $value){
                $relationData = array('company_id'=>$param->company_id,'warehouse_id'=>$value->id,'user_id'=>$controller_id);
                    $column_names = array('company_id','warehouse_id', 'user_id');
                    $relationTblEntry = $this->_parentObj->db->insertIntoTable($relationData, $column_names, DB_PREFIX."company_users");
            }
            if($relationTblEntry!= NULL){
                $data['user_id'] = $controller_id;
                $data['action'] =  $controller_id;
                $data['address'] = $param->address_1.' '.$param->address_2;
                $response["status"] = "success";
                $response["message"] = "Controller created successfully";
                $response["saved_record"] = $data;
            }else {
                $response["status"] = "error";
                $response["message"] = "Failed to create controller. Please try again";
            }        
        }else{
                $response["status"] = "error";
                $response["message"] = "Failed to create controller. Please try again";
            }
        }else{
            $response["status"] = "error";
            $response["message"] = "Controller with the provided email already exists!";
        }
        return $response;
    }

    public function editController($param){
        $updateData = $this->_parentObj->db->updateData("UPDATE ".DB_PREFIX."users SET name='".$param->name."',phone='".$param->phone."',address_1='".$param->address_1."',address_2='".$param->address_2."',postcode='".$param->postcode."',city='".$param->city."',state='".$param->state."',country='".$param->country."' WHERE id = ".$param->id."");
        
        $delete = $this->db->delete("DELETE FROM ".DB_PREFIX."company_users WHERE user_id = $param->id");
        if($delete){
            foreach($param->warehouse as $warehouse_id){
                $updateData = $this->db->save("company_users",array('company_id'=>$param->company_id,'warehouse_id'=>$warehouse_id->id,'user_id'=>$param->id,'status'=>1));
            }
        }
        if ($updateData != NULL) {
            $response["status"] = "success";
            $response["message"] = "Controller details updated successfully";
        }else {
            $response["status"] = "error";
            $response["message"] = "Failed to update controller details. Please try again";
        }
        return $response;
    }

    public

    function getCustomerByControllerId($param){
        return $this->_getCustomerByControllerIdAndKeyword($param->company_id, $param->warehouse_id, $param->keywords);
    }
	
	public function loadCustomerAndUserByCustomerId($param){
        $customerLists = $this->_getCustomerByCompanyId($param["controller_id"], $param["warehouse_id"]);

        foreach($customerLists as $key=>$item){
            $userLists = $this->_getUserByCustomerId($item["id"]);


            if($userLists){
                $customerLists[$key]["default_user_id"] = $item["id"];
                $customerLists[$key]["users"][] = array("id"=>$item["id"],"name"=>$item["name"],"email"=>$item["email"],"is_default"=>0,"collection_address"=>$this->_getCustomerCollectionAddressByCustomerId($item["id"]));

                foreach($userLists as $user_key=>$userItem){

                    $customerLists[$key]["users"][] = array("id"=>$userItem["id"],"name"=>$userItem["name"],"email"=>$userItem["email"],"is_default"=>$userItem["is_default"],"collection_address"=>$this->_getUserCollectionAddressByUserId($userItem["id"]));


                    if($userItem['is_default']=="1")
                        $customerLists[$key]["default_user_id"] = $userItem["id"];
				}  
            }else{
                //unset($customerLists[$key]);
				$userLists = $this->_getDefaultUserByCustomerId($item["id"]);
				$customerCollectionAddress = $this->_getCustomerCollectionAddressByCustomerId($item["id"]);
                $userLists["collection_address"] = $customerCollectionAddress;

                $customerLists[$key]["default_user_id"] = $item["id"];
                $customerLists[$key]["users"][] = $userLists;
            }
        }
        return $customerLists;
    }

    public function getAllWarehouseAddressByCompanyAndUser($param){
        $sql = "SELECT `ABT`.`id`,`ABT`.`address_line1`, `ABT`.`address_line2`, `ABT`.`postcode`, `ABT`.`city`, `ABT`.`state`, `ABT`.`country` FROM `". DB_PREFIX  ."address_book` AS `ABT` INNER JOIN `". DB_PREFIX ."user_address` AS `UAT` ON `ABT`.`id` = `UAT`.`address_id` WHERE `UAT`.`default_address`='Y' AND `ABT`.`customer_id`=".$param['user_id']." AND `ABT`.`status`=1";
        $records = $this->_parentObj->db->getAllRecords($sql);
        return $records;
    }
}
?>