<?php
require_once "model/rest.php";
class Driver_Logout{
	private $_user_id = 0;
	public function __construct($param){
		$this->_user_id = $param->driverCode;
	}
	
	public function clearAccessToken(){
		$obj = new Ws_Model_Rest();
		$obj->update('users',array("access_token"=>""),"id=$this->_user_id");
	}
}