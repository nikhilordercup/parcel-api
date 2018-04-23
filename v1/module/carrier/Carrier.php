<?php
class Carrier{
    protected static $_environment = NULL;
    private $_postParam = array();

    private function _getEnvironment(){
        if(self::$_environment===NULL){
            self::$_environment = new Environment();
        }
        return self::$_environment;
    }

    private function _getApiUrl(){
        return $this->_getEnvironment()->getApiUrl();
    }

    private function _getCoreprimeCredentials(){
        $credentials = array_filter($this->_getEnvironment()->getCoreprimeCredentials(), function($val){
            $val = trim($val);
            return $val != '';
        });

        if(count($credentials)>0){
            $this->_setCoreprimeCredentials($credentials);
            return true;
        }else{
            return false;
        }
    }

    private function _setCoreprimeCredentials($credential){
        $credential = (object)$credential;
        $this->_postParam->credentials = new stdClass();

        foreach($credential as $key=>$item){
            $this->_postParam->credentials->$key = $item;
        }
    }

    private function _setCurrency(){
        $this->_postParam->currency = "GBP";
    }

    private function _setMethodType(){
        $this->_postParam->method_type = "post";
    }

    protected function _send($data){
        $this->_postParam = $data;
        if($this->_getCoreprimeCredentials()){
            $this->_setCurrency();
            $this->_setMethodType();

            $data_string = json_encode($this->_postParam);
//echo $data_string;die;
            $ch = curl_init($this->_getEnvironment()->getApiUrl());
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = json_decode(curl_exec ($ch));
            curl_close ($ch);
            //return $this->_calculateCcf($server_output->rate, $customer_id, $company_id);
            return $server_output->rate;
        }else{
            return array("status"=>"error","message"=>"Credential not found");
        }

    }
}
?>