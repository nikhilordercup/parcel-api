<?php
namespace v1\module\validation\src\lib\text;

use v1\module\validation\src\lib\Textbox;
use v1\module\validation\src\lib\TemplateOptions;

final class Textbox{

  private $_elementParams = array();

  private function _initConstructorParams(array $config){
    $this->_elementParams = (object) array(
      "key"  => "text",
      "type" => "input",
      "templateOptions" => array()
    );

    if(isset($config->templateOptions)){
      $this->_elementParams->templateOptions = TemplateOptions::getInstance()->setConfig($config->templateOptions);
    }

    print_r($this->_elementParams);die;
  }
}
