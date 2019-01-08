<?php
namespace v1\module\validation\src\lib;

use v1\module\validation\src\lib\Validation_Interface;

final class MaxLength implements Validation_Interface{

  var $maxlength = 50;
  private $_constructorParam = array();

  public function validationOnInit(array $config = []){
    $this->_initConstructorParams($config);
    return $this->_constructorParam;
  }

  private function _initConstructorParams(array $config){
    $this->_constructorParam = [
      "maxlength" => $this->maxlength
    ];

    foreach($this->_constructorParam as $key => $val){
      if(isset($config[$key])){
        $this->_constructorParam[$key] = $val;
      }
    }
  }
}
