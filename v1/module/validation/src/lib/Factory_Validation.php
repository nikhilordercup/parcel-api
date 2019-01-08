<?php
namespace v1\module\validation\src\lib;

use stdClass;
use v1\module\validation\src\lib\MaxLength;
use v1\module\validation\src\lib\MinLength;

class Factory_Validation{

  public function __construct(){
    $this->_list = new stdClass();
    $this->_list->minlength = $this->_getMinLengthRule();
    $this->_list->maxlength = $this->_getMaxLengthRule();
    print_r($this->_list);
  }

  private function _getMinLengthRule(){
    $obj = new MinLength();
    return $obj->validationOnInit();
  }

  private function _getMaxLengthRule(){
    $obj = new MaxLength();
    return $obj->validationOnInit();
  }

  private function _getName($name){
    return strtolower($name);
  }

  public function create(){
    return $list->list->{$this->_getName($name)};
  }
}
