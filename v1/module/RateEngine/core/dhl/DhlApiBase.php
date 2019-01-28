<?php
namespace v1\module\RateEngine\core\dhl;

class DhlApiBase 
{
    private $testUrl = 'http://xmlpitest-ea.dhl.com/XMLShippingServlet';
    private $liveUrl = 'https://xmlpi-ea.dhl.com/XMLShippingServlet';
    protected $responseData = [];
                
    public function postDataToDhl($xmlRequest)
    {        
        $url = $this->getUrl();               
        $headers = array(
            "Content-type: text/xml",
            "Content-length: " . strlen($xmlRequest),
            "Connection: close",
        );
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $data = curl_exec($ch); 
        $array_data = json_decode(json_encode(simplexml_load_string($data)), true); 
        //print_r($array_data);  echo 'postDataToDhl'; die;
        return $array_data;
    }
    
    private function getUrl()
    {
        if(ENV == 'live')
        {
            return $this->liveUrl;
        }
        return $this->testUrl;
    }
    
}
