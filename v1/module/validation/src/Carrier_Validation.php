<?php
namespace v1\module\validation;

use v1\module\validation\src\lib\Factory_Validation;

class Carrier_Validation{

  public function validate($param){print_r($param);die;
    $factoryObj = new Factory_Validation();
  }
}
?>
