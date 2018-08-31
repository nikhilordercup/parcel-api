<<<<<<< HEAD
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
=======
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
>>>>>>> b5f5ac66cc7a31a7b7a522c82730846839a4a200
?>