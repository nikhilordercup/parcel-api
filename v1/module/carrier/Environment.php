<?php
class Environment{
    private $_connection = array();
    private $_environment = "test";

    public function __construct(){
        $this->_connection = array(
            "live" => "http://occore.ordercup.com/api/v1/rate",
            "test" => "http://occore.ordercup1.com/api/v1/rate"
        );
    }

    public function getApiUrl(){
        return $this->_connection[$this->_environment];
    }
}
?>