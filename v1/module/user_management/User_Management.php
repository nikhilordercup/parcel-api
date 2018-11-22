<?php
require_once "Providers/Auth_Provider.php";
class User_Management{
    public static $authProviderObj= null;

    public function __construct(){
        if(self::$authProviderObj===null){
            self::$authProviderObj = new Auth_Provider();
        }
        $this->authProvider = self::$authProviderObj;
    }

    public function login($email,$password){
        return $this->authProvider->login($email,$password);
    }

    public function forgotPassword($email){
        return $this->authProvider->forgotPassword($email);
    }

    public function signupUser($email,$password){
        return $this->authProvider->signupUser($email,$password);
    }

    public function signOut(){
        return $this->authProvider->signOut();
    }
}
?>
