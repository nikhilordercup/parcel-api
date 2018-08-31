<?php
class Validation{
	
	public $errorMsg = null;
    
	public function firstName($firstName){
		if(strlen < 35){
			$this->errorMsg = 'first name should be less than 35 characters';
			return false;
		}
		return true;
	} 
	
	public function lastName($lastName){
		if(strlen < 35){
			$this->errorMsg = 'last name should be less than 35 characters';
			return false;
		}
		return true;
	}
	
	
	
}
?>