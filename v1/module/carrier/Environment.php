<?php
class Environment{
    private $_connection = array();

    private $_environment = "live";

    public function __construct(){
        $this->_connection = array(
            "live" => "http://occore.ordercup.com/api/v1/rate",
            "test" => "http://occore.ordercup1.com/api/v1/rate"
        );

        $this->coreprimeCredentials = array(
            "live"=>array(
                "username"=> "nikhil.kumar@ordercup.com",
                "password"=> "[FILTERED]",
                "token"=> "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxLCJlbWFpbCI6ImRldmVsb3BlcnNAb3JkZXJjdXAuY29tIiwiaXNzIjoiT3JkZXJDdXAgb3IgaHR0cHM6Ly93d3cub3JkZXJjdXAuY29tLyIsImlhdCI6MTQ5Njk5MzU0N30.cpm3XYPcLlwb0njGDIf8LGVYPJ2xJnS32y_DiBjSCGI",
                "account_number"=> "K906430",
                "master_carrier_account_number"=> "K906430",
                "latest_time"=> "06:00:00",
                "earliest_time"=> "11:00:00",
                "authentication_token"=> "F8358860-C73B-4470-AE5F-8F8F6D0E9DF1",
                "carrier_account_type"=> "0"
            ),
            "test"=>array(
                "username"=> "nikhil.kumar@ordercup.com",
                "password"=> "[FILTERED]",
                "token"=> "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyLCJlbWFpbCI6InNtYXJnZXNoQGdtYWlsLmNvbSIsImlzcyI6Ik9yZGVyQ3VwIG9yIGh0dHBzOi8vd3d3Lm9yZGVyY3VwLmNvbS8iLCJpYXQiOjE1MDI4MjQ3NTJ9.qGTEGgThFE4GTWC_jR3DIj9NpgY9JdBBL07Hd-6Cy-0",
                "account_number"=> "B069807",
                "master_carrier_account_number"=> "B069807",
                "latest_time"=> "06:00:00",
                "earliest_time"=> "11:00:00"
            )
        );
    }

    public function getApiUrl(){
        return $this->_connection[$this->_environment];
    }

    public function getCoreprimeCredentials(){
        return $this->coreprimeCredentials[$this->_environment];
    }
}
?>