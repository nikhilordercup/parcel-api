<?php
require_once "../v1/module/firebase/Firebase_Api.php";
require_once "../v1/module/firebase/model/User.php";

class Auth_provider{
    public static $_firebaseObj = null;

    public function __construct(){
        if(self::$_firebaseObj===null){
            self::$_firebaseObj =  new Firebase_Api();
        }
        $this->firebaseObj = self::$_firebaseObj;
    }

    private function getFirebase(){
      return $this->firebaseObj->getFirebase();
         //$firebaseObj = new Firebase_Api();
         //return $firebaseObj->getFirebase();
    }

	private function _handleException($e){
		return array("status"=>"error", "message"=>$e->getMessage());
	}

    public function login($email, $password){
        try {
            $obj = $this->getFirebase()->getAuth()->verifyPassword($email, $password);
            $userRecord = User::_getInstance()->deserialize($obj);
            return array("status"=>"success", "data"=>$userRecord, "message"=>"User authenticated successfully");
        } catch (Exception $e) {
            return $this->_handleException($e);
        }
    }

    public function forgotPassword($email){
        try{
            $this->getFirebase()->getAuth()->sendPasswordResetEmail($email);
            return array("status"=>"success", "message"=>"Reset password link has been sent to $email");
        } catch (Exception $e){
            return $this->_handleException($e);
        }
    }

    public function signupUser($email, $password){
        try{
            $obj = $this->getFirebase()->getAuth()->createUserWithEmailAndPassword($email, $password);
            $userRecord = User::_getInstance()->deserialize($obj);
            return array("status"=>"success", "data"=>$userRecord, "message"=>"Signup successful");
        }catch(Exception $e){
            return $this->_handleException($e);
        }
    }

    public function signOut(){
      return $this->getFirebase()->getAuth()->signOut();
    }

    public function getUserByEmail($email){
      try{
          $obj = $this->getFirebase()->getAuth()->getUserByEmail($email);
          $userRecord = User::_getInstance()->deserialize($obj);
          return array("status"=>"success", "data"=>$userRecord, "message"=>"User found");
      }catch(Exception $e){
          return $this->_handleException($e);
      }

    }


}
?>
