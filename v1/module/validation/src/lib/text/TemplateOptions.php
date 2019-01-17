<?php
namespace v1\module\validation\src\lib\text;

use v1\module\validation\src\lib\TemplateOptions;

class TemplateOptions{
  protected $_tOptions = NULL;
  public $tOptionsObj;

  public static function getInstance(){
    if(self::_tOptions===NULL){
      self::_tOptions = new TemplateOptions();
    }

    $this->tOptionsObj = self::_tOptions;
    return $this->tOptionsObj;
  }

  public function setConfig(array $config){
    $baseConfig = array(
      "label",
      "placeholder"
    );

    $oConfig = array_keys($config);

    $result = array_intersect($oConfig, $baseConfig);
    print_r($result);die;

  }
}
