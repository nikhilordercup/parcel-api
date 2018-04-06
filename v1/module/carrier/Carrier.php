<?php
class Carrier{
    protected static $_environment = NULL;
    private function _getEnvironment(){
        if(self::$_environment===NULL){
            self::$_environment = new Environment();
        }
        return self::$_environment;
    }

    private function _getApiUrl(){
        return $this->_getEnvironment()->getApiUrl();
    }

    protected function _send($data){
        $data_string = json_encode($data);

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
print_r(curl_exec ($ch));die;
        curl_close ($ch);
        //return $this->_calculateCcf($server_output->rate, $customer_id, $company_id);
        return $server_output->rate;
    }
}
?>