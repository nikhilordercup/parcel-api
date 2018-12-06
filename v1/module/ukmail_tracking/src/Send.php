<?php
class Send extends Singleton{

  private function _send(){

  }

  private function _initConstructorParams($config){
    $constructor = array(
      "username" => "",
      "password" => "",
      "wsdl_url" => "",
      "xml_string" => ""
    );

    foreach($constructor as $key => $val){
      if(!isset($config[$key]) OR empty($config[$key])){
        throw new Exception("$key is mandatory to post the request");
      }
      else{
        $constructor[$key] = $config[$key];
      }
    }
    return array_values($constructor);
  }

  public function postRequest(array $config = []){
    print_r($this->_initConstructorParams($config));die;
  }
}
?>
