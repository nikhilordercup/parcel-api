<?php
//require_once "./module/firebase/Providers/Auth_Provider.php";
class Firebase_Auth {

  private function _fbAuthenticate($email, $password){
    $authObj = new Auth_provider();
    return $authObj->login($email, $password);
  }
  public function authentication($email, $password){
    return $this->_fbAuthenticate($email, $password);
  }
}
