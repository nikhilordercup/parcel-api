<?php
final class MaxLength implements InterfaceValidation{

  var $maxlength = 50;

  public function validationOnInit(array $config = []){
    $list(
       $maxlength
    ) = $this->_initConstructorParams($config);
    print_r($config);
    $this->_initConfig($config);
  }

  private function _initConstructorParams(array $config){
    $constructor = [
      "maxlength" => $this->maxlength
    ];

    foreach($constructor as $key => $val){
      if(isset($config[$key])){
        $constructor[$key] = $val;
      }
    }
    return array_values($constructor);
  }

  private function _initConfig(array $config){
    foreach($config as $var => $val){
      $this->{$var} = $val;
    }
  }

  public function getValidation(){
    return $this;
  }
}
