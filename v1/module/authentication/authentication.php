<?php

class Authentication{
	private $_email;
	private $_password;
	private $_access_token;
	public $db;
	
	private function _setEmail($v){
		$this->_email = $v;
	}
	
	private function _setPassword($v){
		$this->_password = $v;
	}
	
	private function _getEmail(){
		return $this->_email;
	}
	
	private function _getPassword(){
		return $this->_password;
	}
	
    public function __construct($data){
	    $this->_setEmail($data->auth->email);
		$this->_setPassword($data->auth->password);
		$this->db = new DbHandler();
	}
	
	private function _setAccessToken($v){
		$this->_access_token = base64_encode(rand()."-".uniqid()."-$v");
	}
	
	private function _getAccessToken(){
		return $this->_access_token;
	}
	
    private function _getCompanyList($param){
        $records = $this->db->getAllRecords("SELECT `id` AS `company_id`, `name` AS `company_name` FROM ".DB_PREFIX."users as UT WHERE UT.`id`='".$param['user_id']."' AND UT.`status`=1 AND user_level=2");
		return $records;
    }
    
    private function _getWarehouseList($param){
		if($param['user_level']==2){
			$records = $this->db->getAllRecords("SELECT WT.`id` AS `warehouse_id`, `name` AS `warehouse_name`, latitude, longitude FROM ".DB_PREFIX."warehouse as WT INNER JOIN ".DB_PREFIX."company_warehouse AS CWT ON CWT.warehouse_id = WT.id WHERE CWT.`company_id`='".$param['company_id']."' AND CWT.`status`=1");
		}else{
			$records = $this->db->getAllRecords("SELECT WT.`id` AS `warehouse_id`, `name` AS `warehouse_name`, latitude, longitude FROM ".DB_PREFIX."warehouse AS WT INNER JOIN ".DB_PREFIX."company_warehouse AS CWT ON WT.id = CWT.warehouse_id INNER JOIN ".DB_PREFIX."company_users AS t3 ON WT.id = t3.warehouse_id WHERE CWT.company_id = ".$param["company_id"]." AND t3.user_id=".$param['user_id']);
		}
        
		return $records;
    }
    
	private function _getCompanyId($param){
		if($param['user_code']=="company"){
			return $param['user_id'];
		} else if($param['user_code']=="controller" || $param['user_code']=="customer"){
			$record = $this->db->getOneRecord("SELECT `company_id` FROM ".DB_PREFIX."company_users as UT WHERE UT.`user_id`='".$param['user_id']."' AND UT.`status`=1");
			return $record['company_id'];
		}/* else if($param['user_code']=="customer"){
			$record = $this->db->getOneRecord("SELECT `company_id` FROM ".DB_PREFIX."company_users as UT WHERE UT.`user_id`='".$param['user_id']."' AND UT.`status`=1");
			return $record['company_id'];
		} */
	}
	
    private function _getShipmentCountsDb($param){
		$records = $this->db->getAllRecords(" SELECT SP.instaDispatch_loadGroupTypeCode as shiptype,count(1) as num FROM ".DB_PREFIX."shipment as SP  
        WHERE SP.`company_id`='".$param['company_id']."' AND SP.`warehouse_id`='".$param['warehouse_id']."' AND SP.`current_status`= 'C' group by SP.instaDispatch_loadGroupTypeCode");
		return $records;
        
	}
    
    
    
	public function process(){
		$response = array();
		$user = $this->db->getOneRecord("SELECT UT.`id`,UT.`name`,UT.`password`,UT.`email`,UT.`user_level`,UT.`create_date`,ULT.`user_type`, `UT`.`uid`,UT.`parent_id`, ULT.`code`, UT.profile_image, UT.profile_path FROM ".DB_PREFIX."users as UT INNER JOIN ".DB_PREFIX."user_level as ULT ON UT.`user_level` = ULT.`id` WHERE UT.`phone`='".$this->_getEmail()."' or UT.`email`='".$this->_getEmail()."' AND UT.`email_verified`=1");
		if ($user != NULL) {
			//if(passwordHash::check_password($user['password'],$this->_getPassword())){
				$access_token = $this->_setAccessToken($user['id']);
				$access_token = $this->_getAccessToken();
				$tokenUpdateSuccess = $this->db->updateAccessTokenById($access_token,$user['id']);
				if($tokenUpdateSuccess){
					
					if($user["user_level"]==2 || $user["user_level"]==3){
						$company_id = $this->_getCompanyId(array('user_code' => $user['code'],'user_id' => $user['id']));
						$setupObj = new Setup((object) array("email"=>$user['email'], "access_token"=>$access_token, "user_id"=>$company_id));
						$setup_response = $setupObj->getDefaultRegistrationData();
						
						$response['company'] = $company_id;
						$response['company_list'] = $this->_getCompanyList(array("user_id"=>$user['id']));
						if($user["user_level"]==2)
						$response['warehouse_list'] = $this->_getWarehouseList(array("company_id"=>$company_id,'user_id'=>$user['id'],"user_level"=>$user["user_level"]));
					    else
						$response['warehouse_list'] = $this->_getWarehouseList(array("company_id"=>$company_id,'user_id'=>$user['id'],"user_level"=>$user["user_level"]));
						
						//$response['default_warehouse_id'] = $response['warehouse_list'][0]['warehouse_id'];
						//$response['default_warehouse'] = $response['warehouse_list'][0]['warehouse_name'];
						
						$response['default_warehouse_id'] = 0;
						$response['default_warehouse'] = "";
						if(isset($response['warehouse_list'][0])){
							$response['default_warehouse_id'] = $response['warehouse_list'][0]['warehouse_id'];
							$response['default_warehouse'] = $response['warehouse_list'][0]['warehouse_name'];
						}
						
						$response['setup_completed'] = $setup_response['setup_status'];   
						$response['incomplete_setup'] = $setup_response['setup_data'];
						$response['shipment_counts'] = $this->_getShipmentCount(array("company_id"=>$company_id,"warehouse_id"=>$response['default_warehouse_id']));
						
                    	//$response['default_warehouse_id'] = $response['default_warehouse_id'];
					}
					elseif($user["user_level"]==5 || $user["user_level"]==6){
						$company_id = $this->_getCompanyId(array('user_code' => $user['code'],'user_id' => $user['id']));
						//$setupObj = new Setup((object) array("email"=>$user['email'], "access_token"=>$access_token, "user_id"=>$company_id));
						//$setup_response = $setupObj->getDefaultRegistrationData();
						
						$response['company'] = $company_id;
						$response['company_list'] = $this->_getCompanyList(array("user_id"=>$user['parent_id']));
						$response['warehouse_list'] = $this->_getWarehouseList(array("company_id"=>$company_id,'user_id'=>$user['id'],"user_level"=>$user["user_level"]));
						$response['collection_address'] = $this->_getUserCollectionAddress($user['id']);
						$response['customer_info'] = $this->_getCustomerDetail($user['id']);
						$response['parent_id'] = $user['parent_id'];
						//$response['default_warehouse_id'] = $response['warehouse_list'][0]['warehouse_id'];
						//$response['default_warehouse'] = $response['warehouse_list'][0]['warehouse_name'];
						
						$response['default_warehouse_id'] = 0;
						$response['default_warehouse'] = "";
						if(isset($response['warehouse_list'][0])){
							$response['default_warehouse_id'] = $response['warehouse_list'][0]['warehouse_id'];
							$response['default_warehouse'] = $response['warehouse_list'][0]['warehouse_name'];
						}
						
						//$response['setup_completed'] = $setup_response['setup_status'];   
						//$response['incomplete_setup'] = $setup_response['setup_data'];
						$response['shipment_counts'] = $this->_getShipmentCount(array("company_id"=>$company_id,"warehouse_id"=>$response['default_warehouse_id']));
					}
					elseif($user["user_level"]==1){
						$response['company'] = 0;
					}
					
                    $response['status'] = "success";
					$response['message'] = 'Logged in successfully.';
                    $response['name'] = $user['name'];
                    $response['id'] = $user['id'];
                    $response['email'] = $user['email'];
                    $response['create_date'] = $user['create_date'];
                    $response['user_level'] = $user['user_level'];
                    $response['user_type'] = $user['user_type'];
                    $response['access_token'] = $access_token;
                    $response['uid'] = $user['uid'];
                    $response['user_code'] = $user['code'];
                    $response['profile_image'] = $user['profile_image'];
                    $response['profile_path'] = $user['profile_path'];
                }else {
					$response['status'] = "error";
					$response['message'] = 'Authentication Failure!';
        		}	
			/*} else{
				$response['status'] = "error";
				$response['message'] = 'Login failed. Incorrect credentials!';
        	}*/
   		}else {
            $response['status'] = "error";
            $response['message'] = 'No such user is registered!';
        }
		if($response['status'] == "success"){
			echoResponse(200, $response);
		} else {
			echoResponse(401, $response);
		}
	}
	
    private function _getShipmentCount($param){
        $return = array('SAME'=>0,'NEXT'=>0);
        $records = $this->_getShipmentCountsDb($param);
        if(count($records)>0){foreach($records as $val){
           if($val['shiptype']=='SAME'){
             $return['SAME'] = $val['num'];  
           }else{
            $return['NEXT']+= $val['num'];
           }
        }}
      return $return;
    }
	
    private function _getUserCollectionAddress($customer_id){
            $data = $this->db->getRowRecord("SELECT * FROM ".DB_PREFIX."user_address where user_id=".$customer_id." AND default_address='Y'");
            $user_address = $this->db->getRowRecord("SELECT AT.customer_id AS user_id, AT.address_line1, AT.address_line2, AT.postcode, AT.city, AT.country, AT.latitude, AT.longitude, AT.state, AT.company_name, AT.company_name,AT.name,AT.phone,AT.email, AT.iso_code, CO.alpha2_code, CO.alpha3_code FROM " . DB_PREFIX . "address_book AS AT INNER JOIN ".DB_PREFIX."countries AS CO ON CO.id=AT.country_id WHERE AT.id='".$data['address_id']."'");
            return $user_address;
    }

    private function _getCustomerDetail($customer_id){
        $customerInfo = $this->db->getRowRecord("SELECT * FROM ".DB_PREFIX."customer_info where user_id='".$customer_id."' ");
        return $customerInfo;
    }
}
?>