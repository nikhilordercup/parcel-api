<?php
require_once "Firebase_Api";
class Auth_provider{
    private function getFirebase(){
         $firebaseObj = new Firebase_Api();
         $this->firebase = $firebaseObj->getFirebase();
    }

    public function login($email, $password){
        try {
            $obj = $this->getFirebase()->getAuth()->verifyPassword($email, $password);
            $userRecord = User::_getInstance()->deserialize($obj);
            return array("status"=>"success", "data"=>$userRecord, "message"=>"User authenticated successfully");
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function forgotPassword($email){
        try{
            $this->getFirebase()->getAuth()->sendPasswordResetEmail($email);
            return array("status"=>"success", "message"=>"Reset password link has been sent to $email");
        } catch (Exception $e){
            return array("status"=>"error", "message"=>$e->getMessage());
        }
    }

    public function signupUser($email, $password){
        try{
            $obj = $this->getFirebase()->getAuth()->createUserWithEmailAndPassword($email, $password);
            $userRecord = User::_getInstance()->deserialize($obj);
            return array("status"=>"success", "data"=>$userRecord, "message"=>"Signup successful");
        }catch(Exception $e){
            return array("status"=>"error", "message"=>$e->getMessage());
        }
    }

    public function signOut(){
      print_r($this->getFirebase()->getAuth()->signOut());die;
    }

    /*public function login($email, $password){
        $obj = $this->getFirebase()->createUserWithEmailAndPassword($email, $password);
        print_r($obj);die;
    }

    public function forgotPassword($email){

    }

    public function signupUser($email, $password){

    }*/
}
?>
