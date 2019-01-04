<?php
namespace \validation\src\lib;

class FactoryValidation{
  var $list = new stdClass;

  public function __construct(){
    $this->list->minlength = new MinLength();
    $list->list->maxlength = new MaxLength();
  }

  private function _getName($name){
    return strtolower($name);
  }

  public function create(){
    return $list->list->{$this->_getName($name)};
  }
}
