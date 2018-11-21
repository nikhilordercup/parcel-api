<?php
require "Providers/Auth_Provider.php";
class Firebase_User_Management{
    public static $authProviderObj= null;

    public function __construct(){
        if(self::$authProviderObj===null){
            self::$authProviderObj = new Auth_Provider();
        }
        $this->authProvider = self::$authProviderObj;
    }

    private function _login($email,$password){
        return $this->authProvider->login($email,$password);
    }

    public function forgotPassword($email){
        return $this->authProvider->forgotPassword($email);
    }

    private function _signupUser($email,$password){
        return $this->authProvider->signupUser($email,$password);
    }

    private function _getUserByEmail($email){
        return $this->authProvider->getUserByEmail($email);
    }

    public function signOut(){
        return $this->authProvider->signOut();
    }

	public function customerSignup($param){
		$signupStatus = $this->_signupUser($param->email,$param->password);
		$param->customerbilling = new stdClass();
		$param->customerpickup = new stdClass();

		$param->customerbilling->name = $param->name;
		$param->customerbilling->address_1 = $param->address_1;
		$param->customerbilling->country = $param->country;
		$param->customerbilling->countrycode = $param->countrycode;
		$param->customerbilling->postcode = $param->postcode;
		$param->customerbilling->city = $param->city;
		$param->customerpickup->name = $param->name;
		$param->customerpickup->address_1 = $param->address_1;
		$param->customerpickup->country = $param->country;
		$param->customerpickup->countrycode = $param->countrycode;
		$param->customerpickup->postcode = $param->postcode;
		$param->customerpickup->city = $param->city;

		if($signupStatus['status']=='success'){
			$userManagementObj = new User_Management();
			$saveCustomerToDB = $userManagementObj->createApiCustomer($param,$signupStatus['data']);
			return $saveCustomerToDB;
		}else{
			return $signupStatus;
		}
	}

	public function customerLogin($email,$password){
		$loginStatus = $this->_login($email,$password);
		if($loginStatus['status']=='success'){
			$userManagementObj = new User_Management();
			$loginCustomerFromDB = $userManagementObj->customerLogin($email,$password);
			return $loginCustomerFromDB;
		}else{
			return $loginStatus;
		}
	}

  public function test($u, $p){
    return $this->_signupUser($u, $p);
  }

  public function test1($email){
    return $this->_getUserByEmail($email);
  }
}
?>
