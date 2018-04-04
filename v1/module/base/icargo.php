<?php

class Icargo{
	private $_access_token;
	private $_email;
	private $_verify_chargeBee = true;
	public $db;
	
	private function _setUserEmail($v){
		$this->_email = $v;
	}
	
	private function _getUserEmail(){
		return $this->_email;
	}
	
	private function _setAccessToken($v){
		$this->_access_token = $v;
	}
	
	private function _getAccessToken(){
		return $this->_access_token;
	}
	
	public function _setDbHandler(){
		$this->db = new DbHandler();
	}
	
	public function __construct($data){
		$this->_setUserEmail($data['email']);
		$this->_setAccessToken($data['access_token']);
        
		if(isset($data["verifyChargeBee"])){
			$this->_verify_chargeBee = $data["verifyChargeBee"];
		}
		
		$this->_setDbHandler();
        $validateUser = $this->_checkAccessToken();
        
        if($validateUser["count"] == 0){
			$response["status"]  = "error";
            $response["code"]    = "invalid_user";
			$response["message"] = "Invalid access token";
			echoResponse(201, $response);
			exit();
		}/*else {
			return $this;	
		}*/
		else {
			if($this->_verify_chargeBee){
				$subscription = $this->_verifyUserSubscription();
				if($subscription["status"]=="error"){
					$response["status"]  = "error";
					$response["code"]    = "subscription-error";
					$response["message"] = $subscription["message"];
					echoResponse(201, $response);
					exit();
				}else{
					return $this;
				}
			}else{
				return $this;
			}
		}
	}
	
    public function validateUser(){
        $validateUser = $this->_checkAccessToken();
        
        if($validateUser["count"] == 0){
			$response["status"]  = "error";
            $response["code"]    = "invalid_user";
			$response["message"] = "Invalid access token";
			echoResponse(201, $response);
			exit();
		}else {
			return $this;	
		}
    }
    
	private function _checkAccessToken(){
        return $this->db->getOneRecord("SELECT COUNT(1) AS count FROM ".DB_PREFIX."users WHERE access_token = '".$this->_getAccessToken()."' AND email = '".$this->_getUserEmail()."'");
	}
	
	private function _verifyUserSubscription(){
		
		$user = $this->db->getOneRecord("SELECT `id` AS `user_id` FROM ".DB_PREFIX."users WHERE access_token = '".$this->_getAccessToken()."' AND email = '".$this->_getUserEmail()."'");

		$user_id = $user['user_id'];
		
		$sql = "SELECT DATE_FORMAT(trial_end, \"%Y-%m-%d\") AS trial_end, status FROM `" . DB_PREFIX . "chargebee_subscription` AS T1 INNER JOIN `" . DB_PREFIX . "chargebee_customer` AS T2 ON T1.chargebee_customer_id=T2.chargebee_customer_id WHERE `T2`.`user_id` = '$user_id'";
		$record = $this->db->getRowRecord($sql);
		
		return array("status"=>"valid","message"=>"Subscription valid.");	// please comment when testing complete
		
		if($record){
			//just for testing
			//$record["trial_end"] = "2017-12-22";//remove hardcode after testing
			
			$trial_end_date = strtotime(date("Y-m-d",strtotime($record["trial_end"])));
			$current_date = strtotime(date("Y-m-d"));
			
			if($current_date>$trial_end_date)
				{
				return array("status"=>"error","message"=>"Subscription expired. Please upgarde the plan first");
				}
			elseif($record["status"]!="active")
				{
				return array("status"=>"error","message"=>"Subscription deactivated. Please upgarde the plan first");
				}
			else
				{
				return array("status"=>"valid","message"=>"Subscription valid.");	
				}
			}
		
		else{
			return array("status"=>"error","message"=>"User have no subscription"); //please comment when testing
		}
	}
}
?>