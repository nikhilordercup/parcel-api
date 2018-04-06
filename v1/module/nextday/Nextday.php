<?php
final class Nextday extends Booking
{

    private $_param = array();
    protected static $_ccf = NULL;

    public function __construct($data){
        $this->_parentObj = parent::__construct(array("email" => $data->email, "access_token" => $data->access_token));
        $this->_param = $data;
    }

    private function _ccf(){
        if(self::$_ccf===NULL){
            self::$_ccf = new CustomerCostFactor();
        }
        return self::$_ccf;
    }

    private function _calculateCcf($data,$courier_code){
        return $this->_ccf()->calculate($data, $courier_code, $this->_param->customer_id, $this->_param->company_id);
    }

    private function _searchUkMail(){
        $obj = new Ukmail();
        $data = $obj->searchService($this->_param);
        if($data["status"]=="success"){
            print_r($this->_calculateCcf($data["response"],'ukmail'));
        }
    }

    public function searchNextdayAvailableCarrier(){
        $response = array();
        $errorResponse = array();
        $results = array();

        $this->_param->carrier = 'ukmail';

        if(isset($this->_param->carrier)){
            switch($this->_param->carrier) {
                case 'ukmail' :
                    array_push($results, $this->_searchUkMail());
                    break;
            };
        }

        foreach($results as $item){
            if($item["status"]=="error"){
                array_push($errorResponse, array("status"=>"error", "response"=>$item["response"], "carrier_name"=>$item["carrier_name"]));
                break;
                return $errorResponse;
            }else{
                array_push($response, array("response"=>$item["response"],"carrier_code"=>$item["carrier_code"]));
            }
        }

        return $response;
    }
}
?>