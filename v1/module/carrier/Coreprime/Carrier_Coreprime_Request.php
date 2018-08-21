<?php
/**
 * Created by PhpStorm.
 * User: nishant
 * Date: 05/06/18
 * Time: 8:36 PM
 */

Class Carrier_Coreprime_Request{

    private $_environment = array(
        "live" =>  array(
            "authorization_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImRldmVsb3BlcnNAb3JkZXJjdXAuY29tIiwiaXNzIjoiT3JkZXJDdXAgb3IgaHR0cHM6Ly93d3cub3JkZXJjdXAuY29tLyIsImlhdCI6MTQ5Njk5MzU0N30.cpm3XYPcLlwb0njGDIf8LGVYPJ2xJnS32y_DiBjSCGI",
            "access_url" => "http://occore.ordercup.com/api/v1/"
        ),
        "stagging" =>  array(
            "authorization_token" => "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6Im1hcmdlc2guc29uYXdhbmVAb3JkZXJjdXAuY29tIiwiaXNzIjoiT3JkZXJDdXAgb3IgaHR0cHM6Ly93d3cub3JkZXJjdXAuY29tLyIsImlhdCI6MTQ5Mzk2ODgxMX0.EJc4SVQXIwZibVuXFxkTo8UjKvH8S9gWyuFn9bsi63g",
            "access_url" => "http://occore.ordercup1.com/api/v1"
            //  "/rate"  "/label"
        )
    );



    Public

    function _postRequest($url, $data_string){ 
        $this->apiConn = ENV;
		

        $this->authorization_token = $this->_environment[$this->apiConn]["authorization_token"];
        $this->access_url = $this->_environment[$this->apiConn]["access_url"];

        return $this->_send($url, $data_string);
    }

    private function _send($url, $data_string){
        $url = "$this->access_url/$url";
        //print_r($url);die;
        //echo $data_string;die;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: '.$this->authorization_token,
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec ($ch);
		//print_r($server_output);die;
        curl_close ($ch);
        return $server_output;
    }
}
