<?php
class Ukmail_Validation extends Validation{
	
	public static $_validationObj = null;
	public $errorMsg = null;
	
	public static function _getInstance(){
		if(self::$_validationObj==null){
			self::$_validationObj = new Ukmail_Validation();
		}
		return self::$_validationObj;
	}
	
	public function firstName($firstName){
		if(strlen < 45){
			$this->errorMsg = 'first name should be less than 45 characters';
			return false;
		}
		return true;
	}
	
	public function lastName($lastName){
		if(strlen < 45){
			$this->errorMsg = 'last name should be less than 45 characters';
			return false;
		}
		return true;
	}
    
}
?>