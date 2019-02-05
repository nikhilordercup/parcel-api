<?php
class A extends Singleton{

  public function test(){
    //echo 12345;die;
  }
  /*private function _getUrl(){
    $this->url = array(
      "dev" => array(
        "wsdl_url" => "https://qa-api.ukmail.com/Services/UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl"
      ),
      "live" => array(
        "wsdl_url" => "https://api.ukmail.com/Services/UKMAuthenticationServices/UKMAuthenticationService.svc?wsdl"
      )
    );
    return $this->url[ENV];
  }

  public function login(){
    $soapStr = '<soapenv:Envelope
      xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
      xmlns:ser="http://www.UKMail.com/Services/Contracts/ServiceContracts"
      xmlns:dat="http://www.UKMail.com/Services/Contracts/DataContracts">
      <soapenv:Header/>
      <soapenv:Body>
        <ser:Login>
          <ser:loginWebRequest>
            <dat:Password>USERNAME</dat:Password>
            <dat:Username>PASSWORD</dat:Username>
          </ser:loginWebRequest>
        </ser:Login>
      </soapenv:Body>
    </soapenv:Envelope>';
    echo $soapStr;die;

  }*/
}
