<?php
class Auth extends Singleton{

  var $username;
  var $password;
  var $wsdl_url;

  private function _getLoginStr(){
    $xmlPostString =  '<soapenv:Envelope
      xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
      xmlns:ser="http://www.UKMail.com/Services/Contracts/ServiceContracts"
      xmlns:dat="http://www.UKMail.com/Services/Contracts/DataContracts">
      <soapenv:Header/>
      <soapenv:Body>
        <ser:Login>
          <ser:loginWebRequest>
            <dat:Password>'.$this->username.'</dat:Password>
            <dat:Username>'.$this->password.'</dat:Username>
          </ser:loginWebRequest>
        </ser:Login>
      </soapenv:Body>
    </soapenv:Envelope>';
    return $xmlPostString;
  }

  private function _initConstructorParams($config){
    $constructor = array(
      "username" => "123456",
      "password" => "pcs@pcs",
      "wsdl_url" => "https://qa.ukmail.com/register"
    );

    foreach($constructor as $key => $val){
      if(isset($config[$key])){
        $constructor[$key] = $config[$key];
      }
    }
    return array_values($constructor);
  }

  public function login(array $config = []){
    //echo ENV; print_r($config);die;

    list(
      $username,
      $password,
      $wsdl_url
      ) = $this->_initConstructorParams($config);

    $this->username = $username;
    $this->password = $password;
    $this->wsdl_url = $wsdl_url;

    Send::getInstance()->postRequest(
      array(
        "username" => $username,
        "password" => $password,
        "wsdl_url" => $wsdl_url,
        "xml_string" => $this->_getLoginStr(),
      )
    );
  }
}
