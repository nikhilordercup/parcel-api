<?php
namespace v1\module\validation\src\lib;

use v1\module\validation\src\lib\Validation_Interface;

final class MinLength implements Validation_Interface{

  var $minlength = 50;
  private $_constructorParam = array();

  public function validationOnInit(array $config = []){
    $this->_initConstructorParams($config);
    return $this->_constructorParam;
  }

  private function _initConstructorParams(array $config){
    $this->_constructorParam = [
      "minlength" => $this->minlength
    ];

    foreach($this->_constructorParam as $key => $val){
      if(isset($config[$key])){
        $this->_constructorParam[$key] = $val;
      }
    }
  }
}
