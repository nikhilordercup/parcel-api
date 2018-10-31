<?php
class Model{
	public static $dbObj = null;
	
	private $_db = null;
	
	public function __construct(){
		if(self::$dbObj===null){
			self::$dbObj = new DbHandler();
		}
		$this->_db = self::$dbObj;
	}
	
	public static function getInstanse(){ 
		return new Model();
	}
	
	public function addContent($table_name, $data){ 
		return $this->_db->save($table_name, $data);
	}
	
	public function editContent($table_name, $data, $condition){
		return $this->_db->update($table_name, $data, $condition);
	}
	
    /* public function saveUser($param){
		$sql = "INSERT INTO `icargo_users` SET `user_level`= $param->user_level,`name` = $param->name,`contact_name` = $param->contact_name,`phone` = $param->phone,`email` = $param->email,`password` = $param->password,`postcode` = $param->postcode,`city` = $param->city,`state` = $param->state,`country` = $param->country,`status` = $param->status,`uid` = $param->uid,`register_in_firebase` = $param->register_in_firebase,`email_verified` = $param->email_verified,`access_token` = $param->access_token,`free_trial_expiry` = $param->free_trial_expiry,`parent_id` = $param->parent_id,`is_default` = $param->is_default";
		
		$response = $this->_db->executeQuery($sql);
		if($response!=NULL){
			//save warehouse data
			
			
			//save company_users relation data
			
			
			//save customer_info data
			
			
			//save accountbalancehistory data
			

		}
	} */
	
	public function disableUser(){
		
	}
	
	public function enableUser(){
		
	}
	
	public function updateUser(){
		
	}
	
	public function checkCustomerEmailExist($company_email){ 
		 $record = array();
		 $sqldata ='count(1) as exist';
		 $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "users AS t1
				 WHERE t1.email  = '".$company_email."'";
		 $record = $this->_db->getOneRecord($sql);
		 return $record['exist'];      
	}
	
	public function checkCustomerAccountExist($accountstr){ 
     $record = array();
	 $sqldata ='count(1) as exist';
     $sql = "SELECT ".$sqldata." FROM " . DB_PREFIX . "customer_info AS t1
             WHERE t1.accountnumber  = '".$accountstr."'";
	 $record = $this->_db->getOneRecord($sql);
     return $record['exist'];      
    }  
	
	public function  getAllAccountOfCompany($companyId){  
        $record = array();
        $sqldata = 'A.id as courier_account_id,A.account_number,A.courier_id';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_company as A
                WHERE A.company_id = '" .$companyId ."'
                ORDER BY A.id "; 
        $record = $this->_db->getAllRecords($sql);
        return $record; 
     }
     public function  getAllAccountServices($companyId,$courierAccountId){  
        $record = array();
        $sqldata = 'A.*';
        $sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_services_vs_company as A
                WHERE A.company_id = '" .$companyId ."'
                AND A.courier_id = '" .$courierAccountId ."'
                ORDER BY A.id "; 
        $record = $this->_db->getAllRecords($sql);
        return $record; 
    } 
	public function  getAllAccountSurcharges($companyId,$courierAccountId){  
		$record = array();
		$sqldata = 'A.*';
		$sql = "SELECT " . $sqldata . " FROM " . DB_PREFIX . "courier_vs_surcharge_vs_company as A
				WHERE A.company_id = '" .$companyId ."'
				AND A.courier_id = '" .$courierAccountId ."'
				ORDER BY A.id "; 
		$record = $this->_db->getAllRecords($sql);
		return $record; 
	}
}
?>
